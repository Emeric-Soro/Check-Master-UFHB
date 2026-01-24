-- =====================================================
-- Stockage de configuration applicative (DB only)
-- NOTE: ceci ne chiffre pas. Protéger l'accès DB et comptes.
-- =====================================================

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  is_sensitive TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Exemple (à adapter) : configuration SMTP
-- INSERT INTO app_settings(setting_key, setting_value, is_sensitive) VALUES
-- ('smtp_host', 'smtp.gmail.com', 0),
-- ('smtp_port', '587', 0),
-- ('smtp_username', 'checkmaster.ci@gmail.com', 0),
-- ('smtp_password', 'CHANGE_ME', 1),
-- ('smtp_from_email', 'checkmaster.ci@gmail.com', 0),
-- ('smtp_from_name', 'Check Master', 0)
-- ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), is_sensitive=VALUES(is_sensitive);

