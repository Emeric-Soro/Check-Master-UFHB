<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionUtilisateurService.php';

use CheckMaster\Services\GestionUtilisateurService;
use PHPMailer\PHPMailer\Exception;

class GestionUtilisateurController
{
    /** @var GestionUtilisateurService */
    private $service;

    public function __construct()
    {
        $this->service = new GestionUtilisateurService(Database::getConnection());
    }

    /**
     * Vérifie la disponibilité d'un login et propose une alternative si nécessaire
     * Endpoint AJAX pour vérification en temps réel
     */
    public function checkLoginAvailability()
    {
        header('Content-Type: application/json');

        if (!isset($_GET['login']) || empty($_GET['login'])) {
            echo json_encode(['success' => false, 'message' => 'Login non fourni']);
            exit;
        }

        $result = $this->service->checkLoginAvailability(trim($_GET['login']));
        echo json_encode($result);
        exit;
    }

    // Afficher la liste des étudiants
    public function index()
    {
        // Gérer les requêtes AJAX
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'checkLogin') {
            $this->checkLoginAvailability();
            return; // Sortir après le traitement AJAX
        }

        $utilisateur_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';
        $action = $_GET['action'] ?? '';

        try {
            // Récupérer les personnes non enregistrées comme utilisateurs
            $nonUtilisateurs = $this->service->getNonUtilisateurs();
            $enseignantsNonUtilisateurs = $nonUtilisateurs['enseignantsNonUtilisateurs'];
            $personnelNonUtilisateurs = $nonUtilisateurs['personnelNonUtilisateurs'];
            $etudiantsNonUtilisateurs = $nonUtilisateurs['etudiantsNonUtilisateurs'];

            // Gestion des actions GET pour les modales
            if ($action === 'edit' && isset($_GET['id_utilisateur'])) {
                $utilisateur_a_modifier = $this->service->getUtilisateurById($_GET['id_utilisateur']);
                if (!$utilisateur_a_modifier) {
                    $messageErreur = "Utilisateur non trouvé.";
                }
            } elseif ($action === 'add' || $action === 'addMasse') {
                // Pour l'ajout, on initialise un objet vide
                $utilisateur_a_modifier = (object) [
                    'id_utilisateur' => '',
                    'nom_utilisateur' => '',
                    'login_utilisateur' => '',
                    'id_type_utilisateur' => '',
                    'statut_utilisateur' => 'Actif',
                    'id_GU' => '',
                    'id_niv_acces_donnee' => ''
                ];
            }

            // Gestion des actions POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Ajout d'un nouvel utilisateur
                if (isset($_POST['btn_add_utilisateur'])) {
                    $result = $this->service->addUtilisateur($_POST, $_SESSION['id_utilisateur']);
                    if ($result['success']) {
                        $messageSuccess = $result['message'];
                    } else {
                        $messageErreur = $result['message'];
                    }
                }

                // Traitement de l'ajout en masse
                if (isset($_POST['btn_add_multiple']) && !empty($_POST['selected_persons'])) {
                    $commonData = [
                        'id_type_utilisateur' => $_POST['id_type_utilisateur'],
                        'id_GU' => $_POST['id_GU'],
                        'id_niveau_acces' => $_POST['id_niveau_acces'],
                        'statut_utilisateur' => $_POST['statut_utilisateur']
                    ];
                    $result = $this->service->addUtilisateursEnMasse(
                        $_POST['selected_persons'],
                        $commonData,
                        $_SESSION['id_utilisateur']
                    );
                    if ($result['success']) {
                        $messageSuccess = $result['message'];
                    } else {
                        $messageErreur = $result['message'];
                    }
                }

                // Modification d'un utilisateur
                if (isset($_POST['btn_modifier_utilisateur'])) {
                    $result = $this->service->updateUtilisateur($_POST, $_SESSION['id_utilisateur']);
                    if ($result['success']) {
                        $messageSuccess = $result['message'];
                    } else {
                        $messageErreur = $result['message'];
                    }
                }

                // Activation ou désactivation d'utilisateurs
                if (isset($_POST['selected_ids'])) {
                    if (isset($_POST['submit_enable_multiple']) && $_POST['submit_enable_multiple'] == 3) {
                        $result = $this->service->enableMultipleUtilisateurs($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                        if ($result['success']) {
                            $messageSuccess = $result['message'];
                        } else {
                            $messageErreur = $result['message'];
                        }
                    } elseif (isset($_POST['submit_disable_multiple']) && $_POST['submit_disable_multiple'] == 2) {
                        $result = $this->service->disableMultipleUtilisateurs($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                        if ($result['success']) {
                            $messageSuccess = $result['message'];
                        } else {
                            $messageErreur = $result['message'];
                        }
                    } elseif (isset($_POST['submit_send_access']) && $_POST['submit_send_access'] == 4) {
                        $result = $this->service->sendAccessToMultipleUtilisateurs($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                        if ($result['success']) {
                            $messageSuccess = $result['message'];
                        } else {
                            $messageErreur = $result['message'];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $messageErreur = "Erreur : " . $e->getMessage();
        }

        // Préparation des données pour la vue
        $GLOBALS['messageErreur'] = $messageErreur;
        $GLOBALS['messageSuccess'] = $messageSuccess;
        $GLOBALS['utilisateur_a_modifier'] = $utilisateur_a_modifier;
        $GLOBALS['action'] = $action;
        $GLOBALS['enseignantsNonUtilisateurs'] = $enseignantsNonUtilisateurs;
        $GLOBALS['personnelNonUtilisateurs'] = $personnelNonUtilisateurs;
        $GLOBALS['etudiantsNonUtilisateurs'] = $etudiantsNonUtilisateurs;

        $referenceLists = $this->service->getReferenceLists();
        foreach ($referenceLists as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
}
