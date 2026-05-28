<?php
// Route pour la Fiche Commission (P2.1)
if (isset($_GET['page']) && $_GET['page'] === 'fiche_commission') {
    require_once __DIR__ . '/../../app/controllers/FicheCommissionController.php';
    $controller = new FicheCommissionController();
    $donneesPage = $controller->index();

    $membres = $donneesPage['membres'] ?? [];
    $rapportsEvalues = $donneesPage['rapports_evalues'] ?? [];
    $rapportsAttente = $donneesPage['rapports_attente'] ?? [];
    $statsVote = $donneesPage['stats_vote'] ?? [];
    $decisions = $donneesPage['decisions'] ?? [];
    $planning = $donneesPage['planning'] ?? [];
}
