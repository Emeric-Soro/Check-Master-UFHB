<?php
/**
 * CheckMaster Premium - Layout Principal Refactorisé
 * 
 * Ce layout utilise le Design System Premium avec les composants modulaires.
 * Il peut coexister avec l'ancien layout.php pour une migration progressive.
 */

require_once __DIR__ . '/../app/Core/Autoload.php';

use CheckMaster\Core\Csrf;
use CheckMaster\Core\Session;
use CheckMaster\Core\Bootstrap;

Bootstrap::init();
Session::start();

// Bufferiser la sortie pour injecter CSRF sur les formulaires legacy
ob_start();

include __DIR__ . '/../app/config/database.php';
include __DIR__ . '/../app/controllers/AuthController.php';
include __DIR__ . '/../app/controllers/MenuController.php';
include __DIR__ . '/../app/middlewares/PermissionMiddleware.php';
include __DIR__ . '/../app/utils/permissions_helper.php';

use CheckMaster\Security\RoutePermissionService;

// Charger tous les composants Premium
require_once __DIR__ . '/../ressources/views/components/index.php';

// Redirection si non connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: page_connexion.php');
    exit;
}

// Protection CSRF globale pour les POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez réessayer.']);
            exit;
        }

        $_SESSION['error_message'] = 'Session expirée. Veuillez réessayer.';
        $_SESSION['error_type'] = 'csrf';
        $fallback = 'layout_premium.php?page=' . urlencode($_GET['page'] ?? 'dashboard');
        $redirect = $_SERVER['HTTP_REFERER'] ?? $fallback;
        header('Location: ' . $redirect);
        exit;
    }
}

// Initialiser le middleware de permissions
$permissionMiddleware = new PermissionMiddleware();
$menuController = new MenuController();

// Menu hiérarchique avec catégories
$menuHierarchique = $menuController->genererMenuHierarchique($_SESSION['id_GU']);

// Déterminer la page actuelle
$currentMenuSlug = isset($_GET['page']) ? $_GET['page'] : '';
$currentPageLabel = '';

// Pages sans vérification de permissions
$noCheckPages = ['page_connexion', 'logout', 'reset_password', 'access_denied'];

// Vérification des permissions
if (!empty($currentMenuSlug) && !in_array($currentMenuSlug, $noCheckPages)) {
    $permService = new RoutePermissionService(Database::getConnection());
    $resolved = $permService->resolveLegacy($_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'GET');
    $requiredAction = $resolved['action'];
    
    $hasPermission = $permService->canAccessLegacy((int) $_SESSION['id_GU'], $_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'GET');

    if (!$hasPermission) {
        $permissionMiddleware->logUnauthorizedAccess(
            $_SESSION['id_utilisateur'],
            $currentMenuSlug,
            $requiredAction
        );

        $routeHint = $resolved['pattern'] !== '' ? (' (' . $resolved['pattern'] . ')') : '';
        $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'accéder à cette page$routeHint (action: $requiredAction).";
        $_SESSION['error_type'] = 'permission_denied';

        header('Location: layout_premium.php?page=access_denied');
        exit;
    }
}

// Chercher le label dans le menu hiérarchique
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

// Pages spéciales
if (empty($currentPageLabel)) {
    $specialPages = [
        'archive_comptes_rendus' => 'Archives des comptes rendus',
        'redaction_compte_rendu' => 'Rédaction de compte rendu',
        'access_denied' => 'Accès refusé'
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
        parse_str(parse_url($firstFonc->url_fonctionnalite, PHP_URL_QUERY), $params);
        if (isset($params['page'])) {
            $currentMenuSlug = $params['page'];
            $currentPageLabel = $firstFonc->label_fonctionnalite;
            header('Location: layout_premium.php?page=' . urlencode($currentMenuSlug));
            exit;
        }
    }
}

// Déterminer le fichier de contenu
$contentFile = '';
$partialsBasePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

// Inclure les routes nécessaires
include __DIR__ . '/../ressources/routes/gestionUtilisateurRoutes.php';
include __DIR__ . '/../ressources/routes/gestionRhRoutes.php';
include __DIR__ . '/../ressources/routes/gestionDashboardRoutes.php';
include __DIR__ . '/../ressources/routes/dashboardEnseignantRoutes.php';
include __DIR__ . '/../ressources/routes/gestionScolariteRoutes.php';
include __DIR__ . '/../ressources/routes/gestionNotesRoutes.php';
include __DIR__ . '/../ressources/routes/gestionCandidaturesRoutes.php';
include __DIR__ . '/../ressources/routes/listeEtudiantsRoutes.php';
include __DIR__ . '/../ressources/routes/dossierAcademiqueRoutes.php';
include __DIR__ . '/../ressources/routes/verificationRapportsRoutes.php';
include __DIR__ . '/../ressources/routes/gestionReclamationsScolariteRoutes.php';
include __DIR__ . '/../ressources/routes/evaluationDossiersRoutes.php';
include __DIR__ . '/../ressources/routes/gestionDossiersCandidaturesRoutes.php';
include __DIR__ . '/../ressources/routes/sauvegardeRestaurationRoutes.php';
include __DIR__ . '/../ressources/routes/notesResultatsRoutes.php';
include __DIR__ . '/../ressources/routes/archivesDossiersSoutenanceRoutes.php';
include __DIR__ . '/../ressources/routes/auditRoutes.php';
include __DIR__ . '/../ressources/routes/redactionCompteRenduRoutes.php';
include __DIR__ . '/../ressources/routes/archivesCompteRenduRoutes.php';
include __DIR__ . '/../ressources/routes/archiveHistoryRoutes.php';

// Mapping des pages vers les fichiers de contenu
switch ($currentMenuSlug) {
    case 'access_denied':
        $contentFile = $partialsBasePath . 'access_denied_content.php';
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
            $contentFile = $partialsBasePath . 'admin_historique.php';
        }
        break;
    default:
        $contentFile = $partialsBasePath . $currentMenuSlug . '_content.php';
        if (!file_exists($contentFile)) {
            $contentFile = '';
        }
        break;
}

// Informations utilisateur
$userName = $_SESSION['nom_utilisateur'] ?? 'Utilisateur';
$userRole = $_SESSION['lib_GU'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheckMaster | <?php echo htmlspecialchars($currentPageLabel ?: 'Application'); ?></title>
    
     <!-- Premium Design System CSS -->
     <link rel="stylesheet" href="css/premium.css">
     
     <!-- Tailwind CSS (for legacy compatibility during migration) -->
     <link rel="stylesheet" href="css/output.css">
     
     <!-- Icons -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     
     <!-- Favicon -->
     <link rel="shortcut icon" href="image/logo_cm_sbg.png" type="image/x-icon">
    
    <!-- External libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/fr.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.12.0/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        /* Override for smooth transition */
        body {
            font-family: var(--font-sans);
        }
        .sidebar-logo {
            height: 56px;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-base-200 font-poppins antialiased">
    <!-- Toast Container -->
    <div id="toast-container"></div>
    
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-72 bg-primary text-white">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-center h-24 px-4">
                    <div class="flex flex-col items-center text-center">
                        <img src="image/logo_cm_sbg.png" alt="Logo CheckMaster" class="sidebar-logo mb-2">
                        <span class="font-bold text-lg tracking-wide">CHECK MASTER</span>
                    </div>
                </div>
                
                <!-- Sidebar Navigation -->
                <nav class="flex-grow px-4 py-4 overflow-y-auto">
                    <div class="space-y-3 pb-3">
                        <?php
                        // Render menu using existing MenuView for compatibility
                        include __DIR__ . '/menu.php';
                        $menuView = new MenuView();
                        echo $menuView->afficherMenuHierarchique($menuHierarchique, $currentMenuSlug);
                        ?>
                    </div>
                </nav>
                
                <!-- Sidebar Footer -->
                <div class="px-4 py-3 border-t border-white/10">
                    <form action="index.php?_path=/logout" method="POST" id="logoutForm" class="w-full">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        <button type="submit" class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="text-sm">Déconnexion</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Navbar -->
            <header class="navbar bg-white border-b border-gray-200">
                <div class="flex items-center gap-4">
                    <!-- Mobile Menu Button -->
                    <button type="button" id="mobileMenuButton" class="md:hidden btn btn-ghost">
                        <i class="fas fa-bars text-xl text-primary"></i>
                    </button>
                    
                    <!-- Page Title -->
                    <div>
                        <?php echo renderAutoBreadcrumb(); ?>
                        <h1 class="text-2xl font-bold text-primary">
                            <?php echo htmlspecialchars($currentPageLabel ?: 'Dashboard'); ?>
                        </h1>
                    </div>
                </div>
                
                <!-- Navbar Actions -->
                <div class="flex items-center gap-6">
                    <!-- Notifications -->
                    <button type="button" class="btn btn-ghost relative">
                        <i class="fas fa-bell text-primary/70 text-xl"></i>
                    </button>
                    
                    <div class="w-px h-10 bg-gray-200"></div>
                    
                    <!-- User Info -->
                    <div class="flex items-center gap-4">
                        <div class="text-right hidden md:block">
                            <span class="text-md font-bold text-primary block">
                                Bienvenue, <?php echo htmlspecialchars($userName); ?>
                            </span>
                            <span class="text-sm text-primary/60 block">
                                <?php echo htmlspecialchars($userRole); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                <?php
                // Flash messages
                echo renderFlashMessages();
                
                // Error messages from permissions
                if (isset($_SESSION['error_message']) && isset($_SESSION['error_type']) && $currentMenuSlug !== 'access_denied') {
                    echo renderAlert($_SESSION['error_message'], 'danger', 'Accès refusé');
                    unset($_SESSION['error_message']);
                    unset($_SESSION['error_type']);
                }
                
                // Load content file
                if (!empty($contentFile) && file_exists($contentFile)) {
                    include $contentFile;
                } else {
                    echo renderEmptyState(
                        'Page non trouvée',
                        'fa-exclamation-triangle',
                        renderButtonLink('Retour au dashboard', '?page=dashboard', 'primary')
                    );
                }
                ?>
            </main>
        </div>
    </div>
    
    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" onclick="toggleMobileSidebar()"></div>
    
    <!-- Premium JS -->
    <script src="js/premium.js"></script>
    
    <!-- Legacy JS files for compatibility -->
    <script src="./js/suivi_reclamation.js"></script>
    <script src="./js/historique_reclamation.js"></script>
    
    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const sidebar = document.querySelector('.hidden.md\\:flex.md\\:flex-shrink-0');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (mobileMenuButton && sidebar) {
                mobileMenuButton.addEventListener('click', function() {
                    sidebar.classList.toggle('hidden');
                    sidebar.classList.toggle('absolute');
                    sidebar.classList.toggle('z-50');
                    sidebar.classList.toggle('h-full');
                    overlay.classList.toggle('hidden');
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.add('hidden');
                    sidebar.classList.remove('absolute', 'z-50', 'h-full');
                    overlay.classList.add('hidden');
                });
            }
        });
        
        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.hidden.md\\:flex.md\\:flex-shrink-0');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.add('hidden');
            sidebar.classList.remove('absolute', 'z-50', 'h-full');
            overlay.classList.add('hidden');
        }
    </script>
</body>
</html>

<?php
/**
 * Injecte un champ CSRF dans les formulaires POST du legacy.
 */
function injectCsrfIntoPostForms(string $html): string
{
    $token = htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8');
    $field = '<input type="hidden" name="csrf_token" value="' . $token . '">';

    return preg_replace(
        '/(<form\\b[^>]*\\bmethod\\s*=\\s*(?:\"|\\\')?post(?:\"|\\\')?[^>]*>)/i',
        '$1' . $field,
        $html
    ) ?? $html;
}

$__out = ob_get_clean();
echo injectCsrfIntoPostForms($__out);
?>
