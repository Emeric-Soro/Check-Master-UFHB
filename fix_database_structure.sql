-- Script SQL pour vérification rapide de la structure
-- Permet de tester si la connexion fonctionne après les corrections

-- Vérifier la structure de la table etudiants
DESC etudiants;

-- Test simple d'une jointure
SELECT COUNT(*) as total
FROM
    candidature_soutenance cs
    INNER JOIN etudiants e ON e.num_carte_etud = cs.num_etu;