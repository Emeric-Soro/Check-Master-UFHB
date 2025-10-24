-- Migration Script: Evolution du RBAC vers un Contrôle Granulaire des Permissions (CRUD)
-- Date: 2025-10-24
-- Description: Ce script migre le système de permissions binaire vers un système CRUD granulaire

-- ============================================
-- Phase 1: Mise à jour de la table action
-- ============================================

-- Ajouter l'action CREATE (Ajouter)
INSERT INTO `action` (`id_action`, `lib_action`)
VALUES (1, 'Ajouter')
ON DUPLICATE KEY UPDATE lib_action = 'Ajouter';

-- Vérifier que toutes les actions CRUD existent
-- READ = Consulter (id: 7)
-- UPDATE = Modifier (id: 3)
-- DELETE = Supprimer (id: 6)
-- CREATE = Ajouter (id: 1)

-- ============================================
-- Phase 2: Création de la table permissions
-- ============================================

CREATE TABLE IF NOT EXISTS `permissions` (
    `id_permission` INT NOT NULL AUTO_INCREMENT,
    `id_GU` INT NOT NULL,
    `id_traitement` INT NOT NULL,
    `id_action` INT NOT NULL,
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_permission`),
    UNIQUE KEY `unique_permission` (`id_GU`, `id_traitement`, `id_action`),
    KEY `idx_gu_traitement` (`id_GU`, `id_traitement`),
    KEY `idx_action` (`id_action`),
    CONSTRAINT `fk_permissions_gu` FOREIGN KEY (`id_GU`) REFERENCES `groupe_utilisateur` (`id_GU`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_permissions_traitement` FOREIGN KEY (`id_traitement`) REFERENCES `traitement` (`id_traitement`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_permissions_action` FOREIGN KEY (`id_action`) REFERENCES `action` (`id_action`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- Phase 3: Migration des données
-- ============================================

-- Migrer les données existantes de rattacher vers permissions
-- Par défaut, on accorde toutes les permissions CRUD (1, 3, 6, 7) pour les liaisons existantes
INSERT INTO `permissions` (`id_GU`, `id_traitement`, `id_action`)
SELECT DISTINCT 
    r.id_GU,
    r.id_traitement,
    a.id_action
FROM `rattacher` r
CROSS JOIN `action` a
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` p 
    WHERE p.id_GU = r.id_GU 
    AND p.id_traitement = r.id_traitement 
    AND p.id_action = a.id_action
);

-- ============================================
-- Phase 4: Vérification et rapport
-- ============================================

-- Afficher le nombre de permissions créées
SELECT 
    'Migration terminée' AS statut,
    (SELECT COUNT(*) FROM rattacher) AS anciennes_attributions,
    (SELECT COUNT(*) FROM permissions) AS nouvelles_permissions,
    (SELECT COUNT(DISTINCT id_action) FROM action) AS nombre_actions;

-- Afficher un échantillon des permissions par groupe
SELECT 
    gu.lib_GU AS groupe,
    COUNT(DISTINCT p.id_traitement) AS nb_traitements,
    COUNT(*) AS nb_permissions_total
FROM permissions p
INNER JOIN groupe_utilisateur gu ON p.id_GU = gu.id_GU
GROUP BY gu.id_GU, gu.lib_GU
ORDER BY gu.lib_GU;

-- ============================================
-- Phase 5: Sauvegarde de l'ancienne table (optionnel)
-- ============================================

-- Renommer la table rattacher en rattacher_backup (pour rollback si nécessaire)
-- DÉCOMMENTER UNIQUEMENT APRÈS VALIDATION COMPLÈTE DU SYSTÈME
-- RENAME TABLE `rattacher` TO `rattacher_backup`;

-- Note: La table rattacher est conservée pour le moment pour permettre un rollback
-- Elle sera supprimée manuellement après validation complète du nouveau système
