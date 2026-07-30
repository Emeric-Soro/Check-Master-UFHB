<?php
/**
 * Routes pour le DocViewer unifie.
 * Route : ?page=docviewer&type={TYPE}&id={ID}&action={preview|download}
 */
if (isset($_GET['page']) && $_GET['page'] === 'docviewer') {

    require_once __DIR__ . '/../../app/controllers/DocViewerController.php';

    $controller = new DocViewerController();
    $action = $_GET['action'] ?? 'preview';

    switch ($action) {
        case 'preview':
            $controller->preview();
            break;

        case 'download':
            $controller->download();
            break;

        case 'catalogue_preview':
            $controller->cataloguePreview();
            break;

        default:
            http_response_code(400);
            echo 'Action invalide';
            exit;
    }
}
