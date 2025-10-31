<?php
$viewData = [];

if (isset($_GET['page']) && $_GET['page'] === 'gestion_dossiers_candidatures') {
    // Démarrer la capture d'output pour éviter les problèmes de headers
    ob_start();
    
    require_once __DIR__ . '/../../app/controllers/GestionDossiersCandidaturesController.php';
    $controller = new GestionDossiersCandidaturesController();
    
    // Action pour télécharger le rapport en PDF
    if (isset($_GET['action']) && $_GET['action'] === 'telecharger_pdf' && isset($_GET['id_rapport'])) {
        $controller->telechargerPdf($_GET['id_rapport']);
        exit;
    }
    
    // Action pour consulter le rapport
    if (isset($_GET['action']) && $_GET['action'] === 'consulter_rapport' && isset($_GET['id_rapport'])) {
        $controller->consulterRapport($_GET['id_rapport']);
        exit;
    }
    
    // Action par défaut : afficher la liste
    $viewData = $controller->index();
    
    // Si on arrive ici, c'est l'affichage normal de la page
    ob_end_flush();
} 