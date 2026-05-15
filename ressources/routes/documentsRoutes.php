<?php
/**
 * Route pour la page bibliotheque de documents.
 * Route : ?page=documents
 */
if (isset($_GET['page']) && $_GET['page'] === 'documents') {

    require_once __DIR__ . '/../../app/controllers/DocumentsController.php';

    $controller = new DocumentsController();
    $data = $controller->index();
    $pageTitle = 'Mes documents';
    $contentFile = __DIR__ . '/../views/documents_content.php';
}
