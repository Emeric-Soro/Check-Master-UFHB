-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 24, 2026 at 11:09 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ufrmi1802974_2q2mpf`
--

-- --------------------------------------------------------

--
-- Table structure for table `action`
--

DROP TABLE IF EXISTS `action`;
CREATE TABLE IF NOT EXISTS `action` (
  `id_action` int NOT NULL AUTO_INCREMENT,
  `lib_action` varchar(120) NOT NULL,
  PRIMARY KEY (`id_action`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `action`
--

INSERT INTO `action` (`id_action`, `lib_action`) VALUES
(3, 'Modifier'),
(6, 'Supprimer'),
(7, 'Consulter');

-- --------------------------------------------------------

--
-- Table structure for table `affecter`
--

DROP TABLE IF EXISTS `affecter`;
CREATE TABLE IF NOT EXISTS `affecter` (
  `id_enseignant` int NOT NULL,
  `role` enum('encadrant','directeur') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_rapport` int NOT NULL,
  `id_jury` int DEFAULT NULL,
  PRIMARY KEY (`id_enseignant`,`id_rapport`),
  KEY `Key_affecter_enseignant` (`id_enseignant`),
  KEY `Key_affecter_rappetu` (`id_rapport`),
  KEY `Key_affecter_jury` (`id_jury`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `affecter`
--

INSERT INTO `affecter` (`id_enseignant`, `role`, `id_rapport`, `id_jury`) VALUES
(7, 'encadrant', 17, NULL),
(7, 'directeur', 160, NULL),
(7, 'directeur', 177, NULL),
(7, 'directeur', 179, NULL),
(18, 'encadrant', 33, NULL),
(18, 'encadrant', 58, NULL),
(18, 'encadrant', 63, NULL),
(18, 'encadrant', 68, NULL),
(18, 'encadrant', 71, NULL),
(18, 'encadrant', 78, NULL),
(18, 'encadrant', 82, NULL),
(18, 'encadrant', 89, NULL),
(18, 'encadrant', 90, NULL),
(18, 'encadrant', 104, NULL),
(18, 'encadrant', 121, NULL),
(18, 'encadrant', 124, NULL),
(18, 'encadrant', 132, NULL),
(18, 'encadrant', 133, NULL),
(18, 'encadrant', 139, NULL),
(18, 'encadrant', 140, NULL),
(18, 'encadrant', 144, NULL),
(18, 'encadrant', 161, NULL),
(18, 'encadrant', 172, NULL),
(18, 'encadrant', 173, NULL),
(18, 'encadrant', 194, NULL),
(18, 'encadrant', 195, NULL),
(18, 'encadrant', 202, NULL),
(18, 'encadrant', 205, NULL),
(19, 'encadrant', 25, NULL),
(19, 'encadrant', 27, NULL),
(19, 'encadrant', 29, NULL),
(19, 'encadrant', 30, NULL),
(19, 'encadrant', 34, NULL),
(19, 'encadrant', 37, NULL),
(19, 'encadrant', 39, NULL),
(19, 'encadrant', 46, NULL),
(19, 'encadrant', 49, NULL),
(19, 'encadrant', 54, NULL),
(19, 'encadrant', 55, NULL),
(19, 'encadrant', 61, NULL),
(19, 'encadrant', 62, NULL),
(19, 'encadrant', 65, NULL),
(19, 'encadrant', 66, NULL),
(19, 'encadrant', 70, NULL),
(19, 'encadrant', 72, NULL),
(19, 'encadrant', 84, NULL),
(19, 'encadrant', 85, NULL),
(19, 'encadrant', 95, NULL),
(19, 'encadrant', 98, NULL),
(19, 'encadrant', 101, NULL),
(19, 'encadrant', 103, NULL),
(19, 'encadrant', 106, NULL),
(19, 'encadrant', 113, NULL),
(19, 'encadrant', 117, NULL),
(19, 'encadrant', 120, NULL),
(19, 'encadrant', 126, NULL),
(19, 'encadrant', 131, NULL),
(19, 'encadrant', 141, NULL),
(19, 'encadrant', 145, NULL),
(19, 'directeur', 146, NULL),
(19, 'encadrant', 148, NULL),
(19, 'directeur', 152, NULL),
(19, 'directeur', 154, NULL),
(19, 'encadrant', 157, NULL),
(19, 'encadrant', 160, NULL),
(19, 'encadrant', 171, NULL),
(19, 'encadrant', 175, NULL),
(19, 'encadrant', 176, NULL),
(19, 'encadrant', 180, NULL),
(19, 'encadrant', 186, NULL),
(19, 'encadrant', 188, NULL),
(19, 'encadrant', 196, NULL),
(19, 'encadrant', 199, NULL),
(19, 'encadrant', 201, NULL),
(19, 'encadrant', 204, NULL),
(19, 'encadrant', 206, NULL),
(21, 'encadrant', 16, NULL),
(22, 'directeur', 16, NULL),
(31, 'encadrant', 26, NULL),
(31, 'encadrant', 35, NULL),
(31, 'encadrant', 38, NULL),
(31, 'encadrant', 40, NULL),
(31, 'encadrant', 52, NULL),
(31, 'encadrant', 73, NULL),
(32, 'encadrant', 28, NULL),
(32, 'encadrant', 36, NULL),
(32, 'encadrant', 47, NULL),
(32, 'encadrant', 48, NULL),
(32, 'encadrant', 50, NULL),
(32, 'encadrant', 57, NULL),
(32, 'encadrant', 64, NULL),
(32, 'encadrant', 69, NULL),
(32, 'encadrant', 74, NULL),
(32, 'encadrant', 75, NULL),
(32, 'encadrant', 81, NULL),
(32, 'encadrant', 86, NULL),
(32, 'encadrant', 94, NULL),
(32, 'encadrant', 97, NULL),
(32, 'encadrant', 102, NULL),
(32, 'encadrant', 115, NULL),
(32, 'encadrant', 119, NULL),
(32, 'encadrant', 123, NULL),
(32, 'encadrant', 128, NULL),
(32, 'encadrant', 130, NULL),
(32, 'encadrant', 136, NULL),
(32, 'encadrant', 137, NULL),
(32, 'encadrant', 146, NULL),
(32, 'directeur', 151, NULL),
(33, 'encadrant', 31, NULL),
(33, 'encadrant', 32, NULL),
(33, 'encadrant', 41, NULL),
(33, 'encadrant', 42, NULL),
(33, 'encadrant', 43, NULL),
(33, 'encadrant', 44, NULL),
(33, 'encadrant', 45, NULL),
(33, 'encadrant', 51, NULL),
(33, 'encadrant', 53, NULL),
(33, 'encadrant', 56, NULL),
(33, 'encadrant', 67, NULL),
(33, 'encadrant', 76, NULL),
(33, 'encadrant', 77, NULL),
(33, 'encadrant', 87, NULL),
(33, 'encadrant', 88, NULL),
(33, 'encadrant', 91, NULL),
(33, 'encadrant', 93, NULL),
(33, 'encadrant', 99, NULL),
(33, 'encadrant', 100, NULL),
(33, 'encadrant', 105, NULL),
(33, 'encadrant', 107, NULL),
(33, 'encadrant', 118, NULL),
(33, 'encadrant', 125, NULL),
(33, 'encadrant', 143, NULL),
(34, 'encadrant', 59, NULL),
(34, 'encadrant', 114, NULL),
(35, 'encadrant', 60, NULL),
(35, 'encadrant', 80, NULL),
(36, 'encadrant', 79, NULL),
(36, 'encadrant', 96, NULL),
(37, 'encadrant', 83, NULL),
(38, 'encadrant', 92, NULL),
(39, 'encadrant', 108, NULL),
(39, 'encadrant', 109, NULL),
(39, 'encadrant', 111, NULL),
(39, 'encadrant', 112, NULL),
(39, 'encadrant', 122, NULL),
(39, 'encadrant', 127, NULL),
(39, 'encadrant', 129, NULL),
(39, 'encadrant', 134, NULL),
(39, 'encadrant', 135, NULL),
(39, 'encadrant', 138, NULL),
(39, 'encadrant', 142, NULL),
(39, 'encadrant', 155, NULL),
(39, 'encadrant', 156, NULL),
(39, 'encadrant', 158, NULL),
(39, 'encadrant', 162, NULL),
(39, 'encadrant', 163, NULL),
(39, 'encadrant', 164, NULL),
(39, 'encadrant', 165, NULL),
(39, 'encadrant', 166, NULL),
(39, 'encadrant', 167, NULL),
(39, 'encadrant', 168, NULL),
(39, 'encadrant', 169, NULL),
(39, 'encadrant', 170, NULL),
(39, 'encadrant', 174, NULL),
(39, 'encadrant', 177, NULL),
(39, 'encadrant', 178, NULL),
(39, 'encadrant', 179, NULL),
(39, 'encadrant', 181, NULL),
(39, 'encadrant', 182, NULL),
(39, 'encadrant', 183, NULL),
(39, 'encadrant', 187, NULL),
(39, 'encadrant', 189, NULL),
(39, 'encadrant', 190, NULL),
(39, 'encadrant', 191, NULL),
(39, 'encadrant', 192, NULL),
(39, 'encadrant', 193, NULL),
(39, 'encadrant', 200, NULL),
(39, 'directeur', 204, NULL),
(39, 'directeur', 206, NULL),
(40, 'encadrant', 110, NULL),
(41, 'encadrant', 116, NULL),
(42, 'directeur', 148, NULL),
(43, 'encadrant', 150, NULL),
(43, 'directeur', 155, NULL),
(43, 'directeur', 161, NULL),
(43, 'directeur', 165, NULL),
(43, 'directeur', 166, NULL),
(43, 'directeur', 170, NULL),
(43, 'directeur', 181, NULL),
(43, 'directeur', 192, NULL),
(43, 'directeur', 202, NULL),
(44, 'encadrant', 151, NULL),
(44, 'directeur', 157, NULL),
(44, 'directeur', 163, NULL),
(44, 'directeur', 168, NULL),
(44, 'directeur', 169, NULL),
(44, 'directeur', 171, NULL),
(45, 'encadrant', 152, NULL),
(46, 'encadrant', 153, NULL),
(47, 'encadrant', 154, NULL),
(47, 'directeur', 156, NULL),
(47, 'directeur', 162, NULL),
(47, 'directeur', 167, NULL),
(47, 'directeur', 172, NULL),
(47, 'directeur', 173, NULL),
(47, 'directeur', 175, NULL),
(47, 'directeur', 180, NULL),
(47, 'directeur', 189, NULL),
(47, 'directeur', 190, NULL),
(47, 'directeur', 195, NULL),
(47, 'directeur', 200, NULL),
(48, 'directeur', 158, NULL),
(48, 'directeur', 174, NULL),
(48, 'directeur', 178, NULL),
(48, 'directeur', 194, NULL),
(50, 'directeur', 164, NULL),
(50, 'directeur', 187, NULL),
(50, 'directeur', 193, NULL),
(51, 'directeur', 176, NULL),
(51, 'directeur', 182, NULL),
(51, 'directeur', 183, NULL),
(51, 'directeur', 186, NULL),
(51, 'directeur', 188, NULL),
(51, 'directeur', 191, NULL),
(51, 'directeur', 196, NULL),
(51, 'directeur', 201, NULL),
(52, 'directeur', 199, NULL),
(52, 'directeur', 205, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `annee_academique`
--

DROP TABLE IF EXISTS `annee_academique`;
CREATE TABLE IF NOT EXISTS `annee_academique` (
  `id_annee_acad` int NOT NULL AUTO_INCREMENT,
  `date_deb` date NOT NULL,
  `date_fin` date NOT NULL,
  PRIMARY KEY (`id_annee_acad`)
) ENGINE=InnoDB AUTO_INCREMENT=29908 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `annee_academique`
--

INSERT INTO `annee_academique` (`id_annee_acad`, `date_deb`, `date_fin`) VALUES
(21009, '2009-09-01', '2010-08-31'),
(21110, '2010-09-01', '2011-08-31'),
(21211, '2011-09-01', '2012-08-31'),
(21312, '2012-09-01', '2013-08-31'),
(21413, '2013-09-08', '2014-06-25'),
(21514, '2014-09-02', '2015-06-21'),
(21615, '2015-09-03', '2016-06-24'),
(21716, '2016-09-05', '2017-06-20'),
(21817, '2017-09-01', '2018-06-25'),
(21918, '2018-09-04', '2019-06-23'),
(22019, '2019-09-08', '2020-06-24'),
(22120, '2020-09-01', '2021-06-27'),
(22221, '2021-09-08', '2022-07-20'),
(22322, '2022-09-10', '2023-07-31'),
(22423, '2023-09-11', '2024-07-17'),
(22524, '2024-09-10', '2025-07-30'),
(22625, '2025-09-15', '2026-07-31'),
(29901, '2010-09-01', '2011-08-31');

-- --------------------------------------------------------

--
-- Table structure for table `approuver`
--

DROP TABLE IF EXISTS `approuver`;
CREATE TABLE IF NOT EXISTS `approuver` (
  `id_pers_admin` int NOT NULL,
  `id_rapport` int NOT NULL,
  `decision` enum('approuve','desapprouve') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `date_approv` datetime NOT NULL,
  `commentaire_approv` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_approb` int NOT NULL,
  PRIMARY KEY (`id_pers_admin`,`id_rapport`),
  KEY `Key_approver_enseignant` (`id_pers_admin`),
  KEY `Key_approver_rapport` (`id_rapport`),
  KEY `fk_approuver_niveau` (`id_approb`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `approuver`
--

INSERT INTO `approuver` (`id_pers_admin`, `id_rapport`, `decision`, `date_approv`, `commentaire_approv`, `id_approb`) VALUES
(10, 16, 'approuve', '2025-09-29 22:34:21', 'Tout es bon ', 4);

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `is_sensitive` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `auth_rate_limits`
--

DROP TABLE IF EXISTS `auth_rate_limits`;
CREATE TABLE IF NOT EXISTS `auth_rate_limits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(16) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `identifier` varchar(128) NOT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `window_start` datetime NOT NULL,
  `last_attempt` datetime NOT NULL,
  `blocked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_action_ip_identifier` (`action`,`ip`,`identifier`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avoir`
--

DROP TABLE IF EXISTS `avoir`;
CREATE TABLE IF NOT EXISTS `avoir` (
  `id_grade` int NOT NULL,
  `id_enseignant` int NOT NULL,
  `date_grade` date NOT NULL,
  PRIMARY KEY (`id_grade`,`id_enseignant`),
  KEY `Key_avoir_grade` (`id_grade`),
  KEY `Key_avoir_enseignant` (`id_enseignant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `avoir`
--

INSERT INTO `avoir` (`id_grade`, `id_enseignant`, `date_grade`) VALUES
(7, 18, '1995-11-27'),
(7, 22, '1995-09-01'),
(12, 7, '1993-09-15'),
(12, 19, '2006-07-29'),
(12, 23, '2000-09-19'),
(15, 21, '2000-10-10');

-- --------------------------------------------------------

--
-- Table structure for table `candidature_soutenance`
--

DROP TABLE IF EXISTS `candidature_soutenance`;
CREATE TABLE IF NOT EXISTS `candidature_soutenance` (
  `id_candidature` int NOT NULL AUTO_INCREMENT,
  `num_etu` int NOT NULL,
  `date_candidature` datetime NOT NULL,
  `statut_candidature` enum('En attente','Validée','Rejetée') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'En attente',
  `date_traitement` datetime DEFAULT NULL,
  `id_pers_admin` int DEFAULT NULL,
  `commentaire_admin` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  PRIMARY KEY (`id_candidature`),
  KEY `num_etu` (`num_etu`),
  KEY `id_pers_admin` (`id_pers_admin`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `candidature_soutenance`
--

INSERT INTO `candidature_soutenance` (`id_candidature`, `num_etu`, `date_candidature`, `statut_candidature`, `date_traitement`, `id_pers_admin`, `commentaire_admin`) VALUES
(14, 20220001, '2025-09-29 22:21:31', 'Validée', '2025-09-29 22:22:15', 9, 'Évaluation complète terminée'),
(15, 20220002, '2025-12-11 11:43:54', 'Validée', '2025-12-11 11:45:07', 9, 'Évaluation complète terminée');

-- --------------------------------------------------------

--
-- Table structure for table `categories_fonctionnalites`
--

DROP TABLE IF EXISTS `categories_fonctionnalites`;
CREATE TABLE IF NOT EXISTS `categories_fonctionnalites` (
  `id_categorie` int NOT NULL AUTO_INCREMENT,
  `code_categorie` varchar(50) NOT NULL,
  `lib_categorie` varchar(100) NOT NULL,
  `description_categorie` text,
  `icone_categorie` varchar(100) DEFAULT NULL,
  `ordre_categorie` int DEFAULT '0',
  `actif` tinyint(1) DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categorie`),
  UNIQUE KEY `code_categorie` (`code_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `categories_fonctionnalites`
--

INSERT INTO `categories_fonctionnalites` (`id_categorie`, `code_categorie`, `lib_categorie`, `description_categorie`, `icone_categorie`, `ordre_categorie`, `actif`, `date_creation`) VALUES
(1, 'DASHBOARD', 'Tableau de Bord', 'Tableaux de bord et statistiques', 'fas fa-chart-line', 1, 0, '2026-01-05 22:57:10'),
(2, 'ETUDIANTS', 'Gestion Étudiants', 'Gestion des étudiants et inscriptions', 'fas fa-user-graduate', 2, 0, '2026-01-05 22:57:10'),
(3, 'RAPPORTS', 'Rapports de Stage', 'Création et suivi des rapports', 'fas fa-file-alt', 3, 0, '2026-01-05 22:57:10'),
(4, 'CANDIDATURES', 'Candidatures Soutenance', 'Processus de candidature', 'fas fa-clipboard-check', 4, 0, '2026-01-05 22:57:10'),
(5, 'VALIDATION', 'Validation Rapports', 'Processus de validation commission', 'fas fa-check-double', 5, 0, '2026-01-05 22:57:10'),
(6, 'SOUTENANCES', 'Programmation Soutenances', 'Organisation des soutenances', 'fas fa-calendar-alt', 6, 0, '2026-01-05 22:57:10'),
(7, 'COMPTES_RENDUS', 'Comptes Rendus', 'Rédaction comptes rendus jury', 'fas fa-pen-fancy', 7, 0, '2026-01-05 22:57:10'),
(8, 'NOTES', 'Notes et Résultats', 'Gestion des notes', 'fas fa-graduation-cap', 8, 0, '2026-01-05 22:57:10'),
(9, 'RECLAMATIONS', 'Réclamations', 'Gestion des réclamations', 'fas fa-exclamation-circle', 9, 0, '2026-01-05 22:57:10'),
(10, 'RH', 'Ressources Humaines', 'Gestion du personnel', 'fas fa-users-cog', 10, 0, '2026-01-05 22:57:10'),
(11, 'PARAMETRES', 'Paramètres Généraux', 'Configuration système', 'fas fa-cog', 11, 0, '2026-01-05 22:57:10'),
(12, 'SYSTEME', 'Administration Système', 'Outils administratifs', 'fas fa-tools', 12, 0, '2026-01-05 22:57:10'),
(13, 'SCOLARITE', 'Gestion de la scolarité', 'Scolarité, inscriptions, notes, réclamations scolarité', 'fas fa-school', 1, 1, '2026-01-24 21:20:25'),
(14, 'ETUDIANT_ENV', 'Environnement Étudiant', 'Espace étudiant: candidature, rapports, résultats, réclamations', 'fas fa-user-graduate', 2, 1, '2026-01-24 21:20:25'),
(15, 'COMMISSION', 'Commission validation', 'Jury/commission: validation, soutenances, comptes-rendus', 'fas fa-check-double', 3, 1, '2026-01-24 21:20:25'),
(16, 'ADMIN_PLATEFORME', 'Administration plateforme', 'Administration, paramètres, utilisateurs, audit, sauvegardes', 'fas fa-tools', 4, 1, '2026-01-24 21:20:25');

-- --------------------------------------------------------

--
-- Table structure for table `composer_jury`
--

DROP TABLE IF EXISTS `composer_jury`;
CREATE TABLE IF NOT EXISTS `composer_jury` (
  `num_jury` int NOT NULL,
  `id_enseignant` int NOT NULL,
  `id_qualite_jury` int NOT NULL,
  `date_composer_jury` int NOT NULL,
  PRIMARY KEY (`num_jury`,`id_enseignant`,`id_qualite_jury`),
  KEY `fk_composer_enseignant` (`id_enseignant`),
  KEY `fk_composer_role` (`id_qualite_jury`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `composer_jury`
--

INSERT INTO `composer_jury` (`num_jury`, `id_enseignant`, `id_qualite_jury`, `date_composer_jury`) VALUES
(1, 7, 3, 1760307399),
(1, 18, 1, 1760307399),
(1, 21, 2, 1760307399),
(1, 22, 4, 1760307399),
(2, 7, 3, 1764884760),
(1657, 39, 1, 1765152314),
(1882, 39, 1, 1765152314),
(2045, 18, 1, 1765152314),
(2087, 39, 1, 1765152314),
(2165, 39, 1, 1765152313),
(2265, 39, 1, 1765152314),
(2576, 39, 1, 1765152314),
(2925, 39, 1, 1765152314),
(3123, 39, 1, 1765152314),
(3447, 18, 1, 1765152313),
(3480, 19, 1, 1765152314),
(3831, 49, 1, 1765152313),
(4301, 18, 1, 1765152314),
(4493, 18, 1, 1765152314),
(4557, 39, 1, 1765152314),
(4745, 18, 1, 1765152313),
(5705, 39, 1, 1765152314),
(5808, 39, 1, 1765152314),
(6359, 49, 1, 1765152314),
(6577, 39, 1, 1765152314),
(6636, 18, 1, 1765152313),
(6733, 39, 1, 1765152314),
(6851, 39, 1, 1765152314),
(7665, 18, 1, 1765152314),
(7699, 39, 1, 1765152314),
(7979, 39, 1, 1765152314),
(8089, 39, 1, 1765152313),
(8404, 37, 1, 1765152314),
(8716, 39, 1, 1765152314),
(8900, 39, 1, 1765152314),
(9185, 18, 1, 1765152313),
(9405, 39, 1, 1765152314),
(9484, 18, 1, 1765152314),
(9923, 39, 1, 1765152314);

-- --------------------------------------------------------

--
-- Table structure for table `compte_rendu`
--

DROP TABLE IF EXISTS `compte_rendu`;
CREATE TABLE IF NOT EXISTS `compte_rendu` (
  `id_CR` int NOT NULL AUTO_INCREMENT,
  `num_etu` int NOT NULL,
  `nom_CR` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `contenu_CR` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `chemin_fichier_pdf` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `date_CR` datetime NOT NULL,
  PRIMARY KEY (`id_CR`),
  KEY `fk_etudiant` (`num_etu`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `compte_rendu`
--

INSERT INTO `compte_rendu` (`id_CR`, `num_etu`, `nom_CR`, `contenu_CR`, `chemin_fichier_pdf`, `date_CR`) VALUES
(30, 20220001, 'Compte rendu séance du 29/09/2025', '\r\n                <style>\r\n                .editor-content { font-family: \'Times New Roman\', Times, serif; }             \r\n                .header-logos .right { float: right; }\r\n                .header-logos .center { text-align: center; margin: 0 auto; }\r\n                .editor-content h1 { font-size: 2.2em; font-weight: bold; margin-bottom: 0.5em; text-align: center; }\r\n                .editor-content h2 { font-size: 1.5em; font-weight: bold; margin-bottom: 0.5em; text-align: center; }\r\n                .editor-content h3 { font-size: 1.2em; font-weight: bold; margin-bottom: 0.5em; }\r\n                .section-title { border-bottom: 2px solid #222; margin-bottom: 0.7em; margin-top: 1.5em; }\r\n                .editor-content p { margin-bottom: 0.7em; }\r\n                .editor-content ul { margin-left: 1.5em; margin-bottom: 0.7em; }\r\n                .encadre { background: #f6faff; border: 2px solid #b6d4fe; border-radius: 8px; padding: 1em; margin-bottom: 1em; }\r\n                .cas { background: #fff; border: 1px solid #b6d4fe; border-radius: 8px; padding: 1em; margin-bottom: 1em; }\r\n                .cas-titre { font-weight: bold; margin-bottom: 0.5em; }\r\n                .cas-footer { margin-top: 1em; font-size: 1em; }\r\n                .text-center { text-align: center; }\r\n                .italic { font-style: italic; }\r\n                </style>\r\n                <div class=\"header-logos\">\r\n                    <div class=\"center\">\r\n                        <div style=\"font-size:13px; font-weight:bold; letter-spacing:1px;\">REPUBLIQUE DE COTE D\'IVOIRE</div>\r\n                        <div style=\"font-size:12px;\">Ministère de l\'Enseignement Supérieur et de la Recherche Scientifique</div>\r\n                        </div>\r\n                        </div>\r\n                <h1>Procès-Verbal de séance de validation de thèmes</h1>\r\n                <h2>Thèmes de Soutenance - Filière MIAGE-GI</h2>\r\n                <div class=\"text-center\" style=\"margin-bottom:1em;\">\r\n                    Université Félix Houphouët-Boigny<br>\r\n                    UFR Mathématiques et Informatique\r\n                        </div>\r\n                <h3 class=\"section-title\">CONTEXTE DE LA SÉANCE</h3>\r\n                <p>Dans le bureau du Prof KOUA Brou à l\'UFR MI, le [DATE] s\'est tenue de 11 h 00 à 12 h 30 une séance de validation de thèmes de soutenance des étudiants en fin de cycle de la filière MIAGE-GI.</p>\r\n                <p>La réunion était animée par Prof KOUA Brou le responsable de ladite filière. Etaient présents Prof. KOUA Brou, Dr MAMADOU Diarra, M. WAH Médard et M. BROU Patrice. Les membres de la commission de validation ont examiné [N] dossiers.</p>\r\n                <div class=\"encadre\">\r\n                    <strong>Ordre du jour :</strong>\r\n                    <ul>\r\n                        <li>Informations</li>\r\n                        <li>Validation de thèmes</li>\r\n                        <li>Divers</li>\r\n                                </ul>\r\n                            </div>\r\n                <h3 class=\"section-title\">1. INFORMATIONS</h3>\r\n                <p class=\"italic\">[Le responsable de la filière a exposé sur l\'intérêt des séances de validation. Il a donné des informations sur le choix des thèmes niveau ingénieur et la tenue mensuelle des séances de validation.]</p>\r\n                <p class=\"italic\">[L\'organisation des séances de validation permet de faire le point des encadrements, le contenu potentiel de thèmes, et le suivi des mémoires par des encadreurs pédagogiques.]</p>\r\n                <h3 class=\"section-title\">2. VALIDATION DE THÈMES</h3>\r\n                <div id=\"casDynamique\">\r\n                <div class=\"cas\">\r\n                    <div class=\"cas-titre\">Cas 1</div>\r\n                    <strong>Étudiant :</strong> Adjo Jemima Irie<br>\r\n                    <strong>Thème :</strong> AUDIT ET CONTROLE<br>\r\n                    <strong>Recommandations de la commission :</strong>\r\n                    <ul>\r\n                        <li>thème valide</li>\r\n                        <li>bien décrire le processus de règlement de chèques</li>\r\n                        <li>décrire exactement le contexte</li>\r\n                    </ul>\r\n                    <div class=\"cas-footer\">\r\n                        <strong>Directeur de mémoire :</strong> Michael Foursov &nbsp;&nbsp;\r\n                        <strong>Encadrant pédagogique :</strong> Malan Nindjin\r\n                    </div>\r\n                </div>\r\n                </div>\r\n                <h3 class=\"section-title\">3. DIVERS</h3>\r\n                <p class=\"italic\">[La commission a recommandé au Directeur de la filière d\'améliorer le partenariat avec les entreprises car elles le souhaitent compte tenu du rendement des stagiaires déjà reçus.]</p>\r\n                <strong>Recommandations aux étudiants :</strong>\r\n                <ul>\r\n                    <li>Respecter toutes les rubriques du template de présentation de thème en possession de la chargée de communication</li>\r\n                    <li>Joindre un CV contenant une photo d\'identité</li>\r\n                    <li>Soutenir au plus tard à la session suivante pour ne pas tomber sous le coup d\'une pénalité</li>\r\n                                </ul>\r\n                <div class=\"text-center\" style=\"margin-top:2em;\">\r\n                    Les travaux de la commission ont pris fin à 12 h 30.<br>\r\n                    Fait à Abidjan, le [DATE]<br>\r\n                    <strong>La commission</strong>\r\n                        </div>\r\n                    ', 'ressources/uploads/comptes_rendus/CR_20250929_232200.pdf', '2025-09-29 23:21:59');

-- --------------------------------------------------------

--
-- Table structure for table `compte_rendu_rapport`
--

DROP TABLE IF EXISTS `compte_rendu_rapport`;
CREATE TABLE IF NOT EXISTS `compte_rendu_rapport` (
  `id_CR` int NOT NULL,
  `id_rapport` int NOT NULL,
  PRIMARY KEY (`id_CR`,`id_rapport`),
  KEY `id_rapport` (`id_rapport`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `compte_rendu_rapport`
--

INSERT INTO `compte_rendu_rapport` (`id_CR`, `id_rapport`) VALUES
(30, 16);

-- --------------------------------------------------------

--
-- Table structure for table `correspondre`
--

DROP TABLE IF EXISTS `correspondre`;
CREATE TABLE IF NOT EXISTS `correspondre` (
  `id_annee_acad` int NOT NULL,
  `id_critere` int NOT NULL,
  `bareme` int NOT NULL,
  PRIMARY KEY (`id_annee_acad`,`id_critere`),
  KEY `id_critere` (`id_critere`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `correspondre`
--

INSERT INTO `correspondre` (`id_annee_acad`, `id_critere`, `bareme`) VALUES
(22524, 8, 5),
(22524, 9, 5),
(22625, 3, 4),
(22625, 4, 5),
(22625, 5, 2),
(22625, 6, 4),
(22625, 7, 5);

-- --------------------------------------------------------

--
-- Table structure for table `critere_evaluation`
--

DROP TABLE IF EXISTS `critere_evaluation`;
CREATE TABLE IF NOT EXISTS `critere_evaluation` (
  `id_critere` int NOT NULL AUTO_INCREMENT,
  `lib_critere` varchar(100) NOT NULL,
  PRIMARY KEY (`id_critere`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `critere_evaluation`
--

INSERT INTO `critere_evaluation` (`id_critere`, `lib_critere`) VALUES
(3, 'Exposé'),
(4, 'Réponses aux questions posées'),
(5, 'Présentation du mémoire'),
(6, 'Contenu du mémoire'),
(7, 'Résolution du problème'),
(8, 'Résolution du problème'),
(9, 'Réponses aux questions posées');

-- --------------------------------------------------------

--
-- Table structure for table `decisions_jury`
--

DROP TABLE IF EXISTS `decisions_jury`;
CREATE TABLE IF NOT EXISTS `decisions_jury` (
  `id_decision` int NOT NULL AUTO_INCREMENT,
  `lib_decision` varchar(50) NOT NULL,
  `description` text,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_decision`),
  UNIQUE KEY `lib_decision` (`lib_decision`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `decisions_jury`
--

INSERT INTO `decisions_jury` (`id_decision`, `lib_decision`, `description`, `actif`) VALUES
(1, 'Admis', 'Candidat admis définitivement', 1),
(2, 'Ajourné', 'Candidat ajourné, peut repasser la soutenance', 1),
(3, 'Refusé', 'Candidat refusé', 1);

-- --------------------------------------------------------

--
-- Table structure for table `deposer`
--

DROP TABLE IF EXISTS `deposer`;
CREATE TABLE IF NOT EXISTS `deposer` (
  `num_etu` int NOT NULL,
  `id_rapport` int NOT NULL,
  `date_depot` datetime NOT NULL,
  PRIMARY KEY (`num_etu`,`id_rapport`),
  KEY `Key_deposer_etudiant` (`num_etu`),
  KEY `Key_deposer_rapport_etud` (`id_rapport`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `deposer`
--

INSERT INTO `deposer` (`num_etu`, `id_rapport`, `date_depot`) VALUES
(20220001, 16, '2025-09-29 22:26:45'),
(20220002, 208, '2025-12-11 11:52:12');

-- --------------------------------------------------------

--
-- Table structure for table `echeances`
--

DROP TABLE IF EXISTS `echeances`;
CREATE TABLE IF NOT EXISTS `echeances` (
  `id_echeance` int NOT NULL AUTO_INCREMENT,
  `id_inscription` int DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT NULL,
  `date_echeance` date DEFAULT NULL,
  `statut_echeance` enum('En attente','Payée','En retard') DEFAULT NULL,
  PRIMARY KEY (`id_echeance`),
  KEY `id_inscription` (`id_inscription`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `ecue`
--

DROP TABLE IF EXISTS `ecue`;
CREATE TABLE IF NOT EXISTS `ecue` (
  `id_ecue` int NOT NULL AUTO_INCREMENT,
  `id_ue` int NOT NULL,
  `lib_ecue` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `credit` int NOT NULL,
  `id_enseignant` int DEFAULT NULL,
  PRIMARY KEY (`id_ecue`),
  KEY `Key_ecue_ue` (`id_ue`),
  KEY `fk_enseignant_responsable` (`id_enseignant`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `enseignants`
--

DROP TABLE IF EXISTS `enseignants`;
CREATE TABLE IF NOT EXISTS `enseignants` (
  `id_enseignant` int NOT NULL AUTO_INCREMENT,
  `nom_enseignant` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `prenom_enseignant` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `mail_enseignant` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_specialite` int NOT NULL,
  `type_enseignant` enum('Simple','Administratif') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_enseignant`),
  KEY `Key_enseign_specialite` (`id_specialite`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `enseignants`
--

INSERT INTO `enseignants` (`id_enseignant`, `nom_enseignant`, `prenom_enseignant`, `mail_enseignant`, `id_specialite`, `type_enseignant`) VALUES
(7, 'Koua', 'Brou', 'kouabrou@gmail.com', 2, 'Administratif'),
(18, 'Wah', 'Medar', 'wahmedar@gmail.com', 2, 'Simple'),
(19, 'Brou', 'Patrice', 'bpatrice@gmail.com', 2, 'Administratif'),
(21, 'Nindjin', 'Malan', 'nindjinmalan.0@gmail.com', 2, 'Simple'),
(22, 'Foursov', 'Michael', 'michaelfoufou@gmail.com', 2, 'Administratif'),
(23, 'Diarra', 'prenom', 'Diarraprenom@gmail.com', 2, 'Administratif'),
(31, 'TANOH', 'Lambert', 'lambert.tanoh@ufhb.edu.ci', 2, 'Simple'),
(32, 'Mamadou', 'DIARRA', 'diarra.mamadou@ufhb.edu.ci', 2, 'Simple'),
(33, 'Non', 'attribué', 'attribue.non@ufhb.edu.ci', 2, 'Simple'),
(34, 'BOLI', 'Kuyo', 'kuyo.boli@ufhb.edu.ci', 2, 'Simple'),
(35, 'ASSALE', 'Adjé', 'adje.assale@ufhb.edu.ci', 2, 'Simple'),
(36, 'Cmdt', 'Menan', 'menan.cmdt@ufhb.edu.ci', 2, 'Simple'),
(37, 'KOUASSI', 'Florent', 'florent.kouassi@ufhb.edu.ci', 2, 'Simple'),
(38, 'SEKA', 'Louis', 'louis.seka@ufhb.edu.ci', 2, 'Simple'),
(39, 'Dr.', 'SEKA Louis', 'sekalouis.dr@ufhb.edu.ci', 2, 'Simple'),
(40, 'KPON', 'Roger', 'roger.kpon@ufhb.edu.ci', 2, 'Simple'),
(41, 'GODRIN', 'Kouadio', 'kouadio.godrin@ufhb.edu.ci', 2, 'Simple'),
(42, 'GORE', 'Bi', 'bi.gore@ufhb.edu.ci', 2, 'Simple'),
(43, 'YODE', 'Armel', 'armel.yode@ufhb.edu.ci', 2, 'Simple'),
(44, 'AMAN', 'Auguste', 'auguste.aman@ufhb.edu.ci', 2, 'Simple'),
(45, 'N\'ZI', 'Modeste', 'modeste.nzi@ufhb.edu.ci', 2, 'Simple'),
(46, 'Voir', 'admin', 'admin.voir@ufhb.edu.ci', 2, 'Simple'),
(47, 'KOUAKOU', 'Mathias', 'mathias.kouakou@ufhb.edu.ci', 2, 'Simple'),
(48, 'COULIBALY', 'Adama', 'adama.coulibaly@ufhb.edu.ci', 2, 'Simple'),
(49, 'KONATE', 'N\'Golo', 'ngolo.konate@ufhb.edu.ci', 2, 'Simple'),
(50, 'AKEKE', 'Eric', 'eric.akeke@ufhb.edu.ci', 2, 'Simple'),
(51, 'OKOU', 'Hyppolite', 'hyppolite.okou@ufhb.edu.ci', 2, 'Simple'),
(52, 'FEUTO', 'Justin', 'justin.feuto@ufhb.edu.ci', 2, 'Simple');

-- --------------------------------------------------------

--
-- Table structure for table `entreprises`
--

DROP TABLE IF EXISTS `entreprises`;
CREATE TABLE IF NOT EXISTS `entreprises` (
  `id_entreprise` int NOT NULL AUTO_INCREMENT,
  `lib_entreprise` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_entreprise`),
  UNIQUE KEY `lib_entreprise` (`lib_entreprise`)
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `entreprises`
--

INSERT INTO `entreprises` (`id_entreprise`, `lib_entreprise`) VALUES
(52, 'Afrique Telecom'),
(116, 'AgenceX'),
(96, 'AGL'),
(77, 'AGRISOFT'),
(70, 'Allianz'),
(40, 'AM Service'),
(30, 'ANARE'),
(35, 'Assurance'),
(22, 'ATCI'),
(64, 'Atlantique Assur.'),
(27, 'Atlantique Telecom'),
(44, 'Banque'),
(93, 'BGFI'),
(25, 'BHCI'),
(73, 'BICI Senegal'),
(46, 'BICICI'),
(66, 'BNETD'),
(23, 'Bridge Bank'),
(80, 'CARGILL'),
(104, 'CGEDS'),
(90, 'CI-Energies'),
(60, 'CIE'),
(75, 'CNPS'),
(108, 'CONSULTECH'),
(55, 'CSRS'),
(11, 'Deloitte'),
(3, 'Deloitte Côte d\'Ivoire'),
(34, 'Dette Publique'),
(9, 'DIGICORP'),
(97, 'DJAMO'),
(101, 'DKBS'),
(79, 'DNCI'),
(26, 'Douanes'),
(56, 'DSH'),
(94, 'EBENYX'),
(95, 'EBURTIS'),
(84, 'EDLONA'),
(89, 'EKIP'),
(24, 'ELIPRED'),
(112, 'EVEREST'),
(102, 'EY'),
(57, 'FFPSE'),
(105, 'FIDOPS'),
(67, 'Galloper'),
(32, 'Gendarmerie'),
(39, 'GIE GEMACI'),
(51, 'IAS'),
(65, 'Impôts'),
(85, 'Inst. Bancaire'),
(106, 'Inst. Pasteur'),
(82, 'KIEWU'),
(19, 'LONACI'),
(29, 'Mairie Agboville'),
(113, 'Mairie Cocody'),
(49, 'MTN'),
(20, 'N/A'),
(88, 'NELSON RE'),
(110, 'NIKKOSSA'),
(69, 'NSIA'),
(38, 'NSIA IARD'),
(74, 'ONEP'),
(41, 'Orange'),
(5, 'Orange Côte d\'Ivoire'),
(36, 'Orange Money'),
(114, 'OVERNETFLOW'),
(48, 'PAA'),
(61, 'PGT'),
(72, 'PIC'),
(59, 'PN'),
(92, 'PRIME'),
(58, 'Procure'),
(50, 'PwC'),
(8, 'QuanTech Côte d\'Ivoire'),
(33, 'RCI'),
(53, 'RYCA-PHARMA'),
(47, 'SACO'),
(43, 'SAKO'),
(91, 'SAVENCIA'),
(87, 'SGABS'),
(71, 'SGBCI'),
(81, 'SGCI'),
(63, 'SIATRAIL'),
(37, 'SIB'),
(54, 'SIGFAE'),
(86, 'SIR'),
(78, 'Smart Group'),
(31, 'SNDI'),
(10, 'SODECI'),
(109, 'SUCAF'),
(103, 'Testing Factory'),
(100, 'TOURABI'),
(62, 'Trésor'),
(45, 'Turione'),
(7, 'Tuzzo Côte d\'Ivoire'),
(68, 'UA Vie'),
(21, 'UMOA'),
(28, 'UNILEVER'),
(42, 'Univ Cocody');

-- --------------------------------------------------------

--
-- Table structure for table `etudiants`
--

DROP TABLE IF EXISTS `etudiants`;
CREATE TABLE IF NOT EXISTS `etudiants` (
  `num_etu` int NOT NULL AUTO_INCREMENT,
  `nom_etu` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `prenom_etu` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `email_etu` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `date_naiss_etu` date NOT NULL,
  `genre_etu` enum('Homme','Femme','Neutre') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `promotion_etu` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`num_etu`)
) ENGINE=InnoDB AUTO_INCREMENT=20260002 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `etudiants`
--

INSERT INTO `etudiants` (`num_etu`, `nom_etu`, `prenom_etu`, `email_etu`, `date_naiss_etu`, `genre_etu`, `promotion_etu`) VALUES
(20099001, 'BODJE', 'Nko', 'nko.bodje@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099002, 'AKINOLA', 'Alexis', 'alexis.akinola@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099003, 'OUEDRAOGO', 'Salif Issa', 'salifissa.ouedraogo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099004, 'DIALLO', 'Mamadou', 'mamadou.diallo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099005, 'DEGNI', 'N\'Din Ouyo MC', 'ndinouyomc.degni@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099006, 'BEDI', 'Lasme Laurent', 'lasmelaurent.bedi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099007, 'AYEGON', 'Manlan Doris', 'manlandoris.ayegon@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099008, 'N\'GBO', 'Yapo Joseph', 'yapojoseph.ngbo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099009, 'LOBA', 'Badjo Caroline', 'badjocaroline.loba@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099010, 'KOUAME', 'Ayoua Alain', 'ayouaalain.kouame@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20099011, 'ATTISSOU', 'Ekoué J.F.', 'ekouejf.attissou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2009'),
(20100001, 'KOUASSI', 'Jean-Baptiste', 'jeanbaptiste.kouassi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109001, 'ADJA', 'Willy Junior', 'willyjunior.adja@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109002, 'OUSSOU', 'Ipou', 'ipou.oussou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109003, 'ATTISSOU', 'Ekoué', 'ekoue.attissou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109004, 'NGBO', 'Marie Joseph', 'mariejoseph.ngbo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109005, 'DOBE', 'Anicet', 'anicet.dobe@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109006, 'KABRAN', 'Jules César', 'julescesar.kabran@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109007, 'TOURE', 'Ferdinand', 'ferdinand.toure@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109008, 'NIAMKEY', 'Marie Eve', 'marieeve.niamkey@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109009, 'MONNEY', 'Sylvain Aké', 'sylvainake.monney@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109010, 'GORE', 'Ange Roland', 'angeroland.gore@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109011, 'OUATTARA', 'Nouhoun', 'nouhoun.ouattara@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109012, 'NGUESSAN', 'Djeket Aimé', 'djeketaime.nguessan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109013, 'MARIKO', 'Eba Raissa', 'ebaraissa.mariko@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109014, 'KACOU', 'Sandrine G.', 'sandrineg.kacou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109015, 'GOHOUROU', 'Didier', 'didier.gohourou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109016, 'KOUA', 'Konin Ngoran', 'koninngoran.koua@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109017, 'KEI', 'Ninsemon Hervé', 'ninsemonherve.kei@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20109018, 'ASSI', 'Yves Landry', 'yveslandry.assi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2010'),
(20119001, 'ANGO', 'Charles Erwan', 'charleserwan.ango@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119002, 'ACHO', 'Ivan', 'ivan.acho@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119003, 'KOUAME', 'Nwoley', 'nwoley.kouame@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119004, 'SAWADOGO', 'Moussa', 'moussa.sawadogo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119005, 'ASSI', 'Landry', 'landry.assi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119006, 'MONNE', 'Sylvain M.', 'sylvainm.monne@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119007, 'TOURE', 'Ferdinand', 'ferdinand.toure@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119008, 'KACON', 'N.G Sandrine', 'ngsandrine.kacon@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119009, 'HOUSSOU', '', '.houssou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119010, 'AYENON', 'Malan Doris', 'malandoris.ayenon@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119011, 'KANGAH', 'Didier Frank', 'didierfrank.kangah@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119012, 'ADAMA', '', '.adama@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119013, 'KOFFI', 'Kouakou Eric', 'kouakoueric.koffi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119014, 'AYEGON', 'Prosper', 'prosper.ayegon@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119015, 'DOUAMPO', 'Djro Berthe', 'djroberthe.douampo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119016, 'DONGO', 'Adjehi Yannick', 'adjehiyannick.dongo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119017, 'NAMOGO', 'Soro Christian', 'sorochristian.namogo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119018, 'YETE', 'Jean Philippe', 'jeanphilippe.yete@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119019, 'LOBE', 'Ogonnin Gédéon', 'ogonningedeon.lobe@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119020, 'NINSEMON', 'Hervé', 'herve.ninsemon@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119021, 'GOEH', 'Fabrice Adoté', 'fabriceadote.goeh@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119022, 'FAGLA', 'Armel Jean', 'armeljean.fagla@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119023, 'GNAYORO', 'Dano Hugues', 'danohugues.gnayoro@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119024, 'KOUADIO', 'Loukou Arnaud', 'loukouarnaud.kouadio@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119025, 'KOUASSI', 'Kamelan H.', 'kamelanh.kouassi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119026, 'KOUADIO', 'Hugues', 'hugues.kouadio@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119027, 'GOSAN', 'Akon Boris', 'akonboris.gosan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119028, 'NGUESSAN', 'Ahoke Lazare', 'ahokelazare.nguessan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119029, 'DJEDJE', 'Monoko Arthur', 'monokoarthur.djedje@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119030, 'ANON', 'Adjouapoh N.', 'adjouapohn.anon@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119031, 'KOUASSI', 'N\'Dri Yves', 'ndriyves.kouassi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119032, 'OUSSOU', 'Romaric', 'romaric.oussou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20119033, 'AKEBOUE', 'Monne Hervé', 'monneherve.akeboue@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2011'),
(20129001, 'KOFFI', 'Stéphane P.', 'stephanep.koffi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129002, 'KOFFI', 'Guetta Jean', 'guettajean.koffi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129003, 'KOFFI', 'Stéphane P.', 'stephanep.koffi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129004, 'KOFFI', 'Guetta Jean', 'guettajean.koffi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129005, 'OSSEIN', 'Franck Devy', 'franckdevy.ossein@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129006, 'BAILLY', 'Gouda Arnaud', 'goudaarnaud.bailly@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129007, 'NINDJIN', 'Malan Alain', 'malanalain.nindjin@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129008, 'NGUESSAN', 'Tecleky', 'tecleky.nguessan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129009, 'OUEDRAOGO', 'Salifou', 'salifou.ouedraogo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129010, 'OSSEIN', 'Franck Davy', 'franckdavy.ossein@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129011, 'NINDJIN', 'Malan Alain', 'malanalain.nindjin@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129012, 'KANTE', 'Nene', 'nene.kante@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129013, 'N\'GUESSAN', 'Djeket Aimé', 'djeketaime.nguessan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129014, 'FOFANA', 'N\'Vally', 'nvally.fofana@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129015, 'AMICHIA', 'Jean Marie', 'jeanmarie.amichia@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129016, 'OUATTARA', 'Perbin Parfait', 'perbinparfait.ouattara@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129017, 'ATTIEMBONE', 'Kouadio C.', 'kouadioc.attiembone@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129018, 'KOUAME', 'N\'Woley Kevin', 'nwoleykevin.kouame@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129019, 'MONNE', 'Sylvain M.', 'sylvainm.monne@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20129020, 'DOOU', 'Serge Baulais', 'sergebaulais.doou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2012'),
(20139001, 'KOUADIO', 'Stéphane K.', 'stephanek.kouadio@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139002, 'KOULOUBLA', 'Dakouri A.', 'dakouria.kouloubla@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139003, 'DIAWARA', 'Daoud Ben', 'daoudben.diawara@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139004, 'DIAW', 'Oumar Yao', 'oumaryao.diaw@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139005, 'KOBENA', 'Atta Achile', 'attaachile.kobena@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139006, 'TANOH', 'Adjoua M.', 'adjouam.tanoh@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139007, 'BEUGRE', 'Wallon M.C.', 'wallonmc.beugre@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139008, 'KONAN', 'Konan J.F.', 'konanjf.konan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139009, 'FLAN', 'Zede Delphin', 'zededelphin.flan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139010, 'ATTRO', 'Kouassi Elvis', 'kouassielvis.attro@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139011, 'KOUAME', 'Christian K.', 'christiank.kouame@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139012, 'ASSI', 'Yves Landry', 'yveslandry.assi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139013, 'SYLLA', 'Mohamed', 'mohamed.sylla@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139014, 'TRAORE', 'Christelle R.', 'christeller.traore@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139015, 'DIARRA- SOUBA', 'Nahouo S.', 'nahouos.diarrasouba@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20139016, 'YAO', 'Kouakou Brice', 'kouakoubrice.yao@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2013'),
(20159001, 'N\'GUESSAN', 'Yves Martial', 'yvesmartial.nguessan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159002, 'BLE', 'Anoh Désiré', 'anohdesire.ble@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159003, 'KOUAME', 'Ahou Anita', 'ahouanita.kouame@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159004, 'TRA', 'Lou Colombe', 'loucolombe.tra@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159005, 'ABOULE', 'Kobo Jeanne', 'kobojeanne.aboule@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159006, 'FRONDO', 'Jean Daniel', 'jeandaniel.frondo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159007, 'KOULATE', 'Douai Yves', 'douaiyves.koulate@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159008, 'KONAN', 'Yao Franck', 'yaofranck.konan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159009, 'DIAHOU', 'Chiayé C.', 'chiayec.diahou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159010, 'BOBOU', 'Josué Eliezer', 'josueeliezer.bobou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159011, 'DJIDJI', 'Kadjo D.', 'kadjod.djidji@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20159012, 'AGNARAMON', 'Boris Carnot', 'boriscarnot.agnaramon@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2015'),
(20169001, 'TONOHOAN', 'Oza Maguy', 'ozamaguy.tonohoan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2016'),
(20169002, 'KOUAKOU', 'De Laure A.', 'delaurea.kouakou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2016'),
(20169003, 'OBA', 'Stéphane', 'stephane.oba@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2016'),
(20169004, 'KARDIOULA', 'Oumar', 'oumar.kardioula@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2016'),
(20169005, 'DJIDJI', 'Kadjo D.', 'kadjod.djidji@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2016'),
(20169006, 'KOKI', 'Israel', 'israel.koki@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2016'),
(20169007, 'BLAGUE', 'Ségui Noel', 'seguinoel.blague@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2016'),
(20169008, 'KOFFI', 'Katché Olivier', 'katcheolivier.koffi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2016'),
(20179001, 'DOGO', 'Vih Modeste', 'vihmodeste.dogo@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20179002, 'KOUAO', 'Valdez E.', 'valdeze.kouao@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20179003, 'TRA BI', 'Modeste', 'modeste.trabi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20179004, 'KESSE', 'Poté Senaho', 'potesenaho.kesse@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20179006, 'GAYE', 'Mehibo Sylv.', 'mehibosylv.gaye@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20179008, 'COULIBALY', 'Abdoul Karim', 'abdoulkarim.coulibaly@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20179009, 'N\'ZI', 'Yao Sidney', 'yaosidney.nzi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20179010, 'OUATTARA', 'Kobenan L.', 'kobenanl.ouattara@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20179011, 'COULOU', 'Kouadio L.', 'kouadiol.coulou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20179012, 'KONAN', 'Yao Franck', 'yaofranck.konan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2017'),
(20220001, 'Irie', 'Adjo Jemima', 'iriejemima@gmail.com', '2001-01-01', 'Femme', '2022-2023'),
(20220002, 'Akandan Aho', 'Paul', 'ahopaul@gmail.com', '2004-03-30', 'Homme', '2022-2023'),
(20229001, 'KOUAO', 'Aye Boris', 'ayeboris.kouao@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229002, 'KONAN', 'Serge Landry', 'sergelandry.konan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229003, 'AYA', 'Josiane C.', 'josianec.aya@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229004, 'GNOGAN', 'Amichia Paul', 'amichiapaul.gnogan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229006, 'OUATTARA', 'Zélé', 'zele.ouattara@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229007, 'DJECKET', 'Aniela Carly', 'anielacarly.djecket@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229008, 'DIARRA- SOUBA', 'Siaka', 'siaka.diarrasouba@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229009, 'YAO', 'Josué Kouakou', 'josuekouakou.yao@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229010, 'DIARRA- SOUBA', 'Mohamed', 'mohamed.diarrasouba@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229011, 'SALIFOU', 'Georges-Erwin', 'georgeserwin.salifou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229012, 'DOUASSE', 'Kouetho', 'kouetho.douasse@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229013, 'VANIE', 'Alain Charles', 'alaincharles.vanie@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229014, 'BIDI', 'Koudou Paul', 'koudoupaul.bidi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229015, 'HOUNDJI', 'Gnimassoun L.', 'gnimassounl.houndji@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229016, 'KOUAO', 'Ayé Boris', 'ayeboris.kouao@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229017, 'LAGO', 'Aya Josiane', 'ayajosiane.lago@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229018, 'YAO-SAKY', 'Marlène M.', 'marlenem.yaosaky@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229019, 'BOKA', 'Chiadon Anita', 'chiadonanita.boka@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229020, 'SORO', 'Diabiga Kader', 'diabigakader.soro@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229021, 'BROU', 'Arthur Fiacre', 'arthurfiacre.brou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229022, 'ADOU', 'Lorraine V.', 'lorrainev.adou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20229023, 'KOUASSI', 'Oumar O.', 'oumaro.kouassi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2022'),
(20230001, 'KIOHON', 'Jean François', 'jeanfrancois.kiohon@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20230002, 'COULIBALY', 'Othniel S.', 'othniels.coulibaly@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20230003, 'BAKAYOKO', 'Myriam Anna', 'myriamanna.bakayoko@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20230004, 'KOFFI', 'Kouakou Kan', 'kouakoukan.koffi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20230005, 'SOULEY', 'Aremou Malick', 'aremoumalick.souley@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20230006, 'KONE', 'Ibrahim', 'ibrahim.kone@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20230009, 'ATSE', 'Moye Kouassi', 'moyekouassi.atse@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20230010, 'BALIE', 'Gnahoua Akenou', 'gnahouaakenou.balie@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20230011, 'KADOUNO', 'Jean Louis', 'jeanlouis.kadouno@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20230012, 'KONAN', 'Serge Landry', 'sergelandry.konan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2023'),
(20240001, 'OYOU', 'Assoko Paul', 'assokopaul.oyou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240002, 'TANO', 'Kouadio B.', 'kouadiob.tano@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240003, 'BOUEDIRO', 'Djamoin Steve', 'djamoinsteve.bouediro@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240004, 'SOHOU', 'Marc-Arthur', 'marcarthur.sohou@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240005, 'SORO', 'Foungnigue', 'foungnigue.soro@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240006, 'SORO', 'Kolo Siaka', 'kolosiaka.soro@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240007, 'KOISSI', 'Elysée Morel', 'elyseemorel.koissi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240010, 'NGUESSAN', 'Tecleky Vidal', 'teclekyvidal.nguessan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240011, 'ANOMA', 'Dadié J.C.', 'dadiejc.anoma@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240012, 'SORO', 'Katiénefowa', 'katienefowa.soro@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240013, 'KOFFI', 'Cyl B', 'cylb.koffi@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240015, 'TOURE', 'Sounkaro K.', 'sounkarok.toure@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240016, 'KOUAME', 'Kouassi Oscar', 'kouassioscar.kouame@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024'),
(20240017, 'N\'GUESSAN', 'Hachley Aurore', 'hachleyaurore.nguessan@student.ufhb.edu.ci', '2000-01-01', 'Neutre', '2024');

-- --------------------------------------------------------

--
-- Table structure for table `evaluations_rapports`
--

DROP TABLE IF EXISTS `evaluations_rapports`;
CREATE TABLE IF NOT EXISTS `evaluations_rapports` (
  `id_evaluation` int NOT NULL AUTO_INCREMENT,
  `id_rapport` int NOT NULL,
  `id_evaluateur` int NOT NULL,
  `decision_evaluation` enum('valider','rejeter') DEFAULT NULL,
  `commentaire` text,
  `date_evaluation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evaluation`),
  KEY `id_evaluateur` (`id_evaluateur`),
  KEY `id_rapport` (`id_rapport`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `evaluations_rapports`
--

INSERT INTO `evaluations_rapports` (`id_evaluation`, `id_rapport`, `id_evaluateur`, `decision_evaluation`, `commentaire`, `date_evaluation`, `date_modification`) VALUES
(21, 16, 7, 'valider', 'il est bon ce rapport', '2025-09-29 23:10:04', NULL),
(22, 16, 18, 'valider', 'c\'est bien', '2025-09-29 23:11:48', NULL),
(23, 16, 19, 'valider', 'bon rapport\r\n', '2025-09-29 23:13:08', NULL),
(24, 16, 23, 'valider', 'je suis impatien de le voir a sa soutenance', '2025-09-29 23:19:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `evaluer`
--

DROP TABLE IF EXISTS `evaluer`;
CREATE TABLE IF NOT EXISTS `evaluer` (
  `num_etudiant` int NOT NULL,
  `num_jury` int NOT NULL,
  `id_critere` int NOT NULL,
  `date_eval` date NOT NULL,
  `note` double NOT NULL,
  PRIMARY KEY (`num_etudiant`,`num_jury`,`id_critere`),
  KEY `id_critere` (`id_critere`),
  KEY `num_jury` (`num_jury`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `evaluer`
--

INSERT INTO `evaluer` (`num_etudiant`, `num_jury`, `id_critere`, `date_eval`, `note`) VALUES
(20220001, 1, 3, '2025-10-15', 3),
(20220001, 1, 4, '2025-10-15', 5),
(20220001, 1, 5, '2025-10-15', 2),
(20220001, 1, 6, '2025-10-15', 2),
(20220001, 1, 7, '2025-10-15', 3.5);

-- --------------------------------------------------------

--
-- Table structure for table `filiere`
--

DROP TABLE IF EXISTS `filiere`;
CREATE TABLE IF NOT EXISTS `filiere` (
  `id_filiere` int NOT NULL AUTO_INCREMENT,
  `lib_filiere` varchar(100) NOT NULL,
  PRIMARY KEY (`id_filiere`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `fonction`
--

DROP TABLE IF EXISTS `fonction`;
CREATE TABLE IF NOT EXISTS `fonction` (
  `id_fonction` int NOT NULL AUTO_INCREMENT,
  `lib_fonction` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_fonction`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `fonction`
--

INSERT INTO `fonction` (`id_fonction`, `lib_fonction`) VALUES
(2, 'Doyen de faculté'),
(3, 'Directeur de recherche'),
(5, 'Directeur pédagogique'),
(7, 'Professeur titulaire'),
(8, 'Maître de conférences'),
(9, 'Chargé de cours'),
(10, 'Assistant d\'enseignement'),
(11, 'Chef de département'),
(12, 'Responsable de programme'),
(13, 'Coordonnateur pédagogique'),
(14, 'Directeur de laboratoire'),
(15, 'Encadreur de mémoire'),
(16, 'Enseignant vacataire'),
(17, 'Expert externe'),
(18, 'Secrétaire scientifique'),
(19, 'Président de jury'),
(20, 'Conseiller pédagogique');

-- --------------------------------------------------------

--
-- Table structure for table `fonctionnalites`
--

DROP TABLE IF EXISTS `fonctionnalites`;
CREATE TABLE IF NOT EXISTS `fonctionnalites` (
  `id_fonctionnalite` int NOT NULL AUTO_INCREMENT,
  `id_categorie` int NOT NULL,
  `code_fonctionnalite` varchar(50) NOT NULL,
  `lib_fonctionnalite` varchar(100) NOT NULL,
  `label_fonctionnalite` varchar(150) DEFAULT NULL,
  `description_fonctionnalite` text,
  `url_fonctionnalite` varchar(255) NOT NULL,
  `icone_fonctionnalite` varchar(100) DEFAULT NULL,
  `ordre_fonctionnalite` int DEFAULT '0',
  `est_sous_page` tinyint(1) DEFAULT '0',
  `page_parente` varchar(50) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_fonctionnalite`),
  UNIQUE KEY `code_fonctionnalite` (`code_fonctionnalite`),
  KEY `id_categorie` (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `fonctionnalites`
--

INSERT INTO `fonctionnalites` (`id_fonctionnalite`, `id_categorie`, `code_fonctionnalite`, `lib_fonctionnalite`, `label_fonctionnalite`, `description_fonctionnalite`, `url_fonctionnalite`, `icone_fonctionnalite`, `ordre_fonctionnalite`, `est_sous_page`, `page_parente`, `actif`, `date_creation`) VALUES
(1, 16, 'DASH_GLOBAL', 'Dashboard Global', 'Vue d\'ensemble', NULL, '?page=dashboard', 'fas fa-tachometer-alt', 1, 1, 'ADM_DASHBOARD', 1, '2026-01-05 22:57:10'),
(2, 15, 'DASH_ENSEIGNANT', 'Dashboard Enseignant', 'Mon espace enseignant', NULL, '?page=dashboard_enseignant', 'fas fa-chalkboard-teacher', 1, 1, 'COM_ESPACES', 1, '2026-01-05 22:57:10'),
(3, 13, 'DASH_SCOLARITE', 'Dashboard Scolarité', 'Suivi scolarité', NULL, '?page=dashboard_scolarite', 'fas fa-school', 3, 0, NULL, 1, '2026-01-05 22:57:10'),
(4, 16, 'DASH_SECRETAIRE', 'Dashboard Secrétaire', 'Gestion administrative', NULL, '?page=dashboard_secretaire', 'fas fa-user-tie', 2, 1, 'ADM_PARAMETRAGE', 1, '2026-01-05 22:57:10'),
(5, 15, 'DASH_COMMISSION', 'Dashboard Commission', 'Suivi commission', NULL, '?page=dashboard_commission', 'fas fa-users', 1, 1, 'COM_GESTION', 1, '2026-01-05 22:57:10'),
(6, 13, 'ETU_INSCRIPTION', 'Inscription Étudiants', 'Ajouter/Inscrire', NULL, '?page=gestion_etudiants', 'fas fa-user-plus', 1, 1, 'SCOL_INSCRIPTIONS', 1, '2026-01-05 22:57:10'),
(7, 15, 'ETU_LISTE_ENS', 'Liste Étudiants Enseignant', 'Mes étudiants', NULL, '?page=liste_etudiants_ens', 'fas fa-list', 2, 1, 'COM_ESPACES', 1, '2026-01-05 22:57:10'),
(8, 13, 'ETU_LISTE_RESP', 'Liste Étudiants Responsable', 'Tous les étudiants', NULL, '?page=liste_etudiants_resp', 'fas fa-list-alt', 1, 1, 'SCOL_CONSULTATION', 1, '2026-01-05 22:57:10'),
(9, 14, 'RAPP_HUB', 'Gestion Rapports', 'Mes rapports', NULL, '?page=gestion_rapports', 'fas fa-folder-open', 1, 1, 'ETU_SUIVI', 1, '2026-01-05 22:57:10'),
(10, 14, 'RAPP_CREER', 'Créer Rapport', 'Nouveau rapport', NULL, '?page=gestion_rapports&action=creer_rapport', 'fas fa-file-medical', 2, 1, NULL, 1, '2026-01-05 22:57:10'),
(11, 14, 'RAPP_SUIVI', 'Suivi Rapports', 'Suivre mes rapports', NULL, '?page=gestion_rapports&action=suivi_rapport', 'fas fa-tasks', 3, 1, NULL, 1, '2026-01-05 22:57:10'),
(12, 15, 'RAPP_VERIF', 'Vérification Rapports', 'Approuver rapports', NULL, '?page=verification_rapports', 'fas fa-clipboard-check', 1, 1, 'COM_RAPPORTS', 1, '2026-01-05 22:57:10'),
(13, 14, 'CAND_SOUMETTRE', 'Soumettre Candidature', 'Ma candidature', NULL, '?page=candidature_soutenance', 'fas fa-file-signature', 1, 1, 'ETU_CANDIDATURE', 1, '2026-01-05 22:57:10'),
(14, 13, 'CAND_EXAMINER', 'Examiner Candidatures', 'Examen scolarité', NULL, '?page=gestion_candidatures', 'fas fa-search', 2, 1, 'SCOL_CONSULTATION', 1, '2026-01-05 22:57:10'),
(15, 15, 'CAND_VALIDER', 'Valider Candidatures', 'Validation commission', NULL, '?page=verification_candidatures', 'fas fa-stamp', 2, 1, 'COM_GESTION', 1, '2026-01-05 22:57:10'),
(16, 15, 'VALID_PROCESSUS', 'Processus Validation', 'Suivi validation', NULL, '?page=processus_validation', 'fas fa-stream', 1, 0, NULL, 1, '2026-01-05 22:57:10'),
(17, 15, 'VALID_EVALUER', 'Évaluation Dossiers', 'Évaluer dossiers', NULL, '?page=evaluation_dossiers', 'fas fa-vote-yea', 2, 1, 'COM_EVALUATION', 1, '2026-01-05 22:57:10'),
(18, 15, 'SOUT_PROGRAMMER', 'Programmer Jury', 'Composer jury', NULL, '?page=programmation_soutenance', 'fas fa-user-friends', 1, 1, 'COM_JURY', 1, '2026-01-05 22:57:10'),
(19, 15, 'SOUT_PLANIFIER', 'Planifier Soutenance', 'Date/Heure/Salle', NULL, '?page=planification_soutenance', 'fas fa-calendar-check', 3, 1, 'COM_GESTION', 1, '2026-01-05 22:57:10'),
(20, 15, 'SOUT_EVALUER', 'Évaluer Soutenance', 'Grille évaluation', NULL, '?page=evaluation_soutenance', 'fas fa-star', 1, 1, 'COM_EVALUATION', 1, '2026-01-05 22:57:10'),
(21, 15, 'CR_HUB', 'Comptes Rendus', 'Mes comptes rendus', NULL, '?page=redaction_compte_rendu', 'fas fa-pen', 1, 1, NULL, 1, '2026-01-05 22:57:10'),
(22, 15, 'CR_REDACTION', 'Rédaction', 'Rédiger CR', NULL, '?page=redaction_compte_rendu', 'fas fa-edit', 2, 1, NULL, 1, '2026-01-05 22:57:10'),
(23, 15, 'CR_BROUILLONS', 'Brouillons', 'Mes brouillons', NULL, '?page=redaction_compte_rendu&action=brouillons', 'fas fa-save', 3, 1, NULL, 1, '2026-01-05 22:57:10'),
(24, 15, 'CR_ARCHIVES', 'Archives', 'CR archivés', NULL, '?page=redaction_compte_rendu&action=archives', 'fas fa-archive', 4, 1, NULL, 1, '2026-01-05 22:57:10'),
(25, 14, 'NOTES_CONSULTER', 'Mes Résultats', 'Bulletin de notes', NULL, '?page=notes_resultats', 'fas fa-poll', 1, 1, 'ETU_RESULTATS', 1, '2026-01-05 22:57:10'),
(26, 13, 'NOTES_SAISIR', 'Saisie Notes', 'Entrer notes', NULL, '?page=gestion_notes', 'fas fa-keyboard', 1, 1, 'SCOL_EVALUATIONS', 1, '2026-01-05 22:57:10'),
(27, 14, 'RECL_HUB_ETU', 'Mes Réclamations', 'Soumettre réclamation', NULL, '?page=gestion_reclamations', 'fas fa-comment-alt', 1, 1, 'ETU_RECLAMATIONS', 1, '2026-01-05 22:57:10'),
(28, 13, 'RECL_GESTION', 'Gestion Réclamations', 'Toutes réclamations', NULL, '?page=gestion_reclamations_scolarite', 'fas fa-tasks', 1, 1, 'SCOL_RECLAMATIONS', 1, '2026-01-05 22:57:10'),
(29, 16, 'RH_GESTION', 'Gestion Personnel', 'Personnel', NULL, '?page=gestion_rh', 'fas fa-id-card', 1, 1, 'ADM_REFERENTIEL', 1, '2026-01-05 22:57:10'),
(30, 16, 'PARAM_HUB', 'Paramètres Généraux', 'Configuration', NULL, '?page=parametres_generaux', 'fas fa-cogs', 1, 1, 'ADM_PARAMETRAGE', 1, '2026-01-05 22:57:10'),
(31, 16, 'PARAM_ACTIONS', 'Actions Système', 'Actions', NULL, '?page=parametres_generaux&action=actions', 'fas fa-bolt', 2, 1, NULL, 1, '2026-01-05 22:57:10'),
(32, 16, 'PARAM_ANNEES', 'Années Académiques', 'Années', NULL, '?page=parametres_generaux&action=annees_academiques', 'fas fa-calendar', 3, 1, NULL, 1, '2026-01-05 22:57:10'),
(33, 16, 'PARAM_CRITERES', 'Critères Évaluation', 'Critères', NULL, '?page=parametres_generaux&action=criteres_evaluation', 'fas fa-list-ol', 4, 1, NULL, 1, '2026-01-05 22:57:10'),
(34, 16, 'PARAM_ECUE', 'ECUE', 'Éléments UE', NULL, '?page=parametres_generaux&action=ecue', 'fas fa-puzzle-piece', 5, 1, NULL, 1, '2026-01-05 22:57:10'),
(35, 16, 'PARAM_ENTREPRISES', 'Entreprises', 'Base entreprises', NULL, '?page=parametres_generaux&action=entreprises', 'fas fa-building', 6, 1, NULL, 1, '2026-01-05 22:57:10'),
(36, 16, 'PARAM_FONCTIONS', 'Fonctions Personnel', 'Fonctions', NULL, '?page=parametres_generaux&action=fonctions', 'fas fa-briefcase', 7, 1, NULL, 1, '2026-01-05 22:57:10'),
(37, 16, 'PARAM_FONC_USER', 'Fonctions Utilisateurs', 'Rôles', NULL, '?page=parametres_generaux&action=fonction_utilisateur', 'fas fa-user-tag', 8, 1, NULL, 1, '2026-01-05 22:57:10'),
(38, 16, 'PARAM_ATTRIB', 'Gestion Attributions', 'Permissions', NULL, '?page=parametres_generaux&action=gestion_attribution', 'fas fa-key', 9, 1, NULL, 1, '2026-01-05 22:57:10'),
(39, 16, 'PARAM_GRADES', 'Grades Enseignants', 'Grades', NULL, '?page=parametres_generaux&action=grades', 'fas fa-medal', 10, 1, NULL, 1, '2026-01-05 22:57:10'),
(40, 16, 'PARAM_MESSAGES', 'Messages Système', 'Messages', NULL, '?page=parametres_generaux&action=messages', 'fas fa-envelope', 11, 1, NULL, 1, '2026-01-05 22:57:10'),
(41, 16, 'PARAM_NIV_ACCES', 'Niveaux Accès', 'Accès', NULL, '?page=parametres_generaux&action=niveaux_acces', 'fas fa-lock', 12, 1, NULL, 1, '2026-01-05 22:57:10'),
(42, 16, 'PARAM_NIV_APPRO', 'Niveaux Approbation', 'Workflow', NULL, '?page=parametres_generaux&action=niveaux_approbation', 'fas fa-sitemap', 13, 1, NULL, 1, '2026-01-05 22:57:10'),
(43, 16, 'PARAM_NIV_ETUDE', 'Niveaux Étude', 'M1/M2', NULL, '?page=parametres_generaux&action=niveaux_etude', 'fas fa-layer-group', 14, 1, NULL, 1, '2026-01-05 22:57:10'),
(44, 16, 'PARAM_SALLES', 'Salles', 'Salles soutenance', NULL, '?page=parametres_generaux&action=salles', 'fas fa-door-open', 15, 1, NULL, 1, '2026-01-05 22:57:10'),
(45, 16, 'PARAM_SEMESTRES', 'Semestres', 'Semestres', NULL, '?page=parametres_generaux&action=semestres', 'fas fa-calendar-week', 16, 1, NULL, 1, '2026-01-05 22:57:10'),
(46, 16, 'PARAM_SPECIALITES', 'Spécialités', 'Spécialités', NULL, '?page=parametres_generaux&action=specialites', 'fas fa-graduation-cap', 17, 1, NULL, 1, '2026-01-05 22:57:10'),
(47, 16, 'PARAM_STATUT_JURY', 'Statuts Jury', 'Rôles jury', NULL, '?page=parametres_generaux&action=statut_jury', 'fas fa-user-shield', 18, 1, NULL, 1, '2026-01-05 22:57:10'),
(48, 16, 'PARAM_TRAITEMENTS', 'Traitements Menu', 'Menu actuel', NULL, '?page=parametres_generaux&action=traitements', 'fas fa-bars', 19, 1, NULL, 1, '2026-01-05 22:57:10'),
(49, 16, 'PARAM_UE', 'UE', 'Unités Enseignement', NULL, '?page=parametres_generaux&action=ue', 'fas fa-book', 20, 1, NULL, 1, '2026-01-05 22:57:10'),
(50, 16, 'SYS_UTILISATEURS', 'Gestion Utilisateurs', 'Utilisateurs', NULL, '?page=gestion_utilisateurs', 'fas fa-users-cog', 1, 1, 'ADM_SECURITE', 1, '2026-01-05 22:57:10'),
(51, 16, 'SYS_AUDIT', 'Piste Audit', 'Journal audit', NULL, '?page=piste_audit', 'fas fa-history', 2, 1, 'ADM_SECURITE', 1, '2026-01-05 22:57:10'),
(52, 16, 'SYS_BACKUP', 'Sauvegarde/Restauration', 'Backup', NULL, '?page=sauvegarde_restauration', 'fas fa-database', 1, 1, 'ADM_SYSTEME', 1, '2026-01-05 22:57:10'),
(53, 13, 'ETU_SCOLARITE', 'Gestion Scolarité', 'Scolarité', 'Gestion de la scolarité des étudiants', '?page=gestion_scolarite', 'fas fa-money-bill', 2, 1, 'SCOL_INSCRIPTIONS', 1, '2026-01-14 19:01:01'),
(54, 13, 'CAND_DOSSIERS', 'Gestion Dossiers Candidatures', 'Dossiers vérifiés', 'Gestion des dossiers de candidatures vérifiés', '?page=gestion_dossiers_candidatures', 'fas fa-folder-open', 1, 1, 'SCOL_DOSSIERS', 1, '2026-01-14 19:01:01'),
(55, 16, 'SYS_HISTORIQUE', 'Historique et Archivage', 'Historique', 'Historique et archivage des données', '?page=admin_historique', 'fas fa-archive', 2, 1, 'ADM_SYSTEME', 1, '2026-01-14 19:01:01'),
(56, 15, 'RAPP_VALIDER', 'Rapports à Valider', 'Approuver rapports', 'Approuver les rapports des étudiants', '?page=rapport_a_valider', 'fas fa-check-circle', 5, 0, NULL, 1, '2026-01-14 19:01:02'),
(57, 15, 'VALID_EVAL_SOUT', 'Évaluation Dossiers Soutenance', 'Évaluer dossiers soutenance', 'Évaluation des dossiers de soutenance', '?page=evaluations_dossiers_soutenance', 'fas fa-file-contract', 3, 1, 'COM_EVALUATION', 1, '2026-01-14 19:01:02'),
(58, 13, 'NOTES_EVAL', 'Gestion Notes Évaluations', 'Notes et évaluations', 'Gestion des notes et évaluations', '?page=gestion_notes_evaluations', 'fas fa-chart-bar', 2, 1, 'SCOL_EVALUATIONS', 1, '2026-01-14 19:01:02'),
(59, 15, 'CR_ARCH_MAIN', 'Archives Comptes Rendus', 'Archives CR', 'Archives des comptes rendus', '?page=archive_comptes_rendus', 'fas fa-box-archive', 2, 1, 'COM_RAPPORTS', 1, '2026-01-14 19:01:02'),
(60, 13, 'SCOL_INSCRIPTIONS', 'Inscriptions', 'Inscriptions', NULL, '#', 'fas fa-user-plus', 10, 0, NULL, 1, '2026-01-24 22:09:02'),
(61, 13, 'SCOL_EVALUATIONS', 'Évaluations', 'Évaluations', NULL, '#', 'fas fa-clipboard-list', 20, 0, NULL, 1, '2026-01-24 22:09:02'),
(62, 13, 'SCOL_RECLAMATIONS', 'Réclamations', 'Réclamations', NULL, '#', 'fas fa-exclamation-circle', 30, 0, NULL, 1, '2026-01-24 22:09:02'),
(63, 13, 'SCOL_DOSSIERS', 'Dossiers', 'Dossiers', NULL, '#', 'fas fa-folder-open', 40, 0, NULL, 1, '2026-01-24 22:09:02'),
(64, 13, 'SCOL_CONSULTATION', 'Consultation', 'Consultation', NULL, '#', 'fas fa-search', 50, 0, NULL, 1, '2026-01-24 22:09:02'),
(65, 14, 'ETU_CANDIDATURE', 'Candidature', 'Candidature', NULL, '#', 'fas fa-file-signature', 10, 0, NULL, 1, '2026-01-24 22:09:02'),
(66, 14, 'ETU_RESULTATS', 'Résultats', 'Résultats', NULL, '#', 'fas fa-poll', 20, 0, NULL, 1, '2026-01-24 22:09:02'),
(67, 14, 'ETU_RECLAMATIONS', 'Réclamations', 'Réclamations', NULL, '#', 'fas fa-comment-alt', 30, 0, NULL, 1, '2026-01-24 22:09:02'),
(68, 14, 'ETU_SUIVI', 'Suivi', 'Suivi', NULL, '#', 'fas fa-folder-open', 40, 0, NULL, 1, '2026-01-24 22:09:02'),
(69, 15, 'COM_GESTION', 'Gestion commissions', 'Gestion commissions', NULL, '#', 'fas fa-users', 10, 0, NULL, 1, '2026-01-24 22:09:02'),
(70, 15, 'COM_JURY', 'Jury', 'Jury', NULL, '#', 'fas fa-user-friends', 20, 0, NULL, 1, '2026-01-24 22:09:02'),
(71, 15, 'COM_EVALUATION', 'Évaluation', 'Évaluation', NULL, '#', 'fas fa-star', 30, 0, NULL, 1, '2026-01-24 22:09:02'),
(72, 15, 'COM_RAPPORTS', 'Rapports', 'Rapports', NULL, '#', 'fas fa-clipboard-check', 40, 0, NULL, 1, '2026-01-24 22:09:02'),
(73, 15, 'COM_ESPACES', 'Espaces', 'Espaces', NULL, '#', 'fas fa-chalkboard-teacher', 50, 0, NULL, 1, '2026-01-24 22:09:02'),
(74, 16, 'ADM_DASHBOARD', 'Dashboard', 'Dashboard', NULL, '#', 'fas fa-tachometer-alt', 10, 0, NULL, 1, '2026-01-24 22:09:02'),
(75, 16, 'ADM_PARAMETRAGE', 'Paramétrage', 'Paramétrage', NULL, '#', 'fas fa-cogs', 20, 0, NULL, 1, '2026-01-24 22:09:02'),
(76, 16, 'ADM_SECURITE', 'Sécurité', 'Sécurité', NULL, '#', 'fas fa-shield-alt', 30, 0, NULL, 1, '2026-01-24 22:09:02'),
(77, 16, 'ADM_SYSTEME', 'Système', 'Système', NULL, '#', 'fas fa-server', 40, 0, NULL, 1, '2026-01-24 22:09:02'),
(78, 16, 'ADM_REFERENTIEL', 'Référentiel', 'Référentiel', NULL, '#', 'fas fa-id-card', 50, 0, NULL, 1, '2026-01-24 22:09:02');

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--

DROP TABLE IF EXISTS `grade`;
CREATE TABLE IF NOT EXISTS `grade` (
  `id_grade` int NOT NULL AUTO_INCREMENT,
  `lib_grade` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_grade`),
  UNIQUE KEY `lib_grade` (`lib_grade`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `grade`
--

INSERT INTO `grade` (`id_grade`, `lib_grade`) VALUES
(7, 'A1'),
(6, 'A2'),
(14, 'A3'),
(10, 'B1'),
(12, 'B2'),
(15, 'D1'),
(16, 'E2'),
(13, 'F4');

-- --------------------------------------------------------

--
-- Table structure for table `groupe_utilisateur`
--

DROP TABLE IF EXISTS `groupe_utilisateur`;
CREATE TABLE IF NOT EXISTS `groupe_utilisateur` (
  `id_GU` int NOT NULL AUTO_INCREMENT,
  `lib_GU` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_type_utilisateur` int DEFAULT NULL,
  PRIMARY KEY (`id_GU`),
  KEY `idx_groupe_utilisateur_type` (`id_type_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `groupe_utilisateur`
--

INSERT INTO `groupe_utilisateur` (`id_GU`, `lib_GU`, `id_type_utilisateur`) VALUES
(5, 'Administrateur', 4),
(6, 'Secretaire', 4),
(7, 'Chargée de communication', 4),
(8, 'Responsable scolarité', 4),
(9, 'Responsable Filière', 5),
(10, 'Responsable niveau', 5),
(11, 'commission de validation', 5),
(12, 'Enseignant sans responsabilité administrative', 6),
(13, 'Etudiant', 7);

-- --------------------------------------------------------

--
-- Table structure for table `informations_stage`
--

DROP TABLE IF EXISTS `informations_stage`;
CREATE TABLE IF NOT EXISTS `informations_stage` (
  `id_info_stage` int NOT NULL AUTO_INCREMENT,
  `num_etu` int NOT NULL,
  `id_entreprise` int NOT NULL,
  `date_debut_stage` date NOT NULL,
  `date_fin_stage` date NOT NULL,
  `sujet_stage` text NOT NULL,
  `description_stage` text NOT NULL,
  `encadrant_entreprise` varchar(100) NOT NULL,
  `email_encadrant` varchar(100) NOT NULL,
  `telephone_encadrant` varchar(20) NOT NULL,
  PRIMARY KEY (`id_info_stage`),
  KEY `num_etu` (`num_etu`),
  KEY `id_entreprise` (`id_entreprise`)
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `informations_stage`
--

INSERT INTO `informations_stage` (`id_info_stage`, `num_etu`, `id_entreprise`, `date_debut_stage`, `date_fin_stage`, `sujet_stage`, `description_stage`, `encadrant_entreprise`, `email_encadrant`, `telephone_encadrant`) VALUES
(10, 20220001, 11, '2025-05-01', '2025-09-01', 'Audit et contrôle de securité informatique', 'J\'ai fais de l\'audit et contrôle au niveau de la cybersécurité des entreprise ayant solicité deloitte ', 'Mme. Suzanne Didia', 'sdidagoat0.0@gmail.com', '0303030303'),
(11, 20100001, 10, '2010-01-01', '2010-06-30', 'Développement d\'une application web de gestion des étudiants', 'Stage importé depuis archives', 'TRAORE Ibrahim', '', ''),
(19, 20099001, 19, '2009-01-01', '2009-06-30', 'Outil de gestion des tickets grattage', 'Stage importé depuis archives', 'N/A', '', ''),
(20, 20099002, 20, '2009-01-01', '2009-06-30', 'Optimisation d\'un CRM : cas Trackers', 'Stage importé depuis archives', 'N/A', '', ''),
(21, 20099003, 21, '2009-01-01', '2009-06-30', 'Interfaçage BD site web surveillance bancaire', 'Stage importé depuis archives', 'N/A', '', ''),
(22, 20099004, 20, '2009-01-01', '2009-06-30', 'Logiciel de gestion de devis', 'Stage importé depuis archives', 'N/A', '', ''),
(23, 20099005, 22, '2009-01-01', '2009-06-30', 'Logiciel de help desk', 'Stage importé depuis archives', 'N/A', '', ''),
(24, 20099006, 23, '2009-01-01', '2009-06-30', 'Migration SICA V2 vers V3', 'Stage importé depuis archives', 'N/A', '', ''),
(25, 20099007, 24, '2009-01-01', '2009-06-30', 'Informatisation d\'une SSII', 'Stage importé depuis archives', 'N/A', '', ''),
(26, 20099008, 20, '2009-01-01', '2009-06-30', 'App. gestion données état civil', 'Stage importé depuis archives', 'N/A', '', ''),
(27, 20099009, 20, '2009-01-01', '2009-06-30', 'Logiciel unique gestion', 'Stage importé depuis archives', 'N/A', '', ''),
(28, 20099010, 25, '2009-01-01', '2009-06-30', 'SI Décisionnel commercial', 'Stage importé depuis archives', 'N/A', '', ''),
(29, 20099011, 20, '2009-01-01', '2009-06-30', 'Entrepôt de données support décision', 'Stage importé depuis archives', 'N/A', '', ''),
(30, 20109001, 26, '2010-01-01', '2010-06-30', 'Sécurisation recettes douanières paiement élec.', 'Stage importé depuis archives', 'N/A', '', ''),
(31, 20109002, 27, '2010-01-01', '2010-06-30', 'Implémentation SI ISO 20000', 'Stage importé depuis archives', 'N/A', '', ''),
(32, 20109003, 28, '2010-01-01', '2010-06-30', 'SI Décisionnel direction ventes', 'Stage importé depuis archives', 'N/A', '', ''),
(33, 20109004, 29, '2010-01-01', '2010-06-30', 'App gestion état civil', 'Stage importé depuis archives', 'N/A', '', ''),
(34, 20109005, 30, '2010-01-01', '2010-06-30', 'App informatique décisionnelle SI Anaré', 'Stage importé depuis archives', 'N/A', '', ''),
(35, 20109006, 20, '2010-01-01', '2010-06-30', 'App web gestion RDV visa biométrique', 'Stage importé depuis archives', 'N/A', '', ''),
(36, 20109007, 20, '2010-01-01', '2010-06-30', 'App gestion équipements', 'Stage importé depuis archives', 'N/A', '', ''),
(37, 20109008, 31, '2010-01-01', '2010-06-30', 'App web gestion stocks', 'Stage importé depuis archives', 'N/A', '', ''),
(38, 20109009, 20, '2010-01-01', '2010-06-30', 'Système recueil infos filière coton', 'Stage importé depuis archives', 'N/A', '', ''),
(39, 20109010, 20, '2010-01-01', '2010-06-30', 'Système alerte qualité café cacao', 'Stage importé depuis archives', 'N/A', '', ''),
(40, 20109011, 32, '2010-01-01', '2010-06-30', 'SI données sécuritaires gendarmerie', 'Stage importé depuis archives', 'N/A', '', ''),
(41, 20109012, 33, '2010-01-01', '2010-06-30', 'Gestion finance publique ambassades', 'Stage importé depuis archives', 'N/A', '', ''),
(42, 20109013, 34, '2010-01-01', '2010-06-30', 'Optimisation production état synthèse', 'Stage importé depuis archives', 'N/A', '', ''),
(43, 20109014, 20, '2010-01-01', '2010-06-30', 'Non présenté (Gestion progiciel)', 'Stage importé depuis archives', 'N/A', '', ''),
(44, 20109015, 20, '2010-01-01', '2010-06-30', 'Gestionnaire contenu commerce en ligne', 'Stage importé depuis archives', 'N/A', '', ''),
(45, 20109016, 35, '2010-01-01', '2010-06-30', 'Module gestion lettres chèques Mercure', 'Stage importé depuis archives', 'N/A', '', ''),
(46, 20109017, 36, '2010-01-01', '2010-06-30', 'BI pour mobile banking', 'Stage importé depuis archives', 'N/A', '', ''),
(47, 20109018, 37, '2010-01-01', '2010-06-30', 'Gestion relevés électroniques', 'Stage importé depuis archives', 'N/A', '', ''),
(48, 20119001, 20, '2011-01-01', '2011-06-30', 'Messagerie unifiée RDV', 'Stage importé depuis archives', 'N/A', '', ''),
(49, 20119002, 38, '2011-01-01', '2011-06-30', 'SI aide décision assurance', 'Stage importé depuis archives', 'N/A', '', ''),
(50, 20119003, 20, '2011-01-01', '2011-06-30', 'Progiciel GRH et paie', 'Stage importé depuis archives', 'N/A', '', ''),
(51, 20119004, 20, '2011-01-01', '2011-06-30', 'VPN et serveur BD accès distant', 'Stage importé depuis archives', 'N/A', '', ''),
(52, 20119005, 37, '2011-01-01', '2011-06-30', 'App gestion relevé électronique', 'Stage importé depuis archives', 'N/A', '', ''),
(53, 20119006, 20, '2011-01-01', '2011-06-30', 'Gestion filière coton', 'Stage importé depuis archives', 'N/A', '', ''),
(54, 20119007, 39, '2011-01-01', '2011-06-30', 'App gestion commandes', 'Stage importé depuis archives', 'N/A', '', ''),
(55, 20119008, 20, '2011-01-01', '2011-06-30', 'App gestion école (SaaS)', 'Stage importé depuis archives', 'N/A', '', ''),
(56, 20119009, 20, '2011-01-01', '2011-06-30', 'Thème changé', 'Stage importé depuis archives', 'N/A', '', ''),
(57, 20119010, 40, '2011-01-01', '2011-06-30', 'Informatisation direction commerciale', 'Stage importé depuis archives', 'N/A', '', ''),
(58, 20119011, 41, '2011-01-01', '2011-06-30', 'Gestion incohérences BD', 'Stage importé depuis archives', 'N/A', '', ''),
(59, 20119012, 42, '2011-01-01', '2011-06-30', 'App suivi enseignements', 'Stage importé depuis archives', 'N/A', '', ''),
(60, 20119013, 20, '2011-01-01', '2011-06-30', 'Notification SMS passeport', 'Stage importé depuis archives', 'N/A', '', ''),
(61, 20119014, 20, '2011-01-01', '2011-06-30', 'App contrôle workflow chèques', 'Stage importé depuis archives', 'N/A', '', ''),
(62, 20119015, 43, '2011-01-01', '2011-06-30', 'BD gestion énergies', 'Stage importé depuis archives', 'N/A', '', ''),
(63, 20119016, 20, '2011-01-01', '2011-06-30', 'App gestion panneau publicitaire', 'Stage importé depuis archives', 'N/A', '', ''),
(64, 20119017, 20, '2011-01-01', '2011-06-30', 'Optimisation module GRH Navision', 'Stage importé depuis archives', 'N/A', '', ''),
(65, 20119018, 20, '2011-01-01', '2011-06-30', 'Plateforme décisionnelle analyse clientèle', 'Stage importé depuis archives', 'N/A', '', ''),
(66, 20119019, 44, '2011-01-01', '2011-06-30', 'Audit SI', 'Stage importé depuis archives', 'N/A', '', ''),
(67, 20119020, 41, '2011-01-01', '2011-06-30', 'Archi technique SI décisionnel', 'Stage importé depuis archives', 'N/A', '', ''),
(68, 20119021, 45, '2011-01-01', '2011-06-30', 'Plateforme Cloud collaboratif', 'Stage importé depuis archives', 'N/A', '', ''),
(69, 20119022, 20, '2011-01-01', '2011-06-30', 'Plateforme échange ens/etud/parents', 'Stage importé depuis archives', 'N/A', '', ''),
(70, 20119023, 46, '2011-01-01', '2011-06-30', 'App suivi opérations bancaires', 'Stage importé depuis archives', 'N/A', '', ''),
(71, 20119024, 47, '2011-01-01', '2011-06-30', 'Logiciel suivi coopératives', 'Stage importé depuis archives', 'N/A', '', ''),
(72, 20119025, 48, '2011-01-01', '2011-06-30', 'Logiciel gestion maintenance', 'Stage importé depuis archives', 'N/A', '', ''),
(73, 20119026, 49, '2011-01-01', '2011-06-30', 'SI automatisé USSD gasoil', 'Stage importé depuis archives', 'N/A', '', ''),
(74, 20119027, 20, '2011-01-01', '2011-06-30', 'App gestion produits', 'Stage importé depuis archives', 'N/A', '', ''),
(75, 20119028, 50, '2011-01-01', '2011-06-30', 'App gestion incidents locaux', 'Stage importé depuis archives', 'N/A', '', ''),
(76, 20119029, 51, '2011-01-01', '2011-06-30', 'Module GRH', 'Stage importé depuis archives', 'N/A', '', ''),
(77, 20119030, 47, '2011-01-01', '2011-06-30', 'Logiciel gestion projets agricoles', 'Stage importé depuis archives', 'N/A', '', ''),
(78, 20119031, 20, '2011-01-01', '2011-06-30', 'Système monitoring événements', 'Stage importé depuis archives', 'N/A', '', ''),
(79, 20119032, 52, '2011-01-01', '2011-06-30', 'Archi réseau sécurisée', 'Stage importé depuis archives', 'N/A', '', ''),
(80, 20119033, 20, '2011-01-01', '2011-06-30', 'SI gestion activités forestières', 'Stage importé depuis archives', 'N/A', '', ''),
(81, 20129001, 20, '2012-01-01', '2012-06-30', 'Processus SMQ', 'Stage importé depuis archives', 'N/A', '', ''),
(82, 20129002, 20, '2012-01-01', '2012-06-30', 'Module conseil discipline SIGFAE', 'Stage importé depuis archives', 'N/A', '', ''),
(83, 20129003, 53, '2012-01-01', '2012-06-30', '(2e passage) App web SMQ', 'Stage importé depuis archives', 'N/A', '', ''),
(84, 20129004, 54, '2012-01-01', '2012-06-30', '(2e passage) Module SIGFAE', 'Stage importé depuis archives', 'N/A', '', ''),
(85, 20129005, 20, '2012-01-01', '2012-06-30', 'Messagerie sécurisée', 'Stage importé depuis archives', 'N/A', '', ''),
(86, 20129006, 55, '2012-01-01', '2012-06-30', 'Suivi évaluation à distance', 'Stage importé depuis archives', 'N/A', '', ''),
(87, 20129007, 20, '2012-01-01', '2012-06-30', 'Plateforme GED', 'Stage importé depuis archives', 'N/A', '', ''),
(88, 20129008, 56, '2012-01-01', '2012-06-30', 'Automatisation incidents', 'Stage importé depuis archives', 'N/A', '', ''),
(89, 20129009, 20, '2012-01-01', '2012-06-30', 'Optimisation réseaux', 'Stage importé depuis archives', 'N/A', '', ''),
(90, 20129010, 57, '2012-01-01', '2012-06-30', '(2e pass) Messagerie sécurisé', 'Stage importé depuis archives', 'N/A', '', ''),
(91, 20129011, 20, '2012-01-01', '2012-06-30', '(2e pass) Service annonce', 'Stage importé depuis archives', 'N/A', '', ''),
(92, 20129012, 20, '2012-01-01', '2012-06-30', 'Stratégie sécurité SI', 'Stage importé depuis archives', 'N/A', '', ''),
(93, 20129013, 20, '2012-01-01', '2012-06-30', '(Non indiqué) Module SIGFIP', 'Stage importé depuis archives', 'N/A', '', ''),
(94, 20129014, 20, '2012-01-01', '2012-06-30', 'App web parcs camions', 'Stage importé depuis archives', 'N/A', '', ''),
(95, 20129015, 58, '2012-01-01', '2012-06-30', 'App gestion stocks', 'Stage importé depuis archives', 'N/A', '', ''),
(96, 20129016, 59, '2012-01-01', '2012-06-30', 'Plateforme web OEV', 'Stage importé depuis archives', 'N/A', '', ''),
(97, 20129017, 60, '2012-01-01', '2012-06-30', 'App suivi compteurs HTA/BT', 'Stage importé depuis archives', 'N/A', '', ''),
(98, 20129018, 20, '2012-01-01', '2012-06-30', 'Modules LIGES', 'Stage importé depuis archives', 'N/A', '', ''),
(99, 20129019, 61, '2012-01-01', '2012-06-30', 'SI décisionnel bordereaux', 'Stage importé depuis archives', 'N/A', '', ''),
(100, 20129020, 62, '2012-01-01', '2012-06-30', 'Automatisation budget', 'Stage importé depuis archives', 'N/A', '', ''),
(101, 20139001, 63, '2013-01-01', '2013-06-30', 'Contrôle budgétaire automatisé', 'Stage importé depuis archives', 'N/A', '', ''),
(102, 20139002, 64, '2013-01-01', '2013-06-30', 'App calcul provisions', 'Stage importé depuis archives', 'N/A', '', ''),
(103, 20139003, 20, '2013-01-01', '2013-06-30', 'Centre d\'appel décentralisé', 'Stage importé depuis archives', 'N/A', '', ''),
(104, 20139004, 20, '2013-01-01', '2013-06-30', 'Sonde Nagios', 'Stage importé depuis archives', 'N/A', '', ''),
(105, 20139005, 20, '2013-01-01', '2013-06-30', 'Contrat versus compassion', 'Stage importé depuis archives', 'N/A', '', ''),
(106, 20139006, 20, '2013-01-01', '2013-06-30', 'Optimisation réseau local', 'Stage importé depuis archives', 'N/A', '', ''),
(107, 20139007, 65, '2013-01-01', '2013-06-30', 'Progiciel fonds solidarité', 'Stage importé depuis archives', 'N/A', '', ''),
(108, 20139008, 66, '2013-01-01', '2013-06-30', 'Progiciel gestion patrimoine', 'Stage importé depuis archives', 'N/A', '', ''),
(109, 20139009, 67, '2013-01-01', '2013-06-30', 'App gestion personnel', 'Stage importé depuis archives', 'N/A', '', ''),
(110, 20139010, 20, '2013-01-01', '2013-06-30', 'Objets 3D KinectPedia', 'Stage importé depuis archives', 'N/A', '', ''),
(111, 20139011, 20, '2013-01-01', '2013-06-30', 'Capteur Kinect Objets 3D', 'Stage importé depuis archives', 'N/A', '', ''),
(112, 20139012, 20, '2013-01-01', '2013-06-30', 'App matériel informatique', 'Stage importé depuis archives', 'N/A', '', ''),
(113, 20139013, 20, '2013-01-01', '2013-06-30', 'Automate communication bancaire', 'Stage importé depuis archives', 'N/A', '', ''),
(114, 20139014, 68, '2013-01-01', '2013-06-30', 'Optimisation parc informatique', 'Stage importé depuis archives', 'N/A', '', ''),
(115, 20139015, 20, '2013-01-01', '2013-06-30', 'App gestion appels offres', 'Stage importé depuis archives', 'N/A', '', ''),
(116, 20139016, 69, '2013-01-01', '2013-06-30', 'Datamart assurance-vie', 'Stage importé depuis archives', 'N/A', '', ''),
(117, 20159001, 49, '2015-01-01', '2015-06-30', 'Tableau de bord suivi projets', 'Stage importé depuis archives', 'N/A', '', ''),
(118, 20159002, 70, '2015-01-01', '2015-06-30', 'App assurance vie AS/400 vers Web', 'Stage importé depuis archives', 'N/A', '', ''),
(119, 20159003, 71, '2015-01-01', '2015-06-30', 'App gestion primes', 'Stage importé depuis archives', 'N/A', '', ''),
(120, 20159004, 20, '2015-01-01', '2015-06-30', 'Plateforme réservation taxi', 'Stage importé depuis archives', 'N/A', '', ''),
(121, 20159005, 20, '2015-01-01', '2015-06-30', 'App gestion files d\'attentes', 'Stage importé depuis archives', 'N/A', '', ''),
(122, 20159006, 20, '2015-01-01', '2015-06-30', 'App mobile actes administratifs', 'Stage importé depuis archives', 'N/A', '', ''),
(123, 20159007, 20, '2015-01-01', '2015-06-30', 'App mobile bancaire', 'Stage importé depuis archives', 'N/A', '', ''),
(124, 20159008, 20, '2015-01-01', '2015-06-30', 'Logiciel gestion commerciale', 'Stage importé depuis archives', 'N/A', '', ''),
(125, 20159009, 49, '2015-01-01', '2015-06-30', 'Logiciel gestion projets', 'Stage importé depuis archives', 'N/A', '', ''),
(126, 20159010, 20, '2015-01-01', '2015-06-30', 'Outil workflow SharePoint', 'Stage importé depuis archives', 'N/A', '', ''),
(127, 20159011, 72, '2015-01-01', '2015-06-30', 'SI gestion pesage', 'Stage importé depuis archives', 'N/A', '', ''),
(128, 20159012, 41, '2015-01-01', '2015-06-30', 'App coaching abonnés', 'Stage importé depuis archives', 'N/A', '', ''),
(129, 20169001, 20, '2016-01-01', '2016-06-30', 'App suivi SIMBOX', 'Stage importé depuis archives', 'N/A', '', ''),
(130, 20169002, 20, '2016-01-01', '2016-06-30', 'App gestion titres accès', 'Stage importé depuis archives', 'N/A', '', ''),
(131, 20169003, 73, '2016-01-01', '2016-06-30', 'Solution reporting banking', 'Stage importé depuis archives', 'N/A', '', ''),
(132, 20169004, 20, '2016-01-01', '2016-06-30', 'Service diffusion GSM', 'Stage importé depuis archives', 'N/A', '', ''),
(133, 20169005, 20, '2016-01-01', '2016-06-30', 'App gestion produits auto', 'Stage importé depuis archives', 'N/A', '', ''),
(134, 20169006, 74, '2016-01-01', '2016-06-30', 'App gestion carburants', 'Stage importé depuis archives', 'N/A', '', ''),
(135, 20169007, 70, '2016-01-01', '2016-06-30', 'SI évaluation risques', 'Stage importé depuis archives', 'N/A', '', ''),
(136, 20169008, 20, '2016-01-01', '2016-06-30', 'Solution paiement électrique', 'Stage importé depuis archives', 'N/A', '', ''),
(137, 20179001, 20, '2017-01-01', '2017-06-30', 'Inconnu', 'Stage importé depuis archives', 'N/A', '', ''),
(138, 20179002, 48, '2017-01-01', '2017-06-30', 'Intranet collaboratif', 'Stage importé depuis archives', 'N/A', '', ''),
(139, 20179003, 20, '2017-01-01', '2017-06-30', 'Micro-service microfinance', 'Stage importé depuis archives', 'N/A', '', ''),
(140, 20179004, 20, '2017-01-01', '2017-06-30', 'Billetterie en ligne', 'Stage importé depuis archives', 'N/A', '', ''),
(142, 20179006, 75, '2017-01-01', '2017-06-30', 'Référentiel archi entreprise', 'Stage importé depuis archives', 'N/A', '', ''),
(144, 20179008, 77, '2017-01-01', '2017-06-30', 'Plateforme données agro', 'Stage importé depuis archives', 'N/A', '', ''),
(145, 20179009, 78, '2017-01-01', '2017-06-30', 'App gestion épargne', 'Stage importé depuis archives', 'N/A', '', ''),
(146, 20179010, 79, '2017-01-01', '2017-06-30', 'Datawarehouse', 'Stage importé depuis archives', 'N/A', '', ''),
(147, 20179011, 20, '2017-01-01', '2017-06-30', 'Maintenance réseaux', 'Stage importé depuis archives', 'N/A', '', ''),
(148, 20179012, 20, '2017-01-01', '2017-06-30', 'Logiciel achats généraux', 'Stage importé depuis archives', 'N/A', '', ''),
(149, 20229001, 20, '2022-01-01', '2022-06-30', 'Digitalisation gestion biens état', 'Stage importé depuis archives', 'N/A', '', ''),
(150, 20229002, 80, '2022-01-01', '2022-06-30', 'Traçabilité fèves cacao', 'Stage importé depuis archives', 'N/A', '', ''),
(151, 20229003, 81, '2022-01-01', '2022-06-30', 'Obsolescence SI bancaire', 'Stage importé depuis archives', 'N/A', '', ''),
(152, 20229004, 82, '2022-01-01', '2022-06-30', 'Frontend gestion annuaire', 'Stage importé depuis archives', 'Mme ANHE Esther', '', ''),
(154, 20229006, 41, '2022-01-01', '2022-06-30', 'Gestion recommandation', 'Stage importé depuis archives', 'M. YAO Céleste', '', ''),
(155, 20229007, 60, '2022-01-01', '2022-06-30', 'Plateforme RH', 'Stage importé depuis archives', 'M. YAMB Etienne', '', ''),
(156, 20229008, 60, '2022-01-01', '2022-06-30', 'Plateforme géoréférencement', 'Stage importé depuis archives', 'M. KALA Jules', '', ''),
(157, 20229009, 84, '2022-01-01', '2022-06-30', 'Logiciel centre médical', 'Stage importé depuis archives', 'M. LAUBHOUET Roger', '', ''),
(158, 20229010, 85, '2022-01-01', '2022-06-30', 'Automatisation contrôle conformité', 'Stage importé depuis archives', 'M. ADOU Wilfried', '', ''),
(159, 20229011, 60, '2022-01-01', '2022-06-30', 'RH modules formation congés', 'Stage importé depuis archives', 'M. SANOGO S.', '', ''),
(160, 20229012, 86, '2022-01-01', '2022-06-30', 'App gestion prestations sociales', 'Stage importé depuis archives', 'M. AGUI Ange', '', ''),
(161, 20229013, 69, '2022-01-01', '2022-06-30', 'Outil gestion stock', 'Stage importé depuis archives', 'M. SERI Aristide', '', ''),
(162, 20229014, 87, '2022-01-01', '2022-06-30', 'Automatisation tests SWIFT', 'Stage importé depuis archives', 'M. DEGBEU Aristide', '', ''),
(163, 20229015, 88, '2022-01-01', '2022-06-30', 'App gestion réassurance', 'Stage importé depuis archives', 'M. ASSAH Esdras', '', ''),
(164, 20229016, 89, '2022-01-01', '2022-06-30', 'Digitalisation biens état', 'Stage importé depuis archives', 'M. NIGBAOUA A.', '', ''),
(165, 20229017, 81, '2022-01-01', '2022-06-30', 'Tests qualification module', 'Stage importé depuis archives', 'M. KOUAKOU A.', '', ''),
(166, 20229018, 90, '2022-01-01', '2022-06-30', 'Module mission Dynamics', 'Stage importé depuis archives', 'M. MEKOUNDE O.', '', ''),
(167, 20229019, 91, '2022-01-01', '2022-06-30', 'RH Oracle HCM Cloud', 'Stage importé depuis archives', 'M. OUABI Aurelien', '', ''),
(168, 20229020, 92, '2022-01-01', '2022-06-30', 'Outil gestion incidents', 'Stage importé depuis archives', 'M. AKA Claver', '', ''),
(169, 20229021, 93, '2022-01-01', '2022-06-30', 'App web suivi recommandations', 'Stage importé depuis archives', 'M. ZAMBLE Yves', '', ''),
(170, 20229022, 41, '2022-01-01', '2022-06-30', 'Digitalisation suivi temps', 'Stage importé depuis archives', 'M. BEHOU Séka', '', ''),
(171, 20229023, 94, '2022-01-01', '2022-06-30', 'Gestion facturation navires', 'Stage importé depuis archives', 'M. ALLOUKA Jean', '', ''),
(172, 20230001, 95, '2023-01-01', '2023-06-30', 'Solution RH gestion personnel', 'Stage importé depuis archives', 'M. BEYARA Koutouan', '', ''),
(173, 20230002, 81, '2023-01-01', '2023-06-30', 'Rapprochement états financiers', 'Stage importé depuis archives', 'M. GBELI Abel', '', ''),
(174, 20230003, 96, '2023-01-01', '2023-06-30', 'Planification projet IT', 'Stage importé depuis archives', 'Mme FOFANA Mariam', '', ''),
(175, 20230004, 20, '2023-01-01', '2023-06-30', 'Outil saisie bilan test', 'Stage importé depuis archives', 'N/A', '', ''),
(176, 20230005, 97, '2023-01-01', '2023-06-30', 'Outil suivi dépenses', 'Stage importé depuis archives', 'M. KOFFI Néhémie', '', ''),
(177, 20230006, 58, '2023-01-01', '2023-06-30', 'Gestion trésorerie missions', 'Stage importé depuis archives', 'N/A', '', ''),
(180, 20230009, 100, '2023-01-01', '2023-06-30', 'App web immobilier', 'Stage importé depuis archives', 'M. BOGUE Jonathan', '', ''),
(181, 20230010, 101, '2023-01-01', '2023-06-30', 'Signature électronique PDF', 'Stage importé depuis archives', 'M. KESSE Brice', '', ''),
(182, 20230011, 102, '2023-01-01', '2023-06-30', 'Schéma directeur SI', 'Stage importé depuis archives', 'M. TOURE Mohamed', '', ''),
(183, 20230012, 80, '2023-01-01', '2023-06-30', 'Traçabilité digitale cacao', 'Stage importé depuis archives', 'M. AKA Gervais', '', ''),
(184, 20240001, 103, '2024-01-01', '2024-06-30', 'Datavisualisation', 'Stage importé depuis archives', 'N/A', '', ''),
(185, 20240002, 104, '2024-01-01', '2024-06-30', 'Suivi performance productivité', 'Stage importé depuis archives', 'N/A', '', ''),
(186, 20240003, 35, '2024-01-01', '2024-06-30', 'Etats réglementaires CIMA', 'Stage importé depuis archives', 'N/A', '', ''),
(187, 20240004, 20, '2024-01-01', '2024-06-30', 'Digitalisation paie ODOO', 'Stage importé depuis archives', 'N/A', '', ''),
(188, 20240005, 105, '2024-01-01', '2024-06-30', 'Planification opérations terrains', 'Stage importé depuis archives', 'N/A', '', ''),
(189, 20240006, 106, '2024-01-01', '2024-06-30', 'Paiement mobile money mutuelle', 'Stage importé depuis archives', 'N/A', '', ''),
(190, 20240007, 20, '2024-01-01', '2024-06-30', 'Optimisation déploiement Saphir', 'Stage importé depuis archives', 'N/A', '', ''),
(193, 20240010, 41, '2024-01-01', '2024-06-30', 'Plateforme Iflex fibre optique', 'Stage importé depuis archives', 'M. DJE Bi Cyrille', '', ''),
(194, 20240011, 108, '2024-01-01', '2024-06-30', 'Gestion locative immobilière', 'Stage importé depuis archives', 'Mme ANHE Esther', '', ''),
(195, 20240012, 109, '2024-01-01', '2024-06-30', 'Suivi irrigation parcelles', 'Stage importé depuis archives', 'M. TRABOUE B.', '', ''),
(196, 20240013, 110, '2024-01-01', '2024-06-30', 'Gestion projets BTP', 'Stage importé depuis archives', 'M. KOTEI-NIKOI S.', '', ''),
(198, 20240015, 112, '2024-01-01', '2024-06-30', 'Suivi chèques impayés', 'Stage importé depuis archives', 'M. TAMBIE Alexis', '', ''),
(199, 20240016, 113, '2024-01-01', '2024-06-30', 'Gestion taxes municipales', 'Stage importé depuis archives', 'M. EBE Alex', '', ''),
(200, 20240017, 114, '2024-01-01', '2024-06-30', 'Plateforme santé numérique', 'Stage importé depuis archives', 'M. KEITA Souleymane', '', ''),
(202, 20220002, 116, '2024-06-11', '2025-03-01', 'Le web design', 'Le web design est l\'art de concevoir l\'aspect visuel et fonctionnel d\'un site web ou d\'une application, alliant esthétique, ergonomie et technique pour créer une expérience utilisateur (UX) agréable et intuitive sur tous les appareils, incluant la mise en page, les couleurs, les polices, les images et la structure de navigation.', 'Maitre X', 'maitrex@gmail.com', '0765321489');

-- --------------------------------------------------------

--
-- Table structure for table `inscriptions`
--

DROP TABLE IF EXISTS `inscriptions`;
CREATE TABLE IF NOT EXISTS `inscriptions` (
  `id_inscription` int NOT NULL AUTO_INCREMENT,
  `id_etudiant` int DEFAULT NULL,
  `id_niveau` int DEFAULT NULL,
  `id_annee_acad` int NOT NULL,
  `date_inscription` datetime DEFAULT NULL,
  `statut_inscription` enum('En cours','Validée','Annulée') DEFAULT NULL,
  `nombre_tranche` int NOT NULL,
  `reste_a_payer` decimal(10,2) NOT NULL,
  `montant_paye` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_inscription`),
  KEY `id_etudiant` (`id_etudiant`),
  KEY `id_niveau` (`id_niveau`),
  KEY `id_annee_acad` (`id_annee_acad`)
) ENGINE=InnoDB AUTO_INCREMENT=219 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `inscriptions`
--

INSERT INTO `inscriptions` (`id_inscription`, `id_etudiant`, `id_niveau`, `id_annee_acad`, `date_inscription`, `statut_inscription`, `nombre_tranche`, `reste_a_payer`, `montant_paye`) VALUES
(34, 20220001, 10, 22625, '2025-09-28 21:39:12', 'En cours', 1, 0.00, 980000.00),
(35, 20220002, 10, 22625, '2025-09-29 22:06:37', 'En cours', 1, 0.00, 1025000.00),
(36, 20099001, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(37, 20099002, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(38, 20099003, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(39, 20099004, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(40, 20099005, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(41, 20099006, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(42, 20099007, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(43, 20099008, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(44, 20099009, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(45, 20099010, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(46, 20099011, NULL, 21009, '2009-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(47, 20109001, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(48, 20109002, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(49, 20109003, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(50, 20109004, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(51, 20109005, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(52, 20109006, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(53, 20109007, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(54, 20109008, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(55, 20109009, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(56, 20109010, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(57, 20109011, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(58, 20109012, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(59, 20109013, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(60, 20109014, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(61, 20109015, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(62, 20109016, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(63, 20109017, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(64, 20109018, NULL, 21110, '2010-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(65, 20119001, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(66, 20119002, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(67, 20119003, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(68, 20119004, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(69, 20119005, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(70, 20119006, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(71, 20119007, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(72, 20119008, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(73, 20119009, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(74, 20119010, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(75, 20119011, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(76, 20119012, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(77, 20119013, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(78, 20119014, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(79, 20119015, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(80, 20119016, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(81, 20119017, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(82, 20119018, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(83, 20119019, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(84, 20119020, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(85, 20119021, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(86, 20119022, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(87, 20119023, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(88, 20119024, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(89, 20119025, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(90, 20119026, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(91, 20119027, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(92, 20119028, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(93, 20119029, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(94, 20119030, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(95, 20119031, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(96, 20119032, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(97, 20119033, NULL, 21211, '2011-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(98, 20129001, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(99, 20129002, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(100, 20129003, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(101, 20129004, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(102, 20129005, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(103, 20129006, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(104, 20129007, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(105, 20129008, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(106, 20129009, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(107, 20129010, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(108, 20129011, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(109, 20129012, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(110, 20129013, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(111, 20129014, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(112, 20129015, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(113, 20129016, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(114, 20129017, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(115, 20129018, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(116, 20129019, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(117, 20129020, NULL, 21312, '2012-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(118, 20139001, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(119, 20139002, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(120, 20139003, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(121, 20139004, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(122, 20139005, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(123, 20139006, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(124, 20139007, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(125, 20139008, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(126, 20139009, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(127, 20139010, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(128, 20139011, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(129, 20139012, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(130, 20139013, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(131, 20139014, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(132, 20139015, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(133, 20139016, NULL, 21413, '2013-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(134, 20159001, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(135, 20159002, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(136, 20159003, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(137, 20159004, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(138, 20159005, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(139, 20159006, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(140, 20159007, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(141, 20159008, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(142, 20159009, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(143, 20159010, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(144, 20159011, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(145, 20159012, NULL, 21615, '2015-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(146, 20169001, NULL, 21716, '2016-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(147, 20169002, NULL, 21716, '2016-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(148, 20169003, NULL, 21716, '2016-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(149, 20169004, NULL, 21716, '2016-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(150, 20169005, NULL, 21716, '2016-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(151, 20169006, NULL, 21716, '2016-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(152, 20169007, NULL, 21716, '2016-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(153, 20169008, NULL, 21716, '2016-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(154, 20179001, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(155, 20179002, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(156, 20179003, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(157, 20179004, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(159, 20179006, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(161, 20179008, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(162, 20179009, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(163, 20179010, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(164, 20179011, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(165, 20179012, NULL, 21817, '2017-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(166, 20229001, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(167, 20229002, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(168, 20229003, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(169, 20229004, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(171, 20229006, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(172, 20229007, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(173, 20229008, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(174, 20229009, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(175, 20229010, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(176, 20229011, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(177, 20229012, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(178, 20229013, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(179, 20229014, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(180, 20229015, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(181, 20229016, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(182, 20229017, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(183, 20229018, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(184, 20229019, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(185, 20229020, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(186, 20229021, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(187, 20229022, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(188, 20229023, NULL, 22322, '2022-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(189, 20230001, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(190, 20230002, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(191, 20230003, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(192, 20230004, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(193, 20230005, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(194, 20230006, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(197, 20230009, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(198, 20230010, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(199, 20230011, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(200, 20230012, NULL, 22423, '2023-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(201, 20240001, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(202, 20240002, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(203, 20240003, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(204, 20240004, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(205, 20240005, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(206, 20240006, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(207, 20240007, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(210, 20240010, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(211, 20240011, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(212, 20240012, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(213, 20240013, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(215, 20240015, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(216, 20240016, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00),
(217, 20240017, NULL, 22524, '2024-09-01 00:00:00', 'En cours', 1, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `mentions`
--

DROP TABLE IF EXISTS `mentions`;
CREATE TABLE IF NOT EXISTS `mentions` (
  `id_mention` int NOT NULL AUTO_INCREMENT,
  `lib_mention` varchar(50) NOT NULL,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_mention`),
  UNIQUE KEY `lib_mention` (`lib_mention`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `mentions`
--

INSERT INTO `mentions` (`id_mention`, `lib_mention`, `actif`) VALUES
(1, 'Passable', 1),
(2, 'Assez bien', 1),
(3, 'Bien', 1),
(4, 'Très bien', 1),
(5, 'Excellent', 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id_message` int NOT NULL AUTO_INCREMENT,
  `contenu_message` text NOT NULL,
  `lib_message` varchar(60) NOT NULL,
  `type_message` varchar(60) NOT NULL,
  PRIMARY KEY (`id_message`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id_message`, `contenu_message`, `lib_message`, `type_message`) VALUES
(3, 'Bienvenue sur Soutenance Manager', 'message_bienvenue', 'info'),
(4, 'Erreur lors du traitement du fichier', 'messageErreur', 'error');

-- --------------------------------------------------------

--
-- Table structure for table `niveau_acces_donnees`
--

DROP TABLE IF EXISTS `niveau_acces_donnees`;
CREATE TABLE IF NOT EXISTS `niveau_acces_donnees` (
  `id_niveau_acces_donnees` int NOT NULL AUTO_INCREMENT,
  `lib_niveau_acces_donnees` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_niveau_acces_donnees`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `niveau_acces_donnees`
--

INSERT INTO `niveau_acces_donnees` (`id_niveau_acces_donnees`, `lib_niveau_acces_donnees`) VALUES
(4, 'Lecture seule'),
(5, 'Écriture');

-- --------------------------------------------------------

--
-- Table structure for table `niveau_approbation`
--

DROP TABLE IF EXISTS `niveau_approbation`;
CREATE TABLE IF NOT EXISTS `niveau_approbation` (
  `id_approb` int NOT NULL AUTO_INCREMENT,
  `lib_approb` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_approb`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `niveau_approbation`
--

INSERT INTO `niveau_approbation` (`id_approb`, `lib_approb`) VALUES
(3, 'Niveau 1'),
(4, 'Niveau 2'),
(6, 'Niveau 3');

-- --------------------------------------------------------

--
-- Table structure for table `niveau_etude`
--

DROP TABLE IF EXISTS `niveau_etude`;
CREATE TABLE IF NOT EXISTS `niveau_etude` (
  `id_niv_etude` int NOT NULL AUTO_INCREMENT,
  `lib_niv_etude` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_enseignant` int DEFAULT NULL,
  `montant_scolarite` decimal(10,2) DEFAULT NULL,
  `montant_inscription` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_niv_etude`),
  KEY `id_enseignant` (`id_enseignant`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `niveau_etude`
--

INSERT INTO `niveau_etude` (`id_niv_etude`, `lib_niv_etude`, `id_enseignant`, `montant_scolarite`, `montant_inscription`) VALUES
(10, 'Master 2', 7, 1025000.00, 500000.00),
(17, 'Master 1', 23, 975000.00, 450000.00);

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
CREATE TABLE IF NOT EXISTS `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `num_etu` int NOT NULL,
  `id_ue` int DEFAULT NULL,
  `id_ecue` int DEFAULT NULL,
  `moyenne` decimal(4,2) NOT NULL,
  `commentaire` text,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notes_ibfk_1` (`num_etu`),
  KEY `notes_ibfk_2` (`id_ue`),
  KEY `notes_ibfk_3` (`id_ecue`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `num_etu`, `id_ue`, `id_ecue`, `moyenne`, `commentaire`, `date_creation`, `date_modification`) VALUES
(71, 20220002, 95, NULL, 10.00, '', '2025-10-15 19:31:13', '2025-10-15 19:31:13'),
(72, 20220002, 103, NULL, 12.00, '', '2025-10-15 19:31:13', '2025-10-15 19:31:13'),
(73, 20220002, 98, NULL, 12.00, '', '2025-10-15 19:31:13', '2025-10-15 19:31:13'),
(74, 20220002, 101, NULL, 13.00, '', '2025-10-15 19:31:13', '2025-10-15 19:31:13'),
(75, 20220002, 100, NULL, 15.00, '', '2025-10-15 19:31:13', '2025-10-15 19:31:13'),
(76, 20220002, 102, NULL, 15.00, '', '2025-10-15 19:31:13', '2025-10-15 19:31:13'),
(77, 20220002, 97, NULL, 12.00, '', '2025-10-15 19:31:13', '2025-10-15 19:31:13'),
(78, 20220002, 99, NULL, 20.00, '', '2025-10-15 19:31:13', '2025-10-15 19:31:13'),
(79, 20220002, 96, NULL, 12.00, '', '2025-10-15 19:31:13', '2025-10-15 19:31:13'),
(80, 20220001, 95, NULL, 12.00, '', '2025-10-15 19:32:13', '2025-10-15 19:32:13'),
(81, 20220001, 103, NULL, 12.00, '', '2025-10-15 19:32:13', '2025-10-15 19:32:13'),
(82, 20220001, 98, NULL, 10.00, '', '2025-10-15 19:32:13', '2025-10-15 19:32:13'),
(83, 20220001, 101, NULL, 15.00, '', '2025-10-15 19:32:13', '2025-10-15 19:32:13'),
(84, 20220001, 100, NULL, 16.00, '', '2025-10-15 19:32:13', '2025-10-15 19:32:13'),
(85, 20220001, 102, NULL, 12.00, '', '2025-10-15 19:32:13', '2025-10-15 19:32:13'),
(86, 20220001, 97, NULL, 10.00, '', '2025-10-15 19:32:13', '2025-10-15 19:32:13'),
(87, 20220001, 99, NULL, 13.00, '', '2025-10-15 19:32:13', '2025-10-15 19:32:13'),
(88, 20220001, 96, NULL, 15.00, '', '2025-10-15 19:32:13', '2025-10-15 19:32:13');

-- --------------------------------------------------------

--
-- Table structure for table `occuper`
--

DROP TABLE IF EXISTS `occuper`;
CREATE TABLE IF NOT EXISTS `occuper` (
  `id_fonction` int NOT NULL,
  `id_enseignant` int NOT NULL,
  `date_occupation` date NOT NULL,
  PRIMARY KEY (`id_fonction`,`id_enseignant`),
  KEY `Key_occuper_enseignant` (`id_enseignant`),
  KEY `Key_occuper_fonction` (`id_fonction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `occuper`
--

INSERT INTO `occuper` (`id_fonction`, `id_enseignant`, `date_occupation`) VALUES
(2, 7, '2015-09-09'),
(7, 18, '2000-10-17'),
(9, 19, '1990-09-01'),
(9, 21, '1995-06-05'),
(9, 22, '1989-09-05'),
(9, 23, '1990-09-10');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `email` (`email`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `used`, `created_at`) VALUES
(3, 'soroemeric@gmail.com', '553a20d449bd775c694fc29cfa4b4bc14d7eb4fd79e01b5cbbe86be46b430b15', '2025-09-27 14:51:50', 1, '2025-09-27 13:51:50');

-- --------------------------------------------------------

--
-- Table structure for table `pdf_cr_pv_rapetd`
--

DROP TABLE IF EXISTS `pdf_cr_pv_rapetd`;
CREATE TABLE IF NOT EXISTS `pdf_cr_pv_rapetd` (
  `id_arch` int NOT NULL AUTO_INCREMENT,
  `libelle` varchar(200) NOT NULL,
  PRIMARY KEY (`id_arch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id_permission` int NOT NULL AUTO_INCREMENT,
  `id_GU` int NOT NULL,
  `id_fonctionnalite` int NOT NULL,
  `peut_voir` tinyint(1) DEFAULT '0',
  `peut_creer` tinyint(1) DEFAULT '0',
  `peut_modifier` tinyint(1) DEFAULT '0',
  `peut_supprimer` tinyint(1) DEFAULT '0',
  `date_attribution` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_permission`),
  UNIQUE KEY `unique_permission` (`id_GU`,`id_fonctionnalite`),
  KEY `id_fonctionnalite` (`id_fonctionnalite`)
) ENGINE=InnoDB AUTO_INCREMENT=314 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id_permission`, `id_GU`, `id_fonctionnalite`, `peut_voir`, `peut_creer`, `peut_modifier`, `peut_supprimer`, `date_attribution`) VALUES
(60, 6, 4, 1, 1, 1, 1, '2026-01-14 19:01:02'),
(61, 6, 6, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(62, 6, 8, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(63, 6, 53, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(64, 6, 9, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(65, 6, 11, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(66, 6, 12, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(67, 6, 56, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(68, 6, 14, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(69, 6, 15, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(70, 6, 54, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(71, 6, 16, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(72, 6, 18, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(73, 6, 19, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(74, 6, 20, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(75, 6, 21, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(76, 6, 24, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(77, 6, 59, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(78, 6, 58, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(79, 6, 28, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(80, 7, 4, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(81, 7, 8, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(82, 7, 9, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(83, 7, 12, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(84, 7, 56, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(85, 7, 15, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(86, 7, 54, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(87, 7, 16, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(88, 7, 17, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(89, 7, 57, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(90, 7, 21, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(91, 7, 22, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(92, 7, 23, 1, 1, 1, 1, '2026-01-14 19:01:02'),
(93, 7, 24, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(94, 7, 59, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(95, 8, 3, 1, 1, 1, 1, '2026-01-14 19:01:02'),
(96, 8, 6, 1, 1, 1, 1, '2026-01-14 19:01:02'),
(97, 8, 8, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(98, 8, 53, 1, 1, 1, 1, '2026-01-14 19:01:02'),
(99, 8, 9, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(100, 8, 14, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(101, 8, 54, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(102, 8, 25, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(103, 8, 26, 1, 1, 1, 1, '2026-01-14 19:01:02'),
(104, 8, 58, 1, 1, 1, 1, '2026-01-14 19:01:02'),
(105, 8, 28, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(106, 8, 30, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(107, 8, 32, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(108, 8, 43, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(109, 8, 45, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(110, 9, 2, 1, 1, 1, 1, '2026-01-14 19:01:02'),
(111, 9, 7, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(112, 9, 8, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(113, 9, 9, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(114, 9, 12, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(115, 9, 56, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(116, 9, 15, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(117, 9, 54, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(118, 9, 16, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(119, 9, 17, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(120, 9, 57, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(121, 9, 18, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(122, 9, 19, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(123, 9, 20, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(124, 9, 21, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(125, 9, 22, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(126, 9, 23, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(127, 9, 24, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(128, 9, 59, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(129, 9, 26, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(130, 9, 58, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(131, 10, 2, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(132, 10, 7, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(133, 10, 8, 1, 0, 0, 0, '2026-01-14 19:01:02'),
(134, 10, 9, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(135, 10, 12, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(136, 10, 56, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(137, 10, 54, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(138, 10, 16, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(139, 10, 17, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(140, 10, 57, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(141, 10, 18, 1, 0, 1, 0, '2026-01-14 19:01:02'),
(142, 10, 20, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(143, 10, 26, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(144, 10, 58, 1, 1, 1, 0, '2026-01-14 19:01:02'),
(145, 11, 5, 1, 1, 1, 1, '2026-01-14 19:01:03'),
(146, 11, 8, 1, 0, 0, 0, '2026-01-14 19:01:03'),
(147, 11, 9, 1, 0, 0, 0, '2026-01-14 19:01:03'),
(148, 11, 12, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(149, 11, 56, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(150, 11, 15, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(151, 11, 54, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(152, 11, 16, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(153, 11, 17, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(154, 11, 57, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(155, 11, 18, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(156, 11, 20, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(157, 11, 21, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(158, 11, 22, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(159, 11, 23, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(160, 11, 24, 1, 0, 0, 0, '2026-01-14 19:01:03'),
(161, 11, 59, 1, 0, 0, 0, '2026-01-14 19:01:03'),
(162, 12, 2, 1, 0, 0, 0, '2026-01-14 19:01:03'),
(163, 12, 7, 1, 0, 0, 0, '2026-01-14 19:01:03'),
(164, 12, 9, 1, 0, 0, 0, '2026-01-14 19:01:03'),
(165, 12, 12, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(166, 12, 56, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(167, 12, 17, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(168, 12, 57, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(169, 12, 20, 1, 0, 1, 0, '2026-01-14 19:01:03'),
(170, 12, 26, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(171, 12, 58, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(172, 13, 9, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(173, 13, 10, 1, 1, 0, 0, '2026-01-14 19:01:03'),
(174, 13, 11, 1, 0, 0, 0, '2026-01-14 19:01:03'),
(175, 13, 13, 1, 1, 0, 0, '2026-01-14 19:01:03'),
(176, 13, 25, 1, 0, 0, 0, '2026-01-14 19:01:03'),
(177, 13, 27, 1, 1, 1, 0, '2026-01-14 19:01:03'),
(237, 5, 6, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(238, 5, 8, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(239, 5, 28, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(240, 5, 26, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(241, 5, 54, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(242, 5, 58, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(243, 5, 14, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(244, 5, 53, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(245, 5, 3, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(246, 5, 60, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(247, 5, 61, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(248, 5, 62, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(249, 5, 63, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(250, 5, 64, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(251, 5, 9, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(252, 5, 10, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(253, 5, 11, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(254, 5, 13, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(255, 5, 27, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(256, 5, 25, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(257, 5, 65, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(258, 5, 66, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(259, 5, 67, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(260, 5, 68, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(261, 5, 20, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(262, 5, 18, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(263, 5, 16, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(264, 5, 12, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(265, 5, 5, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(266, 5, 2, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(267, 5, 21, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(268, 5, 23, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(269, 5, 24, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(270, 5, 17, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(271, 5, 15, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(272, 5, 7, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(273, 5, 59, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(274, 5, 19, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(275, 5, 57, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(276, 5, 56, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(277, 5, 69, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(278, 5, 70, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(279, 5, 71, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(280, 5, 72, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(281, 5, 73, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(282, 5, 1, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(283, 5, 29, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(284, 5, 30, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(285, 5, 31, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(286, 5, 32, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(287, 5, 33, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(288, 5, 34, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(289, 5, 35, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(290, 5, 36, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(291, 5, 37, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(292, 5, 38, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(293, 5, 39, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(294, 5, 40, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(295, 5, 41, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(296, 5, 42, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(297, 5, 43, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(298, 5, 44, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(299, 5, 45, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(300, 5, 46, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(301, 5, 47, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(302, 5, 48, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(303, 5, 49, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(304, 5, 50, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(305, 5, 52, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(306, 5, 4, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(307, 5, 51, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(308, 5, 55, 1, 1, 1, 1, '2026-01-24 22:18:30'),
(309, 5, 74, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(310, 5, 75, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(311, 5, 76, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(312, 5, 77, 1, 0, 0, 0, '2026-01-24 22:18:30'),
(313, 5, 78, 1, 0, 0, 0, '2026-01-24 22:18:30');

-- --------------------------------------------------------

--
-- Table structure for table `personnel_admin`
--

DROP TABLE IF EXISTS `personnel_admin`;
CREATE TABLE IF NOT EXISTS `personnel_admin` (
  `id_pers_admin` int NOT NULL AUTO_INCREMENT,
  `nom_pers_admin` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `prenom_pers_admin` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `email_pers_admin` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `tel_pers_admin` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `poste` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `date_embauche` date NOT NULL,
  PRIMARY KEY (`id_pers_admin`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `personnel_admin`
--

INSERT INTO `personnel_admin` (`id_pers_admin`, `nom_pers_admin`, `prenom_pers_admin`, `email_pers_admin`, `tel_pers_admin`, `poste`, `date_embauche`) VALUES
(9, 'KAMENAN', 'DURAND', 'kamenandurand@gmail.com', '0707070707', 'Secretaire générale', '1992-09-10'),
(10, 'Seri', 'Christiane', 'serichristiane@gmail.com', '0505050505', 'Chargé de communication', '1999-09-01');

-- --------------------------------------------------------

--
-- Table structure for table `pister`
--

DROP TABLE IF EXISTS `pister`;
CREATE TABLE IF NOT EXISTS `pister` (
  `id_piste` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `action` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Type d''action (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc.)',
  `statut_action` enum('Erreur','Succès') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `nom_table` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Nom de la table concernée',
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_piste`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_action` (`action`),
  KEY `idx_table` (`nom_table`),
  KEY `idx_created_at` (`date_creation`),
  KEY `idx_utilisateur_action` (`id_utilisateur`,`action`),
  KEY `id_action` (`action`),
  KEY `id_action_2` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=621 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `pister`
--

INSERT INTO `pister` (`id_piste`, `id_utilisateur`, `action`, `statut_action`, `nom_table`, `date_creation`) VALUES
(24, 5, 'Connexion', 'Succès', 'utilisateur', '2025-07-03 01:15:18'),
(25, 5, 'Connexion', 'Succès', 'utilisateur', '2025-07-03 08:21:21'),
(26, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-07-03 08:27:38'),
(27, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-07-03 08:41:20'),
(68, 5, 'Connexion', 'Succès', 'utilisateur', '2025-07-03 11:00:06'),
(90, 5, 'Création', 'Succès', 'utilisateur', '2025-07-03 11:55:29'),
(103, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-07-03 12:08:44'),
(106, 5, 'Création', 'Succès', 'utilisateur', '2025-07-03 12:12:42'),
(120, 5, 'Connexion', 'Succès', 'utilisateur', '2025-07-15 22:52:59'),
(121, 5, 'Connexion', 'Succès', 'utilisateur', '2025-07-16 17:48:49'),
(124, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-07-16 18:07:19'),
(125, 5, 'Connexion', 'Succès', 'utilisateur', '2025-07-16 18:07:42'),
(126, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-07-16 18:08:01'),
(129, 5, 'Création', 'Succès', 'utilisateur', '2025-07-16 18:10:56'),
(146, 5, 'Connexion', 'Succès', 'utilisateur', '2025-09-27 00:04:39'),
(150, 5, 'Connexion', 'Succès', 'utilisateur', '2025-09-27 12:42:55'),
(151, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-27 12:43:11'),
(152, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-27 12:43:20'),
(153, 5, 'Modification', 'Succès', 'utilisateur', '2025-09-27 12:47:11'),
(154, 5, 'Modification', 'Succès', 'utilisateur', '2025-09-27 12:48:50'),
(155, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-09-27 12:49:22'),
(158, 5, 'Connexion', 'Succès', 'utilisateur', '2025-09-27 13:53:05'),
(159, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-09-27 15:45:47'),
(160, 5, 'Connexion', 'Succès', 'utilisateur', '2025-09-27 15:46:05'),
(161, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-27 15:46:05'),
(162, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-27 15:46:14'),
(163, 5, 'Modification', 'Succès', 'enseignant', '2025-09-27 16:37:17'),
(164, 5, 'Création', 'Succès', 'enseignant', '2025-09-27 16:46:23'),
(165, 5, 'Modification', 'Succès', 'utilisateur', '2025-09-27 16:48:33'),
(166, 5, 'Connexion', 'Succès', 'utilisateur', '2025-09-27 23:16:00'),
(167, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-27 23:16:00'),
(168, 5, 'Modification', 'Succès', 'utilisateur', '2025-09-27 23:16:14'),
(169, 5, 'Modification', 'Succès', 'utilisateur', '2025-09-27 23:16:27'),
(170, 5, 'Création', 'Succès', 'utilisateur', '2025-09-27 23:33:27'),
(171, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-09-27 23:57:56'),
(172, 5, 'Connexion', 'Succès', 'utilisateur', '2025-09-28 00:04:13'),
(173, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-28 00:04:14'),
(174, 5, 'Création', 'Succès', 'utilisateur', '2025-09-28 00:05:05'),
(175, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-09-28 00:14:17'),
(176, 5, 'Connexion', 'Succès', 'utilisateur', '2025-09-28 20:58:17'),
(177, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-28 20:58:18'),
(178, 5, 'Création', 'Succès', 'pers_admin', '2025-09-28 21:01:33'),
(179, 5, 'Création', 'Succès', 'utilisateur', '2025-09-28 21:02:09'),
(180, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-09-28 21:36:03'),
(181, 99, 'Connexion', 'Succès', 'utilisateur', '2025-09-28 21:36:18'),
(182, 99, 'Création', 'Succès', 'etudiants', '2025-09-28 21:37:39'),
(183, 5, 'Connexion', 'Succès', 'utilisateur', '2025-09-28 21:38:15'),
(184, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-28 21:38:15'),
(185, 5, 'Création', 'Succès', 'annee_academique', '2025-09-28 21:38:47'),
(186, 99, 'Création', 'Succès', 'inscriptions', '2025-09-28 21:39:12'),
(187, 99, 'Impression', 'Succès', 'inscriptions', '2025-09-28 21:39:20'),
(188, 99, 'Impression', 'Succès', 'inscriptions', '2025-09-28 22:19:33'),
(189, 99, 'Création', 'Succès', 'versements', '2025-09-28 22:59:56'),
(190, 99, 'Impression', 'Succès', 'inscriptions', '2025-09-28 23:39:18'),
(191, 99, 'Impression', 'Succès', 'inscriptions', '2025-09-28 23:42:25'),
(192, 99, 'Connexion', 'Succès', 'utilisateur', '2025-09-29 13:45:09'),
(193, 99, 'Création', 'Succès', 'versements', '2025-09-29 14:12:15'),
(194, 99, 'Connexion', 'Succès', 'utilisateur', '2025-09-29 15:03:40'),
(195, 99, 'Création', 'Succès', 'versements', '2025-09-29 19:04:01'),
(196, 5, 'Connexion', 'Succès', 'utilisateur', '2025-09-29 19:05:34'),
(197, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-29 19:05:34'),
(198, 99, 'Création', 'Succès', 'notes', '2025-09-29 19:14:16'),
(199, 99, 'Impression', 'Succès', 'inscriptions', '2025-09-29 20:43:10'),
(200, 99, 'Impression', 'Succès', 'inscriptions', '2025-09-29 20:45:35'),
(201, 99, 'Création', 'Succès', 'etudiants', '2025-09-29 22:06:12'),
(202, 99, 'Création', 'Succès', 'inscriptions', '2025-09-29 22:06:37'),
(203, 5, 'Création', 'Succès', 'utilisateur', '2025-09-29 22:14:17'),
(204, 5, 'Création', 'Succès', 'utilisateur', '2025-09-29 22:14:34'),
(205, 5, 'Création', 'Succès', 'pers_admin', '2025-09-29 22:16:04'),
(206, 5, 'Création', 'Succès', 'utilisateur', '2025-09-29 22:16:29'),
(207, 102, 'Connexion', 'Succès', 'utilisateur', '2025-09-29 22:17:55'),
(208, 101, 'Connexion', 'Succès', 'utilisateur', '2025-09-29 22:18:54'),
(209, 101, 'Création', 'Succès', 'candidature_soutenance', '2025-09-29 22:21:28'),
(210, 101, 'Création', 'Succès', 'candidature_soutenance', '2025-09-29 22:21:31'),
(211, 99, 'Validation', 'Succès', 'candidature_soutenance', '2025-09-29 22:21:54'),
(212, 99, 'Validation', 'Succès', 'candidature_soutenance', '2025-09-29 22:22:00'),
(213, 99, 'Validation', 'Succès', 'candidature_soutenance', '2025-09-29 22:22:08'),
(214, 99, 'Envoi résultats', 'Succès', 'candidature_soutenance', '2025-09-29 22:22:18'),
(215, 101, 'Création', 'Succès', 'rapport_etudiants', '2025-09-29 22:26:28'),
(216, 101, 'Dépôt', 'Succès', 'rapport', '2025-09-29 22:26:45'),
(217, 5, 'Création', 'Succès', 'enseignant', '2025-09-29 22:53:14'),
(218, 5, 'Création', 'Succès', 'enseignant', '2025-09-29 22:54:15'),
(219, 5, 'Création', 'Succès', 'utilisateur', '2025-09-29 22:55:00'),
(220, 5, 'Création', 'Succès', 'enseignant', '2025-09-29 23:06:36'),
(221, 5, 'Création', 'Succès', 'enseignant', '2025-09-29 23:08:28'),
(222, 5, 'Création', 'Succès', 'utilisateur', '2025-09-29 23:09:04'),
(223, 5, 'Création', 'Succès', 'utilisateur', '2025-09-29 23:09:08'),
(224, 98, 'Connexion', 'Succès', 'utilisateur', '2025-09-29 23:11:29'),
(225, 103, 'Connexion', 'Succès', 'utilisateur', '2025-09-29 23:12:54'),
(226, 5, 'Création', 'Succès', 'enseignant', '2025-09-29 23:17:29'),
(227, 5, 'Création', 'Succès', 'utilisateur', '2025-09-29 23:17:47'),
(228, 108, 'Connexion', 'Succès', 'utilisateur', '2025-09-29 23:19:21'),
(229, 101, 'Connexion', 'Succès', 'utilisateur', '2025-09-29 23:20:13'),
(230, 5, 'Création', 'Succès', 'traitement', '2025-09-29 23:32:49'),
(231, 5, 'Modification', 'Succès', 'attribution', '2025-09-29 23:33:37'),
(232, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-01 19:46:15'),
(233, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-01 19:46:16'),
(234, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-01 23:05:46'),
(235, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-01 23:05:47'),
(236, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-10-02 12:29:36'),
(237, 99, 'Connexion', 'Succès', 'utilisateur', '2025-10-02 12:29:40'),
(238, 99, 'Déconnexion', 'Succès', 'utilisateur', '2025-10-02 13:23:22'),
(239, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-02 13:23:39'),
(240, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-02 13:23:39'),
(241, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-06 22:54:25'),
(242, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-06 22:54:25'),
(243, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-06 22:55:12'),
(244, 5, 'Modification', 'Succès', 'traitement', '2025-10-06 22:56:09'),
(245, 5, 'Création', 'Succès', 'traitement', '2025-10-06 23:45:39'),
(246, 5, 'Modification', 'Succès', 'attribution', '2025-10-06 23:46:03'),
(247, 5, 'Modification', 'Succès', 'traitement', '2025-10-06 23:51:37'),
(248, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-07 10:38:19'),
(249, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-07 10:38:19'),
(250, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-10-07 10:41:25'),
(251, 99, 'Connexion', 'Succès', 'utilisateur', '2025-10-07 10:41:32'),
(252, 99, 'Déconnexion', 'Succès', 'utilisateur', '2025-10-07 10:44:09'),
(253, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-07 10:44:23'),
(254, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-07 10:44:23'),
(255, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-10-07 12:58:08'),
(256, 99, 'Connexion', 'Succès', 'utilisateur', '2025-10-07 12:58:10'),
(257, 99, 'Déconnexion', 'Succès', 'utilisateur', '2025-10-07 13:08:18'),
(258, 99, 'Connexion', 'Succès', 'utilisateur', '2025-10-07 13:08:19'),
(259, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-07 13:08:35'),
(260, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-07 13:08:35'),
(261, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-11 18:40:28'),
(262, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-11 18:40:28'),
(263, 5, 'Création', 'Succès', 'traitement', '2025-10-11 19:23:26'),
(264, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-11 19:23:32'),
(265, 5, 'Modification', 'Succès', 'attribution', '2025-10-11 19:24:02'),
(266, 5, 'Modification', 'Succès', 'enseignant', '2025-10-11 23:43:56'),
(267, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-12 00:43:15'),
(269, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-12 12:43:58'),
(270, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-12 12:43:58'),
(271, 99, 'Connexion', 'Succès', 'utilisateur', '2025-10-15 19:01:38'),
(272, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-15 19:03:39'),
(273, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-15 19:03:39'),
(274, 5, 'Suppression', 'Succès', 'ue', '2025-10-15 19:04:06'),
(275, 5, 'Suppression', 'Succès', 'ue', '2025-10-15 19:04:14'),
(276, 5, 'Création', 'Succès', 'niveau_etude', '2025-10-15 19:09:45'),
(277, 5, 'Création', 'Succès', 'semestre', '2025-10-15 19:11:05'),
(278, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:11:37'),
(279, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:12:58'),
(280, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:13:29'),
(281, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:13:58'),
(282, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:14:18'),
(283, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:14:53'),
(284, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:15:20'),
(285, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:15:44'),
(286, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:16:10'),
(287, 99, 'Création', 'Succès', 'versements', '2025-10-15 19:18:43'),
(288, 5, 'Suppression', 'Succès', 'niveau_etude', '2025-10-15 19:19:58'),
(289, 5, 'Modification', 'Succès', 'niveau_etude', '2025-10-15 19:20:40'),
(290, 5, 'Suppression', 'Succès', 'semestre', '2025-10-15 19:22:32'),
(291, 5, 'Modification', 'Succès', 'semestre', '2025-10-15 19:22:41'),
(292, 99, 'Création', 'Succès', 'versements', '2025-10-15 19:24:34'),
(293, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:25:54'),
(294, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:26:12'),
(295, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:26:27'),
(296, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:26:42'),
(297, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:26:59'),
(298, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:29:27'),
(299, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:29:44'),
(300, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:30:05'),
(301, 5, 'Création', 'Succès', 'ue', '2025-10-15 19:30:21'),
(302, 99, 'Création', 'Succès', 'notes', '2025-10-15 19:31:13'),
(303, 99, 'Création', 'Succès', 'notes', '2025-10-15 19:32:13'),
(304, 5, 'Création', 'Succès', 'traitement', '2025-10-15 19:34:13'),
(305, 5, 'Modification', 'Succès', 'attribution', '2025-10-15 19:34:22'),
(306, 5, 'Connexion', 'Succès', 'utilisateur', '2025-10-16 18:33:55'),
(307, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-10-16 18:33:55'),
(308, 5, 'Connexion', 'Succès', 'utilisateur', '2025-11-30 22:40:15'),
(309, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-11-30 22:40:15'),
(310, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-11-30 22:40:31'),
(311, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-11-30 22:40:49'),
(312, 5, 'Connexion', 'Succès', 'utilisateur', '2025-11-30 23:29:50'),
(313, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-11-30 23:29:50'),
(314, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-11-30 23:30:04'),
(315, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-01 00:38:45'),
(316, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-01 00:38:45'),
(317, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-01 00:38:56'),
(318, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-01 00:39:01'),
(319, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-01 00:58:33'),
(320, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-01 01:52:43'),
(321, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-01 01:52:43'),
(322, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-01 01:53:12'),
(323, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-01 01:53:16'),
(324, 101, 'Connexion', 'Succès', 'utilisateur', '2025-12-01 01:54:09'),
(325, 101, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-01 01:54:28'),
(326, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-02 21:04:12'),
(327, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-02 21:04:12'),
(328, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-02 23:01:49'),
(329, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-02 23:01:49'),
(330, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-04 21:42:10'),
(331, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 21:42:10'),
(332, 5, 'Modification', 'Succès', 'attribution', '2025-12-04 21:42:41'),
(333, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-04 21:42:44'),
(334, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-04 21:43:05'),
(335, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 21:43:05'),
(336, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-04 21:45:09'),
(337, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-04 21:45:31'),
(338, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 21:45:32'),
(339, 5, 'Import', '', 'Archive', '2025-12-04 21:46:01'),
(340, 5, 'Modification', '', 'Archive Étudiant', '2025-12-04 21:47:34'),
(341, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-04 21:54:23'),
(342, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 21:54:23'),
(343, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-04 22:12:59'),
(344, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 22:12:59'),
(345, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 23:11:14'),
(346, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 23:12:24'),
(347, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-04 23:36:00'),
(348, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 23:36:00'),
(349, 5, 'Modification', 'Succès', 'attribution', '2025-12-04 23:37:23'),
(350, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-04 23:37:35'),
(351, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-04 23:37:56'),
(352, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 23:37:56'),
(353, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-04 23:39:48'),
(354, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-04 23:40:52'),
(355, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-08 00:01:26'),
(356, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-08 00:01:30'),
(357, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-08 00:01:39'),
(358, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-08 00:01:39'),
(359, 5, 'Import', 'Succès', 'Archive', '2025-12-08 00:05:14'),
(360, 5, 'Sauvegarde', 'Erreur', 'base_de_donnees', '2025-12-08 00:19:49'),
(361, 5, 'Création', 'Succès', 'niveau_etude', '2025-12-10 18:58:59'),
(362, 5, 'Création', 'Succès', 'semestre', '2025-12-10 18:59:22'),
(363, 5, 'Création', 'Succès', 'semestre', '2025-12-10 18:59:33'),
(364, 5, 'Modification', 'Succès', 'semestre', '2025-12-10 18:59:43'),
(365, 99, 'Connexion', 'Succès', 'utilisateur', '2025-12-10 19:19:24'),
(366, 99, 'Création', 'Succès', 'etudiants', '2025-12-10 19:22:53'),
(367, 99, 'Suppression', 'Succès', 'etudiants', '2025-12-10 19:26:26'),
(368, 99, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-10 19:26:33'),
(369, 101, 'Connexion', 'Succès', 'utilisateur', '2025-12-10 19:27:16'),
(370, 101, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-11 11:39:21'),
(371, 100, 'Connexion', 'Succès', 'utilisateur', '2025-12-11 11:40:21'),
(372, 102, 'Connexion', 'Succès', 'utilisateur', '2025-12-11 11:41:33'),
(373, 100, 'Création', 'Succès', 'candidature_soutenance', '2025-12-11 11:43:49'),
(374, 100, 'Création', 'Succès', 'candidature_soutenance', '2025-12-11 11:43:54'),
(375, 99, 'Connexion', 'Succès', 'utilisateur', '2025-12-11 11:44:43'),
(376, 99, 'Validation', 'Succès', 'candidature_soutenance', '2025-12-11 11:44:57'),
(377, 99, 'Validation', 'Succès', 'candidature_soutenance', '2025-12-11 11:45:00'),
(378, 99, 'Validation', 'Succès', 'candidature_soutenance', '2025-12-11 11:45:04'),
(379, 99, 'Envoi résultats', 'Succès', 'candidature_soutenance', '2025-12-11 11:45:11'),
(380, 100, 'Création', 'Succès', 'rapport_etudiants', '2025-12-11 11:47:33'),
(381, 100, 'Dépôt', 'Succès', 'rapport', '2025-12-11 11:52:12'),
(382, 99, 'Création', 'Succès', 'notes', '2025-12-11 12:04:20'),
(383, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-15 15:42:43'),
(384, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-15 16:04:37'),
(385, 99, 'Connexion', 'Succès', 'utilisateur', '2025-12-15 16:10:29'),
(386, 5, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-15 16:52:21'),
(387, 5, 'Connexion', 'Succès', 'utilisateur', '2025-12-15 17:18:15'),
(388, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-12-15 17:18:15'),
(389, 99, 'Déconnexion', 'Succès', 'utilisateur', '2025-12-15 17:20:54'),
(390, 5, 'Connexion', 'Succès', 'utilisateur', '2026-01-05 22:22:55'),
(391, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-05 22:22:56'),
(392, 5, 'Modification', 'Succès', 'attribution', '2026-01-05 22:23:38'),
(393, 5, 'Connexion', 'Succès', 'utilisateur', '2026-01-05 23:15:24'),
(394, 100, 'Connexion', 'Succès', 'utilisateur', '2026-01-05 23:25:32'),
(395, 5, 'Déconnexion', 'Succès', 'utilisateur', '2026-01-05 23:51:37'),
(396, 5, 'Connexion', 'Succès', 'utilisateur', '2026-01-05 23:51:50'),
(397, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-05 23:51:50'),
(398, 100, 'Déconnexion', 'Succès', 'utilisateur', '2026-01-05 23:58:06'),
(399, 100, 'Connexion', 'Succès', 'utilisateur', '2026-01-05 23:58:50'),
(400, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:15:52'),
(401, 5, 'Modification', 'Erreur', 'attribution', '2026-01-06 00:16:20'),
(402, 5, 'Modification', 'Erreur', 'attribution', '2026-01-06 00:16:32'),
(403, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:24:41'),
(404, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:00'),
(405, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:00'),
(406, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:01'),
(407, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:01'),
(408, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:01'),
(409, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:01'),
(410, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:02'),
(411, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:02'),
(412, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:02'),
(413, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:02'),
(414, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:02'),
(415, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:02'),
(416, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:03'),
(417, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:03'),
(418, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:03'),
(419, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:03'),
(420, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:04'),
(421, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:04'),
(422, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:04'),
(423, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:05'),
(424, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:05'),
(425, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:05'),
(426, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:05'),
(427, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:06'),
(428, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:06'),
(429, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:06'),
(430, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:06'),
(431, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:07'),
(432, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:07'),
(433, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:07'),
(434, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:07'),
(435, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:08'),
(436, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:08'),
(437, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:08'),
(438, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:08'),
(439, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:09'),
(440, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:09'),
(441, 100, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:09'),
(442, 100, 'acces_refuse', '', 'permission', '2026-01-06 00:25:09'),
(443, 100, 'Déconnexion', 'Succès', 'utilisateur', '2026-01-06 00:25:09'),
(444, 99, 'Connexion', 'Succès', 'utilisateur', '2026-01-06 00:25:30'),
(445, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:30'),
(446, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:30'),
(447, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:31'),
(448, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:31'),
(449, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:31'),
(450, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:31'),
(451, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:32'),
(452, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:32'),
(453, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:32'),
(454, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:32'),
(455, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:33'),
(456, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:33'),
(457, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:33'),
(458, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:33'),
(459, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:34'),
(460, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:34'),
(461, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:34'),
(462, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:35'),
(463, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:35'),
(464, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:35'),
(465, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:35'),
(466, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:36'),
(467, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:36'),
(468, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:36'),
(469, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:37'),
(470, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:37'),
(471, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:37'),
(472, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:37'),
(473, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:38'),
(474, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:38'),
(475, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:38'),
(476, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:38'),
(477, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:39'),
(478, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:39'),
(479, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:39'),
(480, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:39'),
(481, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:40'),
(482, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:41'),
(483, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:41'),
(484, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:42'),
(485, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:42'),
(486, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:42'),
(487, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:42'),
(488, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:43'),
(489, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:43'),
(490, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:43'),
(491, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:43'),
(492, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:44'),
(493, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:44'),
(494, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:44'),
(495, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:44'),
(496, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:44'),
(497, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:45'),
(498, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:45'),
(499, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:45'),
(500, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:45'),
(501, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:46'),
(502, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:46'),
(503, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:46'),
(504, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:46'),
(505, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:47'),
(506, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:47'),
(507, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:47'),
(508, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:47'),
(509, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:48'),
(510, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:48'),
(511, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:48'),
(512, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:48'),
(513, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:49'),
(514, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:49'),
(515, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:49'),
(516, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:49'),
(517, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:50'),
(518, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:50'),
(519, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:50'),
(520, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:50'),
(521, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:50'),
(522, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:56'),
(523, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:56'),
(524, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:56'),
(525, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:56'),
(526, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:57'),
(527, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:57'),
(528, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:57'),
(529, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:57'),
(530, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:57'),
(531, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:57'),
(532, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:58'),
(533, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:58'),
(534, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:58'),
(535, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:58'),
(536, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:58'),
(537, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:59'),
(538, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:59'),
(539, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:25:59'),
(540, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:25:59'),
(541, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:00'),
(542, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:00'),
(543, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:00'),
(544, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:00'),
(545, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:01'),
(546, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:01'),
(547, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:01'),
(548, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:01'),
(549, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:02'),
(550, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:02'),
(551, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:02'),
(552, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:02'),
(553, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:03'),
(554, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:03'),
(555, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:03'),
(556, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:03'),
(557, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:03'),
(558, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:04'),
(559, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:04'),
(560, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:04'),
(561, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:04'),
(562, 99, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 00:26:35'),
(563, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:26:35'),
(564, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:28:02'),
(565, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:45:34'),
(566, 5, 'Modification', '', 'attribution', '2026-01-06 00:48:04'),
(567, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:48:12'),
(568, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:48:16'),
(569, 99, 'acces_refuse', '', 'permission', '2026-01-06 00:48:20'),
(570, 99, 'acces_refuse', '', 'permission', '2026-01-06 01:18:06'),
(571, 99, 'acces_refuse', '', 'permission', '2026-01-06 01:18:10'),
(572, 99, 'acces_refuse', '', 'permission', '2026-01-06 01:18:14'),
(573, 99, 'acces_refuse', '', 'permission', '2026-01-06 01:18:20'),
(574, 5, 'Connexion', 'Succès', 'utilisateur', '2026-01-06 21:23:06'),
(575, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 21:23:07'),
(576, 99, 'Connexion', 'Succès', 'utilisateur', '2026-01-06 21:37:03'),
(577, 102, 'Connexion', 'Succès', 'utilisateur', '2026-01-06 21:38:06'),
(578, 102, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 21:38:06'),
(579, 102, 'acces_refuse', '', 'permission', '2026-01-06 21:38:06'),
(580, 5, 'Modification', '', 'attribution', '2026-01-06 21:39:11'),
(581, 5, 'Modification', '', 'attribution', '2026-01-06 21:40:55'),
(582, 5, 'Modification', '', 'attribution', '2026-01-06 21:50:06'),
(583, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 22:02:29'),
(584, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-06 22:55:51'),
(585, 5, 'Connexion', 'Succès', 'utilisateur', '2026-01-07 21:11:52'),
(586, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-07 21:11:52'),
(587, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-08 01:17:52'),
(588, 5, 'Connexion', 'Succès', 'utilisateur', '2026-01-09 10:56:32'),
(589, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-09 10:56:32'),
(590, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-09 10:57:04'),
(591, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-09 11:03:23'),
(592, 5, 'Connexion', 'Succès', 'utilisateur', '2026-01-14 01:19:32'),
(593, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-14 01:19:33'),
(594, 5, 'Connexion', 'Succès', 'utilisateur', '2026-01-14 18:55:19'),
(595, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-14 18:55:19'),
(596, 5, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-14 19:03:11'),
(597, 99, 'Connexion', 'Succès', 'utilisateur', '2026-01-24 12:51:12'),
(598, 99, 'Déconnexion', 'Succès', 'utilisateur', '2026-01-24 14:07:53'),
(599, 99, 'Connexion', 'Succès', 'utilisateur', '2026-01-24 14:20:17'),
(600, 109, 'Connexion', 'Succès', 'utilisateur', '2026-01-24 14:26:04'),
(601, 99, 'Connexion', 'Succès', 'utilisateur', '2026-01-24 14:27:25'),
(602, 99, 'Déconnexion', 'Succès', 'utilisateur', '2026-01-24 14:28:25'),
(603, 109, 'Connexion', 'Succès', 'utilisateur', '2026-01-24 14:28:41'),
(604, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 14:33:29'),
(605, 109, 'Connexion', 'Succès', 'utilisateur', '2026-01-24 17:34:25'),
(606, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 17:37:13'),
(607, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 17:38:10'),
(608, 109, 'Déconnexion', 'Succès', 'utilisateur', '2026-01-24 17:41:29'),
(609, 109, 'Connexion', 'Succès', 'utilisateur', '2026-01-24 17:41:56'),
(610, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 17:41:57'),
(611, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 18:15:37'),
(612, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 19:05:14'),
(613, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 21:20:48'),
(614, 109, 'Déconnexion', 'Succès', 'utilisateur', '2026-01-24 21:25:04'),
(615, 109, 'Connexion', 'Succès', 'utilisateur', '2026-01-24 21:25:27'),
(616, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 21:47:16'),
(617, 109, 'Modification', 'Succès', 'permissions', '2026-01-24 22:18:30'),
(618, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 22:19:03'),
(619, 99, 'Connexion', 'Succès', 'utilisateur', '2026-01-24 22:34:49'),
(620, 109, 'Accès', 'Succès', 'tableau_de_bord', '2026-01-24 22:39:51');

-- --------------------------------------------------------

--
-- Table structure for table `programmer`
--

DROP TABLE IF EXISTS `programmer`;
CREATE TABLE IF NOT EXISTS `programmer` (
  `id_programmation` int NOT NULL AUTO_INCREMENT,
  `num_etud` int NOT NULL,
  `num_jury` int NOT NULL,
  `id_salle` int DEFAULT NULL,
  `date_soutenance` date DEFAULT NULL,
  `heure_soutenance` time DEFAULT NULL,
  `theme_soutenance` varchar(200) NOT NULL,
  PRIMARY KEY (`id_programmation`),
  KEY `num_etud` (`num_etud`),
  KEY `id_salle` (`id_salle`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `programmer`
--

INSERT INTO `programmer` (`id_programmation`, `num_etud`, `num_jury`, `id_salle`, `date_soutenance`, `heure_soutenance`, `theme_soutenance`) VALUES
(2, 20220001, 1, 2, '2025-10-15', '15:00:00', 'Informatisation des techniques d\'audit grâce à l\'IA : Cas Deloitte'),
(3, 20100001, 2, NULL, '0000-00-00', NULL, 'Développement d\'une application web de gestion des étudiants'),
(4, 20229004, 4745, 3, '0000-00-00', '00:00:00', 'Frontend gestion annuaire'),
(5, 20229006, 8089, 4, '0000-00-00', '00:00:00', 'Gestion recommandation'),
(6, 20229007, 2165, 5, '0000-00-00', '00:00:00', 'Plateforme RH'),
(7, 20229008, 9185, 6, '0000-00-00', '00:00:00', 'Plateforme géoréférencement'),
(8, 20229009, 3831, 4, '0000-00-00', '00:00:00', 'Logiciel centre médical'),
(9, 20229010, 3447, 7, '0000-00-00', '00:00:00', 'Automatisation contrôle conformité'),
(10, 20229011, 6636, 3, '0000-00-00', '00:00:00', 'RH modules formation congés'),
(11, 20229012, 7665, 8, '0000-00-00', '00:00:00', 'App gestion prestations sociales'),
(12, 20229013, 5808, 9, '0000-00-00', '00:00:00', 'Outil gestion stock'),
(13, 20229014, 3123, 7, '0000-00-00', '00:00:00', 'Automatisation tests SWIFT'),
(14, 20229015, 4493, 10, '0000-00-00', '00:00:00', 'App gestion réassurance'),
(15, 20229016, 2087, 8, '0000-00-00', '00:00:00', 'Digitalisation biens état'),
(16, 20229017, 4557, 7, '0000-00-00', '00:00:00', 'Tests qualification module'),
(17, 20229018, 6359, 6, '0000-00-00', '00:00:00', 'Module mission Dynamics'),
(18, 20229019, 2576, 6, '0000-00-00', '00:00:00', 'RH Oracle HCM Cloud'),
(19, 20229020, 9484, 3, '0000-00-00', '00:00:00', 'Outil gestion incidents'),
(20, 20229021, 2925, 11, '0000-00-00', '00:00:00', 'App web suivi recommandations'),
(21, 20229022, 1657, 3, '0000-00-00', '00:00:00', 'Digitalisation suivi temps'),
(22, 20229023, 6851, 12, '0000-00-00', '00:00:00', 'Gestion facturation navires'),
(23, 20230001, 6577, 3, '0000-00-00', '00:00:00', 'Solution RH gestion personnel'),
(24, 20230002, 6733, 12, '0000-00-00', '00:00:00', 'Rapprochement états financiers'),
(25, 20230003, 8716, 8, '0000-00-00', '00:00:00', 'Planification projet IT'),
(26, 20230005, 7979, 7, '0000-00-00', '00:00:00', 'Outil suivi dépenses'),
(27, 20230009, 8900, 13, '0000-00-00', '00:00:00', 'App web immobilier'),
(28, 20230010, 3480, 4, '0000-00-00', '00:00:00', 'Signature électronique PDF'),
(29, 20230011, 2045, 10, '0000-00-00', '00:00:00', 'Schéma directeur SI'),
(30, 20230012, 1882, 14, '0000-00-00', '00:00:00', 'Traçabilité digitale cacao'),
(31, 20240010, 2265, 8, '0000-00-00', '00:00:00', 'Plateforme Iflex fibre optique'),
(32, 20240011, 8404, 15, '0000-00-00', '00:00:00', 'Gestion locative immobilière'),
(33, 20240012, 5705, 12, '0000-00-00', '00:00:00', 'Suivi irrigation parcelles'),
(34, 20240013, 9405, 16, '0000-00-00', '00:00:00', 'Gestion projets BTP'),
(35, 20240015, 7699, 12, '0000-00-00', '00:00:00', 'Suivi chèques impayés'),
(36, 20240016, 9923, 5, '0000-00-00', '00:00:00', 'Gestion taxes municipales'),
(37, 20240017, 4301, 5, '0000-00-00', '00:00:00', 'Plateforme santé numérique');

-- --------------------------------------------------------

--
-- Table structure for table `rapport_etudiants`
--

DROP TABLE IF EXISTS `rapport_etudiants`;
CREATE TABLE IF NOT EXISTS `rapport_etudiants` (
  `id_rapport` int NOT NULL AUTO_INCREMENT,
  `num_etu` int NOT NULL,
  `nom_rapport` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `date_rapport` datetime NOT NULL,
  `theme_rapport` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `chemin_fichier` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Chemin vers le fichier de contenu',
  `statut_rapport` enum('en_cours','valider','rejeter','en_attente') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'en_cours',
  `date_modification` datetime DEFAULT NULL,
  `taille_fichier` int DEFAULT NULL COMMENT 'Taille du fichier en octets',
  `version` int NOT NULL DEFAULT '1' COMMENT 'Version du rapport',
  `etape_validation` enum('en_cours','en_attente_communication','desapprouve_communication','approuve_communication','en_attente_commission','desapprouve_commission','approuve_commission','valide','rejete') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT 'en_cours',
  PRIMARY KEY (`id_rapport`),
  KEY `num_etu` (`num_etu`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `rapport_etudiants`
--

INSERT INTO `rapport_etudiants` (`id_rapport`, `num_etu`, `nom_rapport`, `date_rapport`, `theme_rapport`, `chemin_fichier`, `statut_rapport`, `date_modification`, `taille_fichier`, `version`, `etape_validation`) VALUES
(16, 20220001, 'L\'AUDIT AU CENTRE DE TOUTES LES ETAPES DE CONCEPTION', '2025-09-29 22:26:28', 'AUDIT ET CONTROLE', 'rapport_16.html', 'valider', '2025-09-29 22:26:45', 11561, 1, 'valide'),
(17, 20100001, 'Rapport KOUASSI Jean-Baptiste', '2025-12-04 00:00:00', 'Développement d\'une application web de gestion des étudiants', NULL, 'valider', NULL, NULL, 1, 'en_attente_commission'),
(25, 20099001, 'Rapport BODJE', '2010-06-18 00:00:00', 'Outil de gestion des tickets grattage', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(26, 20099002, 'Rapport AKINOLA', '2010-06-18 00:00:00', 'Optimisation d\'un CRM : cas Trackers', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(27, 20099003, 'Rapport OUEDRAOGO', '2010-06-18 00:00:00', 'Interfaçage BD site web surveillance bancaire', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(28, 20099004, 'Rapport DIALLO', '2010-09-13 00:00:00', 'Logiciel de gestion de devis', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(29, 20099005, 'Rapport DEGNI', '2010-09-18 00:00:00', 'Logiciel de help desk', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(30, 20099006, 'Rapport BEDI', '2010-09-18 00:00:00', 'Migration SICA V2 vers V3', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(31, 20099007, 'Rapport AYEGON', '2010-09-18 00:00:00', 'Informatisation d\'une SSII', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(32, 20099008, 'Rapport N\'GBO', '2010-09-18 00:00:00', 'App. gestion données état civil', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(33, 20099009, 'Rapport LOBA', '2010-09-18 00:00:00', 'Logiciel unique gestion', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(34, 20099010, 'Rapport KOUAME', '2010-11-19 00:00:00', 'SI Décisionnel commercial', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(35, 20099011, 'Rapport ATTISSOU', '2010-11-19 00:00:00', 'Entrepôt de données support décision', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(36, 20109001, 'Rapport ADJA', '2011-01-17 00:00:00', 'Sécurisation recettes douanières paiement élec.', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(37, 20109002, 'Rapport OUSSOU', '2011-01-17 00:00:00', 'Implémentation SI ISO 20000', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(38, 20109003, 'Rapport ATTISSOU', '2011-01-17 00:00:00', 'SI Décisionnel direction ventes', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(39, 20109004, 'Rapport NGBO', '2011-01-17 00:00:00', 'App gestion état civil', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(40, 20109005, 'Rapport DOBE', '2011-01-17 00:00:00', 'App informatique décisionnelle SI Anaré', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(41, 20109006, 'Rapport KABRAN', '2011-02-15 00:00:00', 'App web gestion RDV visa biométrique', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(42, 20109007, 'Rapport TOURE', '2011-02-15 00:00:00', 'App gestion équipements', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(43, 20109008, 'Rapport NIAMKEY', '2011-03-16 00:00:00', 'App web gestion stocks', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(44, 20109009, 'Rapport MONNEY', '2011-03-16 00:00:00', 'Système recueil infos filière coton', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(45, 20109010, 'Rapport GORE', '2011-03-16 00:00:00', 'Système alerte qualité café cacao', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(46, 20109011, 'Rapport OUATTARA', '2011-03-16 00:00:00', 'SI données sécuritaires gendarmerie', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(47, 20109012, 'Rapport NGUESSAN', '2011-03-16 00:00:00', 'Gestion finance publique ambassades', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(48, 20109013, 'Rapport MARIKO', '2011-07-07 00:00:00', 'Optimisation production état synthèse', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(49, 20109014, 'Rapport KACOU', '2011-07-07 00:00:00', 'Non présenté (Gestion progiciel)', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(50, 20109015, 'Rapport GOHOUROU', '2011-08-15 00:00:00', 'Gestionnaire contenu commerce en ligne', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(51, 20109016, 'Rapport KOUA', '2011-08-15 00:00:00', 'Module gestion lettres chèques Mercure', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(52, 20109017, 'Rapport KEI', '2011-11-18 00:00:00', 'BI pour mobile banking', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(53, 20109018, 'Rapport ASSI', '2011-12-30 00:00:00', 'Gestion relevés électroniques', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(54, 20119001, 'Rapport ANGO', '2012-01-26 00:00:00', 'Messagerie unifiée RDV', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(55, 20119002, 'Rapport ACHO', '2012-01-26 00:00:00', 'SI aide décision assurance', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(56, 20119003, 'Rapport KOUAME', '2012-01-26 00:00:00', 'Progiciel GRH et paie', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(57, 20119004, 'Rapport SAWADOGO', '2012-01-26 00:00:00', 'VPN et serveur BD accès distant', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(58, 20119005, 'Rapport ASSI', '2012-01-26 00:00:00', 'App gestion relevé électronique', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(59, 20119006, 'Rapport MONNE', '2012-02-24 00:00:00', 'Gestion filière coton', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(60, 20119007, 'Rapport TOURE', '2012-02-24 00:00:00', 'App gestion commandes', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(61, 20119008, 'Rapport KACON', '2012-02-24 00:00:00', 'App gestion école (SaaS)', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(62, 20119009, 'Rapport HOUSSOU', '2012-03-19 00:00:00', 'Thème changé', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(63, 20119010, 'Rapport AYENON', '2012-03-19 00:00:00', 'Informatisation direction commerciale', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(64, 20119011, 'Rapport KANGAH', '2012-03-19 00:00:00', 'Gestion incohérences BD', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(65, 20119012, 'Rapport ADAMA', '2012-03-19 00:00:00', 'App suivi enseignements', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(66, 20119013, 'Rapport KOFFI', '2012-03-19 00:00:00', 'Notification SMS passeport', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(67, 20119014, 'Rapport AYEGON', '2012-04-24 00:00:00', 'App contrôle workflow chèques', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(68, 20119015, 'Rapport DOUAMPO', '2012-06-20 00:00:00', 'BD gestion énergies', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(69, 20119016, 'Rapport DONGO', '2012-06-20 00:00:00', 'App gestion panneau publicitaire', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(70, 20119017, 'Rapport NAMOGO', '2012-06-20 00:00:00', 'Optimisation module GRH Navision', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(71, 20119018, 'Rapport YETE', '2012-06-20 00:00:00', 'Plateforme décisionnelle analyse clientèle', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(72, 20119019, 'Rapport LOBE', '2012-07-23 00:00:00', 'Audit SI', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(73, 20119020, 'Rapport NINSEMON', '2012-07-23 00:00:00', 'Archi technique SI décisionnel', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(74, 20119021, 'Rapport GOEH', '2012-07-23 00:00:00', 'Plateforme Cloud collaboratif', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(75, 20119022, 'Rapport FAGLA', '2012-08-21 00:00:00', 'Plateforme échange ens/etud/parents', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(76, 20119023, 'Rapport GNAYORO', '2012-08-21 00:00:00', 'App suivi opérations bancaires', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(77, 20119024, 'Rapport KOUADIO', '2012-08-21 00:00:00', 'Logiciel suivi coopératives', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(78, 20119025, 'Rapport KOUASSI', '2012-09-19 00:00:00', 'Logiciel gestion maintenance', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(79, 20119026, 'Rapport KOUADIO', '2012-10-18 00:00:00', 'SI automatisé USSD gasoil', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(80, 20119027, 'Rapport GOSAN', '2012-10-18 00:00:00', 'App gestion produits', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(81, 20119028, 'Rapport NGUESSAN', '2012-10-18 00:00:00', 'App gestion incidents locaux', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(82, 20119029, 'Rapport DJEDJE', '2012-11-16 00:00:00', 'Module GRH', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(83, 20119030, 'Rapport ANON', '2012-11-16 00:00:00', 'Logiciel gestion projets agricoles', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(84, 20119031, 'Rapport KOUASSI', '2012-11-16 00:00:00', 'Système monitoring événements', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(85, 20119032, 'Rapport OUSSOU', '2012-12-20 00:00:00', 'Archi réseau sécurisée', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(86, 20119033, 'Rapport AKEBOUE', '2012-12-20 00:00:00', 'SI gestion activités forestières', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(87, 20129001, 'Rapport KOFFI', '2013-01-18 00:00:00', 'Processus SMQ', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(88, 20129002, 'Rapport KOFFI', '2013-01-18 00:00:00', 'Module conseil discipline SIGFAE', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(89, 20129003, 'Rapport KOFFI', '2013-02-19 00:00:00', '(2e passage) App web SMQ', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(90, 20129004, 'Rapport KOFFI', '2013-02-19 00:00:00', '(2e passage) Module SIGFAE', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(91, 20129005, 'Rapport OSSEIN', '2013-02-19 00:00:00', 'Messagerie sécurisée', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(92, 20129006, 'Rapport BAILLY', '2013-02-19 00:00:00', 'Suivi évaluation à distance', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(93, 20129007, 'Rapport NINDJIN', '2013-02-19 00:00:00', 'Plateforme GED', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(94, 20129008, 'Rapport NGUESSAN', '2013-02-19 00:00:00', 'Automatisation incidents', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(95, 20129009, 'Rapport OUEDRAOGO', '2013-02-19 00:00:00', 'Optimisation réseaux', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(96, 20129010, 'Rapport OSSEIN', '2013-03-22 00:00:00', '(2e pass) Messagerie sécurisé', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(97, 20129011, 'Rapport NINDJIN', '2013-03-22 00:00:00', '(2e pass) Service annonce', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(98, 20129012, 'Rapport KANTE', '2013-05-20 00:00:00', 'Stratégie sécurité SI', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(99, 20129013, 'Rapport N\'GUESSAN', '2013-06-20 00:00:00', '(Non indiqué) Module SIGFIP', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(100, 20129014, 'Rapport FOFANA', '2013-06-20 00:00:00', 'App web parcs camions', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(101, 20129015, 'Rapport AMICHIA', '2013-06-20 00:00:00', 'App gestion stocks', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(102, 20129016, 'Rapport OUATTARA', '2013-07-20 00:00:00', 'Plateforme web OEV', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(103, 20129017, 'Rapport ATTIEMBONE', '2013-07-20 00:00:00', 'App suivi compteurs HTA/BT', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(104, 20129018, 'Rapport KOUAME', '2013-07-20 00:00:00', 'Modules LIGES', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(105, 20129019, 'Rapport MONNE', '2013-09-17 00:00:00', 'SI décisionnel bordereaux', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(106, 20129020, 'Rapport DOOU', '2013-12-20 00:00:00', 'Automatisation budget', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(107, 20139001, 'Rapport KOUADIO', '2014-01-17 00:00:00', 'Contrôle budgétaire automatisé', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(108, 20139002, 'Rapport KOULOUBLA', '2014-02-27 00:00:00', 'App calcul provisions', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(109, 20139003, 'Rapport DIAWARA', '2014-06-19 00:00:00', 'Centre d\'appel décentralisé', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(110, 20139004, 'Rapport DIAW', '2014-06-19 00:00:00', 'Sonde Nagios', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(111, 20139005, 'Rapport KOBENA', '2014-06-19 00:00:00', 'Contrat versus compassion', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(112, 20139006, 'Rapport TANOH', '2014-06-19 00:00:00', 'Optimisation réseau local', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(113, 20139007, 'Rapport BEUGRE', '2014-06-19 00:00:00', 'Progiciel fonds solidarité', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(114, 20139008, 'Rapport KONAN', '2014-06-26 00:00:00', 'Progiciel gestion patrimoine', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(115, 20139009, 'Rapport FLAN', '2014-06-26 00:00:00', 'App gestion personnel', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(116, 20139010, 'Rapport ATTRO', '2014-06-26 00:00:00', 'Objets 3D KinectPedia', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(117, 20139011, 'Rapport KOUAME', '2014-06-26 00:00:00', 'Capteur Kinect Objets 3D', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(118, 20139012, 'Rapport ASSI', '2014-10-03 00:00:00', 'App matériel informatique', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(119, 20139013, 'Rapport SYLLA', '2014-10-03 00:00:00', 'Automate communication bancaire', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(120, 20139014, 'Rapport TRAORE', '2014-10-03 00:00:00', 'Optimisation parc informatique', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(121, 20139015, 'Rapport DIARRA- SOUBA', '2014-10-03 00:00:00', 'App gestion appels offres', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(122, 20139016, 'Rapport YAO', '2014-10-03 00:00:00', 'Datamart assurance-vie', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(123, 20159001, 'Rapport N\'GUESSAN', '2016-06-03 00:00:00', 'Tableau de bord suivi projets', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(124, 20159002, 'Rapport BLE', '2016-06-03 00:00:00', 'App assurance vie AS/400 vers Web', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(125, 20159003, 'Rapport KOUAME', '2016-06-03 00:00:00', 'App gestion primes', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(126, 20159004, 'Rapport TRA', '2016-06-03 00:00:00', 'Plateforme réservation taxi', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(127, 20159005, 'Rapport ABOULE', '2016-06-03 00:00:00', 'App gestion files d\'attentes', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(128, 20159006, 'Rapport FRONDO', '2016-06-03 00:00:00', 'App mobile actes administratifs', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(129, 20159007, 'Rapport KOULATE', '2016-06-03 00:00:00', 'App mobile bancaire', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(130, 20159008, 'Rapport KONAN', '2016-09-20 00:00:00', 'Logiciel gestion commerciale', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(131, 20159009, 'Rapport DIAHOU', '2016-09-20 00:00:00', 'Logiciel gestion projets', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(132, 20159010, 'Rapport BOBOU', '2016-09-20 00:00:00', 'Outil workflow SharePoint', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(133, 20159011, 'Rapport DJIDJI', '2016-09-20 00:00:00', 'SI gestion pesage', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(134, 20159012, 'Rapport AGNARAMON', '2016-09-20 00:00:00', 'App coaching abonnés', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(135, 20169001, 'Rapport TONOHOAN', '2017-06-23 00:00:00', 'App suivi SIMBOX', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(136, 20169002, 'Rapport KOUAKOU', '2017-06-23 00:00:00', 'App gestion titres accès', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(137, 20169003, 'Rapport OBA', '2017-07-17 00:00:00', 'Solution reporting banking', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(138, 20169004, 'Rapport KARDIOULA', '2017-07-17 00:00:00', 'Service diffusion GSM', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(139, 20169005, 'Rapport DJIDJI', '2017-09-22 00:00:00', 'App gestion produits auto', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(140, 20169006, 'Rapport KOKI', '2017-09-22 00:00:00', 'App gestion carburants', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(141, 20169007, 'Rapport BLAGUE', '2017-09-22 00:00:00', 'SI évaluation risques', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(142, 20169008, 'Rapport KOFFI', '2017-09-22 00:00:00', 'Solution paiement électrique', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(143, 20179001, 'Rapport DOGO', '2018-01-22 00:00:00', 'Inconnu', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(144, 20179002, 'Rapport KOUAO', '2018-01-22 00:00:00', 'Intranet collaboratif', NULL, 'en_cours', NULL, NULL, 1, 'en_attente_commission'),
(145, 20179003, 'Rapport TRA BI', '2018-01-22 00:00:00', 'Micro-service microfinance', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(146, 20179004, 'Rapport KESSE', '2018-06-21 00:00:00', 'Billetterie en ligne', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(148, 20179006, 'Rapport GAYE', '2018-06-21 00:00:00', 'Référentiel archi entreprise', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(150, 20179008, 'Rapport COULIBALY', '2018-08-10 00:00:00', 'Plateforme données agro', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(151, 20179009, 'Rapport N\'ZI', '2018-08-10 00:00:00', 'App gestion épargne', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(152, 20179010, 'Rapport OUATTARA', '2018-08-10 00:00:00', 'Datawarehouse', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(153, 20179011, 'Rapport COULOU', '2018-08-10 00:00:00', 'Maintenance réseaux', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(154, 20179012, 'Rapport KONAN', '2018-08-10 00:00:00', 'Logiciel achats généraux', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(155, 20229001, 'Rapport KOUAO', '2023-02-02 00:00:00', 'Digitalisation gestion biens état', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(156, 20229002, 'Rapport KONAN', '2023-02-02 00:00:00', 'Traçabilité fèves cacao', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(157, 20229003, 'Rapport AYA', '2023-02-02 00:00:00', 'Obsolescence SI bancaire', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(158, 20229004, 'Rapport GNOGAN', '2023-02-02 00:00:00', 'Frontend gestion annuaire', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(160, 20229006, 'Rapport OUATTARA', '2023-02-02 00:00:00', 'Gestion recommandation', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(161, 20229007, 'Rapport DJECKET', '2023-03-30 00:00:00', 'Plateforme RH', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(162, 20229008, 'Rapport DIARRA- SOUBA', '2023-03-30 00:00:00', 'Plateforme géoréférencement', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(163, 20229009, 'Rapport YAO', '2023-03-30 00:00:00', 'Logiciel centre médical', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(164, 20229010, 'Rapport DIARRA- SOUBA', '2025-12-08 00:00:00', 'Automatisation contrôle conformité', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(165, 20229011, 'Rapport SALIFOU', '2025-12-08 00:00:00', 'RH modules formation congés', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(166, 20229012, 'Rapport DOUASSE', '2025-12-08 00:00:00', 'App gestion prestations sociales', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(167, 20229013, 'Rapport VANIE', '2025-12-08 00:00:00', 'Outil gestion stock', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(168, 20229014, 'Rapport BIDI', '2025-12-08 00:00:00', 'Automatisation tests SWIFT', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(169, 20229015, 'Rapport HOUNDJI', '2025-12-08 00:00:00', 'App gestion réassurance', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(170, 20229016, 'Rapport KOUAO', '2025-12-08 00:00:00', 'Digitalisation biens état', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(171, 20229017, 'Rapport LAGO', '2025-12-08 00:00:00', 'Tests qualification module', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(172, 20229018, 'Rapport YAO-SAKY', '2025-12-08 00:00:00', 'Module mission Dynamics', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(173, 20229019, 'Rapport BOKA', '2025-12-08 00:00:00', 'RH Oracle HCM Cloud', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(174, 20229020, 'Rapport SORO', '2025-12-08 00:00:00', 'Outil gestion incidents', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(175, 20229021, 'Rapport BROU', '2025-12-08 00:00:00', 'App web suivi recommandations', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(176, 20229022, 'Rapport ADOU', '2025-12-08 00:00:00', 'Digitalisation suivi temps', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(177, 20229023, 'Rapport KOUASSI', '2025-12-08 00:00:00', 'Gestion facturation navires', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(178, 20230001, 'Rapport KIOHON', '2024-01-26 00:00:00', 'Solution RH gestion personnel', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(179, 20230002, 'Rapport COULIBALY', '2024-01-26 00:00:00', 'Rapprochement états financiers', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(180, 20230003, 'Rapport BAKAYOKO', '2024-01-26 00:00:00', 'Planification projet IT', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(181, 20230004, 'Rapport KOFFI', '2024-01-26 00:00:00', 'Outil saisie bilan test', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(182, 20230005, 'Rapport SOULEY', '2024-01-26 00:00:00', 'Outil suivi dépenses', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(183, 20230006, 'Rapport KONE', '2024-04-23 00:00:00', 'Gestion trésorerie missions', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(186, 20230009, 'Rapport ATSE', '2024-04-23 00:00:00', 'App web immobilier', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(187, 20230010, 'Rapport BALIE', '2025-12-08 00:00:00', 'Signature électronique PDF', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(188, 20230011, 'Rapport KADOUNO', '2025-12-08 00:00:00', 'Schéma directeur SI', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(189, 20230012, 'Rapport KONAN', '2025-12-08 00:00:00', 'Traçabilité digitale cacao', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(190, 20240001, 'Rapport OYOU', '2024-06-26 00:00:00', 'Datavisualisation', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(191, 20240002, 'Rapport TANO', '2024-06-26 00:00:00', 'Suivi performance productivité', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(192, 20240003, 'Rapport BOUEDIRO', '2024-06-26 00:00:00', 'Etats réglementaires CIMA', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(193, 20240004, 'Rapport SOHOU', '2024-06-26 00:00:00', 'Digitalisation paie ODOO', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(194, 20240005, 'Rapport SORO', '2024-06-26 00:00:00', 'Planification opérations terrains', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(195, 20240006, 'Rapport SORO', '2024-06-26 00:00:00', 'Paiement mobile money mutuelle', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(196, 20240007, 'Rapport KOISSI', '2024-06-26 00:00:00', 'Optimisation déploiement Saphir', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(199, 20240010, 'Rapport NGUESSAN', '2024-12-27 00:00:00', 'Plateforme Iflex fibre optique', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(200, 20240011, 'Rapport ANOMA', '2024-12-27 00:00:00', 'Gestion locative immobilière', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(201, 20240012, 'Rapport SORO', '2024-12-27 00:00:00', 'Suivi irrigation parcelles', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(202, 20240013, 'Rapport KOFFI', '2024-12-27 00:00:00', 'Gestion projets BTP', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(204, 20240015, 'Rapport TOURE', '2025-12-08 00:00:00', 'Suivi chèques impayés', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(205, 20240016, 'Rapport KOUAME', '2025-12-08 00:00:00', 'Gestion taxes municipales', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(206, 20240017, 'Rapport N\'GUESSAN', '2025-12-08 00:00:00', 'Plateforme santé numérique', NULL, 'valider', NULL, NULL, 1, 'approuve_commission'),
(208, 20220002, 'web design moderne - les nouvelles mentalité des clients', '2025-12-11 11:47:33', 'Web design', 'rapport_208.html', 'en_cours', '2025-12-11 11:52:12', 13483, 1, 'en_cours');

-- --------------------------------------------------------

--
-- Table structure for table `rattacher_legacy`
--

DROP TABLE IF EXISTS `rattacher_legacy`;
CREATE TABLE IF NOT EXISTS `rattacher_legacy` (
  `id_GU` int NOT NULL,
  `id_traitement` int NOT NULL,
  PRIMARY KEY (`id_GU`,`id_traitement`),
  KEY `Key_rattacher_GU` (`id_GU`),
  KEY `Key_rattacher_traitement` (`id_traitement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `rattacher_legacy`
--

INSERT INTO `rattacher_legacy` (`id_GU`, `id_traitement`) VALUES
(5, 5),
(5, 6),
(5, 7),
(5, 8),
(5, 9),
(5, 10),
(5, 11),
(5, 12),
(5, 13),
(5, 15),
(5, 16),
(5, 17),
(5, 19),
(5, 20),
(5, 23),
(5, 24),
(5, 25),
(5, 26),
(5, 27),
(5, 28),
(5, 29),
(5, 30),
(5, 31),
(5, 32),
(5, 33),
(5, 34),
(5, 35),
(5, 36),
(5, 38),
(5, 39),
(5, 40),
(5, 41),
(5, 42),
(5, 43),
(5, 44),
(5, 45),
(5, 47),
(5, 48),
(6, 19),
(6, 29),
(6, 33),
(6, 34),
(7, 19),
(7, 29),
(7, 31),
(7, 32),
(8, 6),
(8, 19),
(8, 23),
(8, 24),
(8, 25),
(8, 26),
(8, 41),
(9, 19),
(9, 27),
(9, 29),
(10, 19),
(10, 27),
(10, 30),
(11, 19),
(11, 35),
(11, 36),
(11, 38),
(11, 39),
(11, 42),
(11, 43),
(12, 19),
(12, 27),
(12, 28),
(13, 12),
(13, 13),
(13, 15),
(13, 19),
(13, 20);

-- --------------------------------------------------------

--
-- Table structure for table `reclamations`
--

DROP TABLE IF EXISTS `reclamations`;
CREATE TABLE IF NOT EXISTS `reclamations` (
  `id_reclamation` int NOT NULL AUTO_INCREMENT,
  `num_etu` int DEFAULT NULL,
  `titre_reclamation` varchar(255) NOT NULL,
  `description_reclamation` text NOT NULL,
  `type_reclamation` enum('Académique','Administrative','Technique','Financière','Autre') NOT NULL,
  `priorite_reclamation` enum('Faible','Moyenne','Élevée','Urgente') NOT NULL DEFAULT 'Moyenne',
  `statut_reclamation` enum('En attente','Résolue','Rejetée','En cours') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'En attente',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_mise_a_jour` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_pers_admin` int DEFAULT NULL,
  PRIMARY KEY (`id_reclamation`),
  KEY `idx_num_etu` (`num_etu`),
  KEY `idx_statut` (`statut_reclamation`),
  KEY `idx_type` (`type_reclamation`),
  KEY `idx_date_creation` (`date_creation`),
  KEY `fk_admin_assigne` (`id_pers_admin`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `rendre`
--

DROP TABLE IF EXISTS `rendre`;
CREATE TABLE IF NOT EXISTS `rendre` (
  `id_CR` int NOT NULL,
  `id_enseignant` int NOT NULL,
  `date_env` datetime NOT NULL,
  KEY `Key_rendre_CR` (`id_CR`),
  KEY `Key_rendre_enseignant` (`id_enseignant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `resume_candidature`
--

DROP TABLE IF EXISTS `resume_candidature`;
CREATE TABLE IF NOT EXISTS `resume_candidature` (
  `id` int NOT NULL AUTO_INCREMENT,
  `num_etu` int NOT NULL,
  `id_candidature` int NOT NULL,
  `resume_json` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `decision` varchar(20) NOT NULL,
  `date_enregistrement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `num_etu` (`num_etu`),
  KEY `fk_candidature` (`id_candidature`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `resume_candidature`
--

INSERT INTO `resume_candidature` (`id`, `num_etu`, `id_candidature`, `resume_json`, `decision`, `date_enregistrement`) VALUES
(10, 20220001, 14, '{\"scolarite\":{\"statut\":\"\\u00c0 jour\",\"montant_total\":\"980 000 FCFA\",\"montant_paye\":\"980 000 FCFA\",\"dernier_paiement\":\"29\\/09\\/2025\",\"validation\":\"valid\\u00e9\"},\"stage\":{\"entreprise\":\"Deloitte\",\"sujet\":\"Audit et contr\\u00f4le de securit\\u00e9 informatique\",\"periode\":\"01\\/05\\/2025 - 01\\/09\\/2025\",\"encadrant\":\"Mme. Suzanne Didia\",\"validation\":\"valid\\u00e9\"},\"semestre\":{\"semestre\":\"Semestre 7, Semestre 8\",\"moyenne\":\"12.63\\/20\",\"unites\":\"60\\/60 cr\\u00e9dits valid\\u00e9s\",\"validation\":\"valid\\u00e9\"}}', 'Validée', '2025-09-29 22:22:15'),
(11, 20220002, 15, '{\"scolarite\":{\"statut\":\"\\u00c0 jour\",\"montant_total\":\"1 025 000 FCFA\",\"montant_paye\":\"1 025 000 FCFA\",\"dernier_paiement\":\"15\\/10\\/2025\",\"validation\":\"valid\\u00e9\"},\"stage\":{\"entreprise\":\"AgenceX\",\"sujet\":\"Le web design\",\"periode\":\"11\\/06\\/2024 - 01\\/03\\/2025\",\"encadrant\":\"Maitre X\",\"validation\":\"valid\\u00e9\"},\"semestre\":{\"semestre\":\"Semestre 9\",\"moyenne\":\"13.44\\/20\",\"unites\":\"30\\/30 cr\\u00e9dits valid\\u00e9s\",\"validation\":\"valid\\u00e9\"}}', 'Validée', '2025-12-11 11:45:07');

-- --------------------------------------------------------

--
-- Table structure for table `roles_jury`
--

DROP TABLE IF EXISTS `roles_jury`;
CREATE TABLE IF NOT EXISTS `roles_jury` (
  `id_role_jury` int NOT NULL AUTO_INCREMENT,
  `lib_role` varchar(50) NOT NULL,
  `description` text,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_role_jury`),
  UNIQUE KEY `lib_role` (`lib_role`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `roles_jury`
--

INSERT INTO `roles_jury` (`id_role_jury`, `lib_role`, `description`, `actif`) VALUES
(1, 'Président du jury', 'Préside la soutenance et coordonne le jury', 1),
(2, 'Encadrant', 'Encadre l\'etudiant dans le cadre de la redaction de son memoire', 1),
(3, 'Examinateur', 'Évalue la qualité scientifique du mémoire et la prestation de l’étudiant lors de la soutenance', 1),
(4, 'Directeur de mémoire', 'Directeur scientifique du mémoire', 1),
(5, 'Maitre de stage', 'Supervise, guide et évalue le stagiaire, et assure la liaison avec l’établissement.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `route_actions`
--

DROP TABLE IF EXISTS `route_actions`;
CREATE TABLE IF NOT EXISTS `route_actions` (
  `id_route_action` int NOT NULL AUTO_INCREMENT,
  `route_pattern` varchar(255) NOT NULL,
  `http_method` enum('GET','POST','*') NOT NULL DEFAULT '*',
  `action_crud` enum('voir','creer','modifier','supprimer') NOT NULL,
  `description` text,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_route_action`),
  KEY `idx_route_pattern` (`route_pattern`),
  KEY `idx_actif` (`actif`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `route_actions`
--

INSERT INTO `route_actions` (`id_route_action`, `route_pattern`, `http_method`, `action_crud`, `description`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'page=parametres_generaux&action=gestion_attribution', 'GET', 'voir', 'Écran gestion des permissions', 1, '2026-01-24 17:58:27', NULL),
(2, 'page=parametres_generaux&action=gestion_attribution', 'POST', 'modifier', 'Enregistrer permissions', 1, '2026-01-24 17:58:27', NULL),
(3, 'page=gestion_rapports&action=creer_rapport', 'GET', 'creer', 'Formulaire création rapport', 1, '2026-01-24 17:58:27', NULL),
(4, 'page=gestion_rapports&action=creer_rapport', 'POST', 'creer', 'Création rapport', 1, '2026-01-24 17:58:27', NULL),
(5, 'page=sauvegarde_restauration', 'POST', 'modifier', 'Backup/restore (actions)', 1, '2026-01-24 17:58:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `salles`
--

DROP TABLE IF EXISTS `salles`;
CREATE TABLE IF NOT EXISTS `salles` (
  `id_salle` int NOT NULL AUTO_INCREMENT,
  `lib_salle` varchar(100) NOT NULL,
  PRIMARY KEY (`id_salle`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `salles`
--

INSERT INTO `salles` (`id_salle`, `lib_salle`) VALUES
(1, 'Amphi A'),
(2, 'Amphi Irma'),
(3, 'Prof. FEUTO Justin'),
(4, 'Prof. SOHOU Toussaint'),
(5, 'Prof. ADOU Kablan'),
(6, 'Prof. AKEKE Eric'),
(7, 'Prof. YODE Armel'),
(8, 'Prof. AMAN Auguste'),
(9, 'Prof. MONSAN Vincent'),
(10, 'Prof. KOUAKOU Mathias'),
(11, 'Prof. OKOU Hyppolite'),
(12, 'Prof. TOURE Ibrahima'),
(13, 'Dr. AKEKE Eric'),
(14, 'Prof. DOSSO Mouhamadou'),
(15, 'Prof. KOUA Brou'),
(16, 'Dr. KOUAKOU Mathias');

-- --------------------------------------------------------

--
-- Table structure for table `semestre`
--

DROP TABLE IF EXISTS `semestre`;
CREATE TABLE IF NOT EXISTS `semestre` (
  `id_semestre` int NOT NULL AUTO_INCREMENT,
  `lib_semestre` varchar(100) NOT NULL,
  `id_niv_etude` int NOT NULL,
  PRIMARY KEY (`id_semestre`),
  KEY `id_niv_etude` (`id_niv_etude`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `semestre`
--

INSERT INTO `semestre` (`id_semestre`, `lib_semestre`, `id_niv_etude`) VALUES
(20, 'Semestre 9', 10),
(24, 'Semestre 7', 17),
(25, 'Semestre 8', 17);

-- --------------------------------------------------------

--
-- Table structure for table `specialite`
--

DROP TABLE IF EXISTS `specialite`;
CREATE TABLE IF NOT EXISTS `specialite` (
  `id_specialite` int NOT NULL AUTO_INCREMENT,
  `lib_specialite` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_specialite`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `specialite`
--

INSERT INTO `specialite` (`id_specialite`, `lib_specialite`) VALUES
(2, 'Informatique'),
(3, 'Comptabilité'),
(5, 'Mathématique'),
(6, 'Réseaux'),
(7, 'Médecine'),
(8, 'Géoscience'),
(9, 'Physique'),
(10, 'Génie Électrique et Électronique'),
(11, 'Biologie'),
(12, 'Droit Public'),
(13, 'Langues Étrangères'),
(14, 'Management'),
(15, 'Finance'),
(16, 'Marketing');

-- --------------------------------------------------------

--
-- Table structure for table `statut_jury`
--

DROP TABLE IF EXISTS `statut_jury`;
CREATE TABLE IF NOT EXISTS `statut_jury` (
  `id_jury` int NOT NULL AUTO_INCREMENT,
  `lib_jury` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_jury`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `statut_jury`
--

INSERT INTO `statut_jury` (`id_jury`, `lib_jury`) VALUES
(6, 'accepter'),
(7, 'refuser');

-- --------------------------------------------------------

--
-- Table structure for table `traitement_legacy`
--

DROP TABLE IF EXISTS `traitement_legacy`;
CREATE TABLE IF NOT EXISTS `traitement_legacy` (
  `id_traitement` int NOT NULL AUTO_INCREMENT,
  `lib_traitement` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `label_traitement` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `icone_traitement` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `ordre_traitement` int NOT NULL,
  PRIMARY KEY (`id_traitement`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `traitement_legacy`
--

INSERT INTO `traitement_legacy` (`id_traitement`, `lib_traitement`, `label_traitement`, `icone_traitement`, `ordre_traitement`) VALUES
(5, 'dashboard', 'Tableau de bord', 'fa-home', 1),
(6, 'gestion_etudiants', 'Gestion des étudiants', 'fa-book', 2),
(7, 'gestion_utilisateurs', 'Gestion des utilisateurs', 'fa-user', 3),
(8, 'gestion_rh', 'Gestion des ressources humaines', 'fa-users', 2),
(9, 'piste_audit', 'Gestion de la piste', 'fa-history', 4),
(10, 'sauvegarde_restauration', 'Sauvegarde et restauration des données', 'fa-save', 5),
(11, 'parametres_generaux', 'Paramètres généraux', 'fa-gears', 6),
(12, 'candidature_soutenance', 'Candidater à la soutenance', 'fa-graduation-cap', 1),
(13, 'gestion_rapports', 'Gestion des rapports', 'fa-file', 2),
(15, 'notes_resultats', 'Notes & résultats', 'fa-note-sticky', 4),
(16, 'messagerie', 'Messagerie', 'fa-envelope', 5),
(17, 'profil_etudiant', 'Profil étudiant', 'fa-user', 6),
(19, 'profil', 'Profil', 'fa-user', 6),
(20, 'gestion_reclamations', 'Gestion des réclamations', 'fa-exclamation', 3),
(23, 'dashboard_scolarite', 'Tableau de bord scolarité', 'fa-home', 1),
(24, 'gestion_scolarite', 'Gestion de la scolarité', 'fa-money-bill', 3),
(25, 'gestion_candidatures_soutenance', 'Gestion des candidatures de soutenance', 'fa-folder', 4),
(26, 'gestion_notes_evaluations', 'Gestions des notes et évaluations', 'fa-note-sticky', 5),
(27, 'dashboard_enseignant', 'Tableau de bord enseignant', 'fa-home', 1),
(28, 'liste_etudiants_ens_simple', 'Liste des étudiants évalués', 'fa-users', 2),
(29, 'liste_etudiants_resp_filiere', 'Liste des étudiants MIAGE', 'fa-users', 2),
(30, 'liste_etudiants_resp_niveau', 'Liste des étudiants de mon niveau', 'fa-users', 2),
(31, 'verification_candidatures_soutenance', 'Vérification des candidatures de soutenance', 'fa-certificate', 1),
(32, 'gestion_dossiers_candidatures', 'Gestion des dossiers de candidature', 'fa-folder', 2),
(33, 'dashboard_secretaire', 'Tableau de bord secrétariat', 'fa-home', 1),
(34, 'dossiers_academiques', 'Dossiers académiques', 'fa-folder-open', 3),
(35, 'dashboard_commission', 'Tableau de bord de la commission', 'fa-home', 1),
(36, 'evaluations_dossiers_soutenance', 'Évaluation des dossiers de soutenance', 'fa-file-contract', 2),
(38, 'processus_validation', 'Processus de validation des dossiers', 'fa-list-check', 3),
(39, 'archives_dossiers_soutenance', 'Archives des rapports de soutenance', 'fa-inbox', 5),
(40, 'planification_reunion', 'Planification des réunions', 'fa-calendar-days', 6),
(41, 'gestion_reclamations_scolarite', 'Gestion des réclamations étudiantes ', 'fa-file', 4),
(42, 'redaction_compte_rendu', 'Rédaction du compte rendu', 'fa-file', 6),
(43, 'archive_comptes_rendus', 'Archive des comptes rendus', 'fa-book', 9),
(44, 'programation_soutenance', 'Programation de soutenance', 'fa-calendar', 13),
(45, 'plannificaiton_soutenance', 'plannification de soutenance', 'fa-calendar', 14),
(47, 'evaluation_soutenance', 'Evaluation soutenance', 'fa-note', 16),
(48, 'admin_historique', 'Historique et Archivage', 'fa-archive', 100);

-- --------------------------------------------------------

--
-- Table structure for table `type_utilisateur`
--

DROP TABLE IF EXISTS `type_utilisateur`;
CREATE TABLE IF NOT EXISTS `type_utilisateur` (
  `id_type_utilisateur` int NOT NULL AUTO_INCREMENT,
  `lib_type_utilisateur` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_type_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `type_utilisateur`
--

INSERT INTO `type_utilisateur` (`id_type_utilisateur`, `lib_type_utilisateur`) VALUES
(4, 'Personnel administratif'),
(5, 'Enseignant administratif'),
(6, 'Enseignant simple'),
(7, 'Etudiant');

-- --------------------------------------------------------

--
-- Table structure for table `ue`
--

DROP TABLE IF EXISTS `ue`;
CREATE TABLE IF NOT EXISTS `ue` (
  `id_ue` int NOT NULL AUTO_INCREMENT,
  `lib_ue` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_niveau_etude` int NOT NULL,
  `id_semestre` int NOT NULL,
  `id_annee_academique` int NOT NULL,
  `credit` int NOT NULL,
  `id_enseignant` int DEFAULT NULL,
  PRIMARY KEY (`id_ue`),
  KEY `id_annee_academique` (`id_annee_academique`),
  KEY `id_niveau_etude` (`id_niveau_etude`),
  KEY `id_semestre` (`id_semestre`),
  KEY `fk_enseignant_responsable` (`id_enseignant`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `ue`
--

INSERT INTO `ue` (`id_ue`, `lib_ue`, `id_niveau_etude`, `id_semestre`, `id_annee_academique`, `credit`, `id_enseignant`) VALUES
(95, 'Analyse et conception à objet', 10, 20, 22625, 5, 19),
(96, 'Visualisation des données', 10, 20, 22625, 3, 19),
(97, 'Management de projet et intégration d\'application', 10, 20, 22625, 4, 23),
(98, 'Audit informatique', 10, 20, 22625, 3, 19),
(99, 'Multimedia mobile', 10, 20, 22625, 3, 22),
(100, 'Ingenierie des exigences', 10, 20, 22625, 3, 23),
(101, 'Fouille de données statistiques', 10, 20, 22625, 3, 23),
(102, 'Intelligence Artificielle', 10, 20, 22625, 4, 19),
(103, 'Anglais', 10, 20, 22625, 2, 21);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id_utilisateur` int NOT NULL AUTO_INCREMENT,
  `nom_utilisateur` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_type_utilisateur` int NOT NULL,
  `id_GU` int NOT NULL,
  `id_niv_acces_donnee` int NOT NULL,
  `statut_utilisateur` enum('Actif','Inactif') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `login_utilisateur` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `mdp_utilisateur` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `login_utilisateur` (`login_utilisateur`),
  KEY `id_groupe_utilisateur` (`id_GU`),
  KEY `id_niv_acces_donnee` (`id_niv_acces_donnee`),
  KEY `id_type_utilisateur` (`id_type_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom_utilisateur`, `id_type_utilisateur`, `id_GU`, `id_niv_acces_donnee`, `statut_utilisateur`, `login_utilisateur`, `mdp_utilisateur`) VALUES
(5, 'Koua Brou', 5, 5, 5, 'Actif', 'kouabrou@gmail.com', '$2y$10$IM9LuGERPnqbR.DoqkQnMu.WBSXZJ5T5YtqBSFGO2X5nQF/xCnaFW'),
(98, 'Wah Medar', 5, 11, 5, 'Actif', 'wahmedar@gmail.com', '$2y$10$o19h3iUjmkyJ1.p7OXnExeIO/oticAlREufgPCuZ9jex3k5xNg/vi'),
(99, 'KAMENAN DURAND', 4, 8, 5, 'Actif', 'kamenandurand@gmail.com', '$2y$10$zccgQfpM82czZg.Mg1VhJuaoa5Gspi15W6/4UGAnv0qzcQ9x4aZDm'),
(100, 'Akandan Aho Paul', 7, 13, 5, 'Actif', 'ahopaul@gmail.com', '$2y$10$2Jg6K.W8EPchM2HOXWgSxO.z1GFwsTxxS2nTeoKUKdjtva1BqzoHS'),
(101, 'Irie Adjo Jemima', 7, 13, 5, 'Actif', 'iriejemima@gmail.com', '$2y$10$av1M4Ym41a.jvterPHEg1OIRx1KjfCiT0sSMZunoClBb95iWvDC/q'),
(102, 'Seri Christiane', 4, 7, 5, 'Actif', 'serichristiane@gmail.com', '$2y$10$gvMP07YTYLrLnBWEpC3PLeKXFrWz33Cfy7yYoNnfA9AOCvhMDpd/2'),
(103, 'Brou Patrice', 5, 11, 5, 'Actif', 'bpatrice@gmail.com', '$2y$10$tDob7dnjKgShm5HQulw6QuQoM3gQsrZ1zitc4Z97.7qFkYd28DPlG'),
(105, 'Foursov Michael', 6, 12, 5, 'Actif', 'michaelfoufou@gmail.com', '$2y$10$GLmI5ZJFlIvnqJ4pJhSLFOE/H5FyjSrjHStotwwok9MQA1.LWec4a'),
(106, 'Nindjin Malan', 6, 12, 5, 'Actif', 'nindjinmalan.0@gmail.com', '$2y$10$E3SNJ9zlFQ8wQ3x8lI1B7uTR5j1gO2TtFGQlRqVCjQgVhizwzOYUy'),
(108, 'Diarra prenom', 5, 11, 5, 'Actif', 'Diarraprenom@gmail.com', '$2y$10$d8ULmH4sGq3II1sPwWeMFOsJsy4JAXEYu9NR79SU7wr8NzEiNzSK2'),
(109, 'CYL KOFFI (Super Admin)', 5, 5, 5, 'Actif', 'cylkoffi@gmail.com', '$2y$10$QBOZL8AcwcNF6kzrPscqguIsmXq0vGK4X1Q4OHWCCx/2Z0vpbOpoC');

-- --------------------------------------------------------

--
-- Table structure for table `valider`
--

DROP TABLE IF EXISTS `valider`;
CREATE TABLE IF NOT EXISTS `valider` (
  `id_enseignant` int NOT NULL,
  `id_rapport` int NOT NULL,
  `date_validation` datetime NOT NULL,
  `commentaire_validation` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `decision_validation` enum('valider','rejeter') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'valider',
  PRIMARY KEY (`id_enseignant`,`id_rapport`),
  KEY `Key_valider_enseignant` (`id_enseignant`),
  KEY `Key_valider_rapport` (`id_rapport`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `valider`
--

INSERT INTO `valider` (`id_enseignant`, `id_rapport`, `date_validation`, `commentaire_validation`, `decision_validation`) VALUES
(7, 17, '2025-12-04 00:00:00', 'Validé', 'rejeter'),
(7, 160, '2023-02-02 00:00:00', '2023-10-20', 'valider'),
(7, 177, '2025-12-08 00:00:00', '2023-10-20', 'valider'),
(7, 179, '2024-01-26 00:00:00', '2024-05-29', 'valider'),
(18, 33, '2010-09-18 00:00:00', '', 'rejeter'),
(18, 58, '2012-01-26 00:00:00', '', 'rejeter'),
(18, 63, '2012-03-19 00:00:00', '', 'rejeter'),
(18, 68, '2012-06-20 00:00:00', '', 'rejeter'),
(18, 71, '2012-06-20 00:00:00', '', 'rejeter'),
(18, 78, '2012-09-19 00:00:00', '', 'rejeter'),
(18, 82, '2012-11-16 00:00:00', '', 'valider'),
(18, 89, '2013-02-19 00:00:00', '', 'valider'),
(18, 90, '2013-02-19 00:00:00', '', 'valider'),
(18, 104, '2013-07-20 00:00:00', '', 'rejeter'),
(18, 121, '2014-10-03 00:00:00', '', 'valider'),
(18, 124, '2016-06-03 00:00:00', '', 'rejeter'),
(18, 132, '2016-09-20 00:00:00', '', 'rejeter'),
(18, 133, '2016-09-20 00:00:00', '', 'valider'),
(18, 139, '2017-09-22 00:00:00', '', 'valider'),
(18, 140, '2017-09-22 00:00:00', '', 'rejeter'),
(18, 144, '2018-01-22 00:00:00', '', 'rejeter'),
(19, 16, '2025-09-29 23:20:50', 'nous somme impatient de vous voir a votre soutenance', 'valider'),
(19, 25, '2010-06-18 00:00:00', '', 'rejeter'),
(19, 27, '2010-06-18 00:00:00', 'Reduire doc', 'valider'),
(19, 29, '2010-09-18 00:00:00', '', 'valider'),
(19, 30, '2010-09-18 00:00:00', '', 'rejeter'),
(19, 34, '2010-11-19 00:00:00', '', 'valider'),
(19, 37, '2011-01-17 00:00:00', '', 'rejeter'),
(19, 39, '2011-01-17 00:00:00', '', 'valider'),
(19, 46, '2011-03-16 00:00:00', '', 'valider'),
(19, 49, '2011-07-07 00:00:00', '', 'rejeter'),
(19, 54, '2012-01-26 00:00:00', '', 'rejeter'),
(19, 55, '2012-01-26 00:00:00', '', 'rejeter'),
(19, 61, '2012-02-24 00:00:00', '', 'valider'),
(19, 62, '2012-03-19 00:00:00', '', 'rejeter'),
(19, 65, '2012-03-19 00:00:00', '', 'valider'),
(19, 66, '2012-03-19 00:00:00', '', 'rejeter'),
(19, 70, '2012-06-20 00:00:00', '', 'rejeter'),
(19, 72, '2012-07-23 00:00:00', '', 'valider'),
(19, 84, '2012-11-16 00:00:00', '', 'rejeter'),
(19, 85, '2012-12-20 00:00:00', '', 'valider'),
(19, 95, '2013-02-19 00:00:00', '', 'valider'),
(19, 98, '2013-05-20 00:00:00', '', 'rejeter'),
(19, 101, '2013-06-20 00:00:00', '', 'rejeter'),
(19, 103, '2013-07-20 00:00:00', '', 'rejeter'),
(19, 106, '2013-12-20 00:00:00', '', 'valider'),
(19, 113, '2014-06-19 00:00:00', '', 'valider'),
(19, 117, '2014-06-26 00:00:00', '', 'valider'),
(19, 120, '2014-10-03 00:00:00', '', 'rejeter'),
(19, 126, '2016-06-03 00:00:00', '', 'rejeter'),
(19, 131, '2016-09-20 00:00:00', '', 'valider'),
(19, 141, '2017-09-22 00:00:00', '', 'valider'),
(19, 145, '2018-01-22 00:00:00', '', 'valider'),
(19, 146, '2018-06-21 00:00:00', '', 'valider'),
(19, 152, '2018-08-10 00:00:00', '', 'valider'),
(19, 154, '2018-08-10 00:00:00', '', 'valider'),
(31, 26, '2010-06-18 00:00:00', '', 'rejeter'),
(31, 35, '2010-11-19 00:00:00', '', 'rejeter'),
(31, 38, '2011-01-17 00:00:00', '', 'valider'),
(31, 40, '2011-01-17 00:00:00', '', 'rejeter'),
(31, 52, '2011-11-18 00:00:00', '', 'rejeter'),
(31, 73, '2012-07-23 00:00:00', '', 'valider'),
(32, 28, '2010-09-13 00:00:00', '', 'rejeter'),
(32, 36, '2011-01-17 00:00:00', '', 'rejeter'),
(32, 47, '2011-03-16 00:00:00', '', 'rejeter'),
(32, 48, '2011-07-07 00:00:00', '', 'valider'),
(32, 50, '2011-08-15 00:00:00', '', 'rejeter'),
(32, 57, '2012-01-26 00:00:00', '', 'valider'),
(32, 64, '2012-03-19 00:00:00', '', 'valider'),
(32, 69, '2012-06-20 00:00:00', '', 'rejeter'),
(32, 74, '2012-07-23 00:00:00', '', 'rejeter'),
(32, 75, '2012-08-21 00:00:00', '', 'rejeter'),
(32, 81, '2012-10-18 00:00:00', '', 'rejeter'),
(32, 86, '2012-12-20 00:00:00', '', 'valider'),
(32, 94, '2013-02-19 00:00:00', '', 'valider'),
(32, 97, '2013-03-22 00:00:00', '', 'valider'),
(32, 102, '2013-07-20 00:00:00', '', 'rejeter'),
(32, 115, '2014-06-26 00:00:00', '', 'rejeter'),
(32, 119, '2014-10-03 00:00:00', '', 'rejeter'),
(32, 123, '2016-06-03 00:00:00', '', 'rejeter'),
(32, 128, '2016-06-03 00:00:00', '', 'rejeter'),
(32, 130, '2016-09-20 00:00:00', '', 'valider'),
(32, 136, '2017-06-23 00:00:00', '', 'rejeter'),
(32, 137, '2017-07-17 00:00:00', '', 'valider'),
(32, 151, '2018-08-10 00:00:00', '', 'valider'),
(33, 31, '2010-09-18 00:00:00', '', 'rejeter'),
(33, 32, '2010-09-18 00:00:00', '', 'rejeter'),
(33, 41, '2011-02-15 00:00:00', '', 'rejeter'),
(33, 42, '2011-02-15 00:00:00', '', 'rejeter'),
(33, 43, '2011-03-16 00:00:00', '', 'valider'),
(33, 44, '2011-03-16 00:00:00', '', 'rejeter'),
(33, 45, '2011-03-16 00:00:00', '', 'rejeter'),
(33, 51, '2011-08-15 00:00:00', '', 'rejeter'),
(33, 53, '2011-12-30 00:00:00', '', 'rejeter'),
(33, 56, '2012-01-26 00:00:00', '', 'rejeter'),
(33, 67, '2012-04-24 00:00:00', '', 'rejeter'),
(33, 76, '2012-08-21 00:00:00', '', 'rejeter'),
(33, 77, '2012-08-21 00:00:00', '', 'rejeter'),
(33, 87, '2013-01-18 00:00:00', '', 'rejeter'),
(33, 88, '2013-01-18 00:00:00', '', 'rejeter'),
(33, 91, '2013-02-19 00:00:00', '', 'rejeter'),
(33, 93, '2013-02-19 00:00:00', '', 'rejeter'),
(33, 99, '2013-06-20 00:00:00', '', 'rejeter'),
(33, 100, '2013-06-20 00:00:00', '', 'rejeter'),
(33, 105, '2013-09-17 00:00:00', '', 'rejeter'),
(33, 107, '2014-01-17 00:00:00', '', 'rejeter'),
(33, 118, '2014-10-03 00:00:00', '', 'rejeter'),
(33, 125, '2016-06-03 00:00:00', '', 'rejeter'),
(33, 143, '2018-01-22 00:00:00', '', 'rejeter'),
(34, 59, '2012-02-24 00:00:00', '', 'rejeter'),
(34, 114, '2014-06-26 00:00:00', '', 'valider'),
(35, 60, '2012-02-24 00:00:00', '', 'rejeter'),
(35, 80, '2012-10-18 00:00:00', '', 'rejeter'),
(36, 79, '2012-10-18 00:00:00', '', 'rejeter'),
(36, 96, '2013-03-22 00:00:00', '', 'rejeter'),
(37, 83, '2012-11-16 00:00:00', '', 'valider'),
(38, 92, '2013-02-19 00:00:00', '', 'valider'),
(39, 108, '2014-02-27 00:00:00', '', 'valider'),
(39, 109, '2014-06-19 00:00:00', '', 'valider'),
(39, 111, '2014-06-19 00:00:00', '', 'rejeter'),
(39, 112, '2014-06-19 00:00:00', '', 'rejeter'),
(39, 122, '2014-10-03 00:00:00', '', 'valider'),
(39, 127, '2016-06-03 00:00:00', '', 'rejeter'),
(39, 129, '2016-06-03 00:00:00', '', 'rejeter'),
(39, 134, '2016-09-20 00:00:00', '', 'valider'),
(39, 135, '2017-06-23 00:00:00', '', 'valider'),
(39, 138, '2017-07-17 00:00:00', '', 'rejeter'),
(39, 142, '2017-09-22 00:00:00', '', 'rejeter'),
(39, 204, '2025-12-08 00:00:00', '2025-05-21', 'valider'),
(39, 206, '2025-12-08 00:00:00', '2025-05-22', 'valider'),
(40, 110, '2014-06-19 00:00:00', '', 'valider'),
(41, 116, '2014-06-26 00:00:00', '', 'rejeter'),
(42, 148, '2018-06-21 00:00:00', '', 'valider'),
(43, 150, '2018-08-10 00:00:00', '', 'valider'),
(43, 155, '2023-02-02 00:00:00', '', 'valider'),
(43, 161, '2023-03-30 00:00:00', '2023-10-19', 'valider'),
(43, 165, '2025-12-08 00:00:00', '2022-10-27', 'valider'),
(43, 166, '2025-12-08 00:00:00', '2022-10-27', 'valider'),
(43, 170, '2025-12-08 00:00:00', '2023-10-18', 'valider'),
(43, 181, '2024-01-26 00:00:00', '', 'valider'),
(43, 192, '2024-06-26 00:00:00', '', 'valider'),
(43, 202, '2024-12-27 00:00:00', '2025-05-21', 'valider'),
(44, 157, '2023-02-02 00:00:00', '', 'valider'),
(44, 163, '2023-03-30 00:00:00', '2023-10-20', 'valider'),
(44, 168, '2025-12-08 00:00:00', '2022-10-28', 'valider'),
(44, 169, '2025-12-08 00:00:00', '2022-10-28', 'valider'),
(44, 171, '2025-12-08 00:00:00', '2023-10-18', 'valider'),
(46, 153, '2018-08-10 00:00:00', '', 'valider'),
(47, 156, '2023-02-02 00:00:00', '', 'valider'),
(47, 162, '2023-03-30 00:00:00', '2024-05-29', 'valider'),
(47, 167, '2025-12-08 00:00:00', '2022-10-28', 'valider'),
(47, 172, '2025-12-08 00:00:00', '2023-10-18', 'valider'),
(47, 173, '2025-12-08 00:00:00', '2023-10-18', 'valider'),
(47, 175, '2025-12-08 00:00:00', '2023-10-19', 'valider'),
(47, 180, '2024-01-26 00:00:00', '2024-05-30', 'valider'),
(47, 189, '2025-12-08 00:00:00', '2024-05-30', 'valider'),
(47, 190, '2024-06-26 00:00:00', '', 'valider'),
(47, 195, '2024-06-26 00:00:00', '', 'valider'),
(47, 200, '2024-12-27 00:00:00', '2025-05-22', 'valider'),
(48, 158, '2023-02-02 00:00:00', '2023-06-13', 'valider'),
(48, 174, '2025-12-08 00:00:00', '2023-10-19', 'valider'),
(48, 178, '2024-01-26 00:00:00', '2024-05-29', 'valider'),
(48, 194, '2024-06-26 00:00:00', '', 'valider'),
(50, 164, '2025-12-08 00:00:00', '2023-06-13', 'valider'),
(50, 187, '2025-12-08 00:00:00', '2024-05-29', 'valider'),
(50, 193, '2024-06-26 00:00:00', '', 'valider'),
(51, 176, '2025-12-08 00:00:00', '2023-10-19', 'valider'),
(51, 182, '2024-01-26 00:00:00', '2024-05-30', 'valider'),
(51, 183, '2024-04-23 00:00:00', '', 'valider'),
(51, 186, '2024-04-23 00:00:00', '2025-05-21', 'valider'),
(51, 188, '2025-12-08 00:00:00', '2024-05-30', 'valider'),
(51, 191, '2024-06-26 00:00:00', '', 'valider'),
(51, 196, '2024-06-26 00:00:00', '', 'valider'),
(51, 201, '2024-12-27 00:00:00', '2025-05-21', 'valider'),
(52, 199, '2024-12-27 00:00:00', '2025-05-21', 'valider'),
(52, 205, '2025-12-08 00:00:00', '2025-05-21', 'valider');

-- --------------------------------------------------------

--
-- Table structure for table `versements`
--

DROP TABLE IF EXISTS `versements`;
CREATE TABLE IF NOT EXISTS `versements` (
  `id_versement` int NOT NULL AUTO_INCREMENT,
  `id_inscription` int DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT NULL,
  `date_versement` datetime DEFAULT CURRENT_TIMESTAMP,
  `type_versement` enum('Premier versement','Tranche') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `methode_paiement` enum('Espèce','Carte bancaire','Virement','Chèque') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`id_versement`),
  KEY `id_inscription` (`id_inscription`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `versements`
--

INSERT INTO `versements` (`id_versement`, `id_inscription`, `montant`, `date_versement`, `type_versement`, `methode_paiement`) VALUES
(67, 34, 560000.00, '2025-09-28 21:39:12', 'Premier versement', 'Espèce'),
(68, 34, 200000.00, '2025-09-28 22:59:56', 'Tranche', 'Chèque'),
(69, 34, 100000.00, '2025-09-29 14:12:15', 'Tranche', 'Espèce'),
(70, 34, 120000.00, '2025-09-29 19:04:01', 'Tranche', 'Espèce'),
(71, 35, 560000.00, '2025-09-29 22:06:37', 'Premier versement', 'Chèque'),
(72, 35, 420000.00, '2025-10-15 19:18:43', 'Tranche', 'Espèce'),
(73, 35, 45000.00, '2025-10-15 19:24:34', 'Tranche', 'Espèce');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `affecter`
--
ALTER TABLE `affecter`
  ADD CONSTRAINT `fk_affecter_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_affecter_jury` FOREIGN KEY (`id_jury`) REFERENCES `statut_jury` (`id_jury`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_affecter_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `approuver`
--
ALTER TABLE `approuver`
  ADD CONSTRAINT `fk_approuver_niveau` FOREIGN KEY (`id_approb`) REFERENCES `niveau_approbation` (`id_approb`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_approuver_pers_admin` FOREIGN KEY (`id_pers_admin`) REFERENCES `personnel_admin` (`id_pers_admin`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_approuver_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `avoir`
--
ALTER TABLE `avoir`
  ADD CONSTRAINT `fk_avoir_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_avoir_grade` FOREIGN KEY (`id_grade`) REFERENCES `grade` (`id_grade`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `candidature_soutenance`
--
ALTER TABLE `candidature_soutenance`
  ADD CONSTRAINT `candidature_soutenance_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`),
  ADD CONSTRAINT `candidature_soutenance_ibfk_2` FOREIGN KEY (`id_pers_admin`) REFERENCES `personnel_admin` (`id_pers_admin`);

--
-- Constraints for table `composer_jury`
--
ALTER TABLE `composer_jury`
  ADD CONSTRAINT `fk_composer_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_composer_role` FOREIGN KEY (`id_qualite_jury`) REFERENCES `roles_jury` (`id_role_jury`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `compte_rendu`
--
ALTER TABLE `compte_rendu`
  ADD CONSTRAINT `fk_etudiant` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `compte_rendu_rapport`
--
ALTER TABLE `compte_rendu_rapport`
  ADD CONSTRAINT `compte_rendu_rapport_ibfk_1` FOREIGN KEY (`id_CR`) REFERENCES `compte_rendu` (`id_CR`) ON DELETE CASCADE,
  ADD CONSTRAINT `compte_rendu_rapport_ibfk_2` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE;

--
-- Constraints for table `correspondre`
--
ALTER TABLE `correspondre`
  ADD CONSTRAINT `correspondre_ibfk_1` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `correspondre_ibfk_2` FOREIGN KEY (`id_critere`) REFERENCES `critere_evaluation` (`id_critere`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `deposer`
--
ALTER TABLE `deposer`
  ADD CONSTRAINT `fk_deposer_etudiant` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_deposer_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `echeances`
--
ALTER TABLE `echeances`
  ADD CONSTRAINT `echeances_ibfk_1` FOREIGN KEY (`id_inscription`) REFERENCES `inscriptions` (`id_inscription`);

--
-- Constraints for table `ecue`
--
ALTER TABLE `ecue`
  ADD CONSTRAINT `fk_ecue_ue` FOREIGN KEY (`id_ue`) REFERENCES `ue` (`id_ue`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enseignant_responsable` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `enseignants`
--
ALTER TABLE `enseignants`
  ADD CONSTRAINT `fk_enseignants_specialite` FOREIGN KEY (`id_specialite`) REFERENCES `specialite` (`id_specialite`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `evaluations_rapports`
--
ALTER TABLE `evaluations_rapports`
  ADD CONSTRAINT `evaluations_rapports_ibfk_1` FOREIGN KEY (`id_evaluateur`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `evaluations_rapports_ibfk_2` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `evaluer`
--
ALTER TABLE `evaluer`
  ADD CONSTRAINT `evaluer_ibfk_1` FOREIGN KEY (`id_critere`) REFERENCES `critere_evaluation` (`id_critere`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `evaluer_ibfk_2` FOREIGN KEY (`num_etudiant`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `groupe_utilisateur`
--
ALTER TABLE `groupe_utilisateur`
  ADD CONSTRAINT `fk_groupe_utilisateur_type` FOREIGN KEY (`id_type_utilisateur`) REFERENCES `type_utilisateur` (`id_type_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `informations_stage`
--
ALTER TABLE `informations_stage`
  ADD CONSTRAINT `informations_stage_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `informations_stage_ibfk_2` FOREIGN KEY (`id_entreprise`) REFERENCES `entreprises` (`id_entreprise`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inscriptions`
--
ALTER TABLE `inscriptions`
  ADD CONSTRAINT `inscriptions_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`num_etu`),
  ADD CONSTRAINT `inscriptions_ibfk_2` FOREIGN KEY (`id_niveau`) REFERENCES `niveau_etude` (`id_niv_etude`),
  ADD CONSTRAINT `inscriptions_ibfk_3` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `niveau_etude`
--
ALTER TABLE `niveau_etude`
  ADD CONSTRAINT `fk_niveau_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`id_ue`) REFERENCES `ue` (`id_ue`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`id_ecue`) REFERENCES `ecue` (`id_ecue`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `occuper`
--
ALTER TABLE `occuper`
  ADD CONSTRAINT `fk_occuper_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_occuper_fonction` FOREIGN KEY (`id_fonction`) REFERENCES `fonction` (`id_fonction`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`id_GU`) REFERENCES `groupe_utilisateur` (`id_GU`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `permissions_ibfk_2` FOREIGN KEY (`id_fonctionnalite`) REFERENCES `fonctionnalites` (`id_fonctionnalite`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pister`
--
ALTER TABLE `pister`
  ADD CONSTRAINT `fk_pister_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `programmer`
--
ALTER TABLE `programmer`
  ADD CONSTRAINT `programmer_ibfk_1` FOREIGN KEY (`num_etud`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rapport_etudiants`
--
ALTER TABLE `rapport_etudiants`
  ADD CONSTRAINT `ibfk_etudiant` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rattacher_legacy`
--
ALTER TABLE `rattacher_legacy`
  ADD CONSTRAINT `fk_rattacher_gu` FOREIGN KEY (`id_GU`) REFERENCES `groupe_utilisateur` (`id_GU`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rattacher_traitement` FOREIGN KEY (`id_traitement`) REFERENCES `traitement_legacy` (`id_traitement`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reclamations`
--
ALTER TABLE `reclamations`
  ADD CONSTRAINT `fk_admin_assigne` FOREIGN KEY (`id_pers_admin`) REFERENCES `personnel_admin` (`id_pers_admin`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rendre`
--
ALTER TABLE `rendre`
  ADD CONSTRAINT `fk_rendre_cr` FOREIGN KEY (`id_CR`) REFERENCES `compte_rendu` (`id_CR`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rendre_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `resume_candidature`
--
ALTER TABLE `resume_candidature`
  ADD CONSTRAINT `resume_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `resume_ibfk_2` FOREIGN KEY (`id_candidature`) REFERENCES `candidature_soutenance` (`id_candidature`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `semestre`
--
ALTER TABLE `semestre`
  ADD CONSTRAINT `fk_niveau_etude` FOREIGN KEY (`id_niv_etude`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ue`
--
ALTER TABLE `ue`
  ADD CONSTRAINT `ue_ibfk_1` FOREIGN KEY (`id_annee_academique`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ue_ibfk_2` FOREIGN KEY (`id_niveau_etude`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ue_ibfk_3` FOREIGN KEY (`id_semestre`) REFERENCES `semestre` (`id_semestre`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ue_ibfk_4` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD CONSTRAINT `utilisateur_ibfk_2` FOREIGN KEY (`id_GU`) REFERENCES `groupe_utilisateur` (`id_GU`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `utilisateur_ibfk_3` FOREIGN KEY (`id_niv_acces_donnee`) REFERENCES `niveau_acces_donnees` (`id_niveau_acces_donnees`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `utilisateur_ibfk_4` FOREIGN KEY (`id_type_utilisateur`) REFERENCES `type_utilisateur` (`id_type_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `valider`
--
ALTER TABLE `valider`
  ADD CONSTRAINT `fk_valider_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_valider_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `versements`
--
ALTER TABLE `versements`
  ADD CONSTRAINT `versements_ibfk_1` FOREIGN KEY (`id_inscription`) REFERENCES `inscriptions` (`id_inscription`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
