<?php

// Endpoint legacy: délègue vers le nouveau Router.
$_GET['_path'] = $_GET['_path'] ?? '/logout';
require __DIR__ . '/index.php';
exit;

require_once __DIR__.'/../app/config/database.php';
require_once __DIR__.'/../app/controllers/AuthController.php';

use CheckMaster\Core\Csrf;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    $csrfOk = Csrf::validate($_POST['csrf_token'] ?? null);
    if (!$csrfOk) {
        $_SESSION['error'] = 'Session expirée. Veuillez réessayer.';
        header('Location: layout.php');
        exit;
    }
    
    $authController = new AuthController(Database::getConnection());
    if ($authController->logout()) {
        header('Location: page_connexion.php'); // Rediriger vers la page appropriée
        exit;
    } else {
        $_SESSION['error'] = 'Erreur lors de la déconnexion';
        header('Location: layout.php');
        exit;
    }
        
    
} else {
    header('Location: layout.php');
    exit;
}