<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionEtudiantService.php';
require_once __DIR__ . '/../Services/TabularImportService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../Core/Autoload.php';

use CheckMaster\Core\Session;
use CheckMaster\Services\GestionEtudiantService;
use CheckMaster\Services\TabularImportService;


class GestionEtudiantController
{
    /** @var GestionEtudiantService */
    private $service;
    /** @var TabularImportService */
    private $importService;
    private $baseViewPath;

    public function __construct()
    {
        Session::start();

        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->service = new GestionEtudiantService(Database::getConnection());
        $this->importService = new TabularImportService(Database::getConnection());
    }

    public function index()
    {
        try {
            $currentPage = isset($_GET['p']) ? (int) $_GET['p'] : 1;
            $itemsPerPage = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
            if (!in_array($itemsPerPage, [5, 10, 25, 50, 100], true)) {
                $itemsPerPage = 10; // Valeur par défaut si invalide
            }
            $currentAction = (string) ($_GET['action'] ?? '');
            $etudiant_a_modifier = null;
            $modalAction = '';
            $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Charger la liste des niveaux d'étude
            $listeNiveaux = $this->service->getNiveauxEtude();

            // Charger la liste des années académiques
            $listeAnneesAcad = $this->service->getAnneesAcademiques();

            if ($currentAction === 'importer_etudiants') {
                $this->handleStudentImport($listeAnneesAcad);
                return;
            }

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
                            'num_etu' => $etudiant_a_modifier->num_ident_etud ?? $etudiant_a_modifier->num_carte_etud,
                            'num_carte_etud' => $etudiant_a_modifier->num_carte_etud,
                            'num_ident_etud' => $etudiant_a_modifier->num_ident_etud ?? '',
                            'nom_etu' => $etudiant_a_modifier->nom_etu,
                            'prenom_etu' => $etudiant_a_modifier->prenom_etu,
                            'date_naiss_etu' => $etudiant_a_modifier->date_naiss_etu,
                            'id_genre' => $etudiant_a_modifier->id_genre,
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

    /**
     * @param array<int, object> $listeAnneesAcad
     */
    private function handleStudentImport(array $listeAnneesAcad): void
    {
        $messageErreur = '';
        $messageSuccess = '';
        $importRows = [];
        $importFilename = '';
        $importSummary = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!canCreate('gestion_etudiants')) {
                $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                $_SESSION['error_type'] = 'permission_denied';
                header('Location: layout.php?page=access_denied');
                exit;
            }

            if (isset($_POST['submit_import_upload'])) {
                $parseResult = $this->importService->parseUploadedFile($_FILES['import_file'] ?? [], 'etudiants');
                if ($parseResult['success'] ?? false) {
                    $importRows = is_array($parseResult['rows'] ?? null) ? $parseResult['rows'] : [];
                    $importFilename = (string) ($parseResult['filename'] ?? '');
                    $messageSuccess = (string) ($parseResult['message'] ?? '');
                } else {
                    $messageErreur = (string) ($parseResult['message'] ?? 'Le fichier n\'a pas pu être analysé.');
                }
            } elseif (isset($_POST['submit_import_commit'])) {
                $decodedRows = json_decode((string) ($_POST['import_payload'] ?? '[]'), true);
                if (!is_array($decodedRows) || $decodedRows === []) {
                    $messageErreur = 'Aucune ligne à importer.';
                } else {
                    $importRows = $decodedRows;
                    $importFilename = (string) ($_POST['import_filename'] ?? '');
                    $result = $this->importService->importRows('etudiants', $decodedRows, (int) ($_SESSION['id_utilisateur'] ?? 0));
                    $importSummary = $result['summary'] ?? null;
                    if ($result['success'] ?? false) {
                        $messageSuccess = (string) ($result['message'] ?? 'Import terminé.');
                        $importRows = [];
                    } else {
                        $messageErreur = (string) ($result['message'] ?? 'Des erreurs sont survenues pendant l\'import.');
                    }
                }
            }
        }

        $promotionOptions = [];
        foreach ($listeAnneesAcad as $annee) {
            $id = (string) ($annee->id_annee_acad ?? '');
            $debut = !empty($annee->date_deb) ? date('Y', strtotime((string) $annee->date_deb)) : '';
            $fin = !empty($annee->date_fin) ? date('Y', strtotime((string) $annee->date_fin)) : '';
            if ($id !== '') {
                $promotionOptions[$id] = trim($debut . '-' . $fin, '-');
            }
        }

        $GLOBALS['messageErreur'] = $messageErreur;
        $GLOBALS['messageSuccess'] = $messageSuccess;
        $GLOBALS['tabularImportConfig'] = [
            'entity' => 'etudiants',
            'title' => 'Import d\'étudiants',
            'subtitle' => 'Chargez un fichier CSV/XLSX, corrigez les lignes si nécessaire puis lancez l\'import.',
            'back_url' => '?page=gestion_etudiants&action=ajouter_des_etudiants',
            'upload_url' => '?page=gestion_etudiants&action=importer_etudiants',
            'fields' => [
                ['name' => 'num_ident_etud', 'label' => 'Identifiant MESRS', 'type' => 'text', 'required' => false],
                ['name' => 'num_carte_etud', 'label' => 'N° Carte Etud.', 'type' => 'text', 'required' => true],
                ['name' => 'nom_etu', 'label' => 'Nom', 'type' => 'text', 'required' => true],
                ['name' => 'prenom_etu', 'label' => 'Prénom', 'type' => 'text', 'required' => true],
                ['name' => 'date_naiss_etu', 'label' => 'Date naissance', 'type' => 'date', 'required' => true],
                ['name' => 'id_genre', 'label' => 'Genre', 'type' => 'select', 'required' => true, 'options' => ['M' => 'M', 'F' => 'F', 'N' => 'N']],
                ['name' => 'email_etu', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
                ['name' => 'promotion_etu', 'label' => 'Promotion', 'type' => 'select', 'required' => true, 'options' => $promotionOptions],
            ],
            'expected_headers' => ['num_ident_etud', 'num_carte_etud', 'nom_etu', 'prenom_etu', 'date_naiss_etu', 'id_genre', 'email_etu', 'promotion_etu'],
        ];
        $GLOBALS['tabularImportRows'] = $importRows;
        $GLOBALS['tabularImportFilename'] = $importFilename;
        $GLOBALS['tabularImportSummary'] = $importSummary;
    }
}
