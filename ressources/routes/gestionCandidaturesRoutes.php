<?php

if (isset($_GET['page']) && in_array($_GET['page'], ['gestion_candidatures', 'gestion_candidatures_soutenance'], true)) {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/GestionCandidaturesController.php';

    if (($_GET['action'] ?? '') === 'evaluations_m2_s1') {
        require_once __DIR__ . '/../../app/controllers/GestionCandidaturesEvaluationsController.php';
        $evaluationsController = new GestionCandidaturesEvaluationsController();
        $evaluationsController->index();
    } else {
        $controller = new GestionCandidaturesController();
        if (isset($_GET['examiner']) || isset($_GET['action'])) {
            $controller->examinerCandidature();
        } else {
            $controller->index();
        }
    }
}
