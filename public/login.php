<?php
// Endpoint legacy: délègue vers le nouveau Router.
$_GET['_path'] = $_GET['_path'] ?? '/login';
require __DIR__ . '/index.php';
exit;

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/MenuController.php';

use CheckMaster\Core\Csrf;

$csrfOk = Csrf::validate($_POST['csrf_token'] ?? null);
if (!$csrfOk) {
    $_SESSION['error'] = 'Session expirée. Veuillez réessayer.';
    header('Location: page_connexion.php');
    exit;
}

$authController = new AuthController(Database::getConnection());
if ($authController->login($_POST['login'], $_POST['password'])) {
    // Récupérer le menu hiérarchique pour redirection
    $menuController = new MenuController();
    $menuHierarchique = $menuController->genererMenuHierarchique($_SESSION['id_GU']);

    // Extraire la première page du menu hiérarchique
    $defaultPage = 'dashboard'; // Fallback
    if (!empty($menuHierarchique) && !empty($menuHierarchique[0]['fonctionnalites'])) {
        $firstFonc = $menuHierarchique[0]['fonctionnalites'][0];
        $firstFoncUrl = (string) ($firstFonc->url_fonctionnalite ?? '');
        $firstFoncQuery = (string) (parse_url($firstFoncUrl, PHP_URL_QUERY) ?? '');
        parse_str($firstFoncQuery, $params);
        if (isset($params['page'])) {
            $defaultPage = $params['page'];
        }
    }

    header('Location: layout.php?page=' . urlencode($defaultPage));
    exit;
} else {
    $_SESSION['error'] = 'Login ou mot de passe incorrect';
    header('Location: page_connexion.php');
    exit;
}
