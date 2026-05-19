<?php
/**
 * Routes pour la Fiche Financière Année
 * Slug page: fiche_financiere_annee
 * Permission: gestion_scolarite
 */

if (isset($_GET['page']) && $_GET['page'] === 'fiche_financiere_annee') {

    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/FicheFinanciereController.php';

    $controller = new FicheFinanciereController();

    // Action AJAX : détail étudiant
    if (isset($_GET['action']) && $_GET['action'] === 'detail_etudiant') {
        $controller->detailEtudiant();
        exit;
    }

    // Action par défaut : vue d'ensemble
    $controller->index();
}
