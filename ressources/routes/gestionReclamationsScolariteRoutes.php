<?php
require_once __DIR__ . '/../../app/controllers/GestionReclamationsScolariteController.php';

$controller = new GestionReclamationsScolariteController();

$viewData = [];

if (isset($_GET['page']) && $_GET['page'] === 'gestion_reclamations_scolarite') {
    if (isset($_GET['action']) && $_GET['action'] === 'changer_statut') {
        $controller->changerStatut();
    } else {
        $viewData = $controller->index();
    }
}