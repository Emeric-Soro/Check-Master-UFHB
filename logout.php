<?php

session_start();
require_once __DIR__.'/../app/config/database.php';
require_once __DIR__.'/../app/controllers/AuthController.php';

// Vérifier le token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $authController = new AuthController(Database::getConnection());
    if ($authController->logout()) {
        header('Location: page_connexion.php'); // Rediriger vers la page appropriée
        exit;
    } else {
        $_SESSION['error'] = 'Erreur lors de la déconnexion';
        header('Location: layout.php');
        exit;
    }
        
    
}