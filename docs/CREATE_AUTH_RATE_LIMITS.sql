-- =====================================================
-- Rate limiting (DB only) pour login & reset password
-- =====================================================

CREATE TABLE IF NOT EXISTS auth_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(32) NOT NULL,           -- 'login' | 'reset'
    ip VARCHAR(45) NOT NULL,               -- IPv4/IPv6
    identifier VARCHAR(191) NOT NULL,      -- ex: email (normalisé) ou '-'
    attempts INT NOT NULL DEFAULT 0,
    window_start DATETIME NOT NULL,
    last_attempt DATETIME NOT NULL,
    blocked_until DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_action_ip_identifier (action, ip, identifier),
    INDEX idx_blocked_until (blocked_until),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

