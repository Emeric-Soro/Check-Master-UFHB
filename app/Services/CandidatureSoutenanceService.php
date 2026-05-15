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
