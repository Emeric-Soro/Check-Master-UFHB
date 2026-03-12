<?php
// Routes pour la programmation de soutenance

if (isset($_GET['page']) && in_array($_GET['page'], ['programmation_soutenance', 'programation_soutenance'], true)) {
    $action = $_GET['action'] ?? '';
    if ($action === '') {
        return;
    }

    require_once __DIR__ . '/../../app/controllers/ProgrammationSoutenanceController.php';
    $controller = new ProgrammationSoutenanceController();

    switch ($action) {
        case 'getEtudiants':
            $controller->getEtudiants();
            break;

        case 'getEnseignants':
            $controller->getEnseignants();
            break;

        case 'getProfesseursTitulaires':
            $controller->getProfesseursTitulaires();
            break;
        case 'getSalles':
            $controller->getSalles();
            break;

        case 'getAttributions':
            $controller->getAttributions();
            break;

        case 'createAttribution':
            $controller->createAttribution();
            break;

        case 'updateAttribution':
            $controller->updateAttribution();
            break;

        case 'deleteAttribution':
            $controller->deleteAttribution();
            break;

        case 'getPlanningPreview':
            $controller->getPlanningPreview();
            break;

        case 'generatePlanningPdf':
            $controller->generatePlanningPdf();
            break;

        case 'getDayDetails':
            $controller->getDayDetails();
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
