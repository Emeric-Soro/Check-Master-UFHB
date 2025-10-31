<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Entreprise.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/AuditLog.php';   
require_once __DIR__ . '/../utils/permissions.php';


class CandidatureSoutenanceController {

    private $baseViewPath;

    private $etudiant;

  private $entreprise;

  private $stage;

  private $db;

    private $auditLog;
    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/candidature_soutenance/';
        $this->db = Database::getConnection();
        $this->etudiant = new Etudiant($this->db);
        $this->entreprise = new Entreprise($this->db);
        $this->stage = new InfoStage($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

  public function index()
  {
        
        
        if(isset($_GET['action'])){
            switch($_GET['action']){
                case 'demande_candidature':
                    $this->demande_candidature();
                    break;
                case 'compte_rendu_etudiant':
                    $this->compteRenduRapport();
                    break;
                case 'info_stage':
                    $this->infoStage();
                    break;
            }
        }
        // Récupérer les informations du stage de l'étudiant connecté
        $stage_info = $this->stage->getStageInfo($_SESSION['num_etu']);
        $GLOBALS['stage_info'] = $stage_info;

        //Vérifier si l'étudiant a un compte rendu
        $compte_rendu = $this->etudiant->getCompteRendu($_SESSION['num_etu']);
        $GLOBALS['compte_rendu'] = $compte_rendu;

        // Vérifier si l'étudiant a déjà soumis une candidature
        $candidature = $this->etudiant->getCandidature($_SESSION['num_etu']);
        $GLOBALS['has_candidature'] = !empty($candidature);

        // Charger toutes les candidatures de l'étudiant
        $candidatures_etudiant = $this->etudiant->getCandidatures($_SESSION['num_etu']);
        $GLOBALS['candidatures_etudiant'] = $candidatures_etudiant;
      
    }

    

    //=============================Gestion de la demande de candidature=============================
    public function demande_candidature()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $etudiant_id = $_SESSION['num_etu'];
            
            // Vérifier si l'étudiant a déjà soumis une candidature
            $existing_candidature = $this->etudiant->getCandidature($etudiant_id);

            $status = $existing_candidature ? $existing_candidature['statut_candidature'] : null;
            
            if ($existing_candidature && ($status === 'En attente' || $status === 'Validée')) {
                $_SESSION['error'] = "Vous avez déjà soumis une candidature.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Erreur");
                return;
            }

            // Vérifier si l'étudiant a rempli ses informations de stage
            $stage_info = $this->stage->getStageInfo($etudiant_id);
            if (!$stage_info) {
                $_SESSION['error'] = "Veuillez d'abord remplir les informations de stage.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Erreur");
                return;
            }

            // Créer la candidature
            $result = $this->etudiant->createCandidature($etudiant_id);
            
            if ($result) {
                $_SESSION['success'] = "Votre candidature a été soumise avec succès. Vous recevrez une réponse après l'évaluation de votre dossier.";
                    $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Succès");
            } else {
                $_SESSION['error'] = "Une erreur est survenue lors de la soumission de votre candidature.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Erreur");
            }
            
           
        }
    }
    
      //=============================COMPTE RENDU DE RAPPORTS =============================
    public function compteRenduRapport()
    {
        $etudiant_id = $_SESSION['num_etu'];
        $compte_rendu = $this->etudiant->getCompteRendu($etudiant_id);
        
        // Mettre la variable dans les GLOBALS pour qu'elle soit accessible dans la vue
        $GLOBALS['compte_rendu'] = $compte_rendu;
        
        if (!$compte_rendu) {
            $_SESSION['error'] = "Aucun compte rendu disponible pour le moment. Veuillez patienter jusqu'à ce que la commission d'évaluation ait examiné votre dossier.";
            $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Erreur");
        }
    }

      //=============================ENREGISTRER/ MODIFIER LES INFOS DE STAGE =============================
      public function infoStage()
      {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $etudiant_id = $_SESSION['num_etu'];

            $nom_entreprise = $_POST['entreprise'];

            //Vérifier si cette entreprise est déjà enregistrer dans la base de donnée
            $entreprise = $this->entreprise->getEntrepriseByLibelle($nom_entreprise);

            if (!$entreprise) {
                $this->entreprise->ajouterEntreprise($nom_entreprise);
                $id_entreprise = $this->entreprise->getLastInsertedId();
            } else {
                $id_entreprise = $entreprise->id_entreprise;
            }

         
            // Vérifier si les informations du stage existent déjà
            $existing_info = $this->stage->getStageInfo($etudiant_id);

            $stage_data = [
                'nom_entreprise' => $id_entreprise,
                'date_debut_stage' => $_POST['date_debut'],
                'date_fin_stage' => $_POST['date_fin'],
                'sujet_stage' => $_POST['sujet'],
                'description_stage' => $_POST['description'],
                'encadrant_entreprise' => $_POST['encadrant'],
                'email_encadrant' => $_POST['email_encadrant'],
                'telephone_encadrant' => $_POST['telephone_encadrant']
            ];
            
            if ($existing_info) {
                // Mettre à jour les informations existantes
                $result = $this->stage->updateStageInfo($etudiant_id, $stage_data);
            } else {
                // Créer de nouvelles informations
                $result = $this->stage->createStageInfo($etudiant_id, $stage_data);
            }
            
            if ($result) {
                $_SESSION['success'] = "Les informations du stage ont été enregistrées avec succès.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Succès");
                } else {
                $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement des informations.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "candidature_soutenance", "Erreur");
            }
        }
    }

}