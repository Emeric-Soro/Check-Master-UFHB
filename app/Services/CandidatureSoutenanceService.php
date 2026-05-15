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

    public function __construct($db)
    {
        $this->db = $db;
        $this->etudiant = new Etudiant($this->db);
        $this->entreprise = new Entreprise($this->db);
        $this->stage = new InfoStage($this->db);
        $this->maitreDeStage = new MaitreDeStage($this->db);
        $this->auditLog = new AuditLog($this->db);
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
            return (int) $etudiant->id_annee_acad;
        }

        return null;
    }

    public function soumettreCandidature($etudiant_id, $id_utilisateur)
    {
        $studentYearId = $this->getStudentAcademicYearId((string) $etudiant_id);
        $selectedYearId = \AcademicYear::getSelectedIdFromSession();
        if ($selectedYearId !== null && $studentYearId !== null && $selectedYearId !== $studentYearId) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
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
        $result = $this->etudiant->createCandidature($etudiant_id);

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
        $studentYearId = $this->getStudentAcademicYearId((string) $etudiant_id);
        $selectedYearId = \AcademicYear::getSelectedIdFromSession();
        if ($selectedYearId !== null && $studentYearId !== null && $selectedYearId !== $studentYearId) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => "L'étudiant ne correspond pas à l'année académique actuellement sélectionnée."];
        }

        // La candidature n'est plus requise à cette étape — elle sera créée automatiquement au dépôt du rapport
        $writeGuard = \AcademicYear::ensureWritableYear($this->db, $studentYearId, 'des informations de stage');
        if (!$writeGuard['success']) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => $writeGuard['message']];
        }

        $nom_entreprise = $data['entreprise'];

        $entreprise = $this->entreprise->getEntrepriseByLibelle($nom_entreprise);
        if (!$entreprise) {
            $this->entreprise->ajouterEntreprise($nom_entreprise);
            $id_entreprise = $this->entreprise->getLastInsertedId();
        } else {
            $id_entreprise = $entreprise->id_entreprise;
        }

        // Gérer le maître de stage (créer ou récupérer)
        $maitreStageData = [
            'encadrant' => $data['encadrant'],
            'email_encadrant' => $data['email_encadrant'],
            'telephone_encadrant' => $data['telephone_encadrant'],
            'id_entreprise' => $id_entreprise
        ];

        $id_maitre_stage = $this->maitreDeStage->findOrCreate($maitreStageData);

        // Vérifier que l'ID du maître de stage a bien été créé/récupéré
        if (!$id_maitre_stage) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur - création maître de stage");
            return ['success' => false, 'message' => "Une erreur est survenue lors de l'enregistrement du maître de stage."];
        }

        $existing_info = $this->stage->getStageInfo($etudiant_id);

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

        $date_debut = new DateTime($data['date_debut']);
        $date_fin = new DateTime($data['date_fin']);
        $aujourdhui = new DateTime();
        $aujourdhui->setTime(0, 0, 0);

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

        $interval = $date_debut->diff($date_fin);
        $total_months = ($interval->y * 12) + $interval->m + ($interval->d / 30.44);

        if ($total_months < 6) {
            $months = floor($total_months);
            $weeks = floor(($total_months - $months) * 4.33);
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur - durée insuffisante");
            return ['success' => false, 'message' => "La période de stage doit être d'au minimum 6 mois. Durée actuelle: {$months} mois et {$weeks} semaines."];
        }

        if ($existing_info) {
            $result = $this->stage->updateStageInfo($etudiant_id, $stage_data);
        } else {
            $result = $this->stage->createStageInfo($etudiant_id, $stage_data);
        }

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
                    'status' => $candidature ? 'done' : 'pending',
                    'state_label' => $candidature ? 'Enregistrée' : 'À faire',
                    'date' => $candidature['date_candidature'] ?? null,
                ],
                [
                    'key' => 'validation',
                    'label' => 'Validation',
                    'status' => !empty($candidature['date_traitement']) ? 'done' : ($candidature ? 'current' : 'pending'),
                    'state_label' => $validationLabel,
                    'date' => $candidature['date_traitement'] ?? null,
                ],
                [
                    'key' => 'commission',
                    'label' => 'Commission',
                    'status' => $decisionCommission ? 'done' : (!empty($rapport['date_depot']) ? 'current' : 'pending'),
                    'state_label' => $commissionLabel,
                    'date' => $commissionDate,
                ],
                [
                    'key' => 'compte_rendu',
                    'label' => 'Compte Rendu',
                    'status' => $compteRendu ? 'done' : ($decisionCommission ? 'current' : 'pending'),
                    'state_label' => $compteRendu ? 'Disponible' : 'En attente',
                    'date' => $compteRendu['date_CR'] ?? null,
                ],
                [
                    'key' => 'soutenance',
                    'label' => 'Soutenance',
                    'status' => $soutenance ? 'done' : ($compteRendu ? 'current' : 'pending'),
                    'state_label' => $soutenance ? 'Programmée' : 'Non programmée',
                    'date' => $soutenance['date_soutenance'] ?? null,
                ],
                [
                    'key' => 'pv',
                    'label' => 'PV',
                    'status' => $pvDisponible ? 'done' : ($soutenance ? 'current' : 'pending'),
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
