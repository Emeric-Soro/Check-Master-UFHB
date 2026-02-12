<?php
/**
 * Routes pour l'écran 1.3.1: Candidature (Validation Administrative et Technique)
 * AdminCandidatureController
 */

require_once __DIR__ . '/../../app/controllers/AdminCandidatureController.php';

$controller = new AdminCandidatureController();

if (isset($_GET['page']) && ($_GET['page'] === 'admin_candidatures' || $_GET['page'] === 'gestion_dossiers_candidatures')) {
    $action = $_GET['action'] ?? 'index';
    
    switch ($action) {
        case 'detail':
            $controller->detail();
            break;
            
        case 'valider':
            $controller->valider();
            break;
            
        case 'rejeter':
            $controller->rejeter();
            break;
            
        case 'index':
        default:
            $controller->index();
            break;
    }
}
