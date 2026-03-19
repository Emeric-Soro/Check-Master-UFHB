<?php
if (isset($_GET['page']) && $_GET['page'] === 'gestion_dossiers_candidatures') {
    // Démarrer la capture d'output pour éviter les problèmes de headers
    ob_start();
    
    require_once __DIR__ . '/../../app/controllers/GestionDossiersCandidaturesController.php';
    $controller = new GestionDossiersCandidaturesController();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $result = null;

        if (isset($_POST['valider']) && isset($_POST['id_rapport'])) {
            $result = $controller->validerRapport();
        } elseif (isset($_POST['rejeter']) && isset($_POST['id_rapport'])) {
            $result = $controller->rejeterRapport();
        }

        if (is_array($result)) {
            $_SESSION['message'] = (string) ($result['message'] ?? '');
            $_SESSION['message_type'] = !empty($result['success']) ? 'success' : 'error';
            header('Location: ?page=gestion_dossiers_candidatures');
            exit;
        }
    }
    
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
    $controller->index();
    
    // Si on arrive ici, c'est l'affichage normal de la page
    ob_end_flush();
} 
