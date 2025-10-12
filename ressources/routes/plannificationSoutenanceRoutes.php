<?php
// Routes pour la planification des soutenances

// Inclure le contrôleur
require_once __DIR__ . '/../app/controllers/PlanificationSoutenanceController.php';

// Créer une instance du contrôleur
$controller = new PlanificationSoutenanceController();

// Récupérer l'action demandée
$action = $_GET['action'] ?? '';

// Router les actions
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
        // Action par défaut - afficher la page
        include __DIR__ . '/../ressources/views/plannificaiton_soutenance_content.php';
        break;
}
?>