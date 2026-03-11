-- Script SQL pour ajouter les nouveaux champs à la table etudiants
-- Date: 6 février 2026
-- Description: Ajout des champs id_niveau (FK), id_annee_acad (FK), identifiant_mesrs, num_carte_etudiant

-- Ajouter le champ id_niveau comme clé étrangère vers niveau_etude
ALTER TABLE `etudiants`
ADD COLUMN `id_niveau` INT NULL AFTER `promotion_etu`,
ADD KEY `fk_etudiant_niveau` (`id_niveau`);

-- Ajouter la contrainte de clé étrangère pour id_niveau
ALTER TABLE `etudiants`
ADD CONSTRAINT `fk_etudiant_niveau` FOREIGN KEY (`id_niveau`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Ajouter le champ id_annee_acad comme clé étrangère vers annee_academique
ALTER TABLE `etudiants`
ADD COLUMN `id_annee_acad` INT NULL AFTER `id_niveau`,
ADD KEY `fk_etudiant_annee_acad` (`id_annee_acad`);

-- Ajouter la contrainte de clé étrangère pour id_annee_acad
ALTER TABLE `etudiants`
ADD CONSTRAINT `fk_etudiant_annee_acad` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Ajouter le champ identifiant MESRS (E(15))
ALTER TABLE `etudiants`
ADD COLUMN `identifiant_mesrs` VARCHAR(15) NULL AFTER `id_annee_acad`;

-- Vérifier la structure de la table après modification
-- DESCRIBE etudiants;