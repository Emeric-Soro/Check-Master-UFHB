<?php
if (($_GET['page'] ?? '') === 'parametres_generaux') {
    require_once __DIR__ . '/../../app/controllers/ParametreController.php';
    require_once __DIR__ . '/../../app/controllers/PurgeCycleController.php';
    $controller = new ParametreController();
    $purgeCycleController = new PurgeCycleController();

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'purge_cycle_etudiant':
                $ajax = (string) ($_GET['ajax'] ?? '');
                $op = (string) ($_GET['op'] ?? '');

                if ($ajax === 'search') {
                    $purgeCycleController->searchStudents();
                    break;
                }

                if ($ajax === 'inventory') {
                    $purgeCycleController->getCycleInventory();
                    break;
                }

                if ($ajax === 'rebuild_draft') {
                    $purgeCycleController->getReconstructionDraft();
                    break;
                }

                if ($op === 'dry_run') {
                    $purgeCycleController->dryRunPurge();
                    break;
                }

                if ($op === 'purge') {
                    $purgeCycleController->purge();
                    break;
                }

                if ($op === 'rebuild') {
                    $purgeCycleController->rebuildCycle();
                    break;
                }

                $purgeCycleController->index();
                break;
            // Compatibilité transitoire avec les anciennes URLs internes.
            case 'purge_cycle_etudiant_search':
                $purgeCycleController->searchStudents();
                break;
            case 'purge_cycle_etudiant_inventory':
                $purgeCycleController->getCycleInventory();
                break;
            case 'purge_cycle_etudiant_dry_run':
                $purgeCycleController->dryRunPurge();
                break;
            case 'purge_cycle_etudiant_purge':
                $purgeCycleController->purge();
                break;
            case 'purge_cycle_etudiant_draft':
                $purgeCycleController->getReconstructionDraft();
                break;
            case 'purge_cycle_etudiant_rebuild':
                $purgeCycleController->rebuildCycle();
                break;
            case 'annees_academiques':
                $controller->gestionAnnees();
                break;
            case 'app_settings':
            case 'genre':
            case 'decisions_jury':
            case 'etablissement_origine':
            case 'session':
            case 'mode_paiement':
            case 'statut_reclamation':
            case 'domaine':
            case 'mentions':
            case 'filieres':
            case 'qualite_jury':
            case 'maitre_stage':
            case 'type_enseignant':
                $controller->gestionReferentielSimple();
                break;
            case 'grades':
                $controller->gestionGrade();
                break;
            case 'fonction_utilisateur':
                $controller->gestionFonctionUtilisateur();
                break;
            case 'specialites':
            case 'entreprises':
            case 'actions':
            case 'fonctions':
                $controller->gestionReferentielSimple();
                break;
            case 'niveaux_etude':
                $controller->gestionNiveauEtude();
                break;
            case 'ue':
                $controller->gestionUe();
                break;
            case 'ecue':
                $controller->gestionEcue();
                break;
            case 'statut_jury':
                $controller->gestionStatutJury();
                break;
            case 'niveaux_approbation':
                $controller->gestionNiveauApprobation();
                break;
            case 'semestres':
                $controller->gestionSemestre();
                break;
            case 'niveaux_acces':
                $controller->gestionNiveauAccesDonnees();
                break;
            case 'traitements':
                $controller->gestionTraitement();
                break;
            case 'messages':
                $controller->gestionReferentielSimple();
                break;
            case 'gestion_attribution':
                $controller->gestionAttribution();
                break;
            case 'gestion_menus':
                // Guard (outil d'analyse statique / compat)
                if (method_exists($controller, 'gestionMenus')) {
                    $controller->gestionMenus();
                }
                break;
            case 'salles':
                $controller->gestionSalles();
                break;
            case 'bareme_critere':
                $controller->gestionBaremeCritere();
                break;
            // Pages migrées vers Paramètres Généraux (2026-06-05)
            case 'suivi_scolarite':
            case 'enseignant_gestion':
            case 'outils_direction':
            case 'documents':
            case 'recherche_globale':
                // Routage géré directement dans layout.php
                break;
            default:
                '';
        }
    }
}
