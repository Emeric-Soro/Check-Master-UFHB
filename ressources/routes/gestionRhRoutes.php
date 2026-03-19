<?php


require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/GestionRhController.php';


if (isset($_GET['page']) && in_array($_GET['page'], ['gestion_rh', 'maj_enseignant', 'maj_personnel_admin'], true)) {

    $controller = new GestionRhController();

    if ($_GET['page'] === 'maj_enseignant' && !isset($_GET['tab'])) {
        $_GET['tab'] = 'enseignant';
    }
    if ($_GET['page'] === 'maj_personnel_admin' && !isset($_GET['tab'])) {
        $_GET['tab'] = 'pers_admin';
    }
    
    $controller->index();
        
    
}
