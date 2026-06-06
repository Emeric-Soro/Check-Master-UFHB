<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionUtilisateurService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\GestionUtilisateurService;

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

        if (!canView('gestion_utilisateurs')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Accès refusé."]);
            exit;
        }

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
        // Vérification globale de la permission de voir la page
        if (!canView('gestion_utilisateurs')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Accès refusé."]);
                exit;
            }
            header('Location: layout.php?page=access_denied');
            exit;
        }




        {
            if (isset($_GET['ajax']) && $_GET['ajax'] === 'checkLogin') {
                $this->checkLoginAvailability();
                return; // Sortir après le traitement AJAX
            }

            $utilisateur_a_modifier = null;
            $messageErreur = '';
            $messageSuccess = '';
            $messageSuccessType = 'success';
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
                    $utilisateur_a_modifier = (object)[
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
                        if (!canCreate('gestion_utilisateurs')) {
                            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                                http_response_code(403);
                                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                                exit;
                            }
                            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                            $_SESSION['error_type'] = 'permission_denied';
                            header('Location: layout.php?page=access_denied');
                            exit;
                        }
                        $result = $this->service->addUtilisateur($_POST, $_SESSION['id_utilisateur']);
                        if ($result['success']) {
                            $messageSuccess = $result['message'];
                            $messageSuccessType = (string)($result['feedback_type'] ?? 'success');
                        } else {
                            $messageErreur = $result['message'];
                        }
                    }

                    // Traitement de l'ajout en masse
                    if (isset($_POST['btn_add_multiple']) && !empty($_POST['selected_persons'])) {
                        if (!canCreate('gestion_utilisateurs')) {
                            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                                http_response_code(403);
                                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                                exit;
                            }
                            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                            $_SESSION['error_type'] = 'permission_denied';
                            header('Location: layout.php?page=access_denied');
                            exit;
                        }
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
                            $messageSuccessType = (string)($result['feedback_type'] ?? 'success');
                        } else {
                            $messageErreur = $result['message'];
                        }
                    }

                    // Modification d'un utilisateur
                    if (isset($_POST['btn_modifier_utilisateur'])) {
                        if (!canEdit('gestion_utilisateurs')) {
                            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                                http_response_code(403);
                                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                                exit;
                            }
                            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                            $_SESSION['error_type'] = 'permission_denied';
                            header('Location: layout.php?page=access_denied');
                            exit;
                        }
                        $result = $this->service->updateUtilisateur($_POST, $_SESSION['id_utilisateur']);
                        if ($result['success']) {
                            $messageSuccess = $result['message'];
                            $messageSuccessType = (string)($result['feedback_type'] ?? 'success');
                        } else {
                            $messageErreur = $result['message'];
                        }
                    }

                    // Activation ou désactivation d'utilisateurs
                    if (isset($_POST['selected_ids'])) {
                        if (isset($_POST['submit_enable_multiple']) && $_POST['submit_enable_multiple'] == 3) {
                            if (!canEdit('gestion_utilisateurs')) {
                                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                                    http_response_code(403);
                                    echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                                    exit;
                                }
                                $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                                $_SESSION['error_type'] = 'permission_denied';
                                header('Location: layout.php?page=access_denied');
                                exit;
                            }
                            $result = $this->service->enableMultipleUtilisateurs($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                            if ($result['success']) {
                                $messageSuccess = $result['message'];
                                $messageSuccessType = (string)($result['feedback_type'] ?? 'success');
                            } else {
                                $messageErreur = $result['message'];
                            }
                        } elseif (isset($_POST['submit_disable_multiple']) && $_POST['submit_disable_multiple'] == 2) {
                            if (!canEdit('gestion_utilisateurs')) {
                                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                                    http_response_code(403);
                                    echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                                    exit;
                                }
                                $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                                $_SESSION['error_type'] = 'permission_denied';
                                header('Location: layout.php?page=access_denied');
                                exit;
                            }
                            $result = $this->service->disableMultipleUtilisateurs($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                            if ($result['success']) {
                                $messageSuccess = $result['message'];
                                $messageSuccessType = (string)($result['feedback_type'] ?? 'success');
                            } else {
                                $messageErreur = $result['message'];
                            }
                        } elseif (isset($_POST['submit_send_access']) && $_POST['submit_send_access'] == 4) {
                            if (!canEdit('gestion_utilisateurs')) {
                                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                                    http_response_code(403);
                                    echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                                    exit;
                                }
                                $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                                $_SESSION['error_type'] = 'permission_denied';
                                header('Location: layout.php?page=access_denied');
                                exit;
                            }
                            $result = $this->service->sendAccessToMultipleUtilisateurs($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                            if ($result['success']) {
                                $messageSuccess = $result['message'];
                                $messageSuccessType = (string)($result['feedback_type'] ?? 'success');
                            } else {
                                $messageErreur = $result['message'];
                            }
                        } elseif (isset($_POST['submit_delete_multiple']) && $_POST['submit_delete_multiple'] == 5) {
                            if (!canDelete('gestion_utilisateurs')) {
                                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                                    http_response_code(403);
                                    echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                                    exit;
                                }
                                $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                                $_SESSION['error_type'] = 'permission_denied';
                                header('Location: layout.php?page=access_denied');
                                exit;
                            }
                            $result = $this->service->deleteMultipleUtilisateurs($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                            if ($result['success']) {
                                $messageSuccess = $result['message'];
                                $messageSuccessType = (string)($result['feedback_type'] ?? 'success');
                            } else {
                                $messageErreur = $result['message'];
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                $messageErreur = "Erreur : " . $e->getMessage();
            }

            // Préparation des données pour la vue
            $GLOBALS['messageErreur'] = $messageErreur;
            $GLOBALS['messageSuccess'] = $messageSuccess;
            $GLOBALS['messageSuccessType'] = $messageSuccessType;
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
}