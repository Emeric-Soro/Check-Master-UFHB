<?php
if ($_GET['page'] === 'gestion_etudiants') {
    require_once __DIR__ . '/../../app/controllers/GestionEtudiantController.php';
    $controller = new GestionEtudiantController();

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'ajouter_des_etudiants':
            case 'importer_etudiants':
                $controller->index();
                break;
            case 'inscrire_des_etudiants':
                require_once __DIR__ . '/../../app/controllers/InscriptionController.php';
                $inscriptionController = new InscriptionController();
                $inscriptionController->index();
                break;
            default:
                // Rediriger vers la page par défaut si l'action n'est pas valide
                header('Location: ?page=gestion_etudiants');
                exit;
        }
    } else {
        // Si aucune action n'est spécifiée, afficher la page par défaut
        $controller->index();
    }
}
