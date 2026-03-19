<?php
if (isset($_GET['page']) && $_GET['page'] == 'edition_bulletin') {

    require_once __DIR__ . '/../../app/Controllers/EditionBulletinController.php';
    $controller = new EditionBulletinController();

    // Handle AJAX actions
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'preview':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $controller->preview();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                }
                exit;

            case 'generate':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->generate();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                }
                exit;

            case 'generate_batch':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->generate_batch();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                }
                exit;

            case 'publish':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->publish();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                }
                exit;

            case 'download':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $controller->download();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                }
                exit;

            case 'history':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $controller->history();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                }
                exit;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Action inconnue']);
                exit;
        }
    }

    // Default action: show the edition bulletin page
    $controller->index();
}
?>