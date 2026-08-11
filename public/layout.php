<?php
require_once __DIR__ . '/../app/Core/Autoload.php';

use CheckMaster\Core\Csrf;
use CheckMaster\Core\Session;
use CheckMaster\Core\Bootstrap;
use CheckMaster\Core\Messages;
use CheckMaster\Core\AppConfig;
use CheckMaster\Core\ServiceContainer;

Bootstrap::init();
Session::start();

// Initialiser le conteneur de services (DI)
$container = ServiceContainer::getInstance();
$container->setPDO(\Database::getConnection());
$container->registerAppServices();

if (!function_exists('cm_candidature_debug_log')) {
    function cm_candidature_debug_log(string $message, array $context = []): void
    {
        $logPath = AppConfig::logPath() . DIRECTORY_SEPARATOR . 'candidature_soutenance_debug.log';
        $fallbackLogPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'candidature_soutenance_debug.log';
        $line = date('c') . ' [layout:candidature_soutenance] ' . $message;
        if ($context !== []) {
            $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $line .= ' | context=' . ($json !== false ? $json : '[json_error]');
        }

        error_log($line);
        @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
        @file_put_contents($fallbackLogPath, $line . PHP_EOL, FILE_APPEND);
    }
}

// Bufferiser la sortie pour injecter CSRF sur les formulaires legacy (migration progressive).
ob_start();





require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/utils/AcademicYear.php';

// ── Année académique globale ── résolution AVANT chargement des routes/contrôleurs
// afin que $_SESSION['global_annee_id'] soit disponible dans toutes les vues.
$globalAcademicYears = [];
$currentGlobalYear = '';
$currentGlobalYearId = null;
$currentGlobalYearIsAll = false;
$activeGlobalYear = '';
$activeGlobalYearId = null;
$writableGlobalYear = '';
$writableGlobalYearId = null;
try {
    $__context = AcademicYear::bootstrapSession($container->getPDO(), $_GET, $_SESSION);

    foreach (($__context['years'] ?? []) as $__year) {
        $globalAcademicYears[(int) ($__year['id'] ?? 0)] = (string) ($__year['label'] ?? '');
    }

    $currentGlobalYear = (string) ($__context['selected']['label'] ?? '');
    $currentGlobalYearId = isset($__context['selected']['id']) ? (int) $__context['selected']['id'] : null;
    $currentGlobalYearIsAll = !empty($__context['all_selected']);
    $activeGlobalYear = (string) ($__context['active']['label'] ?? '');
    $activeGlobalYearId = isset($__context['active']['id']) ? (int) $__context['active']['id'] : null;
    $writableGlobalYear = (string) ($__context['writable']['label'] ?? '');
    $writableGlobalYearId = isset($__context['writable']['id']) ? (int) $__context['writable']['id'] : null;
} catch (\Throwable $e) {
    error_log('Layout: erreur chargement années académiques: ' . $e->getMessage());
}

include __DIR__ . '/../app/controllers/AuthController.php';
include __DIR__ . '/../app/controllers/MenuController.php';
include __DIR__ . '/../app/middlewares/PermissionMiddleware.php';
include __DIR__ . '/../app/utils/permissions_helper.php';
include_once __DIR__ . '/../app/utils/FormattingUtils.php';
include_once __DIR__ . '/../app/utils/ComponentHelper.php';
include_once __DIR__ . '/../app/utils/FormHelper.php';
include_once __DIR__ . '/../app/utils/TableHelper.php';
include_once __DIR__ . '/../app/utils/PaginationHelper.php';
include_once __DIR__ . '/../app/utils/AuditPresentationHelper.php';
require_once __DIR__ . '/../app/Services/AuditService.php';

use CheckMaster\Security\PermissionContextFactory;
use CheckMaster\Security\RoutePermissionService;
use CheckMaster\Security\RequestContext;
use CheckMaster\Security\AuthenticationGuard;
use CheckMaster\Security\CsrfGuard;
use CheckMaster\Security\AuthorizationGuard;
use CheckMaster\Security\LegacyCompatibilityLayer;

// ── Compatibilité des alias legacy (maj_etudiant → gestion_etudiants, ...) ──
$legacyCompatibilityLayer = new LegacyCompatibilityLayer();
$legacyCompatibilityLayer->apply();

// Contexte de requête normalisé (page/action/méthode/AJAX/JSON) — créé APRÈS
// l'application des alias pour que page/action reflètent les réécritures.
$requestContext = RequestContext::fromGlobals();

// ── Année académique globale ──

// ── Gardes : authentification → CSRF → autorisation ──
$authGuard = new AuthenticationGuard();
$authResponse = $authGuard->requireAuthenticated();
if ($authResponse !== null) {
    $authResponse->send();
    exit;
}

$isCandidatureStagePost = ($requestContext->page === 'candidature_soutenance') && $requestContext->isPost();
if ($isCandidatureStagePost) {
    cm_candidature_debug_log('request_entered_layout', [
        'get' => $_GET,
        'post' => $_POST,
        'session' => [
            'id_utilisateur' => $_SESSION['id_utilisateur'] ?? null,
            'id_GU' => $_SESSION['id_GU'] ?? null,
            'lib_GU' => $_SESSION['lib_GU'] ?? null,
            'num_etu' => $_SESSION['num_etu'] ?? null,
            'login_utilisateur' => $_SESSION['login_utilisateur'] ?? null,
        ],
        'headers' => [
            'x_requested_with' => $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        ],
    ]);
}

// Protection CSRF globale pour toutes les actions POST du legacy.
if ($requestContext->isPost()) {
    $csrfToken = $requestContext->csrfToken();
    if ($isCandidatureStagePost) {
        cm_candidature_debug_log('csrf_token_received', [
            'has_token' => $csrfToken !== null && $csrfToken !== '',
            'token_length' => is_string($csrfToken) ? strlen($csrfToken) : 0,
        ]);
    }

    // Stocker le JSON décodé pour que le contrôleur puisse y accéder sans relire php://input
    if ($csrfToken === null && $requestContext->isJson) {
        $rawJsonInput = file_get_contents('php://input');
        $jsonInput = json_decode($rawJsonInput, true);
        if (is_array($jsonInput)) {
            $csrfToken = $jsonInput['csrf_token'] ?? null;
            $GLOBALS['decoded_json_input'] = $jsonInput;
            if ($isCandidatureStagePost) {
                cm_candidature_debug_log('csrf_token_loaded_from_json', [
                    'json_keys' => array_keys($jsonInput),
                    'has_token' => isset($jsonInput['csrf_token']) && $jsonInput['csrf_token'] !== '',
                ]);
            }
        }
    }

    $csrfValid = Csrf::validate($csrfToken);
    if ($isCandidatureStagePost) {
        cm_candidature_debug_log($csrfValid ? 'csrf_validation_passed' : 'csrf_validation_failed', [
            'has_token' => $csrfToken !== null && $csrfToken !== '',
        ]);
    }

    if (!$csrfValid) {
        $csrfGuard = new CsrfGuard();
        $csrfResponse = $csrfGuard->check($requestContext);
        if ($csrfResponse !== null) {
            $csrfResponse->send();
            exit;
        }
    }
}

$permissionMiddleware = new PermissionMiddleware();
$routePermissionService = new RoutePermissionService($container->getPDO());
$permissionContextFactory = new PermissionContextFactory($container->getPDO());
$currentMenuSlugForGate = $requestContext->page;
$method = $requestContext->method;

$isOwnProfileUpdate = $requestContext->isOwnProfileUpdate();

$legacyAccessAllowed = true;
// Les routes de service avec leur propre securite interne (docviewer, etc.)
// sont exemptees du controle de permissions legacy. Elles gerent elles-memes
// l'authentification et les droits (session + DocumentRegistry::canView()).
$bypassLegacyPermissionCheck = in_array($currentMenuSlugForGate, ['docviewer'], true);
if ($currentMenuSlugForGate !== '' && !$isOwnProfileUpdate && !$bypassLegacyPermissionCheck) {
    $legacyAccessAllowed = $routePermissionService->canAccessLegacy((int) $_SESSION['id_GU'], $_GET, $_POST, $method);
    if ($isCandidatureStagePost) {
        cm_candidature_debug_log('permission_check', [
            'page' => $currentMenuSlugForGate,
            'method' => $method,
            'group_id' => $_SESSION['id_GU'] ?? null,
            'allowed' => $legacyAccessAllowed,
            'action' => $_GET['action'] ?? null,
        ]);
    }
}

if ($currentMenuSlugForGate !== '' && !$isOwnProfileUpdate && !$legacyAccessAllowed) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Accès refusé. Permissions insuffisantes.']);
        exit;
    }

    $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
    $_SESSION['error_type'] = 'permission_denied';

    $redirect = 'layout.php?page=access_denied';
    header('Location: ' . $redirect);
    exit;
}

include __DIR__ . '/menu.php';

// ── Registre centralisé des routes legacy (page/action) ──
// Les fichiers de routes sont chargés par domaine ; chaque route est déclarée
// avec un nom unique pour permettre la migration vers des routes nommées.
$routeRegistry = new \CheckMaster\Core\RouteRegistry();
$routeRegistry->loadLegacyFiles(__DIR__ . '/../ressources/routes', [
    'gestionUtilisateurRoutes.php',
    'gestionRhRoutes.php',
    'gestionDashboardRoutes.php',
    'dashboardEnseignantRoutes.php',
    'gestionScolariteRoutes.php',
    'gestionNotesRoutes.php',
    'gestionCandidaturesRoutes.php',
    'listeEtudiantsRoutes.php',
    'dossierAcademiqueRoutes.php',
    'verificationRapportsRoutes.php',
    'gestionReclamationsScolariteRoutes.php',
    'evaluationDossiersRoutes.php',
    'programmationSoutenanceRoutes.php',
    'plannificationSoutenanceRoutes.php',
    'gestionDossiersCandidaturesRoutes.php',
    'archivesDossiersSoutenanceRoutes.php',
    'sauvegardeRestaurationRoutes.php',
    'notesResultatsRoutes.php',
    'auditRoutes.php',
    'criteresEvaluationRoutes.php',
    'redactionCompteRenduRoutes.php',
    'archivesCompteRenduRoutes.php',
    'archiveHistoryRoutes.php',
    'archiveRoutes.php',
    'editionBulletinRoutes.php',
    'docviewerRoutes.php',
    'documentsRoutes.php',
    'ficheEtudiantRoutes.php',
    'ficheCommissionRoutes.php',
    'fichePersAdminRoutes.php',
    'historiqueInscriptionsRoutes.php',
    'workflowValidationRoutes.php',
    'ficheEnseignantRoutes.php',
    'ficheFinanciereRoutes.php',
    'dashboardDirectionRoutes.php',
    'rechercheGlobaleRoutes.php',
    'echeancierEtudiantRoutes.php',
    'exportMasseDocumentsRoutes.php',
    'historiqueModificationsRoutes.php',
    'timelineParcoursRoutes.php',
    'annuaireEnseignantsRoutes.php',
    'parametreGenerauxRouteur.php',
]);
$GLOBALS['routeRegistry'] = $routeRegistry;

$menuController = new MenuController();

$currentMenuSlug = isset($_GET['page']) ? $_GET['page'] : '';
$currentPageLabel = '';
$menuHierarchique = [];
$menuHTML = '';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Déterminer le label de page AVANT de construire le menu (pour les pages connues)
$canonicalPageLabels = [
    'admin_historique' => 'Historique et archivage',
    'consultation_cr_etud' => 'Mon compte rendu',
    'candidature_soutenance' => 'Ma candidature à la soutenance',
    'dashboard' => 'Tableau de bord — Administration',
    'dashboard_commission' => 'Tableau de bord — Commission',
    'dashboard_enseignant' => 'Espace enseignant — Participations jurys',
    'dashboard_scolarite' => 'Tableau de bord scolarité',
    'dashboard_securite' => 'Tableau de bord sécurité',
    'edition_bulletin' => 'Édition des PV finaux',
    'evaluation_dossiers' => 'Évaluation des dossiers',
    'gestion_dossiers_candidatures' => 'Gestion des dossiers de candidatures',
    'gestion_notes_evaluations' => 'Gestion des notes et évaluations',
    'gestion_reclamations' => 'Mes réclamations',
    'gestion_reclamations_scolarite' => 'Gestion des réclamations',
    'gestion_scolarite' => 'Inscriptions / Paiements',
    'gestion_utilisateurs' => 'Gestion des utilisateurs',
    'maj_enseignant' => 'Mise à jour enseignant',
    'maj_personnel_admin' => 'Mise à jour personnel administratif',
    'mise_en_ligne_memoire' => 'Mise en ligne des mémoires',
    'validation_memoires' => 'Validation des memoires',
    'parametres_generaux' => 'Paramètres généraux',
    'parametres_specifiques' => 'Paramètres spécifiques',
    'piste_audit' => "Piste d'audit",
    'processus_validation' => 'Suivi de validation des rapports',
    'profil' => 'Mon profil',
    'programmation_ens' => 'Mes soutenances — Programme',
    'programmation_soutenance' => 'Programmation des soutenances',
    'redaction_compte_rendu' => 'Rédaction du compte rendu',
    'reception_rapport_com' => 'Réception des rapports',
    'repertoire_enseignant' => 'Répertoire des documents',
    'sauvegarde_restauration' => 'Sauvegardes et restauration',
    'tableau_bord_enseignant' => 'Mon tableau de bord — Enseignant',
    'fiche_etudiant_complete' => 'Fiche Étudiante Complete',
    'suivi_scolarite' => 'Suivi & Scolarité',
    'commissions_archives' => 'Commissions & Archives',
    'membres_commission' => 'Membres actifs de la commission',
    'enseignant_gestion' => 'Gestion des Enseignants',
    'outils_direction' => 'Outils & Direction',
    'gestion_notes' => 'Gestion des Notes',
    'archive_hub' => 'Archives — Hub',
    'archive_etudiants' => 'Archives Étudiants',
    'archive_documents' => 'Archives Documents',
    'archive_soutenances' => 'Archives Soutenances',
    'archive_admins' => 'Archives Administrateurs',
];
if (isset($canonicalPageLabels[$currentMenuSlug])) {
    $currentPageLabel = $canonicalPageLabels[$currentMenuSlug];
}

// Skip menu building for AJAX requests (the menu is not rendered in AJAX responses)
if (!$isAjax) {
    // NOUVEAU : Menu hiérarchique avec catégories
    $menuHierarchique = $menuController->genererMenuHierarchique($_SESSION['id_GU']);

    // Chercher le label dans le menu hiérarchique si pas trouvé dans le mapping
    if (empty($currentPageLabel) && !empty($currentMenuSlug)) {
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

    // Pages spéciales
    if (empty($currentPageLabel)) {
        $specialPages = [
            'archive_comptes_rendus' => 'Archives des comptes rendus',
            'redaction_compte_rendu' => 'Rédaction de compte rendu',
            'tableau_bord_enseignant' => 'Tableau de bord enseignant',
        ];
        if (isset($specialPages[$currentMenuSlug])) {
            $currentPageLabel = $specialPages[$currentMenuSlug];
        }
    }

    // Redirection si pas de page spécifiée
    if (empty($currentMenuSlug) && !empty($menuHierarchique)) {
        $firstCategorie = $menuHierarchique[0];
        if (!empty($firstCategorie['fonctionnalites'])) {
            $firstFonc = $firstCategorie['fonctionnalites'][0];
            $firstFoncUrl = (string) ($firstFonc->url_fonctionnalite ?? '');
            $firstFoncQuery = (string) (parse_url($firstFoncUrl, PHP_URL_QUERY) ?? '');
            parse_str($firstFoncQuery, $params);
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
}

$GLOBALS['caps'] = isset($_SESSION['id_GU'])
    ? $permissionContextFactory->forCurrentRequest((int) $_SESSION['id_GU'], $_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'GET')
    : ['view' => false, 'create' => false, 'edit' => false, 'delete' => false, 'slug' => ''];

// ANCIEN : Menu plat (commenté pour migration progressive)
// $menuHTML = $menuView->afficherMenu($traitements, $currentMenuSlug);

$currentAction = null;
$contentFile = '';
$auditService = null;
// IMPORTANT: chemin absolu (car layout peut être appelé via /public/app/layout.php)
$partialsBasePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
switch ($currentMenuSlug) {
    case 'profil':
        if ($auditService === null) {
            try {
                $auditService = $container->get('AuditService');
            } catch (\Throwable $e) {
                error_log('Layout profil: initialisation AuditService impossible: ' . $e->getMessage());
            }
        }

        $profileContactEmail = '';
        try {
            $authService = $container->get('AuthService');
            $profileContactEmail = (string) ($authService->getContactEmail($_SESSION['id_utilisateur'] ?? null) ?? '');
        } catch (\Throwable $e) {
            error_log('Layout profil: récupération email de contact impossible: ' . $e->getMessage());
        }
        $GLOBALS['profileContactEmail'] = $profileContactEmail;

        $isPasswordUpdateRequest = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')
            && (
                isset($_POST['update_password'])
                || (string) ($_POST['action'] ?? '') === 'update_password'
            );

        $isEmailUpdateRequest = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')
            && (
                isset($_POST['update_email'])
                || (string) ($_POST['action'] ?? '') === 'update_email'
            );

        if ($isEmailUpdateRequest) {
            $newEmail = (string) ($_POST['newEmail'] ?? $_POST['new_email'] ?? '');
            $confirmEmail = (string) ($_POST['confirmEmail'] ?? $_POST['confirm_email'] ?? '');
            $authService = $container->get('AuthService');
            $idUtilisateur = $_SESSION['id_utilisateur'] ?? null;
            $emailResult = $authService->updateEmail($idUtilisateur, $newEmail, $confirmEmail);
            $emailUpdated = is_array($emailResult) && !empty($emailResult['success']);
            if (is_array($emailResult)) {
                if ($emailUpdated) {
                    $GLOBALS['messageSuccess'] = $emailResult['message'] ?? '';
                    try {
                        $authService->notifierEmailModifie($idUtilisateur, $profileContactEmail, $newEmail);
                    } catch (\Throwable $e) {
                        error_log('Erreur notif email modifie: ' . $e->getMessage());
                    }
                } else {
                    $GLOBALS['messageErreur'] = $emailResult['message'] ?? '';
                }
            }
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            if ($emailUpdated) {
                $_SESSION['success'] = (string) ($GLOBALS['messageSuccess'] ?? 'Email de contact mis à jour avec succès.');
                if ($auditService instanceof \CheckMaster\Services\AuditService) {
                    $auditService->logRequestActivity((int) ($_SESSION['id_utilisateur'] ?? 0), $_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'POST');
                }

                if ($isAjax) {
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode([
                        'success' => true,
                        'redirect' => 'layout.php?page=profil&tab=profile',
                    ]);
                    exit;
                }

                header('Location: layout.php?page=profil&tab=profile');
                exit;
            }

            $_SESSION['error'] = (string) ($GLOBALS['messageErreur'] ?? 'Erreur lors de la mise à jour de l\'email de contact.');
            if ($auditService instanceof \CheckMaster\Services\AuditService) {
                $auditService->logRequestActivity((int) ($_SESSION['id_utilisateur'] ?? 0), $_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'POST');
            }

            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'message' => $_SESSION['error'],
                ]);
                exit;
            }

            header('Location: layout.php?page=profil&tab=profile');
            exit;
        }

        if ($isPasswordUpdateRequest) {
            $currentPassword = (string) ($_POST['currentPassword'] ?? $_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['newPassword'] ?? $_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirmPassword'] ?? $_POST['confirm_password'] ?? '');
            $authService = $container->get('AuthService');
            $passwordResult = $authService->updatePassword(
                $_SESSION['id_utilisateur'] ?? null,
                $currentPassword,
                $newPassword,
                $confirmPassword
            );
            $passwordUpdated = is_array($passwordResult) && !empty($passwordResult['success']);
            if (is_array($passwordResult)) {
                if ($passwordUpdated) {
                    $GLOBALS['messageSuccess'] = $passwordResult['message'] ?? '';
                    try {
                        $authService->notifierMdpChange((int) ($_SESSION['id_utilisateur'] ?? 0));
                    } catch (\Throwable $e) {
                        error_log('Erreur notif mdp change: ' . $e->getMessage());
                    }
                } else {
                    $GLOBALS['messageErreur'] = $passwordResult['message'] ?? '';
                }
            }
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            if ($passwordUpdated) {
                $_SESSION['success'] = (string) ($GLOBALS['messageSuccess'] ?? 'Mot de passe mis à jour avec succès.');
                if ($auditService instanceof \CheckMaster\Services\AuditService) {
                    $auditService->logRequestActivity((int) ($_SESSION['id_utilisateur'] ?? 0), $_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'POST');
                }

                if ($isAjax) {
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode([
                        'success' => true,
                        'redirect' => 'layout.php?page=profil&tab=password',
                    ]);
                    exit;
                }

                header('Location: layout.php?page=profil&tab=password');
                exit;
            }

            $_SESSION['error'] = (string) ($GLOBALS['messageErreur'] ?? 'Erreur lors de la mise à jour du mot de passe.');
            if ($auditService instanceof \CheckMaster\Services\AuditService) {
                $auditService->logRequestActivity((int) ($_SESSION['id_utilisateur'] ?? 0), $_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'POST');
            }

            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'message' => $_SESSION['error'],
                ]);
                exit;
            }

            header('Location: layout.php?page=profil&tab=password');
            exit;
        }

        $historyPage = isset($_GET['history_page']) ? max(1, (int) $_GET['history_page']) : 1;
        $historyPerPage = isset($_GET['history_limit']) ? (int) $_GET['history_limit'] : 10;
        if (!in_array($historyPerPage, [10, 25, 50], true)) {
            $historyPerPage = 10;
        }

        $historyFilters = [
            'date_debut' => '',
            'date_fin' => '',
            'statut' => '',
            'search' => '',
        ];
        $historyRows = [];
        $historyTotal = 0;
        $historyTotalPages = 1;

        if ($auditService instanceof \CheckMaster\Services\AuditService) {
            try {
                $historyFilters = $auditService->extractUserHistoryFilters($_GET);
                $historyUserId = (int) ($_SESSION['id_utilisateur'] ?? 0);
                $historyOffset = ($historyPage - 1) * $historyPerPage;
                $historyRows = $auditService->getUserAuditHistory($historyUserId, $historyFilters, $historyOffset, $historyPerPage);
                $historyTotal = $auditService->getTotalUserAuditHistory($historyUserId, $historyFilters);
                $historyTotalPages = max(1, (int) ceil($historyTotal / $historyPerPage));

                if ($historyPage > $historyTotalPages) {
                    $historyPage = $historyTotalPages;
                    $historyOffset = ($historyPage - 1) * $historyPerPage;
                    $historyRows = $auditService->getUserAuditHistory($historyUserId, $historyFilters, $historyOffset, $historyPerPage);
                }
            } catch (\Throwable $e) {
                error_log('Layout profil: chargement historique utilisateur impossible: ' . $e->getMessage());
            }
        }

        $GLOBALS['profileAuditHistory'] = $historyRows;
        $GLOBALS['profileAuditHistoryFilters'] = $historyFilters;
        $GLOBALS['profileAuditHistoryPage'] = $historyPage;
        $GLOBALS['profileAuditHistoryPerPage'] = $historyPerPage;
        $GLOBALS['profileAuditHistoryTotalPages'] = $historyTotalPages;
        $GLOBALS['profileAuditHistoryTotal'] = $historyTotal;

        $contentFile = $partialsBasePath . 'profil_content.php';
        $currentPageLabel = 'Mon profil';
        break;
    case 'parametres_generaux':
    case 'parametres_specifiques':
        // On charge le contrôleur manuellement pour être sûr qu'il s'exécute
        require_once __DIR__ . '/../app/controllers/ParametreController.php';
        $paramController = new ParametreController();

        if (isset($_GET['action'])) {
            $currentAction = $_GET['action'];

            if ($currentAction === 'traitements') {
                $contentFile = $currentMenuSlug === 'parametres_generaux'
                    ? $partialsBasePath . 'parametres_generaux_content.php'
                    : $partialsBasePath . 'parametres_specifiques_content.php';
                $currentPageLabel = $currentMenuSlug === 'parametres_generaux'
                    ? 'Paramètres Généraux'
                    : 'Paramètres Spécifiques';
                break;
            }

            // ════════════════════════════════════════════════════════
            // Pages migrées vers Paramètres Généraux (2026-06-05)
            // Ces pages ont leurs propres contrôleurs et vues.
            // ════════════════════════════════════════════════════════
            switch ($currentAction) {
                case 'modeles_pdf':
                    $pdfTemplates = $paramController->getPdfTemplatesCatalog();
                    $contentFile = $partialsBasePath . 'parametres_specifiques_modeles_pdf.php';
                    $currentPageLabel = 'Modèles PDF';
                    break 2;
                case 'suivi_scolarite':
                    $hubTab = (string) ($_GET['tab'] ?? 'fiche_financiere_annee');
                    switch ($hubTab) {
                        case 'fiche_financiere_annee':
                            require_once __DIR__ . '/../app/controllers/FicheFinanciereController.php';
                            $ficheFinanciereController = new FicheFinanciereController();
                            if ((string) ($_GET['sub_action'] ?? '') === 'detail_etudiant') {
                                $ficheFinanciereController->detailEtudiant();
                            }
                            $ficheFinanciereController->index();
                            break;
                        case 'historique_inscriptions':
                            require_once __DIR__ . '/../app/controllers/HistoriqueInscriptionsController.php';
                            $histData = (new HistoriqueInscriptionsController())->index();
                            $etudiant = $histData['etudiant'] ?? null;
                            if ($etudiant !== null && is_object($etudiant))
                                $etudiant = (array) $etudiant;
                            $parcours = $histData['parcours'] ?? [];
                            $notes = $histData['notes'] ?? [];
                            break;
                        case 'timeline_parcours_etudiant':
                            try {
                                require_once __DIR__ . '/../app/controllers/ArchiveEtudiantController.php';
                                $matriculeTl = trim((string) ($_GET['num_etu'] ?? $_GET['matricule'] ?? $_GET['id'] ?? ''));
                                if ($matriculeTl === '') {
                                    $GLOBALS['timeline_error'] = 'Matricule étudiant requis.';
                                    $GLOBALS['timeline_data'] = ['matricule' => '', 'evenements' => []];
                                } else {
                                    $GLOBALS['timeline_data'] = (new ArchiveEtudiantController($container->getPDO()))->parcours($matriculeTl);
                                }
                            } catch (Exception $e) {
                                error_log('Erreur timeline hub: ' . $e->getMessage());
                                $GLOBALS['timeline_error'] = 'Erreur de chargement.';
                                $GLOBALS['timeline_data'] = ['matricule' => '', 'evenements' => []];
                            }
                            break;
                        case 'fiche_etudiant_complete':
                            require_once __DIR__ . '/../app/controllers/FicheEtudiantController.php';
                            $data = (new FicheEtudiantController())->index();
                            break;
                        case 'etudiants_sans_compte':
                            require_once __DIR__ . '/../app/models/Utilisateur.php';
                            $utilisateurModel = $container->get('Utilisateur');
                            $etudiantsSansCompteList = $utilisateurModel->getEtudiantsNonUtilisateurs();
                            break;
                        case 'etudiants_sans_rapport':
                        case 'etudiants_non_inscrits':
                        case 'echeancier_etudiant':
                        case 'visualisation_fiche_inscription':
                            break;
                    }
                    $contentFile = $partialsBasePath . 'suivi_scolarite_content.php';
                    $currentPageLabel = 'Suivi & Scolarité';
                    break 2;

                case 'enseignant_gestion':
                    $hubTab = (string) ($_GET['tab'] ?? 'repertoire_enseignant');
                    switch ($hubTab) {
                        case 'repertoire_enseignant':
                            $repService = $container->get('RepertoireEnseignantService');
                            $repService->index();
                            break;
                        case 'fiche_enseignante':
                            require_once __DIR__ . '/../app/controllers/FicheEnseignantController.php';
                            $ensCtrl = new FicheEnseignantController();
                            if ((string) ($_GET['view'] ?? 'liste') === 'fiche' && isset($_GET['id']) && $_GET['id'] !== '') {
                                $ensCtrl->fiche((string) $_GET['id']);
                            } else {
                                $ensCtrl->index();
                            }
                            break;
                        case 'annuaire_enseignants':
                            require_once __DIR__ . '/../ressources/routes/annuaireEnseignantsRoutes.php';
                            break;
                        case 'planning_jurys_enseignant':
                        case 'stats_encadrement_enseignant':
                        case 'portfolio_enseignant':
                            break;
                    }
                    $contentFile = $partialsBasePath . 'enseignant_gestion_content.php';
                    $currentPageLabel = 'Gestion des Enseignants';
                    break 2;

                case 'outils_direction':
                    $hubTab = (string) ($_GET['tab'] ?? 'documents');
                    switch ($hubTab) {
                        case 'documents':
                            require_once __DIR__ . '/../app/controllers/DocumentsController.php';
                            $data = (new DocumentsController())->index();
                            break;
                        case 'dashboard_direction':
                            require_once __DIR__ . '/../app/controllers/DashboardDirectionController.php';
                            (new DashboardDirectionController())->index();
                            break;
                        case 'fiche_personnel_admin':
                            require_once __DIR__ . '/../app/controllers/FichePersAdminController.php';
                            $fpData = (new FichePersAdminController())->index();
                            $identite = $fpData['identite'] ?? null;
                            $compte = $fpData['compte'] ?? null;
                            $candidatures = $fpData['candidatures'] ?? [];
                            $historique = $fpData['historique'] ?? [];
                            $stats = $fpData['stats'] ?? [];
                            break;
                        case 'historique_modifications':
                        case 'export_masse_documents':
                        case 'dashboard_securite':
                        case 'comparaison_versions_document':
                            break;
                    }
                    $contentFile = $partialsBasePath . 'outils_direction_content.php';
                    $currentPageLabel = 'Outils & Direction';
                    break 2;

                case 'documents':
                    require_once __DIR__ . '/../app/controllers/DocumentsController.php';
                    $data = (new DocumentsController())->index();
                    $contentFile = $partialsBasePath . 'documents_content.php';
                    $currentPageLabel = 'Documents';
                    break 2;

                case 'recherche_globale':
                    $contentFile = $partialsBasePath . 'recherche_globale_content.php';
                    $currentPageLabel = 'Recherche globale';
                    break 2;
            }

            // Mapping manuel des actions vers les méthodes du contrôleur
            // Cela remplace le routeur s'il fait défaut
            $actionsPédagogiques = [
                'annees_academiques' => 'gestionAnnees',
                'app_settings' => 'gestionReferentielSimple',
                'genre' => 'gestionReferentielSimple',
                'decisions_jury' => 'gestionReferentielSimple',
                'etablissement_origine' => 'gestionReferentielSimple',
                'session' => 'gestionReferentielSimple',
                'mode_paiement' => 'gestionReferentielSimple',
                'frais_inscription' => 'gestionFraisInscription',
                'statut_reclamation' => 'gestionReferentielSimple',
                'domaine' => 'gestionReferentielSimple',
                'mentions' => 'gestionReferentielSimple',
                'filieres' => 'gestionReferentielSimple',
                'grades' => 'gestionGrade',
                'fonctions' => 'gestionReferentielSimple',
                'fonction_utilisateur' => 'gestionFonctionUtilisateur',
                'specialites' => 'gestionReferentielSimple',
                'niveaux_etude' => 'gestionNiveauEtude',
                'ue' => 'gestionUe',
                'ecue' => 'gestionEcue',
                'statut_jury' => 'gestionStatutJury',
                'qualite_jury' => 'gestionReferentielSimple',
                'niveaux_approbation' => 'gestionNiveauApprobation',
                'semestres' => 'gestionSemestre',
                'niveaux_acces' => 'gestionNiveauAccesDonnees',
                'bareme_critere' => 'gestionBaremeCritere',
                'maitre_stage' => 'gestionReferentielSimple',
                'type_enseignant' => 'gestionReferentielSimple',
                'entreprises' => 'gestionEntreprise',
                'actions' => 'gestionReferentielSimple',
                'messages' => 'gestionReferentielSimple',
                'programmation_sessions_soutenance' => 'gestionProgrammationSessionsSoutenance',
                'schema_tables' => 'gestionSchemaTables',
                'gestion_attribution' => 'gestionAttribution',
                'gestion_menus' => 'gestionMenus',
                'purge_cycle_etudiant' => 'gestionPurgeCycle'
            ];

            if (array_key_exists($currentAction, $actionsPédagogiques)) {
                $methode = $actionsPédagogiques[$currentAction];
                $paramController->$methode();
            }

            // Le fichier de vue reste dans le dossier parametres_generaux
            $contentFile = $partialsBasePath . 'parametres_generaux' . DIRECTORY_SEPARATOR . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        } else {
            // Si pas d'action, on affiche le Hub correspondant
            if ($currentMenuSlug === 'parametres_generaux') {
                $contentFile = $partialsBasePath . 'parametres_generaux_content.php';
                $currentPageLabel = 'Paramètres Généraux';
            } else {
                $contentFile = $partialsBasePath . 'parametres_specifiques_content.php';
                $currentPageLabel = 'Paramètres Spécifiques';
            }
        }
        break;
    case 'gestion_reclamations':
        $allowedActions = ['soumettre_reclamation', 'suivi_historique_reclamation'];
        $ajaxActions = ['get_reclamation_details'];
        if (isset($_GET['action'])) {
            if (in_array($_GET['action'], $ajaxActions)) {
                exit;
            } elseif (in_array($_GET['action'], $allowedActions)) {
                $currentAction = $_GET['action'];
                $contentFile = $partialsBasePath . 'gestion_reclamations/' . $currentAction . '.php';
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
            } else {
                $contentFile = $partialsBasePath . 'gestion_reclamations_content.php';
                $currentPageLabel = 'Gestion des réclamations';
            }
        } else {
            $contentFile = $partialsBasePath . 'gestion_reclamations_content.php';
            $currentPageLabel = 'Gestion des réclamations';
        }
        break;
    case 'gestion_rapports':
        include __DIR__ . '/../ressources/routes/gestionRapportsRoutes.php';
        $allowedActions = ['creer_rapport', 'telecharger_rapport', 'admin_telecharger_rapport'];
        $ajaxActions = [];
        if (isset($_GET['action'])) {
            if (in_array($_GET['action'], $ajaxActions)) {
                exit;
            } elseif (in_array($_GET['action'], $allowedActions)) {
                $currentAction = $_GET['action'];
                $contentFile = $partialsBasePath . 'gestion_rapports/' . $currentAction . '.php';
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
            } else {
                $contentFile = $partialsBasePath . 'gestion_rapports_content.php';
                $currentPageLabel = 'Gestion des rapports';
            }
        } else {
            $contentFile = $partialsBasePath . 'gestion_rapports_content.php';
            $currentPageLabel = 'Gestion des rapports';
        }
        break;
    case 'telecharger_rapport':
        if (!isset($_GET['action']) || $_GET['action'] === '') {
            $_GET['action'] = 'admin_telecharger_rapport';
        }
        include __DIR__ . '/../ressources/routes/gestionRapportsRoutes.php';
        $allowedActions = [
            'admin_telecharger_rapport',
            'download_modele',
            'export_rapports_csv',
            'download_fichier_rapport',
            'get_etudiants_sans_rapport',
        ];
        if (isset($_GET['action']) && in_array($_GET['action'], $allowedActions)) {
            $currentAction = $_GET['action'];
            if ($currentAction === 'admin_telecharger_rapport') {
                $contentFile = $partialsBasePath . 'gestion_rapports/admin_telecharger_rapport.php';
                $currentPageLabel = 'Import rapports étudiants';
            } else {
                $contentFile = $partialsBasePath . 'gestion_rapports/admin_telecharger_rapport.php';
                $currentPageLabel = 'Import rapports étudiants';
            }
        } else {
            $contentFile = $partialsBasePath . 'gestion_rapports/admin_telecharger_rapport.php';
            $currentPageLabel = 'Import rapports étudiants';
        }
        break;
    case 'candidature_soutenance':
        include __DIR__ . '/../ressources/routes/candidatureSoutenanceRoutes.php';
        $allowedActions = ['compte_rendu_etudiant'];
        if (isset($_GET['action']) && in_array($_GET['action'], $allowedActions)) {
            $currentAction = $_GET['action'];
            $contentFile = $partialsBasePath . 'candidature_soutenance/' . $currentAction . '.php';
            $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
        } else {
            $contentFile = $partialsBasePath . 'candidature_soutenance_content.php';
            $currentPageLabel = 'Candidater pour la soutenance';
        }
        break;
    case 'cycle_etudiant':
        include __DIR__ . '/../ressources/routes/cycleEtudiantRoutes.php';
        if (isset($_GET['action']) && $_GET['action'] === 'show' && !empty($_GET['id'])) {
            $contentFile = $partialsBasePath . 'cycle_etudiant/cycle_etudiant_wizard.php';
            $currentPageLabel = 'Parcours etudiant';
        } else {
            $contentFile = $partialsBasePath . 'cycle_etudiant/cycle_etudiant_content.php';
            $currentPageLabel = 'Parcours etudiant complet';
        }
        break;
    case 'gestion_etudiants':
        include __DIR__ . '/../ressources/routes/gestionEtudiantRoutes.php';
        if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'imprimer_recu' && isset($_GET['id_inscription'])) {
            $id_inscription = (string) $_GET['id_inscription'];
            // Anti-IDOR: si étudiant, ne permettre que ses propres documents
            if (isset($_SESSION['id_GU']) && (int) $_SESSION['id_GU'] === 13) {
                require_once __DIR__ . '/../app/models/Scolarite.php';
                $scolarite = $container->get('Scolarite');
                $inscription = $scolarite->getInscriptionById($id_inscription);
                if (!$inscription || (string) ($inscription['num_carte_etud'] ?? '') !== (string) ($_SESSION['num_etu'] ?? '')) {
                    header('Location: layout.php?page=access_denied');
                    exit;
                }
            }
            require_once __DIR__ . '/../app/utils/RecuDataUtils.php';
            $dbWrapper = new \App\Support\Database();
            $recuDataUtils = new \App\Utils\RecuDataUtils($dbWrapper);
            $pdfGen = new \App\Services\Document\PdfGeneratorService(
                __DIR__ . '/../storage/documents',
                __DIR__ . '/../public/assets/img/logo.png'
            );
            $recuService = new \App\Services\Document\RecuGeneratorService($pdfGen, $recuDataUtils, $dbWrapper);
            $parts = explode('-', $id_inscription);
            if (count($parts) >= 3) {
                $result = $recuService->generate($id_inscription, (int) ($_SESSION['id_utilisateur'] ?? 0));
                if ($result['success'] && !empty($result['path']) && file_exists($result['path'])) {
                    header('Location: ?page=docviewer&type=recu&id=' . urlencode((string) $id_inscription) . '&action=preview');
                    exit;
                }
            }
            // Fallback : erreur silencieuse, on continue vers la page normale
            error_log('Erreur génération reçu inscription #' . $id_inscription . ': versement non trouvé ou échec PDF');
        }
        $allowedActions = ['ajouter_des_etudiants', 'inscrire_des_etudiants', 'importer_etudiants'];
        $actionLabels = [
            'ajouter_des_etudiants' => 'Mise à jour étudiant',
            'inscrire_des_etudiants' => 'Inscrire des étudiants',
            'importer_etudiants' => 'Import d\'étudiants',
        ];
        if (isset($_GET['action']) && in_array($_GET['action'], $allowedActions)) {
            $currentAction = $_GET['action'];
            $contentFile = $partialsBasePath . 'gestion_etudiants/' . $currentAction . '.php';
            $currentPageLabel = isset($actionLabels[$currentAction]) ? $actionLabels[$currentAction] : ucfirst(str_replace('_', ' ', $currentAction));
        } else {
            $contentFile = $partialsBasePath . 'gestion_etudiants_content.php';
            $currentPageLabel = 'Gestion des étudiants';
        }
        break;
    case 'liste_etudiants_resp':
    case 'liste_etudiants_ens':
        include __DIR__ . '/../ressources/routes/listeEtudiantsRoutes.php';
        $contentFile = $partialsBasePath . 'liste_etudiants_content.php';
        $currentPageLabel = 'Liste des Étudiants';
        break;
    case 'rapport_a_valider':
        $contentFile = $partialsBasePath . 'rapport_a_valider_content.php';
        $currentPageLabel = 'Approuver Rapports';
        break;
    case 'reception_rapport_com':
        $contentFile = $partialsBasePath . 'rapport_a_valider_content.php';
        $currentPageLabel = 'Réception des rapports';
        break;
    case 'dashboard_commission':
        $contentFile = $partialsBasePath . 'dashboard_commission_content.php';
        $currentPageLabel = 'Tableau de bord commission';
        break;
    case 'gestion_candidatures':
        if (($_GET['action'] ?? '') === 'evaluations_m2_s1') {
            $contentFile = $partialsBasePath . 'gestion_candidatures_evaluations_content.php';
            $currentPageLabel = 'Évaluations M2/S1 des candidatures';
        } else {
            $contentFile = $partialsBasePath . 'gestion_candidatures_soutenance_content.php';
            $currentPageLabel = 'Gestion des Candidatures';
        }
        break;
    case 'verification_candidatures':
        $contentFile = $partialsBasePath . 'verification_candidatures_soutenance_content.php';
        $currentPageLabel = 'Vérification des Candidatures';
        break;
    case 'evaluation_dossiers':
        $contentFile = $partialsBasePath . 'evaluations_dossiers_soutenance_content.php';
        $currentPageLabel = 'Évaluation des Dossiers';
        break;
    case 'programmation_soutenance':
    case 'programation_soutenance':
        $contentFile = $partialsBasePath . 'Programation_soutenance_content.php';
        $currentPageLabel = 'Programmation Soutenance';
        break;
    case 'planification_soutenance':
        $contentFile = $partialsBasePath . 'plannificaiton_soutenance_content.php';
        $currentPageLabel = 'Planification Soutenance';
        break;
    case 'gestion_notes':
        $contentFile = $partialsBasePath . 'gestion_notes_evaluations_content.php';
        $currentPageLabel = 'Gestion des Notes';
        break;
    case 'gestion_scolarite':
        if (isset($_GET['action']) && $_GET['action'] === 'imprimer_recu' && isset($_GET['id'])) {
            $id_versement = (string) $_GET['id'];
            // Anti-IDOR: si étudiant, ne permettre que ses propres versements
            if (isset($_SESSION['id_GU']) && (int) $_SESSION['id_GU'] === 13) {
                require_once __DIR__ . '/../app/models/Scolarite.php';
                $scolarite = $container->get('Scolarite');
                $versement = $scolarite->getVersementById($id_versement);
                if (!$versement || (string) ($versement['num_carte_etud'] ?? '') !== (string) ($_SESSION['num_etu'] ?? '')) {
                    header('Location: layout.php?page=access_denied');
                    exit;
                }
            }
            require_once __DIR__ . '/../app/utils/RecuDataUtils.php';
            $dbWrapper = new \App\Support\Database();
            $recuDataUtils = new \App\Utils\RecuDataUtils($dbWrapper);
            $pdfGen = new \App\Services\Document\PdfGeneratorService(
                __DIR__ . '/../storage/documents',
                __DIR__ . '/../public/assets/img/logo.png'
            );
            $recuService = new \App\Services\Document\RecuGeneratorService($pdfGen, $recuDataUtils, $dbWrapper);
            $result = $recuService->generate($id_versement, (int) ($_SESSION['id_utilisateur'] ?? 0));
            if ($result['success'] && !empty($result['path']) && file_exists($result['path'])) {
                $docReference = !empty($result['reference']) ? (string) $result['reference'] : (string) $id_versement;
                header('Location: ?page=docviewer&type=recu&id=' . urlencode($docReference) . '&action=preview');
                exit;
            }
            // Fallback : erreur silencieuse, on continue vers la page normale
            error_log('Erreur génération reçu versement #' . $id_versement . ': ' . ($result['error'] ?? 'inconnue'));
        }
        $contentFile = $partialsBasePath . 'gestion_scolarite_content.php';
        $currentPageLabel = 'Gestion de la scolarité';
        break;
    case 'gestion_notes_evaluations':
        if (isset($_GET['action']) && $_GET['action'] === 'imprimer_releve' && isset($_GET['student']) && isset($_GET['niveau'])) {
            $id_etudiant = $_GET['student'];
            $niveau = $_GET['niveau'];
            // Anti-IDOR: étudiant ne peut imprimer que son relevé
            if (isset($_SESSION['id_GU']) && (int) $_SESSION['id_GU'] === 13) {
                if ($id_etudiant !== ($_SESSION['num_etu'] ?? '')) {
                    header('Location: layout.php?page=access_denied');
                    exit;
                }
            }
            // NotesResultatsController et NotesResultatsService : autoloadés
            $notesService = $container->get('NotesService');
            // Positionner num_etu pour le service
            $originalNumEtu = $_SESSION['num_etu'] ?? null;
            $_SESSION['num_etu'] = $id_etudiant;
            $notesService->generatePdf((string) $id_etudiant);
            // generatePdf() fait exit, mais au cas où:
            $_SESSION['num_etu'] = $originalNumEtu;
        }
        $contentFile = $partialsBasePath . 'gestion_notes_evaluations_content.php';
        $currentPageLabel = 'Gestion des notes et évaluations';
        break;
    case 'gestion_dossiers_candidatures':
        $contentFile = $partialsBasePath . 'gestion_dossiers_candidatures_content.php';
        $currentPageLabel = 'Gestion des dossiers de candidatures vérifiés';
        break;
    case 'evaluations_dossiers_soutenance':
        $contentFile = $partialsBasePath . 'evaluations_dossiers_soutenance_content.php';
        $currentPageLabel = 'Évaluation des Dossiers de Soutenance';
        break;

    case 'evaluation_soutenance':
        // Inclure les routes pour l'évaluation des soutenances
        include __DIR__ . '/../ressources/routes/evaluationSoutenanceRoutes.php';
        $contentFile = $partialsBasePath . 'evaluation_soutenance_content.php';
        $currentPageLabel = 'Évaluation des Soutenances';
        break;
    case 'consultation_cr_etud':
        $contentFile = $partialsBasePath . 'consultation_cr_etud_content.php';
        $currentPageLabel = 'Mon Compte Rendu';
        break;
    case 'membres_commission':
        require_once __DIR__ . '/../app/controllers/CommissionValidationMembresController.php';
        $membresCommissionController = new CommissionValidationMembresController();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_GET['action'] ?? '') === 'sauvegarder_membres') {
            $membresCommissionController->sauvegarder();
            exit;
        }
        $membresCommissionData = $membresCommissionController->index();
        $contentFile = $partialsBasePath . 'membres_commission_content.php';
        $currentPageLabel = 'Membres actifs';
        break;
    case 'redaction_compte_rendu':
        $contentFile = $partialsBasePath . 'redaction_compte_rendu_content.php';
        $currentPageLabel = 'Rédaction de compte rendu';
        break;
    case 'processus_validation':
        $contentFile = $partialsBasePath . 'processus_validation_content.php';
        $currentPageLabel = 'Suivi de validation';
        break;
    case 'edition_bulletin':
        $contentFile = $partialsBasePath . 'edition_bulletin_content.php';
        $currentPageLabel = 'Édition des PV finaux';
        break;
    case 'archive_comptes_rendus':
        $contentFile = $partialsBasePath . 'redaction_compte_rendu/archives_compte_rendu_content.php';
        $currentPageLabel = 'Archives des comptes rendus';
        break;
    case 'hub_historique':
        $currentPageLabel = 'Historique et Archivage';
        $contentFile = $partialsBasePath . 'v2/archives/hub_historique.php';
        break;
    case 'archives_etudiants':
        $contentFile = $partialsBasePath . 'v2/archives/archives_etudiants.php';
        break;
    case 'fiche_etudiant_archive':
        $contentFile = $partialsBasePath . 'v2/archives/fiche_etudiant_archive.php';
        break;
    case 'parcours_etudiant':
        $contentFile = $partialsBasePath . 'v2/archives/parcours_etudiant.php';
        break;
    case 'archives_soutenances':
        $contentFile = $partialsBasePath . 'v2/archives/archives_soutenances.php';
        break;
    case 'fiche_soutenance':
        $contentFile = $partialsBasePath . 'v2/archives/fiche_soutenance.php';
        break;
    case 'archives_jurys':
        $contentFile = $partialsBasePath . 'v2/archives/archives_jurys.php';
        break;
    case 'archives_documents':
        if (!class_exists('ArchiveDocumentController')) {
            require_once __DIR__ . '/../app/controllers/ArchiveDocumentController.php';
        }
        $data = (new ArchiveDocumentController())->index();
        $contentFile = $partialsBasePath . 'v2/archives/archives_documents.php';
        break;
    case 'documents':
        $contentFile = $partialsBasePath . 'documents_content.php';
        break;
    case 'archives_candidatures':
        $contentFile = $partialsBasePath . 'v2/archives/archives_candidatures.php';
        break;
    case 'archives_reclamations':
        $contentFile = $partialsBasePath . 'v2/archives/archives_reclamations.php';
        break;
    case 'fiche_etudiant_complete':
        require_once __DIR__ . '/../app/controllers/FicheEtudiantController.php';
        $data = (new FicheEtudiantController())->index();
        $contentFile = $partialsBasePath . 'v2/archives/fiche_etudiant_complete.php';
        $currentPageLabel = 'Fiche Etudiante Complete';
        break;
    case 'admin_historique':
        $action = $_GET['action'] ?? 'index';
        $currentPageLabel = 'Historique et Archivage';
        if ($action === 'view_student') {
            $currentPageLabel = 'Fiche étudiant archive';
            $contentFile = $partialsBasePath . 'fiche_etudiant_archive.php';
        } elseif ($action === 'import_result') {
            $currentPageLabel = "Résultat de l'import";
            $contentFile = $partialsBasePath . 'import_result.php';
        } else {
            if (!class_exists('ArchiveHubController')) {
                require_once __DIR__ . '/../app/controllers/ArchiveHubController.php';
            }
            $archiveHubController = new ArchiveHubController();
            $data = $archiveHubController->index();
            $contentFile = $partialsBasePath . 'v2/archives/hub_historique.php';
        }
        break;
    case 'repertoire_enseignant':
        // RepertoireEnseignantService : autoloadé par CheckMaster\ SPL
        $service = new \CheckMaster\Services\RepertoireEnseignantService(\Database::getConnection());
        $service->index();
        $contentFile = $partialsBasePath . 'repertoire_enseignant_content.php';
        $currentPageLabel = 'Repertoire documents';
        break;
    case 'programmation_ens':
        $contentFile = $partialsBasePath . 'soutenance_ens_content.php';
        $currentPageLabel = 'Programmation enseignant';
        break;
    case 'maj_enseignant':
        $_GET['tab'] = 'enseignant';
        if (!class_exists('GestionRhController')) {
            require_once __DIR__ . '/../app/controllers/GestionRhController.php';
        }
        $gestionRhController = new GestionRhController();
        $gestionRhController->index();
        if ((string) ($_GET['action'] ?? '') === 'importer') {
            $contentFile = $partialsBasePath . 'gestion_rh_import.php';
            $currentPageLabel = 'Import d\'enseignants';
        } else {
            $contentFile = $partialsBasePath . 'gestion_rh_content.php';
            $currentPageLabel = 'Mise à jour enseignant';
        }
        break;
    case 'maj_personnel_admin':
        $_GET['tab'] = 'pers_admin';
        if (!class_exists('GestionRhController')) {
            require_once __DIR__ . '/../app/controllers/GestionRhController.php';
        }
        $gestionRhController = new GestionRhController();
        $gestionRhController->index();
        if ((string) ($_GET['action'] ?? '') === 'importer') {
            $contentFile = $partialsBasePath . 'gestion_rh_import.php';
            $currentPageLabel = 'Import du personnel administratif';
        } else {
            $contentFile = $partialsBasePath . 'gestion_rh_content.php';
            $currentPageLabel = 'Mise à jour personnel administratif';
        }
        break;
    case 'fiche_enseignante':
        $contentFile = $partialsBasePath . 'fiche_enseignante_content.php';
        $currentPageLabel = 'Fiche enseignante';
        break;
    case 'fiche_financiere_annee':
        $contentFile = $partialsBasePath . 'fiche_financiere_content.php';
        $currentPageLabel = 'Fiche financière année';
        break;
    case 'dashboard_direction':
        $contentFile = $partialsBasePath . 'dashboard_direction_content.php';
        $currentPageLabel = 'Dashboard Direction';
        break;
    case 'fiche_commission':
        $contentFile = $partialsBasePath . 'fiche_commission_content.php';
        $currentPageLabel = 'Fiche commission';
        break;
    case 'fiche_personnel_admin':
        $contentFile = $partialsBasePath . 'fiche_pers_admin_content.php';
        $currentPageLabel = 'Fiche personnel administratif';
        break;
    case 'historique_inscriptions':
        $contentFile = $partialsBasePath . 'historique_inscriptions_content.php';
        $currentPageLabel = 'Historique des inscriptions';
        break;
    case 'workflow_validation':
        $contentFile = $partialsBasePath . 'workflow_validation_content.php';
        $currentPageLabel = 'Workflow de validation';
        break;
    case 'visualisation_fiche_inscription':
        $contentFile = $partialsBasePath . 'visualisation_fiche_inscription_content.php';
        $currentPageLabel = 'Visualisation fiche inscription';
        break;
    case 'etudiants_sans_rapport':
        $contentFile = $partialsBasePath . 'etudiants_sans_rapport_content.php';
        $currentPageLabel = 'Étudiants sans rapport';
        break;
    case 'etudiants_non_inscrits':
        $contentFile = $partialsBasePath . 'etudiants_non_inscrits_content.php';
        $currentPageLabel = 'Étudiants non inscrits';
        break;
    case 'etudiants_sans_compte':
        require_once __DIR__ . '/../ressources/routes/etudiantsSansCompteRoutes.php';
        $contentFile = $partialsBasePath . 'etudiants_sans_compte_content.php';
        $currentPageLabel = 'Étudiants sans compte';
        break;
    case 'planning_jurys_enseignant':
        $contentFile = $partialsBasePath . 'planning_jurys_enseignant_content.php';
        $currentPageLabel = 'Planning jurys';
        break;
    case 'stats_encadrement_enseignant':
        $contentFile = $partialsBasePath . 'stats_encadrement_enseignant_content.php';
        $currentPageLabel = 'Stats encadrement';
        break;
    case 'portfolio_enseignant':
        $contentFile = $partialsBasePath . 'portfolio_enseignant_content.php';
        $currentPageLabel = 'Portfolio enseignant';
        break;
    case 'recherche_globale':
        $contentFile = $partialsBasePath . 'recherche_globale_content.php';
        $currentPageLabel = 'Recherche globale';
        break;
    case 'echeancier_etudiant':
        $contentFile = $partialsBasePath . 'echeancier_etudiant_content.php';
        $currentPageLabel = 'Échéancier étudiant';
        break;
    case 'historique_modifications':
        $contentFile = $partialsBasePath . 'historique_modifications_content.php';
        $currentPageLabel = 'Historique modifications';
        break;
    case 'mise_en_ligne_memoire':
        require_once __DIR__ . '/../app/controllers/MiseEnLigneMemoireController.php';
        $data = (new MiseEnLigneMemoireController())->handleRequest();
        $contentFile = $partialsBasePath . 'mise_en_ligne_memoire_content.php';
        $currentPageLabel = 'Mise en ligne des mémoires';
        break;
    case 'validation_memoires':
        $contentFile = $partialsBasePath . 'validation_memoires_content.php';
        $currentPageLabel = 'Validation des memoires';
        break;
    case 'export_masse_documents':
        $contentFile = $partialsBasePath . 'export_masse_documents_content.php';
        $currentPageLabel = 'Export masse documents';
        break;
    case 'dashboard_securite':
        $contentFile = $partialsBasePath . 'dashboard_securite_content.php';
        $currentPageLabel = 'Dashboard securite';
        break;
    case 'comparaison_versions_document':
        $contentFile = $partialsBasePath . 'comparaison_versions_document_content.php';
        $currentPageLabel = 'Comparaison versions document';
        break;
    case 'annuaire_enseignants':
        require_once __DIR__ . '/../ressources/routes/annuaireEnseignantsRoutes.php';
        $contentFile = $partialsBasePath . 'annuaire_enseignants_content.php';
        $currentPageLabel = 'Annuaire enseignants';
        break;
    case 'timeline_parcours_etudiant':
        require_once __DIR__ . '/../ressources/routes/timelineParcoursRoutes.php';
        $contentFile = $partialsBasePath . 'v2/archives/timeline_interactive.php';
        $currentPageLabel = 'Timeline parcours etudiant';
        break;

    // ════════════════════════════════════════════════════════
    // HUBS DE NAVIGATION (remaniement menus 2026-05-19)
    // Les routeurs sont déjà include'd plus haut mais leurs
    // conditions vérifient $_GET['page'] === 'slugoriginel', ce
    // qui échoue depuis un hub. On initialise donc directement
    // les contrôleurs ici pour chaque onglet.
    // ════════════════════════════════════════════════════════

    case 'suivi_scolarite':
        $hubTab = (string) ($_GET['tab'] ?? 'fiche_financiere_annee');
        // Les routeurs sont include'd plus haut mais leurs
        // conditions vérifient $_GET['page'] !== hub → on initialise ici
        switch ($hubTab) {
            case 'fiche_financiere_annee':
                require_once __DIR__ . '/../app/controllers/FicheFinanciereController.php';
                $ficheFinanciereController = new FicheFinanciereController();
                if ((string) ($_GET['action'] ?? '') === 'detail_etudiant') {
                    $ficheFinanciereController->detailEtudiant();
                }
                $ficheFinanciereController->index();
                break;
            case 'historique_inscriptions':
                require_once __DIR__ . '/../app/controllers/HistoriqueInscriptionsController.php';
                $histData = (new HistoriqueInscriptionsController())->index();
                $etudiant = $histData['etudiant'] ?? null;
                if ($etudiant !== null && is_object($etudiant))
                    $etudiant = (array) $etudiant;
                $parcours = $histData['parcours'] ?? [];
                $notes = $histData['notes'] ?? [];
                break;
            case 'timeline_parcours_etudiant':
                try {
                    require_once __DIR__ . '/../app/controllers/ArchiveEtudiantController.php';
                    $matriculeTl = trim((string) ($_GET['num_etu'] ?? $_GET['matricule'] ?? $_GET['id'] ?? ''));
                    if ($matriculeTl === '') {
                        $GLOBALS['timeline_error'] = 'Matricule étudiant requis.';
                        $GLOBALS['timeline_data'] = ['matricule' => '', 'evenements' => []];
                    } else {
                        $GLOBALS['timeline_data'] = (new ArchiveEtudiantController($container->getPDO()))->parcours($matriculeTl);
                    }
                } catch (Exception $e) {
                    error_log('Erreur timeline hub: ' . $e->getMessage());
                    $GLOBALS['timeline_error'] = 'Erreur de chargement.';
                    $GLOBALS['timeline_data'] = ['matricule' => '', 'evenements' => []];
                }
                break;
            case 'fiche_etudiant_complete':
                require_once __DIR__ . '/../app/controllers/FicheEtudiantController.php';
                $data = (new FicheEtudiantController())->index();
                break;
            case 'etudiants_sans_compte':
                require_once __DIR__ . '/../app/models/Utilisateur.php';
                $utilisateurModel = $container->get('Utilisateur');
                $etudiantsSansCompteList = $utilisateurModel->getEtudiantsNonUtilisateurs();
                break;
            // Les tabs suivants sont auto-contenus (service/SQL dans la vue)
            case 'etudiants_sans_rapport':
            case 'etudiants_non_inscrits':
            case 'echeancier_etudiant':
            case 'visualisation_fiche_inscription':
                break;
        }
        $contentFile = $partialsBasePath . 'suivi_scolarite_content.php';
        $currentPageLabel = 'Suivi & Scolarité';
        break;

    case 'commissions_archives':
        $hubTab = (string) ($_GET['tab'] ?? 'archives_documents');
        switch ($hubTab) {
            case 'archives_documents':
                if (!class_exists('ArchiveDocumentController')) {
                    require_once __DIR__ . '/../app/controllers/ArchiveDocumentController.php';
                }
                $data = (new ArchiveDocumentController())->index();
                break;
            case 'archives_etudiants':
                if (!class_exists('ArchiveEtudiantController')) {
                    require_once __DIR__ . '/../app/controllers/ArchiveEtudiantController.php';
                }
                $archiveEtudiantController = new ArchiveEtudiantController();
                if ((string) ($_GET['action'] ?? '') === 'exportCsv') {
                    $archiveEtudiantController->exportCsv();
                }
                $data = $archiveEtudiantController->index();
                break;
            case 'archive_comptes_rendus':
                require_once __DIR__ . '/../app/controllers/ArchivesCompteRenduController.php';
                (new ArchivesCompteRenduController())->index();
                break;
            case 'fiche_commission':
                require_once __DIR__ . '/../app/controllers/FicheCommissionController.php';
                $fcData = (new FicheCommissionController())->index();
                $membres = $fcData['membres'] ?? [];
                $rapportsEvalues = $fcData['rapports_evalues'] ?? [];
                $rapportsAttente = $fcData['rapports_attente'] ?? [];
                $statsVote = $fcData['stats_vote'] ?? [];
                $decisions = $fcData['decisions'] ?? [];
                $planning = $fcData['planning'] ?? [];
                break;
            case 'workflow_validation':
                require_once __DIR__ . '/../app/controllers/ProcessusValidationController.php';
                $workflowData = (new ProcessusValidationController())->workflowVisuel();
                $workflow = $workflowData['workflow'] ?? null;
                $statistiques = $workflowData['statistiques'] ?? [];
                if (!$workflow) {
                    $rapports = $workflowData['rapports'] ?? [];
                }
                break;
            case 'archives_memoires':
                require_once __DIR__ . '/../app/controllers/ArchiveDocumentController.php';
                $memoiresData = (new ArchiveDocumentController())->getMemoires();
                $memoires = $memoiresData['memoires'] ?? [];
                $memoiresCount = $memoiresData['total'] ?? count($memoires);
                break;
        }
        $contentFile = $partialsBasePath . 'commissions_archives_content.php';
        $currentPageLabel = 'Commissions & Archives';
        break;

    case 'enseignant_gestion':
        $hubTab = (string) ($_GET['tab'] ?? 'repertoire_enseignant');
        switch ($hubTab) {
            case 'repertoire_enseignant':
                $repService = $container->get('RepertoireEnseignantService');
                $repService->index();
                break;
            case 'fiche_enseignante':
                require_once __DIR__ . '/../app/controllers/FicheEnseignantController.php';
                $ensCtrl = new FicheEnseignantController();
                if ((string) ($_GET['view'] ?? 'liste') === 'fiche' && isset($_GET['id']) && $_GET['id'] !== '') {
                    $ensCtrl->fiche((string) $_GET['id']);
                } else {
                    $ensCtrl->index();
                }
                break;
            case 'annuaire_enseignants':
                require_once __DIR__ . '/../ressources/routes/annuaireEnseignantsRoutes.php';
                break;
            // Les tabs suivants (planning, stats, portfolio) sont auto-contenus dans leurs vues
            case 'planning_jurys_enseignant':
            case 'stats_encadrement_enseignant':
            case 'portfolio_enseignant':
                break;
        }
        $contentFile = $partialsBasePath . 'enseignant_gestion_content.php';
        $currentPageLabel = 'Gestion des Enseignants';
        break;

    case 'outils_direction':
        $hubTab = (string) ($_GET['tab'] ?? 'documents');
        switch ($hubTab) {
            case 'documents':
                require_once __DIR__ . '/../app/controllers/DocumentsController.php';
                $data = (new DocumentsController())->index();
                break;
            case 'dashboard_direction':
                require_once __DIR__ . '/../app/controllers/DashboardDirectionController.php';
                (new DashboardDirectionController())->index();
                break;
            case 'fiche_personnel_admin':
                require_once __DIR__ . '/../app/controllers/FichePersAdminController.php';
                $fpData = (new FichePersAdminController())->index();
                $identite = $fpData['identite'] ?? null;
                $compte = $fpData['compte'] ?? null;
                $candidatures = $fpData['candidatures'] ?? [];
                $historique = $fpData['historique'] ?? [];
                $stats = $fpData['stats'] ?? [];
                break;
            // Les tabs suivants sont auto-contenus (service/sql dans la vue)
            case 'historique_modifications':
            case 'export_masse_documents':
            case 'dashboard_securite':
            case 'comparaison_versions_document':
                break;
        }
        $contentFile = $partialsBasePath . 'outils_direction_content.php';
        $currentPageLabel = 'Outils & Direction';
        break;

    default:
        $groupeUtilisateur = $_SESSION['lib_GU'];
        if ($groupeUtilisateur) {
            $contentFile = $partialsBasePath . $currentMenuSlug . '_content.php';
        }
        if (empty($contentFile) || !file_exists($contentFile)) {
            $contentFile = '';
        }
        break;
}

$canonicalActionLabels = [
    'gestion_etudiants:ajouter_des_etudiants' => 'Mise à jour étudiant',
    'parametres_generaux:annees_academiques' => 'Gestion des années académiques',
    'parametres_generaux:frais_inscription' => "Gestion des frais d'inscription",
    'parametres_generaux:suivi_scolarite' => 'Suivi & Scolarité',
    'parametres_generaux:enseignant_gestion' => 'Gestion des Enseignants',
    'parametres_generaux:outils_direction' => 'Outils & Direction',
    'parametres_generaux:documents' => 'Documents',
    'parametres_generaux:recherche_globale' => 'Recherche globale',
];
$canonicalPageLabels = [
    'admin_historique' => 'Historique et archivage',
    'consultation_cr_etud' => 'Mon compte rendu',
    'candidature_soutenance' => 'Ma candidature à la soutenance',
    'dashboard' => 'Tableau de bord — Administration',
    'dashboard_commission' => 'Tableau de bord — Commission',
    'dashboard_enseignant' => 'Espace enseignant — Participations jurys',
    'dashboard_scolarite' => 'Tableau de bord scolarité',
    'edition_bulletin' => 'Édition des PV finaux',
    'evaluation_dossiers' => 'Évaluation des dossiers',
    'gestion_dossiers_candidatures' => 'Gestion des dossiers de candidatures',
    'gestion_notes_evaluations' => 'Gestion des notes et évaluations',
    'gestion_reclamations' => 'Mes réclamations',
    'gestion_reclamations_scolarite' => 'Gestion des réclamations',
    'gestion_scolarite' => 'Inscriptions / Paiements',
    'gestion_utilisateurs' => 'Gestion des utilisateurs',
    'maj_enseignant' => 'Mise à jour enseignant',
    'maj_personnel_admin' => 'Mise à jour personnel administratif',
    'mise_en_ligne_memoire' => 'Mise en ligne des mémoires',
    'validation_memoires' => 'Validation des memoires',
    'parametres_generaux' => 'Paramètres généraux',
    'parametres_specifiques' => 'Paramètres spécifiques',
    'piste_audit' => "Piste d'audit",
    'processus_validation' => 'Suivi de validation des rapports',
    'profil' => 'Mon profil',
    'programmation_ens' => 'Mes soutenances — Programme',
    'programmation_soutenance' => 'Programmation des soutenances',
    'redaction_compte_rendu' => 'Rédaction du compte rendu',
    'reception_rapport_com' => 'Réception des rapports',
    'repertoire_enseignant' => 'Répertoire des documents',
    'sauvegarde_restauration' => 'Sauvegardes et restauration',
    'tableau_bord_enseignant' => 'Mon tableau de bord — Enseignant',
    'fiche_etudiant_complete' => 'Fiche Étudiante Complete',
    'suivi_scolarite' => 'Suivi & Scolarité',
    'commissions_archives' => 'Commissions & Archives',
    'enseignant_gestion' => 'Gestion des Enseignants',
    'outils_direction' => 'Outils & Direction',
];
$labelKey = $currentMenuSlug . ($currentAction !== null ? ':' . $currentAction : '');
if (isset($canonicalActionLabels[$labelKey])) {
    $currentPageLabel = $canonicalActionLabels[$labelKey];
} elseif (isset($canonicalPageLabels[$currentMenuSlug])) {
    $currentPageLabel = $canonicalPageLabels[$currentMenuSlug];
}
$GLOBALS['currentPageLabel'] = $currentPageLabel;

if ($auditService === null) {
    try {
        $auditService = $container->get('AuditService');
    } catch (\Throwable $e) {
        error_log('Layout: initialisation AuditService impossible: ' . $e->getMessage());
    }
}

if (
    $auditService instanceof \CheckMaster\Services\AuditService
    && isset($_SESSION['id_utilisateur'])
    && !defined('CM_REQUEST_AUDIT_LOGGED')
) {
    define('CM_REQUEST_AUDIT_LOGGED', true);
    $auditService->logRequestActivity(
        (int) $_SESSION['id_utilisateur'],
        $_GET,
        $_POST,
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        !empty($GLOBALS['error'])
    );
}

$isEtudiantEnvironment = strtolower(trim((string) ($_SESSION['type_utilisateur'] ?? ''))) === 'etudiant';
$isSoutenanceContext = strpos((string) $currentMenuSlug, 'soutenance') !== false
    || in_array((string) $currentMenuSlug, ['programmation_ens'], true);
$hideAcademicYearOnNavbar = in_array((string) $currentMenuSlug, ['parametres_generaux', 'parametres_specifiques'], true)
    && empty($_GET['action']);
$lockAcademicYearOnNavbar = !$hideAcademicYearOnNavbar
    && ($isEtudiantEnvironment || ($isSoutenanceContext && !in_array((string) $currentMenuSlug, ['programmation_soutenance', 'programation_soutenance'], true)));
$navbarAcademicYearLabel = $currentGlobalYearIsAll
    ? AcademicYear::getAllLabel()
    : ($currentGlobalYear !== '' ? $currentGlobalYear : ($writableGlobalYear !== '' ? $writableGlobalYear : $activeGlobalYear));

// Debug : log de la page demandée
if (isset($_GET['page'])) {
    error_log('PAGE DEMANDEE : ' . $_GET['page']);
}

// 1. Paramètres GÉNÉRAUX
$cardPGeneraux = [
    [
        'title' => 'Années Académiques',
        'description' => 'Gestion des périodes.',
        'link' => '?page=parametres_generaux&action=annees_academiques',
        'icon' => 'fa-calendar-alt'
    ],
    [
        'title' => 'App Settings',
        'description' => 'Configuration applicative.',
        'link' => '?page=parametres_generaux&action=app_settings',
        'icon' => 'fa-sliders'
    ],
    [
        'title' => 'Niveaux d\'Étude',
        'description' => 'L1, L2, M1, M2.',
        'link' => '?page=parametres_generaux&action=niveaux_etude',
        'icon' => 'fa-layer-group'
    ],
    [
        'title' => 'Frais d\'Inscription',
        'description' => 'Montants par année et niveau.',
        'link' => '?page=parametres_generaux&action=frais_inscription',
        'icon' => 'fa-money-bill-wave'
    ],
    [
        'title' => 'Semestres',
        'description' => 'S1, S2...',
        'link' => '?page=parametres_generaux&action=semestres',
        'icon' => 'fa-calendar-check'
    ],
    [
        'title' => 'Genre',
        'description' => 'Référentiel des genres.',
        'link' => '?page=parametres_generaux&action=genre',
        'icon' => 'fa-venus-mars'
    ],
    [
        'title' => 'Décisions Jury',
        'description' => 'Décisions de validation.',
        'link' => '?page=parametres_generaux&action=decisions_jury',
        'icon' => 'fa-gavel'
    ],
    [
        'title' => 'Établissement Origine',
        'description' => 'Écoles et universités.',
        'link' => '?page=parametres_generaux&action=etablissement_origine',
        'icon' => 'fa-school'
    ],
    [
        'title' => 'Session',
        'description' => 'Sessions académiques.',
        'link' => '?page=parametres_generaux&action=session',
        'icon' => 'fa-clock'
    ],
    [
        'title' => 'Mode Paiement',
        'description' => 'Moyens de règlement.',
        'link' => '?page=parametres_generaux&action=mode_paiement',
        'icon' => 'fa-credit-card'
    ],
    [
        'title' => 'Statut Réclamation',
        'description' => 'États des réclamations.',
        'link' => '?page=parametres_generaux&action=statut_reclamation',
        'icon' => 'fa-triangle-exclamation'
    ],
    [
        'title' => 'Domaine',
        'description' => 'Domaines de soutenance.',
        'link' => '?page=parametres_generaux&action=domaine',
        'icon' => 'fa-diagram-project'
    ],
    [
        'title' => 'Mentions',
        'description' => 'Mentions académiques.',
        'link' => '?page=parametres_generaux&action=mentions',
        'icon' => 'fa-award'
    ],
    [
        'title' => 'Filières',
        'description' => 'Référentiel des filières.',
        'link' => '?page=parametres_generaux&action=filieres',
        'icon' => 'fa-graduation-cap'
    ],
    [
        'title' => 'Grades',
        'description' => 'Grades enseignants.',
        'link' => '?page=parametres_generaux&action=grades',
        'icon' => 'fa-medal'
    ],
    [
        'title' => 'Fonction',
        'description' => 'Fonctions du personnel.',
        'link' => '?page=parametres_generaux&action=fonctions',
        'icon' => 'fa-briefcase'
    ],
    [
        'title' => 'Fonctions Utilisateurs',
        'description' => 'Groupes et types.',
        'link' => '?page=parametres_generaux&action=fonction_utilisateur&tab=groupes',
        'icon' => 'fa-users-cog'
    ],
    [
        'title' => 'Niveaux d\'Accès',
        'description' => 'Lecture/Écriture.',
        'link' => '?page=parametres_generaux&action=niveaux_acces',
        'icon' => 'fa-lock'
    ],
    [
        'title' => 'Niveaux d\'Approbation',
        'description' => 'Workflow de validation.',
        'link' => '?page=parametres_generaux&action=niveaux_approbation',
        'icon' => 'fa-sitemap'
    ],
    [
        'title' => 'Qualité Jury',
        'description' => 'Rôles et qualité du jury.',
        'link' => '?page=parametres_generaux&action=qualite_jury',
        'icon' => 'fa-user-shield'
    ],
    [
        'title' => 'Cycle Étudiant Ciblé',
        'description' => 'Purger ou reconstituer le cycle complet d\'un étudiant.',
        'link' => '?page=parametres_generaux&action=purge_cycle_etudiant',
        'icon' => 'fa-user-graduate',
        'permission_slug' => 'purge_cycle_etudiant'
    ],
    [
        'title' => 'Suivi & Scolarité',
        'description' => 'Finances, inscriptions, échéanciers et fiches étudiantes.',
        'link' => '?page=parametres_generaux&action=suivi_scolarite',
        'icon' => 'fa-user-graduate'
    ],
    [
        'title' => 'Gestion des Enseignants',
        'description' => 'Répertoire, fiches, planning jurys et portfolio.',
        'link' => '?page=parametres_generaux&action=enseignant_gestion',
        'icon' => 'fa-chalkboard-teacher'
    ],
    [
        'title' => 'Outils & Direction',
        'description' => 'Documents, sécurité, export et KPI direction.',
        'link' => '?page=parametres_generaux&action=outils_direction',
        'icon' => 'fa-toolbox'
    ],
    [
        'title' => 'Documents',
        'description' => 'Gestion documentaire.',
        'link' => '?page=parametres_generaux&action=documents',
        'icon' => 'fa-file-alt'
    ],
    [
        'title' => 'Recherche Globale',
        'description' => 'Recherche transversale dans toutes les données.',
        'link' => '?page=parametres_generaux&action=recherche_globale',
        'icon' => 'fa-search'
    ]
];

// 2. Paramètres SPÉCIFIQUES
$cardPSpecifiques = [
    [
        'title' => 'Critères',
        'description' => 'Critères d\'évaluation.',
        'link' => '?page=parametres_specifiques&action=criteres_evaluation',
        'icon' => 'fa-list-check'
    ],
    [
        'title' => 'Barème Critère',
        'description' => 'Barèmes par année et critère.',
        'link' => '?page=parametres_specifiques&action=bareme_critere',
        'icon' => 'fa-scale-balanced'
    ],
    [
        'title' => 'Salles',
        'description' => 'Lieux de soutenance.',
        'link' => '?page=parametres_specifiques&action=salles',
        'icon' => 'fa-door-open'
    ],
    [
        'title' => 'Programmation sessions',
        'description' => 'Calendrier approximatif des sessions de soutenance.',
        'link' => '?page=parametres_specifiques&action=programmation_sessions_soutenance',
        'icon' => 'fa-calendar-days'
    ],
    [
        'title' => 'Entreprises',
        'description' => 'Partenaires de stage.',
        'link' => '?page=parametres_specifiques&action=entreprises',
        'icon' => 'fa-building'
    ],
    [
        'title' => 'Spécialités',
        'description' => 'Options et spécialités.',
        'link' => '?page=parametres_specifiques&action=specialites',
        'icon' => 'fa-user-graduate'
    ],
    [
        'title' => 'Maître de stage',
        'description' => 'Référentiel des maîtres de stage.',
        'link' => '?page=parametres_specifiques&action=maitre_stage',
        'icon' => 'fa-user-tie'
    ],
    [
        'title' => 'Type Enseignant',
        'description' => 'Types d\'enseignants.',
        'link' => '?page=parametres_specifiques&action=type_enseignant',
        'icon' => 'fa-chalkboard-user'
    ],
    [
        'title' => 'Gestion des Menus',
        'description' => 'Structure de navigation.',
        'link' => '?page=parametres_specifiques&action=gestion_menus',
        'icon' => 'fa-sitemap'
    ],
    [
        'title' => 'Habilitations',
        'description' => 'Droits par groupe.',
        'link' => '?page=parametres_specifiques&action=gestion_attribution',
        'icon' => 'fa-key'
    ],
    [
        'title' => 'Messages Système',
        'description' => 'Libellés d\'erreurs.',
        'link' => '?page=parametres_specifiques&action=messages',
        'icon' => 'fa-envelope'
    ],
    [
        'title' => 'Structure BD',
        'description' => 'Couverture tables/colonnes dans les écrans paramètres.',
        'link' => '?page=parametres_specifiques&action=schema_tables',
        'icon' => 'fa-database'
    ],
    [
        'title' => 'Modèles PDF',
        'description' => 'Catalogue des documents PDF, aperçu et téléchargement.',
        'link' => '?page=parametres_specifiques&action=modeles_pdf',
        'icon' => 'fa-file-pdf'
    ]
];
$cardReclamation = [
    [
        'title' => 'Soumettre une Réclamation',
        'description' => 'Déposez une nouvelle réclamation en remplissant le formulaire dédié.',
        'link' => '?page=gestion_reclamations&action=soumettre_reclamation',
        'icon' => 'fa-solid fa-circle-exclamation ',
        'title_link' => 'Soumettre',
        'bg_color' => 'bg-primary',
        'text_color' => 'text-white'
    ],
    [
        'title' => 'Suivi et historique des réclamations',
        'description' => 'Consultez l\'état actuel de vos réclamations en cours et accédez à l\'historique complet de vos réclamations passées.',
        'link' => '?page=gestion_reclamations&action=suivi_historique_reclamation',
        'icon' => 'fa-solid fa-eye ',
        'title_link' => 'Suivi et historique',
        'bg_color' => 'bg-primary-light',
        'text_color' => 'text-white'
    ]
];

$polarizedPages = [
    'gestion_etudiants',
    'gestion_scolarite',
    'gestion_notes_evaluations',
    'gestion_dossiers_candidatures',
    'gestion_reclamations_scolarite',
    'dashboard_commission',
    'rapport_a_valider',
    'reception_rapport_com',
    'evaluation_dossiers',
    'evaluations_dossiers_soutenance',
    'processus_validation',
    'redaction_compte_rendu',
    'programmation_soutenance',
    'programation_soutenance',
    'evaluation_soutenance',
    'edition_bulletin',
];
$adminCrudPages = [
    'gestion_utilisateurs',
    'piste_audit',
    'sauvegarde_restauration',
    'admin_historique',
    'maj_enseignant',
    'maj_personnel_admin',
];
$isAdminParamCrud = in_array((string) $currentMenuSlug, ['parametres_generaux', 'parametres_specifiques'], true)
    && !empty($_GET['action'])
    && ((string) $_GET['action'] !== 'gestion_menus');
$isPolarizedPage = in_array((string) $currentMenuSlug, $polarizedPages, true)
    || in_array((string) $currentMenuSlug, $adminCrudPages, true)
    || $isAdminParamCrud;
$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$publicPrefix = strpos($scriptPath, '/app/') !== false ? '../' : '';

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheckMaster | <?php echo htmlspecialchars($currentPageLabel); ?></title>
    <link rel="stylesheet"
        href="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/checkmaster-theme.css') : 'assets/css/checkmaster-theme.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet"
        href="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/components.css') : 'assets/css/components.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet"
        href="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/utilities.css') : 'assets/css/utilities.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet"
        href="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/responsive.css') : 'assets/css/responsive.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet"
        href="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/compact-forms.css') : 'assets/css/compact-forms.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="shortcut icon"
        href="<?php echo htmlspecialchars($publicPrefix . 'image/logo_cm_sbg.png', ENT_QUOTES, 'UTF-8'); ?>"
        type="image/x-icon">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicPrefix . 'assets/vendor/font-awesome/css/all.min.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link href="<?php echo htmlspecialchars($publicPrefix . 'assets/vendor/flatpickr/flatpickr.min.css', ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?php echo htmlspecialchars($publicPrefix . 'assets/vendor/flatpickr/flatpickr.min.css', ENT_QUOTES, 'UTF-8'); ?>"></noscript>
    <style>
        :root {
            --cm-color-bg-secondary: #f9fafb;
            --cm-color-border: transparent;
            --cm-color-text-muted: #6b7280;
            --cm-color-text: #111827;
            --cm-color-danger: #ef4444;
            --cm-header-height: 60px;
            --cm-content-max-width: 100% !important;
        }
        .cm-content-area .cm-form-group {
            min-width: 0;
        }

        .cm-content-area .cm-form-label {
            margin-bottom: 0.24rem;
            line-height: 1.25;
        }

        .cm-content-area .cm-form-group.cm-field--date,
        .cm-content-area .cm-form-group.cm-field--select,
        .cm-content-area .cm-form-group.cm-field--number {
            max-width: 14rem;
        }

        .cm-content-area .cm-form-group.cm-field--text {
            max-width: 18rem;
        }

        .cm-content-area .cm-form-group.cm-field--email,
        .cm-content-area .cm-form-group.cm-field--password {
            max-width: 30rem;
        }

        /* ── Clickable rows ── */
        .cm-clickable-row {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .cm-clickable-row:hover {
            background-color: rgba(59, 130, 246, 0.08) !important;
        }

        .cm-clickable-row:active {
            background-color: rgba(59, 130, 246, 0.15) !important;
        }

        .cm-clickable-row td:last-child {
            padding-right: 1.5rem;
        }

        .cm-clickable-row td:first-child {
            padding-left: 1.5rem;
        }

        .cm-content-area .cm-form-group.cm-field--textarea,
        .cm-content-area .cm-form-group.cm-field--file,
        .cm-content-area .cm-form-group.cm-field--select-search {
            max-width: none;
        }

        .cm-content-area .cm-crud-wrapper form .cm-grid-2,
        .cm-content-area .cm-crud-wrapper form .cm-grid-3,
        .cm-content-area .cm-crud-wrapper form .cm-grid-4,
        .cm-content-area .cm-crud-wrapper form .cm-grid-5,
        .cm-content-area .cm-crud-wrapper form .cm-grid-auto,
        .cm-content-area .cm-pole-superieur form .cm-grid-2,
        .cm-content-area .cm-pole-superieur form .cm-grid-3,
        .cm-content-area .cm-pole-superieur form .cm-grid-4,
        .cm-content-area .cm-pole-superieur form .cm-grid-5,
        .cm-content-area .cm-pole-superieur form .cm-grid-auto {
            justify-content: start;
            gap: 0.55rem 0.75rem;
        }

        .cm-content-area form .cm-form-control:not(textarea),
        .cm-content-area form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]):not(.cm-etu-input):not(.search-input),
        .cm-content-area form select:not(.cm-etu-select) {
            min-height: 36px;
            padding-top: 0.34rem;
            padding-bottom: 0.34rem;
            font-size: 0.875rem;
        }

        .cm-content-area form textarea.cm-form-control,
        .cm-content-area form textarea:not(.cm-etu-textarea):not(.verification-comment) {
            min-height: 72px;
            padding: 0.45rem 0.6rem;
            font-size: 0.875rem;
        }

        .cm-content-area form input[type="date"]:not(.cm-etu-input),
        .cm-content-area form select:not(.cm-etu-select) {
            max-width: 15rem;
        }

        .cm-content-area form input[type="number"]:not(.cm-etu-input) {
            max-width: 11rem;
        }

        /* Champs compacts pour l'historique/archives */
        .cm-content-area .cm-prd6-admin-screen form .cm-form-group,
        .cm-content-area .cm-archive-etudiants form .cm-form-group,
        .cm-content-area .cm-archive-soutenances form .cm-form-group,
        .cm-content-area .cm-archives-jurys form .cm-form-group,
        .cm-content-area .cm-archives-documents form .cm-form-group,
        .cm-content-area .cm-archives-candidatures form .cm-form-group,
        .cm-content-area .cm-archives-reclamations form .cm-form-group {
            width: auto;
        }

        .cm-content-area .cm-prd6-admin-screen form .cm-form-control:not(textarea),
        .cm-content-area .cm-prd6-admin-screen form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]),
        .cm-content-area .cm-prd6-admin-screen form select,
        .cm-content-area .cm-archive-etudiants form .cm-form-control:not(textarea),
        .cm-content-area .cm-archive-etudiants form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]),
        .cm-content-area .cm-archive-etudiants form select,
        .cm-content-area .cm-archive-soutenances form .cm-form-control:not(textarea),
        .cm-content-area .cm-archive-soutenances form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]),
        .cm-content-area .cm-archive-soutenances form select {
            width: auto;
            min-width: 10ch;
            max-width: 24ch;
        }

        .cm-content-area .cm-prd6-admin-screen .cm-toolbar .cm-toolbar-field-lg {
            width: auto;
            min-width: 14ch;
            max-width: 24ch;
        }

        .cm-content-area .cm-prd6-admin-screen .cm-toolbar .cm-toolbar-field-md,
        .cm-content-area .cm-prd6-admin-screen .cm-toolbar .cm-toolbar-field-sm,
        .cm-content-area .cm-prd6-admin-screen .cm-toolbar .cm-toolbar-field-xs {
            width: auto;
            min-width: 8ch;
            max-width: 14ch;
        }

        .cm-content-area .cm-prd6-admin-screen form select[name*="niveau"],
        .cm-content-area .cm-prd6-admin-screen form input[name*="niveau"],
        .cm-content-area .cm-archive-etudiants form select[name*="niveau"],
        .cm-content-area .cm-archive-etudiants form input[name*="niveau"],
        .cm-content-area .cm-archive-soutenances form select[name*="niveau"],
        .cm-content-area .cm-archive-soutenances form input[name*="niveau"] {
            min-width: 9ch;
            max-width: 19ch;
        }

        /* ── ÉCRAN ÉVALUATION SOUTENANCE ── */

        /* Layout twin: grille à gauche, jury à droite */
        .cm-eval-twin-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 12.5rem;
            gap: 0.85rem;
            align-items: start;
            margin-top: 0.45rem;
            margin-bottom: 0.45rem;
        }

        .cm-eval-criteria-col {
            min-width: 0;
        }

        /* Ligne minimale : Soutenance + Salle + Date/heure */
        .cm-eval-top-row {
            display: grid;
            grid-template-columns: minmax(14rem, 1fr) 8.2rem 10.5rem;
            gap: 0.45rem;
            align-items: end;
            margin-bottom: 0.2rem;
        }

        .cm-eval-top-row .cm-form-group {
            margin-bottom: 0;
            max-width: none;
        }

        .cm-eval-top-row .cm-form-label {
            font-size: 0.72rem;
            margin-bottom: 0.1rem;
        }

        .cm-eval-top-row .cm-form-control,
        .cm-eval-top-row select,
        .cm-eval-top-row input[type="text"] {
            min-height: 36px;
            height: 36px;
            padding: 0.32rem 0.52rem;
            font-size: 0.875rem;
        }

        .cm-eval-table thead tr {
            background: var(--cm-color-bg-secondary, #f9fafb);
        }

        .cm-eval-table th,
        .cm-eval-table td {
            padding: 0.22rem 0.4rem;
            border-bottom: 1px solid var(--cm-color-border, #e5e7eb);
            vertical-align: middle;
        }

        .cm-eval-table th {
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--cm-color-text-muted, #6b7280);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .cm-eval-th--critere {
            width: 60%;
            text-align: left;
        }

        .cm-eval-th--bareme {
            width: 15%;
            text-align: center;
        }

        .cm-eval-th--note {
            width: 25%;
            text-align: left;
        }

        .cm-eval-td--critere {
            color: var(--cm-color-text, #111827);
        }

        .cm-eval-td--bareme {
            text-align: center;
            color: var(--cm-color-text-muted, #6b7280);
        }

        .cm-eval-td--note {
            white-space: nowrap;
        }

        /* Input note dans la table */
        .cm-eval-note-input {
            width: 3.1rem;
            min-width: 3.1rem;
            padding: 0.2rem 0.3rem;
            font-size: 0.875rem;
            border: 1px solid var(--cm-color-border, #d1d5db);
            border-radius: 4px;
            text-align: right;
            background: #fff;
        }

        .cm-eval-note-input.is-invalid {
            border-color: var(--cm-color-danger, #ef4444);
            background: #fef2f2;
        }

        .cm-eval-note-max {
            margin-left: 0.2rem;
            font-size: 0.8rem;
            color: var(--cm-color-text-muted, #6b7280);
        }

        /* Ligne MOYENNE PONDÉRÉE (tfoot) */
        .cm-eval-moyenne-row td {
            background: var(--cm-color-bg-secondary, #f3f4f6);
            font-size: 0.82rem;
            border-top: 2px solid var(--cm-color-border, #d1d5db);
        }

        .cm-eval-moyenne-input {
            width: 3.1rem;
            padding: 0.12rem 0.25rem;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid var(--cm-color-border, #d1d5db);
            border-radius: 4px;
            text-align: right;
            background: var(--cm-color-bg-secondary, #f3f4f6);
            color: var(--cm-color-text, #111827);
        }

        /* Zone sous la table (décision + commentaire + boutons) */
        .cm-eval-below-table {
            padding: 0.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .cm-eval-decision-row .cm-form-group {
            margin-bottom: 0;
            max-width: 9rem;
        }

        .cm-eval-below-table .cm-form-group {
            margin-bottom: 0;
        }

        .cm-eval-below-table .cm-form-buttons {
            margin-top: 0.1rem;
            justify-content: flex-end;
        }

        /* Jury : champs ultra-compacts empilés */
        .cm-eval-section-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--cm-color-text-muted, #6b7280);
            margin: 0 0 0.2rem;
            padding-bottom: 0.12rem;
            border-bottom: 1px solid var(--cm-color-border, #e5e7eb);
        }

        .cm-eval-jury-col .cm-form-group {
            margin-bottom: 0;
            max-width: none;
        }

        .cm-eval-jury-col .cm-form-label {
            font-size: 0.78rem;
            margin-bottom: 0.1rem;
            color: var(--cm-color-text-muted, #6b7280);
        }

        .cm-eval-jury-col .cm-form-control,
        .cm-eval-jury-col input[type="text"] {
            width: 100%;
            font-size: 0.875rem;
            padding: 0.32rem 0.52rem;
            min-height: 36px;
            height: 36px;
            background: var(--cm-color-bg-secondary, #f9fafb);
        }

        @media (max-width: 860px) {
            .cm-eval-twin-panel {
                grid-template-columns: 1fr;
            }

            .cm-eval-top-row {
                grid-template-columns: repeat(2, minmax(10rem, 1fr));
            }

            .cm-eval-top-row .cm-form-group:first-child {
                grid-column: 1 / -1;
            }

            .cm-eval-jury-col {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.35rem 0.55rem;
            }

            .cm-eval-jury-col .cm-eval-section-label {
                grid-column: 1 / -1;
            }
        }

        /* Formulaire ajout étudiant : disposition et tailles */
        .cm-content-area .cm-ajout-etudiant-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Ligne 1 : Niveau + Promotion alignés côte à côte, taille fixe */
        .cm-content-area .cm-ajout-etudiant-form .cm-grid-3 {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.75rem 1rem;
        }

        /* Ligne 2 : 4 champs égaux sur une rangée */
        .cm-content-area .cm-ajout-etudiant-form .cm-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem 1rem;
            align-items: end;
        }

        /* Ligne 2 (Identifiant, Carte, Nom, Prenom): élargir Identifiant + Carte + Prenom */
        .cm-content-area .cm-ajout-etudiant-form .cm-grid-4:has(input[name="identifiant_mesrs"]) {
            grid-template-columns: 1.35fr 1.35fr 1fr 1.35fr;
        }

        /* Taille confortable pour tous les champs */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-control:not(textarea),
        .cm-content-area .cm-ajout-etudiant-form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]),
        .cm-content-area .cm-ajout-etudiant-form select {
            width: 100%;
            min-height: 36px;
            font-size: 0.9rem;
            padding: 0.35rem 0.6rem;
        }

        /* Niveau : largeur calée sur son contenu */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="id_niveau"]) {
            width: 9rem;
            flex-shrink: 0;
        }

        /* Promotion : un peu plus large */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="promotion_etu"]) {
            width: 8.5rem;
            flex-shrink: 0;
        }

        /* Date naissance : largeur fixe lisible */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(input[name="date_naiss_etu"]) {
            width: 13rem;
            flex-shrink: 0;
        }

        /* Genre : compact */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="genre_etu"]) {
            width: 3.6rem;
            min-width: 3.6rem;
            max-width: 3.6rem;
            flex-shrink: 0;
        }

        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="genre_etu"]) .cm-form-label {
            display: inline-flex;
            align-items: flex-start;
            gap: 0.2rem;
            white-space: nowrap;
        }

        /* Email : prend le reste */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(input[name="email_etu"]) {
            flex: 1 1 30rem;
            min-width: 30rem;
            max-width: 40rem;
        }

        /* Ligne 3 reprend le même modèle flex */
        .cm-content-area .cm-ajout-etudiant-form .cm-grid-3 .cm-form-group {
            margin-bottom: 0;
        }

        @media (max-width: 900px) {
            .cm-content-area .cm-ajout-etudiant-form .cm-grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {

            .cm-content-area .cm-ajout-etudiant-form .cm-grid-3,
            .cm-content-area .cm-ajout-etudiant-form .cm-grid-4 {
                flex-direction: column;
                grid-template-columns: 1fr;
            }

            .cm-content-area .cm-ajout-etudiant-form .cm-form-group,
            .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="id_niveau"]),
            .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="promotion_etu"]),
            .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(input[name="date_naiss_etu"]),
            .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="genre_etu"]) {
                width: 100%;
            }
        }

        /* Ajustements barre haute: titre de page + année académique */
        .cm-navbar__left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
            flex: 1 1 auto;
        }

        .cm-navbar__toggle {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            border-radius: 8px !important;
            color: var(--cm-primary-dark) !important;
            width: auto !important;
            min-width: 36px !important;
            height: 36px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            transition: all 150ms ease !important;
            padding: 0 0.75rem !important;
            gap: 0.35rem !important;
            outline: none !important;
        }

        .cm-navbar__toggle:hover {
            background-color: rgba(0, 0, 0, 0.05) !important;
            color: var(--cm-primary-dark) !important;
        }

        .cm-navbar__toggle:active {
            transform: scale(0.95) !important;
        }

        .cm-navbar__btn-text {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .cm-navbar__page-title {
            display: block;
            flex: 1 1 auto;
            max-width: none;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--cm-primary-dark);
            font-size: 1.05rem;
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: -0.010em;
            margin-left: 0.5rem;
        }

        .cm-navbar__right {
            width: auto;
            flex: 0 0 auto;
            margin-left: auto;
            justify-content: flex-end;
            gap: 1rem;
        }

        .cm-navbar__right.is-empty {
            display: none;
        }

        .cm-navbar__year-selector {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.15rem;
            margin-left: 0;
            font-size: 0.82rem;
            width: auto;
        }

        .cm-sidebar__user-summary {
            width: 100%;
            color: #fff;
            text-align: left;
        }

        .cm-sidebar__user-name {
            display: block;
            font-weight: 600;
            font-size: 0.82rem;
            line-height: 1.25;
        }

        .cm-sidebar__user-role {
            display: block;
            margin-top: 0.1rem;
            opacity: 0.7;
            font-size: 0.72rem;
            line-height: 1.2;
        }

        .cm-navbar__year-selector label {
            color: var(--cm-primary-dark);
            opacity: 0.85;
            margin: 0;
            font-size: 0.72rem;
            font-weight: 600;
            text-align: center;
            line-height: 1;
            white-space: nowrap;
        }

        /* Dropdown customisé pour l'année académique */
        .cm-year-dropdown {
            position: relative;
            display: inline-block;
            width: auto;
            min-width: 120px;
        }

        .cm-year-dropdown-btn {
            width: auto;
            min-width: 120px;
            max-width: 100%;
            font-size: 0.8rem;
            font-weight: 600;
            min-height: 32px;
            height: 32px;
            padding: 0.2rem 2.2rem 0.2rem 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--cm-input-border);
            background-color: rgba(255, 255, 255, 0.4);
            color: var(--cm-primary-dark);
            outline: none;
            cursor: pointer;
            transition: all 150ms ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: none !important;
            filter: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        .cm-year-dropdown-btn span {
            flex: 1 1 auto;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cm-year-dropdown-btn i {
            position: absolute;
            right: 0.8rem;
            font-size: 0.75rem;
            color: var(--cm-primary-dark);
            transition: transform 150ms ease;
        }

        .cm-year-dropdown.is-open .cm-year-dropdown-btn i {
            transform: rotate(180deg);
        }

        .cm-year-dropdown-btn:hover {
            background-color: rgba(255, 255, 255, 0.7) !important;
            border-color: var(--cm-primary-light) !important;
        }

        .cm-year-dropdown-btn:focus {
            background-color: rgba(255, 255, 255, 0.9) !important;
            border-color: var(--cm-primary-light) !important;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15) !important;
        }

        .cm-year-dropdown-menu {
            position: absolute;
            top: calc(100% + 4px);
            left: 50%;
            transform: translateX(-50%) translateY(-10px) scale(0.95);
            width: max-content;
            min-width: 100%;
            max-height: 280px;
            overflow-y: auto;
            overflow-x: hidden;
            background-color: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            z-index: var(--cm-z-dropdown);
            opacity: 0;
            pointer-events: none;
            transition: opacity 150ms ease, transform 150ms cubic-bezier(0.16, 1, 0.3, 1);
            scrollbar-width: thin;
            scrollbar-color: rgba(0,0,0,0.15) transparent;
        }

        .cm-year-dropdown.is-open .cm-year-dropdown-menu {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0) scale(1);
        }

        .cm-year-dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }

        .cm-year-dropdown-menu::-webkit-scrollbar-thumb {
            background-color: rgba(0,0,0,0.15);
            border-radius: 3px;
        }

        .cm-year-dropdown-item {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: #334155;
            text-align: center;
            text-decoration: none;
            transition: all 120ms ease;
            border-bottom: 1px solid rgba(0,0,0,0.02);
            box-sizing: border-box;
        }

        .cm-year-dropdown-item:first-child {
            border-top-left-radius: 7px;
            border-top-right-radius: 7px;
        }

        .cm-year-dropdown-item:last-child {
            border-bottom-left-radius: 7px;
            border-bottom-right-radius: 7px;
            border-bottom: none;
        }

        .cm-year-dropdown-item:hover {
            background-color: #f1f5f9;
            color: var(--cm-primary-dark);
        }

        .cm-year-dropdown-item.is-active {
            background-color: var(--cm-primary-lightest);
            color: var(--cm-primary-dark);
            font-weight: 700;
        }

        /* Badge readonly pour l'année */
        .cm-navbar__year-selector .cm-navbar__year-display {
            width: auto;
            min-width: 120px;
            font-size: 0.8rem;
            font-weight: 600;
            min-height: 32px;
            height: 32px;
            padding: 0.2rem 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--cm-input-border);
            background-color: rgba(255, 255, 255, 0.4);
            color: var(--cm-primary-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
            cursor: default;
            user-select: none;
            box-shadow: none !important;
            filter: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        /* Empêche la scrollbar de passer derrière le header */
        #cmLayoutMain {
            margin-top: var(--cm-header-height);
            height: calc(100vh - var(--cm-header-height));
            padding-top: 0.8rem;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-gutter: stable;
        }

        /* Scrollbar visible sur tous les tableaux (sans exception) */
        .cm-content-area .cm-table-wrapper,
        .cm-content-area .cm-table-responsive,
        .cm-content-area .cm-prd3-crud-screen .cm-table-wrapper,
        .cm-content-area .cm-prd6-admin-screen .cm-table-wrapper {
            display: block !important;
            height: auto !important;
            min-height: 200px !important;
            max-height: 75vh !important;
            overflow-x: auto !important;
            overflow-y: auto !important;
            scrollbar-gutter: stable both-edges !important;
            scrollbar-width: auto !important;
            scrollbar-color: #2a8fd4 #d6e6f5 !important;
        }

        .cm-content-area .cm-table-wrapper::-webkit-scrollbar,
        .cm-content-area .cm-table-responsive::-webkit-scrollbar,
        .cm-content-area .cm-prd3-crud-screen .cm-table-wrapper::-webkit-scrollbar,
        .cm-content-area .cm-prd6-admin-screen .cm-table-wrapper::-webkit-scrollbar {
            width: 12px !important;
            height: 12px !important;
        }

        .cm-content-area .cm-table-wrapper::-webkit-scrollbar-track,
        .cm-content-area .cm-table-responsive::-webkit-scrollbar-track,
        .cm-content-area .cm-prd3-crud-screen .cm-table-wrapper::-webkit-scrollbar-track,
        .cm-content-area .cm-prd6-admin-screen .cm-table-wrapper::-webkit-scrollbar-track {
            background: rgba(214, 230, 245, 0.65) !important;
        }

        .cm-content-area .cm-table-wrapper::-webkit-scrollbar-thumb,
        .cm-content-area .cm-table-responsive::-webkit-scrollbar-thumb,
        .cm-content-area .cm-prd3-crud-screen .cm-table-wrapper::-webkit-scrollbar-thumb,
        .cm-content-area .cm-prd6-admin-screen .cm-table-wrapper::-webkit-scrollbar-thumb {
            background: #2a8fd4 !important;
            border-radius: 8px !important;
            border: 2px solid rgba(214, 230, 245, 0.65) !important;
        }

        /* Toolbar unifiée : une seule ligne, sans débordement ni scroll horizontal */
        .cm-content-area .cm-barre-intermediaire {
            overflow: visible !important;
            margin-bottom: 0.5rem !important;
        }

        .cm-content-area .cm-barre-intermediaire .cm-toolbar {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 0.5rem !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin-left: auto !important;
            margin-right: auto !important;
            box-sizing: border-box !important;
            overflow: hidden !important; /* jamais de scroll : le contenu se compacte */
        }

        .cm-content-area .cm-barre-intermediaire .cm-toolbar-left,
        .cm-content-area .cm-barre-intermediaire .cm-toolbar-center,
        .cm-content-area .cm-barre-intermediaire .cm-toolbar-right {
            display: flex !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            width: auto !important;
            min-width: 0 !important;
            gap: 0.4rem !important;
            white-space: nowrap !important;
        }

        .cm-content-area .cm-barre-intermediaire .cm-toolbar-left {
            flex: 0 1 auto !important;
        }

        .cm-content-area .cm-barre-intermediaire .cm-toolbar-center {
            flex: 0 1 auto !important; /* se compacte avant la zone droite */
            width: clamp(10rem, 20vw, 15rem) !important;
            min-width: 10rem !important;
            max-width: 15rem !important;
            justify-content: flex-start !important;
        }

        .cm-content-area .cm-barre-intermediaire .cm-toolbar-right {
            flex: 1 1 auto !important;
            justify-content: flex-end !important;
            margin-left: auto !important;
            overflow: hidden !important;
        }

        .cm-content-area .cm-barre-intermediaire .cm-toolbar__actions-group {
            display: inline-flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 0.3rem !important;
            flex: 0 0 auto !important;
        }

        .cm-content-area .cm-barre-intermediaire .cm-toolbar .cm-btn {
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }

        .cm-content-area .cm-barre-intermediaire .cm-toolbar .cm-toolbar__search-wrap,
        .cm-content-area .cm-barre-intermediaire .cm-toolbar .cm-toolbar-field-lg {
            width: 100% !important;
            min-width: 10rem !important;
            max-width: 15rem !important;
        }

        /* Sur écrans étroits : ne garder que les icônes des boutons d'action */
        @media (max-width: 1100px) {
            .cm-content-area .cm-barre-intermediaire .cm-toolbar .cm-btn[data-cm-toolbar-action] span {
                display: none !important;
            }
            .cm-content-area .cm-barre-intermediaire .cm-toolbar .cm-toolbar__actions-group .cm-btn {
                padding: 0.3rem 0.5rem !important;
            }
        }

        @media (max-width: 720px) {
            .cm-content-area .cm-barre-intermediaire .cm-toolbar {
                gap: 0.35rem !important;
            }
            .cm-content-area .cm-barre-intermediaire .cm-toolbar-center {
                width: 9rem !important;
                min-width: 9rem !important;
                max-width: 9rem !important;
            }
            .cm-content-area .cm-barre-intermediaire .cm-toolbar .cm-toolbar__control span {
                display: none !important;
            }
            .cm-content-area .cm-barre-intermediaire .cm-toolbar .cm-toolbar__search-wrap,
            .cm-content-area .cm-barre-intermediaire .cm-toolbar .cm-toolbar-field-lg {
                min-width: 8rem !important;
                max-width: 9rem !important;
            }
        }

        @media (max-width: 900px) {
            .cm-navbar__page-title {
                max-width: calc(100vw - 18rem);
                font-size: 0.92rem;
                white-space: normal;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                line-height: 1.15;
                margin-left: 0.35rem;
            }

            .cm-navbar__year-selector {
                margin-left: 0.35rem;
                width: auto;
            }
        }

        @media (max-width: 600px) {
            .cm-navbar__btn-text {
                display: none !important;
            }
            .cm-navbar__toggle {
                width: 36px !important;
                padding: 0 !important;
            }
            .cm-navbar__year-selector label {
                display: none;
            }
        }

        /* Correction pour cm-screen-scrollable : autorise la liste à descendre naturellement */
        .cm-screen-scrollable,
        .cm-screen-scrollable .cm-crud-wrapper,
        .cm-screen-scrollable .cm-pole-inferieur,
        .cm-screen-scrollable .cm-table-wrapper {
            height: auto !important;
            max-height: none !important;
            min-height: auto !important;
            overflow-y: visible !important;
            overflow-x: auto !important;
            display: block !important;
        }
    </style>
    <script src="<?php echo htmlspecialchars($publicPrefix . 'assets/vendor/chart.js/chart.umd.min.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicPrefix . 'assets/vendor/flatpickr/flatpickr.min.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars($publicPrefix . 'assets/vendor/flatpickr/fr.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($publicPrefix . 'assets/vendor/animate.css/animate.min.css', ENT_QUOTES, 'UTF-8'); ?>">

</head>

<body class="cm-app-body">
    <aside class="cm-sidebar" id="cmSidebar">
        <div class="cm-sidebar__header">
            <div class="cm-sidebar__logo">
                <img src="<?php echo htmlspecialchars($publicPrefix . 'image/logo_cm_sbg.png', ENT_QUOTES, 'UTF-8'); ?>"
                    alt="Logo CheckMaster" class="cm-sidebar__logo-img">
                <div class="cm-sidebar__brand">
                    <span class="cm-sidebar__logo-text">CHECK MASTER</span>
                    <div class="cm-sidebar__user-summary">
                        <span class="cm-sidebar__user-name"><?php echo htmlspecialchars($_SESSION['nom_utilisateur'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="cm-sidebar__user-role"><?php echo htmlspecialchars($_SESSION['lib_GU'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
        <nav class="cm-sidebar__nav">
            <?php echo $menuHTML; ?>
        </nav>
        <div class="cm-sidebar__footer">
            <form action="index.php?_path=/logout" method="POST" id="logoutForm" class="cm-w-full">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" form="logoutForm"
                    class="cm-btn cm-btn--sidebar-logout cm-w-full cm-flex-center cm-flex-gap-sm">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    <span class="cm-text-sm">Déconnexion</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="cm-main-wrapper" id="mainWrapper">
        <header class="cm-navbar" id="cmNavbar">
            <div class="cm-navbar__left">
                <button type="button" class="cm-navbar__toggle" id="backButton" aria-label="Retour" title="Retour"
                    onclick="history.back()">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span class="cm-navbar__btn-text">Retour</span>
                </button>
                <button type="button" class="cm-navbar__toggle" id="sidebarToggle" aria-label="Ouvrir/fermer le menu">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                    <span class="cm-navbar__btn-text">Menu</span>
                </button>
                <span
                    class="cm-navbar__page-title"><?= htmlspecialchars((string) ($currentPageLabel ?? 'CheckMaster'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="cm-navbar__right<?= $hideAcademicYearOnNavbar ? ' is-empty' : '' ?>">
                <?php if (!$hideAcademicYearOnNavbar): ?>
                    <div class="cm-navbar__year-selector<?= $lockAcademicYearOnNavbar ? ' is-readonly' : '' ?>">
                        <label for="globalAnneeAcademique">Année académique</label>
                        <?php if ($lockAcademicYearOnNavbar): ?>
                            <div id="globalAnneeAcademique" class="cm-navbar__year-display" aria-readonly="true"
                                title="<?= htmlspecialchars((string) $navbarAcademicYearLabel, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) $navbarAcademicYearLabel, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php else: ?>
                            <?php
                            $yearQueryBase = $_GET;
                            unset(
                                $yearQueryBase['global_annee'],
                                $yearQueryBase['global_annee_id'],
                                $yearQueryBase['annee'],
                                $yearQueryBase['id_annee_acad'],
                                $yearQueryBase['bulletin_annee']
                            );
                            ?>
                            <div class="cm-year-dropdown" id="globalAnneeDropdown">
                                <button type="button" class="cm-year-dropdown-btn" id="globalAnneeBtn">
                                    <span><?= $currentGlobalYearIsAll ? htmlspecialchars(AcademicYear::getAllLabel(), ENT_QUOTES, 'UTF-8') : htmlspecialchars($currentGlobalYear) ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="cm-year-dropdown-menu" id="globalAnneeMenu">
                                    <?php $allYearsQuery = $yearQueryBase;
                                    $allYearsQuery['global_annee_id'] = AcademicYear::getAllQueryValue(); ?>
                                    <a href="?<?= htmlspecialchars(http_build_query($allYearsQuery), ENT_QUOTES, 'UTF-8') ?>"
                                        class="cm-year-dropdown-item <?= $currentGlobalYearIsAll ? 'is-active' : '' ?>"
                                        data-cm-ajax-link="false">
                                        <?= htmlspecialchars(AcademicYear::getAllLabel(), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <?php foreach ($globalAcademicYears as $id => $label): ?>
                                        <?php $yearQuery = $yearQueryBase;
                                        $yearQuery['global_annee_id'] = $id; ?>
                                        <a href="?<?= htmlspecialchars(http_build_query($yearQuery), ENT_QUOTES, 'UTF-8') ?>"
                                            class="cm-year-dropdown-item <?= (!$currentGlobalYearIsAll && (int) $currentGlobalYearId === (int) $id) ? 'is-active' : '' ?>"
                                            data-cm-ajax-link="false">
                                            <?= htmlspecialchars($label) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main id="cmLayoutMain"
            class="cm-content-area cm-layout-main <?php echo $isPolarizedPage ? 'cm-layout-main--locked' : 'cm-layout-main--scroll'; ?>"
            data-page="<?php echo htmlspecialchars((string) $currentMenuSlug, ENT_QUOTES, 'UTF-8'); ?>"
            data-action="<?php echo htmlspecialchars((string) ($currentAction ?? ($_GET['action'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">

            <?php // Les variables $globalAcademicYears, $currentGlobalYear, $currentGlobalYearId
            // sont calculées en haut du fichier (après database.php) et $_SESSION['global_annee_id'] est déjà défini. ?>


            <?php
            // Si $contentFile est explicitement null, le contenu a déjà été géré par un service
            if ($contentFile !== null) {
                if (!empty($contentFile) && file_exists($contentFile)) {
                    include $contentFile;
                } else {
                    echo "<div class='cm-card cm-p-lg'>";
                    echo "<div class='cm-text-danger cm-text-semibold cm-mb-sm'>Erreur de chargement</div>";
                    if (empty($contentFile)) {
                        echo "<div>Aucun fichier de contenu n'a été spécifié pour cette vue.</div>";
                    } else {
                        echo "<div>Le fichier de contenu pour '" . htmlspecialchars($currentPageLabel) . "' est introuvable.</div>";
                    }
                    echo "</div>";
                }
            }
            ?>
            <?php cm_component('ui/toast'); ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('cmSidebar');
            const wrapper = document.getElementById('mainWrapper');

            if (toggle) {
                toggle.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 768px)').matches) {
                        // Mobile : slide in/out via is-open
                        if (sidebar) sidebar.classList.toggle('is-open');
                    } else {
                        // Desktop : collapse layout (hidden sidebar + full-width content)
                        document.body.classList.toggle('is-collapsed');
                    }
                });
            }

            // Mobile : close sidebar when clicking outside
            document.addEventListener('click', function (e) {
                if (!window.matchMedia('(max-width: 768px)').matches) return;
                if (sidebar && toggle &&
                    !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('is-open');
                }
            });

            // Toggle custom academic year dropdown (delegated to support AJAX re-rendering)
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('#globalAnneeBtn');
                const dropdown = document.getElementById('globalAnneeDropdown');
                if (btn) {
                    e.stopPropagation();
                    if (dropdown) {
                        dropdown.classList.toggle('is-open');
                    }
                } else if (dropdown && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('is-open');
                }
            });
        });
    </script>
    <script>
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form || typeof form.getAttribute !== 'function') {
                return;
            }

            const confirmMessage = form.getAttribute('data-cm-confirm-message');
            if (!confirmMessage) {
                return;
            }

            event.preventDefault();
            const confirmType = form.getAttribute('data-cm-confirm-type') || 'warning';
            const confirmText = form.getAttribute('data-cm-confirm-text') || 'Confirmer';

            if (window.CM && typeof window.CM.confirm === 'function') {
                window.CM.confirm({
                    title: 'Confirmation',
                    message: confirmMessage,
                    type: confirmType,
                    confirmText: confirmText,
                }).then(function (confirmed) {
                    if (confirmed) {
                        form.setAttribute('data-cm-confirmed', 'true');
                        form.submit();
                    }
                });
            } else if (window.confirm(confirmMessage)) {
                form.submit();
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const gridMap = {
                'cm-grid-2': 2,
                'cm-grid-3': 3,
                'cm-grid-4': 4,
                'cm-grid-5': 5,
                'cm-grid-6': 6
            };

            Object.keys(gridMap).forEach(function (legacyClass) {
                document.querySelectorAll('.' + legacyClass).forEach(function (grid) {
                    grid.classList.add('cm-form-grid', 'cm-form-grid--' + gridMap[legacyClass]);
                });
            });

            document.querySelectorAll('input[type="date"]').forEach(function (field) {
                field.classList.add('cm-field--date');
            });

            document.querySelectorAll('input[type="email"]').forEach(function (field) {
                field.classList.add('cm-field--lg');
            });

            document.querySelectorAll('textarea').forEach(function (field) {
                field.classList.add('cm-field--full');
            });

            document.querySelectorAll('.cm-modal-overlay, .cm-etu-modal, .cm-etu-preview-modal, [id$="Modal"]').forEach(function (panel) {
                if (!panel) {
                    return;
                }
                if (!/^(DIV|SECTION|ASIDE|DIALOG)$/i.test(panel.tagName)) {
                    return;
                }
                panel.classList.add('cm-legacy-panel');
                panel.classList.remove('cm-modal-overlay');
                panel.setAttribute('data-cm-legacy-modal', '1');
            });
        });
    </script>
    <script>
        // ── Clickable rows handler (global) ──
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.cm-clickable-row[data-href]').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    const tag = e.target.tagName.toLowerCase();
                    if (tag === 'a' || tag === 'button' || tag === 'input' || tag === 'select' || tag === 'textarea') {
                        return;
                    }
                    const href = row.getAttribute('data-href');
                    const type = row.getAttribute('data-link-type') || 'href';
                    if (type === 'dialog') {
                        const evt = new CustomEvent('cm:row-click', { detail: { row: row, href: href } });
                        document.dispatchEvent(evt);
                    } else {
                        window.location.href = href;
                    }
                });
            });
        });
    </script>
    <script defer
        src="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('js/app.js') : 'assets/js/app.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script defer
        src="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('js/inline-confirm.js') : 'assets/js/inline-confirm.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script defer
        src="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('js/docviewer.js') : 'assets/js/docviewer.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>

</html>

<?php
/**
 * Injecte un champ CSRF dans les formulaires POST du legacy.
 * Objectif: sécuriser l'existant sans modifier toutes les vues d'un coup.
 */
function injectCsrfIntoPostForms(string $html): string
{
    $token = htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8');
    $field = '<input type="hidden" name="csrf_token" value="' . $token . '">';

    // Ajout juste après la balise <form ... method="post" ...>
    return preg_replace(
        '/(<form\\b[^>]*\\bmethod\\s*=\\s*(?:\"|\\\')?post(?:\"|\\\')?[^>]*>)/i',
        '$1' . $field,
        $html
    ) ?? $html;
}

$__out = ob_get_clean();

// For AJAX requests: extract ONLY the #cmLayoutMain content (skip layout, sidebar, navbar, scripts)
// This dramatically reduces response size and eliminates unnecessary DOM parsing on the client.
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
    // Extract the page title
    $title = '';
    if (preg_match('/<title>([^<]+)<\/title>/i', $__out, $m)) {
        $title = htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8');
    }
    $navbarTitle = '';
    if (preg_match('/<span[^>]*class="cm-navbar__page-title"[^>]*>([\s\S]*?)<\/span>/i', $__out, $m)) {
        $navbarTitle = htmlspecialchars(trim(strip_tags($m[1])), ENT_QUOTES, 'UTF-8');
    }
    // Extract the main content area (cmLayoutMain)
    $mainOpenTag = '<main id="cmLayoutMain">';
    $mainContent = '';
    if (preg_match('/(<main[^>]*id="cmLayoutMain"[^>]*>)([\s\S]*?)<\/main>/i', $__out, $m)) {
        $mainOpenTag = $m[1];
        $mainContent = $m[2];
    }
    if ($mainContent !== '') {
        // Return minimal HTML document that app.js can still parse
        // Set content-type explicitly for clarity
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>' . $title . '</title></head><body>'
           . '<span class="cm-navbar__page-title">' . $navbarTitle . '</span>'
           . $mainOpenTag . $mainContent . '</main></body></html>';
        exit;
    }
}

echo injectCsrfIntoPostForms($__out);
