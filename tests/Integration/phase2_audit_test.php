<?php
/**
 * Tests d'intégration Phase 2 : AuditService migré vers DatabaseService.
 * Lecture seule.
 */

declare(strict_types=1);

use CheckMaster\Services\AuditService;

function makeAuditService(): AuditService
{
    return new AuditService(\Database::getConnection());
}

test('AuditService: getAllActions retourne une liste plate', function () {
    $svc = makeAuditService();
    $actions = $svc->getAllActions();
    assertTrue(is_array($actions), 'tableau');
    foreach ($actions as $a) {
        assertTrue(is_string($a), 'élément string');
    }
});

test('AuditService: getTablesList et getStatutsList (listes plates)', function () {
    $svc = makeAuditService();
    $tables = $svc->getTablesList();
    $statuts = $svc->getStatutsList();
    assertTrue(is_array($tables), 'tables tableau');
    assertTrue(is_array($statuts), 'statuts tableau');
    foreach ($tables as $t) {
        assertTrue(is_string($t), 'table string');
    }
    foreach ($statuts as $s) {
        assertTrue(is_string($s), 'statut string');
    }
});

test('AuditService: extractFilters normalise les paramètres', function () {
    $svc = makeAuditService();
    $filters = $svc->extractFilters(['date_debut' => '2026-01-01', 'action' => 'X']);
    assertEquals('2026-01-01', $filters['date_debut']);
    assertEquals('X', $filters['action']);
    assertEquals('', $filters['statut']);
});

test('AuditService: deleteSingleLog avec id invalide', function () {
    $svc = makeAuditService();
    $result = $svc->deleteSingleLog(0, 1);
    assertEquals('invalid_id', $result['message']);
    assertFalse($result['success']);
});

test('AuditService: cleanupLogs avec jours invalides', function () {
    $svc = makeAuditService();
    $result = $svc->cleanupLogs(0, 1);
    assertEquals('invalid_days', $result['message']);
    assertFalse($result['success']);
});
