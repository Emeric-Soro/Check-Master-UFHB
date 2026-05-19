<?php

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/FicheEnseignantController.php';

if (isset($_GET['page']) && $_GET['page'] === 'fiche_enseignante') {

    $controller = new FicheEnseignantController();

    $viewMode = (string) ($_GET['view'] ?? 'liste');

    if ($viewMode === 'fiche' && isset($_GET['id']) && $_GET['id'] !== '') {
        $controller->fiche((string) $_GET['id']);
    } else {
        $controller->index();
    }
}
