<?php
require_once 'app/config/database.php';

$pdo = Database::getConnection();

echo "=== Exécution de la migration ===\n\n";

$migrations = [
    "ALTER TABLE `inscriptions` MODIFY `date_inscription` DATETIME NULL DEFAULT NULL",
    "ALTER TABLE `inscriptions` MODIFY `statut_inscription` VARCHAR(50) NULL DEFAULT 'En cours'",
    "ALTER TABLE `inscriptions` MODIFY `nombre_tranche` INT NULL DEFAULT 1",
    "ALTER TABLE `inscriptions` MODIFY `montant_paye` DECIMAL(10,2) NULL DEFAULT 0",
    "ALTER TABLE `inscriptions` MODIFY `reste_a_payer` DECIMAL(10,2) NULL DEFAULT 0",
    "ALTER TABLE `inscriptions` MODIFY `num_versement` INT NULL DEFAULT 1",
    "ALTER TABLE `inscriptions` MODIFY `date_versement` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE `inscriptions` MODIFY `montant_verser` DECIMAL(10,2) NULL DEFAULT 0",
    "ALTER TABLE `inscriptions` MODIFY `num_piece_mp` VARCHAR(100) NULL DEFAULT NULL",
    "ALTER TABLE `inscriptions` MODIFY `solde` DECIMAL(10,2) NULL DEFAULT 0",
    "ALTER TABLE `inscriptions` CHANGE `id_mode_paiement` `methode_paiement` VARCHAR(50) NULL DEFAULT NULL"
];

foreach ($migrations as $index => $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ Migration " . ($index + 1) . " réussie\n";
    } catch (Exception $e) {
        echo "✗ Migration " . ($index + 1) . " échouée: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Nouvelle structure de inscriptions ===\n";
$stmt = $pdo->query("DESCRIBE inscriptions");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . " | Default: " . $row['Default'] . "\n";
}

echo "\n✓ Migration terminée avec succès!\n";
