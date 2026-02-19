<?php

// L'étudiant doit d'abord remplir ses informations de stage avant de créer un rapport
// Le workflow est : 1. Infos de stage → 2. Rédaction du rapport → 3. Dépôt

if ($_GET['page'] === 'gestion_rapports') {
    require_once __DIR__ . '/../../app/controllers/GestionRapportController.php';
    require_once __DIR__ . '/../../app/models/InfoStage.php';
    require_once __DIR__ . '/../../app/config/database.php';

    $controller = new GestionRapportController();

    // Vérifier si l'étudiant tente de créer ou modifier un rapport
    $needsStageInfo = isset($_GET['action']) && in_array($_GET['action'], ['creer_rapport', 'suivi_rapport', 'commentaire_rapport']);

    // Si l'action nécessite les infos de stage, vérifier qu'elles existent
    if ($needsStageInfo && isset($_SESSION['num_etu'])) {
        $db = Database::getConnection();
        $infoStageModel = new InfoStage($db);
        $stage_info = $infoStageModel->getStageInfo($_SESSION['num_etu']);

        // Si pas d'infos de stage, rediriger vers la page de candidature
        if (!$stage_info) {
            $_SESSION['error'] = "Vous devez d'abord remplir vos informations de stage avant de créer votre rapport.";
            $_SESSION['error_type'] = 'info_required';
            header('Location: layout.php?page=candidature_soutenance');
            exit;
        }
    }

    // Gestion du POST pour le dépôt de rapport
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deposer_rapport') {
        $controller->traiterCreationRapport();
        exit;
    }

    // Gestion du POST pour la suppression de rapport
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer_rapport') {
        $controller->supprimer_rapport();
        exit;
    }

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'creer_rapport':
                $controller->creerRapport();
                break;
            case 'suivi_rapport':
                $num_etu = $_SESSION['num_etu'];
                $controller->suiviRapport($num_etu);
                break;
            case 'commentaire_rapport':
                $controller->commentaireRapport();
                break;
            case 'supprimer_rapport':
                $controller->supprimer_rapport();
                break;
            case 'get_rapport':
                $controller->getRapportAjax();
                break;
            case 'get_commentaires':
                $controller->getCommentairesAjax();
                break;
            case 'exporter_rapports':
                $controller->exporterRapports();
                break;
            default:
                // Action non reconnue, rediriger vers le dashboard
                header('Location: ?page=gestion_rapports');
                exit;
        }
    } else {
        // Pas d'action spécifiée, afficher le dashboard
        $controller->index();
    }
}