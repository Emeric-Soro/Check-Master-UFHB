<?php
/**
 * Tests des briques communes Phase 2 (DatabaseService, EntityLoader,
 * PaginationService, BusinessLogger, Validator).
 */

declare(strict_types=1);

use App\Support\DatabaseService;
use App\Support\EntityLoader;
use App\Support\PaginationService;
use App\Support\BusinessLogger;
use App\Support\Validator;

// ─── DatabaseService ────────────────────────────────────────
test('DatabaseService: select/selectOne/scalar/execute', function () {
    $db = new DatabaseService(\Database::getConnection());
    $rows = $db->select('SELECT id_GU, lib_GU FROM groupe_utilisateur LIMIT 2');
    assertTrue(is_array($rows), 'select retourne tableau');
    assertTrue(count($rows) <= 2, 'limite respectée');

    $one = $db->selectOne('SELECT lib_GU FROM groupe_utilisateur WHERE id_GU = :id', [':id' => -999]);
    assertEquals(null, $one, 'selectOne inexistant → null');

    $scalar = $db->scalar('SELECT COUNT(*) FROM groupe_utilisateur');
    assertTrue(is_int($scalar) || is_numeric($scalar), 'scalar compte');

    $affected = $db->execute('UPDATE groupe_utilisateur SET lib_GU = lib_GU WHERE id_GU = -999');
    assertEquals(0, $affected, 'update inexistant → 0 ligne');
});

test('DatabaseService: insert/update/delete/transaction', function () {
    $db = new DatabaseService(\Database::getConnection());
    // Table temporaire de test pour ne pas toucher aux données réelles
    $db->execute('CREATE TEMPORARY TABLE cm_test_phase2 (id INT AUTO_INCREMENT PRIMARY KEY, label VARCHAR(50))');

    $id = $db->insert('cm_test_phase2', ['label' => 'test']);
    assertTrue($id > 0, 'insert retourne un ID');

    $affected = $db->update('cm_test_phase2', ['label' => 'modifié'], ['id' => $id]);
    assertEquals(1, $affected, 'update 1 ligne');

    $row = $db->selectOne('SELECT label FROM cm_test_phase2 WHERE id = :id', [':id' => $id]);
    assertEquals('modifié', $row['label']);

    $deleted = $db->delete('cm_test_phase2', ['id' => $id]);
    assertEquals(1, $deleted, 'delete 1 ligne');
});

test('DatabaseService: paginate retourne la structure standard', function () {
    $db = new DatabaseService(\Database::getConnection());
    $res = $db->paginate(
        'SELECT COUNT(*) FROM groupe_utilisateur',
        'SELECT * FROM groupe_utilisateur',
        [],
        1,
        5
    );
    assertTrue(array_key_exists('data', $res), 'data');
    assertTrue(array_key_exists('total', $res), 'total');
    assertTrue(array_key_exists('last_page', $res), 'last_page');
    assertEquals(1, $res['page']);
    assertEquals(5, $res['per_page']);
});

test('DatabaseService: validation des noms de colonnes (anti-injection)', function () {
    $db = new DatabaseService(\Database::getConnection());
    $caught = false;
    try {
        $db->column('id; DROP TABLE etudiants');
    } catch (\InvalidArgumentException $e) {
        $caught = true;
    }
    assertTrue($caught, 'colonne invalide rejetée');
});

// ─── EntityLoader ───────────────────────────────────────────
test('EntityLoader: positiveInt et nonEmptyString', function () {
    $loader = new EntityLoader(new DatabaseService(\Database::getConnection()));
    assertEquals(5, $loader->positiveInt('5'));
    $caught = false;
    try {
        $loader->positiveInt('abc');
    } catch (\InvalidArgumentException $e) {
        $caught = true;
    }
    assertTrue($caught, 'id non numérique rejeté');
    assertEquals('abc', $loader->nonEmptyString(' abc '));
});

test('EntityLoader: findById / findOrFail / exists', function () {
    $loader = new EntityLoader(new DatabaseService(\Database::getConnection()));
    assertEquals(null, $loader->findById('groupe_utilisateur', 'id_GU', 999999));
    assertFalse($loader->exists('groupe_utilisateur', 'id_GU', 999999));

    $caught = false;
    try {
        $loader->findOrFail('groupe_utilisateur', 'id_GU', 999999);
    } catch (\RuntimeException $e) {
        $caught = true;
    }
    assertTrue($caught, 'findOrFail lève sur absence');
});

// ─── PaginationService ──────────────────────────────────────
test('PaginationService: fromQuery normalise page et limite', function () {
    $p = new PaginationService();
    $res = $p->fromQuery(['page' => '2', 'per_page' => '50'], 20, [10, 20, 50, 100]);
    assertEquals(2, $res['page']);
    assertEquals(50, $res['per_page']);
    assertEquals(50, $res['offset']);

    // Valeur non autorisée → défaut
    $res2 = $p->fromQuery(['page' => '0', 'per_page' => '7'], 20, [10, 20, 50, 100]);
    assertEquals(1, $res2['page']);
    assertEquals(20, $res2['per_page']);
});

test('PaginationService: result construit last_page', function () {
    $p = new PaginationService();
    $res = $p->result([['a' => 1]], 25, 2, 10);
    assertEquals(25, $res['total']);
    assertEquals(3, $res['last_page']);
});

// ─── BusinessLogger ─────────────────────────────────────────
test('BusinessLogger: écrit un log JSON dans le répertoire configuré', function () {
    $dir = sys_get_temp_dir() . '/cm_log_test_' . bin2hex(random_bytes(4));
    $logger = new BusinessLogger($dir);
    $logger->info('test message', ['entite' => 'test']);
    $file = $dir . '/business.log';
    assertTrue(is_file($file), 'fichier de log créé');
    $content = file_get_contents($file);
    assertContains('test message', $content);
    assertContains('"level":"info"', $content);
    // Nettoyage
    @unlink($file);
    @rmdir($dir);
});

// ─── Validator ──────────────────────────────────────────────
test('Validator: email, date, positiveInt, length, inList, requiredFields', function () {
    $v = new Validator();
    assertTrue($v->email('test@example.com'));
    assertFalse($v->email('pas-un-email'));
    assertTrue($v->date('2026-08-03'));
    assertFalse($v->date('03/08/2026'));
    assertTrue($v->positiveInt('42'));
    assertFalse($v->positiveInt('-3'));
    assertTrue($v->length('abc', 2, 5));
    assertFalse($v->length('abcdef', 2, 5));
    assertTrue($v->inList('a', ['a', 'b']));
    assertFalse($v->inList('c', ['a', 'b']));
    assertEquals(['nom'], $v->requiredFields(['prenom' => 'x'], ['nom', 'prenom']));
});
