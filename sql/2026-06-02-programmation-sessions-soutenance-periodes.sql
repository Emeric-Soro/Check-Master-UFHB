-- Ajout des periodes aux sessions previsionnelles de soutenance.
-- Execution sure: rerunnable sur MySQL/MariaDB.

SET @schema_name := DATABASE();

SET @date_fin_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'programmation_sessions_soutenance'
      AND COLUMN_NAME = 'date_fin'
);

SET @ddl := IF(
    @date_fin_exists = 0,
    'ALTER TABLE programmation_sessions_soutenance ADD COLUMN date_fin DATE NULL DEFAULT NULL AFTER date_debut',
    'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE programmation_sessions_soutenance
SET date_fin = date_debut
WHERE date_fin IS NULL
  AND date_debut IS NOT NULL;
