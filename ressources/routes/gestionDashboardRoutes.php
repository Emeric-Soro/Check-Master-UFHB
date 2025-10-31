<?php


require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/DashboardController.php';
require_once __DIR__ . '/../../app/controllers/DashboardCommissionController.php';

$controller = new DashboardController();
$commissionController = new DashboardCommissionController();

$viewData = [];

if (isset($_GET['page'])) {
    if ($_GET['page'] === 'dashboard') {
        $viewData = $controller->index(); 
    } elseif ($_GET['page'] === 'dashboard_commission') {
        $viewData = $commissionController->index();
    }
}