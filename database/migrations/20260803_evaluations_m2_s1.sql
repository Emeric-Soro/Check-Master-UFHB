-- CheckMaster - référentiel UE et évaluations M2/S1
-- Migration idempotente. Les anciennes tables notes/evaluer restent intactes.

CREATE TABLE IF NOT EXISTS `semestre_referentiel` (
    `code_semestre` varchar(30) NOT NULL,
    `libelle_semestre` varchar(120) NOT NULL,
    `actif` tinyint(1) NOT NULL DEFAULT 1,
    `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`code_semestre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `semestre_referentiel_alias` (
    `alias_semestre` varchar(60) NOT NULL,
    `code_semestre` varchar(30) NOT NULL,
    PRIMARY KEY (`alias_semestre`),
    KEY `idx_semestre_alias_code` (`code_semestre`),
    CONSTRAINT `fk_semestre_alias_referentiel`
        FOREIGN KEY (`code_semestre`) REFERENCES `semestre_referentiel` (`code_semestre`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `semestre_referentiel` (`code_semestre`, `libelle_semestre`)
VALUES ('M2_S1', 'Semestre 1 Master 2 (S3 / S9)')
ON DUPLICATE KEY UPDATE `libelle_semestre` = VALUES(`libelle_semestre`);

INSERT INTO `semestre_referentiel_alias` (`alias_semestre`, `code_semestre`)
VALUES
    ('M2_S1', 'M2_S1'),
    ('M2 S1', 'M2_S1'),
    ('S3', 'M2_S1'),
    ('S9', 'M2_S1'),
    ('SEMESTRE 3', 'M2_S1'),
    ('SEMESTRE 9', 'M2_S1')
ON DUPLICATE KEY UPDATE `code_semestre` = VALUES(`code_semestre`);

CREATE TABLE IF NOT EXISTS `ue` (
    `id_ue` bigint unsigned NOT NULL AUTO_INCREMENT,
    `code_ue` varchar(50) NOT NULL,
    `libelle_ue` varchar(255) NOT NULL,
    `credit_ue` decimal(5,2) NOT NULL,
    `date_credit` date NOT NULL,
    `semestre_code` varchar(30) NOT NULL DEFAULT 'M2_S1',
    `id_niv_etude` varchar(2) DEFAULT NULL,
    `parcours` varchar(100) DEFAULT NULL,
    `version_ue` int unsigned NOT NULL DEFAULT 1,
    `date_fin` date DEFAULT NULL,
    `ordre_ue` int unsigned NOT NULL DEFAULT 0,
    `actif` tinyint(1) NOT NULL DEFAULT 1,
    `created_by` int DEFAULT NULL,
    `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` datetime DEFAULT NULL,
    PRIMARY KEY (`id_ue`),
    UNIQUE KEY `uq_ue_version` (`code_ue`, `semestre_code`, `date_credit`, `version_ue`),
    KEY `idx_ue_semestre_actif` (`semestre_code`, `actif`, `date_credit`),
    KEY `idx_ue_niveau` (`id_niv_etude`),
    CONSTRAINT `fk_ue_semestre_referentiel`
        FOREIGN KEY (`semestre_code`) REFERENCES `semestre_referentiel` (`code_semestre`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evaluation_s3_import_batch` (
    `id_batch` bigint unsigned NOT NULL AUTO_INCREMENT,
    `nom_fichier` varchar(255) NOT NULL,
    `empreinte_fichier` char(64) DEFAULT NULL,
    `statut_batch` enum('previsualisation','confirme','rejete') NOT NULL DEFAULT 'previsualisation',
    `total_lignes` int unsigned NOT NULL DEFAULT 0,
    `lignes_valides` int unsigned NOT NULL DEFAULT 0,
    `lignes_erreur` int unsigned NOT NULL DEFAULT 0,
    `id_utilisateur` int DEFAULT NULL,
    `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_confirmation` datetime DEFAULT NULL,
    PRIMARY KEY (`id_batch`),
    KEY `idx_import_batch_user` (`id_utilisateur`, `date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evaluation_s3_import_row` (
    `id_ligne` bigint unsigned NOT NULL AUTO_INCREMENT,
    `id_batch` bigint unsigned NOT NULL,
    `numero_ligne` int unsigned NOT NULL,
    `payload_brut` json NOT NULL,
    `num_etu` varchar(25) DEFAULT NULL,
    `id_ue` bigint unsigned DEFAULT NULL,
    `id_annee_acad` int DEFAULT NULL,
    `note` decimal(5,2) DEFAULT NULL,
    `date_note` date DEFAULT NULL,
    `session_normale` tinyint(1) DEFAULT NULL,
    `statut_ligne` enum('valide','erreur','importe') NOT NULL DEFAULT 'erreur',
    `message_erreur` text DEFAULT NULL,
    `message_avertissement` text DEFAULT NULL,
    `id_evaluation` bigint unsigned DEFAULT NULL,
    PRIMARY KEY (`id_ligne`),
    UNIQUE KEY `uq_import_row_number` (`id_batch`, `numero_ligne`),
    KEY `idx_import_row_status` (`id_batch`, `statut_ligne`),
    CONSTRAINT `fk_import_row_batch`
        FOREIGN KEY (`id_batch`) REFERENCES `evaluation_s3_import_batch` (`id_batch`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `evaluation_s3` (
    `id_evaluation` bigint unsigned NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `id_ue` bigint unsigned NOT NULL,
    `id_annee_acad` int NOT NULL,
    `note_obtenue_ue` decimal(5,2) NOT NULL,
    `date_note` date NOT NULL,
    `session_normale` tinyint(1) NOT NULL DEFAULT 1,
    `id_batch_import` bigint unsigned DEFAULT NULL,
    `created_by` int DEFAULT NULL,
    `updated_by` int DEFAULT NULL,
    `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` datetime DEFAULT NULL,
    PRIMARY KEY (`id_evaluation`),
    UNIQUE KEY `uq_evaluation_s3` (`num_etu`, `id_ue`, `id_annee_acad`, `session_normale`),
    KEY `idx_evaluation_s3_year` (`id_annee_acad`, `num_etu`),
    KEY `idx_evaluation_s3_ue` (`id_ue`),
    CONSTRAINT `fk_evaluation_s3_etudiant`
        FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_evaluation_s3_ue`
        FOREIGN KEY (`id_ue`) REFERENCES `ue` (`id_ue`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_evaluation_s3_annee`
        FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_evaluation_s3_import_batch`
        FOREIGN KEY (`id_batch_import`) REFERENCES `evaluation_s3_import_batch` (`id_batch`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

SET @cm_add_nouveau = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `etudiants` ADD COLUMN `nouveau` tinyint(1) NOT NULL DEFAULT 1',
        'SET @cm_noop = 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'etudiants' AND COLUMN_NAME = 'nouveau'
);
PREPARE cm_stmt FROM @cm_add_nouveau;
EXECUTE cm_stmt;
DEALLOCATE PREPARE cm_stmt;

SET @cm_add_nouveau_m2 = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `cycle_etudiant_statut` ADD COLUMN `nouveau_m2` tinyint(1) DEFAULT NULL',
        'SET @cm_noop = 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cycle_etudiant_statut' AND COLUMN_NAME = 'nouveau_m2'
);
PREPARE cm_stmt FROM @cm_add_nouveau_m2;
EXECUTE cm_stmt;
DEALLOCATE PREPARE cm_stmt;

SET @cm_expand_document_type = (
    SELECT IF(
        COUNT(*) = 1 AND MAX(CHARACTER_MAXIMUM_LENGTH) < 40,
        'ALTER TABLE `document_genere` MODIFY COLUMN `type_document` varchar(40) NOT NULL',
        'SET @cm_noop = 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'document_genere'
      AND COLUMN_NAME = 'type_document'
);
PREPARE cm_stmt FROM @cm_expand_document_type;
EXECUTE cm_stmt;
DEALLOCATE PREPARE cm_stmt;

CREATE TABLE IF NOT EXISTS `suivi_encadrement_rdv` (
    `id_rdv` bigint unsigned NOT NULL AUTO_INCREMENT,
    `num_etu` varchar(25) NOT NULL,
    `id_annee_acad` int NOT NULL,
    `type_encadrement` enum('directeur_memoire','encadreur_pedagogique') NOT NULL,
    `date_rdv` date DEFAULT NULL,
    `signature_etudiant` varchar(255) DEFAULT NULL,
    `signature_encadrant` varchar(255) DEFAULT NULL,
    `observation` varchar(1000) DEFAULT NULL,
    `created_by` int DEFAULT NULL,
    `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_rdv`),
    KEY `idx_suivi_rdv_student_year` (`num_etu`, `id_annee_acad`, `type_encadrement`),
    CONSTRAINT `fk_suivi_rdv_etudiant`
        FOREIGN KEY (`num_etu`) REFERENCES `etudiants` (`num_carte_etud`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_suivi_rdv_annee`
        FOREIGN KEY (`id_annee_acad`) REFERENCES `annee_academique` (`id_annee_acad`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
