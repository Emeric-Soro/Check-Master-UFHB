<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/AuditLog.php';

use Scolarite;
use AnneeAcademique;
use AuditLog;

class InscriptionService
{
    private $db;
    private $scolarite;
    private $anneeAcademique;
    private $auditLog;

    public function __construct($db)
    {
        $this->db = $db;
        $this->scolarite = new Scolarite($db);
        $this->anneeAcademique = new AnneeAcademique($db);
        $this->auditLog = new AuditLog($db);
    }

    /**
     * Get all data needed for the inscription index page.
     */
    public function getIndexData(array $queryParams): array
    {
        $data = [
            'etudiantsNonInscrits' => $this->scolarite->getEtudiantsNonInscrits(),
            'niveaux' => $this->scolarite->getNiveauxEtudes(),
            'etudiantsInscrits' => $this->scolarite->getEtudiantsInscrits(),
            'listeAnnees' => $this->anneeAcademique->getAllAnneeAcademiques(),
            'etudiantInfo' => null,
            'inscriptionAModifier' => null,
        ];

        if (isset($queryParams['num_etu'])) {
            $data['etudiantInfo'] = $this->scolarite->getInfoEtudiant($queryParams['num_etu']);
        }

        if (
            isset($queryParams['modalAction']) && $queryParams['modalAction'] === 'modifier'
            && isset($queryParams['id'])
        ) {
            $data['inscriptionAModifier'] = $this->scolarite->getInscriptionById($queryParams['id']);
            if ($data['inscriptionAModifier']) {
                $data['etudiantInfo'] = $this->scolarite->getInfoEtudiant($data['inscriptionAModifier']['id_etudiant']);
            }
        }

        return $data;
    }

    /**
     * Retrieve inscription for receipt printing.
     *
     * @return array|null The inscription data, or null if not found.
     */
    public function getInscriptionForReceipt(int $idInscription): ?array
    {
        return $this->scolarite->getInscriptionById($idInscription);
    }

    /**
     * Log a print action.
     */
    public function logPrint(int $idUtilisateur, string $status): void
    {
        $this->auditLog->logImpression($idUtilisateur, 'inscriptions', $status);
    }

    /**
     * Delete an inscription.
     */
    public function supprimerInscription(int $id, int $idUtilisateur): array
    {
        if ($this->scolarite->supprimerInscription($id)) {
            $this->auditLog->logSuppression($idUtilisateur, 'inscriptions', 'Succès');
            return ['success' => true, 'message' => 'Inscription supprimée avec succès.'];
        }

        $this->auditLog->logSuppression($idUtilisateur, 'inscriptions', 'Erreur');
        return ['success' => false, 'message' => "Erreur lors de la suppression de l'inscription."];
    }

    /**
     * Create a new inscription (enrol a student).
     */
    public function traiterInscription(array $postData, int $idUtilisateur): array
    {
        try {
            // Validation
            if (
                empty($postData['etudiant']) || empty($postData['niveau']) ||
                empty($postData['premier_versement']) || empty($postData['annee_academique']) ||
                empty($postData['methode_paiement'])
            ) {
                return ['success' => false, 'message' => 'Tous les champs sont obligatoires.'];
            }

            $id_etudiant = $postData['etudiant'];
            $id_niveau = $postData['niveau'];
            $id_annee_acad = $postData['annee_academique'];
            $montant_premier_versement = floatval($postData['premier_versement']);
            $nombre_tranches = isset($postData['nombre_tranches']) ? intval($postData['nombre_tranches']) : 1;
            $reste_a_payer = str_replace([' ', "\xC2\xA0", "\xE2\x80\xAF", "\xE2\x80\x89"], '', $postData['reste_payer']);
            $methode_paiement = $postData['methode_paiement'];

            // Check for duplicate inscription
            if ($this->scolarite->estEtudiantInscritPourAnnee($id_etudiant, $id_annee_acad)) {
                $this->auditLog->logCreation($idUtilisateur, 'inscriptions', 'Erreur');
                return ['success' => false, 'message' => 'Cet étudiant est déjà inscrit pour cette année académique.'];
            }

            // Create inscription
            $id_inscription = $this->scolarite->creerInscription(
                $id_etudiant,
                $id_niveau,
                $id_annee_acad,
                $montant_premier_versement,
                $nombre_tranches,
                $reste_a_payer,
                $methode_paiement
            );

            if ($id_inscription) {
                $this->creerEcheancesSiNecessaire($id_inscription, $id_niveau, $montant_premier_versement, $nombre_tranches);
                $this->auditLog->logCreation($idUtilisateur, 'inscriptions', 'Succès');
                return ['success' => true, 'message' => 'Inscription créée avec succès.'];
            }

            $this->auditLog->logCreation($idUtilisateur, 'inscriptions', 'Erreur');
            return ['success' => false, 'message' => "Erreur lors de la création de l'inscription."];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Une erreur est survenue : ' . $e->getMessage()];
        }
    }

    /**
     * Modify an existing inscription.
     */
    public function modifierInscription(array $postData, int $idUtilisateur): array
    {
        try {
            if (empty($postData['id_inscription']) || empty($postData['niveau']) || empty($postData['premier_versement'])) {
                return ['success' => false, 'message' => 'Tous les champs sont obligatoires.'];
            }

            $id_inscription = $postData['id_inscription'];
            $id_annee_acad = $postData['annee_academique'];
            $id_niveau = $postData['niveau'];
            $montant_premier_versement = floatval($postData['premier_versement']);
            $nombre_tranches = isset($postData['nombre_tranches']) ? intval($postData['nombre_tranches']) : 1;
            $methode_paiement = $postData['methode_paiement'];

            if ($this->scolarite->modifierInscription($id_inscription, $id_niveau, $id_annee_acad, $montant_premier_versement, $nombre_tranches, $methode_paiement)) {
                // Delete old instalments and recreate
                $this->scolarite->supprimerEcheances($id_inscription);
                $this->creerEcheancesSiNecessaire($id_inscription, $id_niveau, $montant_premier_versement, $nombre_tranches);

                $this->auditLog->logModification($idUtilisateur, 'inscriptions', 'Succès');
                return ['success' => true, 'message' => 'Inscription modifiée avec succès.'];
            }

            $this->auditLog->logModification($idUtilisateur, 'inscriptions', 'Erreur');
            return ['success' => false, 'message' => "Erreur lors de la modification de l'inscription."];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Une erreur est survenue : ' . $e->getMessage()];
        }
    }

    /**
     * Retrieve student info as an API response array.
     */
    public function getEtudiantInfo(string $numEtu): array
    {
        $etudiant = $this->scolarite->getInfoEtudiant($numEtu);
        if ($etudiant) {
            return ['success' => true, 'etudiant' => $etudiant];
        }
        return ['success' => false, 'message' => 'Étudiant non trouvé'];
    }

    /**
     * Fetch the refreshed list of enrolled students.
     */
    public function getEtudiantsInscrits(): array
    {
        return $this->scolarite->getEtudiantsInscrits();
    }

    // ---------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------

    /**
     * Create instalment schedule when nombre_tranches > 1.
     */
    private function creerEcheancesSiNecessaire($idInscription, $idNiveau, float $montantPremierVersement, int $nombreTranches): void
    {
        if ($nombreTranches <= 1) {
            return;
        }

        $montant_total = $this->scolarite->getMontantScolarite($idNiveau);
        $reste_a_payer = $montant_total - $montantPremierVersement;
        $montant_tranche = $reste_a_payer / ($nombreTranches - 1);

        $date_echeance = date('Y-m-d', strtotime('+3 months'));
        for ($i = 1; $i < $nombreTranches; $i++) {
            $this->scolarite->creerEcheance($idInscription, $montant_tranche, $date_echeance);
            $date_echeance = date('Y-m-d', strtotime($date_echeance . ' +3 months'));
        }
    }
}