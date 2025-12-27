-- Seed: Peuplement des tables de permissions pour CheckMaster
-- Version: 1.0
-- Date: 2024-12-27
-- Description: Migration des données existantes et configuration initiale des permissions

-- ============================================
-- Migration des données existantes de 'rattacher' vers 'permissions_actions'
-- ============================================
-- Copie les attributions existantes avec permission de lecture par défaut
INSERT INTO permissions_actions (id_GU, id_traitement, peut_lire)
SELECT id_GU, id_traitement, TRUE FROM rattacher
ON DUPLICATE KEY UPDATE peut_lire = TRUE;

-- ============================================
-- Configuration des niveaux hiérarchiques des groupes
-- ============================================
-- Plus le niveau est élevé, plus le groupe a de privilèges
UPDATE groupe_utilisateur SET niveau_hierarchique = 100 WHERE id_GU = 5;   -- Administrateur
UPDATE groupe_utilisateur SET niveau_hierarchique = 80 WHERE id_GU = 8;    -- Responsable scolarité
UPDATE groupe_utilisateur SET niveau_hierarchique = 70 WHERE id_GU = 11;   -- Commission (si existe)
UPDATE groupe_utilisateur SET niveau_hierarchique = 60 WHERE id_GU = 9;    -- Responsable Filière
UPDATE groupe_utilisateur SET niveau_hierarchique = 50 WHERE id_GU = 10;   -- Responsable niveau
UPDATE groupe_utilisateur SET niveau_hierarchique = 40 WHERE id_GU = 6;    -- Secrétaire
UPDATE groupe_utilisateur SET niveau_hierarchique = 30 WHERE id_GU = 12;   -- Enseignant (si existe)
UPDATE groupe_utilisateur SET niveau_hierarchique = 10 WHERE id_GU = 13;   -- Étudiant (si existe)

-- ============================================
-- Configuration des permissions complètes pour l'Administrateur (groupe 5)
-- ============================================
-- L'administrateur a toutes les permissions sur tous les traitements
UPDATE permissions_actions 
SET peut_lire = TRUE, 
    peut_creer = TRUE, 
    peut_modifier = TRUE, 
    peut_supprimer = TRUE, 
    peut_exporter = TRUE, 
    peut_valider = TRUE 
WHERE id_GU = 5;

-- ============================================
-- Configuration des permissions pour Responsable scolarité (groupe 8)
-- ============================================
UPDATE permissions_actions 
SET peut_lire = TRUE, 
    peut_creer = TRUE, 
    peut_modifier = TRUE, 
    peut_exporter = TRUE,
    peut_valider = TRUE
WHERE id_GU = 8;

-- ============================================
-- Configuration des permissions pour Responsable Filière (groupe 9)
-- ============================================
UPDATE permissions_actions 
SET peut_lire = TRUE, 
    peut_creer = TRUE, 
    peut_modifier = TRUE, 
    peut_exporter = TRUE
WHERE id_GU = 9;

-- ============================================
-- Configuration des permissions pour Responsable niveau (groupe 10)
-- ============================================
UPDATE permissions_actions 
SET peut_lire = TRUE, 
    peut_modifier = TRUE, 
    peut_exporter = TRUE
WHERE id_GU = 10;

-- ============================================
-- Configuration des permissions pour Secrétaire (groupe 6)
-- ============================================
UPDATE permissions_actions 
SET peut_lire = TRUE, 
    peut_creer = TRUE, 
    peut_modifier = TRUE
WHERE id_GU = 6;

-- ============================================
-- Permissions en lecture seule pour les autres groupes
-- ============================================
-- Les groupes non spécifiés ci-dessus gardent uniquement la permission de lecture
