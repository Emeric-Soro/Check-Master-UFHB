<?php


require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/GestionUtilisateurController.php';

$controller = new GestionUtilisateurController();

$viewData = [];

if (isset($_GET['page']) && ( $_GET['page'] === 'gestion_utilisateurs' || $_GET['page'] === 'profil' )) {

    $viewData = $controller->index(); 
}