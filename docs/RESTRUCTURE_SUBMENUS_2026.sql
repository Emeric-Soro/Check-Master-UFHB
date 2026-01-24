-- ============================================================
-- Sous-menus (niveau 2) selon ta structure remaniée (2026)
--
-- Principe:
-- - Les "sous-menus" sont des entrées `fonctionnalites` (est_sous_page=0)
-- - Les "écrans" sont des sous-pages (est_sous_page=1) avec `page_parente = code du sous-menu`
-- - Les écrans "actions" (ex: créer rapport, suivi rapport) restent possibles
--   mais ne sont pas affichés dans le menu si `est_sous_page=1` et `page_parente` est NULL.
--
-- Compatible avec les ids du dump `ufrmi1802974_2q2mpf.sql` (fonctionnalites 1..59).
-- À exécuter APRÈS `docs/RESTRUCTURE_MENU_4_CATEGORIES.sql` (catégories).
-- ============================================================

START TRANSACTION;

-- 0) Résoudre les IDs de catégories (par code_categorie)
SET @cat_scolarite_id := (SELECT id_categorie FROM categories_fonctionnalites WHERE code_categorie = 'SCOLARITE' LIMIT 1);
SET @cat_etudiant_id  := (SELECT id_categorie FROM categories_fonctionnalites WHERE code_categorie = 'ETUDIANT_ENV' LIMIT 1);
SET @cat_comm_id      := (SELECT id_categorie FROM categories_fonctionnalites WHERE code_categorie = 'COMMISSION' LIMIT 1);
SET @cat_admin_id     := (SELECT id_categorie FROM categories_fonctionnalites WHERE code_categorie = 'ADMIN_PLATEFORME' LIMIT 1);

-- 1) Créer les sous-menus (parents) s'ils n'existent pas
-- Gestion de la scolarité
INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_scolarite_id, 'SCOL_INSCRIPTIONS', 'Inscriptions', 'Inscriptions', NULL, '#', 'fas fa-user-plus', 10, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'SCOL_INSCRIPTIONS');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_scolarite_id, 'SCOL_EVALUATIONS', 'Évaluations', 'Évaluations', NULL, '#', 'fas fa-clipboard-list', 20, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'SCOL_EVALUATIONS');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_scolarite_id, 'SCOL_RECLAMATIONS', 'Réclamations', 'Réclamations', NULL, '#', 'fas fa-exclamation-circle', 30, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'SCOL_RECLAMATIONS');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_scolarite_id, 'SCOL_DOSSIERS', 'Dossiers', 'Dossiers', NULL, '#', 'fas fa-folder-open', 40, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'SCOL_DOSSIERS');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_scolarite_id, 'SCOL_CONSULTATION', 'Consultation', 'Consultation', NULL, '#', 'fas fa-search', 50, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'SCOL_CONSULTATION');

-- Environnement étudiant
INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_etudiant_id, 'ETU_CANDIDATURE', 'Candidature', 'Candidature', NULL, '#', 'fas fa-file-signature', 10, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'ETU_CANDIDATURE');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_etudiant_id, 'ETU_RESULTATS', 'Résultats', 'Résultats', NULL, '#', 'fas fa-poll', 20, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'ETU_RESULTATS');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_etudiant_id, 'ETU_RECLAMATIONS', 'Réclamations', 'Réclamations', NULL, '#', 'fas fa-comment-alt', 30, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'ETU_RECLAMATIONS');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_etudiant_id, 'ETU_SUIVI', 'Suivi', 'Suivi', NULL, '#', 'fas fa-folder-open', 40, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'ETU_SUIVI');

-- Commission validation
INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_comm_id, 'COM_GESTION', 'Gestion commissions', 'Gestion commissions', NULL, '#', 'fas fa-users', 10, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'COM_GESTION');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_comm_id, 'COM_JURY', 'Jury', 'Jury', NULL, '#', 'fas fa-user-friends', 20, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'COM_JURY');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_comm_id, 'COM_EVALUATION', 'Évaluation', 'Évaluation', NULL, '#', 'fas fa-star', 30, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'COM_EVALUATION');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_comm_id, 'COM_RAPPORTS', 'Rapports', 'Rapports', NULL, '#', 'fas fa-clipboard-check', 40, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'COM_RAPPORTS');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_comm_id, 'COM_ESPACES', 'Espaces', 'Espaces', NULL, '#', 'fas fa-chalkboard-teacher', 50, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'COM_ESPACES');

-- Administration plateforme
INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_admin_id, 'ADM_DASHBOARD', 'Dashboard', 'Dashboard', NULL, '#', 'fas fa-tachometer-alt', 10, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'ADM_DASHBOARD');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_admin_id, 'ADM_PARAMETRAGE', 'Paramétrage', 'Paramétrage', NULL, '#', 'fas fa-cogs', 20, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'ADM_PARAMETRAGE');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_admin_id, 'ADM_SECURITE', 'Sécurité', 'Sécurité', NULL, '#', 'fas fa-shield-alt', 30, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'ADM_SECURITE');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_admin_id, 'ADM_SYSTEME', 'Système', 'Système', NULL, '#', 'fas fa-server', 40, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'ADM_SYSTEME');

INSERT INTO fonctionnalites (id_categorie, code_fonctionnalite, lib_fonctionnalite, label_fonctionnalite, description_fonctionnalite, url_fonctionnalite, icone_fonctionnalite, ordre_fonctionnalite, est_sous_page, page_parente, actif)
SELECT @cat_admin_id, 'ADM_REFERENTIEL', 'Référentiel', 'Référentiel', NULL, '#', 'fas fa-id-card', 50, 0, NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM fonctionnalites WHERE code_fonctionnalite = 'ADM_REFERENTIEL');

-- 2) Attacher les écrans existants aux sous-menus (tes choix)
-- SCOLARITÉ
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'SCOL_INSCRIPTIONS', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 6;  -- Ajouter/Inscrire
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'SCOL_INSCRIPTIONS', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 53; -- Suivi scolarité (gestion_scolarite)

UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'SCOL_EVALUATIONS', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 26; -- Entrer notes
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'SCOL_EVALUATIONS', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 58; -- Notes et évaluations

UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'SCOL_RECLAMATIONS', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 28; -- Toutes réclamations

UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'SCOL_DOSSIERS', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 54; -- Dossiers vérifiés

UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'SCOL_CONSULTATION', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 8;  -- Tous les étudiants
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'SCOL_CONSULTATION', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 14; -- Examen scolarité

-- Étudiant (ENV)
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ETU_CANDIDATURE', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 13; -- Ma candidature
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ETU_RESULTATS', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 25;  -- Bulletin de notes
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ETU_RECLAMATIONS', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 27; -- Soumettre réclamation
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ETU_SUIVI', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 9;       -- Mes rapports

-- Écrans actions rapports: rester accessibles mais pas dans le menu (pas de parent)
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = NULL WHERE id_fonctionnalite IN (10, 11);

-- Commission validation
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_GESTION', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 5;  -- Suivi commission
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_GESTION', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 15; -- Validation commission
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_GESTION', ordre_fonctionnalite = 3 WHERE id_fonctionnalite = 19; -- Date/Heure/Salle

UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_JURY', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 18; -- Composer jury

UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_EVALUATION', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 20; -- Grille évaluation
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_EVALUATION', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 17; -- Évaluer dossiers
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_EVALUATION', ordre_fonctionnalite = 3 WHERE id_fonctionnalite = 57; -- Évaluer dossiers soutenance

UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_RAPPORTS', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 12; -- Approuver rapports
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_RAPPORTS', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 59; -- Archives CR

-- Espaces
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_ESPACES', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 2; -- Mon espace enseignant
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'COM_ESPACES', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 7; -- Mes étudiants

-- Comptes-rendus: garder accessibles mais pas dans le menu (non listés)
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = NULL WHERE id_fonctionnalite IN (21, 22, 23, 24);

-- Administration plateforme
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ADM_DASHBOARD', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 1; -- Vue d’ensemble

-- Paramétrage
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ADM_PARAMETRAGE', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 30; -- Configuration
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ADM_PARAMETRAGE', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 4;  -- Gestion administrative (dashboard secrétaire)

-- Sécurité
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ADM_SECURITE', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 50; -- Utilisateurs
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ADM_SECURITE', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 51; -- Journal audit

-- Système
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ADM_SYSTEME', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 52; -- Backup
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ADM_SYSTEME', ordre_fonctionnalite = 2 WHERE id_fonctionnalite = 55; -- Historique

-- Référentiel
UPDATE fonctionnalites SET est_sous_page = 1, page_parente = 'ADM_REFERENTIEL', ordre_fonctionnalite = 1 WHERE id_fonctionnalite = 29; -- Personnel

-- Paramètres généraux (les items internes) : garder accessibles mais pas dans le menu principal
-- (Ils restent atteignables depuis `?page=parametres_generaux` ou via l'espace Paramétrage)
UPDATE fonctionnalites
SET est_sous_page = 1, page_parente = NULL
WHERE id_fonctionnalite BETWEEN 31 AND 49;

-- 3) IMPORTANT: permissions sur les sous-menus "parents"
-- Sans permission, `getFonctionnalitesForGroupeAndCategorie()` ne retourne pas le parent,
-- et l'UI crée alors un parent "virtuel" dont le libellé est le code (SCOL_..., ADM_...).
-- Ici on attribue automatiquement le droit "voir" au parent pour tout groupe qui voit au moins
-- un enfant de ce parent.
INSERT INTO permissions (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer)
SELECT DISTINCT p.id_GU, parent.id_fonctionnalite, 1, 0, 0, 0
FROM permissions p
INNER JOIN fonctionnalites child ON child.id_fonctionnalite = p.id_fonctionnalite
INNER JOIN fonctionnalites parent ON parent.code_fonctionnalite = child.page_parente
WHERE p.peut_voir = 1
  AND child.page_parente IS NOT NULL
  AND parent.code_fonctionnalite IN (
    'SCOL_INSCRIPTIONS','SCOL_EVALUATIONS','SCOL_RECLAMATIONS','SCOL_DOSSIERS','SCOL_CONSULTATION',
    'ETU_CANDIDATURE','ETU_RESULTATS','ETU_RECLAMATIONS','ETU_SUIVI',
    'COM_GESTION','COM_JURY','COM_EVALUATION','COM_RAPPORTS','COM_ESPACES',
    'ADM_DASHBOARD','ADM_PARAMETRAGE','ADM_SECURITE','ADM_SYSTEME','ADM_REFERENTIEL'
  )
ON DUPLICATE KEY UPDATE
  peut_voir = GREATEST(peut_voir, VALUES(peut_voir));

COMMIT;

