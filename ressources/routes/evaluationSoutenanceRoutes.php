<?php
// Routes pour l'évaluation des soutenances

// Inclure le contrôleur
require_once __DIR__ . '/../../app/controllers/EvaluationSoutenanceController.php';

// Créer une instance du contrôleur
$controller = new EvaluationSoutenanceController();

// Récupérer l'action demandée
$action = $_GET['action'] ?? '';

// Router les actions
switch ($action) {
    case 'evaluerSoutenance':
        $result = $controller->enregistrerEvaluation();
        header('Content-Type: application/json; charset=UTF-8');
        if (empty($result['success'])) {
            http_response_code(400);
        }
        echo json_encode($result);
        exit;

    case 'supprimerEvaluation':
        $result = $controller->supprimerEvaluation();
        header('Content-Type: application/json; charset=UTF-8');
        if (empty($result['success'])) {
            http_response_code(400);
        }
        echo json_encode($result);
        exit;

    case 'getEvaluationExistante':
        $numEtu = $_GET['num_etu'] ?? '';
        if ($numEtu) {
            $evaluation = $controller->getEvaluationExistante($numEtu);
            header('Content-Type: application/json');
            echo json_encode($evaluation ?: ['rows' => []]);
            exit;
        }
        break;

    case 'getCriteresParAnnee':
        $controller->getCriteresParAnnee();
        exit;

    case 'imprimer_pv':
        $controller->imprimerPV();
        exit;

    case 'searchSoutenances':
        $controller->searchSoutenances();
        exit;

    default:
        // Pas d'action spécifique - le layout va inclure la vue
        break;
}
?>
