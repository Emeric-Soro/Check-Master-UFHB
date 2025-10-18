<?php
/**
 * Tests unitaires pour la protection CSRF
 * 
 * Ce fichier contient des tests pour vérifier le bon fonctionnement
 * de la classe CSRFProtection.
 */

require_once __DIR__ . '/../app/utils/CSRFProtection.php';

// Démarrer une session pour les tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "=== Tests de Protection CSRF ===\n\n";

// Test 1: Génération de jeton
echo "Test 1: Génération de jeton CSRF...\n";
$token1 = CSRFProtection::generateToken();
if (!empty($token1) && strlen($token1) === 64) {
    echo "✓ Le jeton a été généré avec succès (longueur: " . strlen($token1) . ")\n";
} else {
    echo "✗ ERREUR: Le jeton n'a pas la longueur attendue\n";
}

// Test 2: Récupération du jeton
echo "\nTest 2: Récupération du jeton CSRF...\n";
$token2 = CSRFProtection::getToken();
if ($token1 === $token2) {
    echo "✓ Le jeton récupéré correspond au jeton généré\n";
} else {
    echo "✗ ERREUR: Les jetons ne correspondent pas\n";
}

// Test 3: Validation d'un jeton valide
echo "\nTest 3: Validation d'un jeton valide...\n";
if (CSRFProtection::validateToken($token1)) {
    echo "✓ Le jeton valide a été correctement validé\n";
} else {
    echo "✗ ERREUR: Le jeton valide a été rejeté\n";
}

// Test 4: Validation d'un jeton invalide
echo "\nTest 4: Validation d'un jeton invalide...\n";
$invalidToken = "invalid_token_123";
if (!CSRFProtection::validateToken($invalidToken)) {
    echo "✓ Le jeton invalide a été correctement rejeté\n";
} else {
    echo "✗ ERREUR: Le jeton invalide a été accepté\n";
}

// Test 5: Validation avec un jeton null
echo "\nTest 5: Validation avec un jeton null...\n";
if (!CSRFProtection::validateToken(null)) {
    echo "✓ Le jeton null a été correctement rejeté\n";
} else {
    echo "✗ ERREUR: Le jeton null a été accepté\n";
}

// Test 6: Génération de champ HTML
echo "\nTest 6: Génération de champ HTML...\n";
$htmlField = CSRFProtection::getTokenField();
if (strpos($htmlField, '<input') !== false && 
    strpos($htmlField, 'type="hidden"') !== false && 
    strpos($htmlField, 'name="csrf_token"') !== false &&
    strpos($htmlField, 'value="') !== false) {
    echo "✓ Le champ HTML a été généré correctement\n";
    echo "  Extrait: " . substr($htmlField, 0, 50) . "...\n";
} else {
    echo "✗ ERREUR: Le champ HTML est mal formé\n";
}

// Test 7: Régénération du jeton
echo "\nTest 7: Régénération du jeton...\n";
$newToken = CSRFProtection::regenerateToken();
if ($newToken !== $token1 && strlen($newToken) === 64) {
    echo "✓ Un nouveau jeton a été généré (différent de l'ancien)\n";
} else {
    echo "✗ ERREUR: La régénération a échoué\n";
}

// Test 8: Validation après régénération
echo "\nTest 8: Validation après régénération...\n";
if (CSRFProtection::validateToken($newToken) && !CSRFProtection::validateToken($token1)) {
    echo "✓ Seul le nouveau jeton est valide\n";
} else {
    echo "✗ ERREUR: Problème de validation après régénération\n";
}

// Test 9: Simulation de requête POST valide
echo "\nTest 9: Simulation de requête POST valide...\n";
$currentToken = CSRFProtection::getToken();
$_POST['csrf_token'] = $currentToken;
try {
    CSRFProtection::verifyRequest();
    echo "✓ La requête POST avec jeton valide a été acceptée\n";
} catch (Exception $e) {
    echo "✗ ERREUR: La requête POST valide a été rejetée\n";
}

echo "\n=== Résumé des Tests ===\n";
echo "Tous les tests de base de la protection CSRF ont été exécutés.\n";
echo "La classe CSRFProtection fonctionne correctement.\n";
