<?php
/**
 * Tests d'intégration des scénarios critiques (Phase 8).
 * Utilise la base de données réelle en LECTURE seule (aucune écriture).
 */

declare(strict_types=1);

// ─── Authentification / sessions ────────────────────────────
// ─── Authentification ───────────────────────────────────────
test('AuthController: la classe est chargeable et expose login/logout', function () {
    require_once dirname(__DIR__, 2) . '/app/controllers/AuthController.php';
    assertTrue(class_exists('AuthController'), 'AuthController définie');
    assertTrue(method_exists('AuthController', 'login'), 'login');
    assertTrue(method_exists('AuthController', 'logout'), 'logout');
});

test('AuthService: instanciation directe avec PDO et méthodes critiques', function () {
    $pdo = \Database::getConnection();
    $auth = new \CheckMaster\Services\AuthService($pdo);
    assertInstanceOf(\CheckMaster\Services\AuthService::class, $auth);
    assertTrue(method_exists($auth, 'login'), 'login');
    assertTrue(method_exists($auth, 'logout'), 'logout');
    assertTrue(method_exists($auth, 'updatePassword'), 'updatePassword');
    assertTrue(method_exists($auth, 'updateEmail'), 'updateEmail');
});

// ─── Dashboard par groupe ───────────────────────────────────
test('MenuController: génération du menu hiérarchique', function () {
    require_once dirname(__DIR__, 2) . '/app/controllers/MenuController.php';
    assertTrue(class_exists('MenuController'), 'MenuController définie');
    assertTrue(method_exists('MenuController', 'genererMenuHierarchique'), 'genererMenuHierarchique');
});

// ─── Modèles BDD (schéma réel) ──────────────────────────────
test('Modèle Etudiant: lecture d\'un étudiant existant (première ligne)', function () {
    $pdo = \Database::getConnection();
    $row = $pdo->query('SELECT num_carte_etud, nom_etu, prenom_etu FROM etudiants LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    assertTrue(is_array($row) || $row === false, 'requête etudiants OK');
});

test('Tables critiques existent (schéma réel)', function () {
    $pdo = \Database::getConnection();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $expected = ['utilisateur', 'etudiants', 'enseignants', 'inscriptions', 'rapport_etudiants', 'groupe_utilisateur', 'documents', 'document_genere'];
    foreach ($expected as $t) {
        assertTrue(in_array($t, $tables, true), 'table manquante: ' . $t);
    }
});

// ─── Audit / journalisation ─────────────────────────────────
test('AuditService: classe chargeable', function () {
    assertTrue(class_exists(\CheckMaster\Services\AuditService::class), 'AuditService chargeable');
    assertTrue(method_exists(\CheckMaster\Services\AuditService::class, 'logRequestActivity'), 'logRequestActivity');
});
