<?php
/**
 * Routes pour l'écran 1.3.2: Réclamation (Suivi et Traitement)
 * AdminReclamationController
 */

require_once __DIR__ . '/../../app/controllers/AdminReclamationController.php';

$controller = new AdminReclamationController();

if (isset($_GET['page']) && ($_GET['page'] === 'admin_reclamations' || $_GET['page'] === 'gestion_reclamations_scolarite')) {
    $action = $_GET['action'] ?? 'index';
    
    switch ($action) {
        case 'detail':
            $controller->detail();
            break;
            
        case 'prendre_en_charge':
            $controller->prendreEnCharge();
            break;
            
        case 'terminer':
            $controller->terminer();
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
