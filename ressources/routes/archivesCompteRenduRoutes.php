<?php

require_once __DIR__ . '/../../app/controllers/ArchivesCompteRenduController.php';

if (!isset($GLOBALS['skip_legacy_routing']) && isset($_GET['page']) && $_GET['page'] === 'archive_comptes_rendus') {
    $controller = new ArchivesCompteRenduController();
    
    // Action pour consulter une archive spécifique
    if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
        $controller->viewArchive();
    }
    // Action pour supprimer une archive
    elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->deleteArchive();
    }
    // Action pour exporter les archives
    elseif (isset($_GET['action']) && $_GET['action'] === 'export') {
        $controller->exportArchives();
    }
    // Action pour rechercher les archives (AJAX)
    elseif (isset($_GET['action']) && $_GET['action'] === 'search') {
        $controller->searchArchives();
    }
    // Action pour télécharger un PDF
    elseif (isset($_GET['action']) && $_GET['action'] === 'download_pdf' && isset($_GET['chemin'])) {
        $controller->telechargerPDF($_GET['chemin']);
        exit;
    }
    // Action par défaut : afficher la liste des archives
    else {
        $controller->index();
    }
} 