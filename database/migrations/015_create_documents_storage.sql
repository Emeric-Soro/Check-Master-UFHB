-- Migration 015: centraliser le stockage documentaire en base de donnees
-- Cette migration est idempotente et conserve le stockage disque comme fallback.

CREATE TABLE IF NOT EXISTS `documents` (
    `id_document` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type_document` VARCHAR(30) NOT NULL,
    `sous_type` VARCHAR(30) DEFAULT NULL,
    `reference` VARCHAR(50) DEFAULT NULL,
    `nom_fichier` VARCHAR(255) NOT NULL,
    `extension` VARCHAR(10) NOT NULL,
    `type_mime` VARCHAR(100) NOT NULL,
    `entite_type` VARCHAR(50) DEFAULT NULL,
    `entite_id` VARCHAR(50) DEFAULT NULL,
    `contenu` LONGBLOB NOT NULL,
    `taille_fichier` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `version` INT UNSIGNED NOT NULL DEFAULT 1,
    `id_utilisateur` INT DEFAULT NULL,
    `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `nb_consultations` INT UNSIGNED NOT NULL DEFAULT 0,
    `chemin_original` VARCHAR(500) DEFAULT NULL,
    `statut` ENUM('actif', 'archive', 'supprime') NOT NULL DEFAULT 'actif',
    PRIMARY KEY (`id_document`),
    UNIQUE KEY `uq_documents_reference` (`reference`),
    KEY `idx_documents_type` (`type_document`),
    KEY `idx_documents_entite` (`entite_type`, `entite_id`),
    KEY `idx_documents_utilisateur` (`id_utilisateur`),
    KEY `idx_documents_date_creation` (`date_creation`),
    KEY `idx_documents_statut` (`statut`),
    KEY `idx_documents_type_statut` (`type_document`, `statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documents_consultations` (
    `id_consultation` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_document` BIGINT UNSIGNED NOT NULL,
    `id_utilisateur` INT DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `date_consultation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_consultation`),
    KEY `idx_documents_consultations_document` (`id_document`),
    KEY `idx_documents_consultations_date` (`date_consultation`),
    CONSTRAINT `fk_documents_consultations_document`
        FOREIGN KEY (`id_document`) REFERENCES `documents` (`id_document`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
