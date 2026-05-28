<?php
/**
 * Routeur pour la timeline interactive du parcours etudiant
 * Utilise ArchiveEtudiantController
 */

declare(strict_types=1);

require_once __DIR__ . '/../../app/controllers/ArchiveEtudiantController.php';

if ((string) ($_GET['page'] ?? '') !== 'timeline_parcours_etudiant') {
    return;
}

try {
    $db = Database::getConnection();
    $controller = new ArchiveEtudiantController($db);
    $matricule = trim((string) ($_GET['num_etu'] ?? $_GET['matricule'] ?? $_GET['id'] ?? ''));

    if ($matricule === '') {
        $GLOBALS['timeline_error'] = 'Matricule etudiant requis.';
        $GLOBALS['timeline_data'] = ['matricule' => '', 'evenements' => []];
    } else {
        $result = $controller->parcours($matricule);
        $GLOBALS['timeline_data'] = $result;
    }
} catch (Exception $e) {
    error_log('Erreur timelineParcoursRoutes: ' . $e->getMessage());
    $GLOBALS['timeline_error'] = 'Erreur lors du chargement de la timeline.';
    $GLOBALS['timeline_data'] = ['matricule' => '', 'evenements' => []];
}
