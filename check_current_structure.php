<?php
require_once 'app/config/database.php';

$pdo = Database::getConnection();

echo "=== Structure actuelle de inscriptions ===\n";
$stmt = $pdo->query("DESCRIBE inscriptions");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

echo "\n=== Données actuelles dans inscriptions ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscriptions");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total inscriptions: " . $result['total'] . "\n";

echo "\n=== Structure de versements ===\n";
$stmt = $pdo->query("DESCRIBE versements");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}

echo "\n=== Données actuelles dans versements ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM versements");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total versements: " . $result['total'] . "\n";
