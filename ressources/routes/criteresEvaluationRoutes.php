<?php
require_once __DIR__ . '/../../app/controllers/CriteresEvaluationController.php';

if (isset($_GET['page']) && $_GET['page'] === 'criteres_evaluation') {
    $controller = new CriteresEvaluationController();

    // Action pour charger les années académiques (AJAX)
    if (isset($_GET['action']) && $_GET['action'] === 'getAnnees') {
        $controller->getAnneesAcademiques();
        exit;
    }
    // Action pour charger les critères (AJAX)
    elseif (isset($_GET['action']) && $_GET['action'] === 'getCriteres') {
        $controller->getCriteres();
        exit;
    }
    // Action pour créer un critère (AJAX)
    elseif (isset($_GET['action']) && $_GET['action'] === 'createCritere' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->createCritere();
        exit;
    }
    // Action pour modifier un critère (AJAX)
    elseif (isset($_GET['action']) && $_GET['action'] === 'updateCritere' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->updateCritere();
        exit;
    }
    // Action pour supprimer un critère (AJAX)
    elseif (isset($_GET['action']) && $_GET['action'] === 'deleteCritere' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->deleteCritere();
        exit;
    }
    // Action par défaut : afficher la page
    else {
        $controller->index();
    }
}

// Route de test pour les critères d'évaluation
if (isset($_GET['page']) && $_GET['page'] === 'test_criteres') {
    // Pas besoin de contrôleur, juste inclure la vue de test
}
?>