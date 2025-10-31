<?php

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/GestionCandidaturesController.php';

$controller = new GestionCandidaturesController();

$viewData = [];

// Gérer l'examen d'une candidature si les paramètres sont présents
if (isset($_GET['examiner']) || isset($_GET['action'])) {
    $viewData = $controller->examinerCandidature();
} else {
    $viewData = $controller->index();
}