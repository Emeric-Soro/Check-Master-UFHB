<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/ParametreService.php';

use CheckMaster\Services\ParametreService;

class ParametreController
{
    private $service;

    public function __construct()
    {
        $this->service = new ParametreService(Database::getConnection());
    }

    //=============================GESTION ANNEE ACADEMIQUE=============================
    public function gestionAnnees()
    {
        $result = $this->service->gestionAnnees($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION ANNEE ACADEMIQUE=============================


    //=============================GESTION GRADES=============================
    public function gestionGrade()
    {
        $result = $this->service->gestionGrade($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION GRADES=============================



    //=============================GESTION FONCTION UTILISATEUR=============================
    public function gestionFonctionUtilisateur()
    {
        $result = $this->service->gestionFonctionUtilisateur($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION FONCTION UTILISATEUR=============================


    //=============================GESTION SPECIALITE=============================
    public function gestionSpecialite()
    {
        $result = $this->service->gestionSpecialite($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION SPECIALITE=============================


    //=============================GESTION NIVEAU ETUDE=============================
    public function gestionNiveauEtude()
    {
        $result = $this->service->gestionNiveauEtude($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION NIVEAU ETUDE=============================


    //=============================GESTION UE=============================
    public function gestionUe()
    {
        $result = $this->service->gestionUe($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION UE=============================


    //=============================GESTION ECUE=============================
    public function gestionEcue()
    {
        $result = $this->service->gestionEcue($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION ECUE=============================


    //=============================GESTION STATUT JURY=============================
    public function gestionStatutJury()
    {
        $result = $this->service->gestionStatutJury($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION STATUT JURY=============================


    //=============================GESTION NIVEAU APPROBATION=============================
    public function gestionNiveauApprobation()
    {
        $result = $this->service->gestionNiveauApprobation($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION NIVEAU APPROBATION=============================


    //=============================GESTION SEMESTRE=============================
    public function gestionSemestre()
    {
        $result = $this->service->gestionSemestre($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION SEMESTRE=============================


    //=============================GESTION NIVEAU ACCES DONNEES=============================
    public function gestionNiveauAccesDonnees()
    {
        $result = $this->service->gestionNiveauAccesDonnees($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION NIVEAU ACCES DONNEES=============================


    //=============================GESTION TRAITEMENT=============================
    public function gestionTraitement()
    {
        $result = $this->service->gestionTraitement($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION TRAITEMENT=============================


    //=============================GESTION ENTREPRISE=============================
    public function gestionEntreprise()
    {
        $result = $this->service->gestionEntreprise($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION ENTREPRISE=============================


    //=============================GESTION ACTION=============================
    public function gestionAction()
    {
        $result = $this->service->gestionAction($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION ACTION=============================


    //=============================GESTION FONCTION=============================
    public function gestionFonction()
    {
        $result = $this->service->gestionFonction($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION FONCTION=============================


    //=============================GESTION MESSAGERIE=============================
    public function gestionMessagerie()
    {
        $result = $this->service->gestionMessagerie($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION MESSAGERIE=============================


    //============================GESTION ATTRIBUTION==================================
    public function gestionAttribution()
    {
        $result = $this->service->gestionAttribution(
            $_POST,
            $_GET,
            $_SESSION['id_utilisateur'],
            $_SESSION['id_GU']
        );
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //============================FIN GESTION ATTRIBUTION==================================


    //============================GESTION MENUS==================================
    public function gestionMenus()
    {
        $result = $this->service->gestionMenus($_POST, $_GET);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //============================FIN GESTION MENUS==================================


    //==============================GESTION SALLES==============================
    public function gestionSalles()
    {
        // Cette méthode ne fait rien de spécial car la logique
        // est directement dans la vue salles.php pour simplifier
        // On laisse juste la vue se charger
    }
    //==============================FIN GESTION SALLES==============================
}


/*Ce fichier est le contrôleur principal pour la gestion des paramètres généraux de l'application.
    Il gère les actions liées aux entités telles que les années académiques, les grades, les ECUE, etc.
    Chaque méthode correspond à une fonctionnalité spécifique et délègue la logique métier au ParametreService. */
