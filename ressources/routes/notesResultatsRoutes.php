<?php


// Gérer l'examen d'une candidature si les paramètres sont présents
if (isset($_GET['page']) && $_GET['page'] === 'notes_resultats') {

    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/NotesResultatsController.php';
    $controller = new NotesResultatsController();
    $viewData = $controller->index();

}

// Route pour l'export PDF du relevé de notes
if (isset($_GET['action']) && $_GET['action'] === 'export_pdf') {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/NotesResultatsController.php';
    $controller = new NotesResultatsController();
    $controller->exportPdf();
    exit;
}