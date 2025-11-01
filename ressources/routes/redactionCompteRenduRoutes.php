<?php

require_once __DIR__ . '/../../app/controllers/RedactionCompteRenduController.php';

if (isset($_GET['page']) && $_GET['page'] === 'redaction_compte_rendu') {
    $controller = new RedactionCompteRenduController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_GET['action']) && $_GET['action'] === 'export_pdf') {
            $controller->exporterPDF();
        } else {
            $controller->enregistrer();
        }
    } else {
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'load_template_html':
                    $controller->loadTemplateHtml();
                    break;
                default:
                    $controller->index();
                    break;
            }
        } else {
            $controller->index();
        }
    }
} 