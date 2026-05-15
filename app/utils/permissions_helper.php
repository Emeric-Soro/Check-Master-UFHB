<?php
/**
 * Helpers de compatibilité pour les permissions.
 * Toute la décision runtime est centralisée dans AuthorizationService.
 */

require_once __DIR__ . '/../Core/Autoload.php';
require_once __DIR__ . '/../config/database.php';

use CheckMaster\Security\AuthorizationService;
use CheckMaster\Security\PermissionContextFactory;
use CheckMaster\Security\PermissionRegistry;

if (!function_exists('cm_authorization_service')) {
    function cm_authorization_service(): AuthorizationService
    {
        static $service = null;
        if (!$service instanceof AuthorizationService) {
            $service = new AuthorizationService(Database::getConnection());
        }

        return $service;
    }
}

if (!function_exists('cm_permission_context_factory')) {
    function cm_permission_context_factory(): PermissionContextFactory
    {
        static $factory = null;
        if (!$factory instanceof PermissionContextFactory) {
            $factory = new PermissionContextFactory(Database::getConnection());
        }

        return $factory;
    }
}

if (!function_exists('cm_current_permission_identifier')) {
    function cm_current_permission_identifier(): string
    {
        $resolved = cm_authorization_service()->resolveLegacyRequest($_GET, $_POST, $_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!empty($resolved['slug_permission'])) {
            return (string) $resolved['slug_permission'];
        }

        return (string) ($_GET['page'] ?? '');
    }
}

if (!function_exists('canView')) {
    function canView($codeFonctionnalite = null)
    {
        if (!isset($_SESSION['id_GU'])) {
            return false;
        }

        $identifier = $codeFonctionnalite !== null ? (string) $codeFonctionnalite : cm_current_permission_identifier();
        $allowed = cm_authorization_service()->checkFeaturePermission((int) $_SESSION['id_GU'], $identifier, 'voir');
        if ($identifier === 'gestion_etudiants' || (string) ($_GET['page'] ?? '') === 'gestion_etudiants') {
            $logPath = __DIR__ . '/../../logs/gestion_etudiants.log';
            $fallbackLogPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'gestion_etudiants.log';
            $line = date('c') . ' [gestion_etudiants:perm] canView=' . ($allowed ? '1' : '0');
            @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
            @file_put_contents($fallbackLogPath, $line . PHP_EOL, FILE_APPEND);
        }
        return $allowed;
    }
}

if (!function_exists('canCreate')) {
    function canCreate($codeFonctionnalite = null)
    {
        if (!isset($_SESSION['id_GU'])) {
            return false;
        }

        $identifier = $codeFonctionnalite !== null ? (string) $codeFonctionnalite : cm_current_permission_identifier();
        $allowed = cm_authorization_service()->checkFeaturePermission((int) $_SESSION['id_GU'], $identifier, 'creer');
        if ($identifier === 'gestion_etudiants' || (string) ($_GET['page'] ?? '') === 'gestion_etudiants') {
            $logPath = __DIR__ . '/../../logs/gestion_etudiants.log';
            $fallbackLogPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'gestion_etudiants.log';
            $line = date('c') . ' [gestion_etudiants:perm] canCreate=' . ($allowed ? '1' : '0');
            @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
            @file_put_contents($fallbackLogPath, $line . PHP_EOL, FILE_APPEND);
        }
        return $allowed;
    }
}

if (!function_exists('canEdit')) {
    function canEdit($codeFonctionnalite = null)
    {
        if (!isset($_SESSION['id_GU'])) {
            return false;
        }

        $identifier = $codeFonctionnalite !== null ? (string) $codeFonctionnalite : cm_current_permission_identifier();
        $allowed = cm_authorization_service()->checkFeaturePermission((int) $_SESSION['id_GU'], $identifier, 'modifier');
        if ($identifier === 'gestion_etudiants' || (string) ($_GET['page'] ?? '') === 'gestion_etudiants') {
            $logPath = __DIR__ . '/../../logs/gestion_etudiants.log';
            $fallbackLogPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'gestion_etudiants.log';
            $line = date('c') . ' [gestion_etudiants:perm] canEdit=' . ($allowed ? '1' : '0');
            @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
            @file_put_contents($fallbackLogPath, $line . PHP_EOL, FILE_APPEND);
        }
        return $allowed;
    }
}

if (!function_exists('canDelete')) {
    function canDelete($codeFonctionnalite = null)
    {
        if (!isset($_SESSION['id_GU'])) {
            return false;
        }

        $identifier = $codeFonctionnalite !== null ? (string) $codeFonctionnalite : cm_current_permission_identifier();
        $allowed = cm_authorization_service()->checkFeaturePermission((int) $_SESSION['id_GU'], $identifier, 'supprimer');
        if ($identifier === 'gestion_etudiants' || (string) ($_GET['page'] ?? '') === 'gestion_etudiants') {
            $logPath = __DIR__ . '/../../logs/gestion_etudiants.log';
            $fallbackLogPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'gestion_etudiants.log';
            $line = date('c') . ' [gestion_etudiants:perm] canDelete=' . ($allowed ? '1' : '0');
            @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
            @file_put_contents($fallbackLogPath, $line . PHP_EOL, FILE_APPEND);
        }
        return $allowed;
    }
}

if (!function_exists('showIfCan')) {
    function showIfCan($action, $buttonHtml, $codeFonctionnalite = null)
    {
        $canPerformAction = false;

        switch ($action) {
            case 'creer':
            case 'create':
                $canPerformAction = canCreate($codeFonctionnalite);
                break;
            case 'modifier':
            case 'edit':
                $canPerformAction = canEdit($codeFonctionnalite);
                break;
            case 'supprimer':
            case 'delete':
                $canPerformAction = canDelete($codeFonctionnalite);
                break;
            case 'voir':
            case 'view':
                $canPerformAction = canView($codeFonctionnalite);
                break;
        }

        return $canPerformAction ? $buttonHtml : '';
    }
}

if (!function_exists('showNoPermissionMessage')) {
    function showNoPermissionMessage($action = 'voir', $codeFonctionnalite = null)
    {
        $messages = [
            'voir' => 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.',
            'creer' => 'Vous n\'avez pas l\'autorisation de créer des éléments.',
            'modifier' => 'Vous n\'avez pas l\'autorisation de modifier des éléments.',
            'supprimer' => 'Vous n\'avez pas l\'autorisation de supprimer des éléments.',
        ];

        $message = isset($messages[$action]) ? $messages[$action] : $messages['voir'];

        return '<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">' . htmlspecialchars($message) . '</p>
                        </div>
                    </div>
                </div>';
    }
}

if (!function_exists('getCurrentPermissions')) {
    function getCurrentPermissions($codeFonctionnalite = null)
    {
        $identifier = $codeFonctionnalite !== null ? (string) $codeFonctionnalite : cm_current_permission_identifier();
        $caps = cm_permission_context_factory()->forFeature((int) ($_SESSION['id_GU'] ?? 0), $identifier);

        return [
            'peut_voir' => $caps['view'],
            'peut_creer' => $caps['create'],
            'peut_modifier' => $caps['edit'],
            'peut_supprimer' => $caps['delete'],
        ];
    }
}

if (!function_exists('getPermissionCaps')) {
    function getPermissionCaps($codeFonctionnalite = null)
    {
        $identifier = $codeFonctionnalite !== null ? (string) $codeFonctionnalite : cm_current_permission_identifier();
        return cm_permission_context_factory()->forFeature((int) ($_SESSION['id_GU'] ?? 0), $identifier);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        $adminGroupId = PermissionRegistry::groups()['administrateur'] ?? null;
        return isset($_SESSION['id_GU']) && $adminGroupId !== null && (int) $_SESSION['id_GU'] === (int) $adminGroupId;
    }
}
