<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/Services/GestionScolariteService.php';

use CheckMaster\Services\GestionScolariteService;

try {
    echo "Test 1: Connexion DB...\n";
    $db = Database::getConnection();
    echo "✓ DB connectée\n\n";

    echo "Test 2: Création du service...\n";
    $service = new GestionScolariteService($db);
    echo "✓ Service créé\n\n";

    echo "Test 3: getReferenceLists()...\n";
    $lists = $service->getReferenceLists();
    echo "✓ getReferenceLists OK\n\n";

    echo "Résultats:\n";
    echo "- Étudiants non inscrits: " . count($lists['etudiantsNonInscrits']) . "\n";
    echo "- Étudiants inscrits: " . count($lists['etudiantsInscrits']) . "\n";
    echo "- Niveaux: " . count($lists['niveaux']) . "\n";
    echo "- Versements: " . count($lists['listeVersement']) . "\n";
    echo "- Années: " . count($lists['listeAnnees']) . "\n";

    echo "\n✅ TOUS LES TESTS PASSÉS\n";
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
