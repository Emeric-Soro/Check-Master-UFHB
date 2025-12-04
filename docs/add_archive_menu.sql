-- SQL script to add "Historique et Archivage" menu entry for System Administrator
-- This adds the menu item to the traitement table and associates it with the appropriate user groups

-- Insert the new menu item into the traitement table
INSERT INTO `traitement` (`lib_traitement`, `label_traitement`, `icone_traitement`, `ordre_traitement`)
VALUES ('admin_historique', 'Historique et Archivage', 'fa-archive', 100);

-- Get the ID of the newly inserted traitement
SET @traitement_id = LAST_INSERT_ID();

-- Associate the menu with System Administrator group (id_GU = 1, assuming this is the admin group)
-- You may need to adjust the id_GU based on your actual database structure
-- Common IDs: 1 = System Admin, 6 = Personnel Admin, etc.

-- First, check which groups should have access (typically System Admin and maybe Personnel Admin)
-- The avoir table links groupe_utilisateur to traitement

-- Add for System Administrator (adjust id_GU as needed)
INSERT INTO `avoir` (`id_GU`, `id_traitement`)
SELECT id_GU, @traitement_id
FROM groupe_utilisateur
WHERE lib_GU IN ('Administrateur Système', 'Personnel administratif')
ON DUPLICATE KEY UPDATE id_GU=id_GU;

-- Note: You may need to also add permissions to the action table if your system uses that
-- For now, we're assuming the basic CRUD actions already exist in the action table

-- To verify the insertion, you can run:
-- SELECT * FROM traitement WHERE lib_traitement = 'admin_historique';
-- SELECT * FROM avoir WHERE id_traitement = @traitement_id;
