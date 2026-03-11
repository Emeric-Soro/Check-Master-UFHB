-- Migration: Ajout du champ fiche_inscription à la table inscriptions
-- Date: 27 février 2026
-- Description: Permet de stocker le chemin vers la fiche d'inscription (PDF ou image) de l'étudiant

-- Étape 1: Ajouter la colonne fiche_inscription (nullable car les inscriptions existantes n'ont pas de fiche)
ALTER TABLE `inscriptions`
ADD COLUMN `fiche_inscription` VARCHAR(255) NULL COMMENT 'Chemin vers le fichier de la fiche d\'inscription (PDF ou image)' AFTER `solde`;

-- Vérification de la structure
-- DESCRIBE `inscriptions`;

-- Note: Les formats acceptés côté application seront : PDF, JPG, JPEG, PNG
-- Les fichiers seront stockés dans : ressources/uploads/fiches_inscription/