<?php


if (isset($_GET['page']) && $_GET['page'] == 'gestion_scolarite') {

    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/GestionScolariteController.php';

    $controller = new GestionScolariteController();

    // NOUVELLE ROUTE POUR LE REÇU DE VERSEMENT
    if (isset($_GET['action']) && $_GET['action'] === 'imprimer_recu_versement') {
        $controller->imprimerRecuVersement();
        exit; // Important pour arrêter l'exécution
    }

    $controller->index();
}