-- ============================================================
-- Lier type_utilisateur -> groupe_utilisateur (filtrage UI)
-- ============================================================
-- Objectif:
--   Ajouter une colonne optionnelle `id_type_utilisateur` sur `groupe_utilisateur`
--   pour pouvoir filtrer les groupes par type d’utilisateur dans l’écran
--   "Gestion des habilitations" (gestion_attribution).
--
-- Note:
--   Le code PHP supporte aussi un mode "fallback" sans cette colonne
--   (groupes déduits depuis la table `utilisateur`), mais ce script
--   rend la liaison explicite et administrable.

START TRANSACTION;

ALTER TABLE groupe_utilisateur
  ADD COLUMN id_type_utilisateur INT NULL AFTER lib_GU;

ALTER TABLE groupe_utilisateur
  ADD CONSTRAINT fk_groupe_utilisateur_type
  FOREIGN KEY (id_type_utilisateur)
  REFERENCES type_utilisateur(id_type_utilisateur)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

CREATE INDEX idx_groupe_utilisateur_type ON groupe_utilisateur (id_type_utilisateur);

COMMIT;

