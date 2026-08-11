<?php
/**
 * Tests d'intégration Phase 2 : ReferentielCrudService et ParametreService.
 * Lecture seule — aucune écriture en base.
 */

declare(strict_types=1);

use App\Services\ReferentielCrudService;
use App\Support\DatabaseService;

test('ReferentielCrudService: handle retourne la structure historique complète', function () {
    $pdo = \Database::getConnection();
    $crud = new ReferentielCrudService(new DatabaseService($pdo), $pdo);

    $config = [
        'table' => 'genre',
        'id_column' => 'id_genre',
        'fields' => ['libelle_genre'],
        'required_fields' => ['libelle_genre'],
        'order_by' => 'libelle_genre ASC',
    ];

    $result = $crud->handle($config, [], ['action' => 'genre'], 1);

    assertTrue(array_key_exists('item_a_modifier', $result), 'item_a_modifier');
    assertTrue(array_key_exists('listeReferentiel', $result), 'listeReferentiel');
    assertTrue(array_key_exists('messageErreur', $result), 'messageErreur');
    assertTrue(array_key_exists('messageSuccess', $result), 'messageSuccess');
    assertEquals('', $result['messageErreur'], 'pas d\'erreur');
    assertTrue(is_array($result['listeReferentiel']), 'liste tableau');
});

test('ReferentielCrudService: handle sur table inexistante → message erreur', function () {
    $pdo = \Database::getConnection();
    $crud = new ReferentielCrudService(new DatabaseService($pdo), $pdo);

    $config = [
        'table' => 'table_inexistante_xyz',
        'id_column' => 'id',
        'fields' => ['x'],
    ];

    $result = $crud->handle($config, [], ['action' => 'x'], 1);
    assertEquals([], $result['listeReferentiel']);
    assertContains("n'existe pas", $result['messageErreur']);
});

test('ReferentielCrudService: item_a_modifier chargé depuis GET id', function () {
    $pdo = \Database::getConnection();
    $crud = new ReferentielCrudService(new DatabaseService($pdo), $pdo);

    $config = [
        'table' => 'genre',
        'id_column' => 'id_genre',
        'id_param' => 'id_genre',
        'fields' => ['libelle_genre'],
        'order_by' => 'libelle_genre ASC',
    ];

    // id 999999 inexistant → null (pas d'exception)
    $result = $crud->handle($config, [], ['action' => 'genre', 'id_genre' => '999999'], 1);
    assertEquals(null, $result['item_a_modifier']);
});

test('ParametreService: gestionReferentielSimple délègue et garde maitre_stage', function () {
    $pdo = \Database::getConnection();
    $service = new \CheckMaster\Services\ParametreService($pdo);

    // Référentiel simple 'genre' — GET uniquement (lecture)
    $result = $service->gestionReferentielSimple([], ['action' => 'genre'], '1');
    assertTrue(array_key_exists('listeReferentiel', $result), 'listeReferentiel');
    assertEquals('', $result['messageErreur'], 'pas d\'erreur genre');
    assertTrue(is_array($result['listeReferentiel']), 'liste genre tableau');

    // Référentiel inconnu → message "non supporté"
    $unknown = $service->gestionReferentielSimple([], ['action' => 'action_inconnue'], '1');
    assertContains('non supporté', $unknown['messageErreur']);

    // maitre_stage : clés supplémentaires présentes
    $maitre = $service->gestionReferentielSimple([], ['action' => 'maitre_stage'], '1');
    assertTrue(array_key_exists('listeEntreprisesRef', $maitre), 'listeEntreprisesRef');
    assertTrue(array_key_exists('listeFonctionsRef', $maitre), 'listeFonctionsRef');
});
