<?php
/**
 * Middleware de vérification des permissions.
 * Conserve l'API legacy mais délègue la décision effective à AuthorizationService.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Autoload.php';

use CheckMaster\Security\AuthorizationService;
use CheckMaster\Security\PermissionRegistry;

class PermissionMiddleware
{
    private $pdo;
    private $authorizationService;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->authorizationService = new AuthorizationService($this->pdo);
    }

    public function checkPageAccess($page, $idGroupe, $action = 'voir')
    {
        $page = is_string($page) ? trim($page) : '';
        $action = in_array($action, ['voir', 'creer', 'modifier', 'supprimer'], true) ? $action : 'voir';

        if ($page === '') {
            $page = (string) ($_GET['page'] ?? '');
        }

        if ($page !== '') {
            foreach (PermissionRegistry::publicRoutes() as $route) {
                $pattern = (string) ($route['pattern'] ?? '');
                if ($pattern === 'page=' . $page) {
                    return true;
                }
            }
        }

        return $this->authorizationService->checkFeaturePermission((int) $idGroupe, $page, $action);
    }

    public function requirePermission($page, $idGroupe, $action = 'voir')
    {
        if (!$this->checkPageAccess($page, $idGroupe, $action)) {
            $this->denyAccess($action);
        }
    }

    public function detectAction()
    {
        $resolved = $this->authorizationService->resolveLegacyRequest($_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'GET');
        return (string) ($resolved['action'] ?? 'voir');
    }

    private function denyAccess($action = 'voir')
    {
        $messages = [
            'voir' => 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.',
            'creer' => 'Vous n\'avez pas l\'autorisation de créer des éléments sur cette page.',
            'modifier' => 'Vous n\'avez pas l\'autorisation de modifier des éléments sur cette page.',
            'supprimer' => 'Vous n\'avez pas l\'autorisation de supprimer des éléments sur cette page.',
        ];

        $_SESSION['error_message'] = $messages[$action] ?? $messages['voir'];
        $_SESSION['error_type'] = 'permission_denied';
        header('Location: layout.php?page=access_denied');
        exit;
    }

    public function checkPermissionByCode($codeFonctionnalite, $idGroupe, $action = 'voir')
    {
        return $this->authorizationService->checkFeaturePermission((int) $idGroupe, (string) $codeFonctionnalite, (string) $action);
    }

    private function isAdminGroup(int $idGroupe): bool
    {
        $groups = PermissionRegistry::groups();
        return isset($groups['administrateur']) && (int) $groups['administrateur'] === $idGroupe;
    }

    public function getGroupePermissions($idGroupe)
    {
        require_once __DIR__ . '/../models/Permission.php';
        $permissionModel = new Permission($this->pdo);
        return $permissionModel->getAllPermissionsForGroupe($idGroupe);
    }

    public function checkSousPageAccess($pageParente, $sousPage, $idGroupe, $action = 'voir')
    {
        return $this->checkPageAccess($pageParente, $idGroupe, 'voir')
            && $this->checkPageAccess($sousPage, $idGroupe, $action);
    }

    public function logUnauthorizedAccess($idUtilisateur, $page, $action)
    {
        require_once __DIR__ . '/../models/AuditLog.php';
        $auditLog = new AuditLog($this->pdo);
        $details = "Tentative d'accès non autorisé - Page: {$page}, Action: {$action}";
        $auditLog->logAction($idUtilisateur, 'acces_refuse', 'permission', 'Erreur', $details);
    }
}
