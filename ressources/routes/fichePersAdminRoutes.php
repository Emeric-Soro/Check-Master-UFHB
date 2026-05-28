<?php
// Route pour la Fiche Personnel Administratif (P2.2)
if (isset($_GET['page']) && $_GET['page'] === 'fiche_personnel_admin') {
    require_once __DIR__ . '/../../app/controllers/FichePersAdminController.php';
    $controller = new FichePersAdminController();
    $ficheData = $controller->index();

    $identite = $ficheData['identite'] ?? null;
    $compte = $ficheData['compte'] ?? null;
    $candidatures = $ficheData['candidatures'] ?? [];
    $historique = $ficheData['historique'] ?? [];
    $stats = $ficheData['stats'] ?? [];
}
