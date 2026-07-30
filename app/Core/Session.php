<?php

declare(strict_types=1);

namespace CheckMaster\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        session_set_cookie_params([
            'lifetime' => (int) AppConfig::get('session.lifetime', 0),
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly'  => true,
            'samesite'  => (string) AppConfig::get('session.samesite', 'Lax'),
        ]);

        if (@session_start()) {
            return;
        }

        self::configureFallbackSavePath();

        if (@session_start()) {
            return;
        }

        error_log('Session::start failed even after fallback session.save_path configuration.');
    }

    public static function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        session_regenerate_id(true);
    }

    private static function configureFallbackSavePath(): void
    {
        $fallbackPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';

        if (!is_dir($fallbackPath)) {
            @mkdir($fallbackPath, 0700, true);
        }

        clearstatcache(true, $fallbackPath);
        if (!is_dir($fallbackPath) || !is_writable($fallbackPath)) {
            return;
        }

        @session_save_path($fallbackPath);
        @ini_set('session.save_path', $fallbackPath);
    }
}

