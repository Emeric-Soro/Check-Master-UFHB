-- Migration pour adapter la table inscriptions comme table unique de gestion de scolarité
-- Chaque ligne = un versement pour un étudiant

-- 1. Modifier les colonnes pour les rendre plus flexibles
ALTER TABLE `inscriptions`
MODIFY `date_inscription` DATETIME NULL DEFAULT NULL,
MODIFY `statut_inscription` VARCHAR(50) NULL DEFAULT 'En cours',
MODIFY `nombre_tranche` INT NULL DEFAULT 1,
MODIFY `montant_paye` DECIMAL(10, 2) NULL DEFAULT 0,
MODIFY `reste_a_payer` DECIMAL(10, 2) NULL DEFAULT 0,
MODIFY `num_versement` INT NULL DEFAULT 1,
MODIFY `date_versement` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
MODIFY `montant_verser` DECIMAL(10, 2) NULL DEFAULT 0,
MODIFY `id_mode_paiement` VARCHAR(50) NULL DEFAULT NULL,
MODIFY `num_piece_mp` VARCHAR(100) NULL DEFAULT NULL,
MODIFY `solde` DECIMAL(10, 2) NULL DEFAULT 0;

-- 2. Renommer id_mode_paiement en methode_paiement pour clarté
ALTER TABLE `inscriptions`
CHANGE `id_mode_paiement` `methode_paiement` VARCHAR(50) NULL DEFAULT NULL;

-- 3. Ajouter un commentaire pour documenter la structure
ALTER TABLE `inscriptions` COMMENT = 'Table unique pour inscriptions et versements - chaque ligne = un versement';

-- Note: La table versements peut être supprimée si elle n'est pas utilisée ailleurs
-- DROP TABLE IF EXISTS `versements`;