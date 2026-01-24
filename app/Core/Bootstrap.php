<?php

namespace CheckMaster\Core;

final class Bootstrap
{
    public static function init(): void
    {
        // Ne jamais afficher les erreurs en production (évite fuites de chemins/SQL/etc.)
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL);

        // Journaliser les erreurs PHP
        ini_set('log_errors', '1');
        $logPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'php-error.log';
        ini_set('error_log', $logPath);
    }
}

