-- ============================================================
-- Migration: Permissions Granulaires & Menus Hiérarchiques
-- Date: 2025-01-14
-- Description: Ajoute le support des sous-menus et des droits C.R.U.D
-- ============================================================

-- 1. Support des sous-menus dans la table traitement
ALTER TABLE traitement ADD COLUMN parent_id INT NULL DEFAULT NULL;
ALTER TABLE traitement ADD CONSTRAINT fk_traitement_parent 
    FOREIGN KEY (parent_id) REFERENCES traitement(id_traitement) ON DELETE SET NULL;

-- 2. Création de la table des droits granulaires (C.R.U.D)
CREATE TABLE IF NOT EXISTS droits (
    id_droit INT AUTO_INCREMENT PRIMARY KEY,
    id_GU INT NOT NULL,
    id_traitement INT NOT NULL,
    can_read BOOLEAN DEFAULT 1 COMMENT 'Consulter',
    can_create BOOLEAN DEFAULT 0 COMMENT 'Ajouter',
    can_update BOOLEAN DEFAULT 0 COMMENT 'Modifier',
    can_delete BOOLEAN DEFAULT 0 COMMENT 'Supprimer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_GU) REFERENCES groupe_utilisateur(id_GU) ON DELETE CASCADE,
    FOREIGN KEY (id_traitement) REFERENCES traitement(id_traitement) ON DELETE CASCADE,
    UNIQUE KEY unique_droit (id_GU, id_traitement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Migration des données existantes depuis la table 'rattacher'
-- (Transition en douceur - conserve les droits de lecture existants)
INSERT IGNORE INTO droits (id_GU, id_traitement, can_read, can_create, can_update, can_delete)
SELECT id_GU, id_traitement, 1, 0, 0, 0 FROM rattacher;

-- 4. Création des Menus Parents (Structure logique de navigation)
INSERT INTO traitement (id_traitement, lib_traitement, label_traitement, icone_traitement, ordre_traitement, parent_id) VALUES 
(100, 'menu_dashboard', 'Tableaux de Bord', 'fa-tachometer-alt', 1, NULL),
(200, 'menu_scolarite', 'Scolarité', 'fa-graduation-cap', 2, NULL),
(300, 'menu_pedagogie', 'Pédagogie', 'fa-chalkboard-teacher', 3, NULL),
(400, 'menu_soutenance', 'Soutenances', 'fa-book-reader', 4, NULL),
(500, 'menu_archive', 'Archives', 'fa-archive', 5, NULL),
(600, 'menu_admin', 'Administration', 'fa-cogs', 90, NULL),
(700, 'menu_perso', 'Mon Espace', 'fa-user-circle', 99, NULL)
ON DUPLICATE KEY UPDATE 
    label_traitement = VALUES(label_traitement),
    icone_traitement = VALUES(icone_traitement),
    ordre_traitement = VALUES(ordre_traitement);

-- 5. Classement des pages existantes sous leurs menus parents

-- Administration (parent_id = 600)
UPDATE traitement SET parent_id = 600 WHERE lib_traitement IN (
    'gestion_utilisateurs', 
    'gestion_rh', 
    'parametres_generaux', 
    'sauvegarde_restauration', 
    'piste_audit',
    'admin_historique'
);

-- Scolarité (parent_id = 200)
UPDATE traitement SET parent_id = 200 WHERE lib_traitement IN (
    'gestion_etudiants', 
    'gestion_scolarite', 
    'dossiers_academiques',
    'gestion_candidatures_soutenance',
    'gestion_notes_evaluations',
    'notes_resultats'
);

-- Soutenances (parent_id = 400)
UPDATE traitement SET parent_id = 400 WHERE lib_traitement IN (
    'candidature_soutenance', 
    'verification_candidatures_soutenance', 
    'gestion_rapports', 
    'programation_soutenance',
    'plannificaiton_soutenance',
    'evaluation_soutenance',
    'gestion_dossiers_candidatures',
    'evaluations_dossiers_soutenance',
    'processus_validation'
);

-- Tableaux de Bord (parent_id = 100)
UPDATE traitement SET parent_id = 100 WHERE lib_traitement IN (
    'dashboard',
    'dashboard_scolarite',
    'dashboard_enseignant',
    'dashboard_secretaire',
    'dashboard_commission'
);

-- Archives (parent_id = 500)
UPDATE traitement SET parent_id = 500 WHERE lib_traitement IN (
    'archives_dossiers_soutenance',
    'archive_comptes_rendus'
);

-- Mon Espace (parent_id = 700)
UPDATE traitement SET parent_id = 700 WHERE lib_traitement IN (
    'profil',
    'profil_etudiant',
    'messagerie',
    'gestion_reclamations',
    'gestion_reclamations_scolarite'
);

-- Pédagogie (parent_id = 300)
UPDATE traitement SET parent_id = 300 WHERE lib_traitement IN (
    'liste_etudiants_ens_simple',
    'liste_etudiants_resp_filiere',
    'liste_etudiants_resp_niveau',
    'redaction_compte_rendu',
    'planification_reunion'
);

-- 6. Attribution automatique des droits complets aux administrateurs (groupe 5)
UPDATE droits SET can_create = 1, can_update = 1, can_delete = 1 WHERE id_GU = 5;

-- 7. Ajout des droits pour les menus parents (lecture seule pour tous les groupes existants)
INSERT IGNORE INTO droits (id_GU, id_traitement, can_read)
SELECT DISTINCT r.id_GU, t.id_traitement, 1
FROM rattacher r
CROSS JOIN traitement t
WHERE t.id_traitement IN (100, 200, 300, 400, 500, 600, 700)
AND EXISTS (
    SELECT 1 FROM rattacher r2 
    JOIN traitement t2 ON r2.id_traitement = t2.id_traitement 
    WHERE r2.id_GU = r.id_GU AND t2.parent_id = t.id_traitement
);
