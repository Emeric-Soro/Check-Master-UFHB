<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Valider.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/EvaluationRapport.php';
require_once __DIR__ . '/../models/CommissionValidationMembre.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/AcademicYear.php';
require_once __DIR__ . '/../utils/EmailService.php';

use RapportEtudiant;
use EvaluationRapport;
use Valider;
use AuditLog;
use PDO;
use Exception;

/**
 * Service métier pour l'évaluation des dossiers
 *
 * Contient toute la logique métier extraite de EvaluationDossiersController :
 * - Statistiques des dossiers
 * - Récupération des dossiers à évaluer
 * - Validation et rejet de dossiers
 * - Traitement des décisions de la commission
 * - Finalisation des décisions
 */
class EvaluationDossiersService
{
    /** @var PDO */
    private $db;

    /** @var RapportEtudiant */
    private $rapportEtudiant;

    /** @var EvaluationRapport */
    private $evaluationRapport;

    /** @var AuditLog */
    private $auditLog;
    private $tableExistsCache = [];
    private $columnExistsCache = [];

    /**
     * Constructeur du service
     *
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->rapportEtudiant = new RapportEtudiant($db);
        $this->evaluationRapport = new EvaluationRapport($db);
        $this->auditLog = new AuditLog($db);
    }

    private function peutModifierDecisionCommission(int $idUtilisateur): bool
    {
        $membresCommission = new \CommissionValidationMembre($this->db);
        return $membresCommission->estAdministrateur($idUtilisateur)
            || $membresCommission->estVotantActif($idUtilisateur);
    }

    private function tableExists($tableName)
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableExistsCache[$tableName] = $exists;
            return $exists;
        } catch (Exception $e) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function columnExists($tableName, $columnName)
    {
        $key = strtolower((string) $tableName . '.' . (string) $columnName);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }
        if (!$this->tableExists($tableName)) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$key] = $exists;
            return $exists;
        } catch (Exception $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function updateRapportDecision($idRapport, $statut, $etapeIfExists)
    {
        $set = ['statut_rapport = ?'];
        $params = [$statut];

        if ($etapeIfExists !== null && $this->columnExists('rapport_etudiants', 'etape_validation')) {
            $set[] = 'etape_validation = ?';
            $params[] = $etapeIfExists;
        }

        $params[] = $idRapport;
        $sql = 'UPDATE rapport_etudiants SET ' . implode(', ', $set) . ' WHERE id_rapport = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    private function getSelectedYearId(): ?int
    {
        return \AcademicYear::getSelectedIdFromSession();
    }

    private function studentJoinCondition(string $rapportAlias = 'r', string $etudiantAlias = 'e'): string
    {
        return sprintf(
            '(%1$s.num_etu = %2$s.num_carte_etud OR %1$s.num_etu = %2$s.num_ident_etud)',
            $rapportAlias,
            $etudiantAlias
        );
    }

    private function studentCarteExpr(string $etudiantAlias = 'e'): string
    {
        return sprintf(
            "COALESCE(NULLIF(%s.num_carte_etud, ''), NULLIF(%s.num_ident_etud, ''))",
            $etudiantAlias,
            $etudiantAlias
        );
    }

    private function getFallbackStudentYearExpr(string $etudiantAlias = 'e'): string
    {
        return "
            (SELECT i.id_annee_acad
             FROM inscriptions i
             WHERE i.num_carte_etud = " . $this->studentCarteExpr($etudiantAlias) . "
             ORDER BY i.date_inscription DESC, i.id_annee_acad DESC, i.num_versement DESC
             LIMIT 1)
        ";
    }

    private function getAcademicYearFromDateExpr(string $dateExpr): string
    {
        return "
            (SELECT aa.id_annee_acad
             FROM annee_academique aa
             WHERE DATE($dateExpr) BETWEEN aa.date_deb AND aa.date_fin
             ORDER BY aa.date_deb DESC
             LIMIT 1)
        ";
    }

    private function getReportDateExpr(string $rapportAlias = 'r'): string
    {
        if ($this->columnExists('rapport_etudiants', 'date_rapport')) {
            return $rapportAlias . '.date_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_redaction_rapport')) {
            return $rapportAlias . '.date_redaction_rapport';
        }
        return 'NULL';
    }

    private function getReportAcademicYearExpr(string $rapportAlias = 'r', string $etudiantAlias = 'e', ?string $depotAlias = null): string
    {
        $candidates = [];

        if ($depotAlias !== null && $this->tableExists('deposer')) {
            $candidates[] = $this->getAcademicYearFromDateExpr($depotAlias . '.date_depot');
        }

        $dateExpr = $this->getReportDateExpr($rapportAlias);
        if ($dateExpr !== 'NULL') {
            $candidates[] = $this->getAcademicYearFromDateExpr($dateExpr);
        }

        $candidates[] = $this->getFallbackStudentYearExpr($etudiantAlias);

        return 'COALESCE(' . implode(', ', $candidates) . ')';
    }

    private function getRapportYearId($idRapport): ?int
    {
        $rapport = $this->rapportEtudiant->getRapportById($idRapport);
        if (is_object($rapport) && isset($rapport->id_annee_acad) && is_numeric($rapport->id_annee_acad)) {
            return (int) $rapport->id_annee_acad;
        }
        if (is_array($rapport) && isset($rapport['id_annee_acad']) && is_numeric($rapport['id_annee_acad'])) {
            return (int) $rapport['id_annee_acad'];
        }
        return null;
    }

    private function ensureWritableRapport($idRapport, string $context): void
    {
        $rapportYearId = $this->getRapportYearId($idRapport);
        $selectedYearId = $this->getSelectedYearId();
        if ($selectedYearId !== null && $rapportYearId !== null && $selectedYearId !== $rapportYearId) {
            throw new Exception("Le rapport ne correspond pas à l'année académique actuellement sélectionnée.");
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->db, $rapportYearId, $context);
        if (!$writeGuard['success']) {
            throw new Exception((string) $writeGuard['message']);
        }
    }

    /**
     * Récupère les données de la page index (stats + dossiers)
     *
     * @return array
     */
    public function getIndexData()
    {
        return [
            'stats' => $this->getStatistiques(),
            'dossiers' => $this->getDossiersAEvaluer()
        ];
    }

    /**
     * Récupère les statistiques des dossiers
     *
     * @return array
     */
    public function getStatistiques()
    {
        $aEvaluer = 0;
        $valides = 0;
        $aCorriger = 0;
        try {
            if ($this->columnExists('rapport_etudiants', 'etape_validation')) {
                $yearWhere = '';
                $yearParams = [];
                if (($selectedYearId = $this->getSelectedYearId()) !== null && $selectedYearId > 0) {
                    $yearWhere = " AND " . $this->getReportAcademicYearExpr('r', 'e', 'd') . " = ?";
                    $yearParams[] = $selectedYearId;
                }
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as total
                    FROM rapport_etudiants r
                    INNER JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                    LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
                    WHERE r.etape_validation IN ('approuve_communication', 'en_attente_commission')
                    {$yearWhere}
                ");
                $stmt->execute($yearParams);
                $aEvaluer = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as total
                    FROM rapport_etudiants r
                    INNER JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                    LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
                    WHERE r.etape_validation = 'valide'
                    {$yearWhere}
                ");
                $stmt->execute($yearParams);
                $valides = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as total
                    FROM rapport_etudiants r
                    INNER JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                    LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
                    WHERE r.etape_validation = 'desapprouve_commission'
                    {$yearWhere}
                ");
                $stmt->execute($yearParams);
                $aCorriger = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            } else {
                if ($this->tableExists('valider')) {
                    $yearWhere = '';
                    $yearParams = [];
                    if (($selectedYearId = $this->getSelectedYearId()) !== null && $selectedYearId > 0) {
                        $yearWhere = " AND " . $this->getReportAcademicYearExpr('r', 'e', 'd') . " = ?";
                        $yearParams[] = $selectedYearId;
                    }
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) as total
                        FROM rapport_etudiants r
                        INNER JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                        LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
                        LEFT JOIN valider v ON r.id_rapport = v.id_rapport
                        WHERE v.id_rapport IS NULL
                        {$yearWhere}
                    ");
                    $stmt->execute($yearParams);
                    $aEvaluer = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

                    $stmt = $this->db->prepare("
                        SELECT COUNT(DISTINCT v.id_rapport) as total
                        FROM valider v
                        INNER JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                        INNER JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                        LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
                        WHERE decision_validation = 'valider'
                        {$yearWhere}
                    ");
                    $stmt->execute($yearParams);
                    $valides = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

                    $stmt = $this->db->prepare("
                        SELECT COUNT(DISTINCT v.id_rapport) as total
                        FROM valider v
                        INNER JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                        INNER JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                        LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
                        WHERE decision_validation = 'rejeter'
                        {$yearWhere}
                    ");
                    $stmt->execute($yearParams);
                    $aCorriger = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
                } else {
                    $yearWhere = '';
                    $yearParams = [];
                    if (($selectedYearId = $this->getSelectedYearId()) !== null && $selectedYearId > 0) {
                        $yearWhere = " WHERE " . $this->getReportAcademicYearExpr('r', 'e', 'd') . " = ?";
                        $yearParams[] = $selectedYearId;
                    }
                    $stmt = $this->db->prepare("
                        SELECT
                            SUM(CASE WHEN COALESCE(statut_rapport, '') IN ('valider', 'valide') THEN 1 ELSE 0 END) as valides,
                            SUM(CASE WHEN COALESCE(statut_rapport, '') IN ('rejeter', 'desapprouve_commission') THEN 1 ELSE 0 END) as rejetes,
                            SUM(CASE WHEN COALESCE(statut_rapport, '') NOT IN ('valider', 'valide', 'rejeter', 'desapprouve_commission') THEN 1 ELSE 0 END) as en_cours
                        FROM rapport_etudiants r
                        INNER JOIN etudiants e ON " . $this->studentJoinCondition('r', 'e') . "
                        LEFT JOIN deposer d ON r.id_rapport = d.id_rapport
                        {$yearWhere}
                    ");
                    $stmt->execute($yearParams);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    $aEvaluer = (int) ($row['en_cours'] ?? 0);
                    $valides = (int) ($row['valides'] ?? 0);
                    $aCorriger = (int) ($row['rejetes'] ?? 0);
                }
            }
        } catch (Exception $e) {
            error_log("Erreur getStatistiques EvaluationDossiersService: " . $e->getMessage());
        }

        return [
            'a_evaluer' => $aEvaluer,
            'valides' => $valides,
            'a_corriger' => $aCorriger,
            'moyenne' => '14.5/20'
        ];
    }

    /**
     * Récupère les dossiers à évaluer avec leur statut de vote
     *
     * @return array
     */
    public function getDossiersAEvaluer()
    {
        $evaluationRapport = new EvaluationRapport($this->db);
        return \AcademicYear::filterRowsBySelectedYear($evaluationRapport->getRapportsAvecStatutVote(), 'id_annee_acad');
    }

    /**
     * Récupère l'ID enseignant à partir de l'ID utilisateur admin
     *
     * @param int $id_utilisateur
     * @return string|null
     */
    public function getEnseignantIdFromAdmin($id_utilisateur)
    {
        error_log("DEBUG: ID Utilisateur reçu: " . $id_utilisateur);

        $stmt = $this->db->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
        $stmt->execute([$id_utilisateur]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            error_log("DEBUG: Utilisateur avec ID $id_utilisateur non trouvé");
            $stmt = $this->db->query("SELECT id_enseignant FROM enseignants LIMIT 1");
            $fallback = $stmt->fetch(PDO::FETCH_ASSOC);
            return $fallback ? (string) $fallback['id_enseignant'] : null;
        }

        error_log("DEBUG: Login de l'utilisateur: " . $utilisateur['login_utilisateur']);

        $stmt = $this->db->prepare("
            SELECT e.id_enseignant, e.nom_enseignant, e.prenom_enseignant
            FROM enseignants e
            WHERE e.mail_enseignant = ?
        ");
        $stmt->execute([$utilisateur['login_utilisateur']]);
        $enseignant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($enseignant) {
            error_log("DEBUG: Enseignant trouvé: " . $enseignant['prenom_enseignant'] . " " . $enseignant['nom_enseignant'] . " (ID: " . $enseignant['id_enseignant'] . ")");
            return (string) $enseignant['id_enseignant'];
        }

        error_log("DEBUG: Aucun enseignant trouvé avec le login: " . $utilisateur['login_utilisateur']);

        $stmt = $this->db->query("SELECT id_enseignant, nom_enseignant, prenom_enseignant FROM enseignants LIMIT 1");
        $fallback = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fallback) {
            error_log("DEBUG: Utilisation du fallback - Enseignant: " . $fallback['prenom_enseignant'] . " " . $fallback['nom_enseignant'] . " (ID: " . $fallback['id_enseignant'] . ")");
            return (string) $fallback['id_enseignant'];
        }

        error_log("DEBUG: Aucun enseignant disponible dans la base de données");
        return null;
    }

    /**
     * Valide un dossier
     *
     * @param int $id_rapport
     * @param int $id_utilisateur
     * @return array Résultat de l'opération ['success' => bool, 'message' => string]
     */
    public function validerDossier($id_rapport, $id_utilisateur)
    {
        try {
            if (!$this->peutModifierDecisionCommission((int) $id_utilisateur)) {
                return ['success' => false, 'message' => 'Vous êtes en lecture seule pour la commission.'];
            }
            $this->ensureWritableRapport($id_rapport, 'une validation de dossier');
            $id_enseignant = $this->getEnseignantIdFromAdmin($id_utilisateur);
            if (!$id_enseignant) {
                return ['success' => false, 'message' => 'Aucun enseignant trouvé pour cet utilisateur'];
            }

            $this->updateRapportDecision($id_rapport, 'valider', 'valide');

            Valider::insererDecision($id_enseignant, $id_rapport, 'valider', 'Validé par la commission');
            $this->auditLog->logValidation($id_utilisateur, 'rapport_etudiants', 'Succès');

            return ['success' => true, 'message' => 'Rapport validé avec succès'];

        } catch (Exception $e) {
            error_log("Erreur validerDossier: " . $e->getMessage());
            $this->auditLog->logValidation($id_utilisateur, 'rapport_etudiants', 'Erreur');
            return ['success' => false, 'message' => 'Erreur lors de la validation: ' . $e->getMessage()];
        }
    }

    /**
     * Rejette un dossier
     *
     * @param int $id_rapport
     * @param string $commentaire
     * @param int $id_utilisateur
     * @return array Résultat de l'opération ['success' => bool, 'message' => string]
     */
    public function rejeterDossier($id_rapport, $commentaire, $id_utilisateur)
    {
        try {
            if (!$this->peutModifierDecisionCommission((int) $id_utilisateur)) {
                return ['success' => false, 'message' => 'Vous êtes en lecture seule pour la commission.'];
            }
            $this->ensureWritableRapport($id_rapport, 'un rejet de dossier');
            $id_enseignant = $this->getEnseignantIdFromAdmin($id_utilisateur);
            if (!$id_enseignant) {
                return ['success' => false, 'message' => 'Aucun enseignant trouvé pour cet utilisateur'];
            }

            $this->updateRapportDecision($id_rapport, 'rejeter', 'desapprouve_commission');

            Valider::insererDecision($id_enseignant, $id_rapport, 'rejeter', $commentaire);
            $this->auditLog->logRejet($id_utilisateur, 'rapport_etudiants', 'Succès');

            return ['success' => true, 'message' => 'Rapport rejeté avec succès'];

        } catch (Exception $e) {
            error_log("Erreur rejeterDossier: " . $e->getMessage());
            $this->auditLog->logRejet($id_utilisateur, 'rapport_etudiants', 'Erreur');
            return ['success' => false, 'message' => 'Erreur lors du rejet: ' . $e->getMessage()];
        }
    }

    /**
     * Traite la décision d'un membre de la commission
     *
     * @param int $id_rapport
     * @param string $decision
     * @param string $commentaire
     * @param int $id_utilisateur
     * @return array Résultat de l'opération
     */
    public function traiterDecisionCommission($id_rapport, $decision, $commentaire, $id_utilisateur)
    {
        try {
            $membresCommission = new \CommissionValidationMembre($this->db);
            if (!$membresCommission->estVotantActif((int) $id_utilisateur)) {
                return ['success' => false, 'message' => 'Vous êtes en lecture seule : vous n\'êtes pas membre actif de la commission.'];
            }
            $this->ensureWritableRapport($id_rapport, 'une evaluation de commission');
            $idEvaluateur = (int) $id_utilisateur;
            if ($idEvaluateur <= 0) {
                return ['success' => false, 'message' => 'Utilisateur non identifié'];
            }

            error_log("DEBUG: Traitement décision commission - Rapport: $id_rapport, Décision: $decision, Utilisateur: $idEvaluateur");

            $evaluationRapport = new EvaluationRapport();

            $evaluationExistante = $evaluationRapport->evaluationExiste($id_rapport, $idEvaluateur);

            if ($evaluationExistante) {
                $success = $evaluationRapport->mettreAJourEvaluation(
                    $evaluationExistante['id_evaluation'],
                    $decision,
                    $commentaire
                );
                error_log("DEBUG: Évaluation mise à jour");
            } else {
                $success = $evaluationRapport->ajouterEvaluation(
                    $id_rapport,
                    $idEvaluateur,
                    $decision,
                    $commentaire
                );
                error_log("DEBUG: Nouvelle évaluation créée");
            }

            if (!$success) {
                return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement de l\'évaluation'];
            }

            $statutVote = $evaluationRapport->getStatutVotes($id_rapport);

            return [
                'success' => true,
                'message' => 'Votre évaluation a été enregistrée',
                'statut_vote' => $statutVote
            ];

        } catch (Exception $e) {
            error_log("Erreur lors de l'enregistrement de l'évaluation: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement de l\'évaluation: ' . $e->getMessage()];
        }
    }

    /**
     * Finalise la décision de la commission pour un rapport
     *
     * @param int $id_rapport
     * @param int $id_utilisateur
     * @return array Résultat de l'opération
     */
    public function finaliserDecisionCommission($id_rapport, $id_utilisateur)
    {
        try {
            if (!$this->peutModifierDecisionCommission((int) $id_utilisateur)) {
                return ['success' => false, 'message' => 'Vous êtes en lecture seule pour la commission.'];
            }
            $this->ensureWritableRapport($id_rapport, 'une finalisation de decision de commission');
            $evaluationRapport = new EvaluationRapport();

            $statutVote = $evaluationRapport->getStatutVotes($id_rapport);

            if (!$statutVote['peut_finaliser']) {
                return ['success' => false, 'message' => 'Impossible de finaliser : vote en cours'];
            }

            $id_enseignant = $this->getEnseignantIdFromAdmin($id_utilisateur);
            if (!$id_enseignant) {
                return ['success' => false, 'message' => 'Enseignant non trouvé'];
            }

            $decision = $statutVote['decision_finale'];

            if ($decision === 'valider') {
                $this->updateRapportDecision($id_rapport, 'valider', 'valide');

                Valider::insererDecision($id_enseignant, $id_rapport, 'valider', 'Validé par consensus de la commission');
                $this->auditLog->logValidation($id_utilisateur, 'rapport_etudiants', 'Succès');

                try {
                    $emailService = new \EmailService();
                    $rapport = $this->rapportEtudiant->getRapportById($id_rapport);
                    if (is_object($rapport)) { $rapport = (array) $rapport; }
                    $stmt = $this->db->prepare("SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_carte_etud = ? OR num_ident_etud = ?");
                    $stmt->execute([$rapport['num_etu'] ?? '', $rapport['num_etu'] ?? '']);
                    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    $commentaire = 'Validé par consensus de la commission';
                    if (!empty($etudiant['email_etu'])) {
                        $nom = ($etudiant['prenom_etu'] ?? '') . ' ' . ($etudiant['nom_etu'] ?? '');
                        $emailService->sendTemplate('EVALUATION_RAPPORT_VALIDE', $etudiant['email_etu'], [
                            'nom' => htmlspecialchars(trim($nom), ENT_QUOTES, 'UTF-8'),
                            'nom_rapport' => htmlspecialchars((string)($rapport['nom_rapport'] ?? ''), ENT_QUOTES, 'UTF-8'),
                            'commentaires' => '<p style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin-top: 8px;">' . nl2br(htmlspecialchars((string)($commentaire ?? ''), ENT_QUOTES, 'UTF-8')) . '</p>',
                        ]);
                    }
                } catch (\Throwable $e) {
                    error_log('Erreur notification evaluation: ' . $e->getMessage());
                }

                return ['success' => true, 'message' => 'Rapport validé par consensus de la commission'];

            } elseif ($decision === 'rejeter') {
                $this->updateRapportDecision($id_rapport, 'rejeter', 'desapprouve_commission');

                Valider::insererDecision($id_enseignant, $id_rapport, 'rejeter', 'Rejeté par la commission');
                $this->auditLog->logRejet($id_utilisateur, 'rapport_etudiants', 'Succès');

                try {
                    $emailService = new \EmailService();
                    $rapport = $this->rapportEtudiant->getRapportById($id_rapport);
                    if (is_object($rapport)) { $rapport = (array) $rapport; }
                    $stmt = $this->db->prepare("SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_carte_etud = ? OR num_ident_etud = ?");
                    $stmt->execute([$rapport['num_etu'] ?? '', $rapport['num_etu'] ?? '']);
                    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    $commentaire = 'Rejeté par la commission';
                    if (!empty($etudiant['email_etu'])) {
                        $nom = ($etudiant['prenom_etu'] ?? '') . ' ' . ($etudiant['nom_etu'] ?? '');
                        $emailService->sendTemplate('EVALUATION_RAPPORT_REJETE', $etudiant['email_etu'], [
                            'nom' => htmlspecialchars(trim($nom), ENT_QUOTES, 'UTF-8'),
                            'nom_rapport' => htmlspecialchars((string)($rapport['nom_rapport'] ?? ''), ENT_QUOTES, 'UTF-8'),
                            'commentaires' => '<p style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin-top: 8px;">' . nl2br(htmlspecialchars((string)($commentaire ?? ''), ENT_QUOTES, 'UTF-8')) . '</p>',
                        ]);
                    }
                } catch (\Throwable $e) {
                    error_log('Erreur notification evaluation: ' . $e->getMessage());
                }

                return ['success' => true, 'message' => 'Rapport rejeté par la commission'];
            }

            return ['success' => false, 'message' => 'Décision non reconnue'];

        } catch (Exception $e) {
            error_log("Erreur lors de la finalisation: " . $e->getMessage());
            $this->auditLog->logValidation($id_utilisateur, 'rapport_etudiants', 'Erreur');
            return ['success' => false, 'message' => 'Erreur lors de la finalisation: ' . $e->getMessage()];
        }
    }

    /**
     * Récupère le détail d'un rapport avec ses décisions
     *
     * @param int $id_rapport
     * @return array
     */
    public function getDetail($id_rapport)
    {
        $selectedYearId = $this->getSelectedYearId();
        $rapportYearId = $this->getRapportYearId($id_rapport);
        if ($selectedYearId !== null && $rapportYearId !== null && $selectedYearId !== $rapportYearId) {
            return [
                'rapport' => null,
                'decisions' => []
            ];
        }

        $rapport = $this->rapportEtudiant->getRapportById($id_rapport);
        $decisions = Valider::getByRapport($id_rapport);
        return [
            'rapport' => $rapport,
            'decisions' => $decisions
        ];
    }
}
