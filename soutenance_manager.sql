-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : db
-- Généré le : sam. 27 sep. 2025 à 16:01
-- Version du serveur : 8.0.43
-- Version de PHP : 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `soutenance_manager`
--

-- --------------------------------------------------------

--
-- Structure de la table `action`
--

CREATE TABLE `action` (
  `id_action` int NOT NULL,
  `lib_action` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `action`
--

INSERT INTO `action` (`id_action`, `lib_action`) VALUES
(3, 'Modifier'),
(6, 'Supprimer'),
(7, 'Consulter');

-- --------------------------------------------------------

--
-- Structure de la table `affecter`
--

CREATE TABLE `affecter` (
  `id_enseignant` int NOT NULL,
  `role` enum('encadrant','directeur') COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `id_rapport` int NOT NULL,
  `id_jury` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `annee_academique`
--

CREATE TABLE `annee_academique` (
  `id_annee_acad` int NOT NULL,
  `date_deb` date NOT NULL,
  `date_fin` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `annee_academique`
--

INSERT INTO `annee_academique` (`id_annee_acad`, `date_deb`, `date_fin`) VALUES
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
(22524, '2024-09-10', '2025-07-30');

-- --------------------------------------------------------

--
-- Structure de la table `approuver`
--

CREATE TABLE `approuver` (
  `id_pers_admin` int NOT NULL,
  `id_rapport` int NOT NULL,
  `decision` enum('approuve','desapprouve') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `date_approv` datetime NOT NULL,
  `commentaire_approv` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `id_approb` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avoir`
--

CREATE TABLE `avoir` (
  `id_grade` int NOT NULL,
  `id_enseignant` int NOT NULL,
  `date_grade` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `candidature_soutenance`
--

CREATE TABLE `candidature_soutenance` (
  `id_candidature` int NOT NULL,
  `num_etu` int NOT NULL,
  `date_candidature` datetime NOT NULL,
  `statut_candidature` enum('En attente','Validée','Rejetée') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'En attente',
  `date_traitement` datetime DEFAULT NULL,
  `id_pers_admin` int DEFAULT NULL,
  `commentaire_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `compte_rendu`
--

CREATE TABLE `compte_rendu` (
  `id_CR` int NOT NULL,
  `num_etu` int NOT NULL,
  `nom_CR` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `contenu_CR` longtext COLLATE utf8mb3_general_mysql500_ci,
  `chemin_fichier_pdf` varchar(255) COLLATE utf8mb3_general_mysql500_ci DEFAULT NULL,
  `date_CR` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `compte_rendu_rapport`
--

CREATE TABLE `compte_rendu_rapport` (
  `id_CR` int NOT NULL,
  `id_rapport` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `deposer`
--

CREATE TABLE `deposer` (
  `num_etu` int NOT NULL,
  `id_rapport` int NOT NULL,
  `date_depot` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `dossier_academique`
--

CREATE TABLE `dossier_academique` (
  `id_dossier` int NOT NULL,
  `num_etu` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `nationalite` varchar(50) DEFAULT NULL,
  `situation_familiale` varchar(50) DEFAULT NULL,
  `dernier_diplome` varchar(100) DEFAULT NULL,
  `etablissement_origine` varchar(100) DEFAULT NULL,
  `annee_obtention_diplome` year DEFAULT NULL,
  `mention_diplome` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `echeances`
--

CREATE TABLE `echeances` (
  `id_echeance` int NOT NULL,
  `id_inscription` int DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT NULL,
  `date_echeance` date DEFAULT NULL,
  `statut_echeance` enum('En attente','Payée','En retard') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ecue`
--

CREATE TABLE `ecue` (
  `id_ecue` int NOT NULL,
  `id_ue` int NOT NULL,
  `lib_ecue` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `credit` int NOT NULL,
  `id_enseignant` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `ecue`
--

INSERT INTO `ecue` (`id_ecue`, `id_ue`, `lib_ecue`, `credit`, `id_enseignant`) VALUES
(49, 56, 'ISI', 2, NULL),
(50, 56, 'UML', 3, NULL),
(51, 57, 'Files d\'attente et gestion de stock', 3, NULL),
(52, 57, 'Regression linéaire', 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `enseignants`
--

CREATE TABLE `enseignants` (
  `id_enseignant` int NOT NULL,
  `nom_enseignant` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `prenom_enseignant` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `mail_enseignant` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `id_specialite` int NOT NULL,
  `type_enseignant` enum('Simple','Administratif') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `enseignants`
--

INSERT INTO `enseignants` (`id_enseignant`, `nom_enseignant`, `prenom_enseignant`, `mail_enseignant`, `id_specialite`, `type_enseignant`) VALUES
(7, 'Koua', 'Brou', 'kouabrou@gmail.com', 2, 'Simple');

-- --------------------------------------------------------

--
-- Structure de la table `entreprises`
--

CREATE TABLE `entreprises` (
  `id_entreprise` int NOT NULL,
  `lib_entreprise` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `entreprises`
--

INSERT INTO `entreprises` (`id_entreprise`, `lib_entreprise`) VALUES
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
  `nom_etu` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `prenom_etu` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `email_etu` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `date_naiss_etu` date NOT NULL,
  `genre_etu` enum('Homme','Femme','Neutre') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `promotion_etu` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evaluations_rapports`
--

CREATE TABLE `evaluations_rapports` (
  `id_evaluation` int NOT NULL,
  `id_rapport` int NOT NULL,
  `id_evaluateur` int NOT NULL,
  `decision_evaluation` enum('valider','rejeter') DEFAULT NULL,
  `commentaire` text,
  `date_evaluation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evaluer`
--

CREATE TABLE `evaluer` (
  `num_etu` int NOT NULL,
  `id_ecue` int NOT NULL,
  `id_enseignant` int NOT NULL,
  `date_evaluation` datetime NOT NULL,
  `note` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fonction`
--

CREATE TABLE `fonction` (
  `id_fonction` int NOT NULL,
  `lib_fonction` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `fonction`
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
-- Structure de la table `grade`
--

CREATE TABLE `grade` (
  `id_grade` int NOT NULL,
  `lib_grade` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `grade`
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
-- Structure de la table `groupe_utilisateur`
--

CREATE TABLE `groupe_utilisateur` (
  `id_GU` int NOT NULL,
  `lib_GU` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `groupe_utilisateur`
--

INSERT INTO `groupe_utilisateur` (`id_GU`, `lib_GU`) VALUES
(5, 'Administrateur'),
(6, 'Secretaire'),
(7, 'Chargée de communication'),
(8, 'Responsable scolarité'),
(9, 'Responsable Filière'),
(10, 'Responsable niveau'),
(11, 'commission de validation'),
(12, 'Enseignant sans responsabilité administrative'),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `statut_inscription` enum('En cours','Validée','Annulée') DEFAULT NULL,
  `nombre_tranche` int NOT NULL,
  `reste_a_payer` decimal(10,2) NOT NULL,
  `montant_paye` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id_message` int NOT NULL,
  `contenu_message` text NOT NULL,
  `lib_message` varchar(60) NOT NULL,
  `type_message` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id_message`, `contenu_message`, `lib_message`, `type_message`) VALUES
(3, 'Bienvenue sur CheckMaster', 'message_bienvenue', 'info'),
(4, 'Erreur lors du traitement du fichier', 'messageErreur', 'error');

-- --------------------------------------------------------

--
-- Structure de la table `niveau_acces_donnees`
--

CREATE TABLE `niveau_acces_donnees` (
  `id_niveau_acces_donnees` int NOT NULL,
  `lib_niveau_acces_donnees` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `niveau_acces_donnees`
--

INSERT INTO `niveau_acces_donnees` (`id_niveau_acces_donnees`, `lib_niveau_acces_donnees`) VALUES
(4, 'Lecture seule'),
(5, 'Écriture');

-- --------------------------------------------------------

--
-- Structure de la table `niveau_approbation`
--

CREATE TABLE `niveau_approbation` (
  `id_approb` int NOT NULL,
  `lib_approb` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `niveau_approbation`
--

INSERT INTO `niveau_approbation` (`id_approb`, `lib_approb`) VALUES
(3, 'Niveau 1'),
(4, 'Niveau 2'),
(6, 'Niveau 3');

-- --------------------------------------------------------

--
-- Structure de la table `niveau_etude`
--

CREATE TABLE `niveau_etude` (
  `id_niv_etude` int NOT NULL,
  `lib_niv_etude` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `id_enseignant` int DEFAULT NULL,
  `montant_scolarite` decimal(10,2) DEFAULT NULL,
  `montant_inscription` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `niveau_etude`
--

INSERT INTO `niveau_etude` (`id_niv_etude`, `lib_niv_etude`, `id_enseignant`, `montant_scolarite`, `montant_inscription`) VALUES
(10, 'Master 1', 7, 980000.00, 560000.00);

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

CREATE TABLE `notes` (
  `id` int NOT NULL,
  `num_etu` int NOT NULL,
  `id_ue` int DEFAULT NULL,
  `id_ecue` int DEFAULT NULL,
  `moyenne` decimal(4,2) NOT NULL,
  `commentaire` text,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `occuper`
--

CREATE TABLE `occuper` (
  `id_fonction` int NOT NULL,
  `id_enseignant` int NOT NULL,
  `date_occupation` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `used`, `created_at`) VALUES
(3, 'soroemeric@gmail.com', '553a20d449bd775c694fc29cfa4b4bc14d7eb4fd79e01b5cbbe86be46b430b15', '2025-09-27 14:51:50', 1, '2025-09-27 13:51:50');

-- --------------------------------------------------------

--
-- Structure de la table `personnel_admin`
--

CREATE TABLE `personnel_admin` (
  `id_pers_admin` int NOT NULL,
  `nom_pers_admin` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `prenom_pers_admin` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `email_pers_admin` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `tel_pers_admin` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `poste` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `date_embauche` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pister`
--

CREATE TABLE `pister` (
  `id_piste` int NOT NULL,
  `id_utilisateur` int NOT NULL,
  `action` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d''action (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc.)',
  `statut_action` enum('Erreur','Succès') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom_table` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nom de la table concernée',
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `pister`
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
(162, 5, 'Accès', 'Succès', 'tableau_de_bord', '2025-09-27 15:46:14');

-- --------------------------------------------------------

--
-- Structure de la table `rapport_etudiants`
--

CREATE TABLE `rapport_etudiants` (
  `id_rapport` int NOT NULL,
  `num_etu` int NOT NULL,
  `nom_rapport` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `date_rapport` datetime NOT NULL,
  `theme_rapport` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `chemin_fichier` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci DEFAULT NULL COMMENT 'Chemin vers le fichier de contenu',
  `statut_rapport` enum('en_cours','valider','rejeter','en_attente') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL DEFAULT 'en_cours',
  `date_modification` datetime DEFAULT NULL,
  `taille_fichier` int DEFAULT NULL COMMENT 'Taille du fichier en octets',
  `version` int NOT NULL DEFAULT '1' COMMENT 'Version du rapport',
  `etape_validation` enum('en_cours','en_attente_communication','desapprouve_communication','approuve_communication','en_attente_commission','desapprouve_commission','approuve_commission','valide','rejete') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci DEFAULT 'en_cours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rattacher`
--

CREATE TABLE `rattacher` (
  `id_GU` int NOT NULL,
  `id_traitement` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `rattacher`
--

INSERT INTO `rattacher` (`id_GU`, `id_traitement`) VALUES
(5, 5),
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
  `type_reclamation` enum('Académique','Administrative','Technique','Financière','Autre') NOT NULL,
  `priorite_reclamation` enum('Faible','Moyenne','Élevée','Urgente') NOT NULL DEFAULT 'Moyenne',
  `statut_reclamation` enum('En attente','Résolue','Rejetée','En cours') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'En attente',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_mise_a_jour` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_pers_admin` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendre`
--

CREATE TABLE `rendre` (
  `id_CR` int NOT NULL,
  `id_enseignant` int NOT NULL,
  `date_env` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `resume_candidature`
--

CREATE TABLE `resume_candidature` (
  `id` int NOT NULL,
  `num_etu` int NOT NULL,
  `id_candidature` int NOT NULL,
  `resume_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `decision` varchar(20) NOT NULL,
  `date_enregistrement` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `semestre`
--

CREATE TABLE `semestre` (
  `id_semestre` int NOT NULL,
  `lib_semestre` varchar(100) NOT NULL,
  `id_niv_etude` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `semestre`
--

INSERT INTO `semestre` (`id_semestre`, `lib_semestre`, `id_niv_etude`) VALUES
(20, 'Semestre 7', 10),
(21, 'Semestre 8', 10);

-- --------------------------------------------------------

--
-- Structure de la table `specialite`
--

CREATE TABLE `specialite` (
  `id_specialite` int NOT NULL,
  `lib_specialite` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `specialite`
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
-- Structure de la table `statut_jury`
--

CREATE TABLE `statut_jury` (
  `id_jury` int NOT NULL,
  `lib_jury` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `statut_jury`
--

INSERT INTO `statut_jury` (`id_jury`, `lib_jury`) VALUES
(6, 'accepter'),
(7, 'refuser');

-- --------------------------------------------------------

--
-- Structure de la table `traitement`
--

CREATE TABLE `traitement` (
  `id_traitement` int NOT NULL,
  `lib_traitement` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `label_traitement` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `icone_traitement` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `ordre_traitement` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `traitement`
--

INSERT INTO `traitement` (`id_traitement`, `lib_traitement`, `label_traitement`, `icone_traitement`, `ordre_traitement`) VALUES
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
(43, 'archive_comptes_rendus', 'Archive des comptes rendus', 'fa-book', 9);

-- --------------------------------------------------------

--
-- Structure de la table `type_utilisateur`
--

CREATE TABLE `type_utilisateur` (
  `id_type_utilisateur` int NOT NULL,
  `lib_type_utilisateur` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `type_utilisateur`
--

INSERT INTO `type_utilisateur` (`id_type_utilisateur`, `lib_type_utilisateur`) VALUES
(4, 'Personnel administratif'),
(5, 'Enseignant administratif'),
(6, 'Enseignant simple'),
(7, 'Etudiant');

-- --------------------------------------------------------

--
-- Structure de la table `ue`
--

CREATE TABLE `ue` (
  `id_ue` int NOT NULL,
  `lib_ue` varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `id_niveau_etude` int NOT NULL,
  `id_semestre` int NOT NULL,
  `id_annee_academique` int NOT NULL,
  `credit` int NOT NULL,
  `id_enseignant` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `ue`
--

INSERT INTO `ue` (`id_ue`, `lib_ue`, `id_niveau_etude`, `id_semestre`, `id_annee_academique`, `credit`, `id_enseignant`) VALUES
(56, 'Modélisation système d\'information', 10, 20, 22524, 5, NULL),
(57, 'Compléments de mathématiques', 10, 20, 22423, 4, NULL),
(58, 'Intelligence Artificielle', 10, 20, 22423, 2, NULL),
(59, 'Base de données avancées', 10, 20, 22524, 4, NULL),
(60, 'Programmation avancée Java', 10, 20, 22524, 4, NULL),
(61, 'Progiciel de comptabilité (SAGE)', 10, 20, 22423, 2, NULL),
(62, 'Management des entreprises', 10, 20, 22423, 3, NULL),
(63, 'Concurrence et coopération dans les systèmes et les réseaux', 10, 20, 22423, 4, NULL),
(64, 'Internet/Intranet', 10, 20, 22423, 2, NULL),
(65, 'Base de données décisionnelles ', 10, 21, 22524, 3, NULL),
(66, 'Programmation impérative et developpement d\'IHM ', 10, 21, 22524, 4, NULL),
(67, 'Système d\'information repartis', 10, 21, 22524, 5, NULL),
(68, 'Contrôle de gestion', 10, 21, 22524, 3, NULL),
(69, 'Comptabilité analytique', 10, 21, 22524, 4, NULL),
(70, 'Marketing', 10, 21, 22524, 3, NULL),
(71, 'Projet de developpement logiciel', 10, 21, 22524, 5, NULL),
(72, 'Anglais', 10, 21, 22524, 3, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int NOT NULL,
  `nom_utilisateur` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `id_type_utilisateur` int NOT NULL,
  `id_GU` int NOT NULL,
  `id_niv_acces_donnee` int NOT NULL,
  `statut_utilisateur` enum('Actif','Inactif') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `login_utilisateur` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `mdp_utilisateur` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom_utilisateur`, `id_type_utilisateur`, `id_GU`, `id_niv_acces_donnee`, `statut_utilisateur`, `login_utilisateur`, `mdp_utilisateur`) VALUES
(5, 'Koua Brou', 5, 5, 5, 'Actif', 'soroemeric@gmail.com', '$2y$10$IM9LuGERPnqbR.DoqkQnMu.WBSXZJ5T5YtqBSFGO2X5nQF/xCnaFW');

-- --------------------------------------------------------

--
-- Structure de la table `valider`
--

CREATE TABLE `valider` (
  `id_enseignant` int NOT NULL,
  `id_rapport` int NOT NULL,
  `date_validation` datetime NOT NULL,
  `commentaire_validation` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL,
  `decision_validation` enum('valider','rejeter') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_mysql500_ci NOT NULL DEFAULT 'valider'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_mysql500_ci;

-- --------------------------------------------------------

--
-- Structure de la table `versements`
--

CREATE TABLE `versements` (
  `id_versement` int NOT NULL,
  `id_inscription` int DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT NULL,
  `date_versement` datetime DEFAULT CURRENT_TIMESTAMP,
  `type_versement` enum('Premier versement','Tranche') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `methode_paiement` enum('Espèce','Carte bancaire','Virement','Chèque') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `action`
--
ALTER TABLE `action`
  ADD PRIMARY KEY (`id_action`);

--
-- Index pour la table `affecter`
--
ALTER TABLE `affecter`
  ADD PRIMARY KEY (`id_enseignant`,`id_rapport`),
  ADD KEY `Key_affecter_enseignant` (`id_enseignant`),
  ADD KEY `Key_affecter_rappetu` (`id_rapport`),
  ADD KEY `Key_affecter_jury` (`id_jury`);

--
-- Index pour la table `annee_academique`
--
ALTER TABLE `annee_academique`
  ADD PRIMARY KEY (`id_annee_acad`);

--
-- Index pour la table `approuver`
--
ALTER TABLE `approuver`
  ADD PRIMARY KEY (`id_pers_admin`,`id_rapport`),
  ADD KEY `Key_approver_enseignant` (`id_pers_admin`),
  ADD KEY `Key_approver_rapport` (`id_rapport`),
  ADD KEY `fk_approuver_niveau` (`id_approb`);

--
-- Index pour la table `avoir`
--
ALTER TABLE `avoir`
  ADD PRIMARY KEY (`id_grade`,`id_enseignant`),
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
-- Index pour la table `compte_rendu`
--
ALTER TABLE `compte_rendu`
  ADD PRIMARY KEY (`id_CR`),
  ADD KEY `fk_etudiant` (`num_etu`);

--
-- Index pour la table `compte_rendu_rapport`
--
ALTER TABLE `compte_rendu_rapport`
  ADD PRIMARY KEY (`id_CR`,`id_rapport`),
  ADD KEY `id_rapport` (`id_rapport`);

--
-- Index pour la table `deposer`
--
ALTER TABLE `deposer`
  ADD PRIMARY KEY (`num_etu`,`id_rapport`),
  ADD KEY `Key_deposer_etudiant` (`num_etu`),
  ADD KEY `Key_deposer_rapport_etud` (`id_rapport`);

--
-- Index pour la table `dossier_academique`
--
ALTER TABLE `dossier_academique`
  ADD PRIMARY KEY (`id_dossier`),
  ADD KEY `fk_dossier_etudiant` (`num_etu`);

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
ALTER TABLE `etudiants`
  ADD PRIMARY KEY (`num_etu`);

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
  ADD KEY `Key_evaluer_ecue` (`id_ecue`),
  ADD KEY `Key_evaluer_enseignant` (`id_enseignant`),
  ADD KEY `Key_evaluer_etudiant` (`num_etu`);

--
-- Index pour la table `fonction`
--
ALTER TABLE `fonction`
  ADD PRIMARY KEY (`id_fonction`);

--
-- Index pour la table `grade`
--
ALTER TABLE `grade`
  ADD PRIMARY KEY (`id_grade`),
  ADD UNIQUE KEY `lib_grade` (`lib_grade`);

--
-- Index pour la table `groupe_utilisateur`
--
ALTER TABLE `groupe_utilisateur`
  ADD PRIMARY KEY (`id_GU`);

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
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id_message`);

--
-- Index pour la table `niveau_acces_donnees`
--
ALTER TABLE `niveau_acces_donnees`
  ADD PRIMARY KEY (`id_niveau_acces_donnees`);

--
-- Index pour la table `niveau_approbation`
--
ALTER TABLE `niveau_approbation`
  ADD PRIMARY KEY (`id_approb`);

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
  ADD PRIMARY KEY (`id_fonction`,`id_enseignant`),
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
ALTER TABLE `personnel_admin`
  ADD PRIMARY KEY (`id_pers_admin`);

--
-- Index pour la table `pister`
--
ALTER TABLE `pister`
  ADD PRIMARY KEY (`id_piste`),
  ADD KEY `idx_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table` (`nom_table`),
  ADD KEY `idx_created_at` (`date_creation`),
  ADD KEY `idx_utilisateur_action` (`id_utilisateur`,`action`),
  ADD KEY `id_action` (`action`),
  ADD KEY `id_action_2` (`action`);

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
  ADD PRIMARY KEY (`id_GU`,`id_traitement`),
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
-- Index pour la table `semestre`
--
ALTER TABLE `semestre`
  ADD PRIMARY KEY (`id_semestre`),
  ADD KEY `id_niv_etude` (`id_niv_etude`);

--
-- Index pour la table `specialite`
--
ALTER TABLE `specialite`
  ADD PRIMARY KEY (`id_specialite`);

--
-- Index pour la table `statut_jury`
--
ALTER TABLE `statut_jury`
  ADD PRIMARY KEY (`id_jury`);

--
-- Index pour la table `traitement`
--
ALTER TABLE `traitement`
  ADD PRIMARY KEY (`id_traitement`);

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
  ADD PRIMARY KEY (`id_enseignant`,`id_rapport`),
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
  MODIFY `id_action` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `annee_academique`
--
ALTER TABLE `annee_academique`
  MODIFY `id_annee_acad` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29901;

--
-- AUTO_INCREMENT pour la table `candidature_soutenance`
--
ALTER TABLE `candidature_soutenance`
  MODIFY `id_candidature` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `compte_rendu`
--
ALTER TABLE `compte_rendu`
  MODIFY `id_CR` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pour la table `dossier_academique`
--
ALTER TABLE `dossier_academique`
  MODIFY `id_dossier` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `echeances`
--
ALTER TABLE `echeances`
  MODIFY `id_echeance` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT pour la table `ecue`
--
ALTER TABLE `ecue`
  MODIFY `id_ecue` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT pour la table `enseignants`
--
ALTER TABLE `enseignants`
  MODIFY `id_enseignant` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `entreprises`
--
ALTER TABLE `entreprises`
  MODIFY `id_entreprise` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `etudiants`
--
ALTER TABLE `etudiants`
  MODIFY `num_etu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20250003;

--
-- AUTO_INCREMENT pour la table `evaluations_rapports`
--
ALTER TABLE `evaluations_rapports`
  MODIFY `id_evaluation` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `fonction`
--
ALTER TABLE `fonction`
  MODIFY `id_fonction` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `grade`
--
ALTER TABLE `grade`
  MODIFY `id_grade` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `groupe_utilisateur`
--
ALTER TABLE `groupe_utilisateur`
  MODIFY `id_GU` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `informations_stage`
--
ALTER TABLE `informations_stage`
  MODIFY `id_info_stage` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
  MODIFY `id_inscription` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id_message` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `niveau_acces_donnees`
--
ALTER TABLE `niveau_acces_donnees`
  MODIFY `id_niveau_acces_donnees` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `niveau_approbation`
--
ALTER TABLE `niveau_approbation`
  MODIFY `id_approb` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `niveau_etude`
--
ALTER TABLE `niveau_etude`
  MODIFY `id_niv_etude` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `personnel_admin`
--
ALTER TABLE `personnel_admin`
  MODIFY `id_pers_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `pister`
--
ALTER TABLE `pister`
  MODIFY `id_piste` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT pour la table `rapport_etudiants`
--
ALTER TABLE `rapport_etudiants`
  MODIFY `id_rapport` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `reclamations`
--
ALTER TABLE `reclamations`
  MODIFY `id_reclamation` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `resume_candidature`
--
ALTER TABLE `resume_candidature`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `semestre`
--
ALTER TABLE `semestre`
  MODIFY `id_semestre` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `specialite`
--
ALTER TABLE `specialite`
  MODIFY `id_specialite` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `statut_jury`
--
ALTER TABLE `statut_jury`
  MODIFY `id_jury` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `traitement`
--
ALTER TABLE `traitement`
  MODIFY `id_traitement` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT pour la table `type_utilisateur`
--
ALTER TABLE `type_utilisateur`
  MODIFY `id_type_utilisateur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `ue`
--
ALTER TABLE `ue`
  MODIFY `id_ue` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT pour la table `versements`
--
ALTER TABLE `versements`
  MODIFY `id_versement` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

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
-- Contraintes pour la table `deposer`
--
ALTER TABLE `deposer`
  ADD CONSTRAINT `fk_deposer_etudiant` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_deposer_rapport` FOREIGN KEY (`id_rapport`) REFERENCES `rapport_etudiants` (`id_rapport`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `dossier_academique`
--
ALTER TABLE `dossier_academique`
  ADD CONSTRAINT `fk_dossier_etudiant` FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_etu`) ON DELETE CASCADE;

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
