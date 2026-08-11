<?php
/**
 * Tests d'intégration Phase 2 : EvaluationSoutenanceService migré vers DatabaseService.
 * Lecture seule.
 */

declare(strict_types=1);

use CheckMaster\Services\EvaluationSoutenanceService;

test('EvaluationSoutenanceService: getAnneesAcademiques et getCriteresEvaluation', function () {
    $svc = new EvaluationSoutenanceService(\Database::getConnection());
    $annees = $svc->getAnneesAcademiques();
    assertTrue(is_array($annees), 'années tableau');
    $criteres = $svc->getCriteresEvaluation();
    assertTrue(is_array($criteres), 'critères tableau');
});

test('EvaluationSoutenanceService: getSoutenancesProgrammeesForView sans données', function () {
    $svc = new EvaluationSoutenanceService(\Database::getConnection());
    // Appel sans session année → ne doit pas lever d'exception
    $result = $svc->getSoutenancesProgrammeesForView();
    assertTrue(is_array($result), 'résultat tableau');
});

test('EvaluationSoutenanceService: calculerMention retourne une mention', function () {
    $svc = new EvaluationSoutenanceService(\Database::getConnection());
    $mention = $svc->calculerMention(18.5);
    assertTrue(is_string($mention) && $mention !== '', 'mention non vide');
    $mention2 = $svc->calculerMention(10.0);
    assertTrue(is_string($mention2), 'mention 10');
});

test('EvaluationSoutenanceService: searchSoutenances (lecture)', function () {
    $svc = new EvaluationSoutenanceService(\Database::getConnection());
    $result = $svc->searchSoutenances('zzzz_inexistant');
    assertTrue(is_array($result), 'recherche retourne tableau');
});
