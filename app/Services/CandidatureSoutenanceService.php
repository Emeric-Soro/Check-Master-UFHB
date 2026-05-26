<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Entreprise.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/MaitreDeStage.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use DateTime;
use Etudiant;
use Entreprise;
use InfoStage;
use MaitreDeStage;
use AuditLog;
use Database;

class CandidatureSoutenanceService
{
    private $db;
    private $etudiant;
    private $entreprise;
    private $stage;
    private $maitreDeStage;
    private $auditLog;

    private function debugLog(string $message, array $context = []): void
    {
        $logPath = __DIR__ . '/../../logs/candidature_soutenance_debug.log';
        $fallbackLogPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'candidature_soutenance_debug.log';
        $line = date('c') . ' [CandidatureSoutenanceService] ' . $message;
        if ($context !== []) {
            $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $line .= ' | context=' . ($json !== false ? $json : '[json_error]');
        }

        error_log($line);
        @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
        @file_put_contents($fallbackLogPath, $line . PHP_EOL, FILE_APPEND);
    }

    public function __construct($db)
    {
        $this->db = $db;
        $this->etudiant = new Etudiant($this->db);
        $this->entreprise = new Entreprise($this->db);
        $this->stage = new InfoStage($this->db);
        $this->maitreDeStage = new MaitreDeStage($this->db);
        $this->auditLog = new AuditLog($this->db);
        $this->debugLog('construct');
    }

    private function resolveStudentStorageId(string $studentId): string
    {
        $student = $this->etudiant->getEtudiantById($studentId);
        if ($student && !empty($student->num_carte_etud)) {
            $this->debugLog('resolveStudentStorageId:resolved', [
                'input' => $studentId,
                'resolved' => (string) $student->num_carte_etud,
            ]);
            return (string) $student->num_carte_etud;
        }

        $this->debugLog('resolveStudentStorageId:fallback', [
            'input' => $studentId,
        ]);
        return $studentId;
    }

    public function getStageInfo($num_etu)
    {
        return $this->stage->getStageInfo($num_etu);
    }

    public function getCompteRendu($num_etu)
    {
        return $this->etudiant->getCompteRendu($num_etu);
    }

    public function getCandidature($num_etu)
    {
        return $this->etudiant->getCandidature($num_etu);
    }

    public function getCandidatures($num_etu)
    {
        return $this->etudiant->getCandidatures($num_etu);
    }

    public function getAllEntreprises()
    {
        return $this->entreprise->getAllEntreprises();
    }

    public function getAllMaitresDeStage()
    {
        return $this->maitreDeStage->getAllMaitresDeStage();
    }

    private function fetchOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function fetchCount(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getStudentAcademicYearId(string $etudiantId): ?int
    {
        $etudiant = $this->etudiant->getEtudiantById($etudiantId);
        if ($etudiant && isset($etudiant->id_annee_acad) && is_numeric($etudiant->id_annee_acad)) {
            $this->debugLog('getStudentAcademicYearId:resolved', [
                'etudiantId' => $etudiantId,
                'annee' => (int) $etudiant->id_annee_acad,
            ]);
            return (int) $etudiant->id_annee_acad;
        }

        $this->debugLog('getStudentAcademicYearId:not_found', [
            'etudiantId' => $etudiantId,
        ]);
        return null;
    }

    public function soumettreCandidature($etudiant_id, $id_utilisateur)
    {
        $storageStudentId = $this->resolveStudentStorageId((string) $etudiant_id);
        $studentYearId = $this->getStudentAcademicYearId((string) $etudiant_id);
        $selectedYearId = \AcademicYear::getSelectedIdFromSession();
        if ($selectedYearId !== null && $studentYearId !== null && $selectedYearId !== $studentYearId) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            $this->debugLog('enregistrerInfoStage:year_mismatch');
            return ['success' => false, 'message' => "L'étudiant ne correspond pas à l'année académique actuellement sélectionnée."];
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->db, $studentYearId, 'une candidature de soutenance');
        if (!$writeGuard['success']) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => $writeGuard['message']];
        }

        // Vérifier si l'étudiant a déjà soumis une candidature
        $existing_candidature = $this->etudiant->getCandidature($etudiant_id);
        $status = $existing_candidature ? $existing_candidature['statut_candidature'] : null;

        if ($existing_candidature && ($status === 'En attente' || $status === 'Validée')) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => "Vous avez déjà soumis une candidature."];
        }

        // Créer la candidature
        $result = $this->etudiant->createCandidature($storageStudentId);

        if ($result) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Succès");
            return ['success' => true, 'message' => "Votre candidature a été soumise avec succès. Vous recevrez une réponse après l'évaluation de votre dossier."];
        } else {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => "Une erreur est survenue lors de la soumission de votre candidature."];
        }
    }

    public function enregistrerInfoStage($etudiant_id, $id_utilisateur, $data)
    {
        $this->debugLog('enregistrerInfoStage:start', [
            'etudiant_id' => $etudiant_id,
            'id_utilisateur' => $id_utilisateur,
            'data' => $data,
        ]);
        $storageStudentId = $this->resolveStudentStorageId((string) $etudiant_id);
        $studentYearId = $this->getStudentAcademicYearId((string) $etudiant_id);
        $selectedYearId = \AcademicYear::getSelectedIdFromSession();
        $this->debugLog('enregistrerInfoStage:academic_years', [
            'storageStudentId' => $storageStudentId,
            'studentYearId' => $studentYearId,
            'selectedYearId' => $selectedYearId,
        ]);
        if ($selectedYearId !== null && $studentYearId !== null && $selectedYearId !== $studentYearId) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => "L'étudiant ne correspond pas à l'année académique actuellement sélectionnée."];
        }

        // La candidature n'est plus requise à cette étape — elle sera créée automatiquement au dépôt du rapport
        $writeGuard = \AcademicYear::ensureWritableYear($this->db, $studentYearId, 'des informations de stage');
        $this->debugLog('enregistrerInfoStage:write_guard', [
            'writeGuard' => $writeGuard,
        ]);
        if (!$writeGuard['success']) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => $writeGuard['message']];
        }

        $nom_entreprise = $data['entreprise'];
        $this->debugLog('enregistrerInfoStage:entreprise_input', [
            'nom_entreprise' => $nom_entreprise,
        ]);

        $entreprise = $this->entreprise->getEntrepriseByLibelle($nom_entreprise);
        if (!$entreprise) {
            $this->debugLog('enregistrerInfoStage:creating_entreprise');
            $this->entreprise->ajouterEntreprise($nom_entreprise);
            $id_entreprise = $this->entreprise->getLastInsertedId();
        } else {
            $id_entreprise = $entreprise->id_entreprise;
        }
        $this->debugLog('enregistrerInfoStage:entreprise_resolved', [
            'id_entreprise' => $id_entreprise,
            'existing' => (bool) $entreprise,
        ]);

        // Gérer le maître de stage (créer ou récupérer)
        $maitreStageData = [
            'encadrant' => $data['encadrant'],
            'email_encadrant' => $data['email_encadrant'],
            'telephone_encadrant' => $data['telephone_encadrant'],
            'id_entreprise' => $id_entreprise
        ];

        $this->debugLog('enregistrerInfoStage:maitre_payload', [
            'maitreStageData' => $maitreStageData,
        ]);
        $id_maitre_stage = $this->maitreDeStage->findOrCreate($maitreStageData);
        $this->debugLog('enregistrerInfoStage:maitre_resolved', [
            'id_maitre_stage' => $id_maitre_stage,
        ]);

        // Vérifier que l'ID du maître de stage a bien été créé/récupéré
        if (!$id_maitre_stage) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur - création maître de stage");
            return ['success' => false, 'message' => "Une erreur est survenue lors de l'enregistrement du maître de stage."];
        }

        $existing_info = $this->stage->getStageInfo($storageStudentId);
        $this->debugLog('enregistrerInfoStage:existing_stage_lookup', [
            'has_existing_info' => !empty($existing_info),
        ]);

        $stage_data = [
            'nom_entreprise' => $id_entreprise,
            'date_debut_stage' => $data['date_debut'],
            'date_fin_stage' => $data['date_fin'],
            'sujet_stage' => $data['sujet'],
            'encadrant_entreprise' => $data['encadrant'],
            'email_encadrant' => $data['email_encadrant'],
            'telephone_encadrant' => $data['telephone_encadrant'],
            'id_maitre_stage' => $id_maitre_stage // Nouveau champ
        ];
        $this->debugLog('enregistrerInfoStage:stage_data_built', [
            'stage_data' => $stage_data,
        ]);

        $date_debut = new DateTime($data['date_debut']);
        $date_fin = new DateTime($data['date_fin']);
        $aujourdhui = new DateTime();
        $aujourdhui->setTime(0, 0, 0);
        $this->debugLog('enregistrerInfoStage:dates_parsed', [
            'date_debut' => $date_debut->format('Y-m-d'),
            'date_fin' => $date_fin->format('Y-m-d'),
            'today' => $aujourdhui->format('Y-m-d'),
        ]);

        if ($date_debut > $aujourdhui) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur - date début future");
            return ['success' => false, 'message' => "La date de début ne peut pas être dans le futur."];
        }

        if ($date_fin > $aujourdhui) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur - date fin future");
            return ['success' => false, 'message' => "La date de fin ne peut pas être dans le futur."];
        }

        if ($date_fin <= $date_debut) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur - dates invalides");
            return ['success' => false, 'message' => "La date de fin doit être après la date de début du stage."];
        }

        $date_fin_exclusive = (clone $date_fin)->modify('+1 day' );
        $date_fin_min_exclusive = (clone $date_debut)->modify('+3 months');
        $interval = $date_debut->diff($date_fin_exclusive);
        $total_months = ($interval->y * 12) + $interval->m + ($interval->d / 30.44);
        $this->debugLog('enregistrerInfoStage:duration_computed', [
            'years' => $interval->y,
            'months' => $interval->m,
            'days' => $interval->d,
            'total_months' => $total_months,
            'date_fin_exclusive' => $date_fin_exclusive->format('Y-m-d'),
            'date_fin_min_exclusive' => $date_fin_min_exclusive->format('Y-m-d'),
        ]);

        if ($date_fin_exclusive < $date_fin_min_exclusive) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur - duree invalide");
            $this->debugLog('enregistrerInfoStage:validation_failed', [
                'reason' => 'duration_below_minimum',
                'date_fin_exclusive' => $date_fin_exclusive->format('Y-m-d'),
                'date_fin_min_exclusive' => $date_fin_min_exclusive->format('Y-m-d'),
            ]);
            $minAllowedDate = (clone $date_fin_min_exclusive)->modify('-1 day');
            return ['success' => false, 'message' => sprintf(
                "La periode de stage doit couvrir au moins 3 mois calendaires. Pour une date de debut au %s, la date de fin doit etre a partir du %s.",
                $date_debut->format('d/m/Y'),
                $minAllowedDate->format('d/m/Y')
            )];
        }

        if ($existing_info) {
            $this->debugLog('enregistrerInfoStage:branch_update');
            $result = $this->stage->updateStageInfo($storageStudentId, $stage_data);
        } else {
            $this->debugLog('enregistrerInfoStage:branch_create');
            $result = $this->stage->createStageInfo($storageStudentId, $stage_data);
        }
        $this->debugLog('enregistrerInfoStage:persistence_result', [
            'result' => $result,
        ]);

        if ($result) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Succès");
            return ['success' => true, 'message' => "Les informations du stage ont été enregistrées avec succès. Vous pouvez maintenant rédiger votre rapport."];
        } else {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => "Une erreur est survenue lors de l'enregistrement des informations."];
        }
    }

    public function getLastCandidature($num_etu)
    {
        return $this->etudiant->getCandidature($num_etu);
    }

    public function getSuiviDossier(string $num_etu): array
    {
        $stage = $this->getStageInfo($num_etu) ?: [];
        $candidature = $this->etudiant->getLastCandidatureByNumEtu($num_etu) ?: null;
        $compteRendu = $this->getCompteRendu($num_etu) ?: null;

        $rapport = $this->fetchOne("
            SELECT re.*, d.date_depot
            FROM rapport_etudiants re
            LEFT JOIN deposer d ON d.id_rapport = re.id_rapport
            WHERE re.num_etu = :num_etu
            ORDER BY COALESCE(d.date_depot, re.date_redaction_rapport) DESC, re.id_rapport DESC
            LIMIT 1
        ", [':num_etu' => $num_etu]);

        $decisionCommission = null;
        $votesCommission = 0;
        if ($rapport && !empty($rapport['id_rapport'])) {
            $decisionCommission = $this->fetchOne("
                SELECT v.*, CONCAT(COALESCE(e.prenom_enseignant, ''), ' ', COALESCE(e.nom_enseignant, '')) AS enseignant_decideur
                FROM valider v
                LEFT JOIN enseignants e ON e.id_enseignant = v.id_enseignant
                WHERE v.id_rapport = :id_rapport
                ORDER BY v.date_validation DESC
                LIMIT 1
            ", [':id_rapport' => (int) $rapport['id_rapport']]);

            $votesCommission = $this->fetchCount("
                SELECT COUNT(*)
                FROM evaluations_rapports
                WHERE id_rapport = :id_rapport
            ", [':id_rapport' => (int) $rapport['id_rapport']]);
        }

        $soutenance = $this->fetchOne("
            SELECT
                ps.*,
                s.lib_salle,
                se.lib_session,
                d.lib_domaine
            FROM programmer_soutenance ps
            LEFT JOIN salles s ON s.id_salle = ps.id_salle
            LEFT JOIN session se ON se.id_session = ps.id_session
            LEFT JOIN domaine d ON d.id_domaine = ps.id_domaine
            WHERE ps.num_etud = :num_etu
            ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC
            LIMIT 1
        ", [':num_etu' => $num_etu]);

        $pvDisponible = false;
        $pvDate = null;
        if ($soutenance && !empty($soutenance['num_soutenance'])) {
            $notesSoutenance = $this->fetchCount("
                SELECT COUNT(*)
                FROM evaluer
                WHERE num_etudiant = :num_etu AND num_jury = :num_jury
            ", [
                ':num_etu' => $num_etu,
                ':num_jury' => $soutenance['num_soutenance'],
            ]);
            $pvDisponible = $notesSoutenance > 0;
            $pvDate = $pvDisponible ? ($soutenance['date_soutenance'] ?? null) : null;
        }

        $commissionLabel = 'En attente';
        $commissionDate = $rapport['date_depot'] ?? null;
        if ($decisionCommission) {
            $decision = strtolower((string) ($decisionCommission['decision_validation'] ?? ''));
            if ($decision === 'valider') {
                $commissionLabel = 'Validée';
            } elseif ($decision === 'rejeter') {
                $commissionLabel = 'À corriger';
            }
            $commissionDate = $decisionCommission['date_validation'] ?? $commissionDate;
        } elseif ($votesCommission > 0) {
            $commissionLabel = 'En évaluation';
        } elseif ($rapport && !empty($rapport['date_depot'])) {
            $commissionLabel = 'En attente';
        }

        $validationLabel = 'En attente';
        if ($candidature) {
            $statutCandidature = strtolower((string) ($candidature['statut_candidature'] ?? ''));
            if (in_array($statutCandidature, ['validee', 'validée'], true)) {
                $validationLabel = 'Validée';
            } elseif (in_array($statutCandidature, ['rejetee', 'rejetée'], true)) {
                $validationLabel = 'Rejetée';
            }
        }

        $condCandidature = (bool)$candidature;
        $condValidation = !empty($candidature['date_traitement']) || in_array($validationLabel, ['Validée', 'Rejetée']);
        $condCommission = (bool)$decisionCommission;
        $condCompteRendu = (bool)$compteRendu;
        $condSoutenance = (bool)$soutenance;
        $condPv = (bool)$pvDisponible;

        // Backward propagation of "done" state to fix anomalies
        if ($condPv) $condSoutenance = true;
        if ($condSoutenance) $condCompteRendu = true;
        if ($condCompteRendu) $condCommission = true;
        if ($condCommission) $condValidation = true;
        if ($condValidation) $condCandidature = true;

        if ($condValidation && $validationLabel === 'En attente') {
            $validationLabel = 'Validée';
        }

        if ($condCommission && in_array($commissionLabel, ['En attente', 'En évaluation'])) {
            $commissionLabel = 'Validée';
        }

        return [
            'stage' => $stage,
            'candidature' => $candidature,
            'rapport' => $rapport,
            'commission' => [
                'decision' => $decisionCommission,
                'votes' => $votesCommission,
                'label' => $commissionLabel,
                'date' => $commissionDate,
            ],
            'compte_rendu' => $compteRendu,
            'soutenance' => $soutenance,
            'pv' => [
                'disponible' => $pvDisponible,
                'date' => $pvDate,
            ],
            'tracking_mode' => !empty($rapport['date_depot']),
            'steps' => [
                [
                    'key' => 'candidature',
                    'label' => 'Candidature',
                    'status' => $condCandidature ? 'done' : 'current',
                    'state_label' => $candidature ? 'Enregistrée' : 'À faire',
                    'date' => $candidature['date_candidature'] ?? null,
                ],
                [
                    'key' => 'validation',
                    'label' => 'Validation',
                    'status' => $condValidation ? 'done' : ($condCandidature ? 'current' : 'pending'),
                    'state_label' => $validationLabel,
                    'date' => $candidature['date_traitement'] ?? null,
                ],
                [
                    'key' => 'commission',
                    'label' => 'Commission',
                    'status' => $condCommission ? 'done' : ($condValidation ? 'current' : 'pending'),
                    'state_label' => $commissionLabel,
                    'date' => $commissionDate,
                ],
                [
                    'key' => 'compte_rendu',
                    'label' => 'Compte Rendu',
                    'status' => $condCompteRendu ? 'done' : ($condCommission ? 'current' : 'pending'),
                    'state_label' => $compteRendu ? 'Disponible' : 'En attente',
                    'date' => $compteRendu['date_CR'] ?? null,
                ],
                [
                    'key' => 'soutenance',
                    'label' => 'Soutenance',
                    'status' => $condSoutenance ? 'done' : ($condCompteRendu ? 'current' : 'pending'),
                    'state_label' => $soutenance ? 'Programmée' : 'Non programmée',
                    'date' => $soutenance['date_soutenance'] ?? null,
                ],
                [
                    'key' => 'pv',
                    'label' => 'PV',
                    'status' => $condPv ? 'done' : ($condSoutenance ? 'current' : 'pending'),
                    'state_label' => $pvDisponible ? 'Disponible' : 'En attente',
                    'date' => $pvDate,
                ],
            ],
        ];
    }

    public function calculerProgression($num_etu)
    {
        $progression = [
            'candidature' => false,
            'stage' => false,
            'rapport' => false
        ];

        // Vérifier stage (déclaré en premier maintenant)
        $stage = $this->stage->getStageInfo($num_etu);
        if ($stage) {
            $progression['stage'] = true;
        }

        // Vérifier candidature (créée automatiquement au dépôt du rapport)
        $candidature = $this->etudiant->getCandidature($num_etu);
        if ($candidature) {
            $progression['candidature'] = true;
        }

        // Vérifier rapport — chercher dans rapport_etudiants (la candidature est créée au dépôt)
        $pdo = $this->db;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rapport_etudiants WHERE num_etu = ? AND statut_rapport IN ('soumis', 'en_attente_validation', 'valide')");
        $stmt->execute([$num_etu]);
        $rapportCount = (int) $stmt->fetchColumn();
        if ($rapportCount > 0) {
            $progression['rapport'] = true;
        }

        return $progression;
    }
}
