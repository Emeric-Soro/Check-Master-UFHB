<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Début du test...\n";

try {
    require_once __DIR__ . '/app/config/database.php';
    echo "✓ Config chargée\n";

    $db = Database::getConnection();
    echo "✓ DB connectée\n";

    require_once __DIR__ . '/app/models/Scolarite.php';
    echo "✓ Modèle Scolarite chargé\n";

    $scolarite = new Scolarite($db);
    echo "✓ Instance Scolarite créée\n";

    echo "\nTest getAllEtudiants()...\n";
    $etudiants = $scolarite->getAllEtudiants();
    echo "✓ getAllEtudiants: " . count($etudiants) . " résultats\n";

    echo "\nTest getEtudiantsInscrits()...\n";
    $inscrits = $scolarite->getEtudiantsInscrits();
    echo "✓ getEtudiantsInscrits: " . count($inscrits) . " résultats\n";

    if (count($inscrits) > 0) {
        echo "\nPremier étudiant inscrit:\n";
        print_r(array_slice($inscrits[0], 0, 5));
    }

    echo "\nTest getAllVersements()...\n";
    $versements = $scolarite->getAllVersements();
    echo "✓ getAllVersements: " . count($versements) . " résultats\n";

    if (count($versements) > 0) {
        echo "\nPremier versement:\n";
        print_r(array_slice($versements[0], 0, 5));
    }

    echo "\n✅ TOUS LES TESTS PASSÉS\n";
} catch (Throwable $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
