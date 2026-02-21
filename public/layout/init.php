<?php
require_once __DIR__ . '/../../app/Core/Autoload.php';

use CheckMaster\Core\Session;
use CheckMaster\Core\Bootstrap;

Bootstrap::init();
Session::start();

// Bufferiser la sortie pour injecter CSRF sur les formulaires legacy (migration progressive).
ob_start();

include __DIR__ . '/../../app/config/database.php';
include __DIR__ . '/../../app/controllers/AuthController.php';
include __DIR__ . '/../../app/controllers/MenuController.php';
include __DIR__ . '/../../app/middlewares/PermissionMiddleware.php';
include __DIR__ . '/../../app/utils/permissions_helper.php';

use CheckMaster\Security\RoutePermissionService;

include __DIR__ . '/../menu.php';
include __DIR__ . '/../../ressources/routes/gestionUtilisateurRoutes.php';
include __DIR__ . '/../../ressources/routes/gestionRhRoutes.php';
include __DIR__ . '/../../ressources/routes/gestionDashboardRoutes.php';
include __DIR__ . '/../../ressources/routes/dashboardEnseignantRoutes.php';
include __DIR__ . '/../../ressources/routes/gestionScolariteRoutes.php';
include __DIR__ . '/../../ressources/routes/gestionNotesRoutes.php';
include __DIR__ . '/../../ressources/routes/gestionCandidaturesRoutes.php';
include __DIR__ . '/../../ressources/routes/listeEtudiantsRoutes.php';
include __DIR__ . '/../../ressources/routes/dossierAcademiqueRoutes.php';
include __DIR__ . '/../../ressources/routes/verificationRapportsRoutes.php';
include __DIR__ . '/../../ressources/routes/evaluationDossiersRoutes.php';
include __DIR__ . '/../../ressources/routes/sauvegardeRestaurationRoutes.php';
include __DIR__ . '/../../ressources/routes/notesResultatsRoutes.php';
include __DIR__ . '/../../ressources/routes/archivesDossiersSoutenanceRoutes.php';
include __DIR__ . '/../../ressources/routes/auditRoutes.php';
include __DIR__ . '/../../ressources/routes/redactionCompteRenduRoutes.php';
include __DIR__ . '/../../ressources/routes/archivesCompteRenduRoutes.php';
include __DIR__ . '/../../ressources/routes/archiveHistoryRoutes.php';
include __DIR__ . '/../../ressources/routes/adminCandidatureRoutes.php';
include __DIR__ . '/../../ressources/routes/adminReclamationRoutes.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: page_connexion.php');
    exit;
} else {
    // Protection CSRF globale pour toutes les actions POST du legacy.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!\CheckMaster\Core\Csrf::validate($_POST['csrf_token'] ?? null)) {
            // AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez réessayer.']);
                exit;
            }

            $_SESSION['error_message'] = 'Session expirée. Veuillez réessayer.';
            $_SESSION['error_type'] = 'csrf';
            $fallback = 'layout.php?page=' . urlencode($_GET['page'] ?? 'dashboard');
            $redirect = $_SERVER['HTTP_REFERER'] ?? $fallback;
            header('Location: ' . $redirect);
            exit;
        }
    }

    $permissionMiddleware = new PermissionMiddleware();
    $menuController = new MenuController();
    $menuHierarchique = $menuController->genererMenuHierarchique($_SESSION['id_GU']);

    $currentMenuSlug = isset($_GET['page']) ? $_GET['page'] : '';
    $currentPageLabel = '';

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && empty($_GET['_r'])) {
        $action = $_GET['action'] ?? '';
        if ($currentMenuSlug === 'sauvegarde_restauration') {
            header('Location: index.php?_path=/admin/backups');
            exit;
        }
        if ($currentMenuSlug === 'gestion_utilisateurs') {
            header('Location: index.php?_path=/admin/users');
            exit;
        }
    }

    if (!empty($currentMenuSlug)) {
        foreach ($menuHierarchique as $item) {
            foreach ($item['fonctionnalites'] as $fonc) {
                $query = parse_url($fonc->url_fonctionnalite, PHP_URL_QUERY);
                if ($query) {
                    parse_str($query, $params);
                    if (isset($params['page']) && $params['page'] === $currentMenuSlug) {
                        $currentPageLabel = $fonc->label_fonctionnalite;
                        break 2;
                    }
                }
            }
        }
    }

    if (empty($currentPageLabel)) {
        $specialPages = [
            'archive_comptes_rendus' => 'Archives des comptes rendus',
            'redaction_compte_rendu' => 'Rédaction de compte rendu'
        ];
        if (isset($specialPages[$currentMenuSlug])) {
            $currentPageLabel = $specialPages[$currentMenuSlug];
        }
    }

    if (empty($currentMenuSlug) && !empty($menuHierarchique)) {
        $firstCategorie = $menuHierarchique[0];
        if (!empty($firstCategorie['fonctionnalites'])) {
            $firstFonc = $firstCategorie['fonctionnalites'][0];
            parse_str(parse_url($firstFonc->url_fonctionnalite, PHP_URL_QUERY), $params);
            if (isset($params['page'])) {
                $currentMenuSlug = $params['page'];
                $currentPageLabel = $firstFonc->label_fonctionnalite;
                header('Location: layout.php?page=' . urlencode($currentMenuSlug));
                exit;
            }
        }
    }

    $menuView = new MenuView();
    $menuHTML = $menuView->afficherMenuHierarchique($menuHierarchique, $currentMenuSlug);

    $currentAction = null;
    $contentFile = '';
    $partialsBasePath = __DIR__ . '/../../ressources/views/';
}
