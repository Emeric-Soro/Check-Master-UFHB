<?php

require_once __DIR__ . '/../../app/models/CandidatureSoutenance.php';
if (isset($_SESSION['num_etu'])) {
    $statut = CandidatureSoutenance::getStatutByEtudiant($_SESSION['num_etu']);
    if ($statut !== 'Validée') {
        echo '<div style="margin:2em;text-align:center;color:#b91c1c;font-size:1.2em;"><i class="fa fa-lock fa-2x"></i><br>Accès à la gestion des rapports bloqué tant que votre candidature à la soutenance n\'est pas validée.</div>';
        exit;
    }
}

if ($_GET['page'] === 'gestion_rapports') {
    require_once __DIR__ . '/../../app/controllers/GestionRapportController.php';
    $controller = new GestionRapportController();

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

    if(isset($_GET['action'])){
        switch ($_GET['action']){
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
            case 'load_template_html':
                $controller->loadTemplateHtml();
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