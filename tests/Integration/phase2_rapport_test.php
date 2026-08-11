<?php
/**
 * Tests d'intégration Phase 2 : GestionRapportService migré vers DatabaseService.
 * Lecture seule.
 */

declare(strict_types=1);

use CheckMaster\Services\GestionRapportService;

test('GestionRapportService: getModeleRapportUrl retourne une URL', function () {
    $svc = new GestionRapportService(\Database::getConnection());
    $url = $svc->getModeleRapportUrl();
    assertTrue(is_string($url) && $url !== '', 'URL non vide');
});
