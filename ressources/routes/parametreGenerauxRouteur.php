<?php
if ($_GET['page'] === 'parametres_generaux') {
    require_once __DIR__ . '/../../app/controllers/ParametreController.php';
    $controller = new ParametreController();

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
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
            default:
                '';
        }
    }
}
