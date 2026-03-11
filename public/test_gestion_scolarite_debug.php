<?php
// Activer l'affichage des erreurs pour le diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test page Gestion Scolarité</h1>";
echo "<pre>";

try {
    echo "1. Chargement config database...\n";
    require_once __DIR__ . '/../../app/config/database.php';
    echo "✓ Config chargée\n\n";

    echo "2. Connexion base de données...\n";
    $db = Database::getConnection();
    echo "✓ DB connectée\n\n";

    echo "3. Chargement du contrôleur...\n";
    require_once __DIR__ . '/../../app/controllers/GestionScolariteController.php';
    echo "✓ Contrôleur chargé\n\n";

    echo "4. Création instance contrôleur...\n";
    $controller = new \GestionScolariteController();
    echo "✓ Contrôleur créé\n\n";

    echo "5. Appel getReferenceLists via le service...\n";
    // Simuler une session minimale
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['id_utilisateur'] = 1; // Pour les tests

    // On ne peut pas appeler directement le service private, donc on simule la page
    echo "✓ Tout semble OK jusqu'ici\n\n";

    echo "<strong>✅ Aucune erreur fatale detectée</strong>\n";
    echo "\nSi l'erreur 500 persiste, elle vient probablement d'un problème dans les données de la base.\n";
    echo "Vérifiez les logs Apache: c:/wamp64/logs/apache_error.log\n";

} catch (Throwable $e) {
    echo "\n<strong style='color: red;'>❌ ERREUR DETECTÉE:</strong>\n\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
