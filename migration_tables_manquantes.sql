-- ============================================================
-- Migration : Création des tables manquantes
-- Base : ufrmi1802974_2q2mpf
-- Date : 2026-02-21
-- ============================================================

USE `ufrmi1802974_2q2mpf`;

-- --------------------------------------------------------
-- Table `niveau_approbation`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `niveau_approbation` (
    `id_approb` int NOT NULL AUTO_INCREMENT,
    `lib_approb` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    PRIMARY KEY (`id_approb`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Données initiales
INSERT IGNORE INTO `niveau_approbation` (`id_approb`, `lib_approb`) VALUES
(1, 'En attente'),
(2, 'Approuvé'),
(3, 'Rejeté'),
(4, 'En révision');

-- --------------------------------------------------------
-- Table `approuver`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `approuver` (
    `id_rapport`          int NOT NULL,
    `id_pers_admin`       int NOT NULL,
    `commentaire_approv`  text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
    `decision`            varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    `date_approv`         datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `id_approb`           int NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_rapport`, `id_pers_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------
-- Table `ue` (Unité d'Enseignement)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ue` (
    `id_ue`                int NOT NULL AUTO_INCREMENT,
    `lib_ue`               varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `id_niveau_etude`      int NOT NULL,
    `id_semestre`          int NOT NULL,
    `id_annee_academique`  int DEFAULT NULL,
    `credit`               decimal(5,2) DEFAULT 0.00,
    `id_enseignant`        int DEFAULT NULL,
    PRIMARY KEY (`id_ue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------
-- Table `ecue` (Élément Constitutif d'UE) si elle n'existe pas déjà
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ecue` (
    `id_ecue`         int NOT NULL AUTO_INCREMENT,
    `lib_ecue`        varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `id_ue`           int NOT NULL,
    `coefficient`     decimal(5,2) DEFAULT 1.00,
    `id_enseignant`   int DEFAULT NULL,
    PRIMARY KEY (`id_ecue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------
-- Table `semestre` si elle n'existe pas déjà
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `semestre` (
    `id_semestre`    int NOT NULL AUTO_INCREMENT,
    `lib_semestre`   varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `id_niv_etude`   int DEFAULT NULL,
    PRIMARY KEY (`id_semestre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Données initiales semestre
INSERT IGNORE INTO `semestre` (`id_semestre`, `lib_semestre`, `id_niv_etude`) VALUES
(1, 'Semestre 1', 1),
(2, 'Semestre 2', 1),
(3, 'Semestre 3', 2),
(4, 'Semestre 4', 2);
