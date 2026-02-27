-- ============================================================
-- PRD 8 : Enregistrement fonctionnalite + permissions
-- A executer UNE SEULE FOIS en production
-- ============================================================

-- 1. Inserer la fonctionnalite (idempotent)
INSERT INTO fonctionnalites (
    id_categorie,
    code_fonctionnalite,
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
    'repertoire_enseignant',
    'Repertoire documents',
    'Repertoire documents',
    'Consultation des rapports, comptes-rendus et memoires rattaches a l enseignant',
    '?page=repertoire_enseignant',
    'fas fa-folder-open',
    10,
    0,
    NULL,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'repertoire_enseignant'
);

-- 2. Recuperer l'id de la fonctionnalite creee
SET @id_fonc = (SELECT id_fonctionnalite FROM fonctionnalites WHERE code_fonctionnalite = 'repertoire_enseignant' LIMIT 1);

-- 3. Permissions enseignant (id_GU = 12 : Enseignant sans responsabilite administrative)
-- Supprimer d'abord si existe pour eviter les doublons
DELETE FROM permissions WHERE id_GU = 12 AND id_fonctionnalite = @id_fonc;

INSERT INTO permissions (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer)
VALUES (12, @id_fonc, 1, 0, 0, 0);

-- 4. Permissions admin (id_GU = 5 : Administrateur)
DELETE FROM permissions WHERE id_GU = 5 AND id_fonctionnalite = @id_fonc;

INSERT INTO permissions (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer)
VALUES (5, @id_fonc, 1, 1, 1, 1);

-- 5. Permissions pour les autres responsables enseignants
-- Responsable Filiere (id_GU = 9)
DELETE FROM permissions WHERE id_GU = 9 AND id_fonctionnalite = @id_fonc;
INSERT INTO permissions (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer)
VALUES (9, @id_fonc, 1, 0, 0, 0);

-- Responsable niveau (id_GU = 10)
DELETE FROM permissions WHERE id_GU = 10 AND id_fonctionnalite = @id_fonc;
INSERT INTO permissions (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer)
VALUES (10, @id_fonc, 1, 0, 0, 0);

-- Commission de validation (id_GU = 11)
DELETE FROM permissions WHERE id_GU = 11 AND id_fonctionnalite = @id_fonc;
INSERT INTO permissions (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer)
VALUES (11, @id_fonc, 1, 0, 0, 0);
