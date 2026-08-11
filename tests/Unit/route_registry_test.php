<?php
/**
 * Tests du registre centralisé de routes.
 */

declare(strict_types=1);

use CheckMaster\Core\RouteRegistry;

test('RouteRegistry: enregistrement et résolution exacte page:action', function () {
    $reg = new RouteRegistry();
    $reg->register([
        'name' => 'gestion_etudiants.ajouter',
        'page' => 'gestion_etudiants',
        'action' => 'ajouter_des_etudiants',
        'method' => 'GET',
        'file' => 'gestionEtudiantRoutes.php',
    ]);
    $res = $reg->resolve('gestion_etudiants', 'ajouter_des_etudiants', 'GET');
    assertEquals('gestion_etudiants.ajouter', $res['name']);
    assertEquals('gestionEtudiantRoutes.php', $res['file']);
});

test('RouteRegistry: résolution page seule pour action quelconque', function () {
    $reg = new RouteRegistry();
    $reg->register(['name' => 'dashboard', 'page' => 'dashboard', 'method' => 'GET']);
    $res = $reg->resolve('dashboard', 'nimporte', 'GET');
    assertEquals('dashboard', $res['name']);
});

test('RouteRegistry: page inconnue → null', function () {
    $reg = new RouteRegistry();
    assertEquals(null, $reg->resolve('page_inconnue', '', 'GET'));
});

test('RouteRegistry: has() et count()', function () {
    $reg = new RouteRegistry();
    $reg->register(['name' => 'a', 'page' => 'profil']);
    $reg->register(['name' => 'b', 'page' => 'dashboard', 'action' => 'voir']);
    assertTrue($reg->has('profil'));
    assertTrue($reg->has('dashboard', 'voir'));
    assertFalse($reg->has('rien'));
    assertEquals(2, $reg->count());
    assertEquals(2, count($reg->all()));
});

test('RouteRegistry: loadLegacyFiles charge les fichiers existants', function () {
    $routesDir = dirname(__DIR__, 2) . '/ressources/routes';
    assertTrue(is_dir($routesDir), 'répertoire routes existe');
    $reg = new RouteRegistry();
    $reg->loadLegacyFiles($routesDir, ['auditRoutes.php', 'docviewerRoutes.php']);
    assertTrue(true, 'chargement sans erreur');
});

test('RouteRegistry: méthode ANY accepte toutes les méthodes', function () {
    $reg = new RouteRegistry();
    $reg->register(['name' => 'any', 'page' => 'docviewer', 'method' => 'ANY']);
    assertEquals('any', $reg->resolve('docviewer', '', 'POST')['name']);
    assertEquals('any', $reg->resolve('docviewer', '', 'GET')['name']);
});
