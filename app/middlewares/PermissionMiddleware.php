<?php
/**
 * Middleware de vérification des permissions
 * Vérifie que l'utilisateur a les droits nécessaires pour accéder aux pages et effectuer des actions
 */

require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/Fonctionnalite.php';
require_once __DIR__ . '/../config/database.php';

class PermissionMiddleware
{
    private $permissionModel;
    private $fonctionnaliteModel;
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->permissionModel = new Permission($this->pdo);
        $this->fonctionnaliteModel = new Fonctionnalite($this->pdo);
    }

    /**
     * Vérifie si l'utilisateur a accès à une page
     * @param string $page - Le code de la page (ex: 'dashboard', 'gestion_etudiants')
     * @param int $idGroupe - L'ID du groupe utilisateur
     * @param string $action - L'action CRUD: 'voir', 'creer', 'modifier', 'supprimer'
     * @return bool
     */
    public function checkPageAccess($page, $idGroupe, $action = 'voir')
    {
        // Pages publiques qui ne nécessitent pas de vérification
        $publicPages = ['page_connexion', 'logout', 'reset_password'];
        if (in_array($page, $publicPages)) {
            return true;
        }

        // Administrateur a tous les droits (id_GU = 5)
        if ($idGroupe == 5) {
            return true;
        }

        // Récupérer la fonctionnalité par son URL (correspondant au paramètre ?page=)
        $fonctionnalite = $this->fonctionnaliteModel->getFonctionnaliteByUrl($page);

        if (!$fonctionnalite) {
            error_log("PermissionMiddleware: Fonctionnalité non trouvée pour l'URL '$page'");
            return false;
        }

        // Récupérer toutes les permissions pour cette fonctionnalité
        $permission = $this->permissionModel->getPermissions($idGroupe, $fonctionnalite->id_fonctionnalite);

        if (!$permission) {
            error_log("PermissionMiddleware: Aucune permission trouvée pour groupe $idGroupe, fonctionnalité {$fonctionnalite->id_fonctionnalite}");
            return false;
        }

        // Vérifier le droit spécifique selon l'action
        switch ($action) {
            case 'voir':
                return (bool) $permission->peut_voir;
            case 'creer':
                return (bool) $permission->peut_creer;
            case 'modifier':
                return (bool) $permission->peut_modifier;
            case 'supprimer':
                return (bool) $permission->peut_supprimer;
            default:
                return false;
        }
    }

    /**
     * Vérifie l'accès et redirige si refusé
     * @param string $page
     * @param int $idGroupe
     * @param string $action
     */
    public function requirePermission($page, $idGroupe, $action = 'voir')
    {
        if (!$this->checkPageAccess($page, $idGroupe, $action)) {
            $this->denyAccess($action);
        }
    }

    /**
     * Détecte automatiquement l'action CRUD depuis la requête HTTP
     * @return string - 'voir', 'creer', 'modifier', ou 'supprimer'
     */
    public function detectAction()
    {
        // Vérifier le paramètre 'action' dans l'URL
        if (isset($_GET['action'])) {
            $action = strtolower($_GET['action']);

            // Mapping des actions communes
            if (strpos($action, 'ajouter') !== false || strpos($action, 'create') !== false || strpos($action, 'new') !== false) {
                return 'creer';
            }
            if (strpos($action, 'modifier') !== false || strpos($action, 'edit') !== false || strpos($action, 'update') !== false) {
                return 'modifier';
            }
            if (strpos($action, 'supprimer') !== false || strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
                return 'supprimer';
            }
        }

        // Vérifier les soumissions de formulaires POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['submit_add']) || isset($_POST['submit_create']) || isset($_POST['submit_ajouter'])) {
                return 'creer';
            }
            if (isset($_POST['submit_edit']) || isset($_POST['submit_update']) || isset($_POST['submit_modifier'])) {
                return 'modifier';
            }
            if (isset($_POST['submit_delete']) || isset($_POST['submit_supprimer']) || isset($_POST['submit_delete_multiple'])) {
                return 'supprimer';
            }
        }

        // Si on a un ID dans l'URL et pas de POST, c'est probablement une visualisation
        if (isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return 'voir';
        }

        // Par défaut, c'est une visualisation
        return 'voir';
    }

    /**
     * Bloque l'accès et affiche un message d'erreur
     * @param string $action
     */
    private function denyAccess($action = 'voir')
    {
        $messages = [
            'voir' => 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.',
            'creer' => 'Vous n\'avez pas l\'autorisation de créer des éléments sur cette page.',
            'modifier' => 'Vous n\'avez pas l\'autorisation de modifier des éléments sur cette page.',
            'supprimer' => 'Vous n\'avez pas l\'autorisation de supprimer des éléments sur cette page.'
        ];

        $message = isset($messages[$action]) ? $messages[$action] : $messages['voir'];

        // Stocker le message dans la session
        $_SESSION['error_message'] = $message;
        $_SESSION['error_type'] = 'permission_denied';

        // Rediriger vers la page d'accueil ou la page précédente
        if (isset($_SESSION['id_GU']) && $_SESSION['id_GU'] == 5) {
            // Pour l'admin, rediriger vers le dashboard
            header('Location: layout.php?page=dashboard_admin&error=permission');
        } else {
            // Pour les autres, rediriger vers leur dashboard
            header('Location: layout.php?page=dashboard&error=permission');
        }
        exit;
    }

    /**
     * Vérifie l'accès pour une action spécifique via code
     * @param string $codeFonctionnalite
     * @param int $idGroupe
     * @param string $action
     * @return bool
     */
    public function checkPermissionByCode($codeFonctionnalite, $idGroupe, $action = 'voir')
    {
        // Administrateur a tous les droits
        if ($idGroupe == 5) {
            return true;
        }

        return $this->permissionModel->checkPermissionByCode($idGroupe, $codeFonctionnalite, $action);
    }

    /**
     * Récupère toutes les permissions d'un groupe
     * @param int $idGroupe
     * @return array
     */
    public function getGroupePermissions($idGroupe)
    {
        return $this->permissionModel->getAllPermissionsForGroupe($idGroupe);
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une sous-page
     * @param string $pageParente
     * @param string $sousPage
     * @param int $idGroupe
     * @param string $action
     * @return bool
     */
    public function checkSousPageAccess($pageParente, $sousPage, $idGroupe, $action = 'voir')
    {
        // Vérifier d'abord l'accès à la page parente
        if (!$this->checkPageAccess($pageParente, $idGroupe, 'voir')) {
            return false;
        }

        // Vérifier l'accès à la sous-page
        return $this->checkPageAccess($sousPage, $idGroupe, $action);
    }

    /**
     * Log une tentative d'accès non autorisé
     * @param int $idUtilisateur
     * @param string $page
     * @param string $action
     */
    public function logUnauthorizedAccess($idUtilisateur, $page, $action)
    {
        require_once __DIR__ . '/../models/AuditLog.php';
        $auditLog = new AuditLog($this->pdo);

        $details = "Tentative d'accès non autorisé - Page: $page, Action: $action";
        $auditLog->logAction($idUtilisateur, 'acces_refuse', 'permission', 0, $details);
    }
}
