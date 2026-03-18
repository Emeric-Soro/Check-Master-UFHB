-- ============================================================
-- CORRECTION : Montants des inscriptions / versements
-- Problème 1 : frais_inscription.montant erroné → doit être 1 025 000 FCFA
-- Problème 2 : montant_verser et solde saisis sans ×1000
-- Exemple avant : montant_verser = 300   → doit être 300 000 FCFA
-- Exemple après : montant_verser = 300 000
-- ============================================================

-- 1. Vérification AVANT correction (optionnel — pour comparer après)
SELECT
    num_carte_etud,
    id_annee_acad,
    num_versement,
    montant_verser,
    solde
FROM inscriptions
ORDER BY
    num_carte_etud,
    id_annee_acad,
    num_versement
LIMIT 20;

-- 2a. Correction des frais de référence Master 2
--     Ancienne valeur : 950 000 → Valeur correcte : 1 025 000 FCFA
UPDATE `frais_inscription`
SET
    `montant` = 1025000.00
WHERE
    `id_niv_etude` = 'M2';

-- 2b. Correction des versements saisis sans ×1000
--    • montant_verser : multiplié par 1000
--    • solde          : multiplié par 1000
--    • Sécurité       : WHERE évite de retoucher des lignes déjà au bon format
--      (un montant_verser >= 1000 serait déjà en FCFA complet)
UPDATE `inscriptions`
SET
    `montant_verser` = `montant_verser` * 1000,
    `solde` = `solde` * 1000
WHERE
    `montant_verser` < 1000;

-- 3. Vérification APRÈS correction
SELECT
    num_carte_etud,
    id_annee_acad,
    num_versement,
    montant_verser,
    solde
FROM inscriptions
ORDER BY
    num_carte_etud,
    id_annee_acad,
    num_versement
LIMIT 20;

-- 4. Contrôle de cohérence : total payé VS frais de référence
SELECT
    i.num_carte_etud,
    i.id_annee_acad,
    fi.montant AS frais_reference,
    SUM(i.montant_verser) AS total_verse,
    MIN(i.solde) AS solde_restant,
    fi.montant - SUM(i.montant_verser) AS ecart
FROM
    inscriptions i
    JOIN frais_inscription fi ON fi.id_niv_etude = i.id_niv_etude
    AND fi.id_annee_acad = i.id_annee_acad
GROUP BY
    i.num_carte_etud,
    i.id_annee_acad,
    fi.montant
ORDER BY i.id_annee_acad DESC, i.num_carte_etud;