<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/GroupeUtilisateur.php';
require_once __DIR__ . '/../models/Traitement.php';
require_once __DIR__ . '/../models/Action.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../traits/HasPermissions.php';

/**
 * Contrôleur pour la gestion des permissions
 * 
 * @author CheckMaster Team
 * @version 1. 0
 */
class GestionPermissionsController
{
    use HasPermissions;

    private $db;
    private $permissionModel;
    private $groupeModel;
    private $traitementModel;
    private $actionModel;
    private $auditLog;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->permissionModel = new Permission($this->db);
        $this->groupeModel = new GroupeUtilisateur($this->db);
        $this->traitementModel = new Traitement($this->db);
        $this->actionModel = new Action($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    /**
     * Affiche la page de gestion des permissions
     */
    public function index()
    {
        // Vérifier la permission de consultation
        $this->requirePermission('parametres_generaux', 'Consulter');

        // Récupérer tous les groupes
        $GLOBALS['groupes'] = $this->groupeModel->getAllGroupeUtilisateur();

        // Récupérer tous les traitements et actions
        $GLOBALS['traitements'] = $this->traitementModel->getAllTraitements();
        $GLOBALS['actions'] = $this->actionModel->getAllAction();

        // Si un groupe est sélectionné, charger sa matrice
        $selectedGU = isset($_GET['id_GU']) ? (int)$_GET['id_GU'] : null;
        
        if ($selectedGU) {
            $GLOBALS['matrice'] = $this->permissionModel->getPermissionsMatrice($selectedGU);
            $GLOBALS['selectedGU'] = $selectedGU;
            $GLOBALS['selectedGroupe'] = $this->groupeModel->getGroupeUtilisateurById($selectedGU);
        }

        // Préparer les autorisations pour la vue
        $this->prepareViewAuthorizations('parametres_generaux');

        // Statistiques
        $GLOBALS['stats'] = $this->permissionModel->getStatistiques();
    }

    /**
     * Met à jour les permissions (POST)
     */
    public function updatePermissions()
    {
        // Vérifier la permission de modification
        $this->requirePermission('parametres_generaux', 'Modifier');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Méthode non autorisée";
            header('Location: layout.php?page=parametres_generaux&action=gestion_permissions');
            exit;
        }

        $idGU = isset($_POST['id_GU']) ? (int)$_POST['id_GU'] : 0;
        $permissions = $_POST['permissions'] ?? [];

        if (!$idGU) {
            $_SESSION['error'] = "Groupe non spécifié";
            header('Location: layout.php?page=parametres_generaux&action=gestion_permissions');
            exit;
        }

        // Construire le tableau des permissions
        $permissionsToSave = [];

        // Récupérer tous les traitements et actions pour gérer les cases non cochées
        $traitements = $this->traitementModel->getAllTraitements();
        $actions = $this->actionModel->getAllAction();

        foreach ($traitements as $traitement) {
            foreach ($actions as $action) {
                $isAuthorized = isset($permissions[$traitement->id_traitement][$action->id_action]) 
                                && $permissions[$traitement->id_traitement][$action->id_action] === '1';
                
                $permissionsToSave[] = [
                    'id_traitement' => $traitement->id_traitement,
                    'id_action' => $action->id_action,
                    'autorise' => $isAuthorized,
                    'conditions' => null
                ];
            }
        }

        $creePar = $_SESSION['id_utilisateur'] ?? null;

        if ($this->permissionModel->setPermissionsBatch($idGU, $permissionsToSave, $creePar)) {
            // Log de l'audit
            $this->auditLog->logModification(
                $_SESSION['id_utilisateur'],
                'permissions',
                'Succès'
            );
            
            $groupe = $this->groupeModel->getGroupeUtilisateurById($idGU);
            $_SESSION['success'] = "Permissions du groupe '{$groupe->lib_GU}' mises à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour des permissions";
        }

        header("Location: layout.php?page=parametres_generaux&action=gestion_permissions&id_GU={$idGU}");
        exit;
    }

    /**
     * Récupère la matrice des permissions en AJAX
     */
    public function getMatriceAjax()
    {
        $this->requirePermission('parametres_generaux', 'Consulter');

        $idGU = isset($_GET['id_GU']) ? (int)$_GET['id_GU'] : 0;

        if (! $idGU) {
            $this->sendJsonError('ID groupe requis', 400);
        }

        $matrice = $this->permissionModel->getPermissionsMatrice($idGU);
        $groupe = $this->groupeModel->getGroupeUtilisateurById($idGU);

        $this->sendJsonSuccess('Matrice récupérée', [
            'groupe' => $groupe,
            'matrice' => $matrice
        ]);
    }

    /**
     * Copie les permissions d'un groupe vers un autre
     */
    public function copyPermissions()
    {
        $this->requirePermission('parametres_generaux', 'Modifier');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonError('Méthode non autorisée', 405);
        }

        $fromGU = isset($_POST['from_GU']) ?  (int)$_POST['from_GU'] : 0;
        $toGU = isset($_POST['to_GU']) ? (int)$_POST['to_GU'] : 0;

        if (!$fromGU || !$toGU) {
            $this->sendJsonError('Groupes source et destination requis', 400);
        }

        if ($fromGU === $toGU) {
            $this->sendJsonError('Les groupes source et destination doivent être différents', 400);
        }

        $creePar = $_SESSION['id_utilisateur'] ?? null;

        if ($this->permissionModel->copyPermissions($fromGU, $toGU, $creePar)) {
            $groupeFrom = $this->groupeModel->getGroupeUtilisateurById($fromGU);
            $groupeTo = $this->groupeModel->getGroupeUtilisateurById($toGU);
            
            $this->auditLog->logCreation(
                $_SESSION['id_utilisateur'],
                'permissions',
                'Succès'
            );

            $this->sendJsonSuccess(
                "Permissions copiées de '{$groupeFrom->lib_GU}' vers '{$groupeTo->lib_GU}'"
            );
        } else {
            $this->sendJsonError('Erreur lors de la copie des permissions');
        }
    }

    /**
     * Initialise les permissions par défaut pour un groupe
     */
    public function initDefaultPermissions()
    {
        $this->requirePermission('parametres_generaux', 'Modifier');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonError('Méthode non autorisée', 405);
        }

        $idGU = isset($_POST['id_GU']) ?  (int)$_POST['id_GU'] : 0;

        if (! $idGU) {
            $this->sendJsonError('ID groupe requis', 400);
        }

        $creePar = $_SESSION['id_utilisateur'] ?? null;

        if ($this->permissionModel->initDefaultPermissions($idGU, $creePar)) {
            $groupe = $this->groupeModel->getGroupeUtilisateurById($idGU);
            
            $this->auditLog->logCreation(
                $_SESSION['id_utilisateur'],
                'permissions',
                'Succès'
            );

            $this->sendJsonSuccess(
                "Permissions par défaut initialisées pour '{$groupe->lib_GU}'"
            );
        } else {
            $this->sendJsonError('Erreur lors de l\'initialisation des permissions');
        }
    }

    /**
     * Récupère les statistiques des permissions
     */
    public function getStatistiques()
    {
        $this->requirePermission('parametres_generaux', 'Consulter');

        $stats = $this->permissionModel->getStatistiques();
        $this->sendJsonSuccess('Statistiques récupérées', ['stats' => $stats]);
    }

    /**
     * Sélectionne/Désélectionne toutes les permissions d'une ligne (traitement)
     */
    public function toggleRowPermissions()
    {
        $this->requirePermission('parametres_generaux', 'Modifier');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonError('Méthode non autorisée', 405);
        }

        $idGU = isset($_POST['id_GU']) ? (int)$_POST['id_GU'] : 0;
        $idTraitement = isset($_POST['id_traitement']) ? (int)$_POST['id_traitement'] : 0;
        $autorise = isset($_POST['autorise']) && $_POST['autorise'] === '1';

        if (!$idGU || ! $idTraitement) {
            $this->sendJsonError('Paramètres manquants', 400);
        }

        $actions = $this->actionModel->getAllAction();
        $creePar = $_SESSION['id_utilisateur'] ?? null;

        $permissions = [];
        foreach ($actions as $action) {
            $permissions[] = [
                'id_traitement' => $idTraitement,
                'id_action' => $action->id_action,
                'autorise' => $autorise,
                'conditions' => null
            ];
        }

        if ($this->permissionModel->setPermissionsBatch($idGU, $permissions, $creePar)) {
            $this->sendJsonSuccess('Permissions mises à jour');
        } else {
            $this->sendJsonError('Erreur lors de la mise à jour');
        }
    }

    /**
     * Sélectionne/Désélectionne toutes les permissions d'une colonne (action)
     */
    public function toggleColumnPermissions()
    {
        $this->requirePermission('parametres_generaux', 'Modifier');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonError('Méthode non autorisée', 405);
        }

        $idGU = isset($_POST['id_GU']) ? (int)$_POST['id_GU'] : 0;
        $idAction = isset($_POST['id_action']) ? (int)$_POST['id_action'] : 0;
        $autorise = isset($_POST['autorise']) && $_POST['autorise'] === '1';

        if (!$idGU || !$idAction) {
            $this->sendJsonError('Paramètres manquants', 400);
        }

        $traitements = $this->traitementModel->getAllTraitements();
        $creePar = $_SESSION['id_utilisateur'] ?? null;

        $permissions = [];
        foreach ($traitements as $traitement) {
            $permissions[] = [
                'id_traitement' => $traitement->id_traitement,
                'id_action' => $idAction,
                'autorise' => $autorise,
                'conditions' => null
            ];
        }

        if ($this->permissionModel->setPermissionsBatch($idGU, $permissions, $creePar)) {
            $this->sendJsonSuccess('Permissions mises à jour');
        } else {
            $this->sendJsonError('Erreur lors de la mise à jour');
        }
    }
}