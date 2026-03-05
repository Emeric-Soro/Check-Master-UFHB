<?php

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/ArchiveController.php';
require_once __DIR__ . '/../../app/controllers/ArchiveHubController.php';
require_once __DIR__ . '/../../app/controllers/ArchivesCompteRenduController.php';

$controller = new ArchiveController();

if (isset($_GET['page']) && $_GET['page'] === 'admin_historique') {
    $action = $_GET['action'] ?? '';

    if (in_array($action, ['view_student', 'update_student', 'import', 'import_result', 'export', 'changeYear'], true)) {
        switch ($action) {
            case 'view_student':
                $controller->viewStudentFile();
                break;

            case 'update_student':
                $controller->updateStudentFile();
                break;

            case 'import':
                $controller->importArchive();
                break;

            case 'import_result':
                $controller->showImportResult();
                break;

            case 'export':
                $controller->exportHistory();
                break;

            case 'changeYear':
                $hubController = new ArchiveHubController();
                $hubController->changeYear();
                break;
        }
    }
}

// Route pour archives des comptes rendus
if (isset($_GET['page']) && $_GET['page'] === 'archives_compte_rendu') {
    $crController = new ArchivesCompteRenduController();
    $action = $_GET['action'] ?? 'index';
    
    switch ($action) {
        case 'view':
            $crController->viewArchive();
            break;
            
        default:
            $crController->index();
            break;
    }
}
