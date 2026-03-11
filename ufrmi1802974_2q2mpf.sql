-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 10 mars 2026 à 22:59
-- Version du serveur : 8.3.0
-- Version de PHP : 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;

--
-- Base de données : `ufrmi1802974_2q2mpf`
--

DELIMITER $$
--
-- Procédures
--
DROP PROCEDURE IF EXISTS `add_user_fk` $$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_user_fk` ()   BEGIN
    DECLARE fk1 INT DEFAULT 0;
    DECLARE fk2 INT DEFAULT 0;
    DECLARE fk3 INT DEFAULT 0;
    
    -- Vérifier si les FK existent
    SELECT COUNT(*) INTO fk1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND CONSTRAINT_NAME = 'fk_utilisateur_etudiant';
    
    SELECT COUNT(*) INTO fk2 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND CONSTRAINT_NAME = 'fk_utilisateur_enseignant';
    
    SELECT COUNT(*) INTO fk3 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND CONSTRAINT_NAME = 'fk_utilisateur_pers_admin';
    
    IF fk1 = 0 THEN
        ALTER TABLE utilisateur ADD CONSTRAINT fk_utilisateur_etudiant
            FOREIGN KEY (num_etu) REFERENCES etudiants(num_etu) 
            ON DELETE SET NULL ON UPDATE CASCADE;
    END IF;
    
    IF fk2 = 0 THEN
        ALTER TABLE utilisateur ADD CONSTRAINT fk_utilisateur_enseignant
            FOREIGN KEY (matricule_ens) REFERENCES enseignants(matricule_ens) 
            ON DELETE SET NULL ON UPDATE CASCADE;
    END IF;
    
    IF fk3 = 0 THEN
        ALTER TABLE utilisateur ADD CONSTRAINT fk_utilisateur_pers_admin
            FOREIGN KEY (matricule_admin) REFERENCES personnel_admin(matricule_admin) 
            ON DELETE SET NULL ON UPDATE CASCADE;
    END IF;
END$$

DELIMITER;

-- --------------------------------------------------------

--
-- Structure de la table `action`
--

DROP TABLE IF EXISTS `action`;

CREATE TABLE IF NOT EXISTS `action` (
    `id_action` int NOT NULL AUTO_INCREMENT,
    `lib_action` varchar(120) NOT NULL,
    PRIMARY KEY (`id_action`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `affecter`
--

DROP TABLE IF EXISTS `affecter`;

CREATE TABLE IF NOT EXISTS `affecter` (
    `id_enseignant` varchar(20) NOT NULL,
    `role` enum('encadrant', 'directeur') NOT NULL,
    `id_rapport` int NOT NULL,
    `id_jury` int DEFAULT NULL,
    PRIMARY KEY (`id_enseignant`, `id_rapport`),
    KEY `Key_affecter_enseignant` (`id_enseignant`),
    KEY `Key_affecter_rappetu` (`id_rapport`),
    KEY `Key_affecter_jury` (`id_jury`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `annee_academique`
--

DROP TABLE IF EXISTS `annee_academique`;

CREATE TABLE IF NOT EXISTS `annee_academique` (
    `id_annee_acad` int NOT NULL,
    `date_deb` date NOT NULL,
    `date_fin` date NOT NULL,
    PRIMARY KEY (`id_annee_acad`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `annee_academique`
--

INSERT INTO
    `annee_academique` (
        `id_annee_acad`,
        `date_deb`,
        `date_fin`
    )
VALUES (
        20100,
        '2000-09-01',
        '2001-08-31'
    ),
    (
        20201,
        '2001-09-01',
        '2002-07-31'
    ),
    (
        20302,
        '2002-09-01',
        '2003-07-31'
    ),
    (
        20403,
        '2003-09-01',
        '2004-07-31'
    ),
    (
        20504,
        '2004-09-01',
        '2005-07-31'
    ),
    (
        20605,
        '2005-09-01',
        '2006-07-31'
    ),
    (
        20706,
        '2006-09-01',
        '2007-07-31'
    ),
    (
        20807,
        '2007-09-01',
        '2008-07-31'
    ),
    (
        20908,
        '2008-09-01',
        '2009-07-31'
    ),
    (
        21009,
        '2009-09-01',
        '2010-07-31'
    ),
    (
        21110,
        '2010-09-01',
        '2011-07-31'
    ),
    (
        21211,
        '2011-09-01',
        '2012-07-31'
    ),
    (
        21312,
        '2012-09-01',
        '2013-07-31'
    ),
    (
        21413,
        '2013-09-01',
        '2014-07-31'
    ),
    (
        21514,
        '2014-09-01',
        '2015-07-31'
    ),
    (
        21615,
        '2015-09-01',
        '2016-07-31'
    ),
    (
        21716,
        '2016-09-01',
        '2017-07-31'
    ),
    (
        21817,
        '2017-09-01',
        '2018-07-31'
    ),
    (
        21918,
        '2018-09-01',
        '2019-07-31'
    ),
    (
        22019,
        '2019-09-01',
        '2020-07-31'
    ),
    (
        22120,
        '2020-09-01',
        '2021-07-31'
    ),
    (
        22322,
        '2022-09-01',
        '2023-07-31'
    ),
    (
        22423,
        '2023-09-01',
        '2024-07-31'
    ),
    (
        22524,
        '2024-09-01',
        '2025-07-31'
    ),
    (
        22625,
        '2025-09-01',
        '2026-07-31'
    );

-- --------------------------------------------------------

--
-- Structure de la table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;

CREATE TABLE IF NOT EXISTS `app_settings` (
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text NOT NULL,
    `is_sensitive` tinyint(1) NOT NULL DEFAULT '0',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `app_settings`
--

INSERT INTO
    `app_settings` (
        `setting_key`,
        `setting_value`,
        `is_sensitive`,
        `updated_at`
    )
VALUES (
        'smtp_password',
        'loprluktxassyeqp',
        0,
        '2026-02-12 16:53:51'
    );

-- --------------------------------------------------------

--
-- Structure de la table `auth_rate_limits`
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
    UNIQUE KEY `uniq_action_ip_identifier` (`action`, `ip`, `identifier`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `avoir`
--

DROP TABLE IF EXISTS `avoir`;

CREATE TABLE IF NOT EXISTS `avoir` (
    `id_grade` varchar(2) NOT NULL,
    `id_enseignant` varchar(20) NOT NULL,
    `date_grade` date NOT NULL,
    PRIMARY KEY (`id_grade`, `id_enseignant`),
    KEY `Key_avoir_grade` (`id_grade`),
    KEY `Key_avoir_enseignant` (`id_enseignant`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `avoir`
--

INSERT INTO
    `avoir` (
        `id_grade`,
        `id_enseignant`,
        `date_grade`
    )
VALUES (
        'AS',
        '2022 513 NR',
        '0000-00-00'
    ),
    (
        'AS',
        '239 382 G',
        '0000-00-00'
    ),
    (
        'AS',
        '910 800 A',
        '0000-00-00'
    ),
    (
        'MA',
        '210 001 NR',
        '0000-00-00'
    ),
    (
        'MA',
        '241 053 B',
        '0000-00-00'
    ),
    (
        'MA',
        '265 055 D',
        '0000-00-00'
    ),
    (
        'MA',
        '265 877 A',
        '0000-00-00'
    ),
    (
        'MA',
        '389 845 F',
        '0000-00-00'
    ),
    (
        'MA',
        '398 026 W',
        '0000-00-00'
    ),
    (
        'MA',
        '502 460 B',
        '0000-00-00'
    ),
    (
        'MC',
        '233 324 X',
        '0001-01-23'
    ),
    (
        'MC',
        '234 514 P',
        '0000-00-00'
    ),
    (
        'MC',
        '253 567 F',
        '0000-00-00'
    ),
    (
        'MC',
        '255 998 A',
        '0000-00-00'
    ),
    (
        'MC',
        '283 496 Q',
        '0000-00-00'
    ),
    (
        'MC',
        '320 596 U',
        '0000-00-00'
    ),
    (
        'MC',
        '332 005 X',
        '0000-00-00'
    ),
    (
        'PT',
        '131 438 L',
        '0000-00-00'
    ),
    (
        'PT',
        '149 070 L',
        '0000-00-00'
    ),
    (
        'PT',
        '233 497 N',
        '0000-00-00'
    ),
    (
        'PT',
        '239 514 U',
        '0000-00-00'
    ),
    (
        'PT',
        '242 840 J',
        '0000-00-00'
    ),
    (
        'PT',
        '244 478 M',
        '0000-00-00'
    ),
    (
        'PT',
        '252 975 F',
        '0000-00-00'
    ),
    (
        'PT',
        '253 561 H',
        '0000-00-00'
    ),
    (
        'PT',
        '255 664 J',
        '0000-00-00'
    ),
    (
        'PT',
        '255 997 Z',
        '0000-00-00'
    ),
    (
        'PT',
        '285 394 T',
        '0000-00-00'
    ),
    (
        'PT',
        '309 103 Q',
        '0000-00-00'
    ),
    (
        'PT',
        '344 438 H',
        '0000-00-00'
    ),
    (
        'PT',
        '344 444 F',
        '0000-00-00'
    ),
    (
        'PT',
        '500 076 B',
        '0000-00-00'
    ),
    (
        'PT',
        '500 337 D',
        '0000-00-00'
    );

-- --------------------------------------------------------

--
-- Structure de la table `bareme_critere`
--

DROP TABLE IF EXISTS `bareme_critere`;

CREATE TABLE IF NOT EXISTS `bareme_critere` (
    `id_annee_acad` int NOT NULL,
    `id_critere` varchar(2) NOT NULL,
    `bareme` int NOT NULL,
    PRIMARY KEY (`id_annee_acad`, `id_critere`),
    KEY `id_critere` (`id_critere`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `bareme_critere`
--

INSERT INTO
    `bareme_critere` (
        `id_annee_acad`,
        `id_critere`,
        `bareme`
    )
VALUES (22524, 'CM', 4),
    (22524, 'EX', 4),
    (22524, 'PM', 2),
    (22524, 'RP', 5),
    (22524, 'RQ', 5);

-- --------------------------------------------------------

--
-- Structure de la table `candidature_soutenance`
--

DROP TABLE IF EXISTS `candidature_soutenance`;

CREATE TABLE IF NOT EXISTS `candidature_soutenance` (
    `id_candidature` int NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `date_candidature` datetime NOT NULL,
    `statut_candidature` enum(
        'En attente',
        'Validée',
        'Rejetée'
    ) NOT NULL DEFAULT 'En attente',
    `date_traitement` datetime DEFAULT NULL,
    `id_pers_admin` int DEFAULT NULL,
    `commentaire_admin` text,
    PRIMARY KEY (`id_candidature`),
    KEY `num_etu` (`num_etu`),
    KEY `id_pers_admin` (`id_pers_admin`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `categories_fonctionnalites`
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
) ENGINE = InnoDB AUTO_INCREMENT = 26 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `categories_fonctionnalites`
--

INSERT INTO
    `categories_fonctionnalites` (
        `id_categorie`,
        `code_categorie`,
        `lib_categorie`,
        `description_categorie`,
        `icone_categorie`,
        `ordre_categorie`,
        `actif`,
        `date_creation`
    )
VALUES (
        13,
        'SCOLARITE',
        'Gestion de la scolarité',
        'Scolarité, inscriptions, notes, réclamations scolarité',
        'fas fa-school',
        1,
        1,
        '2026-01-24 21:20:25'
    ),
    (
        14,
        'ETUDIANT_ENV',
        'Environnement Étudiant',
        'Espace étudiant: candidature, rapports, résultats, réclamations',
        'fas fa-user-graduate',
        2,
        1,
        '2026-01-24 21:20:25'
    ),
    (
        15,
        'COMMISSION',
        'Commission validation',
        'Jury/commission: validation, soutenances, comptes-rendus',
        'fas fa-check-double',
        3,
        1,
        '2026-01-24 21:20:25'
    ),
    (
        16,
        'ADMIN_PLATEFORME',
        'Administration plateforme',
        'Administration, paramètres, utilisateurs, audit, sauvegardes',
        'fas fa-tools',
        6,
        1,
        '2026-01-24 21:20:25'
    ),
    (
        17,
        'SOUTENANCE',
        'Soutenance',
        'ce menu fais reference au soutenance',
        'fa-solid fa-user-graduate',
        4,
        1,
        '2026-02-05 23:35:33'
    ),
    (
        24,
        'PROFIL',
        'Profil utilisateur',
        'ce menu fais reference au profil de l\'utilisateur',
        'fa-solid fa-circle-user',
        6,
        1,
        '2026-02-11 15:04:46'
    ),
    (
        25,
        'ENV_ENSEIGNANT',
        'Espace Enseignant',
        'Ce menu fait reference à l\'environnement enseignant',
        'fa-solid fa-person-chalkboard',
        5,
        1,
        '2026-02-20 22:42:53'
    );

-- --------------------------------------------------------

--
-- Structure de la table `compte_rendu`
--

DROP TABLE IF EXISTS `compte_rendu`;

CREATE TABLE IF NOT EXISTS `compte_rendu` (
    `id_CR` int NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `nom_CR` varchar(70) NOT NULL,
    `contenu_CR` longtext,
    `chemin_fichier_pdf` varchar(255) DEFAULT NULL,
    `date_CR` datetime NOT NULL,
    PRIMARY KEY (`id_CR`),
    KEY `fk_etudiant` (`num_etu`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `compte_rendu_rapport`
--

DROP TABLE IF EXISTS `compte_rendu_rapport`;

CREATE TABLE IF NOT EXISTS `compte_rendu_rapport` (
    `id_CR` int NOT NULL,
    `id_rapport` int NOT NULL,
    PRIMARY KEY (`id_CR`, `id_rapport`),
    KEY `id_rapport` (`id_rapport`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `critere_evaluation`
--

DROP TABLE IF EXISTS `critere_evaluation`;

CREATE TABLE IF NOT EXISTS `critere_evaluation` (
    `id_critere` varchar(2) NOT NULL,
    `lib_critere` varchar(100) NOT NULL,
    PRIMARY KEY (`id_critere`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `critere_evaluation`
--

INSERT INTO
    `critere_evaluation` (`id_critere`, `lib_critere`)
VALUES ('CM', 'Contenu du mémoire'),
    ('EX', 'Exposé'),
    (
        'PM',
        'Présentation du mémoire'
    ),
    (
        'RP',
        'Résolution du problème'
    ),
    (
        'RQ',
        'Réponses aux questions posées'
    );

-- --------------------------------------------------------

--
-- Structure de la table `decisions_jury`
--

DROP TABLE IF EXISTS `decisions_jury`;

CREATE TABLE IF NOT EXISTS `decisions_jury` (
    `id_decision` int NOT NULL AUTO_INCREMENT,
    `lib_decision` varchar(120) NOT NULL,
    `description` text,
    `actif` tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id_decision`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `deposer`
--

DROP TABLE IF EXISTS `deposer`;

CREATE TABLE IF NOT EXISTS `deposer` (
    `num_etu` varchar(25) NOT NULL,
    `id_rapport` int NOT NULL,
    `date_depot` datetime NOT NULL,
    PRIMARY KEY (`num_etu`, `id_rapport`),
    KEY `fk_deposer_rapport` (`id_rapport`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

DROP TABLE IF EXISTS `document`;

CREATE TABLE IF NOT EXISTS `document` (
    `id_document` int NOT NULL AUTO_INCREMENT,
    `lien_document` varchar(255) NOT NULL,
    PRIMARY KEY (`id_document`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `domaine`
--

DROP TABLE IF EXISTS `domaine`;

CREATE TABLE IF NOT EXISTS `domaine` (
    `id_domaine` int NOT NULL,
    `lib_domaine` varchar(150) NOT NULL,
    PRIMARY KEY (`id_domaine`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `domaine`
--

INSERT INTO
    `domaine` (`id_domaine`, `lib_domaine`)
VALUES (
        1,
        'Système d\'information, Bases de données, Développement'
    ),
    (
        2,
        'Système d\'information, Bases de données, Développement ERP'
    ),
    (
        3,
        'Système d\'information, Bases de données, Développement WEB'
    ),
    (
        4,
        'Base de données, Génie Logiciel'
    ),
    (
        5,
        'Audit, Management des systèmes d\'information'
    ),
    (
        6,
        'Analyse de données, business intelligence'
    );

-- --------------------------------------------------------

--
-- Structure de la table `enseignants`
--

DROP TABLE IF EXISTS `enseignants`;

CREATE TABLE IF NOT EXISTS `enseignants` (
    `id_enseignant` varchar(20) NOT NULL,
    `nom_enseignant` varchar(50) NOT NULL,
    `prenom_enseignant` varchar(100) NOT NULL,
    `tel_enseignant` varchar(20) DEFAULT NULL,
    `mail_enseignant` varchar(100) DEFAULT NULL,
    `id_specialite` int DEFAULT NULL,
    `type_enseignant` int DEFAULT NULL,
    `id_etablissement_origin` int DEFAULT NULL,
    PRIMARY KEY (`id_enseignant`),
    KEY `Key_enseign_specialite` (`id_specialite`),
    KEY `type_enseignant` (`type_enseignant`),
    KEY `id_etablissement_origin` (`id_etablissement_origin`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `enseignants`
--

INSERT INTO
    `enseignants` (
        `id_enseignant`,
        `nom_enseignant`,
        `prenom_enseignant`,
        `tel_enseignant`,
        `mail_enseignant`,
        `id_specialite`,
        `type_enseignant`,
        `id_etablissement_origin`
    )
VALUES (
        '123 253 S',
        'FOFANA',
        'IBRAHIM',
        '505693941',
        'fofana_ib_math_ab@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '123 978 Z',
        'DIALLO',
        'BOUBACAR',
        '707521950',
        'diallobacar@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '131 438 L',
        'ADJE',
        'ASSOHOUN ',
        '101238522',
        'assohounadje@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '137 612 Q',
        'TOURE',
        'MOUSTAPHA ALMAMY',
        '101007110',
        'tam@arc-ingenierie.com',
        NULL,
        NULL,
        1
    ),
    (
        '149 070 L',
        'KOUA',
        'KONIN',
        '101997235',
        'ehiamba53@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '150 976 E',
        'N\'ZOUKOUDI',
        'BERNARD',
        '505821052',
        'nzoukoudi@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '158 851 D',
        'DEMBELE ',
        'MARIAM',
        '707804290',
        'cdemble@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '163 737 X',
        'ABALO ',
        'KOFFI ENYONAM',
        '707730886',
        'demavi14@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '164 300 P',
        'KANGNI',
        'KINVI',
        '707839399',
        'kangnikinvi@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '200 202 NR',
        'YEO',
        'TENAN',
        '709687466',
        'yeo.tenan21@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '2022 001M',
        'KONATE',
        'N\'GOLO',
        '757699769',
        'ingngolo@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '2022 513 NR',
        'TREY',
        'ZACRADA FRANCOISE ODILE',
        '708283447',
        'mariefranceodiletrey@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '2022 538 VE',
        'BAYOMOCK  LINWA',
        'ANDRE CLAUDE ',
        '556718827',
        'bayomock@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '2022 610 VE',
        'DJE',
        'TANOH JEAN MARCEL',
        '709748827',
        'djetano2017@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '210 001 NR',
        'ASSIE',
        'BROU IDA',
        '758686369',
        'ida_as09@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '214 704 M',
        'ASSOHOUN',
        'EGOMLI STANISLAS',
        '707600212',
        'stanlasso@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '233 324 X',
        'MAMADOU ',
        'DIARRA',
        '758889588',
        'patoudiarra@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '233 497 N',
        'SOHOU ',
        'TOUSSAINT',
        '102446746',
        'sohoutous@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '234 514 P',
        'NINDJIN ',
        'AKA FULGENCE',
        '505178915',
        'nindjinaka_fulgence@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '239 314 B',
        'KAMANO',
        'DAMASE',
        '140309731',
        'kamanodamase@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '239 382 G',
        'BROU',
        'PATRICE MAGLOIRE',
        '755701600',
        'bpatricem@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '239 514 U',
        'DANHO',
        'EMILE',
        '707508263',
        'danhoemile@yahoo.com',
        NULL,
        NULL,
        1
    ),
    (
        '241 053 B',
        'MOBIO ',
        'AKICHI JOSEPH',
        '101004573',
        'mobiojosephakichi@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '241 625 D',
        'TANOE',
        'FRANCOIS EMMANUEL',
        '707098004',
        'aziz_marie@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '242 840 J',
        'ADOU ',
        'KABLAN JEROME',
        '707079191',
        'jkadou@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '244 478 M',
        'N\'ZI ',
        'YAO KOFFI MODESTE',
        '142139595',
        'modestenzi@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '249 395 P',
        'SYLLA ',
        'MOUSSA',
        '708497475',
        'ba_mouss@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '252 975 F',
        'KOUA',
        'BROU JEAN CLAUDE',
        '103285241',
        'k_brou@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '253 043 D',
        'BERETE ',
        'SIAKA',
        '755701600',
        'beretesiaka@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '253 561 H',
        'COULIBALY ',
        'ADAMA',
        '707617314',
        'couliba@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '253 567 F',
        'KAMARA',
        'ALIMA',
        '708351850',
        'Kamaradpse@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '255 664 J',
        'KOUAKOU ',
        'KONAN MATHIAS',
        '708991279',
        'makonankouakou@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '255 685 G',
        'GOLI',
        'KONAN CHARLES ETIENNE',
        NULL,
        'golietienne@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '255 997 Z',
        'KOUROUMA',
        'MOUSSA',
        '505700919',
        'mkouroumafr@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '255 998 A',
        'MONSAN',
        'VINCENT',
        '707899426',
        'vmonsan@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '265 055 D',
        'BAILLY',
        'BALE',
        '707098584',
        'baillybale@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '265 638 J',
        'SORO',
        'ETIENNE TENA',
        '707425976',
        'soroet21@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '265 877 A',
        'WODIE ',
        'AOBA JEAN-CHRISTOPHE',
        '707407551',
        'wodie_jc@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '283 496 Q',
        'CODJIA ',
        'ADOLPHE',
        '505982320',
        'ad_wolf2000@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '285 394 T',
        'AMAN ',
        'AUGUSTE',
        '757012959',
        'aman.auguste@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '285 396 V',
        'N\'GUESSAN ',
        'TETCHI ALBIN',
        '759564555',
        'albintetchi@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '296 262 H',
        'TRAORE ',
        'SIAKA',
        '104959544',
        'akaistraore@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '301 095 T',
        'SIAKA',
        'KONE',
        '505016975',
        'siakakone21@yahoo.com',
        NULL,
        NULL,
        1
    ),
    (
        '301 106 B',
        'GONDO',
        'YAKE',
        '707783971',
        'gondo.yake@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '307 815 X',
        'ELOUAFLIN',
        'ABOUO',
        '707357995',
        'elabouo@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '309 103 Q',
        'YODE ',
        'FABRICE ARMEL EVRARD',
        '708331643',
        'yafevrard@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '312 434 M',
        'DJUE ',
        'N\'DRI ROGER',
        '102230413',
        'djuendri@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '320 596 U',
        'AKEKE ',
        'ERIC DAGO',
        '708175780',
        'ericdago@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '324 747 A',
        'BAHI ',
        'LOUIS CLEMENT YOHOU',
        '707744268',
        'baclemsy@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '332 005 X',
        'DOSSO ',
        'MOUHAMADOU',
        '101130647',
        'mouhamadoudoss@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '332 009 B',
        'SAMASSI',
        'LASSANA',
        '709120947',
        'samassilassana@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '335 522 S',
        'TUO',
        'PAUL DAVID',
        '707549835',
        'tuodavidpaul@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '344 437 Y',
        'DIARRASSOUBA ',
        'SIRIKY',
        '749359032',
        'dsiriky@yahoo.com',
        NULL,
        NULL,
        1
    ),
    (
        '344 438 H',
        'TOURE',
        'IBRAHIMA',
        '707511587',
        'toureibt@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '344 439 A',
        'YANGA',
        'KOUASSI KOUASSI SERGE',
        '708281244',
        'yanga.k.k.serge@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '344 444 F',
        'OKOU',
        'A KPETIHI SAHOUA HYPOLITHE',
        '105825812',
        'okouakpetihi@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '345 005 C',
        'COULIBALY ',
        'NAMORY',
        '707675695',
        'namory.coulibaly@univ-fhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '346 123 X',
        'AYIBE',
        'ARISTIDE',
        '747687227',
        'aristideayibe@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '346 124 Y',
        'COULIBALY',
        'PIE',
        '140351290',
        'foussenico14@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '346 309 S',
        'KAYE BI',
        'KOUAI BERTIN ',
        '709314172',
        'kayebi314@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '364 868 L',
        'COULIBALY ',
        'BAKARY',
        '102696958',
        'coulibaly_bakaryfr@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '364 870 J',
        'OWO',
        'KOUASSI JEAN MARC',
        '504282238',
        'marc.owo@univ-fhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '366 733 E',
        'DIARRA',
        'NOUFFOU',
        '556343400',
        'nouffoud@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '389 845 F',
        'SEKA',
        'LOUIS-PAUL',
        '101133405',
        'lpseka@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '389 891 W',
        'ZOKAGOA ',
        'JEAN-MARIE',
        '707365920',
        'zokagoa@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '395 614 V',
        'SILUE',
        'MARIAME',
        NULL,
        'mamsilk@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '398 026 W',
        'ABDOU ',
        'MAÏGA',
        '748392020',
        'maiga.abdou@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '417 722 P',
        'AHIPO ',
        'KWALHA YVES MARCEL',
        '779393212',
        'yahipo@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '426 090 P',
        'KOUA',
        'KPAAGNI ALEX JEREMIE',
        '707267083',
        'jeremiekoua@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '426 918 M',
        'BAROU',
        'ROPLO ANGE-PAULIN',
        '708080073',
        'barouange@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '440 254 S',
        'TCHOUDI',
        'OLIVIER',
        '757572159',
        'olivier.tchoudi53@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '444 617 Z',
        'AYIKPA',
        'KACOUTCHY JEAN ',
        '708791990',
        'ayikpajean@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '456 909 B',
        'DIABAGATE',
        'AMADOU',
        '567954216',
        'ahmadou.diabagate@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '473 396 Z',
        'KONE',
        'BAKARY',
        NULL,
        'dohirimin@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '474 804 U',
        'AMOUZOU',
        'GILDAS YAOVI',
        NULL,
        'gildasamouzou2@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '474 827 J',
        'N\'DRIN',
        'APALA JULIEN',
        NULL,
        'lecorrige@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '497 246 T',
        'ABLE',
        'ZOBO VINCENT DE PAUL',
        NULL,
        'vincentdepaulzobo@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '500 076 B',
        'FEDIDA ',
        'EDMOND',
        NULL,
        'fedida_edmond@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '500 337 D',
        'FEUTO',
        'JUSTIN',
        '505612947',
        'justfeuto@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '501 081 N',
        'SAWADOGO ',
        'AMADOU',
        '748789856',
        'amadou.sawadogo@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '502 460 B',
        'DIALLO',
        'MOHAMED BOBO',
        '777014522',
        'diallo.med@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '819 665 H',
        'SITIONON',
        'GOSSOUHON',
        '103300486',
        'gossouhon.sitionon@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '826 227 R',
        'KOUAKOU',
        'KOUAME FLORENT',
        '778738780',
        'kouameflorentk@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '826 240 A',
        'KRAIDI',
        'ANOH YANNICK',
        NULL,
        'kayanoh2000@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '828 190 T',
        'OUATTARA',
        'MARIAM',
        '153366911',
        'lajourne21@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '857 962 Z',
        'IBRAHIMA',
        'BAKAYOKO',
        '120202014',
        'bakayoko.ibrahima1@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '872 943 V',
        'YAO',
        'EKOUN  NARCISSE',
        '709434436',
        'narcisseyek@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '900 021 AUF',
        'OUATTARA',
        'CHRISTELLE',
        NULL,
        'nanihioouattara@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '910 800 A',
        'KOUASSI',
        'BROU MEDARD',
        '142479549',
        'medardkoisy@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '952 300 A3',
        'MONSAN',
        'VINCENT',
        NULL,
        'monsanv@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '953 001 A',
        'SAMAGASSI',
        'SOULEYMANE',
        NULL,
        'samagassisouley@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        'MS_NON_RENSEIGNE',
        'MAITRE',
        'STAGE',
        NULL,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        'VE000001',
        'TEMBELY',
        'SALIFOU',
        NULL,
        NULL,
        NULL,
        NULL,
        4
    ),
    (
        'VE000002',
        'WAH',
        'MEDARD',
        '707092619',
        'medardwah@gmail.com',
        NULL,
        NULL,
        4
    ),
    (
        'VE000003',
        'KOTEI',
        'SAMUEL',
        '707354728',
        'nikkosa@yahoo.fr',
        NULL,
        NULL,
        4
    );

-- --------------------------------------------------------

--
-- Structure de la table `enseignant_jury`
--

DROP TABLE IF EXISTS `enseignant_jury`;

CREATE TABLE IF NOT EXISTS `enseignant_jury` (
    `num_soutenance` varchar(20) NOT NULL,
    `id_enseignant` varchar(20) NOT NULL,
    `id_qualite_jury` varchar(2) NOT NULL,
    `date_composer_jury` datetime DEFAULT NULL,
    PRIMARY KEY (
        `num_soutenance`,
        `id_enseignant`,
        `id_qualite_jury`
    ),
    KEY `fk_composer_enseignant` (`id_enseignant`),
    KEY `fk_composer_role` (`id_qualite_jury`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `enseignant_jury`
--

INSERT INTO
    `enseignant_jury` (
        `num_soutenance`,
        `id_enseignant`,
        `id_qualite_jury`,
        `date_composer_jury`
    )
VALUES (
        '22221S2271022-01',
        '210 001 NR',
        'EX',
        NULL
    ),
    (
        '22221S2271022-01',
        '285 394 T',
        'DM',
        NULL
    ),
    (
        '22221S2271022-01',
        '309 103 Q',
        'PJ',
        NULL
    ),
    (
        '22221S2271022-01',
        '389 845 F',
        'EN',
        NULL
    ),
    (
        '22221S2271022-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22221S2271022-02',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22221S2271022-02',
        '255 664 J',
        'PJ',
        NULL
    ),
    (
        '22221S2271022-02',
        '285 394 T',
        'DM',
        NULL
    ),
    (
        '22221S2271022-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22221S2271022-02',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22221S2281022-01',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22221S2281022-01',
        '309 103 Q',
        'DM',
        NULL
    ),
    (
        '22221S2281022-01',
        '500 337 D',
        'PJ',
        NULL
    ),
    (
        '22221S2281022-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22221S2281022-01',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22221S2281022-02',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22221S2281022-02',
        '285 394 T',
        'PJ',
        NULL
    ),
    (
        '22221S2281022-02',
        '309 103 Q',
        'DM',
        NULL
    ),
    (
        '22221S2281022-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22221S2281022-02',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22322S1130823-01',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22322S1130823-01',
        '309 103 Q',
        'PJ',
        NULL
    ),
    (
        '22322S1130823-01',
        '320 596 U',
        'DM',
        NULL
    ),
    (
        '22322S1130823-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22322S1130823-01',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22322S1130823-02',
        '210 001 NR',
        'EN',
        NULL
    ),
    (
        '22322S1130823-02',
        '253 561 H',
        'DM',
        NULL
    ),
    (
        '22322S1130823-02',
        '500 337 D',
        'PJ',
        NULL
    ),
    (
        '22322S1130823-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22322S1130823-02',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22423S1290524-01',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22423S1290524-01',
        '233 497 N',
        'PJ',
        NULL
    ),
    (
        '22423S1290524-01',
        '239 382 G',
        'EX',
        NULL
    ),
    (
        '22423S1290524-01',
        '320 596 U',
        'DM',
        NULL
    ),
    (
        '22423S1290524-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S1290524-02',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22423S1290524-02',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22423S1290524-02',
        '320 596 U',
        'PJ',
        NULL
    ),
    (
        '22423S1290524-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S1290524-02',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22423S1290524-03',
        '2022 001M',
        'EX',
        NULL
    ),
    (
        '22423S1290524-03',
        '253 561 H',
        'DM',
        NULL
    ),
    (
        '22423S1290524-03',
        '389 845 F',
        'EN',
        NULL
    ),
    (
        '22423S1290524-03',
        '500 337 D',
        'PJ',
        NULL
    ),
    (
        '22423S1290524-03',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S1290524-04',
        '2022 001M',
        'EX',
        NULL
    ),
    (
        '22423S1290524-04',
        '252 975 F',
        'DM',
        NULL
    ),
    (
        '22423S1290524-04',
        '344 438 H',
        'PJ',
        NULL
    ),
    (
        '22423S1290524-04',
        '389 845 F',
        'EN',
        NULL
    ),
    (
        '22423S1290524-04',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S1300524-01',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22423S1300524-01',
        '309 103 Q',
        'PJ',
        NULL
    ),
    (
        '22423S1300524-01',
        '344 444 F',
        'DM',
        NULL
    ),
    (
        '22423S1300524-01',
        '389 845 F',
        'EX',
        NULL
    ),
    (
        '22423S1300524-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S1300524-02',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22423S1300524-02',
        '255 664 J',
        'PJ',
        NULL
    ),
    (
        '22423S1300524-02',
        '344 444 F',
        'DM',
        NULL
    ),
    (
        '22423S1300524-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S1300524-02',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22423S1300524-03',
        '210 001 NR',
        'EX',
        NULL
    ),
    (
        '22423S1300524-03',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22423S1300524-03',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22423S1300524-03',
        '285 394 T',
        'PJ',
        NULL
    ),
    (
        '22423S1300524-03',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S1300524-04',
        '210 001 NR',
        'EX',
        NULL
    ),
    (
        '22423S1300524-04',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22423S1300524-04',
        '332 005 X',
        'PJ',
        NULL
    ),
    (
        '22423S1300524-04',
        '389 845 F',
        'EN',
        NULL
    ),
    (
        '22423S1300524-04',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2181023-01',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22423S2181023-01',
        '285 394 T',
        'PJ',
        NULL
    ),
    (
        '22423S2181023-01',
        '309 103 Q',
        'DM',
        NULL
    ),
    (
        '22423S2181023-01',
        '389 845 F',
        'EX',
        NULL
    ),
    (
        '22423S2181023-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2181023-02',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22423S2181023-02',
        '285 394 T',
        'DM',
        NULL
    ),
    (
        '22423S2181023-02',
        '309 103 Q',
        'PJ',
        NULL
    ),
    (
        '22423S2181023-02',
        '389 845 F',
        'EX',
        NULL
    ),
    (
        '22423S2181023-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2181023-03',
        '2022 001M',
        'EX',
        NULL
    ),
    (
        '22423S2181023-03',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22423S2181023-03',
        '320 596 U',
        'PJ',
        NULL
    ),
    (
        '22423S2181023-03',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2181023-03',
        'VE000002',
        'EN',
        NULL
    ),
    (
        '22423S2181023-04',
        '2022 513 NR',
        'EX',
        NULL
    ),
    (
        '22423S2181023-04',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22423S2181023-04',
        '320 596 U',
        'PJ',
        NULL
    ),
    (
        '22423S2181023-04',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2181023-04',
        'VE000002',
        'EN',
        NULL
    ),
    (
        '22423S2191023-01',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22423S2191023-01',
        '253 561 H',
        'DM',
        NULL
    ),
    (
        '22423S2191023-01',
        '500 337 D',
        'PJ',
        NULL
    ),
    (
        '22423S2191023-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2191023-01',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22423S2191023-02',
        '2022 513 NR',
        'EX',
        NULL
    ),
    (
        '22423S2191023-02',
        '242 840 J',
        'PJ',
        NULL
    ),
    (
        '22423S2191023-02',
        '309 103 Q',
        'DM',
        NULL
    ),
    (
        '22423S2191023-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2191023-02',
        'VE000002',
        'EN',
        NULL
    ),
    (
        '22423S2191023-03',
        '233 324 X',
        'EX',
        NULL
    ),
    (
        '22423S2191023-03',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22423S2191023-03',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22423S2191023-03',
        '344 444 F',
        'PJ',
        NULL
    ),
    (
        '22423S2191023-03',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2191023-04',
        '210 001 NR',
        'EX',
        NULL
    ),
    (
        '22423S2191023-04',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22423S2191023-04',
        '344 444 F',
        'DM',
        NULL
    ),
    (
        '22423S2191023-04',
        '500 337 D',
        'PJ',
        NULL
    ),
    (
        '22423S2191023-04',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2201023-01',
        '210 001 NR',
        'EN',
        NULL
    ),
    (
        '22423S2201023-01',
        '233 324 X',
        'EX',
        NULL
    ),
    (
        '22423S2201023-01',
        '252 975 F',
        'DM',
        NULL
    ),
    (
        '22423S2201023-01',
        '344 438 H',
        'PJ',
        NULL
    ),
    (
        '22423S2201023-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2201023-02',
        '210 001 NR',
        'EX',
        NULL
    ),
    (
        '22423S2201023-02',
        '233 497 N',
        'PJ',
        NULL
    ),
    (
        '22423S2201023-02',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22423S2201023-02',
        '252 975 F',
        'DM',
        NULL
    ),
    (
        '22423S2201023-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22423S2201023-03',
        '2022 001M',
        'EX',
        NULL
    ),
    (
        '22423S2201023-03',
        '2022 513 NR',
        'EN',
        NULL
    ),
    (
        '22423S2201023-03',
        '233 497 N',
        'PJ',
        NULL
    ),
    (
        '22423S2201023-03',
        '285 394 T',
        'DM',
        NULL
    ),
    (
        '22423S2201023-03',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1210525-01',
        '210 001 NR',
        'EN',
        NULL
    ),
    (
        '22524S1210525-01',
        '210 001 NR',
        'EX',
        NULL
    ),
    (
        '22524S1210525-01',
        '285 394 T',
        'PJ',
        NULL
    ),
    (
        '22524S1210525-01',
        '500 337 D',
        'DM',
        NULL
    ),
    (
        '22524S1210525-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1210525-02',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22524S1210525-02',
        '285 394 T',
        'PJ',
        NULL
    ),
    (
        '22524S1210525-02',
        '309 103 Q',
        'DM',
        NULL
    ),
    (
        '22524S1210525-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1210525-02',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22524S1210525-03',
        '2022 001M',
        'EX',
        NULL
    ),
    (
        '22524S1210525-03',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22524S1210525-03',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22524S1210525-03',
        '344 438 H',
        'PJ',
        NULL
    ),
    (
        '22524S1210525-03',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1210525-04',
        '255 664 J',
        'PJ',
        NULL
    ),
    (
        '22524S1210525-04',
        '309 103 Q',
        'DM',
        NULL
    ),
    (
        '22524S1210525-04',
        '502 460 B',
        'EX',
        NULL
    ),
    (
        '22524S1210525-04',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1210525-04',
        'VE000002',
        'EN',
        NULL
    ),
    (
        '22524S1210525-05',
        '210 001 NR',
        'EX',
        NULL
    ),
    (
        '22524S1210525-05',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22524S1210525-05',
        '344 438 H',
        'PJ',
        NULL
    ),
    (
        '22524S1210525-05',
        '344 444 F',
        'DM',
        NULL
    ),
    (
        '22524S1210525-05',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1210525-06',
        '233 324 X',
        'EX',
        NULL
    ),
    (
        '22524S1210525-06',
        '242 840 J',
        'PJ',
        NULL
    ),
    (
        '22524S1210525-06',
        '500 337 D',
        'DM',
        NULL
    ),
    (
        '22524S1210525-06',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1210525-06',
        'VE000002',
        'EN',
        NULL
    ),
    (
        '22524S1220525-01',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22524S1220525-01',
        '252 975 F',
        'PJ',
        NULL
    ),
    (
        '22524S1220525-01',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22524S1220525-01',
        '910 800 A',
        'EX',
        NULL
    ),
    (
        '22524S1220525-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1220525-02',
        '233 324 X',
        'DM',
        NULL
    ),
    (
        '22524S1220525-02',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22524S1220525-02',
        '242 840 J',
        'PJ',
        NULL
    ),
    (
        '22524S1220525-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1220525-02',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22524S1220525-03',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22524S1220525-03',
        '320 596 U',
        'PJ',
        NULL
    ),
    (
        '22524S1220525-03',
        '344 444 F',
        'DM',
        NULL
    ),
    (
        '22524S1220525-03',
        '502 460 B',
        'EX',
        NULL
    ),
    (
        '22524S1220525-03',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1220525-04',
        '2022 001M',
        'EN',
        NULL
    ),
    (
        '22524S1220525-04',
        '320 596 U',
        'DM',
        NULL
    ),
    (
        '22524S1220525-04',
        '389 845 F',
        'EX',
        NULL
    ),
    (
        '22524S1220525-04',
        '500 337 D',
        'PJ',
        NULL
    ),
    (
        '22524S1220525-04',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S1220525-05',
        '252 975 F',
        'PJ',
        NULL
    ),
    (
        '22524S1220525-05',
        '320 596 U',
        'DM',
        NULL
    ),
    (
        '22524S1220525-05',
        '389 845 F',
        'EN',
        NULL
    ),
    (
        '22524S1220525-05',
        '910 800 A',
        'EX',
        NULL
    ),
    (
        '22524S1220525-05',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2151025-01',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22524S2151025-01',
        '239 382 G',
        'EX',
        NULL
    ),
    (
        '22524S2151025-01',
        '252 975 F',
        'PJ',
        NULL
    ),
    (
        '22524S2151025-01',
        '320 596 U',
        'DM',
        NULL
    ),
    (
        '22524S2151025-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2151025-02',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22524S2151025-02',
        '239 382 G',
        'EX',
        NULL
    ),
    (
        '22524S2151025-02',
        '252 975 F',
        'PJ',
        NULL
    ),
    (
        '22524S2151025-02',
        '320 596 U',
        'DM',
        NULL
    ),
    (
        '22524S2151025-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2151025-03',
        '2022 001M',
        'EX',
        NULL
    ),
    (
        '22524S2151025-03',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22524S2151025-03',
        '332 005 X',
        'PJ',
        NULL
    ),
    (
        '22524S2151025-03',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2151025-03',
        'VE000002',
        'EN',
        NULL
    ),
    (
        '22524S2151025-04',
        '344 438 H',
        'PJ',
        NULL
    ),
    (
        '22524S2151025-04',
        '344 444 F',
        'DM',
        NULL
    ),
    (
        '22524S2151025-04',
        '389 845 F',
        'EX',
        NULL
    ),
    (
        '22524S2151025-04',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2151025-04',
        'VE000002',
        'EN',
        NULL
    ),
    (
        '22524S2151025-05',
        '233 324 X',
        'EX',
        NULL
    ),
    (
        '22524S2151025-05',
        '242 840 J',
        'PJ',
        NULL
    ),
    (
        '22524S2151025-05',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22524S2151025-05',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2151025-05',
        'VE000002',
        'EN',
        NULL
    ),
    (
        '22524S2151025-06',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22524S2151025-06',
        '309 103 Q',
        'DM',
        NULL
    ),
    (
        '22524S2151025-06',
        '500 337 D',
        'PJ',
        NULL
    ),
    (
        '22524S2151025-06',
        '502 460 B',
        'EX',
        NULL
    ),
    (
        '22524S2151025-06',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2151025-07',
        '233 497 N',
        'PJ',
        NULL
    ),
    (
        '22524S2151025-07',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22524S2151025-07',
        '285 394 T',
        'DM',
        NULL
    ),
    (
        '22524S2151025-07',
        '389 845 F',
        'EX',
        NULL
    ),
    (
        '22524S2151025-07',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2151025-08',
        '210 001 NR',
        'EX',
        NULL
    ),
    (
        '22524S2151025-08',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22524S2151025-08',
        '285 394 T',
        'PJ',
        NULL
    ),
    (
        '22524S2151025-08',
        '389 845 F',
        'EN',
        NULL
    ),
    (
        '22524S2151025-08',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2161025-01',
        '2022 001M',
        'EN',
        NULL
    ),
    (
        '22524S2161025-01',
        '210 001 NR',
        'EX',
        NULL
    ),
    (
        '22524S2161025-01',
        '233 497 N',
        'PJ',
        NULL
    ),
    (
        '22524S2161025-01',
        '500 337 D',
        'DM',
        NULL
    ),
    (
        '22524S2161025-01',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2161025-02',
        '255 664 J',
        'DM',
        NULL
    ),
    (
        '22524S2161025-02',
        '285 394 T',
        'PJ',
        NULL
    ),
    (
        '22524S2161025-02',
        '389 845 F',
        'EN',
        NULL
    ),
    (
        '22524S2161025-02',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2161025-02',
        'VE000002',
        'EX',
        NULL
    ),
    (
        '22524S2161025-03',
        '233 324 X',
        'EN',
        NULL
    ),
    (
        '22524S2161025-03',
        '239 382 G',
        'EX',
        NULL
    ),
    (
        '22524S2161025-03',
        '255 664 J',
        'PJ',
        NULL
    ),
    (
        '22524S2161025-03',
        '320 596 U',
        'DM',
        NULL
    ),
    (
        '22524S2161025-03',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2161025-04',
        '244 478 M',
        'PJ',
        NULL
    ),
    (
        '22524S2161025-04',
        '344 444 F',
        'DM',
        NULL
    ),
    (
        '22524S2161025-04',
        '910 800 A',
        'EX',
        NULL
    ),
    (
        '22524S2161025-04',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    ),
    (
        '22524S2161025-04',
        'VE000002',
        'EN',
        NULL
    ),
    (
        '22524S2161025-05',
        '239 382 G',
        'EN',
        NULL
    ),
    (
        '22524S2161025-05',
        '244 478 M',
        'PJ',
        NULL
    ),
    (
        '22524S2161025-05',
        '309 103 Q',
        'DM',
        NULL
    ),
    (
        '22524S2161025-05',
        '910 800 A',
        'EX',
        NULL
    ),
    (
        '22524S2161025-05',
        'MS_NON_RENSEIGNE',
        'MS',
        NULL
    );

-- --------------------------------------------------------

--
-- Structure de la table `entreprises`
--

DROP TABLE IF EXISTS `entreprises`;

CREATE TABLE IF NOT EXISTS `entreprises` (
    `id_entreprise` int NOT NULL,
    `lib_long_entreprise` varchar(100) NOT NULL,
    `lib_court_en` varchar(50) NOT NULL,
    `logo` varchar(256) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `telephone` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`id_entreprise`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `entreprises`
--

INSERT INTO
    `entreprises` (
        `id_entreprise`,
        `lib_long_entreprise`,
        `lib_court_en`,
        `logo`,
        `email`,
        `telephone`
    )
VALUES (
        1,
        'AFRICA DIGITAL GENIUS',
        'AFRICA DIGITAL GENIUS',
        NULL,
        NULL,
        NULL
    ),
    (
        2,
        'ASCENS SERVICES',
        'ASCENS',
        NULL,
        NULL,
        NULL
    ),
    (
        3,
        'ATLANTIQUE TELECOM COTE D\'IVOIRE',
        'ATCI',
        NULL,
        NULL,
        NULL
    ),
    (
        4,
        'BANQUE ATLANTIQUE CÖTE D\'IVOIRE',
        'BACI',
        NULL,
        NULL,
        NULL
    ),
    (
        5,
        'BANQUE GABONAISE et FRANCAISE COTE D\'IVOIRE',
        'BGFI BANK CI',
        NULL,
        NULL,
        NULL
    ),
    (
        6,
        'CABINET DE GEOMETRE EXPERT DIALLO SEKOU',
        'CGEDS',
        NULL,
        NULL,
        NULL
    ),
    (
        7,
        'CARGILL WEST AFRICA',
        'CARGILL WEST AFRICA',
        NULL,
        NULL,
        NULL
    ),
    (
        8,
        'CENTRE MEDICAL EDLONA',
        'CENTRE MEDICAL EDLONA',
        NULL,
        NULL,
        NULL
    ),
    (
        9,
        'COMPAGNIE IVOIRIENNE d\'ELECTRICITE',
        'CIE',
        NULL,
        NULL,
        NULL
    ),
    (
        10,
        'CONSULTECH',
        'CONSULTECH',
        NULL,
        NULL,
        NULL
    ),
    (
        11,
        'Direction Générale du Trésor et de la Comptabilité Publique ',
        'DGTCP',
        NULL,
        NULL,
        NULL
    ),
    (
        12,
        'DJAMO',
        'DJAMO',
        NULL,
        NULL,
        NULL
    ),
    (
        13,
        'DOCUMENTS KNOWLEDGE BUSINESS SOLUTIONS',
        'DKBS',
        NULL,
        NULL,
        NULL
    ),
    (
        14,
        'EBENYX TECHNOLOGIES',
        'EBENYX',
        NULL,
        NULL,
        NULL
    ),
    (
        15,
        'EBURTIS SARL',
        'EBURTIS',
        NULL,
        NULL,
        NULL
    ),
    (
        16,
        'ECOBANK',
        'ECOBANK',
        NULL,
        NULL,
        NULL
    ),
    (
        17,
        'ECO-ONE GESTION LOCATIVE',
        'ECO-ONE GESTION LOCATIVE',
        NULL,
        NULL,
        NULL
    ),
    (
        18,
        'ERNST & YOUNG',
        'EY',
        NULL,
        NULL,
        NULL
    ),
    (
        19,
        'EVEREST CONSULTING',
        'EVEREST CONSULTING',
        NULL,
        NULL,
        NULL
    ),
    (
        20,
        'GROUPEMENT DES SERVICES EAU ET ELECTRICITE',
        'GS2E',
        NULL,
        NULL,
        NULL
    ),
    (
        21,
        'INTELLIGENCE et EXPERTISE AFRIQUE',
        'INEXA',
        NULL,
        NULL,
        NULL
    ),
    (
        22,
        'KIP SERVICES ET TECHNOLOGIES',
        'EKIP',
        NULL,
        NULL,
        NULL
    ),
    (
        23,
        'LOGICSQUARE',
        'LOGICSQUARE',
        NULL,
        NULL,
        NULL
    ),
    (
        24,
        'MEDIASOFT LAFAYETTE',
        'MEDIASOFT LAFAYETTE',
        NULL,
        NULL,
        NULL
    ),
    (
        25,
        'MOBILE TELEPHONE NETWORK COTE D\'IVOIRE',
        'MTN CI',
        NULL,
        NULL,
        NULL
    ),
    (
        26,
        'NEW DIGITAL AFRICA',
        'NEW DIGITAL AFRICA',
        NULL,
        NULL,
        NULL
    ),
    (
        27,
        'NIKKOSSA Communication',
        'NIKKOSSA',
        NULL,
        NULL,
        NULL
    ),
    (
        28,
        'NOUVELLE SOCIETE INTERAFRICAINE d\'ASSURANCE',
        'NSIA',
        NULL,
        NULL,
        NULL
    ),
    (
        29,
        'ORANGE COTE D\'IVOIRE',
        'OCI',
        NULL,
        NULL,
        NULL
    ),
    (
        30,
        'OVERNETFLOW',
        'OVERNETFLOW',
        NULL,
        NULL,
        NULL
    ),
    (
        31,
        'PRIME CONSULTING',
        'PRIME CONSULTING',
        NULL,
        NULL,
        NULL
    ),
    (
        32,
        'QASH SERVICES',
        'QASH SERVICES',
        NULL,
        NULL,
        NULL
    ),
    (
        33,
        'RYCA PHARMA SA',
        'RYCA PHARMA SA',
        NULL,
        NULL,
        NULL
    ),
    (
        34,
        'SILICIUM TECHNOLOGIES SARL',
        'SILICIUM TECHNOLOGIES SARL',
        NULL,
        NULL,
        NULL
    ),
    (
        35,
        'SMART BUSINESS TECHNOLOGIES',
        'SMART TECHNOLOGIES',
        NULL,
        NULL,
        NULL
    ),
    (
        36,
        'SMARTAPS INGENIERIE INFORMATIQUE',
        'SMARTAPS',
        NULL,
        NULL,
        NULL
    ),
    (
        37,
        'SOCIETE AFRICAINE DE CACAO',
        'SACO',
        NULL,
        NULL,
        NULL
    ),
    (
        38,
        'SOCIETE GENERALE AFRICAN BUSINESS SERVICES',
        'SGABS',
        NULL,
        NULL,
        NULL
    ),
    (
        39,
        'SOCIETE GENERALE COTE D\'IVOIRE',
        'SGCI',
        NULL,
        NULL,
        NULL
    ),
    (
        40,
        'SOCIETE NATIONALE DE DEVELOPPEMENT INFORMATIQUE',
        'SNDI',
        NULL,
        NULL,
        NULL
    ),
    (
        41,
        'SOFTN\'FIX TECHNOLOGY',
        'SOFTN\'FIX TECHNOLOGY',
        NULL,
        NULL,
        NULL
    ),
    (
        42,
        'SOGITECH',
        'SOGITECH',
        NULL,
        NULL,
        NULL
    ),
    (
        43,
        'SQORUS',
        'SQORUS',
        NULL,
        NULL,
        NULL
    ),
    (
        44,
        'SUCRERIE AFRICAINE COTE D\'IVOIRE',
        'SUCAF CI',
        NULL,
        NULL,
        NULL
    ),
    (
        45,
        'SYNELIA',
        'SYNELIA',
        NULL,
        NULL,
        NULL
    ),
    (
        46,
        'TURIONE TECHNOLOGIES',
        'TURIONE TECHNOLOGIES',
        NULL,
        NULL,
        NULL
    ),
    (
        47,
        'TECHNOSE',
        'TECHNOSE',
        NULL,
        NULL,
        NULL
    ),
    (
        48,
        'KYRIA CONSULTING',
        'KYRIA CONSULTING',
        NULL,
        NULL,
        NULL
    ),
    (
        49,
        'QUANTECH SOLUTIONS',
        'QUANTECH SOLUTIONS',
        NULL,
        NULL,
        NULL
    );

-- --------------------------------------------------------

--
-- Structure de la table `etablissement_origine`
--

DROP TABLE IF EXISTS `etablissement_origine`;

CREATE TABLE IF NOT EXISTS `etablissement_origine` (
    `id_etablissement` int NOT NULL,
    `libelle_long` varchar(120) NOT NULL,
    `libelle_court` varchar(30) NOT NULL,
    PRIMARY KEY (`id_etablissement`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `etablissement_origine`
--

INSERT INTO
    `etablissement_origine` (
        `id_etablissement`,
        `libelle_long`,
        `libelle_court`
    )
VALUES (
        1,
        'Université Félix Houphouët-Boigny',
        'UFHB'
    ),
    (
        2,
        'Institut National Polytechnique Houphouët-Boigny',
        'INPHB'
    ),
    (
        3,
        'Université Nangui Abrogoua',
        'UNA'
    ),
    (4, 'Entreprise', 'Entreprise');

-- --------------------------------------------------------

--
-- Structure de la table `etudiants`
--

DROP TABLE IF EXISTS `etudiants`;

CREATE TABLE IF NOT EXISTS `etudiants` (
    `num_ident_etud` varchar(25) DEFAULT NULL,
    `num_carte_etud` varchar(25) NOT NULL,
    `nom_etu` varchar(50) NOT NULL,
    `prenom_etu` varchar(100) NOT NULL,
    `date_naiss_etu` date DEFAULT NULL,
    `id_genre` char(1) DEFAULT NULL,
    `email_etu` varchar(100) DEFAULT NULL,
    `promotion_etu` varchar(30) DEFAULT NULL,
    PRIMARY KEY (`num_carte_etud`),
    UNIQUE KEY `uq_etudiants_num_ident` (`num_ident_etud`),
    KEY `genre_etu` (`id_genre`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `etudiants`
--

INSERT INTO
    `etudiants` (
        `num_ident_etud`,
        `num_carte_etud`,
        `nom_etu`,
        `prenom_etu`,
        `date_naiss_etu`,
        `id_genre`,
        `email_etu`,
        `promotion_etu`
    )
VALUES (
        'CI0114284687',
        'ASSJ2304030001',
        'Asseko-Nkogho',
        'Jean-Alphonse Chris Ange Emmanuel',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'CI0108211061',
        'CI0108211061',
        'Guindo',
        'Abdoulaye  ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'CI0112272440',
        'CI0112272440',
        'N\'guessan',
        'Ahou Paule Célestine',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'KONY1404950002',
        'CI0113250936',
        'Konan',
        'Yao Jean Elisée',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'KIMN2712910001',
        'CI0113251313',
        'Kimou',
        'N\'tamon Jean Philipe',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'CI0113252028',
        'CI0113252028',
        'Adou',
        'Bobo Thierry Hervé',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'CI0113273286',
        'CI0113273286',
        'Mondahan',
        'Lydie Aimée ',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'CI0113273537',
        'CI0113273537',
        'Koffi',
        'Adjo Ruth ',
        NULL,
        'F',
        NULL,
        '21918'
    ),
    (
        'CI0114277408',
        'CI0114277408',
        'Balié',
        'Gnahoua Marc-Michel ',
        NULL,
        'M',
        NULL,
        '21817'
    ),
    (
        'CI0114278909',
        'CI0114278909',
        'Diao',
        'Moussa  ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'AKAC2204960002',
        'CI0114278915',
        'Aka',
        'Christian de Pacques',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'DIBG2005950001',
        'CI0114278923',
        'Dibi',
        'Goli N\'guessan Yoann Eric',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'CI0114279119',
        'CI0114279119',
        'N\'guessan',
        'Kadjo Léon ',
        NULL,
        'M',
        NULL,
        '21817'
    ),
    (
        'KASD2202950001',
        'CI0114281762',
        'Kassamba',
        'Diaby Alassane Samuel',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'CI0114283286',
        'CI0114283286',
        'Doumbia',
        'Anliou Badrah Kévin',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'GOLR1305960001',
        'CI0114283734',
        'Goly',
        'Ephrem  ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'SEHW2903960001',
        'CI0114283771',
        'Seh',
        'Wilfried Amos ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'CI0114283821',
        'CI0114283821',
        'N\'cho',
        'Chippaux Pierrette Naomie',
        NULL,
        'F',
        NULL,
        '21817'
    ),
    (
        'CI0114283849',
        'CI0114283849',
        'Yesso',
        'Linda Ange Aminata',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'ATTJ2905970002',
        'CI0114284153',
        'Attiembono',
        'Jean Cédric ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'OUAD2508910002',
        'CI0114284424',
        'Ouattara',
        'Gbassoloko Chris-Isaïe ',
        NULL,
        'M',
        NULL,
        '21716'
    ),
    (
        'CI0114284425',
        'CI0114284425',
        'Mondah',
        'Aristide Arnaud ',
        NULL,
        'M',
        NULL,
        '21817'
    ),
    (
        'CI0114285095',
        'CI0114285095',
        'Dibi',
        'Brice Armand Kouassi',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'HOUG2309970001',
        'CI0115289178',
        'Houndji',
        'Kouadio Lionnel ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'KOUP2506970001',
        'CI0115289478',
        'Kouamé',
        'Prince Samuel ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'CI0115290087',
        'CI0115290087',
        'Alléchy',
        'Assi Axel Alex',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'EHIA2912960001',
        'CI0115290088',
        'Ehinon',
        'Arriko Désiré Ebenezer',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'CI0115290089',
        'CI0115290089',
        'Atokoli',
        'Kra Affoue Larissa Estelle',
        NULL,
        'F',
        NULL,
        '21918'
    ),
    (
        'CI0115290090',
        'CI0115290090',
        'Yao',
        'Marie Ange Elvire',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'CI0115290092',
        'CI0115290092',
        'Kouamé',
        'Amoin Maéva ',
        NULL,
        'F',
        NULL,
        '21918'
    ),
    (
        'CI0115290094',
        'CI0115290094',
        'Dosso',
        'Abdoul-Rhamane  ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'KOUY0810960001',
        'CI0115290100',
        'Kouadio',
        'Stéphane Emmrich ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'DIAM2310950002',
        'CI0115290103',
        'Diarrassouba',
        'Mohamed  ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'CI0115290104',
        'CI0115290104',
        'Kouamelan',
        'Franck-Eric Lionel ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'CI2200000230',
        'CI0115290105',
        'Houessinon',
        'Landry Ayodé ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        '14-24-LMI',
        'CI0115290108',
        'Kacou',
        'Ehouman Narcisse Innocent',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'SIDM0608940001',
        'CI0115290109',
        'Sidibé',
        'Mohamed  ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'DOUK1312960001',
        'CI0115290386',
        'Douassé',
        'Kouétho  ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'KAMZ1505960001',
        'CI0115290756',
        'Kamo',
        'Zoé Rodrigue ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'KONO0306930001',
        'CI0115290815',
        'Koné',
        'Ouahouele Hermann Désiré',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'CI0115291053',
        'CI0115291053',
        'Koffi',
        'N\'zué Sandra ',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'CI0115291194',
        'CI0115291194',
        'Badolo',
        'Koffi Marius ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'TANA1909980001',
        'CI0115291232',
        'Tanoe',
        'Adjoba Sarah-Marguerite ',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'CI0115291243',
        'CI0115291243',
        'Amand',
        'Kouakou Yann-Axel ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'SORK0511930001',
        'CI0115291535',
        'Soro',
        'Kolo Siaka ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'CI0115301657',
        'CI0115301657',
        'Koffi',
        'Kousso Claverie De Camille',
        NULL,
        'F',
        NULL,
        '21817'
    ),
    (
        'CI0115302066',
        'CI0115302066',
        'Yao',
        'Doucaci Anselme ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'CI0115302301',
        'CI0115302301',
        'Akpagnon',
        'Koffi  ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'CI0115302656',
        'CI0115302656',
        'Vanié',
        'Charles  ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'CI0115303004',
        'CI0115303004',
        'Traoré',
        'Fatime  ',
        NULL,
        'F',
        NULL,
        '21817'
    ),
    (
        'CI0115312737',
        'CI0115312737',
        'Sayni',
        'Koffi Bernadin Pacome',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'CI0116304148',
        'CI0116304148',
        'Kouassi',
        'Theya Aoubla Francisca',
        NULL,
        'F',
        NULL,
        '22120'
    ),
    (
        '155001669/KOFF',
        'CI0116304549',
        'Koffi',
        'Kra Herbert Donatien',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        'SORD2606950002',
        'CI0116304978',
        'Soro',
        'Diabiga Khader Aziz',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'GUEK3003940001',
        'CI0116310995',
        'Guelade',
        'Kévin  ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'KOUA0705950007',
        'CI0116311042',
        'Kouao',
        'Ayé Boris ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'ADOL1109970001',
        'CI0116311045',
        'Adou',
        'Lorraine Victoire Akalé',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'ALAP0811970001',
        'CI0116311048',
        'Alao',
        'Paul-Hermann  ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'KOEB2711970001',
        'CI0116311049',
        'Koet',
        'Bi Boh Charbel',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'DIPS0705980001',
        'CI0116311104',
        'Diplo',
        'Sopi Adonis Maxence',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'BOLY1011980002',
        'CI0116311177',
        'Boly',
        'Yannick Ivann ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'CI0116311179',
        'CI0116311179',
        'Bidi',
        'Paul Pascal ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'DEML1504910001',
        'CI0116311231',
        'Dembélé',
        'Loseni  ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'EHOA0110980001',
        'CI0116311232',
        'Ehounou',
        'Ama Sémira ClaudeHermine',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'KOUY2406980002',
        'CI0116311233',
        'Kouakou',
        'Yao Akouadja Cyriaque Roxane',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'KOFK0405950001',
        'CI0116311241',
        'Koffi',
        'William Chrisostome ',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        'GBAA1502990001',
        'CI0116311245',
        'Gbamélé',
        'Andréa Aimée Stéphanie',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        '151216149/YATT',
        'CI0116311371',
        'Yatté',
        'Acho William ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'KONS2208970001',
        'CI0116311409',
        'Konan',
        'Serges landry ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'BAMA0909970001',
        'CI0116311426',
        'Bamba',
        'Arnaud Maurice ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'COUA2104970001',
        'CI0116311551',
        'Coulibaly',
        'Awa  ',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        'BOUL2811950001',
        'CI0116313729',
        'Boua',
        'Léandre N\'guessan ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'ALLA0109990001',
        'CI0116313923',
        'Alla',
        'Akouba Ange Orlane',
        NULL,
        'F',
        NULL,
        '22120'
    ),
    (
        '143300494/AYEN',
        'CI0116331598',
        'Ayénon',
        'Marc-Arnaud  ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        'SOUA0511980001',
        'CI0117324446',
        'Soumahoro',
        'Aboubakar  ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        '093111826/DOH ',
        'CI0117331078',
        'Doh',
        'Bi Boa César',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        '131202494/BROU',
        'CI0117331397',
        'Brou',
        'Arthur Fiacre ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        '134108790/DIAR',
        'CI0117331488',
        'Diarrassouba',
        'Gniriwa Aminata ',
        NULL,
        'F',
        NULL,
        '22019'
    ),
    (
        '161204577/SALI',
        'CI0117331865',
        'Salifou',
        'Georges-Erwin Christian ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        '161208093/SANO',
        'CI0117331881',
        'Sanogo',
        'Abdoul-Aziz Moussa ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        '161214166/KOUA',
        'CI0117331893',
        'Kouassi',
        'Lorraine Ekou ',
        NULL,
        'F',
        NULL,
        '22120'
    ),
    (
        '163118420/DJEC',
        'CI0117331995',
        'Djécketh',
        'Aniela Carly ',
        NULL,
        'F',
        NULL,
        '22221'
    ),
    (
        '163301119/KONA',
        'CI0117332004',
        'Konan',
        'Harvey Désiré ',
        NULL,
        'M',
        NULL,
        '22120'
    ),
    (
        '163304342/TRAB',
        'CI0117332010',
        'Traby',
        'Japhet Arnold ',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '164012210/LAGO',
        'CI0117332028',
        'Lago',
        'Aya Josiane Christelle',
        NULL,
        'F',
        NULL,
        '22221'
    ),
    (
        '164201727/KINH',
        'CI0117332077',
        'Kinhon',
        'Jean François D\'assise',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '165101454/GNOG',
        'CI0117332106',
        'Gnogan',
        'Amichia Paul-Emmanuel ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'SAMB2109990001',
        'CI0117333489',
        'Samy',
        'Bi Licalo ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'AMIK1809980001',
        'CI0118349943',
        'Amichia',
        'Kpovlé Emmanuel Junior',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '171214745/YAPO',
        'CI0118350089',
        'Yapo',
        'Jean Stephane Ruben Assy',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '172307062/YAO-',
        'CI0118350090',
        'Yao-Saki',
        'Marlène Mirsha Hathémann Danielle',
        NULL,
        'F',
        NULL,
        '22221'
    ),
    (
        'TOUS2506000001',
        'CI0118350091',
        'Touré',
        'Sounkaro Klinnan Ariel',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '172202802/SOUL',
        'CI0118350094',
        'Soulé',
        'Arémou Malick Aziz',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'SORF0303000001',
        'CI0118350096',
        'Soro',
        'Fougnigué Kanigui Daouda',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        '171212074/OYOU',
        'CI0118350100',
        'Oyou',
        'Assoko Paul E.',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '171211865/OUAT',
        'CI0118350101',
        'Ouattara',
        'Zelé mariam ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        '175100747/BOKA',
        'CI0118350104',
        'Boka',
        'Chiadon Anita Marlène',
        NULL,
        'F',
        NULL,
        '22221'
    ),
    (
        '171202824/BOUE',
        'CI0118350106',
        'Bouédiro',
        'Djamoin Steve Benjamin',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '171203302/COUL',
        'CI0118350107',
        'Coulibaly',
        'Gningninri Othniel Samson',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '172601610/DIAR',
        'CI0118350110',
        'Diarrassouba',
        'Siaka  ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        '171201270/ANO ',
        'CI0118350111',
        'Ano',
        'N\'ganza Jean-Noel Romaric',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        '173105975/DIOM',
        'CI0118350113',
        'Diomandé',
        'Habdoul Yacine Megbene',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '174011197/DIOM',
        'CI0118350115',
        'Diomandé',
        'Vamonkié Jean Hubert',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '171201836/BAKA',
        'CI0118350119',
        'Bakayoko',
        'Myriam Ana ',
        NULL,
        'F',
        NULL,
        '22221'
    ),
    (
        '171211638/OUAT',
        'CI0118350120',
        'Ouattara',
        'Chêrê Myriam Marie-Eva Jacqueline',
        NULL,
        'F',
        NULL,
        '22322'
    ),
    (
        '175102432/OHOL',
        'CI0118350122',
        'Oholli',
        'Assamoi Kofi Hugues Aimé',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '174030395/N\'GU',
        'CI0118350125',
        'N\'guessan',
        'Técléky Akrou Vidal',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '173202406/MIAN',
        'CI0118350128',
        'Mian',
        'Angui Arnaud Michel',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        '174023275/KOUA',
        'CI0118350132',
        'Kouadio',
        'Messou Ange Patrick',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '171205456/FAMI',
        'CI0118350137',
        'Famié',
        'Ange Junior ',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        '174021825/KONE',
        'CI0118350168',
        'Koné',
        'Ibrahim Zié ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        '174014814/GBE ',
        'CI0118353340',
        'Gbé',
        'Sekou  ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'NANM0805990002',
        'CI0118356304',
        'Nanihio',
        'Marie Milène Cynthia',
        NULL,
        'F',
        NULL,
        '22120'
    ),
    (
        '181201355/ANOM',
        'CI0119373874',
        'Anoma',
        'Dadié Jean Christophe',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '181201526/ASSI',
        'CI0119373875',
        'Assi',
        'Ange Emmanuel ',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '184023067/KOUA',
        'CI0119373882',
        'Kouadio',
        'Kouakou Desiré ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        '184027328/LODI',
        'CI0119373885',
        'Lodioro',
        'Djro Nandjui Regina Prunelle  ',
        NULL,
        'F',
        NULL,
        '22423'
    ),
    (
        '183302736/MALA',
        'CI0119373886',
        'Malan',
        'Kassi Jean-Chris Emmanuel',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '181213250/SOHO',
        'CI0119373888',
        'Sohou',
        'Marc-Arthur Gbadié ',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '181206875/KADI',
        'CI0119376487',
        'Kadio',
        'N\'gadi Jean MarcTokou',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '184123571/KOUA',
        'CI0119376495',
        'Kouamé',
        'Boni Ezechiel ',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '184032961/SAHO',
        'CI0119376506',
        'Sahoré',
        'Kimberly  ',
        NULL,
        'F',
        NULL,
        '22423'
    ),
    (
        '161213173/SIME',
        'CI0119376507',
        'Siméda',
        'Ezekias Mike Prince',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '181508939/TIA ',
        'CI0119376512',
        'Tia',
        'N\'déa Demaurelle ',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '181214844/YAO ',
        'CI0119376516',
        'Yao',
        'Emmanuel Mardochée Onan',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        'YOBH1802000001',
        'CI0119376518',
        'Yoboué',
        'Henoc Jephté ',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        '191201495/ASSE',
        'CI0120389023',
        'Assémien',
        'Kouamé Flavien ',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        'BAHA2507970002',
        'CI0120389024',
        'Bah',
        'Abdoulaye Sadjo',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        '141202563/CISS',
        'CI0120389029',
        'Cissé',
        'Nana  ',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '193105002/COUL',
        'CI0120389034',
        'Coulibaly',
        'Tiékoura Hervé ',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        '194901089/COUL',
        'CI0120389035',
        'Coulibaly',
        'Ismaël  ',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        'DIAM1811010001',
        'CI0120389040',
        'Diabaté',
        'Makan Eméric ',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        '162000679/DIOM',
        'CI0120389046',
        'Diomandé',
        'Dely Ange ',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        '192602782/GOHI',
        'CI0120389059',
        'Gohi',
        'Ange Marlène ',
        NULL,
        'F',
        NULL,
        '22423'
    ),
    (
        'KANT1303010001',
        'CI0120389068',
        'Kanga',
        'Tiécoura Kouadio KpatchiboW.',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        '193202273/KOFF',
        'CI0120389075',
        'Koffi',
        'Cyl Bethsaléel ',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        'KONM3008010001',
        'CI0120389081',
        'Koné',
        'Mohamed  ',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        '071226195/KOUA',
        'CI0120389088',
        'Kouamé',
        'Kouassi Oscar ',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '194801187/SORO',
        'CI0120389113',
        'Soro',
        'Ibrahim  ',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        '161212417/TANO',
        'CI0120389115',
        'Tano',
        'Kouadio Barthelemy Junior',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        '184907031/TOUR',
        'CI0120389118',
        'Touré',
        'Katinan  ',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        'CI0120394713',
        'CI0120394713',
        'Kadouno',
        'Jean-Louis  ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'KONY0801040001',
        'CI0121391125',
        'Konan',
        'Yann Mendel ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        '204002773/ALAG',
        'CI0121398949',
        'Alagbo',
        'Koffi Uriel ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'ATTK0309000002',
        'CI0121398959',
        'Attitso',
        'Kossivi Joël ',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        '161213861/CISS',
        'CI0121398966',
        'Cissé',
        'Kadidja  ',
        NULL,
        'F',
        NULL,
        '22625'
    ),
    (
        '201205164/COUL',
        'CI0121398967',
        'Coulibaly',
        'Koutianga Malick ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'CRIB2105030002',
        'CI0121398969',
        'Critié',
        'Bi Boti Yann Florent',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        '201206167/DIAB',
        'CI0121398970',
        'Diaby',
        'Hadidja  ',
        NULL,
        'F',
        NULL,
        '22524'
    ),
    (
        'DJAC1110020001',
        'CI0121398973',
        'Djadou',
        'Cauphy Christian Jordy',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        '203107762/DOUA',
        'CI0121398977',
        'Douampo',
        'Marie-Joseph Armel ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'GANG1008030001',
        'CI0121398983',
        'Ganon',
        'Gnidan Myriam ',
        NULL,
        'F',
        NULL,
        '22625'
    ),
    (
        '203402572/KOFF',
        'CI0121398997',
        'Koffi',
        'André Yann Emmanuel',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'KOUA3007030002',
        'CI0121399010',
        'Kouadio',
        'Amenan Marie Renée Emmanuella',
        NULL,
        'F',
        NULL,
        '22625'
    ),
    (
        '201213696/KOUA',
        'CI0121399014',
        'Kouakou',
        'Henri Joel ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'KOUD2803030002',
        'CI0121399019',
        'Kouassi',
        'Djôlo Yves-Aurel ',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'TOUG2003030001',
        'CI0121399059',
        'Touré',
        'Gnimy Henock ',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        '203123140/TRAO',
        'CI0121399060',
        'Traoré',
        'Amy  ',
        NULL,
        'F',
        NULL,
        '22524'
    ),
    (
        '162004707/YAO ',
        'CI0121399063',
        'Yao',
        'Kan N\'guessan Maurice Ferras',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        'CI2200000001',
        'CI2200000001',
        'Brou',
        'Kouamé Wa Ambroise',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000002',
        'CI2200000002',
        'Coulibaly',
        'Pécory Ismaèl ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000003',
        'CI2200000003',
        'Diomandé',
        'Gondo Patrick ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000004',
        'CI2200000004',
        'Ekponou',
        'Georges  ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000005',
        'CI2200000005',
        'Gnaman',
        'Arthur Berenger ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000006',
        'CI2200000006',
        'Guiégui',
        'Arnaud Kévin Boris',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000007',
        'CI2200000007',
        'Kacou',
        'Allou Yves-Roland ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000008',
        'CI2200000008',
        'Kadio',
        'Paule Elodie ',
        NULL,
        'F',
        NULL,
        '20403'
    ),
    (
        'CI2200000009',
        'CI2200000009',
        'Kéi',
        'Ninsémon Hervé ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000010',
        'CI2200000010',
        'Kinimo',
        'Habia Elvire ',
        NULL,
        'F',
        NULL,
        '20403'
    ),
    (
        'CI2200000011',
        'CI2200000011',
        'Kouadio',
        'Donald  ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000012',
        'CI2200000012',
        'Kouadio',
        'Sékédoua Jules ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000013',
        'CI2200000013',
        'Mambo',
        'Katty Tatiana ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000014',
        'CI2200000014',
        'Mukenge',
        'Kalenga  ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000015',
        'CI2200000015',
        'N\'guessan',
        'Constant  ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000016',
        'CI2200000016',
        'Niamien',
        'Casimir  ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000017',
        'CI2200000017',
        'Oula',
        'Séblé Lucien ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000018',
        'CI2200000018',
        'Sagnon',
        'Boga Eric ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000019',
        'CI2200000019',
        'Tiémélé',
        'Solange  ',
        NULL,
        'F',
        NULL,
        '20403'
    ),
    (
        'CI2200000020',
        'CI2200000020',
        'Yao',
        'Hermann Berenger ',
        NULL,
        'M',
        NULL,
        '20403'
    ),
    (
        'CI2200000021',
        'CI2200000021',
        'Yao',
        'Michaelle Sylvie ',
        NULL,
        'F',
        NULL,
        '20403'
    ),
    (
        'CI2200000022',
        'CI2200000022',
        'Zakpa',
        'Emmanuella  ',
        NULL,
        'F',
        NULL,
        '20403'
    ),
    (
        'CI2200000023',
        'CI2200000023',
        'Agounkpeto',
        'Jean michel ',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000024',
        'CI2200000024',
        'Aka',
        'Ange kévin ',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000025',
        'CI2200000025',
        'Aka',
        'Prince  ',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000026',
        'CI2200000026',
        'Akpa',
        'Gnagne david martial',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000027',
        'CI2200000027',
        'Barthe',
        'Kobi hugues didier',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000028',
        'CI2200000028',
        'Djehéré',
        'Claude  ',
        NULL,
        'F',
        NULL,
        '20504'
    ),
    (
        'CI2200000029',
        'CI2200000030',
        'Gogori',
        'N\'guessan etienne hugues',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000030',
        'CI2200000031',
        'Gouzou',
        'Zékou mathurin ',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000031',
        'CI2200000032',
        'Kacou',
        'Akimba carolle ',
        NULL,
        'F',
        NULL,
        '20504'
    ),
    (
        'CI2200000032',
        'CI2200000033',
        'Koffi',
        'Bi tiessé franck',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000033',
        'CI2200000034',
        'Koné',
        'Petiéninpou salifou ',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000034',
        'CI2200000035',
        'Kouadé',
        'Ano jean ',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000035',
        'CI2200000036',
        'Kouadio',
        'Assi donald landry',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000036',
        'CI2200000037',
        'Ossey',
        'tanguy  ',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000037',
        'CI2200000038',
        'Touré',
        'Badiénry fabrice ',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000038',
        'CI2200000039',
        'Yéré',
        'Adou vincent ',
        NULL,
        'M',
        NULL,
        '20504'
    ),
    (
        'CI2200000039',
        'CI2200000040',
        'Aby',
        'Nanpé Olivier ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000040',
        'CI2200000041',
        'Aliman',
        'Prisca  ',
        NULL,
        'F',
        NULL,
        '20605'
    ),
    (
        'CI2200000041',
        'CI2200000042',
        'Bakayoko',
        'Soumaila  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000042',
        'CI2200000043',
        'Berthé',
        'Issa  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000043',
        'CI2200000044',
        'Dacoury',
        'Armand  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000044',
        'CI2200000045',
        'Diallo',
        'Marlène  ',
        NULL,
        'F',
        NULL,
        '20605'
    ),
    (
        'CI2200000045',
        'CI2200000046',
        'Dossou',
        'Falome Flora ',
        NULL,
        'F',
        NULL,
        '20605'
    ),
    (
        'CI2200000046',
        'CI2200000047',
        'Fofana',
        'Lazeni  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000047',
        'CI2200000048',
        'Fongbé',
        'Amadou  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000048',
        'CI2200000049',
        'Gnamien',
        'Badjo Carine ',
        NULL,
        'F',
        NULL,
        '20605'
    ),
    (
        'CI2200000049',
        'CI2200000050',
        'Kalou',
        'Bi Florent ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000050',
        'CI2200000051',
        'Kané',
        'Kader  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000051',
        'CI2200000052',
        'Konan',
        'Hermann Michel ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000052',
        'CI2200000053',
        'Koné',
        'Djébilou  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000053',
        'CI2200000054',
        'Kouyaté',
        'Bangali  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000054',
        'CI2200000055',
        'Latte',
        'Pierre André ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000055',
        'CI2200000056',
        'Méango',
        'Jean Marie ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000056',
        'CI2200000057',
        'Mian',
        'Koffi Jules Césare',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000057',
        'CI2200000058',
        'Monsan',
        'Chimène  ',
        NULL,
        'F',
        NULL,
        '20605'
    ),
    (
        'CI2200000058',
        'CI2200000059',
        'Mouhamed',
        'Moubarak  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000059',
        'CI2200000060',
        'N\'goran',
        'Yao Dénis ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000060',
        'CI2200000061',
        'N\'guessan',
        'Jacques  ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000061',
        'CI2200000062',
        'Ossey',
        'Sabrina  ',
        NULL,
        'F',
        NULL,
        '20605'
    ),
    (
        'CI2200000062',
        'CI2200000063',
        'Ouattara',
        'Ecaré Myriam ',
        NULL,
        'F',
        NULL,
        '20605'
    ),
    (
        'CI2200000063',
        'CI2200000064',
        'Ouffoué',
        'Yawyha Attinouanfier J.',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000064',
        'CI2200000065',
        'Sassou',
        'Mensah Boris ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000065',
        'CI2200000066',
        'Soumahoro',
        'Badra Ali ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000066',
        'CI2200000067',
        'Tanoh',
        'Kouassi Pacome ',
        NULL,
        'M',
        NULL,
        '20605'
    ),
    (
        'CI2200000067',
        'CI2200000068',
        'Akinola',
        'Oyéniyi Alexis Laurent S.',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000068',
        'CI2200000069',
        'Attisou',
        'Jean-François  ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000069',
        'CI2200000070',
        'Badouon',
        'Ange Rodrigue ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000070',
        'CI2200000071',
        'Bédy',
        'Nathanael Durand ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000071',
        'CI2200000072',
        'Blé',
        'Aka Jean-Jacques Ferdinand',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000072',
        'CI2200000073',
        'Bodjé',
        'Hippolyte  ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000073',
        'CI2200000074',
        'Bodjé',
        'N\'kauh Nathan Regis',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000074',
        'CI2200000075',
        'Bouraïman',
        'Farwaz  ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000075',
        'CI2200000076',
        'Brou',
        'Kouakou Ange ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000076',
        'CI2200000077',
        'Cissé',
        'Souleymane Désiré Cédric',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000077',
        'CI2200000078',
        'Diallo',
        'Mamadou  ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000078',
        'CI2200000079',
        'Dja',
        'Blé Robert Martial',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000079',
        'CI2200000080',
        'Dobé',
        'Anicet Landry G.',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000080',
        'CI2200000081',
        'Doh',
        'Alain Hyppolyte ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000081',
        'CI2200000082',
        'Fanoudh-Siefer',
        'Jocelyne  ',
        NULL,
        'F',
        NULL,
        '20706'
    ),
    (
        'CI2200000082',
        'CI2200000083',
        'Fioklou',
        'Mawuena Linda Sandrine',
        NULL,
        'F',
        NULL,
        '20706'
    ),
    (
        'CI2200000083',
        'CI2200000084',
        'Gami',
        'Tizié Bi Eric',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000084',
        'CI2200000085',
        'Gnanagbé',
        'Gilles Gohou ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000085',
        'CI2200000086',
        'Gouley',
        'Vincent de Paul',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000086',
        'CI2200000087',
        'Koffi',
        'Kouassi Michel ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000087',
        'CI2200000088',
        'Koné',
        'Moussa  ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000088',
        'CI2200000089',
        'Kouamé',
        'Kouamenan Jean Baptiste',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000089',
        'CI2200000090',
        'Kouman',
        'Kobenan Constant ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000090',
        'CI2200000091',
        'Maïga',
        'Jean-Luc Hervé Morel',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000091',
        'CI2200000092',
        'N\'gadjingar',
        'Arnold  ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000092',
        'CI2200000093',
        'N\'guessan',
        'Modri Suzanne Sandrine',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000093',
        'CI2200000094',
        'Nindjin',
        'Malan Alain ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000094',
        'CI2200000095',
        'Oussou',
        'Gbogboly Romaric Anselme',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000095',
        'CI2200000096',
        'Rabet',
        'Stéphane  ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000096',
        'CI2200000097',
        'Sékongo',
        'Kafalo David ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000097',
        'CI2200000098',
        'Sékongo',
        'Kafalo Siméon ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000098',
        'CI2200000099',
        'Sékongo',
        'Sionta Débora ',
        NULL,
        'F',
        NULL,
        '20706'
    ),
    (
        'CI2200000099',
        'CI2200000100',
        'Senin',
        'N\'guetta Patrick Yoann',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000100',
        'CI2200000101',
        'Tanoh-Niangoin',
        'Arnaud Joël ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000101',
        'CI2200000102',
        'Tchétché',
        'Lazare  ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000102',
        'CI2200000103',
        'Tchicaillat',
        'Anelvie  ',
        NULL,
        'F',
        NULL,
        '20706'
    ),
    (
        'CI2200000103',
        'CI2200000104',
        'Tiémélé',
        'Amandi Jean-Michel ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000104',
        'CI2200000105',
        'Traoré',
        'Kigninlman François-Michaël ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000105',
        'CI2200000106',
        'Traoré',
        'Mamadou Ben ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000106',
        'CI2200000107',
        'Yaméogo',
        'Emmanuel  ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000107',
        'CI2200000108',
        'Yoboué',
        'Kouamé Françis ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000108',
        'CI2200000109',
        'Zokou',
        'Gbalé Simion ',
        NULL,
        'M',
        NULL,
        '20706'
    ),
    (
        'CI2200000109',
        'CI2200000110',
        'Abudrahman',
        'Bako Rouhiya ',
        NULL,
        'F',
        NULL,
        '20807'
    ),
    (
        'CI2200000110',
        'CI2200000111',
        'Acho',
        'Dessi Stéphane Ivan',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000111',
        'CI2200000112',
        'Adja',
        'Willy Junior ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000112',
        'CI2200000113',
        'Aka',
        'Manouan Angora Jean-Yves',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000113',
        'CI2200000114',
        'Allou',
        'Niamké Jean-Marc ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000114',
        'CI2200000115',
        'Assy',
        'Yves Landry ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000115',
        'CI2200000116',
        'Bouah',
        'Martin Benjamin ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000116',
        'CI2200000117',
        'Cissé',
        'Ladji  ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000117',
        'CI2200000118',
        'Dagbo',
        'Ouraga Hervé ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000118',
        'CI2200000119',
        'Dagnogo',
        'Chigata  ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000119',
        'CI2200000120',
        'Degni',
        'N\'drin Marie-Corine Jordane',
        NULL,
        'F',
        NULL,
        '20807'
    ),
    (
        'CI2200000120',
        'CI2200000121',
        'Djeah',
        'Eric  ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000121',
        'CI2200000122',
        'Fagla',
        'Armel Jean Yves',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000122',
        'CI2200000123',
        'Gnayoro',
        'Dano Hugues Florent',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000123',
        'CI2200000124',
        'Gohourou',
        'Djédjé Didier ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000124',
        'CI2200000125',
        'Houi',
        'Sosthène  ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000125',
        'CI2200000126',
        'Houssou',
        'Ipou Marie-Ange Colette',
        NULL,
        'F',
        NULL,
        '20807'
    ),
    (
        'CI2200000126',
        'CI2200000127',
        'Kabran',
        'N\'guessan Jules-César ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000127',
        'CI2200000128',
        'Kacou',
        'N\'da Geneviève ',
        NULL,
        'F',
        NULL,
        '20807'
    ),
    (
        'CI2200000128',
        'CI2200000129',
        'Kanaté',
        'Adama  ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000129',
        'CI2200000130',
        'Konan',
        'Attocoly Aristide Christian',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000130',
        'CI2200000131',
        'Kotei',
        'Nikoi Samuel ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000131',
        'CI2200000132',
        'Koua',
        'Konin N\'goran Marc Benjamin',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000132',
        'CI2200000133',
        'Kouacou',
        'Adjoua Jessica Noelle',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000133',
        'CI2200000134',
        'Kouamé',
        'Ayoua Alain Blédoumou',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000134',
        'CI2200000135',
        'Kouamé',
        'Bi Gohoré Stéphane-Marcel',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000135',
        'CI2200000136',
        'Kouao',
        'Akoissy Amoan Lynda Flore',
        NULL,
        'F',
        NULL,
        '20807'
    ),
    (
        'CI2200000136',
        'CI2200000137',
        'Kouodé',
        'Nioulé Steve ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000137',
        'CI2200000138',
        'Loba',
        'Badjo Caroline Vinciane',
        NULL,
        'F',
        NULL,
        '20807'
    ),
    (
        'CI2200000138',
        'CI2200000139',
        'Mariko',
        'Eba Raïssa ',
        NULL,
        'F',
        NULL,
        '20807'
    ),
    (
        'CI2200000139',
        'CI2200000140',
        'Moukounzi',
        'Bakala Axel ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000140',
        'CI2200000141',
        'N\'diaye',
        'M\'baye  ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000141',
        'CI2200000142',
        'Niamké',
        'Ehuia Marie-Eve ',
        NULL,
        'F',
        NULL,
        '20807'
    ),
    (
        'CI2200000142',
        'CI2200000143',
        'Oué',
        'Simon  ',
        NULL,
        'M',
        NULL,
        '20807'
    ),
    (
        'CI2200000143',
        'CI2200000144',
        'Akanza',
        'kouassi Ronald ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000144',
        'CI2200000145',
        'Ané',
        'Antoine Ahoua ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000145',
        'CI2200000146',
        'Ango',
        'Charles Erwan Brou',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000146',
        'CI2200000147',
        'Anon',
        'Noelly  ',
        NULL,
        'F',
        NULL,
        '20908'
    ),
    (
        'CI2200000147',
        'CI2200000148',
        'Boni',
        'Jean-Philipe  ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000148',
        'CI2200000149',
        'Boua',
        'Stéphane Guesso ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000149',
        'CI2200000150',
        'Coulibaly',
        'Sékoumar Ayaké ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000150',
        'CI2200000151',
        'Djédjé',
        'Manoko Arthur-Ange ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000151',
        'CI2200000152',
        'Dongo',
        'Kouamé Yannick ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000152',
        'CI2200000153',
        'Doou',
        'Serge Baulais ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000153',
        'CI2200000154',
        'Douampo',
        'Berthe  ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000154',
        'CI2200000155',
        'Goeh-Akue',
        'Adoté Fabrice ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000155',
        'CI2200000156',
        'Kanga',
        'Didier Franck ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000156',
        'CI2200000157',
        'Kanté',
        'Néné  ',
        NULL,
        'F',
        NULL,
        '20908'
    ),
    (
        'CI2200000157',
        'CI2200000158',
        'Kéïta',
        'Abdul Pierre Emmanuel',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000158',
        'CI2200000159',
        'Kouadio',
        'Ange Aristide ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000159',
        'CI2200000160',
        'Kouadio',
        'Loukou Arnaud ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000160',
        'CI2200000161',
        'Kouamé',
        'N\'woley Kévin ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000161',
        'CI2200000162',
        'Kouassi',
        'Kamelan Herman ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000162',
        'CI2200000163',
        'Lobé',
        'Ogonnin Gédéon ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000163',
        'CI2200000164',
        'M\'bra',
        'Koffi Serges Pacôme',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000164',
        'CI2200000165',
        'Namongo',
        'Soro Christian Etienne',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000165',
        'CI2200000166',
        'N\'da-Ezoa',
        'Melanwa Issac ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000166',
        'CI2200000167',
        'N\'guessan',
        'Ahoko Lazare ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000167',
        'CI2200000168',
        'N\'zazi',
        'Yannick  ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000168',
        'CI2200000169',
        'Ouattara',
        'Nambé Adama ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000169',
        'CI2200000170',
        'Sawadogo',
        'Moussa  ',
        NULL,
        'M',
        NULL,
        '20908'
    ),
    (
        'CI2200000170',
        'CI2200000171',
        'Akini',
        'Marie Danielle ',
        NULL,
        'F',
        NULL,
        '21009'
    ),
    (
        'CI2200000171',
        'CI2200000172',
        'Arra',
        'Jean Jonathan ',
        NULL,
        'M',
        NULL,
        '21009'
    ),
    (
        'CI2200000172',
        'CI2200000173',
        'Attro',
        'Elvis Donald ',
        NULL,
        'M',
        NULL,
        '21009'
    ),
    (
        'CI2200000173',
        'CI2200000174',
        'Doe',
        'Kouassi Ezékiel ',
        NULL,
        'M',
        NULL,
        '21009'
    ),
    (
        'CI2200000174',
        'CI2200000175',
        'Facondé',
        'Rudy Ariel ',
        NULL,
        'M',
        NULL,
        '21009'
    ),
    (
        'CI2200000175',
        'CI2200000176',
        'Kouahouri',
        'Okou Joel ',
        NULL,
        'M',
        NULL,
        '21009'
    ),
    (
        'CI2200000176',
        'CI2200000177',
        'Kouamé',
        'Christian Koffi ',
        NULL,
        'M',
        NULL,
        '21009'
    ),
    (
        'CI2200000177',
        'CI2200000178',
        'Kouamé',
        'Kodé Guy Roland',
        NULL,
        'M',
        NULL,
        '21009'
    ),
    (
        'CI2200000178',
        'CI2200000179',
        'Kourouma',
        'Fanta  ',
        NULL,
        'F',
        NULL,
        '21009'
    ),
    (
        'CI2200000179',
        'CI2200000180',
        'N\'guessan',
        'Técléky Hubert N\'da',
        NULL,
        'M',
        NULL,
        '21009'
    ),
    (
        'CI0111272417',
        'CI2200000181',
        'Ahouana',
        'Akichi Roche Wilfried',
        NULL,
        'M',
        NULL,
        '21009'
    ),
    (
        'CI2200000180',
        'CI2200000182',
        'Aka',
        'Itchi Maxime ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000181',
        'CI2200000183',
        'Amisia',
        'Molay Jean-marie ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000182',
        'CI2200000184',
        'Amoikon',
        'Kangah Christophe ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000183',
        'CI2200000185',
        'Beugré',
        'Wahon Marie-claude Esther',
        NULL,
        'F',
        NULL,
        '21110'
    ),
    (
        'CI2200000184',
        'CI2200000186',
        'Cherif',
        'Idriss Ibrahim ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000185',
        'CI2200000187',
        'Flan',
        'Zédé delphin ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000186',
        'CI2200000188',
        'Gbakatchétché',
        'Gilles-loïc  ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000187',
        'CI2200000189',
        'Gnangne',
        'Jean Jacques ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000188',
        'CI2200000190',
        'Kouakou',
        'Enode de Laure',
        NULL,
        'F',
        NULL,
        '21110'
    ),
    (
        'CI2200000189',
        'CI2200000191',
        'Kouakou',
        'N\'guetta Marie-laure Cynthia',
        NULL,
        'F',
        NULL,
        '21110'
    ),
    (
        'CI2200000190',
        'CI2200000192',
        'Kouassi',
        'Laetitia Aimée tomoly',
        NULL,
        'F',
        NULL,
        '21110'
    ),
    (
        'CI2200000191',
        'CI2200000193',
        'Krama',
        'Abdel-Kader  ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000192',
        'CI2200000194',
        'N\'guessan',
        'kouakou Fulgence ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000193',
        'CI2200000195',
        'Tuo',
        'Kolotioloma Augustin ',
        NULL,
        'M',
        NULL,
        '21110'
    ),
    (
        'CI2200000194',
        'CI2200000196',
        'Bailly',
        'G. Arnaud ',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000195',
        'CI2200000197',
        'Brou',
        'Kouakou Konan Ange',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000196',
        'CI2200000198',
        'Coulibaly',
        'Hector Emile ',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000197',
        'CI2200000199',
        'Fofana',
        'N\'valy  ',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000198',
        'CI2200000200',
        'Gossan',
        'Akon Boris ',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000199',
        'CI2200000201',
        'Koffi',
        'Guetta J.B. Carmel',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000200',
        'CI2200000202',
        'Koffi',
        'Stéphane Placide ',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000201',
        'CI2200000203',
        'Kouadio',
        'Stéphane Kpangban ',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000202',
        'CI2200000204',
        'Kouaho',
        'Kodé Guy Roland',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000203',
        'CI2200000205',
        'Kouassi',
        'N\'dri Yves ',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000204',
        'CI2200000206',
        'Kouyo',
        'Jonathan Ivan Lesson',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000205',
        'CI2200000207',
        'Ossein',
        'Franck  ',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000206',
        'CI2200000208',
        'Ouattara',
        'Perbin Parfait ',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000207',
        'CI2200000209',
        'Tra',
        'Bi Tra Tizié Cyrille Modeste',
        NULL,
        'M',
        NULL,
        '21211'
    ),
    (
        'CI2200000208',
        'CI2200000210',
        'Abroh',
        'Alokré Samuel Eliézer',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000209',
        'CI2200000211',
        'Attiembone',
        'Christelle  ',
        NULL,
        'F',
        NULL,
        '21312'
    ),
    (
        'CI2200000210',
        'CI2200000212',
        'Diallo',
        'Ismael  ',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000211',
        'CI2200000213',
        'Diarrassouba',
        'Habib Ismael ',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000212',
        'CI2200000214',
        'Diaw',
        'Oumar Passidi ',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000213',
        'CI2200000215',
        'Diawara',
        'Daoud Ben Ahmed',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000214',
        'CI2200000216',
        'Koloubla',
        'Dakouri Auguste Trésor',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000215',
        'CI2200000217',
        'Konan',
        'Konan Jean François Regis',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000216',
        'CI2200000219',
        'Kouakou',
        'kouamé Adjaphin Noël Désiré',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000217',
        'CI2200000220',
        'Menzan',
        'Bini Kouamé Christian',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000219',
        'CI2200000221',
        'Nguessan',
        'kalou bi Dieudonné',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000220',
        'CI2200000222',
        'Tanoh',
        'Adjoua Marie Elise Rebecca',
        NULL,
        'F',
        NULL,
        '21312'
    ),
    (
        'CI2200000221',
        'CI2200000223',
        'Ya',
        'Sandrine Anne-Elodie ',
        NULL,
        'F',
        NULL,
        '21312'
    ),
    (
        'CI2200000222',
        'CI2200000224',
        'Yéyé',
        'Schadrachs Guy-Roland ',
        NULL,
        'M',
        NULL,
        '21312'
    ),
    (
        'CI2200000223',
        'CI2200000225',
        'Absou',
        'Brice Donald ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000224',
        'CI2200000226',
        'Adane',
        'Kouakou Christian ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000225',
        'CI2200000227',
        'Adja',
        'Gossan Ange Rodrigue',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000226',
        'CI2200000228',
        'Agui',
        'Ange Boris ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000227',
        'CI2200000229',
        'Assoma',
        'Assoma Evrard Wilfried',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI0109224375',
        'CI2200000230',
        'Atsé',
        'Nina Larissa ',
        NULL,
        'F',
        NULL,
        '21413'
    ),
    (
        'BOBJ2203880001',
        'CI2200000231',
        'Bobou',
        'Eliézer Josué ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000228',
        'CI2200000232',
        'Coffi-Amany',
        'Ané Serge Eric',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000229',
        'CI2200000233',
        'Coulibaly',
        'Abdoul Karim ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI0110243163',
        'CI2200000234',
        'Coulou',
        'Kouadio Léandre ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000231',
        'CI2200000235',
        'Diallo',
        'Malick-Olivier  ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000232',
        'CI2200000236',
        'Dioulo',
        'Nempé Antonin Alexis',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000233',
        'CI2200000237',
        'Fagla',
        'Jean Frédéric ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000234',
        'CI2200000238',
        'Goa',
        'Womedo Ghislain ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI0115290105',
        'CI2200000239',
        'Guipie',
        'Goualy Cyrille ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000235',
        'CI2200000240',
        'Hié',
        'Wallo Frédéric Auguste',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000236',
        'CI2200000241',
        'Johnson',
        'Grace Yenin Edwige',
        NULL,
        'F',
        NULL,
        '21413'
    ),
    (
        'CI2200000237',
        'CI2200000242',
        'Kobena',
        'Attah Jean Achille',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000238',
        'CI2200000243',
        'Koffi',
        'Katché Olivier ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000239',
        'CI2200000244',
        'Koffi',
        'kouamé Fabrice ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI0110243311',
        'CI2200000245',
        'Koffi',
        'Mekhan Girault ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000240',
        'CI2200000246',
        'Kouadio',
        'N\'guessan Richard ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000241',
        'CI2200000247',
        'Kouakou',
        'Nanhou Armande Elika',
        NULL,
        'F',
        NULL,
        '21413'
    ),
    (
        'CI2200000242',
        'CI2200000248',
        'Kouakou',
        'Ouattara Affoussatou ',
        NULL,
        'F',
        NULL,
        '21413'
    ),
    (
        'CI2200000243',
        'CI2200000249',
        'Kouakou',
        'Wacrablet Ebony Hyacinthe',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000244',
        'CI2200000250',
        'Kouamé',
        'Ahouo Clotilde ',
        NULL,
        'F',
        NULL,
        '21413'
    ),
    (
        'CI2200000245',
        'CI2200000251',
        'Kouamé',
        'Kacou Christian ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'KOUA0204890001',
        'CI2200000252',
        'Kouassi',
        'Aka Marius ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000246',
        'CI2200000253',
        'Kouassi',
        'N\'gonian Emmanuel ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000247',
        'CI2200000254',
        'Kouassi',
        'Zilé Yao Eric-Gael',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000248',
        'CI2200000255',
        'Kouman',
        'Kouakou Sidoine ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000249',
        'CI2200000256',
        'Mekoundé',
        'Olivier Clotaire ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000250',
        'CI2200000257',
        'Moro',
        'Yves-Kévin  ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000251',
        'CI2200000258',
        'N\'goran',
        'Angoua Omer N.',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000252',
        'CI2200000259',
        'N\'goran',
        'N\'sikan Jean Baptiste',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000253',
        'CI2200000260',
        'Ouattara',
        'Gninlipkoho Romuald ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000254',
        'CI2200000261',
        'Sylla',
        'Mohamed  ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000255',
        'CI2200000262',
        'Tanoh',
        'Armel Désiré ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000256',
        'CI2200000263',
        'Tia',
        'Gbongué Joel ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000257',
        'CI2200000264',
        'Touré',
        'Cédric Delan ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000258',
        'CI2200000265',
        'Traoré',
        'Christelle Rénée ',
        NULL,
        'F',
        NULL,
        '21413'
    ),
    (
        'CI2200000259',
        'CI2200000266',
        'Vanon',
        'Teatoh Paul ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000260',
        'CI2200000267',
        'Yao',
        'Kouakou Brice ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000261',
        'CI2200000268',
        'Yao',
        'Kouakou Patrick Olivier',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000262',
        'CI2200000269',
        'Yapi',
        'Désiré Isaac ',
        NULL,
        'M',
        NULL,
        '21413'
    ),
    (
        'CI2200000263',
        'CI2200000270',
        'Aboulé',
        'Koko Jeanne-d\'Arc Ariane ',
        NULL,
        'F',
        NULL,
        '21514'
    ),
    (
        'CI0111272399',
        'CI2200000271',
        'Agnaramon',
        'Boris Carnot ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000264',
        'CI2200000272',
        'Amon',
        'Serge Kevin ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000265',
        'CI2200000273',
        'Angora',
        'Loic Sosthène Kholou',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000266',
        'CI2200000274',
        'Atché',
        'Aka Henry Jacques',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000267',
        'CI2200000275',
        'Bakayoko',
        'Mamadou  ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000268',
        'CI2200000276',
        'Bamba',
        'Moussa  ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000269',
        'CI2200000277',
        'Blague',
        'Segui Noel ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000270',
        'CI2200000278',
        'Blé',
        'Annoh Désiré P.C',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000271',
        'CI2200000279',
        'Bongo',
        'Murielle  ',
        NULL,
        'F',
        NULL,
        '21514'
    ),
    (
        'CI2200000272',
        'CI2200000280',
        'Botti',
        'Billy Aymeric ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000273',
        'CI2200000281',
        'Coulibaly',
        'Bassiata  ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000274',
        'CI2200000282',
        'Coulibaly',
        'Zié Abou ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000275',
        'CI2200000283',
        'Dibi',
        'Bi Kavola Augustin',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000276',
        'CI2200000284',
        'Doh',
        'Stéphane Isaacs ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI0108207902',
        'CI2200000285',
        'Ebe',
        'Gbebi Alex Auguste',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000277',
        'CI2200000286',
        'Frondo',
        'Jean Daniel ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000278',
        'CI2200000287',
        'Gueu',
        'Loua Alexis ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000279',
        'CI2200000288',
        'Kangni',
        'Joel Kevin ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000280',
        'CI2200000289',
        'Kango',
        'Dioulo Etser Emmanuel',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000281',
        'CI2200000290',
        'Kessé',
        'Pote Senaho Brice Cyriaque',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI0110242904',
        'CI2200000291',
        'Konan',
        'Yao Franck ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000282',
        'CI2200000292',
        'Koua',
        'Valdez Edmon Saturnin',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000283',
        'CI2200000293',
        'Kouadio',
        'Samou Moussa ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000284',
        'CI2200000294',
        'Kouamé',
        'Franck Didier ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000285',
        'CI2200000295',
        'Kouassi',
        'Jean-Armel  ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI0108212628',
        'CI2200000296',
        'Koulaté',
        'Douai Yves-Alain ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000286',
        'CI2200000297',
        'Koutene',
        'Yann Teddy ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000287',
        'CI2200000298',
        'Moegne',
        'Almedine Abdallah ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000288',
        'CI2200000299',
        'Oka',
        'Jean-Luc  ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000289',
        'CI2200000300',
        'Touré',
        'Makoko Madou ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000290',
        'CI2200000301',
        'Tra',
        'Lou T. Marie-Colombe A',
        NULL,
        'F',
        NULL,
        '21514'
    ),
    (
        'CI2200000291',
        'CI2200000302',
        'Traoré',
        'Mohamed  ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000292',
        'CI2200000303',
        'Zézé',
        'Séri Joseph-Désiré ',
        NULL,
        'M',
        NULL,
        '21514'
    ),
    (
        'CI2200000293',
        'CI2200000304',
        'Adomon',
        'Anongba Félix ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI0112272423',
        'CI2200000305',
        'Atta',
        'Amoan Aurélie Nadia',
        NULL,
        'F',
        NULL,
        '21615'
    ),
    (
        'CI2200000294',
        'CI2200000306',
        'Basse',
        'Paul  ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000295',
        'CI2200000307',
        'Cissé',
        'Abdoul Bamory ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000296',
        'CI2200000308',
        'Cissoko',
        'Abdel Aziz ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000297',
        'CI2200000309',
        'Coulibaly',
        'Nanleho Ismael ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000298',
        'CI2200000310',
        'Diahou',
        'Chiayé Marie Christelle',
        NULL,
        'F',
        NULL,
        '21615'
    ),
    (
        'CI2200000299',
        'CI2200000311',
        'Djidji',
        'Kadjo Dieudonné Jean-Jacques',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000300',
        'CI2200000312',
        'Duffi',
        'Konan Ismael ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000301',
        'CI2200000313',
        'Ehora',
        'Djaky Ange Michael',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000302',
        'CI2200000314',
        'Ehouman',
        'Anne Audrey ',
        NULL,
        'F',
        NULL,
        '21615'
    ),
    (
        'CI2200000303',
        'CI2200000315',
        'Fodio',
        'Abo Yao Désiré',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000304',
        'CI2200000316',
        'Gayé',
        'Mehibo Sylvestre ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000305',
        'CI2200000317',
        'Hoba',
        'Stephane Arnaud ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI0111272412',
        'CI2200000318',
        'Karidioula',
        'Homar  ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000306',
        'CI2200000319',
        'Kouadio',
        'Djè Dominique ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000307',
        'CI2200000320',
        'Kouakou',
        'Koffi Yves Forrest',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000308',
        'CI2200000321',
        'Kouamé',
        'Animand Marc Wilfried',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000309',
        'CI2200000322',
        'Kouassi',
        'Akoupo Joel P.',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000310',
        'CI2200000323',
        'Kouassi',
        'Kouakou Jocelin ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI0112272435',
        'CI2200000324',
        'Kra',
        'Yao Ghislain ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000311',
        'CI2200000325',
        'Lavri',
        'Djava Aristide Alain',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI0111272409',
        'CI2200000326',
        'Mohamed',
        'Ibrahim Charles ',
        NULL,
        'M',
        NULL,
        '21615'
    ),
    (
        'CI2200000312',
        'CI2200000327',
        'Traoré',
        'N\'nan Aïcha Jocelyne',
        NULL,
        'F',
        NULL,
        '21716'
    ),
    (
        'CI2200000313',
        'CI2200000328',
        'Coulibaly',
        'Myriam  ',
        NULL,
        'F',
        NULL,
        '21918'
    ),
    (
        'CI2200000314',
        'CI2200000329',
        'Diarrassouba',
        'Mamadou  ',
        NULL,
        'M',
        NULL,
        '21918'
    ),
    (
        'CI0112272443',
        'CI2200000330',
        'Ouattara',
        'Dramane  ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'CI0113273793',
        'CI2200000331',
        'Ouattara',
        'Zié Alhassane ',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'CI2200000315',
        'CI2200000332',
        'Seye',
        'Abdoul Kader Cédric',
        NULL,
        'M',
        NULL,
        '22019'
    ),
    (
        'CI2200000316',
        'CI2200000333',
        'Kouassi',
        'Oumar Ouattara ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'CI2200000317',
        'CI2200000334',
        'Touré',
        'William Benjamin-Noel ',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'CI2200000318',
        'CI2200000335',
        'Yao',
        'Josué Kouakou JeanPierre',
        NULL,
        'M',
        NULL,
        '22221'
    ),
    (
        'CI2200000319',
        'CI2200000336',
        'Atsé',
        'Moyé Anicet ',
        NULL,
        'M',
        NULL,
        '22322'
    ),
    (
        'CI2200000320',
        'CI2200000337',
        'Kaboré',
        'Emmanuel  ',
        NULL,
        'M',
        NULL,
        '22423'
    ),
    (
        'CI2200000321',
        'CI2200000338',
        'N\'guessan',
        'Aurore  ',
        NULL,
        'F',
        NULL,
        '22423'
    ),
    (
        'CI0113272684',
        'CI2200000339',
        'Coulibaly',
        'Sanga Narcisse ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'CI2200000322',
        'CI2200000340',
        'Kouadio',
        'Yao-Elysé Vedrine ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'CI0115301658',
        'CI2200000341',
        'Kouassi',
        'Jean Emmanuel ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'CI2200000323',
        'CI2200000342',
        'Yao',
        'Kouamé Elie-Noel ',
        NULL,
        'M',
        NULL,
        '22524'
    ),
    (
        'CI0113273198',
        'CI2200000343',
        'Rajaonarifetra',
        'Tony Andriamahandry Manohisoa',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'CI2200000324',
        'COUA0404990001',
        'Coulibaly',
        'Aminatou  ',
        NULL,
        'F',
        NULL,
        '22625'
    ),
    (
        'CI0106187064',
        'DEGG2506030001',
        'Degny',
        'Gilles Alfred Emmanuel',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'KEUF2403950001',
        'DIAY0801030001',
        'Diahou',
        'Yapo Charles-Emmanuel ',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'CI0112272430',
        'KOBT1112030001',
        'Kobenan',
        'Tamyao Moye JeanBaptiste',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'CI0115301569',
        'KOFA2802040001',
        'Koffi',
        'Amonnin Daniel Elie',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'CI0112272431',
        'KOUC3001030002',
        'Koutoua',
        'Christopher Isaac William',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'CI0113272986',
        'NIAN2010020001',
        'Niamké',
        'N\'Dédé Ange Joseph',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'CI0113273196',
        'NKUS2509030001',
        'Nkurikiyé',
        'Shime Don Divin',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'CI2200000325',
        'OULP1309030001',
        'Oulaï',
        'Paul-Ivan Yann Idriss',
        NULL,
        'M',
        NULL,
        '22625'
    ),
    (
        'CI2200000326',
        'SEKT1011030002',
        'Sékongo',
        'Tchéfigué Sherazade Gaëlle',
        NULL,
        'F',
        NULL,
        '22625'
    ),
    (
        'THIR2401050001',
        'THIR2401050001',
        'Thio',
        'Ramatien Latyfa ',
        NULL,
        'F',
        NULL,
        '22625'
    );

-- --------------------------------------------------------

--
-- Structure de la table `evaluations_rapports`
--

DROP TABLE IF EXISTS `evaluations_rapports`;

CREATE TABLE IF NOT EXISTS `evaluations_rapports` (
    `id_evaluation` int NOT NULL AUTO_INCREMENT,
    `id_rapport` int NOT NULL,
    `id_evaluateur` int NOT NULL,
    `decision_evaluation` enum('valider', 'rejeter') DEFAULT NULL,
    `commentaire` text,
    `date_evaluation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` datetime DEFAULT NULL,
    PRIMARY KEY (`id_evaluation`),
    KEY `id_evaluateur` (`id_evaluateur`),
    KEY `id_rapport` (`id_rapport`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `evaluer`
--

DROP TABLE IF EXISTS `evaluer`;

CREATE TABLE IF NOT EXISTS `evaluer` (
    `num_etudiant` varchar(25) NOT NULL,
    `num_jury` int NOT NULL,
    `id_critere` varchar(2) NOT NULL,
    `date_eval` date NOT NULL,
    `note` double NOT NULL,
    PRIMARY KEY (
        `num_etudiant`,
        `num_jury`,
        `id_critere`
    ),
    KEY `id_critere` (`id_critere`),
    KEY `num_jury` (`num_jury`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `filiere`
--

DROP TABLE IF EXISTS `filiere`;

CREATE TABLE IF NOT EXISTS `filiere` (
    `id_filiere` int NOT NULL AUTO_INCREMENT,
    `lib_filiere` varchar(100) NOT NULL,
    PRIMARY KEY (`id_filiere`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `fonction`
--

DROP TABLE IF EXISTS `fonction`;

CREATE TABLE IF NOT EXISTS `fonction` (
    `id_fonction` varchar(2) NOT NULL,
    `lib_fonction` varchar(100) NOT NULL,
    `origine_entreprise` tinyint(1) DEFAULT NULL,
    PRIMARY KEY (`id_fonction`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `fonction`
--

INSERT INTO
    `fonction` (
        `id_fonction`,
        `lib_fonction`,
        `origine_entreprise`
    )
VALUES ('AU', 'Autre', NULL),
    (
        'CC',
        'Chargé de communication',
        NULL
    ),
    (
        'CD',
        'Chef de département',
        NULL
    ),
    ('CP', 'Chef de projet', NULL),
    (
        'DG',
        'Directeur général',
        NULL
    ),
    (
        'DL',
        'Directeur de laboratoire',
        NULL
    ),
    (
        'DP',
        'Directeur pédagogique',
        NULL
    ),
    (
        'DR',
        'Directeur de recherche',
        NULL
    ),
    (
        'DT',
        'Directeut technique',
        NULL
    ),
    ('DU', 'Directeur Ufr', NULL),
    ('NA', 'Non attribue', NULL),
    (
        'RF',
        'Responsable de filière',
        NULL
    ),
    (
        'RN',
        'Responsable de niveau',
        NULL
    ),
    (
        'SP',
        'Sécretaire principal',
        NULL
    ),
    ('VP', 'Vice Président', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `fonctionnalites`
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
) ENGINE = InnoDB AUTO_INCREMENT = 126 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `fonctionnalites`
--

INSERT INTO
    `fonctionnalites` (
        `id_fonctionnalite`,
        `id_categorie`,
        `code_fonctionnalite`,
        `lib_fonctionnalite`,
        `label_fonctionnalite`,
        `description_fonctionnalite`,
        `url_fonctionnalite`,
        `icone_fonctionnalite`,
        `ordre_fonctionnalite`,
        `est_sous_page`,
        `page_parente`,
        `actif`,
        `date_creation`
    )
VALUES (
        2,
        15,
        'DASH_ENSEIGNANT',
        'Dashboard Enseignant',
        'Mon espace enseignant',
        NULL,
        '?page=dashboard_enseignant',
        'fas fa-chalkboard-teacher',
        1,
        1,
        'COM_ESPACES',
        1,
        '2026-01-05 22:57:10'
    ),
    (
        21,
        15,
        'CR_HUB',
        'Comptes Rendus',
        'Mes comptes rendus',
        NULL,
        '?page=redaction_compte_rendu',
        'fas fa-pen',
        1,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        22,
        15,
        'CR_REDACTION',
        'Rédaction',
        'Rédiger CR',
        NULL,
        '?page=redaction_compte_rendu',
        'fas fa-edit',
        2,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        23,
        15,
        'CR_BROUILLONS',
        'Brouillons',
        'Mes brouillons',
        NULL,
        '?page=redaction_compte_rendu&action=brouillons',
        'fas fa-save',
        3,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        24,
        15,
        'CR_ARCHIVES',
        'Archives',
        'CR archivés',
        NULL,
        '?page=redaction_compte_rendu&action=archives',
        'fas fa-archive',
        4,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        30,
        16,
        'PARAM_HUB',
        'Paramètres Généraux',
        'Paramètres Géneraux',
        '',
        '?page=parametres_generaux',
        'fas fa-cogs',
        1,
        1,
        'ADM_PARAMETRAGE',
        1,
        '2026-01-05 22:57:10'
    ),
    (
        31,
        16,
        'PARAM_ACTIONS',
        'Actions Système',
        'Actions',
        NULL,
        '?page=parametres_generaux&action=actions',
        'fas fa-bolt',
        2,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        32,
        16,
        'PARAM_ANNEES',
        'Années Académiques',
        'Années',
        NULL,
        '?page=parametres_generaux&action=annees_academiques',
        'fas fa-calendar',
        3,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        33,
        16,
        'PARAM_CRITERES',
        'Critères Évaluation',
        'Critères',
        NULL,
        '?page=parametres_generaux&action=criteres_evaluation',
        'fas fa-list-ol',
        4,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        34,
        16,
        'PARAM_ECUE',
        'ECUE',
        'Éléments UE',
        NULL,
        '?page=parametres_generaux&action=ecue',
        'fas fa-puzzle-piece',
        5,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        35,
        16,
        'PARAM_ENTREPRISES',
        'Entreprises',
        'Base entreprises',
        NULL,
        '?page=parametres_generaux&action=entreprises',
        'fas fa-building',
        6,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        36,
        16,
        'PARAM_FONCTIONS',
        'Fonctions Personnel',
        'Fonctions',
        NULL,
        '?page=parametres_generaux&action=fonctions',
        'fas fa-briefcase',
        7,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        37,
        16,
        'PARAM_FONC_USER',
        'Fonctions Utilisateurs',
        'Rôles',
        NULL,
        '?page=parametres_generaux&action=fonction_utilisateur',
        'fas fa-user-tag',
        8,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        38,
        16,
        'PARAM_ATTRIB',
        'Gestion Attributions',
        'Permissions',
        NULL,
        '?page=parametres_generaux&action=gestion_attribution',
        'fas fa-key',
        9,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        39,
        16,
        'PARAM_GRADES',
        'Grades Enseignants',
        'Grades',
        NULL,
        '?page=parametres_generaux&action=grades',
        'fas fa-medal',
        10,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        40,
        16,
        'PARAM_MESSAGES',
        'Messages Système',
        'Messages',
        NULL,
        '?page=parametres_generaux&action=messages',
        'fas fa-envelope',
        11,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        41,
        16,
        'PARAM_NIV_ACCES',
        'Niveaux Accès',
        'Accès',
        NULL,
        '?page=parametres_generaux&action=niveaux_acces',
        'fas fa-lock',
        12,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        42,
        16,
        'PARAM_NIV_APPRO',
        'Niveaux Approbation',
        'Workflow',
        NULL,
        '?page=parametres_generaux&action=niveaux_approbation',
        'fas fa-sitemap',
        13,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        43,
        16,
        'PARAM_NIV_ETUDE',
        'Niveaux Étude',
        'M1/M2',
        NULL,
        '?page=parametres_generaux&action=niveaux_etude',
        'fas fa-layer-group',
        14,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        44,
        16,
        'PARAM_SALLES',
        'Salles',
        'Salles soutenance',
        NULL,
        '?page=parametres_generaux&action=salles',
        'fas fa-door-open',
        15,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        45,
        16,
        'PARAM_SEMESTRES',
        'Semestres',
        'Semestres',
        NULL,
        '?page=parametres_generaux&action=semestres',
        'fas fa-calendar-week',
        16,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        46,
        16,
        'PARAM_SPECIALITES',
        'Spécialités',
        'Spécialités',
        NULL,
        '?page=parametres_generaux&action=specialites',
        'fas fa-graduation-cap',
        17,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        47,
        16,
        'PARAM_STATUT_JURY',
        'Statuts Jury',
        'Rôles jury',
        NULL,
        '?page=parametres_generaux&action=statut_jury',
        'fas fa-user-shield',
        18,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        48,
        16,
        'PARAM_TRAITEMENTS',
        'Traitements Menu',
        'Menu actuel',
        NULL,
        '?page=parametres_generaux&action=traitements',
        'fas fa-bars',
        19,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        49,
        16,
        'PARAM_UE',
        'UE',
        'Unités Enseignement',
        NULL,
        '?page=parametres_generaux&action=ue',
        'fas fa-book',
        20,
        1,
        NULL,
        1,
        '2026-01-05 22:57:10'
    ),
    (
        50,
        16,
        'SYS_UTILISATEURS',
        'Gestion Utilisateurs',
        'Utilisateurs',
        NULL,
        '?page=gestion_utilisateurs',
        'fas fa-users-cog',
        1,
        1,
        'ADM_SECURITE',
        1,
        '2026-01-05 22:57:10'
    ),
    (
        51,
        16,
        'SYS_AUDIT',
        'Piste Audit',
        'Journal audit',
        NULL,
        '?page=piste_audit',
        'fas fa-history',
        2,
        1,
        'ADM_SECURITE',
        1,
        '2026-01-05 22:57:10'
    ),
    (
        52,
        16,
        'SYS_BACKUP',
        'Sauvegarde/Restauration',
        'Backup',
        '',
        '?page=sauvegarde_restauration',
        'fas fa-database',
        3,
        1,
        'ADM_SECURITE',
        1,
        '2026-01-05 22:57:10'
    ),
    (
        55,
        16,
        'SYS_HISTORIQUE',
        'Historique et Archivage',
        'Import de donées',
        'Historique et archivage des données',
        '?page=admin_historique',
        'fas fa-archive',
        4,
        1,
        'ADM_SECURITE',
        1,
        '2026-01-14 19:01:01'
    ),
    (
        73,
        25,
        'COM_ESPACES',
        'Espaces',
        'Espaces',
        '',
        '#',
        'fa-solid fa-chalkboard-user',
        50,
        0,
        NULL,
        1,
        '2026-01-24 22:09:02'
    ),
    (
        74,
        16,
        'ADM_DASHBOARD',
        'Dashboard',
        'Dashboard',
        '',
        '?page=dashboard',
        'fas fa-tachometer-alt',
        1,
        0,
        NULL,
        1,
        '2026-01-24 22:09:02'
    ),
    (
        75,
        16,
        'ADM_PARAMETRAGE',
        'Paramétrage',
        'Paramétrage',
        '',
        '#',
        'fas fa-cogs',
        3,
        0,
        NULL,
        1,
        '2026-01-24 22:09:02'
    ),
    (
        76,
        16,
        'ADM_SECURITE',
        'Sécurité',
        'Sécurité',
        '',
        '#',
        'fas fa-shield-alt',
        4,
        0,
        NULL,
        1,
        '2026-01-24 22:09:02'
    ),
    (
        78,
        16,
        'ADM_REFERENTIEL',
        'Référentiel',
        'Référentiel',
        '',
        '#',
        'fas fa-id-card',
        5,
        0,
        NULL,
        1,
        '2026-01-24 22:09:02'
    ),
    (
        79,
        24,
        'PROFIL',
        'Mon Profil',
        'Mon profil',
        'Consulter et modifier mon profil utilisateur, changer mon mot de passe',
        '?page=profil',
        'fas fa-user-circle',
        1,
        0,
        NULL,
        1,
        '2026-01-26 12:04:38'
    ),
    (
        81,
        16,
        'PARAM_SPEC',
        'Spécifiques',
        'Paramètres Spécifiques',
        'Gestion des UE, ECUE, Salles et Menus',
        '?page=parametres_specifiques',
        'fas fa-sliders-h',
        3,
        1,
        'ADM_PARAMETRAGE',
        1,
        '2026-01-27 22:55:37'
    ),
    (
        91,
        14,
        'ETUD_RAPPORT',
        'Mes rapports',
        'Mes rapports',
        '',
        '?page=gestion_rapports',
        'fa-solid fa-file-pen',
        2,
        0,
        NULL,
        1,
        '2026-02-11 01:30:01'
    ),
    (
        93,
        13,
        'DASH_SCOLARITE',
        'Tableau de bord scolarité',
        'Tableau de bord scolarité',
        '',
        '?page=dashboard_scolarite',
        'fa-solid fa-gauge-high',
        1,
        0,
        NULL,
        1,
        '2026-02-11 01:36:48'
    ),
    (
        94,
        13,
        'SCOLA_GEST_ETUDIANT',
        'Gestion des étudiants',
        'Gestion des étudiants',
        '',
        '',
        'fas fa-school',
        2,
        0,
        NULL,
        1,
        '2026-02-11 01:38:13'
    ),
    (
        95,
        13,
        'MAJ_ETUDIANT',
        'Mise à jour étudiant',
        'Mise à jour étudiant',
        '',
        '?page=gestion_etudiants&action=ajouter_des_etudiants',
        'fa-solid fa-user-plus',
        1,
        1,
        'SCOLA_GEST_ETUDIANT',
        1,
        '2026-02-11 01:39:03'
    ),
    (
        96,
        13,
        'INSCRIPTION_ETUDIANT',
        'Inscription étudiant',
        'Inscription étudiant',
        '',
        '?page=gestion_scolarite',
        'fa-solid fa-money-bill',
        2,
        1,
        'SCOLA_GEST_ETUDIANT',
        1,
        '2026-02-11 01:41:15'
    ),
    (
        97,
        13,
        'MOYENNE_ETUDIANT',
        'Saisie des moyennes',
        'Saisie des moyennes',
        '',
        '?page=gestion_notes_evaluations',
        'fa-solid fa-keyboard',
        3,
        1,
        'SCOLA_GEST_ETUDIANT',
        1,
        '2026-02-11 01:45:15'
    ),
    (
        98,
        13,
        'SCOLA_GEST_CANDIDATURE',
        'Gestion des candidatures',
        'Gestion des candidatures',
        '',
        '',
        'fa-solid fa-folder-open',
        3,
        0,
        NULL,
        1,
        '2026-02-11 01:46:39'
    ),
    (
        99,
        13,
        'DOSSIER_CANDIDATURE',
        'Dossiers de candidatures',
        'Dossiers de candidatures',
        '',
        '?page=gestion_dossiers_candidatures',
        'fa-solid fa-folder',
        1,
        1,
        'SCOLA_GEST_CANDIDATURE',
        1,
        '2026-02-11 01:47:55'
    ),
    (
        100,
        13,
        'RECLAMATION_ETUDIANT',
        'Reclamations',
        'Reclamations',
        '',
        '?page=gestion_reclamations_scolarite',
        'fa-solid fa-circle-exclamation',
        2,
        1,
        'SCOLA_GEST_CANDIDATURE',
        1,
        '2026-02-11 01:49:26'
    ),
    (
        101,
        14,
        'ETU_CANDIDATURE',
        'Candidature',
        'Candidature',
        '',
        '?page=candidature_soutenance',
        'fa-solid fa-folder',
        1,
        0,
        NULL,
        1,
        '2026-02-11 01:54:25'
    ),
    (
        102,
        14,
        'ETU_RECLAMATION',
        'Reclamations',
        'Reclamations',
        '',
        '?page=gestion_reclamations',
        'fa-solid fa-circle-exclamation',
        3,
        0,
        NULL,
        1,
        '2026-02-11 01:56:23'
    ),
    (
        103,
        14,
        'ETU_CONSULTATION_CR',
        'Consultation du compte rendu',
        'Consultation du compte rendu',
        '',
        '?page=consultation_cr_etud',
        'fa-solid fa-newspaper',
        4,
        0,
        NULL,
        1,
        '2026-02-11 01:58:28'
    ),
    (
        104,
        15,
        'COM_DASHBOARD',
        'Tableau de bord commission',
        'Tableau de bord commission',
        '',
        '?page=dashboard_commission',
        'fa-solid fa-gauge-high',
        1,
        0,
        NULL,
        1,
        '2026-02-11 13:43:57'
    ),
    (
        105,
        15,
        'COM_GEST_RAPPORT',
        'Gestion des rapports de stage',
        'Gestion des rapports de stage',
        '',
        '',
        'fa-solid fa-folder-open',
        2,
        0,
        NULL,
        1,
        '2026-02-11 13:46:20'
    ),
    (
        106,
        15,
        'COM_RECEPTION_RAPPORT',
        'Reception des rapports de stage',
        'Reception des rapports de stage',
        '',
        '?page=reception_rapport_com',
        'fa-solid fa-inbox',
        1,
        1,
        'COM_GEST_RAPPORT',
        1,
        '2026-02-11 13:48:25'
    ),
    (
        107,
        15,
        'ANA_APP_RAPPORT',
        'analyse et approbation des rapports',
        'analyse et approbation des rapports',
        '',
        '?page=evaluation_dossiers',
        'fa-solid fa-check-to-slot',
        2,
        1,
        'COM_GEST_RAPPORT',
        1,
        '2026-02-11 14:19:22'
    ),
    (
        108,
        15,
        'SUIVI_VALIDATION_COM',
        'Suivi d\'avancement',
        'Suivi d\'avancement',
        '',
        '?page=processus_validation',
        'fa-solid fa-stamp',
        3,
        1,
        'COM_GEST_RAPPORT',
        1,
        '2026-02-11 14:21:26'
    ),
    (
        109,
        15,
        'COM_REDACTION_CR',
        'redaction du CR',
        'redaction du CR',
        '',
        '?page=redaction_compte_rendu',
        'fa-solid fa-file-pen',
        3,
        0,
        NULL,
        1,
        '2026-02-11 14:26:39'
    ),
    (
        110,
        17,
        'SOUT_COMPOS_JURY',
        'Composition de jury',
        'Composition de jury',
        '',
        '?page=programmation_soutenance',
        'fa-solid fa-users-line',
        1,
        0,
        NULL,
        1,
        '2026-02-11 14:36:25'
    ),
    (
        111,
        17,
        'SOUT_EVALUATION',
        'Evaluation Soutenance',
        'Evaluation Soutenance',
        '',
        '?page=evaluation_soutenance',
        'fa-solid fa-pencil',
        2,
        0,
        NULL,
        1,
        '2026-02-11 14:38:37'
    ),
    (
        112,
        17,
        'SOUT_EDITION_BULLETIN',
        'Edition des bulletins',
        'Edition des bulletins',
        '',
        '?page=edition_bulletin',
        'fa-solid fa-file-circle-check',
        3,
        0,
        NULL,
        1,
        '2026-02-11 14:43:15'
    ),
    (
        113,
        16,
        'ADMIN_ANNEE_ACADEMIQUE',
        'Ouverture/Fermeture AC',
        'Ouverture/Fermeture AC',
        '',
        '?page=parametres_generaux&action=annees_academiques',
        'fa-solid fa-calendar-day',
        2,
        0,
        NULL,
        1,
        '2026-02-11 14:58:39'
    ),
    (
        114,
        16,
        'MAJ_ENSEIGNANT',
        'Mise a jour enseignant',
        'Mise a jour enseignant',
        '',
        '?page=maj_enseignant',
        'fa-solid fa-person-chalkboard',
        1,
        1,
        'ADM_REFERENTIEL',
        1,
        '2026-02-11 15:01:43'
    ),
    (
        115,
        16,
        'MAJ_PERSONNEL_ADMIN',
        'mise a jour personnel administratif',
        'mise a jour personnel administratif',
        '',
        '?page=maj_personnel_admin',
        'fa-solid fa-user-tie',
        2,
        1,
        'ADM_REFERENTIEL',
        1,
        '2026-02-11 15:02:57'
    ),
    (
        116,
        25,
        'ENS_DASHBOARD',
        'Tableau de bord enseignant',
        'Tableau de bord enseignant',
        '',
        '?page=tableau_bord_enseignant',
        'fa-solid fa-gauge-high',
        1,
        0,
        NULL,
        1,
        '2026-02-20 22:44:42'
    ),
    (
        117,
        25,
        'repertoire_enseignant',
        'Repertoire documents',
        'Repertoire documents',
        'Consultation des rapports, comptes-rendus et memoires rattaches a l enseignant',
        '?page=repertoire_enseignant',
        'fas fa-folder-open',
        10,
        0,
        NULL,
        1,
        '2026-02-27 18:49:53'
    ),
    (
        124,
        25,
        'ENV_ENSEIGNANT',
        'Programmation Enseignant',
        'Programmation Enseignant',
        '',
        '?page=programmation_ens',
        'fa-solid fa-clock',
        2,
        0,
        NULL,
        1,
        '2026-03-07 20:50:48'
    ),
    (
        125,
        13,
        'SCOLARITE',
        'Mise en ligne memoire',
        'Mise en ligne memoire',
        '',
        '?page=mise_en_ligne_memoire',
        'fa-solid fa-book',
        4,
        0,
        NULL,
        1,
        '2026-03-07 22:40:21'
    );

-- --------------------------------------------------------

--
-- Structure de la table `frais_inscription`
--

DROP TABLE IF EXISTS `frais_inscription`;

CREATE TABLE IF NOT EXISTS `frais_inscription` (
    `id_niv_etude` varchar(2) NOT NULL,
    `id_annee_acad` int NOT NULL,
    `montant` decimal(10, 2) NOT NULL,
    PRIMARY KEY (
        `id_niv_etude`,
        `id_annee_acad`
    ),
    KEY `idx_frais_inscription_annee` (`id_annee_acad`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `frais_inscription`
--

INSERT INTO
    `frais_inscription` (
        `id_niv_etude`,
        `id_annee_acad`,
        `montant`
    )
VALUES ('M2', 22221, 950.00),
    ('M2', 22322, 950.00),
    ('M2', 22423, 950.00),
    ('M2', 22524, 950.00),
    ('M2', 22625, 950.00);

-- --------------------------------------------------------

--
-- Structure de la table `genre`
--

DROP TABLE IF EXISTS `genre`;

CREATE TABLE IF NOT EXISTS `genre` (
    `id_genre` char(1) NOT NULL,
    `libelle_genre` varchar(20) NOT NULL,
    PRIMARY KEY (`id_genre`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `genre`
--

INSERT INTO
    `genre` (`id_genre`, `libelle_genre`)
VALUES ('F', 'Féminin'),
    ('M', 'Masculin'),
    ('N', 'Neutre');

-- --------------------------------------------------------

--
-- Structure de la table `grade`
--

DROP TABLE IF EXISTS `grade`;

CREATE TABLE IF NOT EXISTS `grade` (
    `id_grade` varchar(2) NOT NULL,
    `lib_grade` varchar(50) NOT NULL,
    PRIMARY KEY (`id_grade`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `grade`
--

INSERT INTO
    `grade` (`id_grade`, `lib_grade`)
VALUES ('AS', 'Assistant'),
    ('MA', 'Maître assistant'),
    ('MC', 'Maître de conférence'),
    ('PT', 'Professeur titulaire');

-- --------------------------------------------------------

--
-- Structure de la table `groupe_utilisateur`
--

DROP TABLE IF EXISTS `groupe_utilisateur`;

CREATE TABLE IF NOT EXISTS `groupe_utilisateur` (
    `id_GU` int NOT NULL AUTO_INCREMENT,
    `lib_GU` varchar(100) NOT NULL,
    `id_type_utilisateur` int DEFAULT NULL,
    PRIMARY KEY (`id_GU`),
    KEY `idx_groupe_utilisateur_type` (`id_type_utilisateur`)
) ENGINE = InnoDB AUTO_INCREMENT = 14 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `groupe_utilisateur`
--

INSERT INTO
    `groupe_utilisateur` (
        `id_GU`,
        `lib_GU`,
        `id_type_utilisateur`
    )
VALUES (5, 'Administrateur', 4),
    (6, 'Secretaire', 4),
    (
        7,
        'Chargée de communication',
        4
    ),
    (8, 'Responsable scolarité', 4),
    (9, 'Responsable Filière', 5),
    (10, 'Responsable niveau', 5),
    (
        11,
        'commission de validation',
        5
    ),
    (
        12,
        'Enseignant sans responsabilité administrative',
        6
    ),
    (13, 'Etudiant', 7);

-- --------------------------------------------------------

--
-- Structure de la table `informations_stage`
--

DROP TABLE IF EXISTS `informations_stage`;

CREATE TABLE IF NOT EXISTS `informations_stage` (
    `id_info_stage` int NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `id_entreprise` int NOT NULL,
    `date_debut_stage` date NOT NULL,
    `date_fin_stage` date NOT NULL,
    `sujet_stage` text NOT NULL,
    `id_maitre_stage` varchar(15) NOT NULL,
    PRIMARY KEY (`id_info_stage`),
    KEY `num_etu` (`num_etu`),
    KEY `id_entreprise` (`id_entreprise`),
    KEY `id_maitre_stage` (`id_maitre_stage`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions`
--

DROP TABLE IF EXISTS `inscriptions`;

CREATE TABLE IF NOT EXISTS `inscriptions` (
    `num_carte_etud` varchar(25) NOT NULL,
    `id_annee_acad` int NOT NULL,
    `num_versement` int NOT NULL DEFAULT '1',
    `date_inscription` datetime DEFAULT NULL,
    `date_versement` datetime DEFAULT CURRENT_TIMESTAMP,
    `id_niv_etude` varchar(2) DEFAULT NULL,
    `montant_verser` decimal(10, 2) NOT NULL DEFAULT '0.00',
    `methode_paiement` varchar(2) DEFAULT NULL,
    `num_piece_mp` varchar(100) DEFAULT NULL,
    `solde` decimal(10, 2) NOT NULL DEFAULT '0.00',
    `fiche_inscription` varchar(255) DEFAULT NULL COMMENT 'Chemin vers le fichier de la fiche d''inscription (PDF ou image)',
    PRIMARY KEY (
        `num_carte_etud`,
        `id_annee_acad`,
        `num_versement`
    ),
    KEY `idx_inscriptions_annee` (`id_annee_acad`),
    KEY `idx_inscriptions_niveau` (`id_niv_etude`),
    KEY `idx_inscriptions_mode_paiement` (`methode_paiement`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `inscriptions`
--

INSERT INTO
    `inscriptions` (
        `num_carte_etud`,
        `id_annee_acad`,
        `num_versement`,
        `date_inscription`,
        `date_versement`,
        `id_niv_etude`,
        `montant_verser`,
        `methode_paiement`,
        `num_piece_mp`,
        `solde`,
        `fiche_inscription`
    )
VALUES (
        '071226195/KOUA',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        '071226195/KOUA',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        425.00,
        NULL
    ),
    (
        '071226195/KOUA',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        175.00,
        NULL
    ),
    (
        '071226195/KOUA',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        175.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '131202494/BROU',
        22423,
        1,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        450.00,
        'ES',
        NULL,
        575.00,
        NULL
    ),
    (
        '131202494/BROU',
        22423,
        2,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        100.00,
        'ES',
        NULL,
        475.00,
        NULL
    ),
    (
        '131202494/BROU',
        22423,
        3,
        '0002-12-24 00:00:00',
        '0002-12-24 00:00:00',
        'M2',
        175.00,
        'ES',
        NULL,
        300.00,
        NULL
    ),
    (
        '131202494/BROU',
        22423,
        4,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '161213861/CISS',
        22625,
        1,
        '2026-03-10 22:17:14',
        '2026-03-10 22:17:14',
        'M2',
        450.00,
        'Es',
        '',
        500.00,
        NULL
    ),
    (
        '162004707/YAO ',
        22524,
        1,
        '2012-12-24 00:00:00',
        '2012-12-24 00:00:00',
        'M2',
        500.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        '162004707/YAO ',
        22524,
        2,
        '0002-05-25 00:00:00',
        '0002-05-25 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        '162004707/YAO ',
        22524,
        3,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '163118420/DJEC',
        22423,
        1,
        '0001-01-23 00:00:00',
        '0001-01-23 00:00:00',
        'M2',
        500.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        '163118420/DJEC',
        22423,
        2,
        '0003-01-23 00:00:00',
        '0003-01-23 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        250.00,
        NULL
    ),
    (
        '163118420/DJEC',
        22423,
        3,
        '0001-01-24 00:00:00',
        '0001-01-24 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '163304342/TRAB',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        750.00,
        NULL
    ),
    (
        '163304342/TRAB',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        450.00,
        NULL
    ),
    (
        '163304342/TRAB',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        200.00,
        NULL
    ),
    (
        '163304342/TRAB',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '164012210/LAGO',
        22423,
        1,
        '0002-02-23 00:00:00',
        '0002-02-23 00:00:00',
        'M2',
        600.00,
        'ES',
        NULL,
        425.00,
        NULL
    ),
    (
        '164012210/LAGO',
        22423,
        2,
        '0003-02-23 00:00:00',
        '0003-02-23 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        200.00,
        NULL
    ),
    (
        '164012210/LAGO',
        22423,
        3,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '164201727/KINH',
        22423,
        1,
        '2010-03-22 00:00:00',
        '2010-03-22 00:00:00',
        'M2',
        350.00,
        'ES',
        NULL,
        675.00,
        NULL
    ),
    (
        '164201727/KINH',
        22423,
        2,
        '0001-03-23 00:00:00',
        '0001-03-23 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        375.00,
        NULL
    ),
    (
        '164201727/KINH',
        22423,
        3,
        '0003-03-23 00:00:00',
        '0003-03-23 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        175.00,
        NULL
    ),
    (
        '164201727/KINH',
        22423,
        4,
        '0001-03-24 00:00:00',
        '0001-03-24 00:00:00',
        'M2',
        175.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '165101454/GNOG',
        22322,
        1,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        375.00,
        'ES',
        NULL,
        650.00,
        NULL
    ),
    (
        '165101454/GNOG',
        22322,
        2,
        '0003-07-22 00:00:00',
        '0003-07-22 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        400.00,
        NULL
    ),
    (
        '165101454/GNOG',
        22322,
        3,
        '0008-12-21 00:00:00',
        '0008-12-21 00:00:00',
        'M2',
        400.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '171201836/BAKA',
        22423,
        1,
        '0001-01-23 00:00:00',
        '0001-01-23 00:00:00',
        'M2',
        500.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        '171201836/BAKA',
        22423,
        2,
        '2011-01-23 00:00:00',
        '2011-01-23 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        '171201836/BAKA',
        22423,
        3,
        '0001-01-24 00:00:00',
        '0001-01-24 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '171203302/COUL',
        22423,
        1,
        '0001-01-23 00:00:00',
        '0001-01-23 00:00:00',
        'M2',
        500.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        '171203302/COUL',
        22423,
        2,
        '2011-01-23 00:00:00',
        '2011-01-23 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        '171203302/COUL',
        22423,
        3,
        '0001-01-24 00:00:00',
        '0001-01-24 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '171205456/FAMI',
        22524,
        1,
        '2010-11-24 00:00:00',
        '2010-11-24 00:00:00',
        'M2',
        500.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        '171205456/FAMI',
        22524,
        2,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        '171205456/FAMI',
        22524,
        3,
        '0001-10-25 00:00:00',
        '0001-10-25 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '171211638/OUAT',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        375.00,
        'ES',
        NULL,
        650.00,
        NULL
    ),
    (
        '171211638/OUAT',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        350.00,
        NULL
    ),
    (
        '171211638/OUAT',
        22524,
        3,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        350.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '171211865/OUAT',
        22423,
        1,
        '2010-01-22 00:00:00',
        '2010-01-22 00:00:00',
        'M2',
        450.00,
        'ES',
        NULL,
        575.00,
        NULL
    ),
    (
        '171211865/OUAT',
        22423,
        2,
        '0002-01-23 00:00:00',
        '0002-01-23 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        350.00,
        NULL
    ),
    (
        '171211865/OUAT',
        22423,
        3,
        '0003-01-23 00:00:00',
        '0003-01-23 00:00:00',
        'M2',
        150.00,
        'ES',
        NULL,
        200.00,
        NULL
    ),
    (
        '171211865/OUAT',
        22423,
        4,
        '0004-10-23 00:00:00',
        '0004-10-23 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '172202802/SOUL',
        22423,
        1,
        '2010-03-22 00:00:00',
        '2010-03-22 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        '172202802/SOUL',
        22423,
        2,
        '0001-03-23 00:00:00',
        '0001-03-23 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        425.00,
        NULL
    ),
    (
        '172202802/SOUL',
        22423,
        3,
        '0003-03-23 00:00:00',
        '0003-03-23 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        125.00,
        NULL
    ),
    (
        '172202802/SOUL',
        22423,
        4,
        '0001-03-24 00:00:00',
        '0001-03-24 00:00:00',
        'M2',
        125.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '172307062/YAO-',
        22423,
        1,
        '2010-03-22 00:00:00',
        '2010-03-22 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        775.00,
        NULL
    ),
    (
        '172307062/YAO-',
        22423,
        2,
        '0001-03-23 00:00:00',
        '0001-03-23 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        '172307062/YAO-',
        22423,
        3,
        '0003-03-23 00:00:00',
        '0003-03-23 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        '172307062/YAO-',
        22423,
        4,
        '0001-03-24 00:00:00',
        '0001-03-24 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '174023275/KOUA',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        '174023275/KOUA',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        175.00,
        'ES',
        NULL,
        550.00,
        NULL
    ),
    (
        '174023275/KOUA',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        350.00,
        'ES',
        NULL,
        200.00,
        NULL
    ),
    (
        '174023275/KOUA',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '174030395/N\'GU',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        '174030395/N\'GU',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        425.00,
        NULL
    ),
    (
        '174030395/N\'GU',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        175.00,
        NULL
    ),
    (
        '174030395/N\'GU',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        175.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '175100747/BOKA',
        22423,
        1,
        '2010-01-22 00:00:00',
        '2010-01-22 00:00:00',
        'M2',
        550.00,
        'ES',
        NULL,
        475.00,
        NULL
    ),
    (
        '175100747/BOKA',
        22423,
        2,
        '0001-02-23 00:00:00',
        '0001-02-23 00:00:00',
        'M2',
        475.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '181201355/ANOM',
        22524,
        1,
        '2012-07-24 00:00:00',
        '2012-07-24 00:00:00',
        'M2',
        650.00,
        'ES',
        NULL,
        375.00,
        NULL
    ),
    (
        '181201355/ANOM',
        22524,
        2,
        '0001-09-25 00:00:00',
        '0001-09-25 00:00:00',
        'M2',
        375.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '181201526/ASSI',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        825.00,
        NULL
    ),
    (
        '181201526/ASSI',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        '181201526/ASSI',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        300.00,
        NULL
    ),
    (
        '181201526/ASSI',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '181206875/KADI',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        325.00,
        'ES',
        NULL,
        700.00,
        NULL
    ),
    (
        '181206875/KADI',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        400.00,
        'ES',
        NULL,
        300.00,
        NULL
    ),
    (
        '181206875/KADI',
        22524,
        3,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '181214844/YAO ',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        750.00,
        NULL
    ),
    (
        '181214844/YAO ',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        500.00,
        NULL
    ),
    (
        '181214844/YAO ',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        250.00,
        NULL
    ),
    (
        '181214844/YAO ',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '183302736/MALA',
        22524,
        1,
        '2012-12-24 00:00:00',
        '2012-12-24 00:00:00',
        'M2',
        500.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        '183302736/MALA',
        22524,
        2,
        '0002-05-25 00:00:00',
        '0002-05-25 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        '183302736/MALA',
        22524,
        3,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '184027328/LODI',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        775.00,
        NULL
    ),
    (
        '184027328/LODI',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        500.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        '184027328/LODI',
        22524,
        3,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '184907031/TOUR',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        800.00,
        NULL
    ),
    (
        '184907031/TOUR',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        575.00,
        NULL
    ),
    (
        '184907031/TOUR',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        350.00,
        'ES',
        NULL,
        225.00,
        NULL
    ),
    (
        '184907031/TOUR',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '193105002/COUL',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        '193105002/COUL',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        425.00,
        NULL
    ),
    (
        '193105002/COUL',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        200.00,
        NULL
    ),
    (
        '193105002/COUL',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '193202273/KOFF',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        '193202273/KOFF',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        425.00,
        NULL
    ),
    (
        '193202273/KOFF',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        225.00,
        NULL
    ),
    (
        '193202273/KOFF',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '194801187/SORO',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        350.00,
        'ES',
        NULL,
        675.00,
        NULL
    ),
    (
        '194801187/SORO',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        450.00,
        NULL
    ),
    (
        '194801187/SORO',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        225.00,
        NULL
    ),
    (
        '194801187/SORO',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '201205164/COUL',
        22524,
        1,
        '2012-12-24 00:00:00',
        '2012-12-24 00:00:00',
        'M2',
        525.00,
        'ES',
        NULL,
        500.00,
        NULL
    ),
    (
        '201205164/COUL',
        22524,
        2,
        '0002-05-25 00:00:00',
        '0002-05-25 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        250.00,
        NULL
    ),
    (
        '201205164/COUL',
        22524,
        3,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '203107762/DOUA',
        22524,
        1,
        '2012-12-24 00:00:00',
        '2012-12-24 00:00:00',
        'M2',
        500.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        '203107762/DOUA',
        22524,
        2,
        '0002-05-25 00:00:00',
        '0002-05-25 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        250.00,
        NULL
    ),
    (
        '203107762/DOUA',
        22524,
        3,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        '203123140/TRAO',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        '203123140/TRAO',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        475.00,
        NULL
    ),
    (
        '203123140/TRAO',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        225.00,
        NULL
    ),
    (
        '203123140/TRAO',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        125.00,
        'ES',
        NULL,
        100.00,
        NULL
    ),
    (
        'ADOL1109970001',
        22423,
        1,
        '0001-01-23 00:00:00',
        '0001-01-23 00:00:00',
        'M2',
        450.00,
        'ES',
        NULL,
        575.00,
        NULL
    ),
    (
        'ADOL1109970001',
        22423,
        2,
        '2011-01-23 00:00:00',
        '2011-01-23 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        'ADOL1109970001',
        22423,
        3,
        '0001-01-24 00:00:00',
        '0001-01-24 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'CI0114277408',
        22423,
        1,
        '0001-01-23 00:00:00',
        '0001-01-23 00:00:00',
        'M2',
        550.00,
        'ES',
        NULL,
        475.00,
        NULL
    ),
    (
        'CI0114277408',
        22423,
        2,
        '2011-01-23 00:00:00',
        '2011-01-23 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        200.00,
        NULL
    ),
    (
        'CI0114277408',
        22423,
        3,
        '0001-01-24 00:00:00',
        '0001-01-24 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'CI0116311179',
        22221,
        1,
        '0008-12-21 00:00:00',
        '0008-12-21 00:00:00',
        'M2',
        450.00,
        'ES',
        NULL,
        500.00,
        NULL
    ),
    (
        'CI0116311179',
        22221,
        2,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        250.00,
        NULL
    ),
    (
        'CI0116311179',
        22221,
        3,
        '0003-07-22 00:00:00',
        '0003-07-22 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'CI0120394713',
        22423,
        1,
        '2011-01-23 00:00:00',
        '2011-01-23 00:00:00',
        'M2',
        600.00,
        'ES',
        NULL,
        425.00,
        NULL
    ),
    (
        'CI0120394713',
        22423,
        2,
        '0001-01-24 00:00:00',
        '0001-01-24 00:00:00',
        'M2',
        425.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'CI2200000001',
        22423,
        1,
        '0002-10-23 00:00:00',
        '0002-10-23 00:00:00',
        'M2',
        500.00,
        'ES',
        NULL,
        525.00,
        NULL
    ),
    (
        'CI2200000001',
        22423,
        2,
        '0004-05-23 00:00:00',
        '0004-05-23 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        250.00,
        NULL
    ),
    (
        'CI2200000001',
        22423,
        3,
        '0006-10-23 00:00:00',
        '0006-10-23 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'CI2200000002',
        22423,
        1,
        '2012-01-22 00:00:00',
        '2012-01-22 00:00:00',
        'M2',
        400.00,
        'ES',
        NULL,
        625.00,
        NULL
    ),
    (
        'CI2200000002',
        22423,
        2,
        '0002-01-23 00:00:00',
        '0002-01-23 00:00:00',
        'M2',
        400.00,
        'ES',
        NULL,
        225.00,
        NULL
    ),
    (
        'CI2200000002',
        22423,
        3,
        '0003-03-23 00:00:00',
        '0003-03-23 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'CI2200000004',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        'CI2200000004',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        500.00,
        NULL
    ),
    (
        'CI2200000004',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        200.00,
        NULL
    ),
    (
        'CI2200000004',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'CI2200000005',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        'CI2200000005',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        500.00,
        NULL
    ),
    (
        'CI2200000005',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        350.00,
        'ES',
        NULL,
        150.00,
        NULL
    ),
    (
        'CI2200000005',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        150.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'DIAM2310950002',
        22322,
        1,
        '2010-10-21 00:00:00',
        '2010-10-21 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        825.00,
        NULL
    ),
    (
        'DIAM2310950002',
        22322,
        2,
        '0003-05-22 00:00:00',
        '0003-05-22 00:00:00',
        'M2',
        200.00,
        'ES',
        NULL,
        625.00,
        NULL
    ),
    (
        'DIAM2310950002',
        22322,
        3,
        '0006-10-22 00:00:00',
        '0006-10-22 00:00:00',
        'M2',
        350.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        'DIAM2310950002',
        22322,
        4,
        '0007-10-22 00:00:00',
        '0007-10-22 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'DOUK1312960001',
        22221,
        1,
        '0008-12-21 00:00:00',
        '0008-12-21 00:00:00',
        'M2',
        250.00,
        'ES',
        NULL,
        700.00,
        NULL
    ),
    (
        'DOUK1312960001',
        22221,
        2,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        400.00,
        NULL
    ),
    (
        'DOUK1312960001',
        22221,
        3,
        '0003-07-22 00:00:00',
        '0003-07-22 00:00:00',
        'M2',
        400.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'KONS2208970001',
        22423,
        1,
        '2010-03-22 00:00:00',
        '2010-03-22 00:00:00',
        'M2',
        350.00,
        'ES',
        NULL,
        675.00,
        NULL
    ),
    (
        'KONS2208970001',
        22423,
        2,
        '0001-03-23 00:00:00',
        '0001-03-23 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        450.00,
        NULL
    ),
    (
        'KONS2208970001',
        22423,
        3,
        '0003-03-23 00:00:00',
        '0003-03-23 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        225.00,
        NULL
    ),
    (
        'KONS2208970001',
        22423,
        4,
        '0001-03-24 00:00:00',
        '0001-03-24 00:00:00',
        'M2',
        225.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'KOUA0705950007',
        22423,
        1,
        '0001-01-23 00:00:00',
        '0001-01-23 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        'KOUA0705950007',
        22423,
        2,
        '0003-01-23 00:00:00',
        '0003-01-23 00:00:00',
        'M2',
        375.00,
        'ES',
        NULL,
        350.00,
        NULL
    ),
    (
        'KOUA0705950007',
        22423,
        3,
        '0001-01-24 00:00:00',
        '0001-01-24 00:00:00',
        'M2',
        350.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'SORD2606950002',
        22423,
        1,
        '2011-02-22 00:00:00',
        '2011-02-22 00:00:00',
        'M2',
        450.00,
        'ES',
        NULL,
        575.00,
        NULL
    ),
    (
        'SORD2606950002',
        22423,
        2,
        '0001-02-23 00:00:00',
        '0001-02-23 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        275.00,
        NULL
    ),
    (
        'SORD2606950002',
        22423,
        3,
        '0003-02-23 00:00:00',
        '0003-02-23 00:00:00',
        'M2',
        275.00,
        'ES',
        NULL,
        0.00,
        NULL
    ),
    (
        'TOUS2506000001',
        22524,
        1,
        '0008-11-24 00:00:00',
        '0008-11-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        725.00,
        NULL
    ),
    (
        'TOUS2506000001',
        22524,
        2,
        '2010-07-24 00:00:00',
        '2010-07-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        425.00,
        NULL
    ),
    (
        'TOUS2506000001',
        22524,
        3,
        '2012-03-24 00:00:00',
        '2012-03-24 00:00:00',
        'M2',
        300.00,
        'ES',
        NULL,
        125.00,
        NULL
    ),
    (
        'TOUS2506000001',
        22524,
        4,
        '0001-05-25 00:00:00',
        '0001-05-25 00:00:00',
        'M2',
        125.00,
        'ES',
        NULL,
        0.00,
        NULL
    );

-- --------------------------------------------------------

--
-- Structure de la table `maitre_de_stage`
--

DROP TABLE IF EXISTS `maitre_de_stage`;

CREATE TABLE IF NOT EXISTS `maitre_de_stage` (
    `id_maitre_stage` varchar(15) NOT NULL,
    `Nom` varchar(50) DEFAULT NULL,
    `prenom` varchar(100) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `telephone` varchar(20) DEFAULT NULL,
    `id_entreprise` int NOT NULL,
    `id_fonction` varchar(2) DEFAULT NULL,
    PRIMARY KEY (`id_maitre_stage`),
    KEY `id_entreprise` (`id_entreprise`),
    KEY `id_fonction` (`id_fonction`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `maitre_de_stage`
--

INSERT INTO
    `maitre_de_stage` (
        `id_maitre_stage`,
        `Nom`,
        `prenom`,
        `email`,
        `telephone`,
        `id_entreprise`,
        `id_fonction`
    )
VALUES (
        'MS-13-001',
        'FOFANA',
        'Mariam',
        '',
        '',
        13,
        'NA'
    ),
    (
        'MS-14-001',
        'COFFI',
        'Amany',
        '',
        '',
        14,
        'NA'
    ),
    (
        'MS-15-001',
        'ZAMBLE',
        'Yves',
        '',
        '',
        15,
        'NA'
    ),
    (
        'MS-16-001',
        'AKA',
        'Gervais',
        '',
        '',
        16,
        'NA'
    ),
    (
        'MS-17-001',
        'LAUBHOUET',
        'Roger',
        '',
        '',
        17,
        'NA'
    ),
    (
        'MS-18-001',
        'KALA',
        'Jules Raymond',
        '',
        '',
        18,
        'NA'
    ),
    (
        'MS-18-002',
        'SANOGO',
        'Souleymane',
        '',
        '',
        18,
        'NA'
    ),
    (
        'MS-18-003',
        'YAMB',
        'Etienne Landry',
        '',
        '',
        18,
        'NA'
    ),
    (
        'MS-19-001',
        'ANHE',
        'Esther',
        '',
        '',
        19,
        'NA'
    ),
    (
        'MS-20-001',
        'KOFFI',
        'Néhémie',
        '',
        '',
        20,
        'NA'
    ),
    (
        'MS-21-001',
        'KESSE',
        'Brice',
        '',
        '',
        21,
        'NA'
    ),
    (
        'MS-22-001',
        'ADOU',
        'Wilfried',
        '',
        '',
        22,
        'NA'
    ),
    (
        'MS-22-002',
        'ALLOUKA',
        'Jean Romaric',
        '',
        '',
        22,
        'NA'
    ),
    (
        'MS-23-001',
        'BEYARA',
        'Koutouan Jean Roméo',
        '',
        '',
        23,
        'NA'
    ),
    (
        'MS-24-001',
        'AMOIKON',
        'Georges Wilrid',
        '',
        '',
        24,
        'NA'
    ),
    (
        'MS-25-001',
        'BOGUE',
        'Jonathan',
        '',
        '',
        25,
        'NA'
    ),
    (
        'MS-26-001',
        'TOURE',
        'Mohamed Lamine',
        '',
        '',
        26,
        'NA'
    ),
    (
        'MS-27-001',
        'TAMBIE',
        'Guy Alexis',
        '',
        '',
        27,
        'NA'
    ),
    (
        'MS-28-001',
        'ANGUI',
        'Ange Boris',
        '',
        '',
        28,
        'NA'
    ),
    (
        'MS-28-002',
        'ASSAH',
        'Esdras',
        '',
        '',
        28,
        'NA'
    ),
    (
        'MS-28-003',
        'MAMADOU',
        'Diarra',
        '',
        '',
        28,
        'NA'
    ),
    (
        'MS-29-001',
        'NIGBAOUA',
        'Abdouramane Sorho',
        '',
        '',
        29,
        'NA'
    ),
    (
        'MS-30-001',
        'KOFFI',
        'Annette-Cyrielle',
        '',
        '',
        30,
        'NA'
    ),
    (
        'MS-30-002',
        'TOUKAM',
        'Isidore',
        '',
        '',
        30,
        'NA'
    ),
    (
        'MS-31-001',
        'BEKOUAN',
        'Kassi',
        '',
        '',
        31,
        'NA'
    ),
    (
        'MS-45-001',
        'Maitre',
        'Stage Vedrine',
        'MsVedrine@fauxmail.com',
        '+225 0101010202',
        45,
        'AU'
    );

-- --------------------------------------------------------

--
-- Structure de la table `mentions`
--

DROP TABLE IF EXISTS `mentions`;

CREATE TABLE IF NOT EXISTS `mentions` (
    `id_mention` int NOT NULL AUTO_INCREMENT,
    `lib_mention` varchar(100) NOT NULL,
    `actif` tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id_mention`)
) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `mentions`
--

INSERT INTO
    `mentions` (
        `id_mention`,
        `lib_mention`,
        `actif`
    )
VALUES (1, 'Insuffisant', 1),
    (2, 'Passable', 1),
    (3, 'Assez-Bien', 1),
    (4, 'Bien', 1),
    (5, 'Très-Bien', 1),
    (6, 'Honorable', 1);

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

DROP TABLE IF EXISTS `messages`;

CREATE TABLE IF NOT EXISTS `messages` (
    `id_message` int NOT NULL AUTO_INCREMENT,
    `contenu_message` text NOT NULL,
    `lib_message` varchar(60) NOT NULL,
    `type_message` varchar(60) NOT NULL,
    PRIMARY KEY (`id_message`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `mode_paiement`
--

DROP TABLE IF EXISTS `mode_paiement`;

CREATE TABLE IF NOT EXISTS `mode_paiement` (
    `id_mode_paiement` varchar(2) NOT NULL,
    `libelle_mode_paement` varchar(25) NOT NULL,
    PRIMARY KEY (`id_mode_paiement`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `mode_paiement`
--

INSERT INTO
    `mode_paiement` (
        `id_mode_paiement`,
        `libelle_mode_paement`
    )
VALUES ('CH', 'Chèque'),
    ('ES', 'Espèce'),
    ('MN', 'Mtn money'),
    ('MV', 'Moov money'),
    ('OM', 'Orange money'),
    ('VR', 'Virement'),
    ('WV', 'Wave');

-- --------------------------------------------------------

--
-- Structure de la table `niveau_acces_donnees`
--

DROP TABLE IF EXISTS `niveau_acces_donnees`;

CREATE TABLE IF NOT EXISTS `niveau_acces_donnees` (
    `id_niveau_acces_donnees` int NOT NULL AUTO_INCREMENT,
    `lib_niveau_acces_donnees` varchar(70) NOT NULL,
    PRIMARY KEY (`id_niveau_acces_donnees`)
) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `niveau_acces_donnees`
--

INSERT INTO
    `niveau_acces_donnees` (
        `id_niveau_acces_donnees`,
        `lib_niveau_acces_donnees`
    )
VALUES (4, 'Lecture seule'),
    (5, 'Écriture');

-- --------------------------------------------------------

--
-- Structure de la table `niveau_approbation`
--

DROP TABLE IF EXISTS `niveau_approbation`;

CREATE TABLE IF NOT EXISTS `niveau_approbation` (
    `id_approb` int NOT NULL AUTO_INCREMENT,
    `lib_approb` varchar(50) NOT NULL,
    PRIMARY KEY (`id_approb`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `niveau_etude`
--

DROP TABLE IF EXISTS `niveau_etude`;

CREATE TABLE IF NOT EXISTS `niveau_etude` (
    `id_niv_etude` varchar(2) NOT NULL,
    `lib_niv_etude` varchar(50) NOT NULL,
    PRIMARY KEY (`id_niv_etude`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `niveau_etude`
--

INSERT INTO
    `niveau_etude` (
        `id_niv_etude`,
        `lib_niv_etude`
    )
VALUES ('M2', 'Master 2');

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

DROP TABLE IF EXISTS `notes`;

CREATE TABLE IF NOT EXISTS `notes` (
    `num_etu` varchar(25) NOT NULL,
    `id_annee_acad` int DEFAULT NULL,
    `moyenne_M1` decimal(4, 2) NOT NULL,
    `moyenne_M2` decimal(4, 2) NOT NULL,
    `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
    `date_modification` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`num_etu`),
    KEY `notes_ibfk_1` (`num_etu`),
    KEY `fk_notes_annee_acad` (`id_annee_acad`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `notes`
--

INSERT INTO
    `notes` (
        `num_etu`,
        `id_annee_acad`,
        `moyenne_M1`,
        `moyenne_M2`,
        `date_creation`,
        `date_modification`
    )
VALUES (
        '071226195/KOUA',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '093111826/DOH ',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '131202494/BROU',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '134108790/DIAR',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '14-24-LMI',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '141202563/CISS',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '143300494/AYEN',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '151216149/YATT',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '155001669/KOFF',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '161204577/SALI',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '161208093/SANO',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '161212417/TANO',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '161213173/SIME',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '161213861/CISS',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '161214166/KOUA',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '162000679/DIOM',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '162004707/YAO ',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '163118420/DJEC',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '163301119/KONA',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '163304342/TRAB',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '164012210/LAGO',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '164201727/KINH',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '165101454/GNOG',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '171201270/ANO ',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '171201836/BAKA',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '171202824/BOUE',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '171203302/COUL',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '171205456/FAMI',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '171211638/OUAT',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '171211865/OUAT',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '171212074/OYOU',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '171214745/YAPO',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '172202802/SOUL',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '172307062/YAO-',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '172601610/DIAR',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '173105975/DIOM',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '173202406/MIAN',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '174011197/DIOM',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '174014814/GBE ',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '174021825/KONE',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '174023275/KOUA',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '174030395/N\'GU',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '175100747/BOKA',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '175102432/OHOL',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '181201355/ANOM',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '181201526/ASSI',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '181206875/KADI',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '181213250/SOHO',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '181214844/YAO ',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '181508939/TIA ',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '183302736/MALA',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '184023067/KOUA',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '184027328/LODI',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '184032961/SAHO',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '184123571/KOUA',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '184907031/TOUR',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '191201495/ASSE',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '192602782/GOHI',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '193105002/COUL',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '193202273/KOFF',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '194801187/SORO',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '194901089/COUL',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '201205164/COUL',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '201206167/DIAB',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '201213696/KOUA',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '203107762/DOUA',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '203123140/TRAO',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '203402572/KOFF',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        '204002773/ALAG',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'ADOL1109970001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'AKAC2204960002',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'ALAP0811970001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'ALLA0109990001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'AMIK1809980001',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'ASSJ2304030001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'ATTJ2905970002',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'ATTK0309000002',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'BAHA2507970002',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'BAMA0909970001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'BOBJ2203880001',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'BOLY1011980002',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'BOUL2811950001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0106187064',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0108207902',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0108211061',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0108212628',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0109224375',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0110242904',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0110243163',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0110243311',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0111272399',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0111272409',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0111272412',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0111272417',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0112272423',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0112272430',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0112272431',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0112272435',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0112272440',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0112272443',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0113252028',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0113272684',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0113272986',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0113273196',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0113273198',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0113273286',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0113273537',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0113273793',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0114277408',
        21817,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0114278909',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0114279119',
        21817,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0114283286',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0114283821',
        21817,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0114283849',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0114284425',
        21817,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0114284687',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0114285095',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115290087',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115290089',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115290090',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115290092',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115290094',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115290104',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115290105',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115291053',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115291194',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115291243',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115301569',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115301657',
        21817,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115301658',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115302066',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115302301',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115302656',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115303004',
        21817,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0115312737',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0116304148',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0116311179',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI0120394713',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000001',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000002',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000003',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000004',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000005',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000006',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000007',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000008',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000009',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000010',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000011',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000012',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000013',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000014',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000015',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000016',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000017',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000018',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000019',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000020',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000021',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000022',
        20403,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000023',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000024',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000025',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000026',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000027',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000028',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000029',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000030',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000031',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000032',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000033',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000034',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000035',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000036',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000037',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000038',
        20504,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000039',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000040',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000041',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000042',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000043',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000044',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000045',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000046',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000047',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000048',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000049',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000050',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000051',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000052',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000053',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000054',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000055',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000056',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000057',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000058',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000059',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000060',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000061',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000062',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000063',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000064',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000065',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000066',
        20605,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000067',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000068',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000069',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000070',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000071',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000072',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000073',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000074',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000075',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000076',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000077',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000078',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000079',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000080',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000081',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000082',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000083',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000084',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000085',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000086',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000087',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000088',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000089',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000090',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000091',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000092',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000093',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000094',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000095',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000096',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000097',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000098',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000099',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000100',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000101',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000102',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000103',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000104',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000105',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000106',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000107',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000108',
        20706,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000109',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000110',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000111',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000112',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000113',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000114',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000115',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000116',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000117',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000118',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000119',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000120',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000121',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000122',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000123',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000124',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000125',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000126',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000127',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000128',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000129',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000130',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000131',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000132',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000133',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000134',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000135',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000136',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000137',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000138',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000139',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000140',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000141',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000142',
        20807,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000143',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000144',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000145',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000146',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000147',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000148',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000149',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000150',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000151',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000152',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000153',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000154',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000155',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000156',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000157',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000158',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000159',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000160',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000161',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000162',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000163',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000164',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000165',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000166',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000167',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000168',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000169',
        20908,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000170',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000171',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000172',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000173',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000174',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000175',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000176',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000177',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000178',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000179',
        21009,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000180',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000181',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000182',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000183',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000184',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000185',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000186',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000187',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000188',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000189',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000190',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000191',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000192',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000193',
        21110,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000194',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000195',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000196',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000197',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000198',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000199',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000200',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000201',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000202',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000203',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000204',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000205',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000206',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000207',
        21211,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000208',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000209',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000210',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000211',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000212',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000213',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000214',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000215',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000216',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000217',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000219',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000220',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000221',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000222',
        21312,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000223',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000224',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000225',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000226',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000227',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000228',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000229',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000230',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000231',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000232',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000233',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000234',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000235',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000236',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000237',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000238',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000239',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000240',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000241',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000242',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000243',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000244',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000245',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000246',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000247',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000248',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000249',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000250',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000251',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000252',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000253',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000254',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000255',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000256',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000257',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000258',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000259',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000260',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000261',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000262',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000263',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000264',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000265',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000266',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000267',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000268',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000269',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000270',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000271',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000272',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000273',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000274',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000275',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000276',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000277',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000278',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000279',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000280',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000281',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000282',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000283',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000284',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000285',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000286',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000287',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000288',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000289',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000290',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000291',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000292',
        21514,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000293',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000294',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000295',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000296',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000297',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000298',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000299',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000300',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000301',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000302',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000303',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000304',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000305',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000306',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000307',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000308',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000309',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000310',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000311',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000312',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000313',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000314',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000315',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000316',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000317',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000318',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000319',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000320',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000321',
        21615,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000322',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000323',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000324',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000325',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000326',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000327',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000328',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000329',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000330',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000331',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000332',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000333',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000334',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000335',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000336',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000337',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000338',
        22423,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000339',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000340',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000341',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000342',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CI2200000343',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'COUA0404990001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'COUA2104970001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'CRIB2105030002',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'DEGG2506030001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'DEML1504910001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'DIAM1811010001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'DIAM2310950002',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'DIAY0801030001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'DIBG2005950001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'DIPS0705980001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'DJAC1110020001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'DOUK1312960001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'EHIA2912960001',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'EHOA0110980001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'GANG1008030001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'GBAA1502990001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'GOLR1305960001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'GUEK3003940001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'HOUG2309970001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KAMZ1505960001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KANT1303010001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KASD2202950001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KEUF2403950001',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KIMN2712910001',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOBT1112030001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOEB2711970001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOFA2802040001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOFK0405950001',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KONM3008010001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KONO0306930001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KONS2208970001',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KONY0801040001',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KONY1404950002',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOUA0204890001',
        21413,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOUA0705950007',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOUA3007030002',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOUC3001030002',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOUD2803030002',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOUP2506970001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOUY0810960001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'KOUY2406980002',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'NANM0805990002',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'NIAN2010020001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'NKUS2509030001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'OUAD2508910002',
        21716,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'OULP1309030001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'SAMB2109990001',
        22524,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'SEHW2903960001',
        21918,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'SEKT1011030002',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'SIDM0608940001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'SORD2606950002',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'SORF0303000001',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'SORK0511930001',
        NULL,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'SOUA0511980001',
        22120,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'TANA1909980001',
        22019,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'THIR2401050001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'TOUG2003030001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'TOUS2506000001',
        22322,
        12.00,
        12.00,
        NULL,
        NULL
    ),
    (
        'YOBH1802000001',
        22625,
        12.00,
        12.00,
        NULL,
        NULL
    );

-- --------------------------------------------------------

--
-- Structure de la table `occuper`
--

DROP TABLE IF EXISTS `occuper`;

CREATE TABLE IF NOT EXISTS `occuper` (
    `id_fonction` varchar(2) NOT NULL,
    `id_enseignant` varchar(20) NOT NULL,
    `date_occupation` date NOT NULL,
    PRIMARY KEY (
        `id_fonction`,
        `id_enseignant`
    ),
    KEY `id_enseignant` (`id_enseignant`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` int NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `token` varchar(255) NOT NULL,
    `expires_at` datetime NOT NULL,
    `used` tinyint(1) NOT NULL DEFAULT '0',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `email` (`email`),
    KEY `expires_at` (`expires_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

DROP TABLE IF EXISTS `permissions`;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id_permission` int NOT NULL AUTO_INCREMENT,
    `id_GU` int NOT NULL,
    `id_fonctionnalite` int NOT NULL,
    `peut_voir` tinyint(1) NOT NULL DEFAULT '0',
    `peut_creer` tinyint(1) NOT NULL DEFAULT '0',
    `peut_modifier` tinyint(1) NOT NULL DEFAULT '0',
    `peut_supprimer` tinyint(1) NOT NULL DEFAULT '0',
    `date_attribution` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_permission`),
    KEY `id_GU` (`id_GU`),
    KEY `id_fonctionnalite` (`id_fonctionnalite`)
) ENGINE = InnoDB AUTO_INCREMENT = 1910 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO
    `permissions` (
        `id_permission`,
        `id_GU`,
        `id_fonctionnalite`,
        `peut_voir`,
        `peut_creer`,
        `peut_modifier`,
        `peut_supprimer`,
        `date_attribution`
    )
VALUES (
        1760,
        13,
        101,
        1,
        1,
        1,
        1,
        '2026-02-12 17:11:49'
    ),
    (
        1761,
        13,
        91,
        1,
        1,
        1,
        1,
        '2026-02-12 17:11:49'
    ),
    (
        1762,
        13,
        102,
        1,
        1,
        1,
        1,
        '2026-02-12 17:11:49'
    ),
    (
        1763,
        13,
        103,
        1,
        0,
        0,
        0,
        '2026-02-12 17:11:49'
    ),
    (
        1764,
        13,
        79,
        1,
        1,
        1,
        1,
        '2026-02-12 17:11:49'
    ),
    (
        1804,
        12,
        117,
        1,
        0,
        0,
        0,
        '2026-02-27 18:49:53'
    ),
    (
        1806,
        9,
        117,
        1,
        0,
        0,
        0,
        '2026-02-27 18:49:53'
    ),
    (
        1807,
        10,
        117,
        1,
        0,
        0,
        0,
        '2026-02-27 18:49:54'
    ),
    (
        1808,
        11,
        117,
        1,
        0,
        0,
        0,
        '2026-02-27 18:49:54'
    ),
    (
        1868,
        5,
        99,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1869,
        5,
        95,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1870,
        5,
        93,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1871,
        5,
        96,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1872,
        5,
        100,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1873,
        5,
        94,
        1,
        0,
        0,
        0,
        '2026-03-07 22:40:49'
    ),
    (
        1874,
        5,
        98,
        1,
        0,
        0,
        0,
        '2026-03-07 22:40:49'
    ),
    (
        1875,
        5,
        97,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1876,
        5,
        125,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1877,
        5,
        101,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1878,
        5,
        91,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1879,
        5,
        102,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1880,
        5,
        103,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1881,
        5,
        2,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1882,
        5,
        104,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1883,
        5,
        106,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1884,
        5,
        107,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1885,
        5,
        105,
        1,
        0,
        0,
        0,
        '2026-03-07 22:40:49'
    ),
    (
        1886,
        5,
        108,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1887,
        5,
        109,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1888,
        5,
        110,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1889,
        5,
        111,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1890,
        5,
        112,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1891,
        5,
        116,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1892,
        5,
        124,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1893,
        5,
        117,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1894,
        5,
        73,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1895,
        5,
        74,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1896,
        5,
        30,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1897,
        5,
        50,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1898,
        5,
        114,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1899,
        5,
        51,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1900,
        5,
        113,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1901,
        5,
        115,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1902,
        5,
        52,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1903,
        5,
        75,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1904,
        5,
        81,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1905,
        5,
        76,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1906,
        5,
        55,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1907,
        5,
        78,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    ),
    (
        1908,
        5,
        38,
        1,
        0,
        1,
        0,
        '2026-03-07 22:40:49'
    ),
    (
        1909,
        5,
        79,
        1,
        1,
        1,
        1,
        '2026-03-07 22:40:49'
    );

-- --------------------------------------------------------

--
-- Structure de la table `personnel_admin`
--

DROP TABLE IF EXISTS `personnel_admin`;

CREATE TABLE IF NOT EXISTS `personnel_admin` (
    `id_pers_admin` int NOT NULL AUTO_INCREMENT,
    `nom_pers_admin` varchar(50) NOT NULL,
    `prenom_pers_admin` varchar(100) NOT NULL,
    `email_pers_admin` varchar(100) NOT NULL,
    `tel_pers_admin` varchar(20) NOT NULL,
    `poste` varchar(60) NOT NULL,
    `date_embauche` date NOT NULL,
    PRIMARY KEY (`id_pers_admin`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `pister`
--

DROP TABLE IF EXISTS `pister`;

CREATE TABLE IF NOT EXISTS `pister` (
    `id_piste` int NOT NULL AUTO_INCREMENT,
    `id_utilisateur` int NOT NULL,
    `action` varchar(60) NOT NULL COMMENT 'Type d''action (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc.)',
    `statut_action` enum('Erreur', 'Succès') NOT NULL,
    `nom_table` varchar(50) DEFAULT NULL COMMENT 'Nom de la table concernee',
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_piste`),
    KEY `idx_utilisateur` (`id_utilisateur`),
    KEY `idx_action` (`action`),
    KEY `idx_table` (`nom_table`),
    KEY `idx_created_at` (`date_creation`),
    KEY `idx_utilisateur_action` (`id_utilisateur`, `action`)
) ENGINE = InnoDB AUTO_INCREMENT = 2 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `pister`
--

INSERT INTO
    `pister` (
        `id_piste`,
        `id_utilisateur`,
        `action`,
        `statut_action`,
        `nom_table`,
        `date_creation`
    )
VALUES (
        1,
        5,
        'Création',
        'Succès',
        'inscriptions',
        '2026-03-10 22:17:14'
    );

-- --------------------------------------------------------

--
-- Structure de la table `programmer_soutenance`
--

DROP TABLE IF EXISTS `programmer_soutenance`;

CREATE TABLE IF NOT EXISTS `programmer_soutenance` (
    `num_soutenance` varchar(20) NOT NULL,
    `num_etud` varchar(25) NOT NULL,
    `theme_soutenance` varchar(255) NOT NULL,
    `id_domaine` int DEFAULT NULL,
    `id_session` int NOT NULL,
    `id_salle` int DEFAULT NULL,
    `date_soutenance` date DEFAULT NULL,
    `heure_soutenance` time DEFAULT NULL,
    `id_annee_acad` int DEFAULT NULL,
    PRIMARY KEY (`num_soutenance`),
    KEY `num_etud` (`num_etud`),
    KEY `id_salle` (`id_salle`),
    KEY `id_domaine` (`id_domaine`),
    KEY `id_session` (`id_session`),
    KEY `id_annee_acad` (`id_annee_acad`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `programmer_soutenance`
--

INSERT INTO
    `programmer_soutenance` (
        `num_soutenance`,
        `num_etud`,
        `theme_soutenance`,
        `id_domaine`,
        `id_session`,
        `id_salle`,
        `date_soutenance`,
        `heure_soutenance`,
        `id_annee_acad`
    )
VALUES (
        '22221S2271022-01',
        'CI0116311179',
        'ETUDE ET MISE EN ŒUVRE DE L\'AUTOMATISATION DES TESTS POUR L\'INTEGRATION DES MESSAGES SWIFT: CAS DE LA SGABS',
        NULL,
        2,
        1,
        NULL,
        NULL,
        22221
    ),
    (
        '22423S1290524-01',
        'CI0114277408',
        'CONCEPTION ET REALISATION D\'UNE PLATEFORME DE SIGNATURE ELECTRONIQUE DE DOCUMENT PDF',
        NULL,
        1,
        1,
        '0000-00-00',
        '00:00:08',
        22423
    ),
    (
        '22423S1300524-02',
        'CI0120394713',
        'ACCOMPAGNEMENT DE LA TRANSFORMATION NUMERIQUE D\'UNE ENTREPRISE A TRAVERS L\'ELABORATION D\'UN SCHEMA DIRECTEUR DES SYSTÈME D\'INFORMATION : CAS DU TRANS-URBAIN',
        NULL,
        1,
        1,
        '0000-00-00',
        '00:00:10',
        22423
    ),
    (
        '22423S2201023-01',
        'CI2200000001',
        'MISE EN PLACE D\'UN SYSTÈME DE GESTION DE LA FACTURATION DES NAVIRES EN ESCALE A UN PORT',
        1,
        2,
        1,
        '0000-00-00',
        '00:00:08',
        22423
    ),
    (
        '22423S2201023-03',
        'CI2200000002',
        'CONCEPTION ET REALISATION D\'UN LOGICIEL DE GESTION DE CENTRE MEDICAL : CAS DU CENTRE MEDICAL EDLONA',
        1,
        2,
        1,
        '0000-00-00',
        '00:00:14',
        22423
    ),
    (
        '22524S1220525-03',
        'CI2200000005',
        'Conception et réalisation d\'une application ppour lapromotion de l\'immobilier ivoirien : Cas du portail WEB TOUBABI.COM',
        NULL,
        1,
        NULL,
        NULL,
        NULL,
        22524
    );

-- --------------------------------------------------------

--
-- Structure de la table `qualite_jury`
--

DROP TABLE IF EXISTS `qualite_jury`;

CREATE TABLE IF NOT EXISTS `qualite_jury` (
    `id_role_jury` varchar(2) NOT NULL,
    `lib_role` varchar(50) NOT NULL,
    PRIMARY KEY (`id_role_jury`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `qualite_jury`
--

INSERT INTO
    `qualite_jury` (`id_role_jury`, `lib_role`)
VALUES ('DM', 'Directeur mémoire'),
    ('EN', 'Encadrant'),
    ('EX', 'Examinateur'),
    ('MS', 'Maître de stage'),
    ('PJ', 'Président');

-- --------------------------------------------------------

--
-- Structure de la table `rapport_etudiants`
--

DROP TABLE IF EXISTS `rapport_etudiants`;

CREATE TABLE IF NOT EXISTS `rapport_etudiants` (
    `id_rapport` int NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `date_redaction_rapport` datetime NOT NULL,
    `theme_rapport` varchar(255) NOT NULL,
    `nom_rapport` varchar(255) DEFAULT NULL,
    `chemin_fichier` varchar(255) DEFAULT NULL,
    `statut_rapport` enum(
        'en_cours',
        'valider',
        'rejeter',
        'en_attente'
    ) NOT NULL DEFAULT 'en_cours',
    `date_modification` datetime DEFAULT NULL,
    `taille_fichier` int DEFAULT NULL,
    `version` int NOT NULL DEFAULT '1',
    PRIMARY KEY (`id_rapport`),
    KEY `num_etu` (`num_etu`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `reclamations`
--

DROP TABLE IF EXISTS `reclamations`;

CREATE TABLE IF NOT EXISTS `reclamations` (
    `id_reclamation` int NOT NULL AUTO_INCREMENT,
    `num_carte_etud` varchar(25) DEFAULT NULL,
    `objet_reclamation` varchar(150) NOT NULL,
    `description_reclamation` text NOT NULL,
    `statut_reclamation` int NOT NULL,
    `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_mise_a_jour` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_reclamation`),
    KEY `idx_num_etu` (`num_carte_etud`),
    KEY `idx_statut` (`statut_reclamation`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `rendre`
--

DROP TABLE IF EXISTS `rendre`;

CREATE TABLE IF NOT EXISTS `rendre` (
    `id_CR` int NOT NULL,
    `id_enseignant` varchar(20) NOT NULL,
    `date_env` datetime NOT NULL,
    PRIMARY KEY (`id_CR`, `id_enseignant`),
    KEY `Key_rendre_enseignant` (`id_enseignant`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `resume_candidature`
--

DROP TABLE IF EXISTS `resume_candidature`;

CREATE TABLE IF NOT EXISTS `resume_candidature` (
    `id` int NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `id_candidature` int NOT NULL,
    `resume_json` longtext NOT NULL,
    `decision` varchar(20) NOT NULL,
    `date_enregistrement` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `num_etu` (`num_etu`),
    KEY `fk_candidature` (`id_candidature`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `route_actions`
--

DROP TABLE IF EXISTS `route_actions`;

CREATE TABLE IF NOT EXISTS `route_actions` (
    `id_route_action` int NOT NULL AUTO_INCREMENT,
    `route_pattern` varchar(255) NOT NULL,
    `http_method` enum(
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        '*'
    ) NOT NULL DEFAULT '*',
    `action_crud` enum(
        'voir',
        'creer',
        'modifier',
        'supprimer'
    ) NOT NULL,
    `description` text,
    `actif` tinyint(1) NOT NULL DEFAULT '1',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_route_action`),
    KEY `idx_route_pattern` (`route_pattern`),
    KEY `idx_actif` (`actif`)
) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `route_actions`
--

INSERT INTO
    `route_actions` (
        `id_route_action`,
        `route_pattern`,
        `http_method`,
        `action_crud`,
        `description`,
        `actif`,
        `created_at`,
        `updated_at`
    )
VALUES (
        1,
        'page=parametres_generaux&action=gestion_attribution',
        'GET',
        'voir',
        'Écran gestion des permissions',
        1,
        '2026-01-24 17:58:27',
        NULL
    ),
    (
        2,
        'page=parametres_generaux&action=gestion_attribution',
        'POST',
        'modifier',
        'Enregistrer permissions',
        1,
        '2026-01-24 17:58:27',
        NULL
    ),
    (
        3,
        'page=gestion_rapports&action=creer_rapport',
        'GET',
        'creer',
        'Formulaire création rapport',
        1,
        '2026-01-24 17:58:27',
        NULL
    ),
    (
        4,
        'page=gestion_rapports&action=creer_rapport',
        'POST',
        'creer',
        'Création rapport',
        1,
        '2026-01-24 17:58:27',
        NULL
    ),
    (
        5,
        'page=sauvegarde_restauration',
        'POST',
        'modifier',
        'Backup/restore (actions)',
        1,
        '2026-01-24 17:58:27',
        NULL
    );

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

DROP TABLE IF EXISTS `salles`;

CREATE TABLE IF NOT EXISTS `salles` (
    `id_salle` int NOT NULL,
    `lib_salle` varchar(100) NOT NULL,
    PRIMARY KEY (`id_salle`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO
    `salles` (`id_salle`, `lib_salle`)
VALUES (
        1,
        'Salle de conférence UFRMI'
    ),
    (2, 'Salle de conférence IRMA'),
    (3, 'Amphithéâtre IRMA'),
    (4, 'Salle TD 207 UFRMI'),
    (5, 'Salle TD VALLON'),
    (6, 'Salle TD CESTIA');

-- --------------------------------------------------------

--
-- Structure de la table `semestre`
--

DROP TABLE IF EXISTS `semestre`;

CREATE TABLE IF NOT EXISTS `semestre` (
    `id_semestre` int NOT NULL AUTO_INCREMENT,
    `lib_semestre` varchar(15) NOT NULL,
    `id_niv_etude` varchar(2) NOT NULL,
    PRIMARY KEY (`id_semestre`),
    KEY `id_niv_etude` (`id_niv_etude`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `session`
--

DROP TABLE IF EXISTS `session`;

CREATE TABLE IF NOT EXISTS `session` (
    `id_session` int NOT NULL,
    `lib_session` varchar(30) NOT NULL,
    PRIMARY KEY (`id_session`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `session`
--

INSERT INTO
    `session` (`id_session`, `lib_session`)
VALUES (1, 'Mai'),
    (2, 'Octobre'),
    (3, 'Décembre');

-- --------------------------------------------------------

--
-- Structure de la table `specialite`
--

DROP TABLE IF EXISTS `specialite`;

CREATE TABLE IF NOT EXISTS `specialite` (
    `id_specialite` int NOT NULL AUTO_INCREMENT,
    `lib_specialite` varchar(100) NOT NULL,
    PRIMARY KEY (`id_specialite`)
) ENGINE = InnoDB AUTO_INCREMENT = 17 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `specialite`
--

INSERT INTO
    `specialite` (
        `id_specialite`,
        `lib_specialite`
    )
VALUES (2, 'Informatique'),
    (3, 'Comptabilité'),
    (5, 'Mathématique'),
    (6, 'Réseaux'),
    (7, 'Médecine'),
    (8, 'Géoscience'),
    (9, 'Physique'),
    (
        10,
        'Génie Électrique et Électronique'
    ),
    (11, 'Biologie'),
    (12, 'Droit Public'),
    (13, 'Langues Étrangères'),
    (14, 'Management'),
    (15, 'Finance'),
    (16, 'Marketing');

-- --------------------------------------------------------

--
-- Structure de la table `statut_jury`
--

DROP TABLE IF EXISTS `statut_jury`;

CREATE TABLE IF NOT EXISTS `statut_jury` (
    `id_jury` int NOT NULL AUTO_INCREMENT,
    `lib_jury` varchar(50) NOT NULL,
    PRIMARY KEY (`id_jury`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `statut_reclamation`
--

DROP TABLE IF EXISTS `statut_reclamation`;

CREATE TABLE IF NOT EXISTS `statut_reclamation` (
    `id_statut_reclamation` int NOT NULL AUTO_INCREMENT,
    `libelle_statut_reclamation` varchar(50) NOT NULL,
    PRIMARY KEY (`id_statut_reclamation`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `type_enseignant`
--

DROP TABLE IF EXISTS `type_enseignant`;

CREATE TABLE IF NOT EXISTS `type_enseignant` (
    `id_type_enseignant` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(100) NOT NULL,
    PRIMARY KEY (`id_type_enseignant`)
) ENGINE = InnoDB AUTO_INCREMENT = 3 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `type_enseignant`
--

INSERT INTO
    `type_enseignant` (
        `id_type_enseignant`,
        `libelle`
    )
VALUES (1, 'Administratif'),
    (2, 'Simple');

-- --------------------------------------------------------

--
-- Structure de la table `type_utilisateur`
--

DROP TABLE IF EXISTS `type_utilisateur`;

CREATE TABLE IF NOT EXISTS `type_utilisateur` (
    `id_type_utilisateur` int NOT NULL AUTO_INCREMENT,
    `lib_type_utilisateur` varchar(100) NOT NULL,
    PRIMARY KEY (`id_type_utilisateur`)
) ENGINE = InnoDB AUTO_INCREMENT = 8 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `type_utilisateur`
--

INSERT INTO
    `type_utilisateur` (
        `id_type_utilisateur`,
        `lib_type_utilisateur`
    )
VALUES (4, 'Personnel administratif'),
    (5, 'Enseignant administratif'),
    (6, 'Enseignant simple'),
    (7, 'Etudiant');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;

CREATE TABLE IF NOT EXISTS `utilisateur` (
    `id_utilisateur` int NOT NULL AUTO_INCREMENT,
    `nom_utilisateur` varchar(200) NOT NULL,
    `id_type_utilisateur` int NOT NULL,
    `id_GU` int NOT NULL,
    `id_niv_acces_donnee` int NOT NULL,
    `statut_utilisateur` enum('Actif', 'Inactif') NOT NULL,
    `login_utilisateur` varchar(60) NOT NULL,
    `mdp_utilisateur` varchar(255) NOT NULL,
    PRIMARY KEY (`id_utilisateur`),
    UNIQUE KEY `login_utilisateur` (`login_utilisateur`),
    KEY `id_groupe_utilisateur` (`id_GU`),
    KEY `id_niv_acces_donnee` (`id_niv_acces_donnee`),
    KEY `id_type_utilisateur` (`id_type_utilisateur`)
) ENGINE = InnoDB AUTO_INCREMENT = 112 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO
    `utilisateur` (
        `id_utilisateur`,
        `nom_utilisateur`,
        `id_type_utilisateur`,
        `id_GU`,
        `id_niv_acces_donnee`,
        `statut_utilisateur`,
        `login_utilisateur`,
        `mdp_utilisateur`
    )
VALUES (
        5,
        'Koua Brou',
        5,
        5,
        5,
        'Actif',
        'kouabrou@gmail.com',
        '$2y$10$IM9LuGERPnqbR.DoqkQnMu.WBSXZJ5T5YtqBSFGO2X5nQF/xCnaFW'
    ),
    (
        111,
        'WAH MEDARD',
        5,
        5,
        5,
        'Actif',
        'wmedard',
        '$2y$10$CnRWG58zZNSJjjBgxxMcGeCpFuUpqaz89EGiQKaCWAJJ6amGzGGKK'
    );

-- --------------------------------------------------------

--
-- Structure de la table `valider`
--

DROP TABLE IF EXISTS `valider`;

CREATE TABLE IF NOT EXISTS `valider` (
    `id_enseignant` varchar(20) NOT NULL,
    `id_rapport` int NOT NULL,
    `date_validation` datetime NOT NULL,
    `commentaire_validation` varchar(1000) NOT NULL,
    `decision_validation` enum('valider', 'rejeter') NOT NULL DEFAULT 'valider',
    PRIMARY KEY (`id_enseignant`, `id_rapport`),
    KEY `Key_valider_rapport` (`id_rapport`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `affecter`
--
ALTER TABLE `affecter`
ADD CONSTRAINT `fk_affecter_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_affecter_jury` FOREIGN KEY (`id_jury`) REFERENCES `statut_jury` (`id_jury`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_affecter_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `avoir`
--
ALTER TABLE `avoir`
ADD CONSTRAINT `avoir_ibfk_1` FOREIGN KEY (`id_grade`) REFERENCES `grade` (`id_grade`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `avoir_ibfk_2` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `bareme_critere`
--
ALTER TABLE `bareme_critere`
ADD CONSTRAINT `bareme_critere_ibfk_1` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `bareme_critere_ibfk_2` FOREIGN KEY (`id_critere`) REFERENCES `critere_evaluation` (`id_critere`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `candidature_soutenance`
--
ALTER TABLE `candidature_soutenance`
ADD CONSTRAINT `candidature_soutenance_ibfk_2` FOREIGN KEY (`id_pers_admin`) REFERENCES `personnel_admin` (`id_pers_admin`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `candidature_soutenance_ibfk_3` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `compte_rendu`
--
ALTER TABLE `compte_rendu`
ADD CONSTRAINT `compte_rendu_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `compte_rendu_rapport`
--
ALTER TABLE `compte_rendu_rapport`
ADD CONSTRAINT `compte_rendu_rapport_ibfk_1` FOREIGN KEY (`id_CR`) REFERENCES `compte_rendu` (`id_CR`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `compte_rendu_rapport_ibfk_2` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `deposer`
--
ALTER TABLE `deposer`
ADD CONSTRAINT `fk_deposer_etudiant` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_deposer_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `enseignants`
--
ALTER TABLE `enseignants`
ADD CONSTRAINT `enseignants_ibfk_1` FOREIGN KEY (`type_enseignant`) REFERENCES `type_enseignant` (`id_type_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `enseignants_ibfk_2` FOREIGN KEY (`id_etablissement_origin`) REFERENCES `etablissement_origine` (`id_etablissement`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_enseignants_specialite` FOREIGN KEY (`id_specialite`) REFERENCES `specialite` (`id_specialite`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `enseignant_jury`
--
ALTER TABLE `enseignant_jury`
ADD CONSTRAINT `fk_composer_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_composer_role` FOREIGN KEY (`id_qualite_jury`) REFERENCES `qualite_jury` (`id_role_jury`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_composer_soutenance` FOREIGN KEY (`num_soutenance`) REFERENCES `programmer_soutenance` (`num_soutenance`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `etudiants`
--
ALTER TABLE `etudiants`
ADD CONSTRAINT `etudiants_ibfk_1` FOREIGN KEY (`id_genre`) REFERENCES `genre` (`id_genre`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `evaluations_rapports`
--
ALTER TABLE `evaluations_rapports`
ADD CONSTRAINT `evaluations_rapports_ibfk_2` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `evaluer`
--
ALTER TABLE `evaluer`
ADD CONSTRAINT `evaluer_ibfk_1` FOREIGN KEY (`id_critere`) REFERENCES `critere_evaluation` (`id_critere`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `evaluer_ibfk_2` FOREIGN KEY (`num_etudiant`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `fonctionnalites`
--
ALTER TABLE `fonctionnalites`
ADD CONSTRAINT `fonctionnalites_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories_fonctionnalites` (`id_categorie`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `frais_inscription`
--
ALTER TABLE `frais_inscription`
ADD CONSTRAINT `fk_frais_inscription_annee` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_frais_inscription_niveau` FOREIGN KEY (`id_niv_etude`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `groupe_utilisateur`
--
ALTER TABLE `groupe_utilisateur`
ADD CONSTRAINT `fk_groupe_utilisateur_type` FOREIGN KEY (`id_type_utilisateur`) REFERENCES `type_utilisateur` (`id_type_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `informations_stage`
--
ALTER TABLE `informations_stage`
ADD CONSTRAINT `informations_stage_ibfk_2` FOREIGN KEY (`id_entreprise`) REFERENCES `entreprises` (`id_entreprise`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `informations_stage_ibfk_3` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `informations_stage_ibfk_4` FOREIGN KEY (`id_maitre_stage`) REFERENCES `maitre_de_stage` (`id_maitre_stage`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
ADD CONSTRAINT `fk_inscriptions_annee` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_inscriptions_etudiant` FOREIGN KEY (`num_carte_etud`) REFERENCES `etudiants` (`num_ident_etud`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_inscriptions_mode_paiement` FOREIGN KEY (`methode_paiement`) REFERENCES `mode_paiement` (`id_mode_paiement`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_inscriptions_niveau` FOREIGN KEY (`id_niv_etude`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `maitre_de_stage`
--
ALTER TABLE `maitre_de_stage`
ADD CONSTRAINT `maitre_de_stage_ibfk_1` FOREIGN KEY (`id_entreprise`) REFERENCES `entreprises` (`id_entreprise`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `maitre_de_stage_ibfk_2` FOREIGN KEY (`id_fonction`) REFERENCES `fonction` (`id_fonction`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
ADD CONSTRAINT `fk_notes_annee_acad` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `occuper`
--
ALTER TABLE `occuper`
ADD CONSTRAINT `occuper_ibfk_1` FOREIGN KEY (`id_fonction`) REFERENCES `fonction` (`id_fonction`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `occuper_ibfk_2` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `permissions`
--
ALTER TABLE `permissions`
ADD CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`id_GU`) REFERENCES `groupe_utilisateur` (`id_GU`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `permissions_ibfk_2` FOREIGN KEY (`id_fonctionnalite`) REFERENCES `fonctionnalites` (`id_fonctionnalite`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `pister`
--
ALTER TABLE `pister`
ADD CONSTRAINT `fk_pister_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `programmer_soutenance`
--
ALTER TABLE `programmer_soutenance`
ADD CONSTRAINT `programmer_soutenance_ibfk_1` FOREIGN KEY (`num_etud`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `programmer_soutenance_ibfk_2` FOREIGN KEY (`id_domaine`) REFERENCES `domaine` (`id_domaine`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `programmer_soutenance_ibfk_3` FOREIGN KEY (`id_session`) REFERENCES `session` (`id_session`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `programmer_soutenance_ibfk_4` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id_salle`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `programmer_soutenance_ibfk_5` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rapport_etudiants`
--
ALTER TABLE `rapport_etudiants`
ADD CONSTRAINT `rapport_etudiants_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reclamations`
--
ALTER TABLE `reclamations`
ADD CONSTRAINT `reclamations_ibfk_1` FOREIGN KEY (`num_carte_etud`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `reclamations_ibfk_2` FOREIGN KEY (`statut_reclamation`) REFERENCES `statut_reclamation` (`id_statut_reclamation`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rendre`
--
ALTER TABLE `rendre`
ADD CONSTRAINT `fk_rendre_cr` FOREIGN KEY (`id_CR`) REFERENCES `compte_rendu` (`id_CR`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_rendre_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `resume_candidature`
--
ALTER TABLE `resume_candidature`
ADD CONSTRAINT `resume_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `resume_ibfk_2` FOREIGN KEY (`id_candidature`) REFERENCES `candidature_soutenance` (`id_candidature`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `semestre`
--
ALTER TABLE `semestre`
ADD CONSTRAINT `fk_semestre_niveau` FOREIGN KEY (`id_niv_etude`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
ADD CONSTRAINT `utilisateur_ibfk_2` FOREIGN KEY (`id_GU`) REFERENCES `groupe_utilisateur` (`id_GU`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `utilisateur_ibfk_3` FOREIGN KEY (`id_niv_acces_donnee`) REFERENCES `niveau_acces_donnees` (`id_niveau_acces_donnees`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `utilisateur_ibfk_4` FOREIGN KEY (`id_type_utilisateur`) REFERENCES `type_utilisateur` (`id_type_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `valider`
--
ALTER TABLE `valider`
ADD CONSTRAINT `fk_valider_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_valider_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;