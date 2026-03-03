<?php
declare(strict_types=1);

// Front controller "application" (Router).

// Composer autoload (optionnel). Si vendor/ n'est pas installé, on continue.
$composerAutoload = __DIR__ . '/../../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}
require_once __DIR__ . '/../../app/Core/Autoload.php';

use CheckMaster\Core\Request;
use CheckMaster\Core\Response;
use CheckMaster\Core\Router;
use CheckMaster\Core\Session;
use CheckMaster\Core\Csrf;
use CheckMaster\Core\Bootstrap;

Bootstrap::init();
Session::start();

// [INJECTED_LOGGER]
register_shutdown_function(function() {
    $files = get_included_files();
    $logFile = __DIR__ . '/../../../views_used.log';
    if (!file_exists($logFile)) {
        touch($logFile);
        chmod($logFile, 0777);
    }
    $usedViews = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($usedViews === false) $usedViews = [];
    
    $updated = false;
    foreach ($files as $f) {
        $f = str_replace(DIRECTORY_SEPARATOR, '/', $f);
        if (strpos($f, 'ressources/views') !== false) {
            if (!in_array($f, $usedViews)) {
                $usedViews[] = $f;
                $updated = true;
            }
        }
    }
    
    if ($updated) {
        file_put_contents($logFile, implode("\n", $usedViews) . "\n");
    }
});
// [/INJECTED_LOGGER]



$router = new Router();

/**
 * Page 403 “jolie” pour le nouveau routeur.
 */
function renderAccessDenied(string $message = ''): Response
{
    $libGU = htmlspecialchars((string)($_SESSION['lib_GU'] ?? ''), ENT_QUOTES, 'UTF-8');
    $msg = $message !== '' ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : '';

    ob_start();
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Accès refusé</title>
        <link rel="stylesheet" href="../css/output.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body style="background-color:#DFF2FF;" class="font-poppins">
        <div class="min-h-screen flex items-center justify-center p-6">
            <div class="w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-white/40 overflow-hidden">
                <div class="p-8 sm:p-10">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 rounded-2xl bg-red-50 flex items-center justify-center mb-5">
                            <i class="fas fa-lock text-red-500 text-3xl"></i>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-slate-900">Accès refusé</h1>
                        <p class="mt-2 text-slate-600">
                            Vous n'avez pas les permissions nécessaires pour accéder à cette page.
                        </p>
                        <?php if ($libGU !== ''): ?>
                            <p class="mt-2 text-sm text-slate-500">
                                Rôle actuel: <span class="font-semibold text-slate-700"><?php echo $libGU; ?></span>
                            </p>
                        <?php endif; ?>

                        <?php if ($msg !== ''): ?>
                            <div class="mt-6 w-full rounded-xl border border-red-200 bg-red-50 p-4 text-left">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-circle-exclamation text-red-500 mt-1"></i>
                                    <div>
                                        <div class="font-semibold text-red-800">Détail</div>
                                        <div class="text-sm text-red-700"><?php echo $msg; ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-8 flex flex-col sm:flex-row gap-3">
                            <a href="index.php?_path=/dashboard"
                               class="px-5 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary-light transition">
                                <i class="fas fa-home mr-2"></i>Aller au dashboard
                            </a>
                            <a href="layout.php"
                               class="px-5 py-3 rounded-xl bg-slate-100 text-slate-800 font-semibold hover:bg-slate-200 transition">
                                <i class="fas fa-layer-group mr-2"></i>Ouvrir l’application (legacy)
                            </a>
                            <button onclick="window.history.back()"
                                    class="px-5 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 font-semibold hover:bg-slate-50 transition">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </button>
                        </div>

                        <p class="mt-8 text-xs text-slate-500">
                            Si vous pensez que c'est une erreur, contactez l'administrateur pour activer les permissions.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return new Response((string) ob_get_clean(), 403, ['Content-Type' => 'text/html; charset=UTF-8']);
}

// ---- Auth routes ----
$router->get('/login', function (): Response {
    if (isset($_SESSION['id_utilisateur'])) {
        return Response::redirect('index.php?_path=/app');
    }
    ob_start();
    require __DIR__ . '/page_connexion.php';
    return new Response((string) ob_get_clean());
});

$router->post('/login', function (Request $req): Response {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/AuthController.php';

    if (!Csrf::validate($req->post['csrf_token'] ?? null)) {
        $_SESSION['error'] = 'Session expirée. Veuillez réessayer.';
        return Response::redirect('index.php?_path=/login');
    }

    $login = (string)($req->post['login'] ?? '');
    $password = (string)($req->post['password'] ?? '');

    $auth = new \AuthController(\Database::getConnection());
    if ($auth->login($login, $password)) {
        return Response::redirect('index.php?_path=/app');
    }

    $_SESSION['error'] = 'Login ou mot de passe incorrect';
    return Response::redirect('index.php?_path=/login');
});

$router->post('/logout', function (Request $req): Response {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/AuthController.php';

    if (!Csrf::validate($req->post['csrf_token'] ?? null)) {
        $_SESSION['error'] = 'Session expirée. Veuillez réessayer.';
        return Response::redirect('index.php?_path=/app');
    }

    $auth = new \AuthController(\Database::getConnection());
    $auth->logout();
    return Response::redirect('index.php?_path=/login');
});

// ---- Premier écran migré (démo) ----
$router->get('/dashboard', function (): Response {
    if (!isset($_SESSION['id_utilisateur'])) {
        return Response::redirect('index.php?_path=/login');
    }

    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/MenuController.php';
    require_once __DIR__ . '/../../app/Security/RoutePermissionService.php';

    $idGU = (int)($_SESSION['id_GU'] ?? 0);
    if ($idGU > 0) {
        $permService = new \CheckMaster\Security\RoutePermissionService(\Database::getConnection());
        $fakeGet = ['page' => 'dashboard'];
        if (!$permService->canAccessLegacy($idGU, $fakeGet, [], 'GET')) {
            return renderAccessDenied("Vous n'avez pas le droit de voir le dashboard.");
        }
    } else {
        return renderAccessDenied("Vous n'avez pas le droit de voir le dashboard.");
    }

    $menuController = new \MenuController();
    $menu = $menuController->genererMenuHierarchique($idGU);

    ob_start();
    $userName = htmlspecialchars((string)($_SESSION['nom_utilisateur'] ?? ''), ENT_QUOTES, 'UTF-8');
    $csrf = htmlspecialchars(\CheckMaster\Core\Csrf::token(), ENT_QUOTES, 'UTF-8');
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard (nouveau)</title>
        <link rel="stylesheet" href="../css/output.css">
    </head>
    <body style="background-color:#DFF2FF;" class="font-poppins">
        <div class="max-w-5xl mx-auto p-6">
            <div class="bg-white rounded-xl p-6 shadow">
                <h1 class="text-2xl font-bold text-primary">Dashboard (nouvelle architecture)</h1>
                <p class="mt-2 text-gray-600">Connecté: <strong><?php echo $userName; ?></strong></p>

                <div class="mt-6 flex gap-3">
                    <a class="px-4 py-2 rounded bg-primary text-white" href="layout.php">Ouvrir l’application (legacy)</a>
                    <form method="POST" action="index.php?_path=/logout">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <button class="px-4 py-2 rounded bg-gray-200" type="submit">Déconnexion</button>
                    </form>
                </div>

                <h2 class="mt-8 text-lg font-semibold">Menu autorisé (depuis DB)</h2>
                <div class="mt-3 space-y-3">
                    <?php foreach ($menu as $item): ?>
                        <div class="border rounded-lg p-4">
                            <div class="font-semibold"><?php echo htmlspecialchars($item['categorie']->lib_categorie ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            <ul class="list-disc ml-6 mt-2">
                                <?php foreach ($item['fonctionnalites'] as $f): ?>
                                    <li>
                                        <a class="text-primary underline" href="<?php echo htmlspecialchars($f->url_fonctionnalite ?? '#', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($f->label_fonctionnalite ?? $f->lib_fonctionnalite ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return new Response((string) ob_get_clean());
});

// ---- Routes admin (URL canonique) ----
$router->get('/admin/permissions', function (): Response {
    if (!isset($_SESSION['id_utilisateur'])) {
        return Response::redirect('index.php?_path=/login');
    }
    return Response::redirect('layout.php?page=parametres_generaux&action=gestion_attribution&_r=1');
});

$router->get('/admin/backups', function (): Response {
    if (!isset($_SESSION['id_utilisateur'])) {
        return Response::redirect('index.php?_path=/login');
    }
    return Response::redirect('layout.php?page=sauvegarde_restauration&_r=1');
});

$router->get('/admin/users', function (): Response {
    if (!isset($_SESSION['id_utilisateur'])) {
        return Response::redirect('index.php?_path=/login');
    }
    return Response::redirect('layout.php?page=gestion_utilisateurs&_r=1');
});

$router->get('/access-denied', function (): Response {
    return renderAccessDenied($_SESSION['error_message'] ?? '');
});

// Accueil "application"
$router->get('/app', function (): Response {
    return Response::redirect('layout.php');
});

// Racine du router -> site vitrine
$router->get('/', function (): Response {
    return Response::redirect('../site/index.php');
});

$req = Request::fromGlobals();
$router->dispatch($req)->send();

