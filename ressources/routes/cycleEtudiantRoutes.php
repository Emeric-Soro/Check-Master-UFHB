<?php
/**
 * Route file pour page=cycle_etudiant
 * Interface wizard du Parcours Etudiant Complet (admin uniquement)
 */

if (isset($_GET['page']) && $_GET['page'] === 'cycle_etudiant') {
    require_once __DIR__ . '/../../app/controllers/CycleEtudiantController.php';
    $controller = new CycleEtudiantController();

    // Actions AJAX JSON
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'search':
                $controller->searchEtudiants();
                exit;

            case 'progression':
                $controller->getProgression();
                exit;

            case 'module':
                $controller->getModule();
                exit;

            case 'show':
                $controller->show();
                break;

            case 'save':
                $controller->saveModule();
                exit;
        }
    } else {
        // Page principale (index)
        $controller->index();
    }
}
