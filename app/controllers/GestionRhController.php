<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionRhService.php';
require_once __DIR__ . '/../Services/TabularImportService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\GestionRhService;
use CheckMaster\Services\TabularImportService;
class GestionRhController
{
    private $baseViewPath;
    /** @var GestionRhService */
    private $service;
    /** @var TabularImportService */
    private $importService;

    private function getPermissionSlug(): string
    {
        $page = (string) ($_GET['page'] ?? 'gestion_rh');
        if (in_array($page, ['maj_enseignant', 'maj_personnel_admin', 'gestion_rh'], true)) {
            return $page;
        }

        return 'gestion_rh';
    }

    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/gestion_rh_content.php';
        $this->service = new GestionRhService(Database::getConnection());
        $this->importService = new TabularImportService(Database::getConnection());
    }
    public function index()
    {
        $permissionSlug = $this->getPermissionSlug();
        $messageErreur = '';
        $messageSuccess = '';
        $enseignant_a_modifier = null;
        $pers_admin_a_modifier = null;
        if (isset($_GET['tab']) && $_GET['tab'] === 'enseignant') {
            // Ajout ou modification d'un enseignant
            if (isset($_POST['btn_add_enseignant']) || isset($_POST['btn_modifier_enseignant'])) {
                if (!canCreate($permissionSlug) && !canEdit($permissionSlug)) {
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
                $result = $this->service->saveEnseignant($_POST, $_SESSION['id_utilisateur']);
                if ($result['success']) {
                    $messageSuccess = $result['message'];
                } else {
                    $messageErreur = $result['message'];
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!canDelete($permissionSlug)) {
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
                $result = $this->service->deleteMultipleEnseignants($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                if ($result['success']) {
                    $messageSuccess = $result['message'];
                } else {
                    $messageErreur = $result['message'];
                }
            }
            // Récupération de l'enseignant à modifier
            $enseignant_a_modifier = null;
            if (isset($_GET['id_enseignant'])) {
                $enseignant_a_modifier = $this->service->getEnseignantById($_GET['id_enseignant']);
            }

        }
        // Gestion du personnel administratif
        else if (isset($_GET['tab']) && $_GET['tab'] === 'pers_admin') {
            // Ajout ou modification d'un membre du personnel
            if (isset($_POST['btn_add_pers_admin']) || isset($_POST['btn_modifier_pers_admin'])) {
                if (!canCreate($permissionSlug) && !canEdit($permissionSlug)) {
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
                $result = $this->service->savePersAdmin($_POST, $_SESSION['id_utilisateur']);
                if ($result['success']) {
                    $messageSuccess = $result['message'];
                } else {
                    $messageErreur = $result['message'];
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                if (!canDelete($permissionSlug)) {
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
                $result = $this->service->deleteMultiplePersAdmin($_POST['selected_ids'], $_SESSION['id_utilisateur']);
                if ($result['success']) {
                    $messageSuccess = $result['message'];
                } else {
                    $messageErreur = $result['message'];
                }
            }
            // Récupération du membre à modifier
            if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id_pers_admin'])) {
                $pers_admin_a_modifier = $this->service->getPersAdminById($_GET['id_pers_admin']);
            }
        }
       

        // Variables communes pour toutes les vues
        $GLOBALS['messageErreur'] = $messageErreur;
        $GLOBALS['messageSuccess'] = $messageSuccess;
        $GLOBALS['pers_admin_a_modifier'] = $pers_admin_a_modifier;;
        $GLOBALS['enseignant_a_modifier'] = $enseignant_a_modifier;
        $referenceLists = $this->service->getReferenceLists();
        foreach ($referenceLists as $key => $value) {
            $GLOBALS[$key] = $value;
        }

        if ((string) ($_GET['action'] ?? '') === 'importer') {
            $this->handleRhImport(
                (string) ($_GET['tab'] ?? ''),
                $permissionSlug,
                $referenceLists,
                $messageErreur,
                $messageSuccess
            );
        }
    }

    /**
     * @param array<string, mixed> $referenceLists
     */
    private function handleRhImport(string $tab, string $permissionSlug, array $referenceLists, string $messageErreur, string $messageSuccess): void
    {
        $entity = $tab === 'enseignant' ? 'enseignants' : 'personnel_admin';
        $importRows = [];
        $importFilename = '';
        $importSummary = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!canCreate($permissionSlug)) {
                $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                $_SESSION['error_type'] = 'permission_denied';
                header('Location: layout.php?page=access_denied');
                exit;
            }

            if (isset($_POST['submit_import_upload'])) {
                $parseResult = $this->importService->parseUploadedFile($_FILES['import_file'] ?? [], $entity);
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
                    $result = $this->importService->importRows($entity, $decodedRows, (int) ($_SESSION['id_utilisateur'] ?? 0));
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

        $specialitesOptions = [];
        foreach ((array) ($referenceLists['listeSpecialites'] ?? []) as $specialite) {
            $id = (string) ($specialite->id_specialite ?? '');
            if ($id !== '') {
                $specialitesOptions[$id] = (string) ($specialite->lib_specialite ?? $id);
            }
        }

        $typeEnseignantsOptions = [];
        foreach ((array) ($referenceLists['listeTypeEnseignants'] ?? []) as $typeEnseignant) {
            $id = (string) ($typeEnseignant->id_type_enseignant ?? '');
            if ($id !== '') {
                $typeEnseignantsOptions[$id] = (string) ($typeEnseignant->libelle ?? $id);
            }
        }

        $fonctionsOptions = [];
        foreach ((array) ($referenceLists['listeFonctions'] ?? []) as $fonction) {
            $id = (string) ($fonction->id_fonction ?? '');
            if ($id !== '') {
                $fonctionsOptions[$id] = (string) ($fonction->lib_fonction ?? $id);
            }
        }

        if ($entity === 'enseignants') {
            $config = [
                'entity' => 'enseignants',
                'title' => 'Import d\'enseignants',
                'subtitle' => 'Importez un tableau CSV/XLSX puis corrigez les lignes avant enregistrement.',
                'back_url' => '?page=maj_enseignant&tab=enseignant',
                'upload_url' => '?page=maj_enseignant&tab=enseignant&action=importer',
                'fields' => [
                    ['name' => 'id_enseignant', 'label' => 'Matricule', 'type' => 'text', 'required' => true],
                    ['name' => 'nom_enseignant', 'label' => 'Nom', 'type' => 'text', 'required' => true],
                    ['name' => 'prenom_enseignant', 'label' => 'Prénom', 'type' => 'text', 'required' => true],
                    ['name' => 'tel_enseignant', 'label' => 'Téléphone', 'type' => 'text', 'required' => false],
                    ['name' => 'mail_enseignant', 'label' => 'E-mail', 'type' => 'email', 'required' => false],
                    ['name' => 'id_specialite', 'label' => 'Spécialité', 'type' => 'select', 'required' => false, 'options' => $specialitesOptions],
                    ['name' => 'id_genre', 'label' => 'Genre', 'type' => 'select', 'required' => false, 'options' => ['' => '-', 'M' => 'M', 'F' => 'F', 'N' => 'N']],
                    ['name' => 'type_enseignant', 'label' => 'Type', 'type' => 'select', 'required' => false, 'options' => $typeEnseignantsOptions],
                    ['name' => 'id_etablissement_origin', 'label' => 'Etab. origine', 'type' => 'text', 'required' => false],
                ],
                'expected_headers' => ['id_enseignant', 'nom_enseignant', 'prenom_enseignant', 'tel_enseignant', 'mail_enseignant', 'id_specialite', 'id_genre', 'type_enseignant', 'id_etablissement_origin'],
            ];
        } else {
            $config = [
                'entity' => 'personnel_admin',
                'title' => 'Import du personnel administratif',
                'subtitle' => 'Importez un tableau CSV/XLSX puis corrigez les lignes avant enregistrement.',
                'back_url' => '?page=maj_personnel_admin&tab=pers_admin',
                'upload_url' => '?page=maj_personnel_admin&tab=pers_admin&action=importer',
                'fields' => [
                    ['name' => 'id_pers_admin', 'label' => 'ID', 'type' => 'text', 'required' => false],
                    ['name' => 'nom_pers_admin', 'label' => 'Nom', 'type' => 'text', 'required' => true],
                    ['name' => 'prenom_pers_admin', 'label' => 'Prénom', 'type' => 'text', 'required' => true],
                    ['name' => 'id_genre', 'label' => 'Genre', 'type' => 'select', 'required' => false, 'options' => ['' => '-', 'M' => 'M', 'F' => 'F', 'N' => 'N']],
                    ['name' => 'email_pers_admin', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
                    ['name' => 'tel_pers_admin', 'label' => 'Téléphone', 'type' => 'text', 'required' => true],
                    ['name' => 'poste', 'label' => 'Poste', 'type' => 'select', 'required' => true, 'options' => $fonctionsOptions],
                    ['name' => 'date_embauche', 'label' => 'Date embauche', 'type' => 'date', 'required' => true],
                ],
                'expected_headers' => ['id_pers_admin', 'nom_pers_admin', 'prenom_pers_admin', 'id_genre', 'email_pers_admin', 'tel_pers_admin', 'poste', 'date_embauche'],
            ];
        }

        $GLOBALS['messageErreur'] = $messageErreur;
        $GLOBALS['messageSuccess'] = $messageSuccess;
        $GLOBALS['tabularImportConfig'] = $config;
        $GLOBALS['tabularImportRows'] = $importRows;
        $GLOBALS['tabularImportFilename'] = $importFilename;
        $GLOBALS['tabularImportSummary'] = $importSummary;
    }
}
