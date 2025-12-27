-- Migration: Création des tables de permissions granulaires pour CheckMaster
-- Version: 1.0
-- Date: 2024-12-27
-- Description: Implémente un système de permissions granulaires, cache de permissions,
--              rôles temporaires et codes d'accès temporaires

-- ============================================
-- Table des permissions granulaires
-- ============================================
-- Cette table remplace/étend la table 'rattacher' existante avec des permissions plus fines
CREATE TABLE IF NOT EXISTS permissions_actions (
  id_GU int NOT NULL,
  id_traitement int NOT NULL,
  peut_lire BOOLEAN DEFAULT TRUE,
  peut_creer BOOLEAN DEFAULT FALSE,
  peut_modifier BOOLEAN DEFAULT FALSE,
  peut_supprimer BOOLEAN DEFAULT FALSE,
  peut_exporter BOOLEAN DEFAULT FALSE,
  peut_valider BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (id_GU, id_traitement),
  FOREIGN KEY (id_GU) REFERENCES groupe_utilisateur(id_GU) ON DELETE CASCADE,
  FOREIGN KEY (id_traitement) REFERENCES traitement(id_traitement) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table du cache des permissions
-- ============================================
-- Stocke les permissions calculées pour chaque utilisateur afin d'améliorer les performances
CREATE TABLE IF NOT EXISTS permissions_cache (
  id_utilisateur int NOT NULL,
  id_traitement int NOT NULL,
  permissions_json JSON NOT NULL,
  genere_le DATETIME DEFAULT CURRENT_TIMESTAMP,
  expire_le DATETIME NOT NULL,
  PRIMARY KEY (id_utilisateur, id_traitement),
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
  INDEX idx_cache_expiration (expire_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des rôles temporaires
-- ============================================
-- Permet d'attribuer des rôles temporaires à des utilisateurs (ex: Président de Jury)
CREATE TABLE IF NOT EXISTS roles_temporaires (
  id_role_temp int NOT NULL AUTO_INCREMENT,
  id_utilisateur int NOT NULL,
  role_code varchar(50) NOT NULL,
  contexte_type varchar(50),
  contexte_id int,
  permissions_json JSON NOT NULL,
  actif BOOLEAN DEFAULT TRUE,
  valide_de DATETIME NOT NULL,
  valide_jusqu_a DATETIME NOT NULL,
  cree_par int,
  cree_le DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_role_temp),
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
  INDEX idx_role_actif (id_utilisateur, actif, valide_de, valide_jusqu_a),
  INDEX idx_role_code (role_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des codes d'accès temporaires (Président Jury)
-- ============================================
-- Stocke les codes d'accès à usage unique pour les rôles temporaires
CREATE TABLE IF NOT EXISTS codes_acces_temporaires (
  id_code int NOT NULL AUTO_INCREMENT,
  id_utilisateur int NOT NULL,
  id_soutenance int NOT NULL,
  code_hash varchar(255) NOT NULL,
  valide_de DATETIME NOT NULL,
  valide_jusqu_a DATETIME NOT NULL,
  utilise BOOLEAN DEFAULT FALSE,
  utilise_le DATETIME,
  ip_utilisation varchar(45),
  PRIMARY KEY (id_code),
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
  INDEX idx_code_valide (id_utilisateur, valide_de, valide_jusqu_a),
  INDEX idx_code_soutenance (id_soutenance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Modification de la table groupe_utilisateur
-- ============================================
-- Ajoute le niveau hiérarchique pour la gestion des délégations de permissions
ALTER TABLE groupe_utilisateur 
ADD COLUMN IF NOT EXISTS niveau_hierarchique int DEFAULT 0;

-- Index pour optimiser les requêtes sur le niveau hiérarchique
CREATE INDEX IF NOT EXISTS idx_groupe_niveau ON groupe_utilisateur(niveau_hierarchique);
