<?php
/**
 * Tests des helpers de sécurité (permissions_helper) et du service de permissions.
 */

declare(strict_types=1);

use CheckMaster\Core\Bootstrap;

// ─── permissions_helper (fonctions globales) ────────────────
test('permissions_helper: chargement du fichier de fonctions', function () {
    $file = dirname(__DIR__, 2) . '/app/utils/permissions_helper.php';
    assertTrue(is_file($file), 'permissions_helper.php existe');
    require_once $file;
    $defined = array_filter(get_defined_functions()['user'], fn($f) => str_starts_with($f, 'cm_'));
    assertTrue(count($defined) > 0, 'au moins une fonction cm_* définie (' . implode(',', $defined) . ')');
});

test('permissions_helper: cm_can retourne un booléen pour un groupe inconnu', function () {
    if (function_exists('cm_can')) {
        $result = cm_can('aucune_permission_xyz', -1);
        assertTrue(is_bool($result), 'cm_can renvoie bool');
    }
});

// ─── RoutePermissionService (DB réelle) ─────────────────────
test('RoutePermissionService: constructeur et méthodes existent', function () {
    $pdo = \Database::getConnection();
    $svc = new \CheckMaster\Security\RoutePermissionService($pdo);
    assertTrue(method_exists($svc, 'canAccessLegacy'), 'canAccessLegacy');
    assertTrue(method_exists($svc, 'resolveLegacy'), 'resolveLegacy');
});

test('RoutePermissionService: une requête inconnue sans session est refusée pour un groupe inexistant', function () {
    $pdo = \Database::getConnection();
    $svc = new \CheckMaster\Security\RoutePermissionService($pdo);
    // Groupe 999999 n'existe pas → doit être refusé
    $allowed = $svc->canAccessLegacy(999999, ['page' => 'dashboard'], [], 'GET');
    assertFalse($allowed, 'groupe inexistant doit être refusé');
});

test('RoutePermissionService: resolveLegacy retourne une structure stable', function () {
    $pdo = \Database::getConnection();
    $svc = new \CheckMaster\Security\RoutePermissionService($pdo);
    $res = $svc->resolveLegacy(['page' => 'dashboard'], [], 'GET');
    assertTrue(is_array($res), 'résolution tableau');
    assertTrue(array_key_exists('action', $res), 'clé action');
    assertTrue(array_key_exists('pattern', $res), 'clé pattern');
    assertTrue(array_key_exists('reason', $res), 'clé reason');
    assertTrue(array_key_exists('is_public', $res), 'clé is_public');
});
