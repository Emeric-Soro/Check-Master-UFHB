<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/CandidatureSoutenanceService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\CandidatureSoutenanceService;

class CandidatureSoutenanceController
{

    private $baseViewPath;

    private $service;

    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/candidature_soutenance/';
        $db = Database::getConnection();
        $this->service = new CandidatureSoutenanceService($db);
    }

    public function index()
    {


        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
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

        // Vérifier que l'utilisateur est un étudiant ou un administrateur
        $lib = $_SESSION['lib_GU'] ?? null;
        $isAdmin = is_string($lib) && in_array(strtolower(trim($lib)), ['administrateur', 'admin'], true);

        if (!isset($_SESSION['num_etu']) && !$isAdmin) {
            $_SESSION['error'] = "Cette page est réservée aux étudiants uniquement.";
            header('Location: layout.php?page=dashboard');
            exit();
        }

        // Récupérer les informations du stage de l'étudiant connecté (si étudiant)
        if (isset($_SESSION['num_etu'])) {
            $GLOBALS['stage_info'] = $this->service->getStageInfo($_SESSION['num_etu']);

            //Vérifier si l'étudiant a un compte rendu
            $GLOBALS['compte_rendu'] = $this->service->getCompteRendu($_SESSION['num_etu']);

            // Vérifier si l'étudiant a déjà soumis une candidature
            $candidature = $this->service->getCandidature($_SESSION['num_etu']);
            $GLOBALS['has_candidature'] = !empty($candidature);

            // Charger toutes les candidatures de l'étudiant
            $GLOBALS['candidatures_etudiant'] = $this->service->getCandidatures($_SESSION['num_etu']);

            // Ajouter la dernière candidature et la progression
            $GLOBALS["candidature_active"] = $this->service->getLastCandidature($_SESSION["num_etu"]);
            $GLOBALS["progression"] = $this->service->calculerProgression($_SESSION["num_etu"]);
            $GLOBALS['dossier_soutenance'] = $this->service->getSuiviDossier($_SESSION['num_etu']);
        } else {
            // Pour l'administrateur, initialiser des valeurs par défaut
            $GLOBALS['stage_info'] = null;
            $GLOBALS['compte_rendu'] = null;
            $GLOBALS['has_candidature'] = false;
            $GLOBALS['candidatures_etudiant'] = [];
            $GLOBALS['dossier_soutenance'] = [];
        }

        // Récupérer toutes les entreprises pour l'autocomplétion
        $GLOBALS['entreprises'] = $this->service->getAllEntreprises();

        // Récupérer tous les maîtres de stage pour l'autocomplétion
        $GLOBALS['maitres_de_stage'] = $this->service->getAllMaitresDeStage();

    }



    //=============================Redirection de l'ancienne action candidature=============================
    // La candidature est désormais créée automatiquement lors du dépôt du rapport.
    // Cette action redirige vers la page principale avec un message informatif.
    public function demande_candidature()
    {
        $_SESSION['success'] = "Votre candidature sera soumise automatiquement lors du dépôt de votre rapport. Veuillez d'abord remplir les informations de stage, puis déposer votre rapport.";
        header('Location: ?page=candidature_soutenance');
        exit();
    }

    //=============================COMPTE RENDU DE RAPPORTS =============================
    public function compteRenduRapport()
    {
        if (!isset($_SESSION['num_etu']) || empty($_SESSION['num_etu'])) {
            $_SESSION['error'] = "Identifiant étudiant non trouvé. Veuillez vous reconnecter.";
            return;
        }

        $etudiant_id = $_SESSION['num_etu'];
        $compte_rendu = $this->service->getCompteRendu($etudiant_id);

        // Mettre la variable dans les GLOBALS pour qu'elle soit accessible dans la vue
        $GLOBALS['compte_rendu'] = $compte_rendu;

        if (!$compte_rendu) {
            $_SESSION['error'] = "Aucun compte rendu disponible pour le moment. Veuillez patienter jusqu'à ce que la commission d'évaluation ait examiné votre dossier.";
        }
    }

    //=============================ENREGISTRER/ MODIFIER LES INFOS DE STAGE =============================
    public function infoStage()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Si l'utilisateur n'est pas un étudiant (admin par exemple), rediriger directement
            if (!isset($_SESSION['num_etu']) || empty($_SESSION['num_etu'])) {
                header('Location: ?page=gestion_rapports&action=creer_rapport');
                exit();
            }
            if (!canCreate('candidature_soutenance')) {
                $_SESSION['error'] = "Accès non autorisé.";
                header('Location: ?page=candidature_soutenance');
                exit();
            }
            $etudiant_id = $_SESSION['num_etu'];
            $id_utilisateur = $_SESSION['id_utilisateur'];

            $data = [
                'entreprise' => $_POST['entreprise'],
                'date_debut' => $_POST['date_debut'],
                'date_fin' => $_POST['date_fin'],
                'sujet' => $_POST['sujet'],
                'encadrant' => $_POST['encadrant'],
                'email_encadrant' => $_POST['email_encadrant'],
                'telephone_encadrant' => $_POST['telephone_encadrant']
            ];

            $result = $this->service->enregistrerInfoStage($etudiant_id, $id_utilisateur, $data);

            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
                header('Location: ?page=gestion_rapports&action=creer_rapport');
                exit();
            } else {
                $_SESSION['error'] = $result['message'];
                header('Location: ?page=candidature_soutenance');
                exit();
            }
        }
    }

}
