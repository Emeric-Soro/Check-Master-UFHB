-- Patch memoires / validation multi-roles
-- Execution sure: rerunnable on MySQL/MariaDB.

START TRANSACTION;

-- 1) Nouveau groupe "Admin + Responsable filiere"
INSERT INTO groupe_utilisateur (id_GU, lib_GU, id_type_utilisateur)
SELECT 14, 'Admin + Responsable filiere', 4
WHERE NOT EXISTS (
    SELECT 1
    FROM groupe_utilisateur
    WHERE id_GU = 14
);

-- 2) Dupliquer les permissions admin existantes vers le groupe 14
INSERT INTO permissions (
    id_GU,
    id_fonctionnalite,
    peut_voir,
    peut_creer,
    peut_modifier,
    peut_supprimer
)
SELECT
    14,
    p.id_fonctionnalite,
    p.peut_voir,
    p.peut_creer,
    p.peut_modifier,
    p.peut_supprimer
FROM permissions p
WHERE p.id_GU = 5
ON DUPLICATE KEY UPDATE
    peut_voir = VALUES(peut_voir),
    peut_creer = VALUES(peut_creer),
    peut_modifier = VALUES(peut_modifier),
    peut_supprimer = VALUES(peut_supprimer);

-- 3) Table d'historique des validations de memoires
CREATE TABLE IF NOT EXISTS evaluations_memoires (
    id_evaluation INT NOT NULL AUTO_INCREMENT,
    id_document BIGINT UNSIGNED NOT NULL,
    id_rapport INT NULL,
    id_evaluateur INT NOT NULL,
    type_evaluateur ENUM('encadrant', 'directeur', 'responsable_filiere') NOT NULL,
    decision ENUM('valider', 'rejeter') NOT NULL,
    commentaire TEXT NULL,
    date_evaluation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evaluation),
    UNIQUE KEY uq_eval_memoire_document_role (id_document, type_evaluateur),
    KEY idx_eval_memoire_document (id_document),
    KEY idx_eval_memoire_rapport (id_rapport),
    KEY idx_eval_memoire_evaluateur (id_evaluateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Fonctionnalite / menu de validation memoires
INSERT INTO fonctionnalites (
    id_categorie,
    code_fonctionnalite,
    slug_permission,
    lib_fonctionnalite,
    label_fonctionnalite,
    description_fonctionnalite,
    url_fonctionnalite,
    icone_fonctionnalite,
    ordre_fonctionnalite,
    est_sous_page,
    page_parente,
    actif
)
SELECT
    25,
    'MEMOIRE_VALIDATION',
    'validation_memoires',
    'Validation des memoires',
    'Validation des memoires',
    'Validation du memoire par l''encadrant, le directeur et le responsable de filiere',
    '?page=validation_memoires',
    'fa-solid fa-book-open-reader',
    11,
    0,
    NULL,
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM fonctionnalites
    WHERE code_fonctionnalite = 'MEMOIRE_VALIDATION'
       OR slug_permission = 'validation_memoires'
);

SET @id_validation_memoires := (
    SELECT id_fonctionnalite
    FROM fonctionnalites
    WHERE slug_permission = 'validation_memoires'
       OR code_fonctionnalite = 'MEMOIRE_VALIDATION'
    LIMIT 1
);

-- 5) Permissions du nouvel ecran
INSERT INTO permissions (
    id_GU,
    id_fonctionnalite,
    peut_voir,
    peut_creer,
    peut_modifier,
    peut_supprimer
)
SELECT 5, @id_validation_memoires, 1, 1, 1, 1
WHERE @id_validation_memoires IS NOT NULL
ON DUPLICATE KEY UPDATE
    peut_voir = VALUES(peut_voir),
    peut_creer = VALUES(peut_creer),
    peut_modifier = VALUES(peut_modifier),
    peut_supprimer = VALUES(peut_supprimer);

INSERT INTO permissions (
    id_GU,
    id_fonctionnalite,
    peut_voir,
    peut_creer,
    peut_modifier,
    peut_supprimer
)
SELECT 14, @id_validation_memoires, 1, 1, 1, 1
WHERE @id_validation_memoires IS NOT NULL
ON DUPLICATE KEY UPDATE
    peut_voir = VALUES(peut_voir),
    peut_creer = VALUES(peut_creer),
    peut_modifier = VALUES(peut_modifier),
    peut_supprimer = VALUES(peut_supprimer);

INSERT INTO permissions (
    id_GU,
    id_fonctionnalite,
    peut_voir,
    peut_creer,
    peut_modifier,
    peut_supprimer
)
SELECT 9, @id_validation_memoires, 1, 0, 1, 0
WHERE @id_validation_memoires IS NOT NULL
ON DUPLICATE KEY UPDATE
    peut_voir = VALUES(peut_voir),
    peut_creer = VALUES(peut_creer),
    peut_modifier = VALUES(peut_modifier),
    peut_supprimer = VALUES(peut_supprimer);

INSERT INTO permissions (
    id_GU,
    id_fonctionnalite,
    peut_voir,
    peut_creer,
    peut_modifier,
    peut_supprimer
)
SELECT 12, @id_validation_memoires, 1, 0, 1, 0
WHERE @id_validation_memoires IS NOT NULL
ON DUPLICATE KEY UPDATE
    peut_voir = VALUES(peut_voir),
    peut_creer = VALUES(peut_creer),
    peut_modifier = VALUES(peut_modifier),
    peut_supprimer = VALUES(peut_supprimer);

-- 6) Routes du nouvel ecran
INSERT INTO route_actions (
    route_pattern,
    http_method,
    action_crud,
    id_fonctionnalite,
    is_public,
    description,
    notes_admin,
    actif
)
SELECT
    'page=validation_memoires',
    'GET',
    'voir',
    @id_validation_memoires,
    0,
    'RBAC Validation des memoires [GET page=validation_memoires]',
    'patch:validation_memoires',
    1
WHERE @id_validation_memoires IS NOT NULL
ON DUPLICATE KEY UPDATE
    id_fonctionnalite = VALUES(id_fonctionnalite),
    action_crud = VALUES(action_crud),
    is_public = VALUES(is_public),
    description = VALUES(description),
    notes_admin = VALUES(notes_admin),
    actif = VALUES(actif);

INSERT INTO route_actions (
    route_pattern,
    http_method,
    action_crud,
    id_fonctionnalite,
    is_public,
    description,
    notes_admin,
    actif
)
SELECT
    'page=validation_memoires&action=enregistrer_decision',
    'POST',
    'modifier',
    @id_validation_memoires,
    0,
    'RBAC Validation des memoires [POST page=validation_memoires&action=enregistrer_decision]',
    'patch:validation_memoires',
    1
WHERE @id_validation_memoires IS NOT NULL
ON DUPLICATE KEY UPDATE
    id_fonctionnalite = VALUES(id_fonctionnalite),
    action_crud = VALUES(action_crud),
    is_public = VALUES(is_public),
    description = VALUES(description),
    notes_admin = VALUES(notes_admin),
    actif = VALUES(actif);

COMMIT;
