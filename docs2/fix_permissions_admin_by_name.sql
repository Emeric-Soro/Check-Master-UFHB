-- =====================================================
-- Donner TOUTES les permissions au groupe "Administrateur"
-- (sans dépendre de id_GU=5)
-- =====================================================

START TRANSACTION;

-- 1) Trouver l'id_GU de l'admin (par libellé)
SET @admin_id := (
    SELECT id_GU
    FROM groupe_utilisateur
    WHERE LOWER(TRIM(lib_GU)) IN ('administrateur','admin')
    LIMIT 1
);

-- Si @admin_id est NULL => il faut créer/renommer le groupe côté DB
-- SELECT @admin_id as admin_id;

-- 2) Nettoyer les permissions existantes de l'admin
DELETE FROM permissions WHERE id_GU = @admin_id;

-- 3) Attribuer toutes les fonctionnalités actives avec tous les droits
INSERT INTO permissions (
    id_GU,
    id_fonctionnalite,
    peut_voir,
    peut_creer,
    peut_modifier,
    peut_supprimer
)
SELECT
    @admin_id,
    f.id_fonctionnalite,
    TRUE,
    TRUE,
    TRUE,
    TRUE
FROM fonctionnalites f
WHERE f.actif = TRUE;

COMMIT;

-- Vérification
SELECT
  @admin_id as admin_id,
  COUNT(*) as nb_permissions,
  SUM(peut_voir) as peut_voir,
  SUM(peut_creer) as peut_creer,
  SUM(peut_modifier) as peut_modifier,
  SUM(peut_supprimer) as peut_supprimer
FROM permissions
WHERE id_GU = @admin_id;

