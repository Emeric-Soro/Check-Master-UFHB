<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Entreprise.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/AuditLog.php';

use DateTime;
use Etudiant;
use Entreprise;
use InfoStage;
use AuditLog;
use Database;

class CandidatureSoutenanceService
{
    private $db;
    private $etudiant;
    private $entreprise;
    private $stage;
    private $auditLog;

    public function __construct($db)
    {
        $this->db = $db;
        $this->etudiant = new Etudiant($this->db);
        $this->entreprise = new Entreprise($this->db);
        $this->stage = new InfoStage($this->db);
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

    public function soumettreCandidature($etudiant_id, $id_utilisateur)
    {
        // Vérifier si l'étudiant a déjà soumis une candidature
        $existing_candidature = $this->etudiant->getCandidature($etudiant_id);
        $status = $existing_candidature ? $existing_candidature['statut_candidature'] : null;

        if ($existing_candidature && ($status === 'En attente' || $status === 'Validée')) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => "Vous avez déjà soumis une candidature."];
        }

        // Vérifier si l'étudiant a rempli ses informations de stage
        $stage_info = $this->stage->getStageInfo($etudiant_id);
        if (!$stage_info) {
            $this->auditLog->logCreation($id_utilisateur, "candidature_soutenance", "Erreur");
            return ['success' => false, 'message' => "Veuillez d'abord remplir les informations de stage."];
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
        $nom_entreprise = $data['entreprise'];

        $entreprise = $this->entreprise->getEntrepriseByLibelle($nom_entreprise);
        if (!$entreprise) {
            $this->entreprise->ajouterEntreprise($nom_entreprise);
            $id_entreprise = $this->entreprise->getLastInsertedId();
        } else {
            $id_entreprise = $entreprise->id_entreprise;
        }

        $existing_info = $this->stage->getStageInfo($etudiant_id);

        $stage_data = [
            'nom_entreprise' => $id_entreprise,
            'date_debut_stage' => $data['date_debut'],
            'date_fin_stage' => $data['date_fin'],
            'sujet_stage' => $data['sujet'],
            'encadrant_entreprise' => $data['encadrant'],
            'email_encadrant' => $data['email_encadrant'],
            'telephone_encadrant' => $data['telephone_encadrant']
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
}