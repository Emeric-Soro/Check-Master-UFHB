<?php
/**
 * Routes pour le Dashboard Direction (KPIs)
 * Slug page: dashboard_direction
 * Permission: dashboard_direction
 */

if (isset($_GET['page']) && $_GET['page'] === 'dashboard_direction') {

    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/DashboardDirectionController.php';

    $controller = new DashboardDirectionController();
    $controller->index();
}
