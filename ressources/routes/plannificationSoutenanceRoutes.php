<?php
// Routes pour la planification des soutenances

if (isset($_GET['page']) && in_array($_GET['page'], ['planification_soutenance', 'plannification_soutenance'], true)) {
    $action = $_GET['action'] ?? '';
    if ($action === '') {
        return;
    }

    require_once __DIR__ . '/../../app/controllers/PlanificationSoutenanceController.php';
    $controller = new PlanificationSoutenanceController();

    switch ($action) {
        case 'planifierSoutenance':
            $controller->planifierSoutenance();
            break;

        case 'supprimerPlanification':
            $controller->supprimerPlanification();
            break;

        case 'getPlanification':
            $controller->getPlanification();
            break;

        default:
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Action non reconnue'
            ]);
            break;
    }
}
