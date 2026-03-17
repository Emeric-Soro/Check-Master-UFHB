<?php
require_once __DIR__ . '/../../app/controllers/CriteresEvaluationController.php';

$page = (string) ($_GET['page'] ?? '');
$action = (string) ($_GET['action'] ?? '');

$isLegacyCriteriaPage = ($page === 'criteres_evaluation');
$isParametresCriteriaPage = ($page === 'parametres_specifiques' && $action === 'criteres_evaluation');

if ($isLegacyCriteriaPage || $isParametresCriteriaPage) {
    $controller = new CriteresEvaluationController();
    $ajaxAction = '';

    if ($isParametresCriteriaPage) {
        $ajaxAction = (string) ($_GET['ajaxAction'] ?? '');
    } elseif (in_array($action, ['getAnnees', 'getCriteres', 'createCritere', 'updateCritere', 'deleteCritere'], true)) {
        $ajaxAction = $action;
    }

    if ($ajaxAction !== '') {
        switch ($ajaxAction) {
            case 'getAnnees':
                $controller->getAnneesAcademiques();
                exit;
            case 'getCriteres':
                $controller->getCriteres();
                exit;
            case 'createCritere':
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                    http_response_code(405);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                    exit;
                }
                $controller->createCritere();
                exit;
            case 'updateCritere':
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                    http_response_code(405);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                    exit;
                }
                $controller->updateCritere();
                exit;
            case 'deleteCritere':
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                    http_response_code(405);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
                    exit;
                }
                $controller->deleteCritere();
                exit;
            default:
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Action inconnue']);
                exit;
        }
    }

    // Action par défaut : afficher la page
    $controller->index();
}

// Route de test pour les critères d'évaluation
if (isset($_GET['page']) && $_GET['page'] === 'test_criteres') {
    // Pas besoin de contrôleur, juste inclure la vue de test
}
?>
