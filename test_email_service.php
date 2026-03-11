<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Test 1: Chargement de vendor/autoload.php\n";
require_once __DIR__ . '/vendor/autoload.php';
echo "✓ vendor/autoload.php chargé\n";

echo "\nTest 2: Vérification du classmap\n";
$classmap = require __DIR__ . '/vendor/composer/autoload_classmap.php';
if (isset($classmap['EmailService'])) {
    echo "✓ EmailService trouvé dans le classmap: " . $classmap['EmailService'] . "\n";
} else {
    echo "✗ EmailService NOT trouvé dans le classmap\n";
}

echo "\nTest 3: Vérification du fichier EmailService.php\n";
$emailServicePath = __DIR__ . '/app/utils/EmailService.php';
if (file_exists($emailServicePath)) {
    echo "✓ Fichier existe: $emailServicePath\n";
} else {
    echo "✗ Fichier n'existe PAS: $emailServicePath\n";
}

echo "\nTest 4: Tentative d'instanciation de EmailService\n";
try {
    $emailService = new EmailService();
    echo "✓ EmailService instancié avec succès!\n";
} catch (Throwable $e) {
    echo "✗ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTest 5: Vérification si la classe existe\n";
if (class_exists('EmailService', false)) {
    echo "✓ La classe EmailService existe\n";
} else {
    echo "✗ La classe EmailService n'existe pas\n";
}

echo "\nFin des tests\n";
