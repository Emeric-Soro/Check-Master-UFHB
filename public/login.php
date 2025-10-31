<?php
session_start();
require_once __DIR__.'/../app/config/database.php';
require_once __DIR__.'/../app/controllers/AuthController.php';
require_once __DIR__.'/../app/controllers/MenuController.php';


$authController = new AuthController(Database::getConnection());
if ($authController->login($_POST['login'], $_POST['password'])) {
    // Récupérer les traitements autorisés pour l'utilisateur
    $menuController = new MenuController();
    $traitements = $menuController->genererMenu($_SESSION['id_GU']);

    // Utiliser le premier traitement comme page par défaut
    $defaultPage = !empty($traitements) ? $traitements[0]['lib_traitement'] : 'dashboard';

    header('Location: index.php?page=' . urlencode($defaultPage));
    exit;
} else {
    $_SESSION['error'] = 'Login ou mot de passe incorrect';
    header('Location: page_connexion.php');
    exit;
}