<?php
/**
 * Tests d'intégration Phase 2 : GestionSallesService migré vers DatabaseService.
 * Lecture seule.
 */

declare(strict_types=1);

test('GestionSallesService: pagination et recherche (lecture seule)', function () {
    $service = new \CheckMaster\Services\GestionSallesService(\Database::getConnection());

    $result = $service->getSallesAvecPagination('', 1, 10);
    assertTrue(array_key_exists('data', $result), 'data');
    assertTrue(array_key_exists('totalPages', $result), 'totalPages');
    assertTrue(array_key_exists('totalItems', $result), 'totalItems');
    assertTrue(is_array($result['data']), 'data tableau');
    assertTrue(is_int($result['totalItems']), 'totalItems int');
    assertTrue(count($result['data']) <= 10, 'limite respectée');

    // Recherche sans résultat → tableau vide, total 0
    $empty = $service->getSallesAvecPagination('zzzz_inexistant_zzzz', 1, 10);
    assertEquals(0, $empty['totalItems']);
    assertEquals([], $empty['data']);
});

test('GestionSallesService: ajouterOuModifierSalle valide le nom requis', function () {
    $service = new \CheckMaster\Services\GestionSallesService(\Database::getConnection());
    $result = $service->ajouterOuModifierSalle(['lib_salle' => '']);
    assertFalse($result['success'], 'nom requis');
    assertContains('requis', $result['message']);
});
