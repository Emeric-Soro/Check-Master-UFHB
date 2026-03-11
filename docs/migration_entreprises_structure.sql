-- Migration: Restructuration de la table entreprises
-- Date: 27 février 2026
-- Description: Ajout de nouveaux champs et renommage de lib_entreprise

-- ATTENTION: Ce script suppose que vous avez déjà une table entreprises existante
-- avec le champ lib_entreprise. Si c'est déjà fait, pas besoin d'exécuter ce script.

-- Étape 1: Ajouter les nouveaux champs (si ils n'existent pas déjà)
ALTER TABLE `entreprises`
ADD COLUMN IF NOT EXISTS `lib_long_entreprise` VARCHAR(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' AFTER `id_entreprise`,
ADD COLUMN IF NOT EXISTS `lib_court_en` VARCHAR(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' AFTER `lib_long_entreprise`,
ADD COLUMN IF NOT EXISTS `logo` VARCHAR(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' AFTER `lib_court_en`,
ADD COLUMN IF NOT EXISTS `email` VARCHAR(100) NOT NULL DEFAULT '' AFTER `logo`,
ADD COLUMN IF NOT EXISTS `telephone` VARCHAR(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' AFTER `email`;

-- Étape 2: Migrer les données de lib_entreprise vers lib_long_entreprise (si nécessaire)
-- Cette étape copie les anciennes données vers le nouveau champ
UPDATE `entreprises`
SET
    `lib_long_entreprise` = `lib_entreprise`
WHERE (
        `lib_long_entreprise` IS NULL
        OR `lib_long_entreprise` = ''
    )
    AND `lib_entreprise` IS NOT NULL;

-- Étape 3: Supprimer l'ancienne colonne lib_entreprise (ATTENTION: perte de données si pas de backup)
-- Commenté par sécurité - décommenter seulement si vous êtes sûr
-- ALTER TABLE `entreprises` DROP COLUMN IF EXISTS `lib_entreprise`;

-- Étape 4: Supprimer l'ancienne colonne lien_logo_entreprise si elle existe
-- (maintenant remplacée par 'logo')
-- ALTER TABLE `entreprises` DROP COLUMN IF EXISTS `lien_logo_entreprise`;

-- Vérification: Afficher la structure finale de la table
-- DESCRIBE `entreprises`;

-- Vérification: Compter les entreprises avec les nouveaux champs remplis
-- SELECT
--     COUNT(*) as total_entreprises,
--     SUM(CASE WHEN lib_long_entreprise != '' THEN 1 ELSE 0 END) as avec_nom_long,
--     SUM(CASE WHEN lib_court_en != '' THEN 1 ELSE 0 END) as avec_nom_court,
--     SUM(CASE WHEN email != '' THEN 1 ELSE 0 END) as avec_email,
--     SUM(CASE WHEN telephone != '' THEN 1 ELSE 0 END) as avec_telephone,
--     SUM(CASE WHEN logo != '' THEN 1 ELSE 0 END) as avec_logo
-- FROM `entreprises`;

-- Note: Structure finale attendue
-- --------------------------------------------------
-- CREATE TABLE `entreprises` (
--     `id_entreprise` INT NOT NULL AUTO_INCREMENT,
--     `lib_long_entreprise` VARCHAR(100) NOT NULL,
--     `lib_court_en` VARCHAR(50) NOT NULL,
--     `logo` VARCHAR(256) NOT NULL,
--     `email` VARCHAR(100) NOT NULL,
--     `telephone` VARCHAR(15) NOT NULL,
--     PRIMARY KEY (`id_entreprise`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
-- --------------------------------------------------