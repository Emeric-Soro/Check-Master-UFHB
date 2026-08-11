<?php


require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/NotesController.php';
require_once __DIR__ . '/../../app/controllers/EvaluationS3Controller.php';
require_once __DIR__ . '/../../app/controllers/UeReferentielController.php';

$controller = new NotesController();
$evaluationS3Controller = new EvaluationS3Controller();
$ueReferentielController = new UeReferentielController();

if (isset($_GET['page']) && ( $_GET['page'] === 'gestion_notes_evaluations')) {
    if (($_GET['tab'] ?? '') === 'evaluations_s3') {
        $evaluationS3Controller->index();
    } elseif (($_GET['tab'] ?? '') === 'ue') {
        $ueReferentielController->index();
    } else {
        $controller->index();
    }
}
