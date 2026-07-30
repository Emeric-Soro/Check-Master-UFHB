<?php

namespace CheckMaster\Security;

use PDO;

/**
 * Rate limiter DB-only (sans Redis).
 * - Agrège par (action, ip, identifier)
 * - Fenêtre glissante simple
 * - Fail-open si la table n'existe pas encore
 */
final class DbRateLimiter
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Vérifie si l'action est autorisée (sans incrémenter).
     */
    public function isAllowed(string $action, string $ip, string $identifier): bool
    {
        try {
            $sql = "SELECT blocked_until
                    FROM auth_rate_limits
                    WHERE action = :action AND ip = :ip AND identifier = :identifier
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':action' => $action,
                ':ip' => $ip,
                ':identifier' => $identifier,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row) || empty($row['blocked_until'])) {
                return true;
            }
            return (strtotime((string) $row['blocked_until']) <= time());
        } catch (\Throwable $e) {
            // Fail-open (ne pas casser prod si table absente)
            return true;
        }
    }

    /**
     * Enregistre une tentative (échec typiquement) et applique un blocage si seuil dépassé.
     */
    public function hit(string $action, string $ip, string $identifier, int $maxAttempts, int $windowSeconds, int $blockSeconds): void
    {
        $now = time();
        $windowStart = $now - $windowSeconds;

        try {
            // Transaction avec SELECT ... FOR UPDATE pour éviter la race condition TOCTOU
            $this->pdo->beginTransaction();

            // Lire état actuel avec verrouillage de ligne
            $sql = "SELECT attempts, window_start, blocked_until
                    FROM auth_rate_limits
                    WHERE action = :action AND ip = :ip AND identifier = :identifier
                    FOR UPDATE";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':action' => $action,
                ':ip' => $ip,
                ':identifier' => $identifier,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $attempts = 0;
            $existingWindowStart = $now;
            if (is_array($row)) {
                $attempts = (int)($row['attempts'] ?? 0);
                $existingWindowStart = isset($row['window_start']) ? (int) strtotime((string) $row['window_start']) : $now;
                if ($existingWindowStart === 0) {
                    $existingWindowStart = $now;
                }
            }

            // Reset fenêtre si expirée
            if ($existingWindowStart < $windowStart) {
                $attempts = 0;
                $existingWindowStart = $now;
            }

            $attempts++;
            $blockedUntil = null;
            if ($attempts >= $maxAttempts) {
                $blockedUntil = date('Y-m-d H:i:s', $now + $blockSeconds);
            }

            $upsert = "INSERT INTO auth_rate_limits (action, ip, identifier, attempts, window_start, last_attempt, blocked_until)
                       VALUES (:action, :ip, :identifier, :attempts, :window_start, :last_attempt, :blocked_until)
                       ON DUPLICATE KEY UPDATE
                          attempts = VALUES(attempts),
                          window_start = VALUES(window_start),
                          last_attempt = VALUES(last_attempt),
                          blocked_until = VALUES(blocked_until)";
            $stmt2 = $this->pdo->prepare($upsert);
            $stmt2->execute([
                ':action' => $action,
                ':ip' => $ip,
                ':identifier' => $identifier,
                ':attempts' => $attempts,
                ':window_start' => date('Y-m-d H:i:s', $existingWindowStart),
                ':last_attempt' => date('Y-m-d H:i:s', $now),
                ':blocked_until' => $blockedUntil,
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            // Annuler la transaction en cas d'erreur
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Fail-open
        }
    }

    /**
     * Réinitialise les tentatives (succès login typiquement).
     */
    public function reset(string $action, string $ip, string $identifier): void
    {
        try {
            $sql = "DELETE FROM auth_rate_limits WHERE action = :action AND ip = :ip AND identifier = :identifier";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':action' => $action,
                ':ip' => $ip,
                ':identifier' => $identifier,
            ]);
        } catch (\Throwable $e) {
            // Fail-open
        }
    }
}

