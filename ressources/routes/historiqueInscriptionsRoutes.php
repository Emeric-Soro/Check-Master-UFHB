<?php
// Route pour l'Historique des Inscriptions (P2.3)
if (isset($_GET['page']) && $_GET['page'] === 'historique_inscriptions') {
    require_once __DIR__ . '/../../app/controllers/HistoriqueInscriptionsController.php';
    $controller = new HistoriqueInscriptionsController();
    $donnees = $controller->index();

    $etudiant = $donnees['etudiant'] ?? null;
    // Normaliser en tableau associatif si c'est un objet
    if ($etudiant !== null && is_object($etudiant)) {
        $etudiant = (array) $etudiant;
    }
    $parcours = $donnees['parcours'] ?? [];
    $notes = $donnees['notes'] ?? [];
}
