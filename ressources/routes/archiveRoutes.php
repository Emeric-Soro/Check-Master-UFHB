<?php
/**
 * Routes pour le module d'archivage (PRD8)
 */

require_once __DIR__ . '/../../app/controllers/ArchiveHubController.php';
require_once __DIR__ . '/../../app/controllers/ArchiveEtudiantController.php';
require_once __DIR__ . '/../../app/controllers/ArchiveSoutenanceController.php';
require_once __DIR__ . '/../../app/controllers/ArchiveDocumentController.php';
require_once __DIR__ . '/../../app/controllers/ArchiveAdminController.php';

$page = $_GET['page'] ?? '';
$action = $_GET['action'] ?? '';

// Router vers le bon contrôleur selon la page
switch ($page) {
    case 'admin_historique':
    case 'hub_historique':
        if (
            $page === 'admin_historique'
            && in_array($action, ['view_student', 'update_student', 'import', 'import_result', 'export'], true)
        ) {
            break;
        }
        $controller = new ArchiveHubController();
        if ($action === 'changeYear') {
            $controller->changeYear();
        } else {
            $data = $controller->index();
            $pageTitle = 'Historique et Archivage';
            $contentFile = __DIR__ . '/../views/v2/archives/hub_historique.php';
        }
        break;

    case 'archives_etudiants':
        $controller = new ArchiveEtudiantController();
        if ($action === 'exportCsv') {
            $controller->exportCsv();
        } else {
            $data = $controller->index();
            $pageTitle = 'Archives Étudiants';
            $contentFile = __DIR__ . '/../views/v2/archives/archives_etudiants.php';
        }
        break;

    case 'fiche_etudiant_archive':
        $controller = new ArchiveEtudiantController();
        $matricule = $_GET['id'] ?? '';
        $data = $controller->fiche($matricule);
        $pageTitle = 'Fiche Étudiant';
        $contentFile = __DIR__ . '/../views/v2/archives/fiche_etudiant_archive.php';
        break;

    case 'parcours_etudiant':
        $controller = new ArchiveEtudiantController();
        $matricule = $_GET['id'] ?? '';
        $data = $controller->parcours($matricule);
        $pageTitle = 'Parcours Étudiant';
        $contentFile = __DIR__ . '/../views/v2/archives/parcours_etudiant.php';
        break;

    case 'archives_soutenances':
        $controller = new ArchiveSoutenanceController();
        $data = $controller->index();
        $pageTitle = 'Archives Soutenances';
        $contentFile = __DIR__ . '/../views/v2/archives/archives_soutenances.php';
        break;

    case 'fiche_soutenance':
        $controller = new ArchiveSoutenanceController();
        $numSoutenance = $_GET['id'] ?? '';
        $data = $controller->fiche($numSoutenance);
        $pageTitle = 'Fiche Soutenance';
        $contentFile = __DIR__ . '/../views/v2/archives/fiche_soutenance.php';
        break;

    case 'archives_jurys':
        $controller = new ArchiveSoutenanceController();
        $data = $controller->jurys();
        $pageTitle = 'Archives Jurys';
        $contentFile = __DIR__ . '/../views/v2/archives/archives_jurys.php';
        break;

    case 'archives_documents':
        $controller = new ArchiveDocumentController();
        $data = $controller->index();
        $pageTitle = 'Archives Documents';
        $contentFile = __DIR__ . '/../views/v2/archives/archives_documents.php';
        break;

    case 'visionneuse_document':
        $controller = new ArchiveDocumentController();
        $controller->visionneuse();
        break;

    case 'telecharger_document':
        $controller = new ArchiveDocumentController();
        $controller->telecharger();
        break;

    case 'archives_candidatures':
        $controller = new ArchiveAdminController();
        $data = $controller->candidatures();
        $pageTitle = 'Archives Candidatures';
        $contentFile = __DIR__ . '/../views/v2/archives/archives_candidatures.php';
        break;

    case 'archives_reclamations':
        $controller = new ArchiveAdminController();
        $data = $controller->reclamations();
        $pageTitle = 'Archives Réclamations';
        $contentFile = __DIR__ . '/../views/v2/archives/archives_reclamations.php';
        break;

    default:
        // Page non reconnue
        break;
}

// Le layout.php inclura le fichier de contenu approprié
