<?php
/**
 * Tests d'intégration Phase 2 : GestionReclamationsService migré vers DatabaseService.
 * Lecture seule.
 */

declare(strict_types=1);

use CheckMaster\Services\GestionReclamationsService;

function makeReclamationService(): GestionReclamationsService
{
    $pdo = \Database::getConnection();
    return new GestionReclamationsService(
        new \Reclamation($pdo),
        new \AuditLog($pdo)
    );
}

test('GestionReclamationsService: recupererNumEtuParEmail (inexistant → null)', function () {
    $svc = makeReclamationService();
    $num = $svc->recupererNumEtuParEmail('inexistant_' . bin2hex(random_bytes(4)) . '@example.com');
    assertEquals(null, $num, 'email inconnu → null');
});

test('GestionReclamationsService: validerDonneesReclamation', function () {
    $svc = makeReclamationService();
    $erreurs = $svc->validerDonneesReclamation([
        'titre' => 'ab',
        'description' => 'court',
        'type' => 'invalide',
    ]);
    assertTrue(count($erreurs) >= 3, '3 erreurs pour données invalides');

    $ok = $svc->validerDonneesReclamation([
        'titre' => 'Titre valide',
        'description' => 'Description suffisamment longue pour passer la validation',
        'type' => 'Académique',
    ]);
    assertEquals([], $ok, 'aucune erreur pour données valides');
});

test('GestionReclamationsService: construireFiltres et mapTypeFromForm', function () {
    $svc = makeReclamationService();
    $filtres = $svc->construireFiltres(['status' => 'en_cours', 'type' => 'academic']);
    assertEquals('En cours', $filtres['statut'] ?? null);
    assertEquals('Académique', $filtres['type'] ?? null);

    assertEquals('Financière', $svc->mapTypeFromForm('financial'));
    assertEquals('Autre', $svc->mapTypeFromForm('inconnu'));
});

test('GestionReclamationsService: verifierDroitsAdmin', function () {
    $svc = makeReclamationService();
    assertTrue($svc->verifierDroitsAdmin(5));
    assertFalse($svc->verifierDroitsAdmin(13));
});

test('GestionReclamationsService: getHistoriqueReclamation (inexistant → [])', function () {
    $svc = makeReclamationService();
    $hist = $svc->getHistoriqueReclamation(999999);
    assertTrue(is_array($hist), 'historique tableau');
});
