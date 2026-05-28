<?php
/**
 * Routes pour fiche_etudiant_complete (PRD P1.1 – Fiche Etudiante Complete 10 onglets)
 * 
 * Route unique : ?page=fiche_etudiant_complete
 *   - id     : matricule etudiant (num_carte_etud)
 *   - onglet : onglet a afficher (optionnel)
 *   - ajax   : mode AJAX (optionnel)
 */

require_once __DIR__ . '/../../app/controllers/FicheEtudiantController.php';

$page = $_GET['page'] ?? '';

switch ($page) {
    case 'fiche_etudiant_complete':
        $controller = new FicheEtudiantController();
        $data = $controller->index();

        // En mode AJAX, le controleur a deja echo et exit
        if (($data['_ajax'] ?? false) || ($_GET['ajax'] ?? '') === '1') {
            exit;
        }

        $pageTitle = 'Fiche Etudiante Complete';
        $contentFile = __DIR__ . '/../views/v2/archives/fiche_etudiant_complete.php';
        break;

    default:
        // Page non reconnue – on ne fait rien, le layout gerera le fallback
        break;
}
