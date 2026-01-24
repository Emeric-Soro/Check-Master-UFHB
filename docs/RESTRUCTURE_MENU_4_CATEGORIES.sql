-- ============================================================
-- Regrouper les menus en 4 grandes catégories (2026)
--   1) Gestion de la scolarité
--   2) Environnement Étudiant
--   3) Commission validation
--   4) Administration plateforme
--
-- Compatible avec le système actuel:
-- - permissions restent liées à id_fonctionnalite (inchangé)
-- - on ne fait que déplacer les fonctionnalités vers de nouvelles catégories
-- ============================================================

START TRANSACTION;

-- 1) Créer (ou récupérer) les 4 catégories
SET @cat_scolarite_id := (SELECT id_categorie FROM categories_fonctionnalites WHERE code_categorie = 'SCOLARITE' LIMIT 1);
SET @cat_etudiant_id  := (SELECT id_categorie FROM categories_fonctionnalites WHERE code_categorie = 'ETUDIANT_ENV' LIMIT 1);
SET @cat_comm_id      := (SELECT id_categorie FROM categories_fonctionnalites WHERE code_categorie = 'COMMISSION' LIMIT 1);
SET @cat_admin_id     := (SELECT id_categorie FROM categories_fonctionnalites WHERE code_categorie = 'ADMIN_PLATEFORME' LIMIT 1);

INSERT INTO categories_fonctionnalites (code_categorie, lib_categorie, description_categorie, icone_categorie, ordre_categorie, actif)
SELECT 'SCOLARITE', 'Gestion de la scolarité', 'Scolarité, inscriptions, notes, réclamations scolarité', 'fas fa-school', 1, 1
WHERE @cat_scolarite_id IS NULL;
SET @cat_scolarite_id := COALESCE(@cat_scolarite_id, LAST_INSERT_ID());

INSERT INTO categories_fonctionnalites (code_categorie, lib_categorie, description_categorie, icone_categorie, ordre_categorie, actif)
SELECT 'ETUDIANT_ENV', 'Environnement Étudiant', 'Espace étudiant: candidature, rapports, résultats, réclamations', 'fas fa-user-graduate', 2, 1
WHERE @cat_etudiant_id IS NULL;
SET @cat_etudiant_id := COALESCE(@cat_etudiant_id, LAST_INSERT_ID());

INSERT INTO categories_fonctionnalites (code_categorie, lib_categorie, description_categorie, icone_categorie, ordre_categorie, actif)
SELECT 'COMMISSION', 'Commission validation', 'Jury/commission: validation, soutenances, comptes-rendus', 'fas fa-check-double', 3, 1
WHERE @cat_comm_id IS NULL;
SET @cat_comm_id := COALESCE(@cat_comm_id, LAST_INSERT_ID());

INSERT INTO categories_fonctionnalites (code_categorie, lib_categorie, description_categorie, icone_categorie, ordre_categorie, actif)
SELECT 'ADMIN_PLATEFORME', 'Administration plateforme', 'Administration, paramètres, utilisateurs, audit, sauvegardes', 'fas fa-tools', 4, 1
WHERE @cat_admin_id IS NULL;
SET @cat_admin_id := COALESCE(@cat_admin_id, LAST_INSERT_ID());

-- 2) Déplacer les fonctionnalités vers les 4 catégories
-- Notes:
-- - La liste est basée sur le dump `ufrmi1802974_2q2mpf.sql` (ids 1..59)
-- - Tu peux ajuster cette répartition sans impacter les permissions.

-- Environnement Étudiant
UPDATE fonctionnalites
SET id_categorie = @cat_etudiant_id
WHERE id_fonctionnalite IN (
  9,10,11,        -- gestion rapports + sous-écrans
  13,             -- candidature étudiant
  25,             -- mes résultats
  27              -- mes réclamations
);

-- Gestion de la scolarité
UPDATE fonctionnalites
SET id_categorie = @cat_scolarite_id
WHERE id_fonctionnalite IN (
  3,              -- dashboard scolarité
  6,8,            -- gestion étudiants + liste responsable
  14,54,          -- gestion candidatures (scolarité) + dossiers vérifiés
  53,             -- gestion scolarité (paiements/reçus)
  26,58,          -- saisie notes + gestion notes/évaluations
  28              -- réclamations scolarité
);

-- Commission validation
UPDATE fonctionnalites
SET id_categorie = @cat_comm_id
WHERE id_fonctionnalite IN (
  2,5,            -- dashboard enseignant + commission
  7,12,           -- liste étudiants enseignant + vérification rapports
  15,             -- valider candidatures (commission)
  16,17,57,       -- processus validation + évaluation dossiers + éval dossiers soutenance
  18,19,20,       -- programmer jury + planifier + évaluer soutenance
  21,22,23,24,59, -- comptes rendus + archives
  56              -- rapports à valider
);

-- Administration plateforme
UPDATE fonctionnalites
SET id_categorie = @cat_admin_id
WHERE id_fonctionnalite IN (
  1,4,            -- dashboard global + secrétaire (admin)
  29,             -- RH
  30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49, -- paramètres généraux + sous-pages
  50,51,52,55     -- utilisateurs + audit + backup + historique
);

-- 3) (Optionnel) Désactiver les anciennes catégories pour n'afficher que les 4 nouvelles
UPDATE categories_fonctionnalites
SET actif = CASE
  WHEN code_categorie IN ('SCOLARITE','ETUDIANT_ENV','COMMISSION','ADMIN_PLATEFORME') THEN 1
  ELSE 0
END;

COMMIT;

