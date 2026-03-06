<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionEtudiantService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../Core/Autoload.php';

use CheckMaster\Core\Session;
use CheckMaster\Services\GestionEtudiantService;


class GestionEtudiantController
{
    /** @var GestionEtudiantService */
    private $service;
    private $baseViewPath;

    public function __construct()
    {
        Session::start();

        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->service = new GestionEtudiantService(Database::getConnection());
    }

    public function index()
    {
        try {
            $currentPage = isset($_GET['p']) ? (int) $_GET['p'] : 1;
            $itemsPerPage = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
            if (!in_array($itemsPerPage, [2, 5, 10, 25, 50, 100])) {
                $itemsPerPage = 10; // Valeur par défaut si invalide
            }
            $etudiant_a_modifier = null;
            $modalAction = '';
            $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Charger la liste des niveaux d'étude
            $listeNiveaux = $this->service->getNiveauxEtude();

            // Charger la liste des années académiques
            $listeAnneesAcad = $this->service->getAnneesAcademiques();

            // Charger les données de l'étudiant à modifier si num_etu est présent
            if (isset($_GET['num_etu']) && !empty($_GET['num_etu'])) {
                $etudiant_a_modifier = $this->service->getEtudiantById($_GET['num_etu']);
                if (!$etudiant_a_modifier) {
                    $GLOBALS['messageErreur'] = "Étudiant non trouvé.";
                }
            }

            // Enregistrer la consultation de la liste des étudiants
            // Gestion des actions GET pour les modales
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'edit' && isset($_GET['num_etu'])) {
                // Cette partie n'est plus nécessaire car on charge directement avec num_etu
                // On la garde pour compatibilité rétroactive
                $etudiant_a_modifier = $this->service->getEtudiantById($_GET['num_etu']);
                if (!$etudiant_a_modifier) {
                    $GLOBALS['messageErreur'] = "Étudiant non trouvé.";
                } else {
                    $modalAction = 'edit';
                    // Enregistrer la consultation d'un étudiant spécifique

                    // Si c'est une requête AJAX, renvoyer les données en JSON
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'num_etu' => $etudiant_a_modifier->num_carte_etud,
                            'nom_etu' => $etudiant_a_modifier->nom_etu,
                            'prenom_etu' => $etudiant_a_modifier->prenom_etu,
                            'date_naiss_etu' => $etudiant_a_modifier->date_naiss_etu,
                            'genre_etu' => $etudiant_a_modifier->genre_etu,
                            'email_etu' => $etudiant_a_modifier->email_etu,
                            'promotion_etu' => $etudiant_a_modifier->promotion_etu
                        ]);
                        exit;
                    }
                }
            }

            // Gestion des actions POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Ajout d'un nouvel étudiant
                if (isset($_POST['submit_add_etudiant'])) {
                    if (!canCreate('gestion_etudiants')) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                            http_response_code(403);
                            echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                            exit;
                        }
                        $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                        $_SESSION['error_type'] = 'permission_denied';
                        header('Location: layout.php?page=access_denied');
                        exit;
                    }
                    $result = $this->service->ajouterEtudiant($_POST, $_SESSION['id_utilisateur']);
                    if ($result['success']) {
                        $GLOBALS['messageSuccess'] = $result['message'];
                    } else {
                        $GLOBALS['messageErreur'] = $result['message'];
                        if (
                            strpos($result['message'], 'obligatoires') !== false ||
                            strpos($result['message'], 'existe déjà') !== false ||
                            strpos($result['message'], 'pas valide') !== false ||
                            strpos(strtolower($result['message']), 'invalide') !== false
                        ) {
                            return;
                        }
                    }
                }

                // Modification d'un étudiant
                if (isset($_POST['submit_modifier_etudiant'])) {
                    if (!canEdit('gestion_etudiants')) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                            http_response_code(403);
                            echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                            exit;
                        }
                        $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                        $_SESSION['error_type'] = 'permission_denied';
                        header('Location: layout.php?page=access_denied');
                        exit;
                    }
                    $result = $this->service->modifierEtudiant($_POST, $_SESSION['id_utilisateur']);
                    if ($result['success']) {
                        $GLOBALS['messageSuccess'] = $result['message'];
                    } else {
                        $GLOBALS['messageErreur'] = $result['message'];
                        if (
                            strpos($result['message'], 'obligatoires') !== false ||
                            strpos($result['message'], 'existe déjà') !== false ||
                            strpos($result['message'], 'pas valide') !== false ||
                            strpos(strtolower($result['message']), 'invalide') !== false
                        ) {
                            return;
                        }
                    }
                }

                // Suppression d'étudiants
                if (isset($_POST['selected_ids']) && !empty($_POST['selected_ids'])) {
                    if (!canDelete('gestion_etudiants')) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                            http_response_code(403);
                            echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                            exit;
                        }
                        $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                        $_SESSION['error_type'] = 'permission_denied';
                        header('Location: layout.php?page=access_denied');
                        exit;
                    }
                    $result = $this->service->supprimerEtudiants($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                    if ($result['success']) {
                        $GLOBALS['messageSuccess'] = $result['message'];
                    } else {
                        $GLOBALS['messageErreur'] = $result['message'];
                    }
                }
            }

            // Récupération des données pour l'affichage
            $listeEtudiants = $this->service->getAllEtudiants();

            // Filtrer les étudiants si un terme de recherche est présent
            $listeEtudiants = $this->service->filtrerEtudiants($listeEtudiants, $searchTerm);

            // Paginer les résultats
            $pagination = $this->service->paginer($listeEtudiants, $currentPage, $itemsPerPage);

            // Préparation des données pour la vue
            $GLOBALS['listeEtudiants'] = $pagination['items'];
            $GLOBALS['allEtudiants'] = $listeEtudiants;
            $GLOBALS['etudiant_a_modifier'] = $etudiant_a_modifier;
            $GLOBALS['modalAction'] = $modalAction;
            $GLOBALS['currentPage'] = $pagination['currentPage'];
            $GLOBALS['totalPages'] = $pagination['totalPages'];
            $GLOBALS['totalItems'] = $pagination['totalItems'];
            $GLOBALS['startIndex'] = $pagination['startIndex'];
            $GLOBALS['endIndex'] = $pagination['endIndex'];
            $GLOBALS['itemsPerPage'] = $pagination['itemsPerPage'];
            $GLOBALS['searchTerm'] = $searchTerm;
            $GLOBALS['listeNiveaux'] = $listeNiveaux;
            $GLOBALS['listeAnneesAcad'] = $listeAnneesAcad;

        } catch (Exception $e) {
            error_log("Erreur dans GestionEtudiantController::index : " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}
