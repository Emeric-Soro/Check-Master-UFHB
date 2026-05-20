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
        $controller->enregistrerEvaluation();
        break;

    case 'supprimerEvaluation':
        $controller->supprimerEvaluation();
        break;

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

    default:
        // Pas d'action spécifique - le layout va inclure la vue
        break;
}
?>
