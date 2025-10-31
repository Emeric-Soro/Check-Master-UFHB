<?php

$viewData = [];

if (isset($_GET['page']) && $_GET['page'] == 'gestion_scolarite') {

    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/GestionScolariteController.php';

    $controller = new GestionScolariteController();


    $viewData = $controller->index();
}