-- =====================================================
-- Migration: formaliser le mapping route -> action CRUD
-- =====================================================

CREATE TABLE IF NOT EXISTS route_actions (
    id_route_action INT AUTO_INCREMENT PRIMARY KEY,
    route_pattern VARCHAR(255) NOT NULL,
    http_method ENUM('GET','POST','*') NOT NULL DEFAULT '*',
    action_crud ENUM('voir','creer','modifier','supprimer') NOT NULL,
    description TEXT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_route_pattern (route_pattern),
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Quelques règles de base (à enrichir progressivement)
INSERT INTO route_actions (route_pattern, http_method, action_crud, description, actif) VALUES
  ('page=parametres_generaux&action=gestion_attribution', 'GET',  'voir',     'Écran gestion des permissions', 1),
  ('page=parametres_generaux&action=gestion_attribution', 'POST', 'modifier', 'Enregistrer permissions', 1),
  ('page=gestion_rapports&action=creer_rapport',          'GET',  'creer',    'Formulaire création rapport', 1),
  ('page=gestion_rapports&action=creer_rapport',          'POST', 'creer',    'Création rapport', 1),
  ('page=sauvegarde_restauration',                        'POST', 'modifier', 'Backup/restore (actions)', 1)
ON DUPLICATE KEY UPDATE
  action_crud = VALUES(action_crud),
  description = VALUES(description),
  actif = VALUES(actif);

