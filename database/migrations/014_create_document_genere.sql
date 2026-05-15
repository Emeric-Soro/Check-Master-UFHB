-- Migration 014: Create document_genere table + register DocViewer in RBAC
-- Run this script against the CheckMaster database.

-- 1. Create the document_genere table
CREATE TABLE IF NOT EXISTS `document_genere` (
    `id_document`      INT AUTO_INCREMENT PRIMARY KEY,
    `reference`        VARCHAR(20) NOT NULL,
    `type_document`    VARCHAR(10) NOT NULL,
    `id_utilisateur`   INT NOT NULL,
    `id_source`        VARCHAR(50) DEFAULT NULL,
    `chemin_fichier`   VARCHAR(500) NOT NULL,
    `nom_fichier`      VARCHAR(255) NOT NULL,
    `taille_fichier`   BIGINT DEFAULT 0,
    `date_generation`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `nb_consultations` INT DEFAULT 0,
    UNIQUE KEY `uq_document_genere_reference` (`reference`),
    KEY `idx_type_document` (`type_document`),
    KEY `idx_id_source` (`id_source`),
    KEY `idx_date_generation` (`date_generation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- 2. Register the DocViewer feature in fonctionnalites
-- Use the same id_categorie as ADM_DASHBOARD (administrateur category)
INSERT INTO `fonctionnalites` (
    `id_categorie`, `code_fonctionnalite`, `slug_permission`, `lib_fonctionnalite`,
    `label_fonctionnalite`, `description_fonctionnalite`, `url_fonctionnalite`,
    `icone_fonctionnalite`, `ordre_fonctionnalite`, `est_sous_page`, `page_parente`, `actif`
)
SELECT
    `id_categorie`, 'DOC_VIEWER', 'docviewer', 'Visionneuse de documents',
    'Visionneuse de documents', 'Previsualisation et telechargement unifie des documents PDF',
    '?page=docviewer', 'fas fa-file-pdf', 0, 0, NULL, 1
FROM `fonctionnalites`
WHERE `code_fonctionnalite` = 'ADM_DASHBOARD'
LIMIT 1;

-- 3. Register route_actions for docviewer
SET @docviewer_fid = (SELECT `id_fonctionnalite` FROM `fonctionnalites` WHERE `code_fonctionnalite` = 'DOC_VIEWER' LIMIT 1);

INSERT INTO `route_actions` (`route_pattern`, `http_method`, `action_crud`, `id_fonctionnalite`, `is_public`, `description`, `actif`) VALUES
    ('page=docviewer&action=preview',  'GET', 'voir', @docviewer_fid, 0, 'Previsualisation PDF inline via DocViewer', 1),
    ('page=docviewer&action=download', 'GET', 'voir', @docviewer_fid, 0, 'Telechargement PDF via DocViewer', 1);

-- 4. Grant view permission to all relevant groups
-- 5=Administrateur, 6=Secretaire, 8=Responsable scolarite, 11=Commission, 12=Enseignant, 13=Etudiant
INSERT INTO `permissions` (`id_GU`, `id_fonctionnalite`, `peut_voir`, `peut_creer`, `peut_modifier`, `peut_supprimer`)
SELECT `g`.`id_GU`, @docviewer_fid, 1, 0, 0, 0
FROM (
    SELECT 5 AS `id_GU` UNION SELECT 6 UNION SELECT 8 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13
) AS `g`;
