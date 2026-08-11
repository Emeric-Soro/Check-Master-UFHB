<?php
/**
 * Tests d'intégration Phase 2 : CriteresEvaluationService migré vers DatabaseService.
 * Lecture seule + validation sans écriture.
 */

declare(strict_types=1);

use CheckMaster\Services\CriteresEvaluationService;

test('CriteresEvaluationService: getAnneesAcademiques retourne des années', function () {
    $svc = new CriteresEvaluationService(\Database::getConnection());
    $annees = $svc->getAnneesAcademiques();
    assertTrue(is_array($annees), 'tableau');
    foreach ($annees as $a) {
        assertTrue(array_key_exists('id', $a), 'clé id');
        assertTrue(array_key_exists('lib', $a), 'clé lib');
    }
});

test('CriteresEvaluationService: getCriteres retourne la structure groupée', function () {
    $svc = new CriteresEvaluationService(\Database::getConnection());
    $criteres = $svc->getCriteres();
    assertTrue(is_array($criteres), 'tableau');
    foreach ($criteres as $c) {
        assertTrue(array_key_exists('id', $c), 'id');
        assertTrue(array_key_exists('libelle', $c), 'libelle');
        assertTrue(array_key_exists('baremes', $c), 'baremes');
    }
});

test('CriteresEvaluationService: createCritere valide les entrées sans écrire', function () {
    $svc = new CriteresEvaluationService(\Database::getConnection());

    // Libellé manquant → exception
    $caught = false;
    try {
        $svc->createCritere(['baremes' => []]);
    } catch (\Exception $e) {
        $caught = true;
        assertContains('libellé', $e->getMessage());
    }
    assertTrue($caught, 'libellé requis');

    // Barèmes manquants → exception
    $caught2 = false;
    try {
        $svc->createCritere(['libelle' => 'Test']);
    } catch (\Exception $e) {
        $caught2 = true;
        assertContains('barème', $e->getMessage());
    }
    assertTrue($caught2, 'barème requis');
});

test('CriteresEvaluationService: updateCritere et deleteCritere valident les IDs', function () {
    $svc = new CriteresEvaluationService(\Database::getConnection());

    $caught = false;
    try {
        $svc->updateCritere(['libelle' => 'X', 'baremes' => []]);
    } catch (\Exception $e) {
        $caught = true;
        assertContains('ID', $e->getMessage());
    }
    assertTrue($caught, 'ID requis update');

    $caught2 = false;
    try {
        $svc->deleteCritere([]);
    } catch (\Exception $e) {
        $caught2 = true;
        assertContains('ID', $e->getMessage());
    }
    assertTrue($caught2, 'ID requis delete');
});
