<?php

if (isset($_GET['page']) && in_array($_GET['page'], [
    'liste_etudiants_resp_filiere',
    'liste_etudiants_resp_niveau',
    'liste_etudiants_resp',
    'liste_etudiants_ens',
], true)) {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/GestionEtudiantController.php';

    $controller = new GestionEtudiantController();
    $controller->index();
}
