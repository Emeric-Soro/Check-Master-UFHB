-- Migration: Add date_operation and id_candidature columns to rapport_etudiants
-- PRD 3: Date système vs date opération
-- PRD 1: Liason rapport-candidature

ALTER TABLE rapport_etudiants 
  ADD COLUMN IF NOT EXISTS `date_operation` datetime DEFAULT NULL COMMENT 'Date métier (opération) modifiable par admin' AFTER `date_redaction_rapport`,
  ADD COLUMN IF NOT EXISTS `id_candidature` int DEFAULT NULL AFTER `num_etu`,
  ADD KEY IF NOT EXISTS `fk_rapport_candidature` (`id_candidature`);

-- Update existing records: set date_operation = date_redaction_rapport for existing rows
UPDATE rapport_etudiants SET date_operation = date_redaction_rapport WHERE date_operation IS NULL;
