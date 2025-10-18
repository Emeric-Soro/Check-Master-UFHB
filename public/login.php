<?php
session_start();
require_once __DIR__.'/../app/config/database.php';
require_once __DIR__.'/../app/controllers/AuthController.php';
require_once __DIR__.'/../app/controllers/MenuController.php';
require_once __DIR__.'/../app/utils/CSRFProtection.php';

// Vérifier le jeton CSRF
CSRFProtection::verifyRequest();

$authController = new AuthController(Database::getConnection());
if ($authController->login($_POST['login'], $_POST['password'])) {
    // Régénérer le jeton CSRF après une connexion réussie
    CSRFProtection::regenerateToken();
    
    // Récupérer les traitements autorisés pour l'utilisateur
    $menuController = new MenuController();
    $traitements = $menuController->genererMenu($_SESSION['id_GU']);

    // Utiliser le premier traitement comme page par défaut
    $defaultPage = !empty($traitements) ? $traitements[0]['lib_traitement'] : 'dashboard';

    header('Location: layout.php?page=' . urlencode($defaultPage));
    exit;
} else {
    $_SESSION['error'] = 'Login ou mot de passe incorrect';
    header('Location: page_connexion.php');
    exit;
}