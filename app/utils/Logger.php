<?php
declare(strict_types=1);

final class Logger
{
    private static string $logDir = '';
    private static bool $enabled = true;

    public static function init(string $logDir = ''): void
    {
        if ($logDir === '') {
            $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs';
        }
        self::$logDir = rtrim($logDir, '/\\');
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0775, true);
        }
    }

    public static function enable(): void { self::$enabled = true; }
    public static function disable(): void { self::$enabled = false; }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function sql(string $query, array $params = [], ?float $duration = null): void
    {
        $ctx = ['query' => $query, 'params' => $params];
        if ($duration !== null) {
            $ctx['duration_ms'] = round($duration * 1000, 2);
        }
        self::log('SQL', '', $ctx);
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }

        if (self::$logDir === '') {
            self::init();
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2] ?? $trace[1] ?? [];
        $file = basename((string) ($caller['file'] ?? 'unknown'));
        $line = (int) ($caller['line'] ?? 0);

        $timestamp = date('Y-m-d H:i:s.v');
        $ctxJson = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $logLine = "[{$timestamp}] {$level}.{$file}:{$line} {$message}{$ctxJson}" . PHP_EOL;

        $logFile = self::$logDir . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';
        try {
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            error_log("[Logger] Cannot write to {$logFile}: " . $e->getMessage());
        }
    }
}
