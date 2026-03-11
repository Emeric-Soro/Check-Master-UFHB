<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use Etudiant;
use Scolarite;
use PersAdmin;
use AuditLog;
use EmailService;
use PDO;

/**
 * Service métier pour la gestion des candidatures de soutenance.
 *
 * Contient toute la logique métier extraite de GestionCandidaturesController :
 * - Récupération de toutes les candidatures
 * - Construction des données par étape (scolarité, stage, semestre)
 * - Génération du résumé final
 * - Détermination de la décision (Validée / Rejetée)
 * - Envoi des résultats finaux (mise à jour BDD + email)
 * - Récupération directe d'une candidature par numéro étudiant
 */
class GestionCandidaturesService
{
    private $db;
    private $etudiant;
    private $scolarite;
    private $emailService;
    private $persAdmin;
    private $auditLog;

    public function __construct($db)
    {
        $this->db = $db;
        $this->etudiant = new Etudiant($this->db);
        $this->scolarite = new Scolarite($this->db);
        $this->emailService = new EmailService();
        $this->persAdmin = new PersAdmin($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    // ──────────────────────────────────────────────────────────────
    //  Lecture
    // ──────────────────────────────────────────────────────────────

    /**
     * Retourne la liste complète des candidatures.
     */
    public function getAllCandidatures(): array
    {
        return \AcademicYear::filterRowsBySelectedYear($this->etudiant->getAllCandidature(), 'id_annee_acad');
    }

    public function ensureWritableCandidature(string $numEtu): array
    {
        $etudiant = $this->etudiant->getEtudiantByNumEtu($numEtu);
        $studentYearId = null;
        if (is_array($etudiant) && isset($etudiant['id_annee_acad']) && is_numeric($etudiant['id_annee_acad'])) {
            $studentYearId = (int) $etudiant['id_annee_acad'];
        }

        $selectedYearId = \AcademicYear::getSelectedIdFromSession();
        if ($selectedYearId !== null && $studentYearId !== null && $selectedYearId !== $studentYearId) {
            return [
                'success' => false,
                'message' => "La candidature ne correspond pas à l'année académique actuellement sélectionnée.",
            ];
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->db, $studentYearId, 'une candidature de soutenance');
        if (!$writeGuard['success']) {
            return [
                'success' => false,
                'message' => (string) $writeGuard['message'],
            ];
        }

        return ['success' => true, 'message' => ''];
    }

    /**
     * Retourne les données d'un étudiant par son numéro.
     */
    public function getEtudiantByNumEtu(string $numEtu)
    {
        return $this->etudiant->getEtudiantByNumEtu($numEtu);
    }

    /**
     * Retourne la dernière candidature d'un étudiant.
     */
    public function getLastCandidatureByNumEtu(string $numEtu)
    {
        $sql = "SELECT * FROM candidature_soutenance WHERE num_etu = ? ORDER BY date_candidature DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$numEtu]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retourne le résumé d'une candidature.
     */
    public function getResumeCandidature(string $numEtu)
    {
        return $this->etudiant->getResumeCandidature($numEtu);
    }

    // ──────────────────────────────────────────────────────────────
    //  Données par étape
    // ──────────────────────────────────────────────────────────────

    /**
     * Construit les données correspondant à une étape donnée.
     *
     * @param string $numEtu   Numéro de l'étudiant
     * @param int    $etape    Numéro d'étape (1-4)
     * @param array  $etapesValidation  État de validation par étape (issu de la session)
     * @return array|null
     */
    public function getEtapeData(string $numEtu, int $etape, array $etapesValidation = []): ?array
    {
        switch ($etape) {
            case 1: // Scolarité
                return $this->buildScolariteData($numEtu);

            case 2: // Stage
                return $this->buildStageData($numEtu);

            case 3: // Semestre
                return $this->buildSemestreData($numEtu);

            case 4: // Résumé final
                return $this->genererResumeFinal($numEtu, $etapesValidation);

            default:
                return null;
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Audit helpers
    // ──────────────────────────────────────────────────────────────

    public function logValidation(int $idUtilisateur): void
    {
        $this->auditLog->logValidation($idUtilisateur, 'candidature_soutenance', 'Succès');
    }

    public function logRejet(int $idUtilisateur): void
    {
        $this->auditLog->logRejet($idUtilisateur, 'candidature_soutenance', 'Succès');
    }

    public function logAction(int $idUtilisateur, string $action): void
    {
        $this->auditLog->logAction($idUtilisateur, $action, 'candidature_soutenance', 'Succès');
    }

    // ──────────────────────────────────────────────────────────────
    //  Envoi des résultats finaux
    // ──────────────────────────────────────────────────────────────

    /**
     * Traite et envoie les résultats finaux pour une candidature.
     *
     * @param string $numEtu            Numéro de l'étudiant
     * @param string $loginUtilisateur  Login de l'administrateur connecté
     * @param array  $etapesValidation  État de validation par étape (issu de la session)
     */
    public function envoyerResultatsFinaux(string $numEtu, string $loginUtilisateur, array $etapesValidation): void
    {
        $resume = $this->genererResumeFinal($numEtu, $etapesValidation);
        $decision = $this->determinerDecision($resume);

        $persAdmin = $this->persAdmin->getPersAdminByLogin($loginUtilisateur);

        // Récupérer la dernière candidature
        $candidature = $this->etudiant->getLastCandidatureByNumEtu($numEtu);
        $idCandidature = $candidature['id_candidature'] ?? null;

        // Mettre à jour le statut
        $this->etudiant->traiterCandidature($idCandidature, $decision, 'Évaluation complète terminée', $persAdmin->id_pers_admin);

        // Enregistrer le résumé
        $this->etudiant->saveResumeCandidature($idCandidature, $numEtu, $resume, $decision);

        // Envoyer l'email
        $this->envoyerEmailResultat($numEtu, $resume, $decision);
    }

    // ──────────────────────────────────────────────────────────────
    //  Méthodes internes
    // ──────────────────────────────────────────────────────────────

    private function buildScolariteData(string $numEtu): array
    {
        $scolarite = $this->scolarite->getScolariteEtudiant($numEtu);

        return [
            'status'          => ($scolarite && $scolarite['reste_a_payer'] > 0) ? 'En retard' : 'À jour',
            'montant'         => $scolarite ? number_format($scolarite['montant_total'], 0, ',', ' ') . ' FCFA' : '0 FCFA',
            'montant_paye'    => $scolarite ? number_format($scolarite['montant_paye'], 0, ',', ' ') . ' FCFA' : '0 FCFA',
            'dernierPaiement'  => ($scolarite && $scolarite['dernier_paiement'])
                ? date('d/m/Y', strtotime($scolarite['dernier_paiement']))
                : 'Aucun paiement'
        ];
    }

    private function buildStageData(string $numEtu): array
    {
        $stage = $this->etudiant->getInfoStage($numEtu);

        return [
            'entreprise' => $stage ? $stage['nom_entreprise'] : 'Non renseigné',
            'sujet'      => $stage ? $stage['sujet_stage'] : 'Non renseigné',
            'periode'    => $stage
                ? date('d/m/Y', strtotime($stage['date_debut_stage'])) . ' - ' . date('d/m/Y', strtotime($stage['date_fin_stage']))
                : 'Non renseigné',
            'encadrant'  => $stage ? $stage['encadrant_entreprise'] : 'Non renseigné'
        ];
    }

    private function buildSemestreData(string $numEtu): array
    {
        $notes = $this->etudiant->getNotesEtudiant($numEtu);
        $semestreInfo = $this->etudiant->getSemestreActuel($numEtu);

        $semestreText = 'Non renseigné';
        if ($semestreInfo && !empty($semestreInfo['semestres'])) {
            $semestres = array_map(function ($s) {
                return $s['lib_semestre'];
            }, $semestreInfo['semestres']);
            $semestreText = implode(', ', $semestres);
        }

        return [
            'semestre' => $semestreText,
            'moyenne'  => number_format($notes['moyenne'] ?? 0, 2) . '/20',
            'unites'   => ($notes['unites_validees'] ?? 0) . '/' . ($notes['total_unites'] ?? 0) . ' crédits validés'
        ];
    }

    /**
     * Génère le résumé de toutes les étapes.
     */
    private function genererResumeFinal(string $numEtu, array $etapesValidation): array
    {
        $scolarite = $this->scolarite->getScolariteEtudiant($numEtu);
        $stage = $this->etudiant->getInfoStage($numEtu);
        $notes = $this->etudiant->getNotesEtudiant($numEtu);
        $semestreInfo = $this->etudiant->getSemestreActuel($numEtu);

        // Semestre text
        $semestreText = 'Non renseigné';
        if ($semestreInfo && !empty($semestreInfo['semestres'])) {
            $semestres = array_map(function ($s) {
                return $s['lib_semestre'];
            }, $semestreInfo['semestres']);
            $semestreText = implode(', ', $semestres);
        }

        return [
            'scolarite' => [
                'statut'           => ($scolarite && $scolarite['reste_a_payer'] > 0) ? 'En retard' : 'À jour',
                'montant_total'    => $scolarite ? number_format($scolarite['montant_total'], 0, ',', ' ') . ' FCFA' : '0 FCFA',
                'montant_paye'     => $scolarite ? number_format($scolarite['montant_paye'], 0, ',', ' ') . ' FCFA' : '0 FCFA',
                'dernier_paiement' => ($scolarite && $scolarite['dernier_paiement'])
                    ? date('d/m/Y', strtotime($scolarite['dernier_paiement']))
                    : 'Aucun paiement',
                'validation'       => $etapesValidation[1] ?? 'Non évalué'
            ],
            'stage' => [
                'entreprise' => $stage ? $stage['nom_entreprise'] : 'Non renseigné',
                'sujet'      => $stage ? $stage['sujet_stage'] : 'Non renseigné',
                'periode'    => $stage
                    ? date('d/m/Y', strtotime($stage['date_debut_stage'])) . ' - ' . date('d/m/Y', strtotime($stage['date_fin_stage']))
                    : 'Non renseigné',
                'encadrant'  => $stage ? $stage['encadrant_entreprise'] : 'Non renseigné',
                'validation' => $etapesValidation[2] ?? 'Non évalué'
            ],
            'semestre' => [
                'semestre'   => $semestreText,
                'moyenne'    => number_format($notes['moyenne'] ?? 0, 2) . '/20',
                'unites'     => ($notes['unites_validees'] ?? 0) . '/' . ($notes['total_unites'] ?? 0) . ' crédits validés',
                'validation' => $etapesValidation[3] ?? 'Non évalué'
            ]
        ];
    }

    /**
     * Détermine la décision finale à partir du résumé.
     */
    private function determinerDecision(array $resume): string
    {
        $rejets = 0;
        foreach ($resume as $etape) {
            if ($etape['validation'] === 'rejeté') {
                $rejets++;
            }
        }

        return $rejets > 0 ? 'Rejetée' : 'Validée';
    }

    /**
     * Envoie l'email de résultat à l'étudiant.
     */
    private function envoyerEmailResultat(string $numEtu, array $resume, string $decision): void
    {
        $etudiant = $this->etudiant->getEtudiantByNumEtu($numEtu);
        if (!$etudiant || empty($etudiant['email_etu'])) {
            error_log("Email non trouvé pour l'étudiant: $numEtu");
            return;
        }

        $studentName = $etudiant['prenom_etu'] . ' ' . $etudiant['nom_etu'];
        $this->emailService->sendResultEmail($etudiant['email_etu'], $studentName, $resume, $decision);
    }
}
