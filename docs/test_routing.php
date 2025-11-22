<?php
/**
 * Test script for the new routing system
 * This script tests RouterHelper and route generation
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/utils/RouterHelper.php';

echo "=== Test du Système de Routage ===\n\n";

// Initialize AltoRouter
$router = new AltoRouter();

// Load routes
require_once __DIR__ . '/../app/config/routes.php';

// Initialize RouterHelper
RouterHelper::init($router);

echo "1. Test de génération d'URLs avec AltoRouter:\n";
echo "   - Route 'dashboard': " . RouterHelper::route('dashboard') . "\n";
echo "   - Route 'gestion_utilisateurs': " . RouterHelper::route('gestion_utilisateurs') . "\n";
echo "   - Route 'gestion_etudiants': " . RouterHelper::route('gestion_etudiants') . "\n";
echo "   - Route 'parametres_generaux': " . RouterHelper::route('parametres_generaux') . "\n";
echo "\n";

echo "2. Test de génération d'URLs avec paramètres:\n";
echo "   - Route 'etudiants_imprimer_recu' (id=123): " . RouterHelper::route('etudiants_imprimer_recu', ['id' => 123]) . "\n";
echo "   - Route 'scolarite_imprimer_recu' (id=456): " . RouterHelper::route('scolarite_imprimer_recu', ['id' => 456]) . "\n";
echo "\n";

echo "3. Test de mapping ancien système vers nouveau:\n";
echo "   - Page 'gestion_etudiants' -> " . RouterHelper::mapOldPageToRoute('gestion_etudiants') . "\n";
echo "   - Page 'gestion_etudiants', action 'ajouter_des_etudiants' -> " . RouterHelper::mapOldPageToRoute('gestion_etudiants', 'ajouter_des_etudiants') . "\n";
echo "   - Page 'parametres_generaux', action 'annees_academiques' -> " . RouterHelper::mapOldPageToRoute('parametres_generaux', 'annees_academiques') . "\n";
echo "\n";

echo "4. Test de matching de routes:\n";
$testUrls = [
    '/',
    '/dashboard',
    '/utilisateurs',
    '/etudiants',
    '/etudiants/ajouter',
    '/rapports/creer',
    '/parametres/annees-academiques',
];

foreach ($testUrls as $url) {
    $_SERVER['REQUEST_URI'] = $url;
    $match = $router->match($url);
    if ($match) {
        echo "   ✓ URL '$url' -> " . $match['target'] . " (route: " . $match['name'] . ")\n";
    } else {
        echo "   ✗ URL '$url' -> Aucune correspondance\n";
    }
}
echo "\n";

echo "5. Test de fallback pour routes non définies:\n";
echo "   - Route non existante 'test_route': " . RouterHelper::route('test_route') . "\n";
echo "\n";

echo "=== Tests terminés ===\n";
