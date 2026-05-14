<?php

if ($_GET['page'] === 'gestion_rapports') {
    require_once __DIR__ . '/../../app/controllers/GestionRapportController.php';
    require_once __DIR__ . '/../../app/models/InfoStage.php';
    require_once __DIR__ . '/../../app/models/RapportEtudiant.php';
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/Services/GestionRapportService.php';

    $controller = new GestionRapportController();
    $service = new GestionRapportService(Database::getConnection());

    // ====== PRD 1: Téléchargement du rapport étudiant (upload de fichier) ======
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_rapport':
                $controller->traiterUploadRapport();
                exit;
            case 'admin_upload_rapport':
                $controller->traiterAdminUploadRapport();
                exit;
            case 'update_date_operation':
                $controller->updateDateOperation();
                exit;
        }
    }

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            // PRD 1: Page de téléchargement étudiant
            case 'telecharger_rapport':
                $controller->telechargerRapportEtudiant();
                break;
            // PRD 1: Téléchargement du modèle
            case 'download_modele':
                $controller->downloadModele();
                exit;
            // PRD 2: Page d'import admin
            case 'admin_telecharger_rapport':
                $controller->adminTelechargerRapport();
                break;
            // PRD 2: AJAX liste étudiants sans rapport
            case 'get_etudiants_sans_rapport':
                $controller->getEtudiantsSansRapportAjax();
                exit;
            // PRD 4: Export CSV
            case 'export_rapports_csv':
                $controller->exporterRapportsCsv();
                exit;
            // PRD: Téléchargement fichier physique rapport
            case 'download_fichier_rapport':
                $controller->downloadFichierRapport();
                exit;
            // Legacy actions
            case 'creer_rapport':
                // Vérifier si l'étudiant tente de créer ou modifier un rapport
                $needsStageInfo = true;

                if ($needsStageInfo && isset($_SESSION['num_etu'])) {
                    $infoStageModel = new InfoStage(Database::getConnection());
                    $stage_info = $infoStageModel->getStageInfo($_SESSION['num_etu']);

                    $GLOBALS['stage_info'] = $stage_info;

                    if (!$stage_info) {
                        $_SESSION['error'] = "Vous devez d'abord remplir vos informations de stage avant de créer votre rapport.";
                        header('Location: layout.php?page=candidature_soutenance');
                        exit;
                    }
                }

                $controller->creerRapport();
                break;
            default:
                // Action non reconnue, on continue pour afficher le dashboard
                break;
        }
    }

    // Legacy POST handling
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if (in_array($_POST['action'], ['save_rapport', 'export_pdf', 'deposer_rapport'], true)) {
            $controller->traiterCreationRapport();
            exit;
        }
    }

    // Préparation des données pour le dashboard (gestion_rapports_content.php)
    // Seulement si aucune action spécifique n'a déjà été traitée
    $actionsThatSetData = ['telecharger_rapport', 'admin_telecharger_rapport', 'creer_rapport'];
    $isDataAlreadySet = isset($_GET['action']) && in_array($_GET['action'], $actionsThatSetData);

    if (!$isDataAlreadySet) {
        if (isset($_SESSION['num_etu'])) {
            $num_etu = $_SESSION['num_etu'];
            
            // Récupérer les rapports et stats
            $GLOBALS['rapportsRecents'] = $service->getRapportsRecentsEtudiant($num_etu);
            $GLOBALS['statistiquesRapports'] = $service->getStatsEtudiant($num_etu);
            
            // Préparer les infos de dépôt pour chaque rapport
            $infosDepot = [];
            foreach ($GLOBALS['rapportsRecents'] as $rapport) {
                $rapportId = (int) ($rapport->id_rapport ?? 0);
                $peutDeposer = !$service->isRapportDepose($num_etu, $rapportId) && !$service->aUnRapportEnCours($num_etu);
                $dejaDepose = $service->isRapportDepose($num_etu, $rapportId);
                
                $messageDepot = '';
                if ($dejaDepose) {
                    $messageDepot = 'Déjà déposé';
                } elseif ($service->aUnRapportEnCours($num_etu)) {
                    $messageDepot = 'Un rapport est déjà en cours d\'évaluation';
                }
                
                $infosDepot[$rapportId] = [
                    'peutDeposer' => $peutDeposer,
                    'dejaDepose' => $dejaDepose,
                    'messageDepot' => $messageDepot
                ];
            }
            $GLOBALS['infosDepot'] = $infosDepot;
        } else {
            $GLOBALS['rapportsRecents'] = [];
            $GLOBALS['statistiquesRapports'] = (object) ['total_rapports' => 0];
            $GLOBALS['infosDepot'] = [];
        }
    }
}
