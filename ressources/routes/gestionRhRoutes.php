<?php


require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/GestionRhController.php';


if (isset($_GET['page']) && $_GET['page'] === 'gestion_rh') {

    $controller = new GestionRhController();
    
    $viewData = $controller->index();
        
    
}