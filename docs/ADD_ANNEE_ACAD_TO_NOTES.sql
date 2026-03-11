-- ============================================
-- Migration : Ajout de l'année académique à la table notes
-- Date : 13 février 2026
-- Description : Lie la table notes avec la table annee_academique
-- ============================================

-- Étape 1 : Ajouter la colonne id_annee_acad
ALTER TABLE `notes`
ADD COLUMN `id_annee_acad` INT NULL AFTER `num_etu`;

-- Étape 2 : Ajouter l'index
ALTER TABLE `notes` ADD KEY `fk_notes_annee_acad` (`id_annee_acad`);

-- Étape 3 : Ajouter la contrainte de clé étrangère
ALTER TABLE `notes`
ADD CONSTRAINT `fk_notes_annee_acad` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Vérifier la structure de la table
DESCRIBE `notes`;