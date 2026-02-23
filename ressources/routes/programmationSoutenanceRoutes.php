<?php
// Routes pour la programmation de soutenance

require_once __DIR__ . '/../../app/controllers/ProgrammationSoutenanceController.php';

$controller = new ProgrammationSoutenanceController();
$action = $_GET['action'] ?? '';

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

    default:
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action non reconnue'
        ]);
        break;
}
?>
