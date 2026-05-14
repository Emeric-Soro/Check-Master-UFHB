<?php
/**
 * Migration 013: Add date_operation and id_candidature to rapport_etudiants
 * 
 * PRD 3: Distinction date système vs date opération
 * Permet à l'admin de définir une date métier différente de la date système
 */

require_once __DIR__ . '/../app/config/database.php';

echo "=== Migration 013: Ajout date_operation/id_candidature dans rapport_etudiants ===\n\n";

$pdo = Database::getConnection();
$success = true;

// Helper function to check if a column exists
function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool) $stmt->fetch();
}

try {
    // 1. Add date_operation column
    if (!columnExists($pdo, 'rapport_etudiants', 'date_operation')) {
        $pdo->exec("ALTER TABLE `rapport_etudiants` 
                    ADD COLUMN `date_operation` datetime DEFAULT NULL COMMENT 'Date métier (opération) modifiable par admin' 
                    AFTER `date_redaction_rapport`");
        echo "✓ Colonne `date_operation` ajoutée\n";
        
        // Set date_operation = date_redaction_rapport for existing records
        $pdo->exec("UPDATE `rapport_etudiants` SET `date_operation` = `date_redaction_rapport` WHERE `date_operation` IS NULL");
        echo "✓ Valeurs initiales de date_operation définies depuis date_redaction_rapport\n";
    } else {
        echo "→ Colonne `date_operation` existe déjà\n";
    }

    // 2. Add id_candidature column if not exists
    if (!columnExists($pdo, 'rapport_etudiants', 'id_candidature')) {
        $pdo->exec("ALTER TABLE `rapport_etudiants` 
                    ADD COLUMN `id_candidature` int DEFAULT NULL AFTER `num_etu`,
                    ADD KEY `fk_rapport_candidature` (`id_candidature`)");
        echo "✓ Colonne `id_candidature` ajoutée\n";
    } else {
        echo "→ Colonne `id_candidature` existe déjà\n";
    }

} catch (Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    $success = false;
}

echo "\n";
echo $success ? "✓ Migration terminée avec succès!\n" : "✗ Migration terminée avec des erreurs.\n";
