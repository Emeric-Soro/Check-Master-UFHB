<?php

require_once __DIR__ . '/../../app/controllers/RedactionCompteRenduController.php';

if (isset($_GET['page']) && $_GET['page'] === 'redaction_compte_rendu') {
    $controller = new RedactionCompteRenduController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_GET['action']) && $_GET['action'] === 'export_pdf') {
            $controller->exporterPDF();
            exit; // S'assurer que le script s'arrête après l'export
        } else {
            $controller->enregistrer();
        }
    } else {
        $controller->index();
    }
} 