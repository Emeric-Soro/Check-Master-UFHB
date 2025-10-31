<?php

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/DashboardEnseignantController.php';

$controller = new DashboardEnseignantController();

$viewData = [];

if (isset($_GET['page']) && $_GET['page'] === 'dashboard_enseignant') {
    $viewData = $controller->index();
}



?>