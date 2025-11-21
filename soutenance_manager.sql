-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : db
-- Généré le : jeu. 16 oct. 2025 à 18:40
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb3 */
; -- Changement de utf8mb4 à utf8mb3

--
-- Base de données : `ufrmi1802974_2q2mpf_dev-checkmaster`
--

-- --------------------------------------------------------

--
-- Structure de la table `action`
--

CREATE TABLE `action` (
                          `id_action` int NOT NULL,
                          `lib_action` varchar(120) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `action`
--

INSERT INTO
    `action` (`id_action`, `lib_action`)
VALUES (3, 'Modifier'),
       (6, 'Supprimer'),
       (7, 'Consulter');

-- --------------------------------------------------------

--
-- Structure de la table `affecter`
--

CREATE TABLE `affecter` (
                            `id_enseignant` int NOT NULL,
                            `role` enum('encadrant', 'directeur') COLLATE utf8mb3_general_ci NOT NULL,
                            `id_rapport` int NOT NULL,
                            `id_jury` int DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `affecter`
--

INSERT INTO
    `affecter` (
    `id_enseignant`,
    `role`,
    `id_rapport`,
    `id_jury`
)
VALUES (21, 'encadrant', 16, NULL),
       (22, 'directeur', 16, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `annee_academique`
--

CREATE TABLE `annee_academique` (
                                    `id_annee_acad` int NOT NULL,
                                    `date_deb` date NOT NULL,
                                    `date_fin` date NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

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
           21413,
           '2013-09-08',
           '2014-06-25'
       ),
       (
           21514,
           '2014-09-02',
           '2015-06-21'
       ),
       (
           21615,
           '2015-09-03',
           '2016-06-24'
       ),
       (
           21716,
           '2016-09-05',
           '2017-06-20'
       ),
       (
           21817,
           '2017-09-01',
           '2018-06-25'
       ),
       (
           21918,
           '2018-09-04',
           '2019-06-23'
       ),
       (
           22019,
           '2019-09-08',
           '2020-06-24'
       ),
       (
           22120,
           '2020-09-01',
           '2021-06-27'
       ),
       (
           22221,
           '2021-09-08',
           '2022-07-20'
       ),
       (
           22322,
           '2022-09-10',
           '2023-07-31'
       ),
       (
           22423,
           '2023-09-11',
           '2024-07-17'
       ),
       (
           22524,
           '2024-09-10',
           '2025-07-30'
       ),
       (
           22625,
           '2025-09-15',
           '2026-07-31'
       );

-- --------------------------------------------------------

--
-- Structure de la table `approuver`
--

CREATE TABLE `approuver` (
                             `id_pers_admin` int NOT NULL,
                             `id_rapport` int NOT NULL,
                             `decision` enum('approuve', 'desapprouve') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                             `date_approv` datetime NOT NULL,
                             `commentaire_approv` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                             `id_approb` int NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `approuver`
--

INSERT INTO
    `approuver` (
    `id_pers_admin`,
    `id_rapport`,
    `decision`,
    `date_approv`,
    `commentaire_approv`,
    `id_approb`
)
VALUES (
           10,
           16,
           'approuve',
           '2025-09-29 22:34:21',
           'Tout es bon ',
           4
       );

-- --------------------------------------------------------

--
-- Structure de la table `avoir`
--

CREATE TABLE `avoir` (
                         `id_grade` int NOT NULL,
                         `id_enseignant` int NOT NULL,
                         `date_grade` date NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `avoir`
--

INSERT INTO
    `avoir` (
    `id_grade`,
    `id_enseignant`,
    `date_grade`
)
VALUES (7, 18, '1995-11-27'),
       (7, 22, '1995-09-01'),
       (12, 7, '1993-09-15'),
       (12, 19, '2006-07-29'),
       (12, 23, '2000-09-19'),
       (15, 21, '2000-10-10');

-- --------------------------------------------------------

--
-- Structure de la table `candidature_soutenance`
--

CREATE TABLE `candidature_soutenance` (
                                          `id_candidature` int NOT NULL,
                                          `num_etu` int NOT NULL,
                                          `date_candidature` datetime NOT NULL,
                                          `statut_candidature` enum(
        'En attente',
        'Validée',
        'Rejetée'
    ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'En attente', -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci
                                          `date_traitement` datetime DEFAULT NULL,
                                          `id_pers_admin` int DEFAULT NULL,
                                          `commentaire_admin` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `candidature_soutenance`
--

INSERT INTO
    `candidature_soutenance` (
    `id_candidature`,
    `num_etu`,
    `date_candidature`,
    `statut_candidature`,
    `date_traitement`,
    `id_pers_admin`,
    `commentaire_admin`
)
VALUES (
           14,
           20220001,
           '2025-09-29 22:21:31',
           'Validée',
           '2025-09-29 22:22:15',
           9,
           'Évaluation complète terminée'
       );

-- --------------------------------------------------------

--
-- Structure de la table `composer_jury`
--

CREATE TABLE `composer_jury` (
                                 `num_jury` int NOT NULL,
                                 `id_enseignant` int NOT NULL,
                                 `id_qualite_jury` int NOT NULL,
                                 `date_composer_jury` int NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `composer_jury`
--

INSERT INTO
    `composer_jury` (
    `num_jury`,
    `id_enseignant`,
    `id_qualite_jury`,
    `date_composer_jury`
)
VALUES (1, 7, 3, 1760307399),
       (1, 18, 1, 1760307399),
       (1, 21, 2, 1760307399),
       (1, 22, 4, 1760307399);

-- --------------------------------------------------------

--
-- Structure de la table `compte_rendu`
--

CREATE TABLE `compte_rendu` (
                                `id_CR` int NOT NULL,
                                `num_etu` int NOT NULL,
                                `nom_CR` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                                `contenu_CR` longtext COLLATE utf8mb3_general_ci,
                                `chemin_fichier_pdf` varchar(255) COLLATE utf8mb3_general_ci DEFAULT NULL,
                                `date_CR` datetime NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `compte_rendu`
--

INSERT INTO
    `compte_rendu` (
    `id_CR`,
    `num_etu`,
    `nom_CR`,
    `contenu_CR`,
    `chemin_fichier_pdf`,
    `date_CR`
)
VALUES (
           30,
           20220001,
           'Compte rendu séance du 29/09/2025',
           '\r\n                <style>\r\n                .editor-content { font-family: \'Times New Roman\', Times, serif; }             \r\n                .header-logos .right { float: right; }\r\n                .header-logos .center { text-align: center; margin: 0 auto; }\r\n                .editor-content h1 { font-size: 2.2em; font-weight: bold; margin-bottom: 0.5em; text-align: center; }\r\n                .editor-content h2 { font-size: 1.5em; font-weight: bold; margin-bottom: 0.5em; text-align: center; }\r\n                .editor-content h3 { font-size: 1.2em; font-weight: bold; margin-bottom: 0.5em; }\r\n                .section-title { border-bottom: 2px solid #222; margin-bottom: 0.7em; margin-top: 1.5em; }\r\n                .editor-content p { margin-bottom: 0.7em; }\r\n                .editor-content ul { margin-left: 1.5em; margin-bottom: 0.7em; }\r\n                .encadre { background: #f6faff; border: 2px solid #b6d4fe; border-radius: 8px; padding: 1em; margin-bottom: 1em; }\r\n                .cas { background: #fff; border: 1px solid #b6d4fe; border-radius: 8px; padding: 1em; margin-bottom: 1em; }\r\n                .cas-titre { font-weight: bold; margin-bottom: 0.5em; }\r\n                .cas-footer { margin-top: 1em; font-size: 1em; }\r\n                .text-center { text-align: center; }\r\n                .italic { font-style: italic; }\r\n                </style>\r\n                <div class=\"header-logos\">\r\n                    <div class=\"center\">\r\n                        <div style=\"font-size:13px; font-weight:bold; letter-spacing:1px;\">REPUBLIQUE DE COTE D\'IVOIRE</div>\r\n                        <div style=\"font-size:12px;\">Ministère de l\'Enseignement Supérieur et de la Recherche Scientifique</div>\r\n                        </div>\r\n                        </div>\r\n                <h1>Procès-Verbal de séance de validation de thèmes</h1>\r\n                <h2>Thèmes de Soutenance - Filière MIAGE-GI</h2>\r\n                <div class=\"text-center\" style=\"margin-bottom:1em;\">\r\n                    Université Félix Houphouët-Boigny<br>\r\n                    UFR Mathématiques et Informatique\r\n                        </div>\r\n                <h3 class=\"section-title\">CONTEXTE DE LA SÉANCE</h3>\r\n                <p>Dans le bureau du Prof KOUA Brou à l\'UFR MI, le [DATE] s\'est tenue de 11 h 00 à 12 h 30 une séance de validation de thèmes de soutenance des étudiants en fin de cycle de la filière MIAGE-GI.</p>\r\n                <p>La réunion était animée par Prof KOUA Brou le responsable de ladite filière. Etaient présents Prof. KOUA Brou, Dr MAMADOU Diarra, M. WAH Médard et M. BROU Patrice. Les membres de la commission de validation ont examiné [N] dossiers.</p>\r\n                <div class=\"encadre\">\r\n                    <strong>Ordre du jour :</strong>\r\n                    <ul>\r\n                        <li>Informations</li>\r\n                        <li>Validation de thèmes</li>\r\n                        <li>Divers</li>\r\n                                </ul>\r\n                            </div>\r\n                <h3 class=\"section-title\">1. INFORMATIONS</h3>\r\n                <p class=\"italic\">[Le responsable de la filière a exposé sur l\'intérêt des séances de validation. Il a donné des informations sur le choix des thèmes niveau ingénieur et la tenue mensuelle des séances de validation.]</p>\r\n                <p class=\"italic\">[L\'organisation des séances de validation permet de faire le point des encadrements, le contenu potentiel de thèmes, et le suivi des mémoires par des encadreurs pédagogiques.]</p>\r\n                <h3 class=\"section-title\">2. VALIDATION DE THÈMES</h3>\r\n                <div id=\"casDynamique\">\r\n                <div class=\"cas\">\r\n                    <div class=\"cas-titre\">Cas 1</div>\r\n                    <strong>Étudiant :</strong> Adjo Jemima Irie<br>\r\n                    <strong>Thème :</strong> AUDIT ET CONTROLE<br>\r\n                    <strong>Recommandations de la commission :</strong>\r\n                    <ul>\r\n                        <li>thème valide</li>\r\n                        <li>bien décrire le processus de règlement de chèques</li>\r\n                        <li>décrire exactement le contexte</li>\r\n                    </ul>\r\n                    <div class=\"cas-footer\">\r\n                        <strong>Directeur de mémoire :</strong> Michael Foursov &nbsp;&nbsp;\r\n                        <strong>Encadrant pédagogique :</strong> Malan Nindjin\r\n                    </div>\r\n                </div>\r\n                </div>\r\n                <h3 class=\"section-title\">3. DIVERS</h3>\r\n                <p class=\"italic\">[La commission a recommandé au Directeur de la filière d\'améliorer le partenariat avec les entreprises car elles le souhaitent compte tenu du rendement des stagiaires déjà reçus.]</p>\r\n                <strong>Recommandations aux étudiants :</strong>\r\n                <ul>\r\n                    <li>Respecter toutes les rubriques du template de présentation de thème en possession de la chargée de communication</li>\r\n                    <li>Joindre un CV contenant une photo d\'identité</li>\r\n                    <li>Soutenir au plus tard à la session suivante pour ne pas tomber sous le coup d\'une pénalité</li>\r\n                                </ul>\r\n                <div class=\"text-center\" style=\"margin-top:2em;\">\r\n                    Les travaux de la commission ont pris fin à 12 h 30.<br>\r\n                    Fait à Abidjan, le [DATE]<br>\r\n                    <strong>La commission</strong>\r\n                        </div>\r\n                    ',
        'ressources/uploads/comptes_rendus/CR_20250929_232200.pdf',
           '2025-09-29 23:21:59'
       );

-- --------------------------------------------------------

--
-- Structure de la table `compte_rendu_rapport`
--

CREATE TABLE `compte_rendu_rapport` (
                                        `id_CR` int NOT NULL,
                                        `id_rapport` int NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `compte_rendu_rapport`
--

INSERT INTO
    `compte_rendu_rapport` (`id_CR`, `id_rapport`)
VALUES (30, 16);

-- --------------------------------------------------------

--
-- Structure de la table `correspondre`
--

CREATE TABLE `correspondre` (
                                `id_annee_acad` int NOT NULL,
                                `id_critere` int NOT NULL,
                                `bareme` int NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `correspondre`
--

INSERT INTO
    `correspondre` (
    `id_annee_acad`,
    `id_critere`,
    `bareme`
)
VALUES (22524, 8, 5),
       (22524, 9, 5),
       (22625, 3, 4),
       (22625, 4, 5),
       (22625, 5, 2),
       (22625, 6, 4),
       (22625, 7, 5);

-- --------------------------------------------------------

--
-- Structure de la table `critere_evaluation`
--

CREATE TABLE `critere_evaluation` (
                                      `id_critere` int NOT NULL,
                                      `lib_critere` varchar(100) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `critere_evaluation`
--

INSERT INTO
    `critere_evaluation` (`id_critere`, `lib_critere`)
VALUES (3, 'Exposé'),
       (
           4,
           'Réponses aux questions posées'
       ),
       (5, 'Présentation du mémoire'),
       (6, 'Contenu du mémoire'),
       (7, 'Résolution du problème'),
       (8, 'Résolution du problème'),
       (
           9,
           'Réponses aux questions posées'
       );

-- --------------------------------------------------------

--
-- Structure de la table `decisions_jury`
--

CREATE TABLE `decisions_jury` (
                                  `id_decision` int NOT NULL,
                                  `lib_decision` varchar(50) NOT NULL,
                                  `description` text,
                                  `actif` tinyint(1) DEFAULT '1'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `decisions_jury`
--

INSERT INTO
    `decisions_jury` (
    `id_decision`,
    `lib_decision`,
    `description`,
    `actif`
)
VALUES (
           1,
           'Admis',
           'Candidat admis définitivement',
           1
       ),
       (
           2,
           'Ajourné',
           'Candidat ajourné, peut repasser la soutenance',
           1
       ),
       (
           3,
           'Refusé',
           'Candidat refusé',
           1
       );

-- --------------------------------------------------------

--
-- Structure de la table `deposer`
--

CREATE TABLE `deposer` (
                           `num_etu` int NOT NULL,
                           `id_rapport` int NOT NULL,
                           `date_depot` datetime NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `deposer`
--

INSERT INTO
    `deposer` (
    `num_etu`,
    `id_rapport`,
    `date_depot`
)
VALUES (
           20220001,
           16,
           '2025-09-29 22:26:45'
       );

-- --------------------------------------------------------

--
-- Structure de la table `echeances`
--

CREATE TABLE `echeances` (
                             `id_echeance` int NOT NULL,
                             `id_inscription` int DEFAULT NULL,
                             `montant` decimal(10, 2) DEFAULT NULL,
                             `date_echeance` date DEFAULT NULL,
                             `statut_echeance` enum(
        'En attente',
        'Payée',
        'En retard'
    ) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

-- --------------------------------------------------------

--
-- Structure de la table `ecue`
--

CREATE TABLE `ecue` (
                        `id_ecue` int NOT NULL,
                        `id_ue` int NOT NULL,
                        `lib_ecue` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                        `credit` int NOT NULL,
                        `id_enseignant` int DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `enseignants`
--

CREATE TABLE `enseignants` (
                               `id_enseignant` int NOT NULL,
                               `nom_enseignant` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                               `prenom_enseignant` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                               `mail_enseignant` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                               `id_specialite` int NOT NULL,
                               `type_enseignant` enum('Simple', 'Administratif') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `enseignants`
--

INSERT INTO
    `enseignants` (
    `id_enseignant`,
    `nom_enseignant`,
    `prenom_enseignant`,
    `mail_enseignant`,
    `id_specialite`,
    `type_enseignant`
)
VALUES (
           7,
           'Koua',
           'Brou',
           'soroemeric@gmail.com',
           2,
           'Administratif'
       ),
       (
           18,
           'Wah',
           'Medar',
           'wahmedar@gmail.com',
           2,
           'Simple'
       ),
       (
           19,
           'Brou',
           'Patrice',
           'bpatrice@gmail.com',
           2,
           'Administratif'
       ),
       (
           21,
           'Nindjin',
           'Malan',
           'nindjinmalan.0@gmail.com',
           2,
           'Simple'
       ),
       (
           22,
           'Foursov',
           'Michael',
           'michaelfoufou@gmail.com',
           2,
           'Administratif'
       ),
       (
           23,
           'Diarra',
           'prenom',
           'Diarraprenom@gmail.com',
           2,
           'Administratif'
       );

-- --------------------------------------------------------

--
-- Structure de la table `entreprises`
--

CREATE TABLE `entreprises` (
                               `id_entreprise` int NOT NULL,
                               `lib_entreprise` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `entreprises`
--

INSERT INTO
    `entreprises` (
    `id_entreprise`,
    `lib_entreprise`
)
VALUES (11, 'Deloitte'),
       (3, 'Deloitte Côte d\'Ivoire'),
    (9, 'DIGICORP'),
    (5, 'Orange Côte d\'Ivoire'),
       (8, 'QuanTech Côte d\'Ivoire'),
    (10, 'SODECI'),
    (7, 'Tuzzo Côte d\'Ivoire');

-- --------------------------------------------------------

--
-- Structure de la table `etudiants`
--

CREATE TABLE `etudiants` (
                             `num_etu` int NOT NULL,
                             `nom_etu` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                             `prenom_etu` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                             `email_etu` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                             `date_naiss_etu` date NOT NULL,
                             `genre_etu` enum('Homme', 'Femme', 'Neutre') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                             `promotion_etu` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `etudiants`
--

INSERT INTO
    `etudiants` (
    `num_etu`,
    `nom_etu`,
    `prenom_etu`,
    `email_etu`,
    `date_naiss_etu`,
    `genre_etu`,
    `promotion_etu`
)
VALUES (
           20220001,
           'Irie',
           'Adjo Jemima',
           'iriejemima@gmail.com',
           '2001-01-01',
           'Femme',
           '2022-2023'
       ),
       (
           20220002,
           'Akandan Aho',
           'Paul',
           'ahopaul@gmail.com',
           '2004-03-30',
           'Homme',
           '2022-2023'
       );

-- --------------------------------------------------------

--
-- Structure de la table `evaluations_rapports`
--

CREATE TABLE `evaluations_rapports` (
                                        `id_evaluation` int NOT NULL,
                                        `id_rapport` int NOT NULL,
                                        `id_evaluateur` int NOT NULL,
                                        `decision_evaluation` enum('valider', 'rejeter') DEFAULT NULL,
                                        `commentaire` text,
                                        `date_evaluation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `evaluations_rapports`
--

INSERT INTO
    `evaluations_rapports` (
    `id_evaluation`,
    `id_rapport`,
    `id_evaluateur`,
    `decision_evaluation`,
    `commentaire`,
    `date_evaluation`,
    `date_modification`
)
VALUES (
           21,
           16,
           7,
           'valider',
           'il est bon ce rapport',
           '2025-09-29 23:10:04',
           NULL
       ),
       (
           22,
           16,
           18,
           'valider',
           'c\'est bien',
        '2025-09-29 23:11:48',
        NULL
    ),
    (
        23,
        16,
        19,
        'valider',
        'bon rapport\r\n',
        '2025-09-29 23:13:08',
        NULL
    ),
    (
        24,
        16,
        23,
        'valider',
        'je suis impatien de le voir a sa soutenance',
        '2025-09-29 23:19:58',
        NULL
    );

-- --------------------------------------------------------

--
-- Structure de la table `evaluer`
--

CREATE TABLE `evaluer` (
    `num_etudiant` int NOT NULL,
    `num_jury` int NOT NULL,
    `id_critere` int NOT NULL,
    `date_eval` date NOT NULL,
    `note` double NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `evaluer`
--

INSERT INTO
    `evaluer` (
        `num_etudiant`,
        `num_jury`,
        `id_critere`,
        `date_eval`,
        `note`
    )
VALUES (
        20220001,
        1,
        3,
        '2025-10-15',
        3
    ),
    (
        20220001,
        1,
        4,
        '2025-10-15',
        5
    ),
    (
        20220001,
        1,
        5,
        '2025-10-15',
        2
    ),
    (
        20220001,
        1,
        6,
        '2025-10-15',
        2
    ),
    (
        20220001,
        1,
        7,
        '2025-10-15',
        3.5
    );

-- --------------------------------------------------------

--
-- Structure de la table `filiere`
--

CREATE TABLE `filiere` (
    `id_filiere` int NOT NULL,
    `lib_filiere` varchar(100) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

-- --------------------------------------------------------

--
-- Structure de la table `fonction`
--

CREATE TABLE `fonction` (
    `id_fonction` int NOT NULL,
    `lib_fonction` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `fonction`
--

INSERT INTO
    `fonction` (`id_fonction`, `lib_fonction`)
VALUES (2, 'Doyen de faculté'),
    (3, 'Directeur de recherche'),
    (5, 'Directeur pédagogique'),
    (7, 'Professeur titulaire'),
    (8, 'Maître de conférences'),
    (9, 'Chargé de cours'),
    (
        10,
        'Assistant d\'enseignement'
       ),
       (11, 'Chef de département'),
       (
           12,
           'Responsable de programme'
       ),
       (
           13,
           'Coordonnateur pédagogique'
       ),
       (
           14,
           'Directeur de laboratoire'
       ),
       (15, 'Encadreur de mémoire'),
       (16, 'Enseignant vacataire'),
       (17, 'Expert externe'),
       (18, 'Secrétaire scientifique'),
       (19, 'Président de jury'),
       (20, 'Conseiller pédagogique');

-- --------------------------------------------------------

--
-- Structure de la table `grade`
--

CREATE TABLE `grade` (
                         `id_grade` int NOT NULL,
                         `lib_grade` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `grade`
--

INSERT INTO
    `grade` (`id_grade`, `lib_grade`)
VALUES (7, 'A1'),
       (6, 'A2'),
       (14, 'A3'),
       (10, 'B1'),
       (12, 'B2'),
       (15, 'D1'),
       (16, 'E2'),
       (13, 'F4');

-- --------------------------------------------------------

--
-- Structure de la table `groupe_utilisateur`
--

CREATE TABLE `groupe_utilisateur` (
                                      `id_GU` int NOT NULL,
                                      `lib_GU` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `groupe_utilisateur`
--

INSERT INTO
    `groupe_utilisateur` (`id_GU`, `lib_GU`)
VALUES (5, 'Administrateur'),
       (6, 'Secretaire'),
       (7, 'Chargée de communication'),
       (8, 'Responsable scolarité'),
       (9, 'Responsable Filière'),
       (10, 'Responsable niveau'),
       (
           11,
           'commission de validation'
       ),
       (
           12,
           'Enseignant sans responsabilité administrative'
       ),
       (13, 'Etudiant');

-- --------------------------------------------------------

--
-- Structure de la table `informations_stage`
--

CREATE TABLE `informations_stage` (
                                      `id_info_stage` int NOT NULL,
                                      `num_etu` int NOT NULL,
                                      `id_entreprise` int NOT NULL,
                                      `date_debut_stage` date NOT NULL,
                                      `date_fin_stage` date NOT NULL,
                                      `sujet_stage` text NOT NULL,
                                      `description_stage` text NOT NULL,
                                      `encadrant_entreprise` varchar(100) NOT NULL,
                                      `email_encadrant` varchar(100) NOT NULL,
                                      `telephone_encadrant` varchar(20) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `informations_stage`
--

INSERT INTO
    `informations_stage` (
    `id_info_stage`,
    `num_etu`,
    `id_entreprise`,
    `date_debut_stage`,
    `date_fin_stage`,
    `sujet_stage`,
    `description_stage`,
    `encadrant_entreprise`,
    `email_encadrant`,
    `telephone_encadrant`
)
VALUES (
           10,
           20220001,
           11,
           '2025-05-01',
           '2025-09-01',
           'Audit et contrôle de securité informatique',
           'J\'ai fais de l\'audit et contrôle au niveau de la cybersécurité des entreprise ayant solicité deloitte ',
           'Mme. Suzanne Didia',
           'sdidagoat0.0@gmail.com',
           '0303030303'
       );

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions`
--

CREATE TABLE `inscriptions` (
                                `id_inscription` int NOT NULL,
                                `id_etudiant` int DEFAULT NULL,
                                `id_niveau` int DEFAULT NULL,
                                `id_annee_acad` int NOT NULL,
                                `date_inscription` datetime DEFAULT NULL,
                                `statut_inscription` enum(
        'En cours',
        'Validée',
        'Annulée'
    ) DEFAULT NULL,
                                `nombre_tranche` int NOT NULL,
                                `reste_a_payer` decimal(10, 2) NOT NULL,
                                `montant_paye` decimal(10, 2) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `inscriptions`
--

INSERT INTO
    `inscriptions` (
    `id_inscription`,
    `id_etudiant`,
    `id_niveau`,
    `id_annee_acad`,
    `date_inscription`,
    `statut_inscription`,
    `nombre_tranche`,
    `reste_a_payer`,
    `montant_paye`
)
VALUES (
           34,
           20220001,
           10,
           22625,
           '2025-09-28 21:39:12',
           'En cours',
           1,
           0.00,
           980000.00
       ),
       (
           35,
           20220002,
           10,
           22625,
           '2025-09-29 22:06:37',
           'En cours',
           1,
           0.00,
           1025000.00
       );

-- --------------------------------------------------------

--
-- Structure de la table `mentions`
--

CREATE TABLE `mentions` (
                            `id_mention` int NOT NULL,
                            `lib_mention` varchar(50) NOT NULL,
                            `actif` tinyint(1) DEFAULT '1'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `mentions`
--

INSERT INTO
    `mentions` (
    `id_mention`,
    `lib_mention`,
    `actif`
)
VALUES (1, 'Passable', 1),
       (2, 'Assez bien', 1),
       (3, 'Bien', 1),
       (4, 'Très bien', 1),
       (5, 'Excellent', 1);

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
                            `id_message` int NOT NULL,
                            `contenu_message` text NOT NULL,
                            `lib_message` varchar(60) NOT NULL,
                            `type_message` varchar(60) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `messages`
--

INSERT INTO
    `messages` (
    `id_message`,
    `contenu_message`,
    `lib_message`,
    `type_message`
)
VALUES (
           3,
           'Bienvenue sur Soutenance Manager',
           'message_bienvenue',
           'info'
       ),
       (
           4,
           'Erreur lors du traitement du fichier',
           'messageErreur',
           'error'
       );

-- --------------------------------------------------------

--
-- Structure de la table `niveau_acces_donnees`
--

CREATE TABLE `niveau_acces_donnees` (
                                        `id_niveau_acces_donnees` int NOT NULL,
                                        `lib_niveau_acces_donnees` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

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

CREATE TABLE `niveau_approbation` (
                                      `id_approb` int NOT NULL,
                                      `lib_approb` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `niveau_approbation`
--

INSERT INTO
    `niveau_approbation` (`id_approb`, `lib_approb`)
VALUES (3, 'Niveau 1'),
       (4, 'Niveau 2'),
       (6, 'Niveau 3');

-- --------------------------------------------------------

--
-- Structure de la table `niveau_etude`
--

CREATE TABLE `niveau_etude` (
                                `id_niv_etude` int NOT NULL,
                                `lib_niv_etude` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                                `id_enseignant` int DEFAULT NULL,
                                `montant_scolarite` decimal(10, 2) DEFAULT NULL,
                                `montant_inscription` decimal(10, 2) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

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
           10,
           'Master 2',
           7,
           1025000.00,
           500000.00
       );

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

CREATE TABLE `notes` (
                         `id` int NOT NULL,
                         `num_etu` int NOT NULL,
                         `id_ue` int DEFAULT NULL,
                         `id_ecue` int DEFAULT NULL,
                         `moyenne` decimal(4, 2) NOT NULL,
                         `commentaire` text,
                         `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
                         `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `notes`
--

INSERT INTO
    `notes` (
    `id`,
    `num_etu`,
    `id_ue`,
    `id_ecue`,
    `moyenne`,
    `commentaire`,
    `date_creation`,
    `date_modification`
)
VALUES (
           71,
           20220002,
           95,
           NULL,
           10.00,
           '',
           '2025-10-15 19:31:13',
           '2025-10-15 19:31:13'
       ),
       (
           72,
           20220002,
           103,
           NULL,
           12.00,
           '',
           '2025-10-15 19:31:13',
           '2025-10-15 19:31:13'
       ),
       (
           73,
           20220002,
           98,
           NULL,
           12.00,
           '',
           '2025-10-15 19:31:13',
           '2025-10-15 19:31:13'
       ),
       (
           74,
           20220002,
           101,
           NULL,
           13.00,
           '',
           '2025-10-15 19:31:13',
           '2025-10-15 19:31:13'
       ),
       (
           75,
           20220002,
           100,
           NULL,
           15.00,
           '',
           '2025-10-15 19:31:13',
           '2025-10-15 19:31:13'
       ),
       (
           76,
           20220002,
           102,
           NULL,
           15.00,
           '',
           '2025-10-15 19:31:13',
           '2025-10-15 19:31:13'
       ),
       (
           77,
           20220002,
           97,
           NULL,
           12.00,
           '',
           '2025-10-15 19:31:13',
           '2025-10-15 19:31:13'
       ),
       (
           78,
           20220002,
           99,
           NULL,
           20.00,
           '',
           '2025-10-15 19:31:13',
           '2025-10-15 19:31:13'
       ),
       (
           79,
           20220002,
           96,
           NULL,
           12.00,
           '',
           '2025-10-15 19:31:13',
           '2025-10-15 19:31:13'
       ),
       (
           80,
           20220001,
           95,
           NULL,
           12.00,
           '',
           '2025-10-15 19:32:13',
           '2025-10-15 19:32:13'
       ),
       (
           81,
           20220001,
           103,
           NULL,
           12.00,
           '',
           '2025-10-15 19:32:13',
           '2025-10-15 19:32:13'
       ),
       (
           82,
           20220001,
           98,
           NULL,
           10.00,
           '',
           '2025-10-15 19:32:13',
           '2025-10-15 19:32:13'
       ),
       (
           83,
           20220001,
           101,
           NULL,
           15.00,
           '',
           '2025-10-15 19:32:13',
           '2025-10-15 19:32:13'
       ),
       (
           84,
           20220001,
           100,
           NULL,
           16.00,
           '',
           '2025-10-15 19:32:13',
           '2025-10-15 19:32:13'
       ),
       (
           85,
           20220001,
           102,
           NULL,
           12.00,
           '',
           '2025-10-15 19:32:13',
           '2025-10-15 19:32:13'
       ),
       (
           86,
           20220001,
           97,
           NULL,
           10.00,
           '',
           '2025-10-15 19:32:13',
           '2025-10-15 19:32:13'
       ),
       (
           87,
           20220001,
           99,
           NULL,
           13.00,
           '',
           '2025-10-15 19:32:13',
           '2025-10-15 19:32:13'
       ),
       (
           88,
           20220001,
           96,
           NULL,
           15.00,
           '',
           '2025-10-15 19:32:13',
           '2025-10-15 19:32:13'
       );

-- --------------------------------------------------------

--
-- Structure de la table `occuper`
--

CREATE TABLE `occuper` (
                           `id_fonction` int NOT NULL,
                           `id_enseignant` int NOT NULL,
                           `date_occupation` date NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `occuper`
--

INSERT INTO
    `occuper` (
    `id_fonction`,
    `id_enseignant`,
    `date_occupation`
)
VALUES (2, 7, '2015-09-09'),
       (7, 18, '2000-10-17'),
       (9, 19, '1990-09-01'),
       (9, 21, '1995-06-05'),
       (9, 22, '1989-09-05'),
       (9, 23, '1990-09-10');

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
                                   `id` int NOT NULL,
                                   `email` varchar(255) COLLATE utf8mb3_general_ci NOT NULL, -- Changement de utf8mb4_unicode_ci à utf8mb3_general_ci
                                   `token` varchar(255) COLLATE utf8mb3_general_ci NOT NULL, -- Changement de utf8mb4_unicode_ci à utf8mb3_general_ci
                                   `expires_at` datetime NOT NULL,
                                   `used` tinyint(1) NOT NULL DEFAULT '0',
                                   `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_unicode_ci à utf8mb3_general_ci

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
           3,
           'soroemeric@gmail.com',
           '553a20d449bd775c694fc29cfa4b4bc14d7eb4fd79e01b5cbbe86be46b430b15',
           '2025-09-27 14:51:50',
           1,
           '2025-09-27 13:51:50'
       );

-- --------------------------------------------------------

--
-- Structure de la table `personnel_admin`
--

CREATE TABLE `personnel_admin` (
                                   `id_pers_admin` int NOT NULL,
                                   `nom_pers_admin` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                                   `prenom_pers_admin` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                                   `email_pers_admin` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                                   `tel_pers_admin` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                                   `poste` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                                   `date_embauche` date NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `personnel_admin`
--

INSERT INTO
    `personnel_admin` (
    `id_pers_admin`,
    `nom_pers_admin`,
    `prenom_pers_admin`,
    `email_pers_admin`,
    `tel_pers_admin`,
    `poste`,
    `date_embauche`
)
VALUES (
           9,
           'KAMENAN',
           'DURAND',
           'kamenandurand@gmail.com',
           '0707070707',
           'Secretaire générale',
           '1992-09-10'
       ),
       (
           10,
           'Seri',
           'Christiane',
           'serichristiane@gmail.com',
           '0505050505',
           'Chargé de communication',
           '1999-09-01'
       );

-- --------------------------------------------------------

--
-- Structure de la table `pister`
--

CREATE TABLE `pister` (
                          `id_piste` int NOT NULL,
                          `id_utilisateur` int NOT NULL,
                          `action` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Type d''action (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc.)', -- Changement de utf8mb4_unicode_ci à utf8mb3_general_ci
                          `statut_action` enum('Erreur', 'Succès') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL, -- Changement de utf8mb4_unicode_ci à utf8mb3_general_ci
                          `nom_table` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Nom de la table concernée', -- Changement de utf8mb4_unicode_ci à utf8mb3_general_ci
                          `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_unicode_ci à utf8mb3_general_ci

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
           24,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-07-03 01:15:18'
       ),
       (
           25,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-07-03 08:21:21'
       ),
       (
           26,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-07-03 08:27:38'
       ),
       (
           27,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-07-03 08:41:20'
       ),
       (
           68,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-07-03 11:00:06'
       ),
       (
           90,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-07-03 11:55:29'
       ),
       (
           103,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-07-03 12:08:44'
       ),
       (
           106,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-07-03 12:12:42'
       ),
       (
           120,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-07-15 22:52:59'
       ),
       (
           121,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-07-16 17:48:49'
       ),
       (
           124,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-07-16 18:07:19'
       ),
       (
           125,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-07-16 18:07:42'
       ),
       (
           126,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-07-16 18:08:01'
       ),
       (
           129,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-07-16 18:10:56'
       ),
       (
           146,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-27 00:04:39'
       ),
       (
           150,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-27 12:42:55'
       ),
       (
           151,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-09-27 12:43:11'
       ),
       (
           152,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-09-27 12:43:20'
       ),
       (
           153,
           5,
           'Modification',
           'Succès',
           'utilisateur',
           '2025-09-27 12:47:11'
       ),
       (
           154,
           5,
           'Modification',
           'Succès',
           'utilisateur',
           '2025-09-27 12:48:50'
       ),
       (
           155,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-09-27 12:49:22'
       ),
       (
           158,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-27 13:53:05'
       ),
       (
           159,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-09-27 15:45:47'
       ),
       (
           160,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-27 15:46:05'
       ),
       (
           161,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-09-27 15:46:05'
       ),
       (
           162,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-09-27 15:46:14'
       ),
       (
           163,
           5,
           'Modification',
           'Succès',
           'enseignant',
           '2025-09-27 16:37:17'
       ),
       (
           164,
           5,
           'Création',
           'Succès',
           'enseignant',
           '2025-09-27 16:46:23'
       ),
       (
           165,
           5,
           'Modification',
           'Succès',
           'utilisateur',
           '2025-09-27 16:48:33'
       ),
       (
           166,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-27 23:16:00'
       ),
       (
           167,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-09-27 23:16:00'
       ),
       (
           168,
           5,
           'Modification',
           'Succès',
           'utilisateur',
           '2025-09-27 23:16:14'
       ),
       (
           169,
           5,
           'Modification',
           'Succès',
           'utilisateur',
           '2025-09-27 23:16:27'
       ),
       (
           170,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-27 23:33:27'
       ),
       (
           171,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-09-27 23:57:56'
       ),
       (
           172,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-28 00:04:13'
       ),
       (
           173,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-09-28 00:04:14'
       ),
       (
           174,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-28 00:05:05'
       ),
       (
           175,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-09-28 00:14:17'
       ),
       (
           176,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-28 20:58:17'
       ),
       (
           177,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-09-28 20:58:18'
       ),
       (
           178,
           5,
           'Création',
           'Succès',
           'pers_admin',
           '2025-09-28 21:01:33'
       ),
       (
           179,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-28 21:02:09'
       ),
       (
           180,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-09-28 21:36:03'
       ),
       (
           181,
           99,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-28 21:36:18'
       ),
       (
           182,
           99,
           'Création',
           'Succès',
           'etudiants',
           '2025-09-28 21:37:39'
       ),
       (
           183,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-28 21:38:15'
       ),
       (
           184,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-09-28 21:38:15'
       ),
       (
           185,
           5,
           'Création',
           'Succès',
           'annee_academique',
           '2025-09-28 21:38:47'
       ),
       (
           186,
           99,
           'Création',
           'Succès',
           'inscriptions',
           '2025-09-28 21:39:12'
       ),
       (
           187,
           99,
           'Impression',
           'Succès',
           'inscriptions',
           '2025-09-28 21:39:20'
       ),
       (
           188,
           99,
           'Impression',
           'Succès',
           'inscriptions',
           '2025-09-28 22:19:33'
       ),
       (
           189,
           99,
           'Création',
           'Succès',
           'versements',
           '2025-09-28 22:59:56'
       ),
       (
           190,
           99,
           'Impression',
           'Succès',
           'inscriptions',
           '2025-09-28 23:39:18'
       ),
       (
           191,
           99,
           'Impression',
           'Succès',
           'inscriptions',
           '2025-09-28 23:42:25'
       ),
       (
           192,
           99,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-29 13:45:09'
       ),
       (
           193,
           99,
           'Création',
           'Succès',
           'versements',
           '2025-09-29 14:12:15'
       ),
       (
           194,
           99,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-29 15:03:40'
       ),
       (
           195,
           99,
           'Création',
           'Succès',
           'versements',
           '2025-09-29 19:04:01'
       ),
       (
           196,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-29 19:05:34'
       ),
       (
           197,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-09-29 19:05:34'
       ),
       (
           198,
           99,
           'Création',
           'Succès',
           'notes',
           '2025-09-29 19:14:16'
       ),
       (
           199,
           99,
           'Impression',
           'Succès',
           'inscriptions',
           '2025-09-29 20:43:10'
       ),
       (
           200,
           99,
           'Impression',
           'Succès',
           'inscriptions',
           '2025-09-29 20:45:35'
       ),
       (
           201,
           99,
           'Création',
           'Succès',
           'etudiants',
           '2025-09-29 22:06:12'
       ),
       (
           202,
           99,
           'Création',
           'Succès',
           'inscriptions',
           '2025-09-29 22:06:37'
       ),
       (
           203,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-29 22:14:17'
       ),
       (
           204,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-29 22:14:34'
       ),
       (
           205,
           5,
           'Création',
           'Succès',
           'pers_admin',
           '2025-09-29 22:16:04'
       ),
       (
           206,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-29 22:16:29'
       ),
       (
           207,
           102,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-29 22:17:55'
       ),
       (
           208,
           101,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-29 22:18:54'
       ),
       (
           209,
           101,
           'Création',
           'Succès',
           'candidature_soutenance',
           '2025-09-29 22:21:28'
       ),
       (
           210,
           101,
           'Création',
           'Succès',
           'candidature_soutenance',
           '2025-09-29 22:21:31'
       ),
       (
           211,
           99,
           'Validation',
           'Succès',
           'candidature_soutenance',
           '2025-09-29 22:21:54'
       ),
       (
           212,
           99,
           'Validation',
           'Succès',
           'candidature_soutenance',
           '2025-09-29 22:22:00'
       ),
       (
           213,
           99,
           'Validation',
           'Succès',
           'candidature_soutenance',
           '2025-09-29 22:22:08'
       ),
       (
           214,
           99,
           'Envoi résultats',
           'Succès',
           'candidature_soutenance',
           '2025-09-29 22:22:18'
       ),
       (
           215,
           101,
           'Création',
           'Succès',
           'rapport_etudiants',
           '2025-09-29 22:26:28'
       ),
       (
           216,
           101,
           'Dépôt',
           'Succès',
           'rapport',
           '2025-09-29 22:26:45'
       ),
       (
           217,
           5,
           'Création',
           'Succès',
           'enseignant',
           '2025-09-29 22:53:14'
       ),
       (
           218,
           5,
           'Création',
           'Succès',
           'enseignant',
           '2025-09-29 22:54:15'
       ),
       (
           219,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-29 22:55:00'
       ),
       (
           220,
           5,
           'Création',
           'Succès',
           'enseignant',
           '2025-09-29 23:06:36'
       ),
       (
           221,
           5,
           'Création',
           'Succès',
           'enseignant',
           '2025-09-29 23:08:28'
       ),
       (
           222,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-29 23:09:04'
       ),
       (
           223,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-29 23:09:08'
       ),
       (
           224,
           98,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-29 23:11:29'
       ),
       (
           225,
           103,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-29 23:12:54'
       ),
       (
           226,
           5,
           'Création',
           'Succès',
           'enseignant',
           '2025-09-29 23:17:29'
       ),
       (
           227,
           5,
           'Création',
           'Succès',
           'utilisateur',
           '2025-09-29 23:17:47'
       ),
       (
           228,
           108,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-29 23:19:21'
       ),
       (
           229,
           101,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-09-29 23:20:13'
       ),
       (
           230,
           5,
           'Création',
           'Succès',
           'traitement',
           '2025-09-29 23:32:49'
       ),
       (
           231,
           5,
           'Modification',
           'Succès',
           'attribution',
           '2025-09-29 23:33:37'
       ),
       (
           232,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-01 19:46:15'
       ),
       (
           233,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-01 19:46:16'
       ),
       (
           234,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-01 23:05:46'
       ),
       (
           235,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-01 23:05:47'
       ),
       (
           236,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-10-02 12:29:36'
       ),
       (
           237,
           99,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-02 12:29:40'
       ),
       (
           238,
           99,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-10-02 13:23:22'
       ),
       (
           239,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-02 13:23:39'
       ),
       (
           240,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-02 13:23:39'
       ),
       (
           241,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-06 22:54:25'
       ),
       (
           242,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-06 22:54:25'
       ),
       (
           243,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-06 22:55:12'
       ),
       (
           244,
           5,
           'Modification',
           'Succès',
           'traitement',
           '2025-10-06 22:56:09'
       ),
       (
           245,
           5,
           'Création',
           'Succès',
           'traitement',
           '2025-10-06 23:45:39'
       ),
       (
           246,
           5,
           'Modification',
           'Succès',
           'attribution',
           '2025-10-06 23:46:03'
       ),
       (
           247,
           5,
           'Modification',
           'Succès',
           'traitement',
           '2025-10-06 23:51:37'
       ),
       (
           248,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-07 10:38:19'
       ),
       (
           249,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-07 10:38:19'
       ),
       (
           250,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-10-07 10:41:25'
       ),
       (
           251,
           99,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-07 10:41:32'
       ),
       (
           252,
           99,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-10-07 10:44:09'
       ),
       (
           253,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-07 10:44:23'
       ),
       (
           254,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-07 10:44:23'
       ),
       (
           255,
           5,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-10-07 12:58:08'
       ),
       (
           256,
           99,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-07 12:58:10'
       ),
       (
           257,
           99,
           'Déconnexion',
           'Succès',
           'utilisateur',
           '2025-10-07 13:08:18'
       ),
       (
           258,
           99,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-07 13:08:19'
       ),
       (
           259,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-07 13:08:35'
       ),
       (
           260,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-07 13:08:35'
       ),
       (
           261,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-11 18:40:28'
       ),
       (
           262,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-11 18:40:28'
       ),
       (
           263,
           5,
           'Création',
           'Succès',
           'traitement',
           '2025-10-11 19:23:26'
       ),
       (
           264,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-11 19:23:32'
       ),
       (
           265,
           5,
           'Modification',
           'Succès',
           'attribution',
           '2025-10-11 19:24:02'
       ),
       (
           266,
           5,
           'Modification',
           'Succès',
           'enseignant',
           '2025-10-11 23:43:56'
       ),
       (
           267,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-12 00:43:15'
       ),
       (
           269,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-12 12:43:58'
       ),
       (
           270,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-12 12:43:58'
       ),
       (
           271,
           99,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-15 19:01:38'
       ),
       (
           272,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-15 19:03:39'
       ),
       (
           273,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-15 19:03:39'
       ),
       (
           274,
           5,
           'Suppression',
           'Succès',
           'ue',
           '2025-10-15 19:04:06'
       ),
       (
           275,
           5,
           'Suppression',
           'Succès',
           'ue',
           '2025-10-15 19:04:14'
       ),
       (
           276,
           5,
           'Création',
           'Succès',
           'niveau_etude',
           '2025-10-15 19:09:45'
       ),
       (
           277,
           5,
           'Création',
           'Succès',
           'semestre',
           '2025-10-15 19:11:05'
       ),
       (
           278,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:11:37'
       ),
       (
           279,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:12:58'
       ),
       (
           280,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:13:29'
       ),
       (
           281,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:13:58'
       ),
       (
           282,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:14:18'
       ),
       (
           283,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:14:53'
       ),
       (
           284,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:15:20'
       ),
       (
           285,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:15:44'
       ),
       (
           286,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:16:10'
       ),
       (
           287,
           99,
           'Création',
           'Succès',
           'versements',
           '2025-10-15 19:18:43'
       ),
       (
           288,
           5,
           'Suppression',
           'Succès',
           'niveau_etude',
           '2025-10-15 19:19:58'
       ),
       (
           289,
           5,
           'Modification',
           'Succès',
           'niveau_etude',
           '2025-10-15 19:20:40'
       ),
       (
           290,
           5,
           'Suppression',
           'Succès',
           'semestre',
           '2025-10-15 19:22:32'
       ),
       (
           291,
           5,
           'Modification',
           'Succès',
           'semestre',
           '2025-10-15 19:22:41'
       ),
       (
           292,
           99,
           'Création',
           'Succès',
           'versements',
           '2025-10-15 19:24:34'
       ),
       (
           293,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:25:54'
       ),
       (
           294,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:26:12'
       ),
       (
           295,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:26:27'
       ),
       (
           296,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:26:42'
       ),
       (
           297,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:26:59'
       ),
       (
           298,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:29:27'
       ),
       (
           299,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:29:44'
       ),
       (
           300,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:30:05'
       ),
       (
           301,
           5,
           'Création',
           'Succès',
           'ue',
           '2025-10-15 19:30:21'
       ),
       (
           302,
           99,
           'Création',
           'Succès',
           'notes',
           '2025-10-15 19:31:13'
       ),
       (
           303,
           99,
           'Création',
           'Succès',
           'notes',
           '2025-10-15 19:32:13'
       ),
       (
           304,
           5,
           'Création',
           'Succès',
           'traitement',
           '2025-10-15 19:34:13'
       ),
       (
           305,
           5,
           'Modification',
           'Succès',
           'attribution',
           '2025-10-15 19:34:22'
       ),
       (
           306,
           5,
           'Connexion',
           'Succès',
           'utilisateur',
           '2025-10-16 18:33:55'
       ),
       (
           307,
           5,
           'Accès',
           'Succès',
           'tableau_de_bord',
           '2025-10-16 18:33:55'
       );

-- --------------------------------------------------------

--
-- Structure de la table `programmer`
--

CREATE TABLE `programmer` (
                              `id_programmation` int NOT NULL,
                              `num_etud` int NOT NULL,
                              `num_jury` int NOT NULL,
                              `id_salle` int DEFAULT NULL,
                              `date_soutenance` date DEFAULT NULL,
                              `heure_soutenance` time DEFAULT NULL,
                              `theme_soutenance` varchar(200) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `programmer`
--

INSERT INTO
    `programmer` (
    `id_programmation`,
    `num_etud`,
    `num_jury`,
    `id_salle`,
    `date_soutenance`,
    `heure_soutenance`,
    `theme_soutenance`
)
VALUES (
           2,
           20220001,
           1,
           2,
           '2025-10-15',
           '15:00:00',
           'Informatisation des techniques d\'audit grâce à l\'IA : Cas Deloitte'
       );

-- --------------------------------------------------------

--
-- Structure de la table `rapport_etudiants`
--

CREATE TABLE `rapport_etudiants` (
                                     `id_rapport` int NOT NULL,
                                     `num_etu` int NOT NULL,
                                     `nom_rapport` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                                     `date_rapport` datetime NOT NULL,
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
                                     `etape_validation` enum(
        'en_cours',
        'en_attente_communication',
        'desapprouve_communication',
        'approuve_communication',
        'en_attente_commission',
        'desapprouve_commission',
        'approuve_commission',
        'valide',
        'rejete'
    ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT 'en_cours'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `rapport_etudiants`
--

INSERT INTO
    `rapport_etudiants` (
    `id_rapport`,
    `num_etu`,
    `nom_rapport`,
    `date_rapport`,
    `theme_rapport`,
    `chemin_fichier`,
    `statut_rapport`,
    `date_modification`,
    `taille_fichier`,
    `version`,
    `etape_validation`
)
VALUES (
           16,
           20220001,
           'L\'AUDIT AU CENTRE DE TOUTES LES ETAPES DE CONCEPTION',
        '2025-09-29 22:26:28',
        'AUDIT ET CONTROLE',
        'rapport_16.html',
        'valider',
        '2025-09-29 22:26:45',
        11561,
        1,
        'valide'
    );

-- --------------------------------------------------------

--
-- Structure de la table `rattacher`
--

CREATE TABLE `rattacher` (
    `id_GU` int NOT NULL,
    `id_traitement` int NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `rattacher`
--

INSERT INTO
    `rattacher` (`id_GU`, `id_traitement`)
VALUES (5, 5),
    (5, 7),
    (5, 8),
    (5, 9),
    (5, 10),
    (5, 11),
    (5, 19),
    (5, 35),
    (5, 36),
    (5, 38),
    (5, 39),
    (5, 42),
    (5, 43),
    (5, 44),
    (5, 45),
    (5, 47),
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
-- Structure de la table `reclamations`
--

CREATE TABLE `reclamations` (
    `id_reclamation` int NOT NULL,
    `num_etu` int DEFAULT NULL,
    `titre_reclamation` varchar(255) NOT NULL,
    `description_reclamation` text NOT NULL,
    `type_reclamation` enum(
        'Académique',
        'Administrative',
        'Technique',
        'Financière',
        'Autre'
    ) NOT NULL,
    `priorite_reclamation` enum(
        'Faible',
        'Moyenne',
        'Élevée',
        'Urgente'
    ) NOT NULL DEFAULT 'Moyenne',
    `statut_reclamation` enum(
        'En attente',
        'Résolue',
        'Rejetée',
        'En cours'
    ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'En attente', -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci
    `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_mise_a_jour` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `id_pers_admin` int DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

-- --------------------------------------------------------

--
-- Structure de la table `rendre`
--

CREATE TABLE `rendre` (
    `id_CR` int NOT NULL,
    `id_enseignant` int NOT NULL,
    `date_env` datetime NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `resume_candidature`
--

CREATE TABLE `resume_candidature` (
    `id` int NOT NULL,
    `num_etu` int NOT NULL,
    `id_candidature` int NOT NULL,
    `resume_json` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL, -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci
    `decision` varchar(20) NOT NULL,
    `date_enregistrement` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `resume_candidature`
--

INSERT INTO
    `resume_candidature` (
        `id`,
        `num_etu`,
        `id_candidature`,
        `resume_json`,
        `decision`,
        `date_enregistrement`
    )
VALUES (
        10,
        20220001,
        14,
        '{\"scolarite\":{\"statut\":\"\\u00c0 jour\",\"montant_total\":\"980 000 FCFA\",\"montant_paye\":\"980 000 FCFA\",\"dernier_paiement\":\"29\\/09\\/2025\",\"validation\":\"valid\\u00e9\"},\"stage\":{\"entreprise\":\"Deloitte\",\"sujet\":\"Audit et contr\\u00f4le de securit\\u00e9 informatique\",\"periode\":\"01\\/05\\/2025 - 01\\/09\\/2025\",\"encadrant\":\"Mme. Suzanne Didia\",\"validation\":\"valid\\u00e9\"},\"semestre\":{\"semestre\":\"Semestre 7, Semestre 8\",\"moyenne\":\"12.63\\/20\",\"unites\":\"60\\/60 cr\\u00e9dits valid\\u00e9s\",\"validation\":\"valid\\u00e9\"}}',
        'Validée',
           '2025-09-29 22:22:15'
       );

-- --------------------------------------------------------

--
-- Structure de la table `roles_jury`
--

CREATE TABLE `roles_jury` (
                              `id_role_jury` int NOT NULL,
                              `lib_role` varchar(50) NOT NULL,
                              `description` text,
                              `actif` tinyint(1) DEFAULT '1'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `roles_jury`
--

INSERT INTO
    `roles_jury` (
    `id_role_jury`,
    `lib_role`,
    `description`,
    `actif`
)
VALUES (
           1,
           'Président du jury',
           'Préside la soutenance et coordonne le jury',
           1
       ),
       (
           2,
           'Encadrant',
           'Encadre l\'etudiant dans le cadre de la redaction de son memoire',
        1
    ),
    (
        3,
        'Examinateur',
        'Évalue la qualité scientifique du mémoire et la prestation de l’étudiant lors de la soutenance',
        1
    ),
    (
        4,
        'Directeur de mémoire',
        'Directeur scientifique du mémoire',
        1
    ),
    (
        5,
        'Maitre de stage',
        'Supervise, guide et évalue le stagiaire, et assure la liaison avec l’établissement.',
        1
    );

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

CREATE TABLE `salles` (
    `id_salle` int NOT NULL,
    `lib_salle` varchar(100) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `salles`
--

INSERT INTO
    `salles` (`id_salle`, `lib_salle`)
VALUES (1, 'Amphi A'),
    (2, 'Amphi Irma');

-- --------------------------------------------------------

--
-- Structure de la table `semestre`
--

CREATE TABLE `semestre` (
    `id_semestre` int NOT NULL,
    `lib_semestre` varchar(100) NOT NULL,
    `id_niv_etude` int NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `semestre`
--

INSERT INTO
    `semestre` (
        `id_semestre`,
        `lib_semestre`,
        `id_niv_etude`
    )
VALUES (20, 'Semestre 9', 10);

-- --------------------------------------------------------

--
-- Structure de la table `specialite`
--

CREATE TABLE `specialite` (
    `id_specialite` int NOT NULL,
    `lib_specialite` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

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

CREATE TABLE `statut_jury` (
    `id_jury` int NOT NULL,
    `lib_jury` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `statut_jury`
--

INSERT INTO
    `statut_jury` (`id_jury`, `lib_jury`)
VALUES (6, 'accepter'),
    (7, 'refuser');

-- --------------------------------------------------------

--
-- Structure de la table `traitement`
--

CREATE TABLE `traitement` (
    `id_traitement` int NOT NULL,
    `lib_traitement` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `label_traitement` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `icone_traitement` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `ordre_traitement` int NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `traitement`
--

INSERT INTO
    `traitement` (
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
    );

-- --------------------------------------------------------

--
-- Structure de la table `type_utilisateur`
--

CREATE TABLE `type_utilisateur` (
    `id_type_utilisateur` int NOT NULL,
    `lib_type_utilisateur` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

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
-- Structure de la table `ue`
--

CREATE TABLE `ue` (
    `id_ue` int NOT NULL,
    `lib_ue` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
    `id_niveau_etude` int NOT NULL,
    `id_semestre` int NOT NULL,
    `id_annee_academique` int NOT NULL,
    `credit` int NOT NULL,
    `id_enseignant` int DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `ue`
--

INSERT INTO
    `ue` (
        `id_ue`,
        `lib_ue`,
        `id_niveau_etude`,
        `id_semestre`,
        `id_annee_academique`,
        `credit`,
        `id_enseignant`
    )
VALUES (
        95,
        'Analyse et conception à objet',
        10,
        20,
        22625,
        5,
        19
    ),
    (
        96,
        'Visualisation des données',
        10,
        20,
        22625,
        3,
        19
    ),
    (
        97,
        'Management de projet et intégration d\'application',
           10,
           20,
           22625,
           4,
           23
       ),
       (
           98,
           'Audit informatique',
           10,
           20,
           22625,
           3,
           19
       ),
       (
           99,
           'Multimedia mobile',
           10,
           20,
           22625,
           3,
           22
       ),
       (
           100,
           'Ingenierie des exigences',
           10,
           20,
           22625,
           3,
           23
       ),
       (
           101,
           'Fouille de données statistiques',
           10,
           20,
           22625,
           3,
           23
       ),
       (
           102,
           'Intelligence Artificielle',
           10,
           20,
           22625,
           4,
           19
       ),
       (
           103,
           'Anglais',
           10,
           20,
           22625,
           2,
           21
       );

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
                               `id_utilisateur` int NOT NULL,
                               `nom_utilisateur` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                               `id_type_utilisateur` int NOT NULL,
                               `id_GU` int NOT NULL,
                               `id_niv_acces_donnee` int NOT NULL,
                               `statut_utilisateur` enum('Actif', 'Inactif') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                               `login_utilisateur` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                               `mdp_utilisateur` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

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
           'soroemeric@gmail.com',
           '$2y$10$IM9LuGERPnqbR.DoqkQnMu.WBSXZJ5T5YtqBSFGO2X5nQF/xCnaFW'
       ),
       (
           98,
           'Wah Medar',
           5,
           11,
           5,
           'Actif',
           'wahmedar@gmail.com',
           '$2y$10$o19h3iUjmkyJ1.p7OXnExeIO/oticAlREufgPCuZ9jex3k5xNg/vi'
       ),
       (
           99,
           'KAMENAN DURAND',
           4,
           8,
           5,
           'Actif',
           'kamenandurand@gmail.com',
           '$2y$10$zccgQfpM82czZg.Mg1VhJuaoa5Gspi15W6/4UGAnv0qzcQ9x4aZDm'
       ),
       (
           100,
           'Akandan Aho Paul',
           7,
           13,
           5,
           'Actif',
           'ahopaul@gmail.com',
           '$2y$10$2Jg6K.W8EPchM2HOXWgSxO.z1GFwsTxxS2nTeoKUKdjtva1BqzoHS'
       ),
       (
           101,
           'Irie Adjo Jemima',
           7,
           13,
           5,
           'Actif',
           'iriejemima@gmail.com',
           '$2y$10$av1M4Ym41a.jvterPHEg1OIRx1KjfCiT0sSMZunoClBb95iWvDC/q'
       ),
       (
           102,
           'Seri Christiane',
           4,
           7,
           5,
           'Actif',
           'serichristiane@gmail.com',
           '$2y$10$gvMP07YTYLrLnBWEpC3PLeKXFrWz33Cfy7yYoNnfA9AOCvhMDpd/2'
       ),
       (
           103,
           'Brou Patrice',
           5,
           11,
           5,
           'Actif',
           'bpatrice@gmail.com',
           '$2y$10$tDob7dnjKgShm5HQulw6QuQoM3gQsrZ1zitc4Z97.7qFkYd28DPlG'
       ),
       (
           105,
           'Foursov Michael',
           6,
           12,
           5,
           'Actif',
           'michaelfoufou@gmail.com',
           '$2y$10$GLmI5ZJFlIvnqJ4pJhSLFOE/H5FyjSrjHStotwwok9MQA1.LWec4a'
       ),
       (
           106,
           'Nindjin Malan',
           6,
           12,
           5,
           'Actif',
           'nindjinmalan.0@gmail.com',
           '$2y$10$E3SNJ9zlFQ8wQ3x8lI1B7uTR5j1gO2TtFGQlRqVCjQgVhizwzOYUy'
       ),
       (
           108,
           'Diarra prenom',
           5,
           11,
           5,
           'Actif',
           'Diarraprenom@gmail.com',
           '$2y$10$d8ULmH4sGq3II1sPwWeMFOsJsy4JAXEYu9NR79SU7wr8NzEiNzSK2'
       );

-- --------------------------------------------------------

--
-- Structure de la table `valider`
--

CREATE TABLE `valider` (
                           `id_enseignant` int NOT NULL,
                           `id_rapport` int NOT NULL,
                           `date_validation` datetime NOT NULL,
                           `commentaire_validation` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                           `decision_validation` enum('valider', 'rejeter') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'valider'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci;

--
-- Déchargement des données de la table `valider`
--

INSERT INTO
    `valider` (
    `id_enseignant`,
    `id_rapport`,
    `date_validation`,
    `commentaire_validation`,
    `decision_validation`
)
VALUES (
           19,
           16,
           '2025-09-29 23:20:50',
           'nous somme impatient de vous voir a votre soutenance',
           'valider'
       );

-- --------------------------------------------------------

--
-- Structure de la table `versements`
--

CREATE TABLE `versements` (
                              `id_versement` int NOT NULL,
                              `id_inscription` int DEFAULT NULL,
                              `montant` decimal(10, 2) DEFAULT NULL,
                              `date_versement` datetime DEFAULT CURRENT_TIMESTAMP,
                              `type_versement` enum(
        'Premier versement',
        'Tranche'
    ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL, -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci
                              `methode_paiement` enum(
        'Espèce',
        'Carte bancaire',
        'Virement',
        'Chèque'
    ) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3 COLLATE = utf8mb3_general_ci; -- Changement de utf8mb4_0900_ai_ci à utf8mb3_general_ci

--
-- Déchargement des données de la table `versements`
--

INSERT INTO
    `versements` (
    `id_versement`,
    `id_inscription`,
    `montant`,
    `date_versement`,
    `type_versement`,
    `methode_paiement`
)
VALUES (
           67,
           34,
           560000.00,
           '2025-09-28 21:39:12',
           'Premier versement',
           'Espèce'
       ),
       (
           68,
           34,
           200000.00,
           '2025-09-28 22:59:56',
           'Tranche',
           'Chèque'
       ),
       (
           69,
           34,
           100000.00,
           '2025-09-29 14:12:15',
           'Tranche',
           'Espèce'
       ),
       (
           70,
           34,
           120000.00,
           '2025-09-29 19:04:01',
           'Tranche',
           'Espèce'
       ),
       (
           71,
           35,
           560000.00,
           '2025-09-29 22:06:37',
           'Premier versement',
           'Chèque'
       ),
       (
           72,
           35,
           420000.00,
           '2025-10-15 19:18:43',
           'Tranche',
           'Espèce'
       ),
       (
           73,
           35,
           45000.00,
           '2025-10-15 19:24:34',
           'Tranche',
           'Espèce'
       );

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `action`
--
ALTER TABLE `action` ADD PRIMARY KEY (`id_action`);

--
-- Index pour la table `affecter`
--
ALTER TABLE `affecter`
    ADD PRIMARY KEY (`id_enseignant`, `id_rapport`),
ADD KEY `Key_affecter_enseignant` (`id_enseignant`),
ADD KEY `Key_affecter_rappetu` (`id_rapport`),
ADD KEY `Key_affecter_jury` (`id_jury`);

--
-- Index pour la table `annee_academique`
--
ALTER TABLE `annee_academique` ADD PRIMARY KEY (`id_annee_acad`);

--
-- Index pour la table `approuver`
--
ALTER TABLE `approuver`
    ADD PRIMARY KEY (`id_pers_admin`, `id_rapport`),
ADD KEY `Key_approver_enseignant` (`id_pers_admin`),
ADD KEY `Key_approver_rapport` (`id_rapport`),
ADD KEY `fk_approuver_niveau` (`id_approb`);

--
-- Index pour la table `avoir`
--
ALTER TABLE `avoir`
    ADD PRIMARY KEY (`id_grade`, `id_enseignant`),
ADD KEY `Key_avoir_grade` (`id_grade`),
ADD KEY `Key_avoir_enseignant` (`id_enseignant`);

--
-- Index pour la table `candidature_soutenance`
--
ALTER TABLE `candidature_soutenance`
    ADD PRIMARY KEY (`id_candidature`),
ADD KEY `num_etu` (`num_etu`),
ADD KEY `id_pers_admin` (`id_pers_admin`);

--
-- Index pour la table `composer_jury`
--
ALTER TABLE `composer_jury`
    ADD PRIMARY KEY (
                     `num_jury`,
                     `id_enseignant`,
                     `id_qualite_jury`
        ),
ADD KEY `fk_composer_enseignant` (`id_enseignant`),
ADD KEY `fk_composer_role` (`id_qualite_jury`);

--
-- Index pour la table `compte_rendu`
--
ALTER TABLE `compte_rendu`
    ADD PRIMARY KEY (`id_CR`),
ADD KEY `fk_etudiant` (`num_etu`);

--
-- Index pour la table `compte_rendu_rapport`
--
ALTER TABLE `compte_rendu_rapport`
    ADD PRIMARY KEY (`id_CR`, `id_rapport`),
ADD KEY `id_rapport` (`id_rapport`);

--
-- Index pour la table `correspondre`
--
ALTER TABLE `correspondre`
    ADD PRIMARY KEY (`id_annee_acad`, `id_critere`),
ADD KEY `id_critere` (`id_critere`);

--
-- Index pour la table `critere_evaluation`
--
ALTER TABLE `critere_evaluation` ADD PRIMARY KEY (`id_critere`);

--
-- Index pour la table `decisions_jury`
--
ALTER TABLE `decisions_jury`
    ADD PRIMARY KEY (`id_decision`),
ADD UNIQUE KEY `lib_decision` (`lib_decision`);

--
-- Index pour la table `deposer`
--
ALTER TABLE `deposer`
    ADD PRIMARY KEY (`num_etu`, `id_rapport`),
ADD KEY `Key_deposer_etudiant` (`num_etu`),
ADD KEY `Key_deposer_rapport_etud` (`id_rapport`);

--
-- Index pour la table `echeances`
--
ALTER TABLE `echeances`
    ADD PRIMARY KEY (`id_echeance`),
ADD KEY `id_inscription` (`id_inscription`);

--
-- Index pour la table `ecue`
--
ALTER TABLE `ecue`
    ADD PRIMARY KEY (`id_ecue`),
ADD KEY `Key_ecue_ue` (`id_ue`),
ADD KEY `fk_enseignant_responsable` (`id_enseignant`);

--
-- Index pour la table `enseignants`
--
ALTER TABLE `enseignants`
    ADD PRIMARY KEY (`id_enseignant`),
ADD KEY `Key_enseign_specialite` (`id_specialite`);

--
-- Index pour la table `entreprises`
--
ALTER TABLE `entreprises`
    ADD PRIMARY KEY (`id_entreprise`),
ADD UNIQUE KEY `lib_entreprise` (`lib_entreprise`);

--
-- Index pour la table `etudiants`
--
ALTER TABLE `etudiants` ADD PRIMARY KEY (`num_etu`);

--
-- Index pour la table `evaluations_rapports`
--
ALTER TABLE `evaluations_rapports`
    ADD PRIMARY KEY (`id_evaluation`),
ADD KEY `id_evaluateur` (`id_evaluateur`),
ADD KEY `id_rapport` (`id_rapport`);

--
-- Index pour la table `evaluer`
--
ALTER TABLE `evaluer`
    ADD PRIMARY KEY (
                     `num_etudiant`,
                     `num_jury`,
                     `id_critere`
        ),
ADD KEY `id_critere` (`id_critere`),
ADD KEY `num_jury` (`num_jury`);

--
-- Index pour la table `filiere`
--
ALTER TABLE `filiere` ADD PRIMARY KEY (`id_filiere`);

--
-- Index pour la table `fonction`
--
ALTER TABLE `fonction` ADD PRIMARY KEY (`id_fonction`);

--
-- Index pour la table `grade`
--
ALTER TABLE `grade`
    ADD PRIMARY KEY (`id_grade`),
ADD UNIQUE KEY `lib_grade` (`lib_grade`);

--
-- Index pour la table `groupe_utilisateur`
--
ALTER TABLE `groupe_utilisateur` ADD PRIMARY KEY (`id_GU`);

--
-- Index pour la table `informations_stage`
--
ALTER TABLE `informations_stage`
    ADD PRIMARY KEY (`id_info_stage`),
ADD KEY `num_etu` (`num_etu`),
ADD KEY `id_entreprise` (`id_entreprise`);

--
-- Index pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
    ADD PRIMARY KEY (`id_inscription`),
ADD KEY `id_etudiant` (`id_etudiant`),
ADD KEY `id_niveau` (`id_niveau`),
ADD KEY `id_annee_acad` (`id_annee_acad`);

--
-- Index pour la table `mentions`
--
ALTER TABLE `mentions`
    ADD PRIMARY KEY (`id_mention`),
ADD UNIQUE KEY `lib_mention` (`lib_mention`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages` ADD PRIMARY KEY (`id_message`);

--
-- Index pour la table `niveau_acces_donnees`
--
ALTER TABLE `niveau_acces_donnees`
    ADD PRIMARY KEY (`id_niveau_acces_donnees`);

--
-- Index pour la table `niveau_approbation`
--
ALTER TABLE `niveau_approbation` ADD PRIMARY KEY (`id_approb`);

--
-- Index pour la table `niveau_etude`
--
ALTER TABLE `niveau_etude`
    ADD PRIMARY KEY (`id_niv_etude`),
ADD KEY `id_enseignant` (`id_enseignant`);

--
-- Index pour la table `notes`
--
ALTER TABLE `notes`
    ADD PRIMARY KEY (`id`),
ADD KEY `notes_ibfk_1` (`num_etu`),
ADD KEY `notes_ibfk_2` (`id_ue`),
ADD KEY `notes_ibfk_3` (`id_ecue`);

--
-- Index pour la table `occuper`
--
ALTER TABLE `occuper`
    ADD PRIMARY KEY (
                     `id_fonction`,
                     `id_enseignant`
        ),
ADD KEY `Key_occuper_enseignant` (`id_enseignant`),
ADD KEY `Key_occuper_fonction` (`id_fonction`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
    ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `token` (`token`),
ADD KEY `email` (`email`),
ADD KEY `expires_at` (`expires_at`);

--
-- Index pour la table `personnel_admin`
--
ALTER TABLE `personnel_admin` ADD PRIMARY KEY (`id_pers_admin`);

--
-- Index pour la table `pister`
--
ALTER TABLE `pister`
    ADD PRIMARY KEY (`id_piste`),
ADD KEY `idx_utilisateur` (`id_utilisateur`),
ADD KEY `idx_action` (`action`),
ADD KEY `idx_table` (`nom_table`),
ADD KEY `idx_created_at` (`date_creation`),
ADD KEY `idx_utilisateur_action` (`id_utilisateur`, `action`),
ADD KEY `id_action` (`action`),
ADD KEY `id_action_2` (`action`);

--
-- Index pour la table `programmer`
--
ALTER TABLE `programmer`
    ADD PRIMARY KEY (`id_programmation`),
ADD KEY `num_etud` (`num_etud`),
ADD KEY `id_salle` (`id_salle`);

--
-- Index pour la table `rapport_etudiants`
--
ALTER TABLE `rapport_etudiants`
    ADD PRIMARY KEY (`id_rapport`),
ADD KEY `num_etu` (`num_etu`);

--
-- Index pour la table `rattacher`
--
ALTER TABLE `rattacher`
    ADD PRIMARY KEY (`id_GU`, `id_traitement`),
ADD KEY `Key_rattacher_GU` (`id_GU`),
ADD KEY `Key_rattacher_traitement` (`id_traitement`);

--
-- Index pour la table `reclamations`
--
ALTER TABLE `reclamations`
    ADD PRIMARY KEY (`id_reclamation`),
ADD KEY `idx_num_etu` (`num_etu`),
ADD KEY `idx_statut` (`statut_reclamation`),
ADD KEY `idx_type` (`type_reclamation`),
ADD KEY `idx_date_creation` (`date_creation`),
ADD KEY `fk_admin_assigne` (`id_pers_admin`);

--
-- Index pour la table `rendre`
--
ALTER TABLE `rendre`
    ADD KEY `Key_rendre_CR` (`id_CR`),
ADD KEY `Key_rendre_enseignant` (`id_enseignant`);

--
-- Index pour la table `resume_candidature`
--
ALTER TABLE `resume_candidature`
    ADD PRIMARY KEY (`id`),
ADD KEY `num_etu` (`num_etu`),
ADD KEY `fk_candidature` (`id_candidature`);

--
-- Index pour la table `roles_jury`
--
ALTER TABLE `roles_jury`
    ADD PRIMARY KEY (`id_role_jury`),
ADD UNIQUE KEY `lib_role` (`lib_role`);

--
-- Index pour la table `salles`
--
ALTER TABLE `salles` ADD PRIMARY KEY (`id_salle`);

--
-- Index pour la table `semestre`
--
ALTER TABLE `semestre`
    ADD PRIMARY KEY (`id_semestre`),
ADD KEY `id_niv_etude` (`id_niv_etude`);

--
-- Index pour la table `specialite`
--
ALTER TABLE `specialite` ADD PRIMARY KEY (`id_specialite`);

--
-- Index pour la table `statut_jury`
--
ALTER TABLE `statut_jury` ADD PRIMARY KEY (`id_jury`);

--
-- Index pour la table `traitement`
--
ALTER TABLE `traitement` ADD PRIMARY KEY (`id_traitement`);

--
-- Index pour la table `type_utilisateur`
--
ALTER TABLE `type_utilisateur`
    ADD PRIMARY KEY (`id_type_utilisateur`);

--
-- Index pour la table `ue`
--
ALTER TABLE `ue`
    ADD PRIMARY KEY (`id_ue`),
ADD KEY `id_annee_academique` (`id_annee_academique`),
ADD KEY `id_niveau_etude` (`id_niveau_etude`),
ADD KEY `id_semestre` (`id_semestre`),
ADD KEY `fk_enseignant_responsable` (`id_enseignant`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
    ADD PRIMARY KEY (`id_utilisateur`),
ADD UNIQUE KEY `login_utilisateur` (`login_utilisateur`),
ADD KEY `id_groupe_utilisateur` (`id_GU`),
ADD KEY `id_niv_acces_donnee` (`id_niv_acces_donnee`),
ADD KEY `id_type_utilisateur` (`id_type_utilisateur`);

--
-- Index pour la table `valider`
--
ALTER TABLE `valider`
    ADD PRIMARY KEY (`id_enseignant`, `id_rapport`),
ADD KEY `Key_valider_enseignant` (`id_enseignant`),
ADD KEY `Key_valider_rapport` (`id_rapport`);

--
-- Index pour la table `versements`
--
ALTER TABLE `versements`
    ADD PRIMARY KEY (`id_versement`),
ADD KEY `id_inscription` (`id_inscription`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `action`
--
ALTER TABLE `action`
    MODIFY `id_action` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 8;

--
-- AUTO_INCREMENT pour la table `annee_academique`
--
ALTER TABLE `annee_academique`
    MODIFY `id_annee_acad` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 29901;

--
-- AUTO_INCREMENT pour la table `candidature_soutenance`
--
ALTER TABLE `candidature_soutenance`
    MODIFY `id_candidature` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 15;

--
-- AUTO_INCREMENT pour la table `compte_rendu`
--
ALTER TABLE `compte_rendu`
    MODIFY `id_CR` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 31;

--
-- AUTO_INCREMENT pour la table `critere_evaluation`
--
ALTER TABLE `critere_evaluation`
    MODIFY `id_critere` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 10;

--
-- AUTO_INCREMENT pour la table `decisions_jury`
--
ALTER TABLE `decisions_jury`
    MODIFY `id_decision` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 4;

--
-- AUTO_INCREMENT pour la table `echeances`
--
ALTER TABLE `echeances`
    MODIFY `id_echeance` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 54;

--
-- AUTO_INCREMENT pour la table `ecue`
--
ALTER TABLE `ecue`
    MODIFY `id_ecue` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 53;

--
-- AUTO_INCREMENT pour la table `enseignants`
--
ALTER TABLE `enseignants`
    MODIFY `id_enseignant` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 24;

--
-- AUTO_INCREMENT pour la table `entreprises`
--
ALTER TABLE `entreprises`
    MODIFY `id_entreprise` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 12;

--
-- AUTO_INCREMENT pour la table `etudiants`
--
ALTER TABLE `etudiants`
    MODIFY `num_etu` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 20250003;

--
-- AUTO_INCREMENT pour la table `evaluations_rapports`
--
ALTER TABLE `evaluations_rapports`
    MODIFY `id_evaluation` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 25;

--
-- AUTO_INCREMENT pour la table `filiere`
--
ALTER TABLE `filiere`
    MODIFY `id_filiere` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `fonction`
--
ALTER TABLE `fonction`
    MODIFY `id_fonction` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 21;

--
-- AUTO_INCREMENT pour la table `grade`
--
ALTER TABLE `grade`
    MODIFY `id_grade` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 17;

--
-- AUTO_INCREMENT pour la table `groupe_utilisateur`
--
ALTER TABLE `groupe_utilisateur`
    MODIFY `id_GU` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 19;

--
-- AUTO_INCREMENT pour la table `informations_stage`
--
ALTER TABLE `informations_stage`
    MODIFY `id_info_stage` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 11;

--
-- AUTO_INCREMENT pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
    MODIFY `id_inscription` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 36;

--
-- AUTO_INCREMENT pour la table `mentions`
--
ALTER TABLE `mentions`
    MODIFY `id_mention` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 6;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
    MODIFY `id_message` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 6;

--
-- AUTO_INCREMENT pour la table `niveau_acces_donnees`
--
ALTER TABLE `niveau_acces_donnees`
    MODIFY `id_niveau_acces_donnees` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 6;

--
-- AUTO_INCREMENT pour la table `niveau_approbation`
--
ALTER TABLE `niveau_approbation`
    MODIFY `id_approb` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 7;

--
-- AUTO_INCREMENT pour la table `niveau_etude`
--
ALTER TABLE `niveau_etude`
    MODIFY `id_niv_etude` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 17;

--
-- AUTO_INCREMENT pour la table `notes`
--
ALTER TABLE `notes`
    MODIFY `id` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 90;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
    MODIFY `id` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 4;

--
-- AUTO_INCREMENT pour la table `personnel_admin`
--
ALTER TABLE `personnel_admin`
    MODIFY `id_pers_admin` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 11;

--
-- AUTO_INCREMENT pour la table `pister`
--
ALTER TABLE `pister`
    MODIFY `id_piste` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 308;

--
-- AUTO_INCREMENT pour la table `programmer`
--
ALTER TABLE `programmer`
    MODIFY `id_programmation` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 3;

--
-- AUTO_INCREMENT pour la table `rapport_etudiants`
--
ALTER TABLE `rapport_etudiants`
    MODIFY `id_rapport` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 17;

--
-- AUTO_INCREMENT pour la table `reclamations`
--
ALTER TABLE `reclamations`
    MODIFY `id_reclamation` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 5;

--
-- AUTO_INCREMENT pour la table `resume_candidature`
--
ALTER TABLE `resume_candidature`
    MODIFY `id` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 11;

--
-- AUTO_INCREMENT pour la table `roles_jury`
--
ALTER TABLE `roles_jury`
    MODIFY `id_role_jury` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 6;

--
-- AUTO_INCREMENT pour la table `salles`
--
ALTER TABLE `salles`
    MODIFY `id_salle` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 3;

--
-- AUTO_INCREMENT pour la table `semestre`
--
ALTER TABLE `semestre`
    MODIFY `id_semestre` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 24;

--
-- AUTO_INCREMENT pour la table `specialite`
--
ALTER TABLE `specialite`
    MODIFY `id_specialite` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 17;

--
-- AUTO_INCREMENT pour la table `statut_jury`
--
ALTER TABLE `statut_jury`
    MODIFY `id_jury` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 8;

--
-- AUTO_INCREMENT pour la table `traitement`
--
ALTER TABLE `traitement`
    MODIFY `id_traitement` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 48;

--
-- AUTO_INCREMENT pour la table `type_utilisateur`
--
ALTER TABLE `type_utilisateur`
    MODIFY `id_type_utilisateur` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 12;

--
-- AUTO_INCREMENT pour la table `ue`
--
ALTER TABLE `ue`
    MODIFY `id_ue` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 104;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
    MODIFY `id_utilisateur` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 109;

--
-- AUTO_INCREMENT pour la table `versements`
--
ALTER TABLE `versements`
    MODIFY `id_versement` int NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 74;

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
-- Contraintes pour la table `approuver`
--
ALTER TABLE `approuver`
    ADD CONSTRAINT `fk_approuver_niveau` FOREIGN KEY (`id_approb`) REFERENCES `niveau_approbation` (`id_approb`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_approuver_pers_admin` FOREIGN KEY (`id_pers_admin`) REFERENCES `personnel_admin` (`id_pers_admin`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_approuver_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `avoir`
--
ALTER TABLE `avoir`
    ADD CONSTRAINT `fk_avoir_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_avoir_grade` FOREIGN KEY (`id_grade`) REFERENCES `grade` (`id_grade`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `candidature_soutenance`
--
ALTER TABLE `candidature_soutenance`
    ADD CONSTRAINT `candidature_soutenance_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`),
ADD CONSTRAINT `candidature_soutenance_ibfk_2` FOREIGN KEY (`id_pers_admin`) REFERENCES `personnel_admin` (`id_pers_admin`);

--
-- Contraintes pour la table `composer_jury`
--
ALTER TABLE `composer_jury`
    ADD CONSTRAINT `fk_composer_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_composer_role` FOREIGN KEY (`id_qualite_jury`) REFERENCES `roles_jury` (`id_role_jury`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `compte_rendu`
--
ALTER TABLE `compte_rendu`
    ADD CONSTRAINT `fk_etudiant` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `compte_rendu_rapport`
--
ALTER TABLE `compte_rendu_rapport`
    ADD CONSTRAINT `compte_rendu_rapport_ibfk_1` FOREIGN KEY (`id_CR`) REFERENCES `compte_rendu` (`id_CR`) ON DELETE CASCADE,
ADD CONSTRAINT `compte_rendu_rapport_ibfk_2` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE;

--
-- Contraintes pour la table `correspondre`
--
ALTER TABLE `correspondre`
    ADD CONSTRAINT `correspondre_ibfk_1` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `correspondre_ibfk_2` FOREIGN KEY (`id_critere`) REFERENCES `critere_evaluation` (`id_critere`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `deposer`
--
ALTER TABLE `deposer`
    ADD CONSTRAINT `fk_deposer_etudiant` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_deposer_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `echeances`
--
ALTER TABLE `echeances`
    ADD CONSTRAINT `echeances_ibfk_1` FOREIGN KEY (`id_inscription`) REFERENCES `inscriptions` (`id_inscription`);

--
-- Contraintes pour la table `ecue`
--
ALTER TABLE `ecue`
    ADD CONSTRAINT `fk_ecue_ue` FOREIGN KEY (`id_ue`) REFERENCES `ue` (`id_ue`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_enseignant_responsable` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `enseignants`
--
ALTER TABLE `enseignants`
    ADD CONSTRAINT `fk_enseignants_specialite` FOREIGN KEY (`id_specialite`) REFERENCES `specialite` (`id_specialite`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `evaluations_rapports`
--
ALTER TABLE `evaluations_rapports`
    ADD CONSTRAINT `evaluations_rapports_ibfk_1` FOREIGN KEY (`id_evaluateur`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `evaluations_rapports_ibfk_2` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `evaluer`
--
ALTER TABLE `evaluer`
    ADD CONSTRAINT `evaluer_ibfk_1` FOREIGN KEY (`id_critere`) REFERENCES `critere_evaluation` (`id_critere`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `evaluer_ibfk_2` FOREIGN KEY (`num_etudiant`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `informations_stage`
--
ALTER TABLE `informations_stage`
    ADD CONSTRAINT `informations_stage_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `informations_stage_ibfk_2` FOREIGN KEY (`id_entreprise`) REFERENCES `entreprises` (`id_entreprise`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
    ADD CONSTRAINT `inscriptions_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`num_etu`),
ADD CONSTRAINT `inscriptions_ibfk_2` FOREIGN KEY (`id_niveau`) REFERENCES `niveau_etude` (`id_niv_etude`),
ADD CONSTRAINT `inscriptions_ibfk_3` FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Contraintes pour la table `niveau_etude`
--
ALTER TABLE `niveau_etude`
    ADD CONSTRAINT `fk_niveau_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
    ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`id_ue`) REFERENCES `ue` (`id_ue`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`id_ecue`) REFERENCES `ecue` (`id_ecue`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `occuper`
--
ALTER TABLE `occuper`
    ADD CONSTRAINT `fk_occuper_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_occuper_fonction` FOREIGN KEY (`id_fonction`) REFERENCES `fonction` (`id_fonction`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `pister`
--
ALTER TABLE `pister`
    ADD CONSTRAINT `fk_pister_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `programmer`
--
ALTER TABLE `programmer`
    ADD CONSTRAINT `programmer_ibfk_1` FOREIGN KEY (`num_etud`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rapport_etudiants`
--
ALTER TABLE `rapport_etudiants`
    ADD CONSTRAINT `ibfk_etudiant` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rattacher`
--
ALTER TABLE `rattacher`
    ADD CONSTRAINT `fk_rattacher_gu` FOREIGN KEY (`id_GU`) REFERENCES `groupe_utilisateur` (`id_GU`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `fk_rattacher_traitement` FOREIGN KEY (`id_traitement`) REFERENCES `traitement` (`id_traitement`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reclamations`
--
ALTER TABLE `reclamations`
    ADD CONSTRAINT `fk_admin_assigne` FOREIGN KEY (`id_pers_admin`) REFERENCES `personnel_admin` (`id_pers_admin`) ON DELETE CASCADE ON UPDATE CASCADE;

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
    ADD CONSTRAINT `resume_ibfk_1` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `resume_ibfk_2` FOREIGN KEY (`id_candidature`) REFERENCES `candidature_soutenance` (`id_candidature`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `semestre`
--
ALTER TABLE `semestre`
    ADD CONSTRAINT `fk_niveau_etude` FOREIGN KEY (`id_niv_etude`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `ue`
--
ALTER TABLE `ue`
    ADD CONSTRAINT `ue_ibfk_1` FOREIGN KEY (`id_annee_academique`) REFERENCES `annee_academique` (`id_annee_acad`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `ue_ibfk_2` FOREIGN KEY (`id_niveau_etude`) REFERENCES `niveau_etude` (`id_niv_etude`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `ue_ibfk_3` FOREIGN KEY (`id_semestre`) REFERENCES `semestre` (`id_semestre`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `ue_ibfk_4` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id_enseignant`) ON DELETE CASCADE ON UPDATE CASCADE;

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
