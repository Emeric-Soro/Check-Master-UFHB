<?php

if (isset($_GET['page']) && in_array($_GET['page'], ['gestion_candidatures', 'gestion_candidatures_soutenance'], true)) {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/GestionCandidaturesController.php';

    $controller = new GestionCandidaturesController();

    if (isset($_GET['examiner']) || isset($_GET['action'])) {
        $controller->examinerCandidature();
    } else {
        $controller->index();
    }
}
