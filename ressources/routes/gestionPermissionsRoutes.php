<?php

/**
 * Routes pour la gestion des permissions
 */

if (isset($_GET['page']) && $_GET['page'] === 'parametres_generaux' 
    && isset($_GET['action']) && $_GET['action'] === 'gestion_permissions') {
    
    require_once __DIR__ . '/../../app/controllers/GestionPermissionsController.php';
    $controller = new GestionPermissionsController();

    // Gestion des sous-actions
    $subAction = $_GET['subaction'] ?? '';

    switch ($subAction) {
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->updatePermissions();
            }
            break;

        case 'get_matrice':
            $controller->getMatriceAjax();
            break;

        case 'copy':
            $controller->copyPermissions();
            break;

        case 'init_default':
            $controller->initDefaultPermissions();
            break;

        case 'stats':
            $controller->getStatistiques();
            break;

        case 'toggle_row':
            $controller->toggleRowPermissions();
            break;

        case 'toggle_column':
            $controller->toggleColumnPermissions();
            break;

        default:
            // Action par défaut : afficher la page
            $controller->index();
            break;
    }
}