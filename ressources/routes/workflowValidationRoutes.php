<?php
// Route pour le Workflow Validation Visuel (P2.4)
if (isset($_GET['page']) && $_GET['page'] === 'workflow_validation') {
    require_once __DIR__ . '/../../app/controllers/ProcessusValidationController.php';
    $controller = new ProcessusValidationController();
    $donnees = $controller->workflowVisuel();

    $workflow = $donnees['workflow'] ?? null;
    $statistiques = $donnees['statistiques'] ?? [];

    // Si pas de workflow, charger la liste des rapports
    if (!$workflow) {
        $rapports = $donnees['rapports'] ?? [];
    }
}
