<?php
if ($_GET['page'] === 'gestion_etudiants') {
    $debugLogPath = __DIR__ . '/../../logs/gestion_etudiants.log';
    $fallbackLogPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'gestion_etudiants.log';
    $routeLog = date('c') . ' [gestion_etudiants:route] entry ' . json_encode([
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'page' => $_GET['page'] ?? null,
        'action' => $_GET['action'] ?? null,
        'modalAction' => $_GET['modalAction'] ?? null,
        'get_keys' => array_keys($_GET),
        'post_keys' => array_keys($_POST),
    ]);
    @file_put_contents($debugLogPath, $routeLog . PHP_EOL, FILE_APPEND);
    @file_put_contents($fallbackLogPath, $routeLog . PHP_EOL, FILE_APPEND);
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
