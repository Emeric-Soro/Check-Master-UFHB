<?php
require_once 'app/config/database.php';

$db = Database::getConnection();

echo "=== STRUCTURE TABLE echeances ===\n\n";
try {
    $stmt = $db->query("DESCRIBE echeances");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = $row['Key'] ? " [{$row['Key']}]" : "";
        echo "{$row['Field']}: {$row['Type']}{$key}\n";
    }
    
    echo "\n=== EXEMPLE DE DONNÉES ===\n";
    $stmt = $db->query("SELECT * FROM echeances LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "\nLa table 'echeances' n'existe pas dans la base de données.\n";
    }
}
