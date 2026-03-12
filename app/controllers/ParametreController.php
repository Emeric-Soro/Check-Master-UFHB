<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/ParametreService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

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
if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionAnnees($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION ANNEE ACADEMIQUE=============================


    //=============================GESTION GRADES=============================
    public function gestionGrade()
    {
if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
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
if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION FONCTION UTILISATEUR=============================


    //=============================GESTION SPECIALITE=============================
    public function gestionSpecialite()
    {
if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
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
if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION NIVEAU ETUDE=============================


    //=============================GESTION STATUT JURY=============================
    public function gestionStatutJury()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionStatutJury($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION STATUT JURY=============================


    //=============================GESTION NIVEAU APPROBATION=============================
    public function gestionNiveauApprobation()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionNiveauApprobation($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION NIVEAU APPROBATION=============================


    //=============================GESTION SEMESTRE=============================
    public function gestionSemestre()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionSemestre($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION SEMESTRE=============================


    //=============================GESTION NIVEAU ACCES DONNEES=============================
    public function gestionNiveauAccesDonnees()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionNiveauAccesDonnees($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION NIVEAU ACCES DONNEES=============================


    //=============================GESTION TRAITEMENT=============================
    public function gestionTraitement()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionTraitement($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION TRAITEMENT=============================


    //=============================GESTION ENTREPRISE=============================
    public function gestionEntreprise()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionEntreprise($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION ENTREPRISE=============================


    //=============================GESTION ACTION=============================
    public function gestionAction()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionAction($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION ACTION=============================


    //=============================GESTION FONCTION=============================
    public function gestionFonction()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionFonction($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION FONCTION=============================


    //=============================GESTION MESSAGERIE=============================
    public function gestionMessagerie()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionMessagerie($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //=============================FIN GESTION MESSAGERIE=============================


    //============================GESTION ATTRIBUTION==================================
    public function gestionAttribution()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
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
        $permissionCode = (string) ($_GET['page'] ?? 'parametres_generaux');
        if (!in_array($permissionCode, ['parametres_generaux', 'parametres_specifiques'], true)) {
            $permissionCode = 'parametres_generaux';
        }
        if (!canCreate($permissionCode) && !canEdit($permissionCode) && !canDelete($permissionCode)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        $result = $this->service->gestionMenus($_POST, $_GET);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //============================FIN GESTION MENUS==================================

    //============================GESTION REFERENTIEL SIMPLE==================================
    public function gestionReferentielSimple()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $result = $this->service->gestionReferentielSimple($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //============================FIN GESTION REFERENTIEL SIMPLE==================================

    //============================GESTION BAREME CRITERE==================================
    public function gestionBaremeCritere()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $result = $this->service->gestionBaremeCritere($_POST, $_GET, $_SESSION['id_utilisateur']);
        foreach ($result as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
    //============================FIN GESTION BAREME CRITERE==================================


    //==============================GESTION SALLES==============================
    public function gestionSalles()
    {
        if (!canCreate('parametres_generaux') && !canEdit('parametres_generaux') && !canDelete('parametres_generaux')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        // Cette méthode ne fait rien de spécial car la logique
        // est directement dans la vue salles.php pour simplifier
        // On laisse juste la vue se charger
    }
    //==============================FIN GESTION SALLES==============================
}


/*Ce fichier est le contrôleur principal pour la gestion des paramètres généraux de l'application.
    Il gère les actions liées aux entités telles que les années académiques, les grades, les ECUE, etc.
    Chaque méthode correspond à une fonctionnalité spécifique et délègue la logique métier au ParametreService. */
