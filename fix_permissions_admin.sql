-- =====================================================
-- Script pour donner TOUTES les permissions à l'Administrateur
-- =====================================================

-- Supposons que l'administrateur a id_GU = 5
-- Ajustez si nécessaire (vérifiez avec: SELECT * FROM groupe_utilisateur WHERE lib_GU LIKE '%Admin%')

-- Supprimer les permissions existantes de l'admin pour éviter les doublons
DELETE FROM permissions WHERE id_GU = 5;

-- Attribuer TOUTES les fonctionnalités à l'administrateur avec tous les droits
INSERT INTO
    permissions (
        id_GU,
        id_fonctionnalite,
        peut_voir,
        peut_creer,
        peut_modifier,
        peut_supprimer
    )
SELECT
    5 as id_GU,
    id_fonctionnalite,
    TRUE as peut_voir,
    TRUE as peut_creer,
    TRUE as peut_modifier,
    TRUE as peut_supprimer
FROM fonctionnalites
WHERE
    actif = TRUE;

-- Vérifier le résultat
SELECT
    COUNT(*) as nb_permissions,
    SUM(peut_voir) as peut_voir,
    SUM(peut_creer) as peut_creer,
    SUM(peut_modifier) as peut_modifier,
    SUM(peut_supprimer) as peut_supprimer
FROM permissions
WHERE
    id_GU = 5;

-- Afficher les catégories et fonctionnalités accessibles
SELECT
    c.lib_categorie as Categorie,
    COUNT(f.id_fonctionnalite) as NbFonctionnalites
FROM
    categories_fonctionnalites c
    INNER JOIN fonctionnalites f ON c.id_categorie = f.id_categorie
    INNER JOIN permissions p ON f.id_fonctionnalite = p.id_fonctionnalite
WHERE
    p.id_GU = 5
    AND p.peut_voir = TRUE
GROUP BY
    c.id_categorie,
    c.lib_categorie
ORDER BY c.ordre_categorie;