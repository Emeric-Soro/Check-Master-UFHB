<?php
if ($_GET['page'] === 'parametres_generaux') {
    require_once __DIR__ . '/../../app/controllers/ParametreController.php';
    $controller = new ParametreController();

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'annees_academiques':
                $controller->gestionAnnees();
                break;
            case 'grades':
                $controller->gestionGrade();
                break;
            case 'fonction_utilisateur':
                $controller->gestionFonctionUtilisateur();
                break;
            case 'specialites':
                $controller->gestionSpecialite();
                break;
            case 'niveaux_etude':
                $controller->gestionNiveauEtude();
                break;
            case 'ue';
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
            case 'entreprises':
                $controller->gestionEntreprise();
                break;
            case 'actions':
                $controller->gestionAction();
                break;
            case 'fonctions':
                $controller->gestionFonction();
                break;
            case 'messages':
                $controller->gestionMessagerie();
                break;
            case 'gestion_attribution':
                $controller->gestionAttribution();
                break;
            case 'salles':
                $controller->gestionSalles();
                break;
            case 'modeles_documents':
                $controller->gestionModeles();
                break;
            case 'placeholders_documentation':
                include __DIR__ . '/../../ressources/views/parametres_generaux/placeholders_documentation.php';
                break;
            default:
                '';
        }
    }
}