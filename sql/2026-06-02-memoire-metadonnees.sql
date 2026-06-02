-- Metadonnees propres aux memoires mis en ligne.
-- Permet de stocker un theme de memoire distinct du theme du rapport de stage.

CREATE TABLE IF NOT EXISTS memoire_metadonnees (
    id_memoire_metadata INT NOT NULL AUTO_INCREMENT,
    id_document BIGINT UNSIGNED NOT NULL,
    id_rapport INT NULL,
    num_etu VARCHAR(25) NOT NULL,
    theme_memoire VARCHAR(500) NOT NULL,
    num_session_previsionnelle TINYINT NULL,
    date_debut_session DATE NULL,
    date_fin_session DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_memoire_metadata),
    UNIQUE KEY uq_memoire_metadata_document (id_document),
    KEY idx_memoire_metadata_rapport (id_rapport),
    KEY idx_memoire_metadata_num_etu (num_etu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @schema_name := DATABASE();

SET @has_num_session := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'memoire_metadonnees'
      AND COLUMN_NAME = 'num_session_previsionnelle'
);
SET @ddl := IF(
    @has_num_session = 0,
    'ALTER TABLE memoire_metadonnees ADD COLUMN num_session_previsionnelle TINYINT NULL AFTER theme_memoire',
    'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_date_debut_session := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'memoire_metadonnees'
      AND COLUMN_NAME = 'date_debut_session'
);
SET @ddl := IF(
    @has_date_debut_session = 0,
    'ALTER TABLE memoire_metadonnees ADD COLUMN date_debut_session DATE NULL AFTER num_session_previsionnelle',
    'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_date_fin_session := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'memoire_metadonnees'
      AND COLUMN_NAME = 'date_fin_session'
);
SET @ddl := IF(
    @has_date_fin_session = 0,
    'ALTER TABLE memoire_metadonnees ADD COLUMN date_fin_session DATE NULL AFTER date_debut_session',
    'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO memoire_metadonnees (id_document, id_rapport, num_etu, theme_memoire)
SELECT
    d.id_document,
    r.id_rapport,
    COALESCE(e.num_ident_etud, e.num_carte_etud, r.num_etu) AS num_etu,
    COALESCE(NULLIF(r.theme_rapport, ''), 'Theme memoire non renseigne') AS theme_memoire
FROM documents d
INNER JOIN rapport_etudiants r ON CAST(r.id_rapport AS CHAR) = d.entite_id
LEFT JOIN etudiants e ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
WHERE d.entite_type = 'rapport_etudiants'
  AND d.type_document = 'memoire'
ON DUPLICATE KEY UPDATE
    id_rapport = VALUES(id_rapport),
    num_etu = VALUES(num_etu),
    theme_memoire = VALUES(theme_memoire),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO memoire_metadonnees (id_document, id_rapport, num_etu, theme_memoire)
SELECT
    d.id_document,
    NULL,
    COALESCE(e.num_ident_etud, e.num_carte_etud, ps.num_etud) AS num_etu,
    COALESCE(NULLIF(ps.theme_soutenance, ''), 'Theme memoire non renseigne') AS theme_memoire
FROM documents d
INNER JOIN programmer_soutenance ps ON CAST(ps.num_soutenance AS CHAR) = d.entite_id
LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
WHERE d.entite_type = 'programmer_soutenance'
  AND d.type_document = 'memoire'
ON DUPLICATE KEY UPDATE
    num_etu = VALUES(num_etu),
    theme_memoire = VALUES(theme_memoire),
    updated_at = CURRENT_TIMESTAMP;
