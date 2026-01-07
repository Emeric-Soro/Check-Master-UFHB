<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/MenuController.php';


$authController = new AuthController(Database::getConnection());
if ($authController->login($_POST['login'], $_POST['password'])) {
    // Récupérer le menu hiérarchique pour redirection
    $menuController = new MenuController();
    $menuHierarchique = $menuController->genererMenuHierarchique($_SESSION['id_GU']);

    // Extraire la première page du menu hiérarchique
    $defaultPage = 'dashboard'; // Fallback
    if (!empty($menuHierarchique) && !empty($menuHierarchique[0]['fonctionnalites'])) {
        $firstFonc = $menuHierarchique[0]['fonctionnalites'][0];
        parse_str(parse_url($firstFonc->url_fonctionnalite, PHP_URL_QUERY), $params);
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