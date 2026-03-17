<?php

namespace CheckMaster\Core;

final class Bootstrap
{
    private const APP_TIMEZONE = 'Africa/Abidjan';

    public static function init(): void
    {
        // Ne jamais afficher les erreurs en production (évite fuites de chemins/SQL/etc.)
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL);

        // Forcer une timezone unique pour tous les traitements PHP.
        date_default_timezone_set(self::APP_TIMEZONE);
        ini_set('date.timezone', self::APP_TIMEZONE);

        // Journaliser les erreurs PHP
        ini_set('log_errors', '1');
        $logPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'php-error.log';
        ini_set('error_log', $logPath);
    }
}

