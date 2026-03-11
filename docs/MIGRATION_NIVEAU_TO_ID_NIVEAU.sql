-- Script de migration SQL si vous aviez déjà créé les champs "niveau" VARCHAR et "annee_a" VARCHAR
-- Date: 6 février 2026
-- Description: Remplacer niveau par id_niveau et annee_a par id_annee_acad (clés étrangères)

-- Étape 1: Supprimer les anciens champs VARCHAR si ils existent déjà
-- Note: Si ces colonnes n'existent pas, vous verrez une erreur que vous pouvez ignorer
-- Ou commentez ces lignes si les colonnes n'ont jamais été créées

SET @dbname = DATABASE();

SET @tablename = 'etudiants';

SET @columnname1 = 'niveau';

SET @columnname2 = 'annee_a';

-- Supprimer la colonne 'niveau' si elle existe
SET
    @s1 = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND COLUMN_NAME = @columnname1
                ) > 0, 'ALTER TABLE `etudiants` DROP COLUMN `niveau`', 'SELECT "La colonne niveau n\'existe pas"'
            )
    );

PREPARE stmt1 FROM @s1;

EXECUTE stmt1;

DEALLOCATE PREPARE stmt1;

-- Supprimer la colonne 'annee_a' si elle existe
SET
    @s2 = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND COLUMN_NAME = @columnname2
                ) > 0, 'ALTER TABLE `etudiants` DROP COLUMN `annee_a`', 'SELECT "La colonne annee_a n\'existe pas"'
            )
    );

PREPARE stmt2 FROM @s2;

EXECUTE stmt2;

DEALLOCATE PREPARE stmt2;

-- Étape 2: Ajouter le champ id_niveau si il n'existe pas encore
SET
    @s3 = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND COLUMN_NAME = 'id_niveau'
                ) = 0, 'ALTER TABLE `etudiants` ADD COLUMN `id_niveau` INT NULL AFTER `promotion_etu`', 'SELECT "La colonne id_niveau existe déjà"'
            )
    );

PREPARE stmt3 FROM @s3;

EXECUTE stmt3;

DEALLOCATE PREPARE stmt3;

-- Étape 3: Ajouter l'index pour id_niveau si il n'existe pas
SET
    @s4 = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE
                        TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND INDEX_NAME = 'fk_etudiant_niveau'
                ) = 0, 'ALTER TABLE `etudiants` ADD KEY `fk_etudiant_niveau` (`id_niveau`)', 'SELECT "L\'index fk_etudiant_niveau existe déjà"'
            )
    );

PREPARE stmt4 FROM @s4;

EXECUTE stmt4;

DEALLOCATE PREPARE stmt4;

-- Étape 4: Ajouter la contrainte de clé étrangère pour id_niveau si elle n'existe pas
SET
    @s5 = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE
                        TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND CONSTRAINT_NAME = 'fk_etudiant_niveau'
                ) = 0, 'ALTER TABLE `etudiants` ADD CONSTRAINT `fk_etudiant_niveau` FOREIGN KEY (`id_niveau`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT "La contrainte fk_etudiant_niveau existe déjà"'
            )
    );

PREPARE stmt5 FROM @s5;

EXECUTE stmt5;

DEALLOCATE PREPARE stmt5;

-- Étape 5: Ajouter le champ id_annee_acad si il n'existe pas encore
SET
    @s6 = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND COLUMN_NAME = 'id_annee_acad'
                ) = 0, 'ALTER TABLE `etudiants` ADD COLUMN `id_annee_acad` INT NULL AFTER `id_niveau`', 'SELECT "La colonne id_annee_acad existe déjà"'
            )
    );

PREPARE stmt6 FROM @s6;

EXECUTE stmt6;

DEALLOCATE PREPARE stmt6;

-- Étape 6: Ajouter l'index pour id_annee_acad si il n'existe pas
SET
    @s7 = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE
                        TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND INDEX_NAME = 'fk_etudiant_annee_acad'
                ) = 0, 'ALTER TABLE `etudiants` ADD KEY `fk_etudiant_annee_acad` (`id_annee_acad`)', 'SELECT "L\'index fk_etudiant_annee_acad existe déjà"'
            )
    );

PREPARE stmt7 FROM @s7;

EXECUTE stmt7;

DEALLOCATE PREPARE stmt7;

-- Étape 7: Ajouter la contrainte de clé étrangère pour id_annee_acad si elle n'existe pas
SET
    @s8 = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE
                        TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND CONSTRAINT_NAME = 'fk_etudiant_annee_acad'
                ) = 0, 'ALTER TABLE `etudiants` ADD CONSTRAINT `fk_etudiant_annee_acad` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT "La contrainte fk_etudiant_annee_acad existe déjà"'
            )
    );

PREPARE stmt8 FROM @s8;

EXECUTE stmt8;

DEALLOCATE PREPARE stmt8;

-- Étape 8: Ajouter le champ identifiant MESRS si il n'existe pas déjà
SET
    @s9 = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE
                        TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND COLUMN_NAME = 'identifiant_mesrs'
                ) = 0, 'ALTER TABLE `etudiants` ADD COLUMN `identifiant_mesrs` VARCHAR(15) NULL AFTER `id_annee_acad`', 'SELECT "La colonne identifiant_mesrs existe déjà"'
            )
    );

PREPARE stmt9 FROM @s9;

EXECUTE stmt9;

DEALLOCATE PREPARE stmt9;

-- Vérifier la structure de la table après modification
-- DESCRIBE etudiants;