<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/AcademicYear.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../utils/NotificationService.php';

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
        $this->emailService = new \EmailService();
    }

    /**
     * Get all data needed for the inscription index page.
     */
    public function getIndexData(array $queryParams): array
    {
        $selectedYearId = \AcademicYear::getSelectedIdFromSession();
        $data = [
            'etudiantsNonInscrits' => $this->scolarite->getEtudiantsNonInscrits($selectedYearId),
            'niveaux' => $this->scolarite->getNiveauxEtudes(),
            'etudiantsInscrits' => $this->scolarite->getEtudiantsInscrits($selectedYearId),
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
        $inscription = $this->scolarite->getInscriptionById($id);
        $inscriptionYearId = is_array($inscription) && !empty($inscription['id_annee_acad'])
            ? (int) $inscription['id_annee_acad']
            : null;
        $selectedYearId = \AcademicYear::getSelectedIdFromSession();

        if ($selectedYearId !== null && $inscriptionYearId !== null && $selectedYearId !== $inscriptionYearId) {
            $this->auditLog->logSuppression($idUtilisateur, 'inscriptions', 'Erreur');
            return ['success' => false, 'message' => "L'inscription ne correspond pas à l'année académique actuellement sélectionnée."];
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->db, $inscriptionYearId, 'une inscription');
        if (!$writeGuard['success']) {
            $this->auditLog->logSuppression($idUtilisateur, 'inscriptions', 'Erreur');
            return ['success' => false, 'message' => $writeGuard['message']];
        }

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
            $methode_paiement = $postData['methode_paiement'];
            $num_piece = isset($postData['num_piece']) ? trim((string) $postData['num_piece']) : null;
            $selectedYearId = \AcademicYear::getSelectedIdFromSession();

            if ($selectedYearId !== null && (int) $selectedYearId > 0 && (int) $selectedYearId !== (int) $id_annee_acad) {
                return ['success' => false, 'message' => "L'inscription doit être créée dans l'année académique actuellement sélectionnée."];
            }

            $writeGuard = \AcademicYear::ensureWritableYear($this->db, $id_annee_acad, 'une inscription');
            if (!$writeGuard['success']) {
                return ['success' => false, 'message' => $writeGuard['message']];
            }

            $montant_total = (float) $this->scolarite->getMontantScolarite($id_niveau);
            if ($montant_premier_versement > $montant_total) {
                return [
                    'success' => false,
                    'message' => 'Le montant ne peut pas dépasser le montant total (' . number_format($montant_total, 0, ',', ' ') . ' FCFA).'
                ];
            }

            // Check for duplicate inscription
            if ($this->scolarite->estEtudiantInscritPourAnnee($id_etudiant, $id_annee_acad)) {
                $this->auditLog->logCreation($idUtilisateur, 'inscriptions', 'Erreur');
                return ['success' => false, 'message' => 'Cet étudiant est déjà inscrit pour cette année académique.'];
            }

            // Create inscription
            $inscriptionSuccess = $this->scolarite->creerInscription(
                $id_etudiant,
                $id_niveau,
                $id_annee_acad,
                $montant_premier_versement,
                $methode_paiement,
                $num_piece !== '' ? $num_piece : null
            );

            if ($inscriptionSuccess) {
                // Build composite key for echeances (first versement = 1)
                $compositeKey = $id_etudiant . '-' . $id_annee_acad . '-1';
                $this->creerEcheancesSiNecessaire($compositeKey, $id_niveau, $montant_premier_versement, $nombre_tranches);
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
            if (
                empty($postData['id_inscription']) ||
                empty($postData['niveau']) ||
                empty($postData['premier_versement']) ||
                empty($postData['annee_academique']) ||
                empty($postData['methode_paiement'])
            ) {
                return ['success' => false, 'message' => 'Tous les champs sont obligatoires.'];
            }

            $id_inscription = $postData['id_inscription'];
            $id_annee_acad = $postData['annee_academique'];
            $id_niveau = $postData['niveau'];
            $montant_premier_versement = floatval($postData['premier_versement']);
            $nombre_tranches = isset($postData['nombre_tranches']) ? intval($postData['nombre_tranches']) : 1;
            $methode_paiement = $postData['methode_paiement'];
            $num_piece = isset($postData['num_piece']) ? trim((string) $postData['num_piece']) : null;
            $selectedYearId = \AcademicYear::getSelectedIdFromSession();
            $existingInscription = $this->scolarite->getInscriptionById($id_inscription);
            $existingYearId = is_array($existingInscription) && !empty($existingInscription['id_annee_acad'])
                ? (int) $existingInscription['id_annee_acad']
                : null;

            if ($selectedYearId !== null && $existingYearId !== null && $selectedYearId !== $existingYearId) {
                return ['success' => false, 'message' => "L'inscription modifiée ne correspond pas à l'année académique actuellement sélectionnée."];
            }
            if ($selectedYearId !== null && (int) $selectedYearId > 0 && (int) $selectedYearId !== (int) $id_annee_acad) {
                return ['success' => false, 'message' => "L'inscription doit rester dans l'année académique actuellement sélectionnée."];
            }

            $currentYearGuard = \AcademicYear::ensureWritableYear($this->db, $existingYearId, 'une inscription');
            if (!$currentYearGuard['success']) {
                return ['success' => false, 'message' => $currentYearGuard['message']];
            }

            $writeGuard = \AcademicYear::ensureWritableYear($this->db, $id_annee_acad, 'une inscription');
            if (!$writeGuard['success']) {
                return ['success' => false, 'message' => $writeGuard['message']];
            }

            $montant_total = (float) $this->scolarite->getMontantScolarite($id_niveau);
            if ($montant_premier_versement > $montant_total) {
                return [
                    'success' => false,
                    'message' => 'Le montant ne peut pas dépasser le montant total (' . number_format($montant_total, 0, ',', ' ') . ' FCFA).'
                ];
            }

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
        return $this->scolarite->getEtudiantsInscrits(\AcademicYear::getSelectedIdFromSession());
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

    public function notifierInscription(string $numEtu, string $niveau, float $montantTotal, float $montantVerse, float $solde, string $anneeLabel): void
    {
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_carte_etud = ? OR num_ident_etud = ?");
            $stmt->execute([$numEtu, $numEtu]);
            $etu = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$etu || empty($etu['email_etu'])) {
                return;
            }
            $nom = trim(($etu['prenom_etu'] ?? '') . ' ' . ($etu['nom_etu'] ?? ''));
            $this->emailService->sendTemplate('INSCRIPTION_CONFIRMATION', $etu['email_etu'], [
                'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'annee_academique' => htmlspecialchars($anneeLabel, ENT_QUOTES, 'UTF-8'),
                'niveau' => htmlspecialchars($niveau, ENT_QUOTES, 'UTF-8'),
                'montant_total' => number_format($montantTotal, 0, ',', ' '),
                'montant_verse' => number_format($montantVerse, 0, ',', ' '),
                'solde' => number_format($solde, 0, ',', ' '),
            ]);
        } catch (\Exception $e) {
            error_log('Erreur notifierInscription: ' . $e->getMessage());
        }
    }

    public function notifierPaiement(string $numEtu, float $montant, string $modePaiement, float $solde): void
    {
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_carte_etud = ? OR num_ident_etud = ?");
            $stmt->execute([$numEtu, $numEtu]);
            $etu = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$etu || empty($etu['email_etu'])) {
                return;
            }
            $nom = trim(($etu['prenom_etu'] ?? '') . ' ' . ($etu['nom_etu'] ?? ''));
            $this->emailService->sendTemplate('RECU_PAIEMENT', $etu['email_etu'], [
                'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'montant' => number_format($montant, 0, ',', ' '),
                'date_paiement' => date('d/m/Y H:i'),
                'mode_paiement' => htmlspecialchars((string)$modePaiement, ENT_QUOTES, 'UTF-8'),
                'solde' => number_format($solde, 0, ',', ' '),
            ]);
        } catch (\Exception $e) {
            error_log('Erreur notifierPaiement: ' . $e->getMessage());
        }
    }

    public function notifierInscriptionValidee(string $numEtu, string $anneeLabel): void
    {
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_carte_etud = ? OR num_ident_etud = ?");
            $stmt->execute([$numEtu, $numEtu]);
            $etu = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$etu || empty($etu['email_etu'])) {
                return;
            }
            $nom = trim(($etu['prenom_etu'] ?? '') . ' ' . ($etu['nom_etu'] ?? ''));
            $this->emailService->sendTemplate('INSCRIPTION_VALIDEE', $etu['email_etu'], [
                'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'annee_academique' => htmlspecialchars($anneeLabel, ENT_QUOTES, 'UTF-8'),
            ]);
        } catch (\Exception $e) {
            error_log('Erreur notifierInscriptionValidee: ' . $e->getMessage());
        }
    }

    public function notifierRelanceImpaye(string $numEtu, float $total, float $verse, float $solde, string $anneeLabel): void
    {
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_carte_etud = ? OR num_ident_etud = ?");
            $stmt->execute([$numEtu, $numEtu]);
            $etu = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$etu || empty($etu['email_etu'])) {
                return;
            }
            $nom = trim(($etu['prenom_etu'] ?? '') . ' ' . ($etu['nom_etu'] ?? ''));
            $this->emailService->sendTemplate('RELANCE_IMPAYE', $etu['email_etu'], [
                'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'annee_academique' => htmlspecialchars($anneeLabel, ENT_QUOTES, 'UTF-8'),
                'montant_total' => number_format($total, 0, ',', ' '),
                'montant_verse' => number_format($verse, 0, ',', ' '),
                'solde' => number_format($solde, 0, ',', ' '),
            ]);
        } catch (\Exception $e) {
            error_log('Erreur notifierRelanceImpaye: ' . $e->getMessage());
        }
    }
}
