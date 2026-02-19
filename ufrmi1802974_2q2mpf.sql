-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 18 fév. 2026 à 21:09
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
    `id_enseignant` int NOT NULL,
    `role` enum('encadrant', 'directeur') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
    `id_annee_acad` int NOT NULL AUTO_INCREMENT,
    `date_deb` date NOT NULL,
    `date_fin` date NOT NULL,
    PRIMARY KEY (`id_annee_acad`)
) ENGINE = InnoDB AUTO_INCREMENT = 22525 DEFAULT CHARSET = utf8mb3;

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
        22524,
        '2024-09-01',
        '2025-07-31'
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
) ENGINE = MyISAM DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avoir`
--

DROP TABLE IF EXISTS `avoir`;

CREATE TABLE IF NOT EXISTS `avoir` (
    `id_grade` varchar(2) NOT NULL,
    `id_enseignant` int NOT NULL,
    `date_grade` date NOT NULL,
    PRIMARY KEY (`id_grade`, `id_enseignant`),
    KEY `Key_avoir_grade` (`id_grade`),
    KEY `Key_avoir_enseignant` (`id_enseignant`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `bareme_critere`
--

DROP TABLE IF EXISTS `bareme_critere`;

CREATE TABLE IF NOT EXISTS `bareme_critere` (
    `id_annee_acad` int NOT NULL,
    `id_critere` int NOT NULL,
    `bareme` int NOT NULL,
    PRIMARY KEY (`id_annee_acad`, `id_critere`),
    KEY `id_critere` (`id_critere`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

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
    ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'En attente',
    `date_traitement` datetime DEFAULT NULL,
    `id_pers_admin` int DEFAULT NULL,
    `commentaire_admin` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
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
) ENGINE = InnoDB AUTO_INCREMENT = 25 DEFAULT CHARSET = utf8mb3;

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
        5,
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
    );

-- --------------------------------------------------------

--
-- Structure de la table `compte_rendu`
--

DROP TABLE IF EXISTS `compte_rendu`;

CREATE TABLE IF NOT EXISTS `compte_rendu` (
    `id_CR` int NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `nom_CR` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `contenu_CR` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
    `chemin_fichier_pdf` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
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
    `id_critere` int NOT NULL AUTO_INCREMENT,
    `code_critere` varchar(2) NOT NULL,
    `lib_critere` varchar(100) NOT NULL,
    PRIMARY KEY (`id_critere`)
) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `critere_evaluation`
--

INSERT INTO
    `critere_evaluation` (
        `id_critere`,
        `code_critere`,
        `lib_critere`
    )
VALUES (1, 'EX', 'Exposé'),
    (
        2,
        'RQ',
        'Réponses aux questions posées'
    ),
    (
        3,
        'PM',
        'Présentation du mémoire'
    ),
    (4, 'CM', 'Contenu du mémoire'),
    (
        5,
        'RP',
        'Résolution du problème'
    );

-- --------------------------------------------------------

--
-- Structure de la table `decisions_jury`
--

DROP TABLE IF EXISTS `decisions_jury`;

CREATE TABLE IF NOT EXISTS `decisions_jury` (
    `id_decision` int NOT NULL AUTO_INCREMENT,
    `lib_decision` varchar(50) NOT NULL,
    `description` text,
    `actif` tinyint(1) DEFAULT '1',
    PRIMARY KEY (`id_decision`),
    UNIQUE KEY `lib_decision` (`lib_decision`)
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
    KEY `Key_deposer_etudiant` (`num_etu`),
    KEY `Key_deposer_rapport_etud` (`id_rapport`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `domaine`
--

DROP TABLE IF EXISTS `domaine`;

CREATE TABLE IF NOT EXISTS `domaine` (
    `id_domaine` int NOT NULL AUTO_INCREMENT,
    `lib_domaine` varchar(150) NOT NULL,
    PRIMARY KEY (`id_domaine`)
) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8mb3;

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
-- Structure de la table `echeances`
--

DROP TABLE IF EXISTS `echeances`;

CREATE TABLE IF NOT EXISTS `echeances` (
    `id_echeance` int NOT NULL AUTO_INCREMENT,
    `id_inscription` int DEFAULT NULL,
    `montant` decimal(10, 2) DEFAULT NULL,
    `date_echeance` date DEFAULT NULL,
    `statut_echeance` enum(
        'En attente',
        'Payée',
        'En retard'
    ) DEFAULT NULL,
    PRIMARY KEY (`id_echeance`),
    KEY `id_inscription` (`id_inscription`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `enseignants`
--

DROP TABLE IF EXISTS `enseignants`;

CREATE TABLE IF NOT EXISTS `enseignants` (
    `id_enseignant` varchar(20) NOT NULL,
    `nom_enseignant` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `prenom_enseignant` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `tel_enseignant` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    `mail_enseignant` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
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
        '05 05 69 39 41',
        'fofana_ib_math_ab@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '123 978 Z',
        'DIALLO',
        'BOUBACAR',
        '07 07 52 19 50',
        'diallobacar@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '131 438 L',
        'ADJE',
        'ASSOHOUN',
        '01 01 23 85 22',
        'assohounadje@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '137 612 Q',
        'TOURE',
        'MOUSTAPHA ALMAMY',
        '01 01 00 71 10',
        'tam@arc-ingenierie.com',
        NULL,
        NULL,
        1
    ),
    (
        '149 070 L',
        'KOUA',
        'KONIN',
        '01 01 99 72 35',
        'ehiamba53@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '150 976 E',
        'N\'ZOUKOUDI',
        'BERNARD',
        '05 05 82 10 52',
        'nzoukoudi@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '158 851 D',
        'DEMBELE',
        'MARIAM',
        '07 07 80 42 90',
        'cdemble@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '163 737 X',
        'ABALO',
        'KOFFI ENYONAM',
        '07 07 73 08 86',
        'demavi14@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '164 300 P',
        'KANGNI',
        'KINVI',
        '07 07 83 93 99',
        'kangnikinvi@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '200 202 NR',
        'YEO',
        'TENAN',
        '07 09 68 74 66',
        'yeo.tenan21@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '2022 001M',
        'KONATE',
        'N\'GOLO',
        '07 57 69 97 69',
        'ingngolo@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '2022 513 NR',
        'TREY',
        'ZACRADA FRANCOISE ODILE',
        '07 08 28 34 47',
        'mariefranceodiletrey@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '2022 538 VE',
        'BAYOMOCK LINWA',
        'ANDRE CLAUDE',
        '05 56 71 88 27',
        'bayomock@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '2022 610 VE',
        'DJE',
        'TANOH JEAN MARCEL',
        '07 09 74 88 27',
        'djetano2017@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '210 001 NR',
        'ASSIE',
        'BROU IDA',
        '07 58 68 63 69',
        'ida_as09@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '214 704 M',
        'ASSOHOUN',
        'EGOMLI STANISLAS',
        '07 07 60 02 12',
        'stanlasso@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '233 324 X',
        'MAMADOU',
        'DIARRA',
        '07 58 88 95 88',
        'patoudiarra@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '233 497 N',
        'SOHOU',
        'TOUSSAINT',
        '01 02 44 67 46',
        'sohoutous@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '234 514 P',
        'NINDJIN',
        'AKA FULGENCE',
        '05 05 17 89 15',
        'nindjinaka_fulgence@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '239 314 B',
        'KAMANO',
        'DAMASE',
        '01 40 30 97 31',
        'kamanodamase@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '239 382 G',
        'BROU',
        'PATRICE MAGLOIRE',
        '07 55 70 16 00',
        'bpatricem@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '239 514 U',
        'DANHO',
        'EMILE',
        '07 07 50 82 63',
        'danhoemile@yahoo.com',
        NULL,
        NULL,
        1
    ),
    (
        '241 053 B',
        'MOBIO',
        'AKICHI JOSEPH',
        '01 01 00 45 73',
        'mobiojosephakichi@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '241 625 D',
        'TANOE',
        'FRANCOIS EMMANUEL',
        '07 07 09 80 04',
        'aziz_marie@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '242 840 J',
        'ADOU',
        'KABLAN JEROME',
        '07 07 07 91 91',
        'jkadou@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '244 478 M',
        'N\'ZI',
        'YAO KOFFI MODESTE',
        '01 42 13 95 95',
        'modestenzi@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '249 395 P',
        'SYLLA',
        'MOUSSA',
        '07 08 49 74 75',
        'ba_mouss@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '252 975 F',
        'KOUA',
        'BROU JEAN CLAUDE',
        '01 03 28 52 41',
        'k_brou@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '253 043 D',
        'BERETE',
        'SIAKA',
        '07 55 70 16 00',
        'beretesiaka@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '253 561 H',
        'COULIBALY',
        'ADAMA',
        '07 07 61 73 14',
        'couliba@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '253 567 F',
        'KAMARA',
        'ALIMA',
        '07 08 35 18 50',
        'Kamaradpse@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '255 664 J',
        'KOUAKOU',
        'KONAN MATHIAS',
        '07 08 99 12 79',
        'makonankouakou@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '255 685 G',
        'GOLI',
        'KONAN CHARLES ETIENNE',
        '',
        'golietienne@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '255 997 Z',
        'KOUROUMA',
        'MOUSSA',
        '05 05 70 09 19',
        'mkouroumafr@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '255 998 A',
        'MONSAN',
        'VINCENT',
        '07 07 89 94 26',
        'vmonsan@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '265 055 D',
        'BAILLY',
        'BALE',
        '07 07 09 85 84',
        'baillybale@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '265 638 J',
        'SORO',
        'ETIENNE TENA',
        '07 07 42 59 76',
        'soroet21@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '265 877 A',
        'WODIE',
        'AOBA JEAN-CHRISTOPHE',
        '07 07 40 75 51',
        'wodie_jc@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '283 496 Q',
        'CODJIA',
        'ADOLPHE',
        '05 05 98 23 20',
        'ad_wolf2000@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '285 394 T',
        'AMAN',
        'AUGUSTE',
        '07 57 01 29 59',
        'aman.auguste@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '285 396 V',
        'N\'GUESSAN',
        'TETCHI ALBIN',
        '07 59 56 45 55',
        'albintetchi@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '296 262 H',
        'TRAORE',
        'SIAKA',
        '01 04 95 95 44',
        'akaistraore@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '301 095 T',
        'SIAKA',
        'KONE',
        '05 05 01 69 75',
        'siakakone21@yahoo.com',
        NULL,
        NULL,
        1
    ),
    (
        '301 106 B',
        'GONDO',
        'YAKE',
        '07 07 78 39 71',
        'gondo.yake@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '307 815 X',
        'ELOUAFLIN',
        'ABOUO',
        '07 07 35 79 95',
        'elabouo@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '309 103 Q',
        'YODE',
        'FABRICE ARMEL EVRARD',
        '07 08 33 16 43',
        'yafevrard@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '312 434 M',
        'DJUE',
        'N\'DRI ROGER',
        '01 02 23 04 13',
        'djuendri@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '320 596 U',
        'AKEKE',
        'ERIC DAGO',
        '07 08 17 57 80',
        'ericdago@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '324 747 A',
        'BAHI',
        'LOUIS CLEMENT YOHOU',
        '07 07 74 42 68',
        'baclemsy@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '332 005 X',
        'DOSSO',
        'MOUHAMADOU',
        '01 01 13 06 47',
        'mouhamadoudoss@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '332 009 B',
        'SAMASSI',
        'LASSANA',
        '07 09 12 09 47',
        'samassilassana@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '335 522 S',
        'TUO',
        'PAUL DAVID',
        '07 07 54 98 35',
        'tuodavidpaul@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '344 437 Y',
        'DIARRASSOUBA',
        'SIRIKY',
        '07 49 35 90 32',
        'dsiriky@yahoo.com',
        NULL,
        NULL,
        1
    ),
    (
        '344 438 H',
        'TOURE',
        'IBRAHIMA',
        '07 07 51 15 87',
        'toureibt@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '344 439 A',
        'YANGA',
        'KOUASSI KOUASSI SERGE',
        '07 08 28 12 44',
        'yanga.k.k.serge@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '344 444 F',
        'OKOU',
        'A KPETIHI SAHOUA HYPOLITHE',
        '01 05 82 58 12',
        'okouakpetihi@hotmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '345 005 C',
        'COULIBALY',
        'NAMORY',
        '07 07 67 56 95',
        'namory.coulibaly@univ-fhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '346 123 X',
        'AYIBE',
        'ARISTIDE',
        '07 47 68 72 27',
        'aristideayibe@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '346 124 Y',
        'COULIBALY',
        'PIE',
        '01 40 35 12 90',
        'foussenico14@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '346 309 S',
        'KAYE BI',
        'KOUAI BERTIN',
        '07 09 31 41 72',
        'kayebi314@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '364 868 L',
        'COULIBALY',
        'BAKARY',
        '01 02 69 69 58',
        'coulibaly_bakaryfr@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '364 870 J',
        'OWO',
        'KOUASSI JEAN MARC',
        '05 04 28 22 38',
        'marc.owo@univ-fhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '366 733 E',
        'DIARRA',
        'NOUFFOU',
        '05 56 34 34 00',
        'nouffoud@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '389 845 F',
        'SEKA',
        'LOUIS-PAUL',
        '01 01 13 34 05',
        'lpseka@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '389 891 W',
        'ZOKAGOA',
        'JEAN-MARIE',
        '07 07 36 59 20',
        'zokagoa@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '395 614 V',
        'SILUE',
        'MARIAME',
        '',
        'mamsilk@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '398 026 W',
        'ABDOU',
        'MAÏGA',
        '07 48 39 20 20',
        'maiga.abdou@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '417 722 P',
        'AHIPO',
        'KWALHA YVES MARCEL',
        '07 79 39 32 12',
        'yahipo@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '426 090 P',
        'KOUA',
        'KPAAGNI ALEX JEREMIE',
        '07 07 26 70 83',
        'jeremiekoua@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '426 918 M',
        'BAROU',
        'ROPLO ANGE-PAULIN',
        '07 08 08 00 73',
        'barouange@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '440 254 S',
        'TCHOUDI',
        'OLIVIER',
        '07 57 57 21 59',
        'olivier.tchoudi53@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '444 617 Z',
        'AYIKPA',
        'KACOUTCHY JEAN',
        '07 08 79 19 90',
        'ayikpajean@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '456 909 B',
        'DIABAGATE',
        'AMADOU',
        '05 67 95 42 16',
        'ahmadou.diabagate@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '473 396 Z',
        'KONE',
        'BAKARY',
        '',
        'dohirimin@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '474 804 U',
        'AMOUZOU',
        'GILDAS YAOVI',
        '',
        'gildasamouzou2@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '474 827 J',
        'N\'DRIN',
        'APALA JULIEN',
        '',
        'lecorrige@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '497 246 T',
        'ABLE',
        'ZOBO VINCENT DE PAUL',
        '',
        'vincentdepaulzobo@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '500 076 B',
        'FEDIDA',
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
        '05 05 61 29 47',
        'justfeuto@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '501 081 N',
        'SAWADOGO',
        'AMADOU',
        '07 48 78 98 56',
        'amadou.sawadogo@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '502 460 B',
        'DIALLO',
        'MOHAMED BOBO',
        '07 77 01 45 22',
        'diallo.med@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '7',
        'Koua',
        'Brou',
        '',
        'kouabrou@gmail.com',
        2,
        1,
        NULL
    ),
    (
        '819 665 H',
        'SITIONON',
        'GOSSOUHON',
        '01 03 30 04 86',
        'gossouhon.sitionon@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '826 227 R',
        'KOUAKOU',
        'KOUAME FLORENT',
        '07 78 73 87 80',
        'kouameflorentk@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '826 240 A',
        'KRAIDI',
        'ANOH YANNICK',
        '',
        'kayanoh2000@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '828 190 T',
        'OUATTARA',
        'MARIAM',
        '01 53 36 69 11',
        'lajourne21@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '857 962 Z',
        'IBRAHIMA',
        'BAKAYOKO',
        '01 20 20 20 14',
        'bakayoko.ibrahima1@ufhb.edu.ci',
        NULL,
        NULL,
        1
    ),
    (
        '872 943 V',
        'YAO',
        'EKOUN NARCISSE',
        '07 09 43 44 36',
        'narcisseyek@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '900 021 AUF',
        'OUATTARA',
        'CHRISTELLE',
        '',
        'nanihioouattara@yahoo.fr',
        NULL,
        NULL,
        1
    ),
    (
        '910 800 A',
        'KOUASSI',
        'BROU MEDARD',
        '01 42 47 95 49',
        'medardkoisy@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '952 300 A3',
        'MONSAN',
        'VINCENT',
        '',
        'monsanv@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        '953 001 A',
        'SAMAGASSI',
        'SOULEYMANE',
        '',
        'samagassisouley@gmail.com',
        NULL,
        NULL,
        1
    ),
    (
        'VE000001',
        'TEMBELY',
        'SALIFOU',
        '',
        '',
        NULL,
        NULL,
        4
    ),
    (
        'VE000002',
        'WAH',
        'MEDARD',
        '07 07 09 26 19',
        'medardwah@gmail.com',
        NULL,
        NULL,
        4
    ),
    (
        'VE000003',
        'KOTEI',
        'SAMUEL',
        '07 07 35 47 28',
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
    `id_enseignant` int NOT NULL,
    `id_qualite_jury` int NOT NULL,
    `date_composer_jury` int NOT NULL,
    KEY `fk_composer_enseignant` (`id_enseignant`),
    KEY `fk_composer_role` (`id_qualite_jury`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `entreprises`
--

DROP TABLE IF EXISTS `entreprises`;

CREATE TABLE IF NOT EXISTS `entreprises` (
    `id_entreprise` int NOT NULL AUTO_INCREMENT,
    `lib_entreprise` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `lien_logo_entreprise` varchar(256) NOT NULL,
    PRIMARY KEY (`id_entreprise`)
) ENGINE = InnoDB AUTO_INCREMENT = 13 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `entreprises`
--

INSERT INTO
    `entreprises` (
        `id_entreprise`,
        `lib_entreprise`,
        `lien_logo_entreprise`
    )
VALUES (
        1,
        'Orange Côte d\'Ivoire',
        'logos/orange_ci.png'
    ),
    (
        2,
        'MTN Côte d\'Ivoire',
        'logos/mtn_ci.png'
    ),
    (
        3,
        'Moov Africa Côte d\'Ivoire',
        'logos/moov_ci.png'
    ),
    (
        4,
        'Société Générale Côte d\'Ivoire',
        'logos/sgci.png'
    ),
    (
        5,
        'NSIA Banque Côte d\'Ivoire',
        'logos/nsia.png'
    ),
    (
        6,
        'SODECI',
        'logos/sodeci.png'
    ),
    (
        7,
        'CIE (Compagnie Ivoirienne d\'Électricité)',
        'logos/cie.png'
    ),
    (
        8,
        'Société Ivoirienne de Raffinage (SIR)',
        'logos/sir.png'
    ),
    (
        9,
        'Nestlé Côte d\'Ivoire',
        'logos/nestle_ci.png'
    ),
    (
        10,
        'Unilever Côte d\'Ivoire',
        'logos/unilever_ci.png'
    ),
    (12, 'MasterSolut', '');

-- --------------------------------------------------------

--
-- Structure de la table `etablissement_origine`
--

DROP TABLE IF EXISTS `etablissement_origine`;

CREATE TABLE IF NOT EXISTS `etablissement_origine` (
    `id_etablissement` int NOT NULL AUTO_INCREMENT,
    `libelle_long` varchar(125) NOT NULL,
    `libelle_court` varchar(20) NOT NULL,
    PRIMARY KEY (`id_etablissement`)
) ENGINE = InnoDB AUTO_INCREMENT = 5 DEFAULT CHARSET = utf8mb3;

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
    `num_ident_etud` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    `num_carte_etud` varchar(25) NOT NULL,
    `nom_etu` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `prenom_etu` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `email_etu` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `date_naiss_etu` date NOT NULL,
    `genre_etu` int NOT NULL,
    `promotion_etu` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `id_niveau` int DEFAULT NULL,
    `id_annee_acad` int DEFAULT NULL,
    PRIMARY KEY (`num_carte_etud`),
    KEY `fk_etudiant_niveau` (`id_niveau`),
    KEY `fk_etudiant_annee_acad` (`id_annee_acad`),
    KEY `genre_etu` (`genre_etu`)
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
        `email_etu`,
        `date_naiss_etu`,
        `genre_etu`,
        `promotion_etu`,
        `id_niveau`,
        `id_annee_acad`
    )
VALUES (
        'CI0106187064',
        'CI0106187064',
        'Karamoko',
        'Ibrahim  ',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0108207902',
        'CI0108207902',
        'Doumun',
        'Mékapeu solange ',
        '',
        '0000-00-00',
        2,
        '2004-2005',
        NULL,
        NULL
    ),
    (
        'CI0108207903',
        'CI0108207903',
        'Ebe',
        'Gbebi Alex Auguste',
        '',
        '0000-00-00',
        1,
        '2014-2015',
        NULL,
        NULL
    ),
    (
        'CI0108211061',
        'CI0108211061',
        'Guindo',
        'Abdoulaye  ',
        '',
        '0000-00-00',
        1,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'CI0108212628',
        'CI0108212628',
        'Koulaté',
        'Douai Yves-Alain ',
        '',
        '0000-00-00',
        1,
        '2014-2015',
        NULL,
        NULL
    ),
    (
        'CI0109224375',
        'CI0109224375',
        'Atsé',
        'Nina Larissa ',
        '',
        '0000-00-00',
        2,
        '2013-2014',
        NULL,
        NULL
    ),
    (
        'BOBJ2203880001',
        'CI0109224377',
        'Bobou',
        'Eliézer Josué ',
        '',
        '0000-00-00',
        1,
        '2013-2014',
        NULL,
        NULL
    ),
    (
        'KOUA0204890001',
        'CI0109243169',
        'Kouassi',
        'Aka Marius ',
        '',
        '0000-00-00',
        1,
        '2013-2014',
        NULL,
        NULL
    ),
    (
        'CI0110242904',
        'CI0110242904',
        'Konan',
        'Yao Franck ',
        '',
        '0000-00-00',
        1,
        '2014-2015',
        NULL,
        NULL
    ),
    (
        'CI0110243163',
        'CI0110243163',
        'Coulou',
        'Kouadio Léandre ',
        '',
        '0000-00-00',
        1,
        '2013-2014',
        NULL,
        NULL
    ),
    (
        'CI0110243311',
        'CI0110243311',
        'Koffi',
        'Mekhan Girault ',
        '',
        '0000-00-00',
        1,
        '2013-2014',
        NULL,
        NULL
    ),
    (
        'CI0111272399',
        'CI0111272399',
        'Agnaramon',
        'Boris Carnot ',
        '',
        '0000-00-00',
        1,
        '2014-2015',
        NULL,
        NULL
    ),
    (
        'CI0111272409',
        'CI0111272409',
        'Mohamed',
        'Ibrahim Charles ',
        '',
        '0000-00-00',
        1,
        '2015-2016',
        NULL,
        NULL
    ),
    (
        'CI0111272412',
        'CI0111272412',
        'Karidioula',
        'Homar  ',
        '',
        '0000-00-00',
        1,
        '2015-2016',
        NULL,
        NULL
    ),
    (
        'CI0111272417',
        'CI0111272417',
        'Ahouana',
        'Akichi Roche Wilfried',
        '',
        '0000-00-00',
        1,
        '2010-2011',
        NULL,
        NULL
    ),
    (
        'CI0112272423',
        'CI0112272423',
        'Atta',
        'Amoan Aurélie Nadia',
        '',
        '0000-00-00',
        2,
        '2015-2016',
        NULL,
        NULL
    ),
    (
        'CI0112272430',
        'CI0112272430',
        'Koissi',
        'Elysée Morel James',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0112272431',
        'CI0112272431',
        'Komana',
        'Parfait  ',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0112272435',
        'CI0112272435',
        'Kra',
        'Yao Ghislain ',
        '',
        '0000-00-00',
        1,
        '2015-2016',
        NULL,
        NULL
    ),
    (
        'CI0112272443',
        'CI0112272443',
        'N\'zi',
        'Yao Sidney Maurel',
        '',
        '0000-00-00',
        1,
        '2015-2016',
        NULL,
        NULL
    ),
    (
        'CI0113272684',
        'CI0113272684',
        'Amoa',
        'Ablan Stéphanie ',
        '',
        '0000-00-00',
        2,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0113272986',
        'CI0113272986',
        'Konaté',
        'Dotégué Léon-Cédric ',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'KEUF2403950001',
        'CI0113273194',
        'Keulegbe',
        'Franck-Cyril  ',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0113273196',
        'CI0113273196',
        'Kondou',
        'Terrence Yves Fallon',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0113273198',
        'CI0113273198',
        'N\'guessan',
        'Léandre Yvon ',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0113273793',
        'CI0113273793',
        'Ouattara',
        'kobenan Landry ',
        '',
        '0000-00-00',
        1,
        '2015-2016',
        NULL,
        NULL
    ),
    (
        'CI0114277408',
        'CI0114277408',
        'Balié',
        'Gnahoua Marc-Michel ',
        '',
        '0000-00-00',
        1,
        '2017-2018',
        NULL,
        NULL
    ),
    (
        'CI0114278909',
        'CI0114278909',
        'Diao',
        'Moussa  ',
        '',
        '0000-00-00',
        1,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'CI0114279119',
        'CI0114279119',
        'N\'guessan',
        'Kadjo Léon ',
        '',
        '0000-00-00',
        1,
        '2017-2018',
        NULL,
        NULL
    ),
    (
        'CI0114283286',
        'CI0114283286',
        'Doumbia',
        'Anliou Badrah Kévin',
        '',
        '0000-00-00',
        1,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'CI0114283821',
        'CI0114283821',
        'N\'cho',
        'Chippaux Pierrette Naomie',
        '',
        '0000-00-00',
        2,
        '2017-2018',
        NULL,
        NULL
    ),
    (
        'OUAD2508910002',
        'CI0114284424',
        'Ouattara',
        'Dramane Fanhyogo ',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0114284425',
        'CI0114284425',
        'Mondah',
        'Aristide Arnaud ',
        '',
        '0000-00-00',
        1,
        '2017-2018',
        NULL,
        NULL
    ),
    (
        'CI0114284687',
        'CI0114284687',
        'Diarrassouba',
        'Nagnon Mamadou ',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'EHIA2912960001',
        'CI0115290088',
        'Ehinon',
        'Arriko Désiré Ebenezer',
        '',
        '0000-00-00',
        1,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'CI0115290089',
        'CI0115290089',
        'Atokoli',
        'Kra Affoue Larissa Estelle',
        '',
        '0000-00-00',
        2,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'CI0115290094',
        'CI0115290094',
        'Dosso',
        'Abdoul-Rhamane  ',
        '',
        '0000-00-00',
        1,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'DIAM2310950002',
        'CI0115290103',
        'Diarrassouba',
        'Mohamed  ',
        '',
        '0000-00-00',
        1,
        '2019-2020',
        NULL,
        NULL
    ),
    (
        '14-24-LMI',
        'CI0115290108',
        'Kacou',
        'Ehouman Narcisse Innocent',
        '',
        '0000-00-00',
        1,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'CI0115291194',
        'CI0115291194',
        'Badolo',
        'Koffi Marius ',
        '',
        '0000-00-00',
        1,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'CI0115291243',
        'CI0115291243',
        'Amand',
        'Kouakou Yann-Axel ',
        '',
        '0000-00-00',
        1,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'CI0115301569',
        'CI0115301569',
        'Koki',
        'Israël  ',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0115301657',
        'CI0115301657',
        'Koffi',
        'Kousso Claverie De Camille',
        '',
        '0000-00-00',
        2,
        '2017-2018',
        NULL,
        NULL
    ),
    (
        'CI0115301658',
        'CI0115301658',
        'Bamba',
        'Aboubakar Siriki ',
        '',
        '0000-00-00',
        1,
        '2016-2017',
        NULL,
        NULL
    ),
    (
        'CI0115302301',
        'CI0115302301',
        'Akpagnon',
        'Koffi  ',
        '',
        '0000-00-00',
        1,
        '2018-2019',
        NULL,
        NULL
    ),
    (
        'CI0115303004',
        'CI0115303004',
        'Traoré',
        'Fatime  ',
        '',
        '0000-00-00',
        2,
        '2017-2018',
        NULL,
        NULL
    ),
    (
        'DEML1504910001',
        'CI0116311231',
        'Dembélé',
        'Loseni  ',
        '',
        '0000-00-00',
        1,
        '2019-2020',
        NULL,
        NULL
    ),
    (
        'COUA2104970001',
        'CI0116311551',
        'Coulibaly',
        'Awa  ',
        '',
        '0000-00-00',
        2,
        '2019-2020',
        NULL,
        NULL
    ),
    (
        '134108790/DIAR',
        'CI0117331488',
        'Diarrassouba',
        'Gniriwa Aminata ',
        '',
        '0000-00-00',
        2,
        '2019-2020',
        NULL,
        NULL
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
    `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
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
    `id_critere` int NOT NULL,
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
    `lib_fonction` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `origine_entreprise` tinyint(1) NOT NULL,
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
VALUES (
        'CC',
        'Chargé de communication',
        0
    ),
    (
        'CD',
        'Chef de département',
        0
    ),
    ('CP', 'Chef de projet', 1),
    ('DG', 'Directeur général', 1),
    (
        'DL',
        'Directeur de laboratoire',
        0
    ),
    (
        'DP',
        'Directeur pédagogique',
        0
    ),
    (
        'DR',
        'Directeur de recherche',
        0
    ),
    (
        'DT',
        'Directeur technique',
        1
    ),
    ('DU', 'Directeur Ufr', 0),
    (
        'RF',
        'Responsable de filière',
        0
    ),
    (
        'RN',
        'Responsable de niveau',
        0
    ),
    (
        'SP',
        'Secrétaire principal',
        0
    ),
    ('VP', 'Vice Président', 0);

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
) ENGINE = InnoDB AUTO_INCREMENT = 116 DEFAULT CHARSET = utf8mb3;

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
        15,
        'COM_ESPACES',
        'Espaces',
        'Espaces',
        NULL,
        '#',
        'fas fa-chalkboard-teacher',
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
        '?=redaction_compte_rendu',
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
        '?=programmation_soutenance',
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
        '?=edition_bulletin',
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
    );

-- --------------------------------------------------------

--
-- Structure de la table `genre`
--

DROP TABLE IF EXISTS `genre`;

CREATE TABLE IF NOT EXISTS `genre` (
    `id_genre` int NOT NULL AUTO_INCREMENT,
    `libelle_genre` varchar(10) NOT NULL,
    PRIMARY KEY (`id_genre`)
) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `genre`
--

INSERT INTO
    `genre` (`id_genre`, `libelle_genre`)
VALUES (1, 'Masculin'),
    (2, 'Féminin'),
    (3, 'Neutre');

-- --------------------------------------------------------

--
-- Structure de la table `grade`
--

DROP TABLE IF EXISTS `grade`;

CREATE TABLE IF NOT EXISTS `grade` (
    `id_grade` varchar(2) NOT NULL,
    `lib_grade` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    PRIMARY KEY (`id_grade`),
    UNIQUE KEY `lib_grade` (`lib_grade`)
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
    `lib_GU` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `id_type_utilisateur` int DEFAULT NULL,
    PRIMARY KEY (`id_GU`),
    KEY `idx_groupe_utilisateur_type` (`id_type_utilisateur`)
) ENGINE = InnoDB AUTO_INCREMENT = 28 DEFAULT CHARSET = utf8mb3;

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
    `encadrant_entreprise` varchar(100) NOT NULL,
    `email_encadrant` varchar(100) NOT NULL,
    `telephone_encadrant` varchar(20) NOT NULL,
    PRIMARY KEY (`id_info_stage`),
    KEY `num_etu` (`num_etu`),
    KEY `id_entreprise` (`id_entreprise`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions`
--

DROP TABLE IF EXISTS `inscriptions`;

CREATE TABLE IF NOT EXISTS `inscriptions` (
    `id_inscription` int NOT NULL AUTO_INCREMENT,
    `id_annee_acad` int NOT NULL,
    `id_etudiant` varchar(25) DEFAULT NULL,
    `num_versement` int NOT NULL,
    `date_versement` datetime DEFAULT NULL,
    `montant_verser` int NOT NULL,
    `id_mode_paiement` int NOT NULL,
    `num_piece_mp` int NOT NULL,
    `solde` decimal(10, 2) NOT NULL,
    PRIMARY KEY (`id_inscription`),
    KEY `id_etudiant` (`id_etudiant`),
    KEY `id_annee_acad` (`id_annee_acad`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `mentions`
--

DROP TABLE IF EXISTS `mentions`;

CREATE TABLE IF NOT EXISTS `mentions` (
    `id_mention` int NOT NULL AUTO_INCREMENT,
    `lib_mention` varchar(50) NOT NULL,
    `actif` tinyint(1) DEFAULT '1',
    PRIMARY KEY (`id_mention`),
    UNIQUE KEY `lib_mention` (`lib_mention`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

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
    `id_mode_paiement` int NOT NULL AUTO_INCREMENT,
    `code_mode_paiement` varchar(2) NOT NULL,
    `libelle_mode_paement` varchar(25) NOT NULL,
    PRIMARY KEY (`id_mode_paiement`)
) ENGINE = InnoDB AUTO_INCREMENT = 8 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `mode_paiement`
--

INSERT INTO
    `mode_paiement` (
        `id_mode_paiement`,
        `code_mode_paiement`,
        `libelle_mode_paement`
    )
VALUES (1, 'ES', 'Espèce'),
    (2, 'VR', 'Virement'),
    (3, 'CH', 'Chèque'),
    (4, 'OM', 'Orange money'),
    (5, 'WV', 'Wave'),
    (6, 'MN', 'Mtn money'),
    (7, 'MV', 'Moov money');

-- --------------------------------------------------------

--
-- Structure de la table `niveau_acces_donnees`
--

DROP TABLE IF EXISTS `niveau_acces_donnees`;

CREATE TABLE IF NOT EXISTS `niveau_acces_donnees` (
    `id_niveau_acces_donnees` int NOT NULL AUTO_INCREMENT,
    `lib_niveau_acces_donnees` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
    `lib_approb` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    PRIMARY KEY (`id_approb`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `niveau_etude`
--

DROP TABLE IF EXISTS `niveau_etude`;

CREATE TABLE IF NOT EXISTS `niveau_etude` (
    `id_niv_etude` int NOT NULL AUTO_INCREMENT,
    `lib_niv_etude` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `id_enseignant` int DEFAULT NULL,
    `montant_scolarite` decimal(10, 2) DEFAULT NULL,
    `montant_inscription` decimal(10, 2) NOT NULL,
    PRIMARY KEY (`id_niv_etude`),
    KEY `id_enseignant` (`id_enseignant`)
) ENGINE = InnoDB AUTO_INCREMENT = 3 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `niveau_etude`
--

INSERT INTO
    `niveau_etude` (
        `id_niv_etude`,
        `lib_niv_etude`,
        `id_enseignant`,
        `montant_scolarite`,
        `montant_inscription`
    )
VALUES (
        1,
        'Master 1',
        7,
        975000.00,
        450000.00
    ),
    (
        2,
        'Master 2',
        7,
        1025000.00,
        450000.00
    );

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

DROP TABLE IF EXISTS `notes`;

CREATE TABLE IF NOT EXISTS `notes` (
    `id` int NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `id_annee_acad` int DEFAULT NULL,
    `moyenne_M1` decimal(4, 2) NOT NULL,
    `moyenne_M2` decimal(4, 2) NOT NULL,
    `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
    `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `notes_ibfk_1` (`num_etu`),
    KEY `fk_notes_annee_acad` (`id_annee_acad`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `occuper`
--

DROP TABLE IF EXISTS `occuper`;

CREATE TABLE IF NOT EXISTS `occuper` (
    `id_fonction` int NOT NULL,
    `id_enseignant` int NOT NULL,
    `date_occupation` date NOT NULL,
    PRIMARY KEY (
        `id_fonction`,
        `id_enseignant`
    ),
    KEY `Key_occuper_enseignant` (`id_enseignant`),
    KEY `Key_occuper_fonction` (`id_fonction`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
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
) ENGINE = InnoDB AUTO_INCREMENT = 9 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `password_resets`
--

INSERT INTO
    `password_resets` (
        `id`,
        `email`,
        `token`,
        `expires_at`,
        `used`,
        `created_at`
    )
VALUES (
        1,
        'iadjoannejemima',
        'd9dc8e30fbfb86ab8766274b1a5018c735586988b065eaaf8c07fce129e00917',
        '2026-02-12 16:33:13',
        0,
        '2026-02-12 15:33:13'
    ),
    (
        2,
        'iadjoannejemima',
        '71b1968cc384b6a86f4563fd1e91d33a915266742f0e9db17ed5906bacbde11e',
        '2026-02-12 17:18:43',
        0,
        '2026-02-12 16:18:43'
    ),
    (
        3,
        'iannejemima@gmail.com',
        'b46ed2ed4be30d81006cc0ae4186a49694cee68957cf45ea6ffd8bc443ce7f68',
        '2026-02-12 17:28:22',
        0,
        '2026-02-12 16:28:22'
    ),
    (
        4,
        'iannejemima@gmail.com',
        '1e8503fe4f00ef50c81bb97448625903324e410d59ae2d152d40365a8ab3c030',
        '2026-02-12 17:32:12',
        0,
        '2026-02-12 16:32:12'
    ),
    (
        5,
        'iannejemima@gmail.com',
        'a76d7eda50bb1a6893479b06f642327be9d8c688ac30e32ae0a59135de6cd96a',
        '2026-02-12 17:32:25',
        0,
        '2026-02-12 16:32:25'
    ),
    (
        6,
        'iannejemima@gmail.com',
        'd15b908b533e9168228584b98a38e5eeffd80316e6c948c0e8c182a1b1d8ac91',
        '2026-02-12 17:32:37',
        0,
        '2026-02-12 16:32:37'
    ),
    (
        7,
        'iannejemima@gmail.com',
        'cc91aeee5c00f65cd9228d3f2847b2abf8adf80ebdf9779e4f957ef8f4cbf7e7',
        '2026-02-12 17:44:28',
        0,
        '2026-02-12 16:44:28'
    ),
    (
        8,
        'iannejemima@gmail.com',
        'dae5a0cbb8859d407e852938c2e6dcedbbc2f011c7e40849af2834c338de90cc',
        '2026-02-12 17:54:17',
        1,
        '2026-02-12 16:54:17'
    );

-- --------------------------------------------------------

--
-- Structure de la table `pdf_cr_pv_rapetd`
--

DROP TABLE IF EXISTS `pdf_cr_pv_rapetd`;

CREATE TABLE IF NOT EXISTS `pdf_cr_pv_rapetd` (
    `id_arch` int NOT NULL AUTO_INCREMENT,
    `libelle` varchar(200) NOT NULL,
    PRIMARY KEY (`id_arch`)
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
    `peut_voir` tinyint(1) DEFAULT '0',
    `peut_creer` tinyint(1) DEFAULT '0',
    `peut_modifier` tinyint(1) DEFAULT '0',
    `peut_supprimer` tinyint(1) DEFAULT '0',
    `date_attribution` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_permission`),
    UNIQUE KEY `unique_permission` (`id_GU`, `id_fonctionnalite`),
    KEY `id_fonctionnalite` (`id_fonctionnalite`)
) ENGINE = InnoDB AUTO_INCREMENT = 1765 DEFAULT CHARSET = utf8mb3;

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
        1718,
        5,
        93,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1719,
        5,
        94,
        1,
        0,
        0,
        0,
        '2026-02-11 15:07:14'
    ),
    (
        1720,
        5,
        95,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1721,
        5,
        96,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1722,
        5,
        97,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1723,
        5,
        98,
        1,
        0,
        0,
        0,
        '2026-02-11 15:07:14'
    ),
    (
        1724,
        5,
        99,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1725,
        5,
        100,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1726,
        5,
        101,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1727,
        5,
        91,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1728,
        5,
        102,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1729,
        5,
        103,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1730,
        5,
        104,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1731,
        5,
        105,
        1,
        0,
        0,
        0,
        '2026-02-11 15:07:14'
    ),
    (
        1732,
        5,
        106,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1733,
        5,
        107,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1734,
        5,
        108,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1735,
        5,
        109,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1736,
        5,
        73,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1737,
        5,
        2,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1738,
        5,
        110,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1739,
        5,
        111,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1740,
        5,
        112,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1741,
        5,
        74,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1742,
        5,
        113,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1743,
        5,
        75,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1744,
        5,
        30,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1745,
        5,
        81,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1746,
        5,
        76,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1747,
        5,
        50,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1748,
        5,
        51,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1749,
        5,
        52,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1750,
        5,
        55,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1751,
        5,
        78,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1752,
        5,
        114,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1753,
        5,
        115,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1754,
        5,
        79,
        1,
        1,
        1,
        1,
        '2026-02-11 15:07:14'
    ),
    (
        1755,
        5,
        38,
        1,
        0,
        1,
        0,
        '2026-02-11 15:07:14'
    ),
    (
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
    );

-- --------------------------------------------------------

--
-- Structure de la table `personnel_admin`
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `pister`
--

DROP TABLE IF EXISTS `pister`;

CREATE TABLE IF NOT EXISTS `pister` (
    `id_piste` int NOT NULL AUTO_INCREMENT,
    `id_utilisateur` int NOT NULL,
    `action` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Type d''action (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc.)',
    `statut_action` enum('Erreur', 'Succès') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `nom_table` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Nom de la table concernée',
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_piste`),
    KEY `idx_utilisateur` (`id_utilisateur`),
    KEY `idx_action` (`action`),
    KEY `idx_table` (`nom_table`),
    KEY `idx_created_at` (`date_creation`),
    KEY `idx_utilisateur_action` (`id_utilisateur`, `action`),
    KEY `id_action` (`action`),
    KEY `id_action_2` (`action`)
) ENGINE = InnoDB AUTO_INCREMENT = 59 DEFAULT CHARSET = utf8mb3;

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
        'Connexion',
        'Succès',
        'utilisateur',
        '2026-02-09 14:48:38'
    ),
    (
        2,
        5,
        'acces_refuse',
        'Erreur',
        'permission',
        '2026-02-09 15:52:38'
    ),
    (
        3,
        5,
        'acces_refuse',
        'Erreur',
        'permission',
        '2026-02-09 15:55:53'
    ),
    (
        4,
        5,
        'acces_refuse',
        'Erreur',
        'permission',
        '2026-02-09 15:58:14'
    ),
    (
        5,
        5,
        'acces_refuse',
        'Erreur',
        'permission',
        '2026-02-09 15:59:57'
    ),
    (
        6,
        5,
        'Création',
        'Succès',
        'annee_academique',
        '2026-02-09 16:04:02'
    ),
    (
        7,
        5,
        'Création',
        'Succès',
        'annee_academique',
        '2026-02-09 16:04:56'
    ),
    (
        8,
        5,
        'Déconnexion',
        'Succès',
        'utilisateur',
        '2026-02-10 22:03:29'
    ),
    (
        9,
        5,
        'Connexion',
        'Succès',
        'utilisateur',
        '2026-02-10 22:03:40'
    ),
    (
        10,
        5,
        'Connexion',
        'Succès',
        'utilisateur',
        '2026-02-10 22:33:36'
    ),
    (
        11,
        5,
        'Déconnexion',
        'Succès',
        'utilisateur',
        '2026-02-11 01:09:38'
    ),
    (
        12,
        5,
        'Connexion',
        'Succès',
        'utilisateur',
        '2026-02-11 01:09:46'
    ),
    (
        13,
        5,
        'Modification',
        'Succès',
        'permissions',
        '2026-02-11 01:11:54'
    ),
    (
        14,
        5,
        'Modification',
        'Succès',
        'permissions',
        '2026-02-11 01:59:43'
    ),
    (
        15,
        5,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-11 13:41:14'
    ),
    (
        16,
        5,
        'Modification',
        'Succès',
        'permissions',
        '2026-02-11 14:46:13'
    ),
    (
        17,
        5,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-11 14:51:13'
    ),
    (
        18,
        5,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-11 14:52:15'
    ),
    (
        19,
        5,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-11 14:52:18'
    ),
    (
        20,
        5,
        'Modification',
        'Succès',
        'permissions',
        '2026-02-11 15:07:14'
    ),
    (
        21,
        5,
        'Connexion',
        'Succès',
        'utilisateur',
        '2026-02-12 02:31:56'
    ),
    (
        22,
        5,
        'Connexion',
        'Succès',
        'utilisateur',
        '2026-02-12 12:37:02'
    ),
    (
        23,
        5,
        'Création',
        'Succès',
        'annee_academique',
        '2026-02-12 12:50:04'
    ),
    (
        24,
        5,
        'Création',
        'Erreur',
        'annee_academique',
        '2026-02-12 12:50:04'
    ),
    (
        25,
        5,
        'Création',
        'Succès',
        'niveau_etude',
        '2026-02-12 12:50:55'
    ),
    (
        26,
        5,
        'Création',
        'Succès',
        'niveau_etude',
        '2026-02-12 12:51:13'
    ),
    (
        27,
        5,
        'Création',
        'Succès',
        'semestre',
        '2026-02-12 12:51:28'
    ),
    (
        28,
        5,
        'Création',
        'Succès',
        'semestre',
        '2026-02-12 12:51:33'
    ),
    (
        29,
        5,
        'Création',
        'Succès',
        'semestre',
        '2026-02-12 12:51:41'
    ),
    (
        30,
        5,
        'Création',
        'Succès',
        'etudiants',
        '2026-02-12 12:53:39'
    ),
    (
        31,
        5,
        'Création',
        'Erreur',
        'utilisateur',
        '2026-02-12 15:33:13'
    ),
    (
        32,
        5,
        'Création',
        'Erreur',
        'envoi_acces',
        '2026-02-12 16:18:43'
    ),
    (
        33,
        5,
        'Création',
        'Erreur',
        'envoi_acces',
        '2026-02-12 16:28:24'
    ),
    (
        34,
        5,
        'Création',
        'Erreur',
        'envoi_acces',
        '2026-02-12 16:32:13'
    ),
    (
        35,
        5,
        'Création',
        'Erreur',
        'envoi_acces',
        '2026-02-12 16:32:26'
    ),
    (
        36,
        5,
        'Création',
        'Erreur',
        'envoi_acces',
        '2026-02-12 16:32:39'
    ),
    (
        37,
        5,
        'Création',
        'Erreur',
        'envoi_acces',
        '2026-02-12 16:44:29'
    ),
    (
        38,
        5,
        'Création',
        'Succès',
        'envoi_acces',
        '2026-02-12 16:54:20'
    ),
    (
        39,
        110,
        'Connexion',
        'Succès',
        'utilisateur',
        '2026-02-12 17:08:22'
    ),
    (
        40,
        5,
        'Modification',
        'Succès',
        'permissions',
        '2026-02-12 17:11:32'
    ),
    (
        41,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 17:11:38'
    ),
    (
        42,
        5,
        'Modification',
        'Succès',
        'permissions',
        '2026-02-12 17:11:49'
    ),
    (
        43,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 17:11:53'
    ),
    (
        44,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 17:11:56'
    ),
    (
        45,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 17:12:07'
    ),
    (
        46,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 17:12:24'
    ),
    (
        47,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 17:15:18'
    ),
    (
        48,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 17:33:23'
    ),
    (
        49,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 17:33:53'
    ),
    (
        50,
        5,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 17:45:45'
    ),
    (
        51,
        5,
        'Modification',
        'Succès',
        'etudiants',
        '2026-02-12 18:10:11'
    ),
    (
        52,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 18:12:25'
    ),
    (
        53,
        110,
        'Accès',
        'Succès',
        'tableau_de_bord',
        '2026-02-12 18:12:25'
    ),
    (
        54,
        110,
        'Déconnexion',
        'Succès',
        'utilisateur',
        '2026-02-12 18:12:27'
    ),
    (
        55,
        110,
        'Connexion',
        'Succès',
        'utilisateur',
        '2026-02-12 18:13:17'
    ),
    (
        56,
        110,
        'Connexion',
        'Succès',
        'utilisateur',
        '2026-02-12 18:13:17'
    ),
    (
        57,
        5,
        'Modification',
        'Succès',
        'etudiants',
        '2026-02-13 13:43:21'
    ),
    (
        58,
        5,
        'Modification',
        'Succès',
        'etudiants',
        '2026-02-13 14:14:38'
    );

-- --------------------------------------------------------

--
-- Structure de la table `programmer_soutenance`
--

DROP TABLE IF EXISTS `programmer_soutenance`;

CREATE TABLE IF NOT EXISTS `programmer_soutenance` (
    `num_soutenance` varchar(20) NOT NULL,
    `num_etud` varchar(25) NOT NULL,
    `theme_soutenance` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `id_domaine` int NOT NULL,
    `id_session` int NOT NULL,
    `id_salle` int DEFAULT NULL,
    `date_soutenance` date DEFAULT NULL,
    `heure_soutenance` time DEFAULT NULL,
    PRIMARY KEY (`num_soutenance`),
    KEY `num_etud` (`num_etud`),
    KEY `id_salle` (`id_salle`),
    KEY `id_domaine` (`id_domaine`),
    KEY `id_session` (`id_session`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `qualite_jury`
--

DROP TABLE IF EXISTS `qualite_jury`;

CREATE TABLE IF NOT EXISTS `qualite_jury` (
    `id_role_jury` int NOT NULL AUTO_INCREMENT,
    `code_qltjury` varchar(2) NOT NULL,
    `lib_role` varchar(50) NOT NULL,
    PRIMARY KEY (`id_role_jury`)
) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `qualite_jury`
--

INSERT INTO
    `qualite_jury` (
        `id_role_jury`,
        `code_qltjury`,
        `lib_role`
    )
VALUES (1, 'PJ', 'Président'),
    (2, 'DM', 'Directeur mémoire'),
    (3, 'EX', 'Examinateur'),
    (4, 'EN', 'Encadrant'),
    (5, 'MS', 'Maître de stage');

-- --------------------------------------------------------

--
-- Structure de la table `rapport_etudiants`
--

DROP TABLE IF EXISTS `rapport_etudiants`;

CREATE TABLE IF NOT EXISTS `rapport_etudiants` (
    `id_rapport` int NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `date_redaction_rapport` datetime NOT NULL,
    `theme_rapport` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `chemin_fichier` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Chemin vers le fichier de contenu',
    `statut_rapport` enum(
        'en_cours',
        'valider',
        'rejeter',
        'en_attente'
    ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'en_cours',
    `date_modification` datetime DEFAULT NULL,
    `taille_fichier` int DEFAULT NULL COMMENT 'Taille du fichier en octets',
    `version` int NOT NULL DEFAULT '1' COMMENT 'Version du rapport',
    PRIMARY KEY (`id_rapport`),
    KEY `num_etu` (`num_etu`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `rattacher_legacy`
--

DROP TABLE IF EXISTS `rattacher_legacy`;

CREATE TABLE IF NOT EXISTS `rattacher_legacy` (
    `id_GU` int NOT NULL,
    `id_traitement` int NOT NULL,
    PRIMARY KEY (`id_GU`, `id_traitement`),
    KEY `Key_rattacher_GU` (`id_GU`),
    KEY `Key_rattacher_traitement` (`id_traitement`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `rattacher_legacy`
--

INSERT INTO
    `rattacher_legacy` (`id_GU`, `id_traitement`)
VALUES (5, 5),
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
    (5, 48);

-- --------------------------------------------------------

--
-- Structure de la table `reclamations`
--

DROP TABLE IF EXISTS `reclamations`;

CREATE TABLE IF NOT EXISTS `reclamations` (
    `id_reclamation` int NOT NULL AUTO_INCREMENT,
    `num_carte_etud` varchar(25) DEFAULT NULL,
    `objet_reclamation` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
    `id_enseignant` int NOT NULL,
    `date_env` datetime NOT NULL,
    KEY `Key_rendre_CR` (`id_CR`),
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
    `resume_json` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
    `http_method` enum('GET', 'POST', '*') NOT NULL DEFAULT '*',
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
    `id_salle` int NOT NULL AUTO_INCREMENT,
    `lib_salle` varchar(100) NOT NULL,
    PRIMARY KEY (`id_salle`)
) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8mb3;

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
    `lib_semestre` varchar(100) NOT NULL,
    `id_niv_etude` int NOT NULL,
    PRIMARY KEY (`id_semestre`),
    KEY `id_niv_etude` (`id_niv_etude`)
) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `semestre`
--

INSERT INTO
    `semestre` (
        `id_semestre`,
        `lib_semestre`,
        `id_niv_etude`
    )
VALUES (1, 'semestre 7', 1),
    (2, 'Semestre 8', 1),
    (3, 'Semestre 9', 2);

-- --------------------------------------------------------

--
-- Structure de la table `session`
--

DROP TABLE IF EXISTS `session`;

CREATE TABLE IF NOT EXISTS `session` (
    `id_session` int NOT NULL AUTO_INCREMENT,
    `lib_session` varchar(20) NOT NULL,
    PRIMARY KEY (`id_session`)
) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb3;

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
    `lib_specialite` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
    `lib_jury` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    PRIMARY KEY (`id_jury`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `statut_reclamation`
--

DROP TABLE IF EXISTS `statut_reclamation`;

CREATE TABLE IF NOT EXISTS `statut_reclamation` (
    `id_statut_reclamation` int NOT NULL AUTO_INCREMENT,
    `libelle_statut_reclamation` varchar(15) NOT NULL,
    PRIMARY KEY (`id_statut_reclamation`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `traitement_legacy`
--

DROP TABLE IF EXISTS `traitement_legacy`;

CREATE TABLE IF NOT EXISTS `traitement_legacy` (
    `id_traitement` int NOT NULL AUTO_INCREMENT,
    `lib_traitement` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `label_traitement` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `icone_traitement` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `ordre_traitement` int NOT NULL,
    PRIMARY KEY (`id_traitement`)
) ENGINE = InnoDB AUTO_INCREMENT = 51 DEFAULT CHARSET = utf8mb3;

--
-- Déchargement des données de la table `traitement_legacy`
--

INSERT INTO
    `traitement_legacy` (
        `id_traitement`,
        `lib_traitement`,
        `label_traitement`,
        `icone_traitement`,
        `ordre_traitement`
    )
VALUES (
        5,
        'dashboard',
        'Tableau de bord',
        'fa-home',
        1
    ),
    (
        6,
        'gestion_etudiants',
        'Gestion des étudiants',
        'fa-book',
        2
    ),
    (
        7,
        'gestion_utilisateurs',
        'Gestion des utilisateurs',
        'fa-user',
        3
    ),
    (
        8,
        'gestion_rh',
        'Gestion des ressources humaines',
        'fa-users',
        2
    ),
    (
        9,
        'piste_audit',
        'Gestion de la piste',
        'fa-history',
        4
    ),
    (
        10,
        'sauvegarde_restauration',
        'Sauvegarde et restauration des données',
        'fa-save',
        5
    ),
    (
        11,
        'parametres_generaux',
        'Paramètres généraux',
        'fa-gears',
        6
    ),
    (
        12,
        'candidature_soutenance',
        'Candidater à la soutenance',
        'fa-graduation-cap',
        1
    ),
    (
        13,
        'gestion_rapports',
        'Gestion des rapports',
        'fa-file',
        2
    ),
    (
        15,
        'notes_resultats',
        'Notes & résultats',
        'fa-note-sticky',
        4
    ),
    (
        16,
        'messagerie',
        'Messagerie',
        'fa-envelope',
        5
    ),
    (
        17,
        'profil_etudiant',
        'Profil étudiant',
        'fa-user',
        6
    ),
    (
        19,
        'profil',
        'Profil',
        'fa-user',
        6
    ),
    (
        20,
        'gestion_reclamations',
        'Gestion des réclamations',
        'fa-exclamation',
        3
    ),
    (
        23,
        'dashboard_scolarite',
        'Tableau de bord scolarité',
        'fa-home',
        1
    ),
    (
        24,
        'gestion_scolarite',
        'Gestion de la scolarité',
        'fa-money-bill',
        3
    ),
    (
        25,
        'gestion_candidatures_soutenance',
        'Gestion des candidatures de soutenance',
        'fa-folder',
        4
    ),
    (
        26,
        'gestion_notes_evaluations',
        'Gestions des notes et évaluations',
        'fa-note-sticky',
        5
    ),
    (
        27,
        'dashboard_enseignant',
        'Tableau de bord enseignant',
        'fa-home',
        1
    ),
    (
        28,
        'liste_etudiants_ens_simple',
        'Liste des étudiants évalués',
        'fa-users',
        2
    ),
    (
        29,
        'liste_etudiants_resp_filiere',
        'Liste des étudiants MIAGE',
        'fa-users',
        2
    ),
    (
        30,
        'liste_etudiants_resp_niveau',
        'Liste des étudiants de mon niveau',
        'fa-users',
        2
    ),
    (
        31,
        'verification_candidatures_soutenance',
        'Vérification des candidatures de soutenance',
        'fa-certificate',
        1
    ),
    (
        32,
        'gestion_dossiers_candidatures',
        'Gestion des dossiers de candidature',
        'fa-folder',
        2
    ),
    (
        33,
        'dashboard_secretaire',
        'Tableau de bord secrétariat',
        'fa-home',
        1
    ),
    (
        34,
        'dossiers_academiques',
        'Dossiers académiques',
        'fa-folder-open',
        3
    ),
    (
        35,
        'dashboard_commission',
        'Tableau de bord de la commission',
        'fa-home',
        1
    ),
    (
        36,
        'evaluations_dossiers_soutenance',
        'Évaluation des dossiers de soutenance',
        'fa-file-contract',
        2
    ),
    (
        38,
        'processus_validation',
        'Processus de validation des dossiers',
        'fa-list-check',
        3
    ),
    (
        39,
        'archives_dossiers_soutenance',
        'Archives des rapports de soutenance',
        'fa-inbox',
        5
    ),
    (
        40,
        'planification_reunion',
        'Planification des réunions',
        'fa-calendar-days',
        6
    ),
    (
        41,
        'gestion_reclamations_scolarite',
        'Gestion des réclamations étudiantes ',
        'fa-file',
        4
    ),
    (
        42,
        'redaction_compte_rendu',
        'Rédaction du compte rendu',
        'fa-file',
        6
    ),
    (
        43,
        'archive_comptes_rendus',
        'Archive des comptes rendus',
        'fa-book',
        9
    ),
    (
        44,
        'programation_soutenance',
        'Programation de soutenance',
        'fa-calendar',
        13
    ),
    (
        45,
        'plannificaiton_soutenance',
        'plannification de soutenance',
        'fa-calendar',
        14
    ),
    (
        47,
        'evaluation_soutenance',
        'Evaluation soutenance',
        'fa-note',
        16
    ),
    (
        48,
        'admin_historique',
        'Historique et Archivage',
        'fa-archive',
        100
    );

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
    `lib_type_utilisateur` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    PRIMARY KEY (`id_type_utilisateur`)
) ENGINE = InnoDB AUTO_INCREMENT = 12 DEFAULT CHARSET = utf8mb3;

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
    `nom_utilisateur` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `id_type_utilisateur` int NOT NULL,
    `id_GU` int NOT NULL,
    `id_niv_acces_donnee` int NOT NULL,
    `statut_utilisateur` enum('Actif', 'Inactif') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `login_utilisateur` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `mdp_utilisateur` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    PRIMARY KEY (`id_utilisateur`),
    UNIQUE KEY `login_utilisateur` (`login_utilisateur`),
    KEY `id_groupe_utilisateur` (`id_GU`),
    KEY `id_niv_acces_donnee` (`id_niv_acces_donnee`),
    KEY `id_type_utilisateur` (`id_type_utilisateur`)
) ENGINE = InnoDB AUTO_INCREMENT = 111 DEFAULT CHARSET = utf8mb3;

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
        110,
        'Irie Adjo Anne Jemima',
        7,
        13,
        5,
        'Actif',
        'iadjoannejemima',
        '$2y$10$sMUplz7tHt5H9gdz92Qjluy3IKTGbnCheSpETELcxLD3PB2thRGxO'
    );

-- --------------------------------------------------------

--
-- Structure de la table `valider`
--

DROP TABLE IF EXISTS `valider`;

CREATE TABLE IF NOT EXISTS `valider` (
    `id_enseignant` int NOT NULL,
    `id_rapport` int NOT NULL,
    `date_validation` datetime NOT NULL,
    `commentaire_validation` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `decision_validation` enum('valider', 'rejeter') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'valider',
    PRIMARY KEY (`id_enseignant`, `id_rapport`),
    KEY `Key_valider_enseignant` (`id_enseignant`),
    KEY `Key_valider_rapport` (`id_rapport`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `versements`
--

DROP TABLE IF EXISTS `versements`;

CREATE TABLE IF NOT EXISTS `versements` (
    `id_versement` int NOT NULL AUTO_INCREMENT,
    `id_inscription` int DEFAULT NULL,
    `montant` decimal(10, 2) DEFAULT NULL,
    `date_versement` datetime DEFAULT CURRENT_TIMESTAMP,
    `type_versement` enum(
        'Premier versement',
        'Tranche'
    ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
    `methode_paiement` enum(
        'Espèce',
        'Carte bancaire',
        'Virement',
        'Chèque'
    ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    PRIMARY KEY (`id_versement`),
    KEY `id_inscription` (`id_inscription`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `affecter`
--
ALTER TABLE `affecter`
ADD CONSTRAINT `fk_affecter_jury` FOREIGN KEY (`id_jury`) REFERENCES `statut_jury` (`id_jury`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_affecter_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `avoir`
--
ALTER TABLE `avoir`
ADD CONSTRAINT `avoir_ibfk_1` FOREIGN KEY (`id_grade`) REFERENCES `grade` (`id_grade`) ON DELETE CASCADE ON UPDATE CASCADE;

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
ADD CONSTRAINT `compte_rendu_rapport_ibfk_1` FOREIGN KEY (`id_CR`) REFERENCES `compte_rendu` (`id_CR`) ON DELETE CASCADE,
ADD CONSTRAINT `compte_rendu_rapport_ibfk_2` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE;

--
-- Contraintes pour la table `deposer`
--
ALTER TABLE `deposer`
ADD CONSTRAINT `fk_deposer_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `echeances`
--
ALTER TABLE `echeances`
ADD CONSTRAINT `echeances_ibfk_1` FOREIGN KEY (`id_inscription`) REFERENCES `inscriptions` (`id_inscription`);

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
ADD CONSTRAINT `fk_composer_role` FOREIGN KEY (`id_qualite_jury`) REFERENCES `qualite_jury` (`id_role_jury`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `etudiants`
--
ALTER TABLE `etudiants`
ADD CONSTRAINT `etudiants_ibfk_1` FOREIGN KEY (`genre_etu`) REFERENCES `genre` (`id_genre`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_etudiant_annee_acad` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_etudiant_niveau` FOREIGN KEY (`id_niveau`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Contraintes pour la table `groupe_utilisateur`
--
ALTER TABLE `groupe_utilisateur`
ADD CONSTRAINT `fk_groupe_utilisateur_type` FOREIGN KEY (`id_type_utilisateur`) REFERENCES `type_utilisateur` (`id_type_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `informations_stage`
--
ALTER TABLE `informations_stage`
ADD CONSTRAINT `informations_stage_ibfk_2` FOREIGN KEY (`id_entreprise`) REFERENCES `entreprises` (`id_entreprise`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `informations_stage_ibfk_3` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
ADD CONSTRAINT `inscriptions_ibfk_3` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `inscriptions_ibfk_4` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
ADD CONSTRAINT `fk_notes_annee_acad` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE;

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
ADD CONSTRAINT `programmer_soutenance_ibfk_4` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id_salle`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rapport_etudiants`
--
ALTER TABLE `rapport_etudiants`
ADD CONSTRAINT `rapport_etudiants_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rattacher_legacy`
--
ALTER TABLE `rattacher_legacy`
ADD CONSTRAINT `fk_rattacher_gu` FOREIGN KEY (`id_GU`) REFERENCES `groupe_utilisateur` (`id_GU`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_rattacher_traitement` FOREIGN KEY (`id_traitement`) REFERENCES `traitement_legacy` (`id_traitement`) ON DELETE CASCADE ON UPDATE CASCADE;

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
ADD CONSTRAINT `fk_rendre_cr` FOREIGN KEY (`id_CR`) REFERENCES `compte_rendu` (`id_CR`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `resume_candidature`
--
ALTER TABLE `resume_candidature`
ADD CONSTRAINT `resume_ibfk_2` FOREIGN KEY (`id_candidature`) REFERENCES `candidature_soutenance` (`id_candidature`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `semestre`
--
ALTER TABLE `semestre`
ADD CONSTRAINT `fk_niveau_etude` FOREIGN KEY (`id_niv_etude`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE CASCADE ON UPDATE CASCADE;

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
ADD CONSTRAINT `fk_valider_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `versements`
--
ALTER TABLE `versements`
ADD CONSTRAINT `versements_ibfk_1` FOREIGN KEY (`id_inscription`) REFERENCES `inscriptions` (`id_inscription`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;