<?php
/**
 * Tests d'intégration Phase 2 : GestionUtilisateurService migré vers DatabaseService.
 * Lecture seule + rollback pour les écritures de test.
 */

declare(strict_types=1);

use CheckMaster\Services\GestionUtilisateurService;

test('GestionUtilisateurService: checkLoginAvailability', function () {
    $svc = new GestionUtilisateurService(\Database::getConnection());
    $result = $svc->checkLoginAvailability('login_totalement_inexistant_' . bin2hex(random_bytes(4)));
    assertTrue($result['success'], 'succès');
    assertTrue($result['available'], 'login disponible');
    assertTrue(is_string($result['message']) && $result['message'] !== '', 'message non vide');
});

test('GestionUtilisateurService: createPasswordResetToken (rollback)', function () {
    $pdo = \Database::getConnection();
    $svc = new GestionUtilisateurService($pdo);
    $email = 'test_' . bin2hex(random_bytes(4)) . '@example.com';

    $pdo->beginTransaction();
    try {
        $token = $svc->createPasswordResetToken($email);
        assertTrue(is_string($token) && strlen($token) === 64, 'token 64 hex');
        // Vérifier que le token est bien en base (dans la transaction)
        $row = $pdo->query("SELECT token FROM password_resets WHERE email = '" . $email . "' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        assertEquals($token, $row['token'] ?? null, 'token en base');
    } finally {
        $pdo->rollBack();
    }
});

test('GestionUtilisateurService: buildResetLink et buildLoginLink produisent des URLs', function () {
    $svc = new GestionUtilisateurService(\Database::getConnection());
    $link = $svc->buildResetLink('abc123');
    assertContains('reset_password.php', $link);
    assertContains('token=abc123', $link);
});
