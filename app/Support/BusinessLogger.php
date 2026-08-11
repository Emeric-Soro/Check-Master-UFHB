<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Journalisation métier centralisée (Phase 2 / Phase 7).
 *
 * Remplace les error_log() dispersés par un point unique avec contexte :
 * utilisateur, route, action, entité, résultat, IP.
 *
 * Les logs sont écrits dans le répertoire configuré par AppConfig::logPath()
 * sous un fichier business.log, et aussi propagés à error_log().
 */
final class BusinessLogger
{
    private string $logFile;
    private bool $enabled = true;

    public function __construct(?string $logDir = null)
    {
        $logDir = $logDir ?? \CheckMaster\Core\AppConfig::logPath();
        if (!is_dir($logDir) && !@mkdir($logDir, 0750, true) && !is_dir($logDir)) {
            $this->enabled = false;
            return;
        }
        $this->logFile = rtrim($logDir, '/\\') . DIRECTORY_SEPARATOR . 'business.log';
    }

    /**
     * @param array<string,mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $entry = [
            'ts' => date('c'),
            'level' => $level,
            'message' => $message,
            'user' => $_SESSION['id_utilisateur'] ?? null,
            'route' => $_GET['page'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'context' => $context,
        ];

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            $line = json_encode(['ts' => date('c'), 'level' => $level, 'message' => $message]);
        }

        @file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        error_log('[' . strtoupper($level) . '] ' . $message);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }
}
