<?php

declare(strict_types=1);

namespace CheckMaster\Core;

final class Bootstrap
{
    private const APP_TIMEZONE = 'Africa/Abidjan';

    public static function init(): void
    {
        // Ne jamais afficher les erreurs en production
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL);

        // Forcer une timezone unique pour tous les traitements PHP.
        date_default_timezone_set(self::APP_TIMEZONE);
        ini_set('date.timezone', self::APP_TIMEZONE);

        // Initialiser la configuration centralisée
        $projectRoot = dirname(__DIR__, 2);
        AppConfig::boot($projectRoot);

        // Configurer le logging depuis la config
        self::configureLogging();
    }

    /**
     * Configurer le répertoire et le niveau de logs.
     */
    private static function configureLogging(): void
    {
        $logDir = AppConfig::logPath();
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0750, true);
        }

        $logFile = $logDir . DIRECTORY_SEPARATOR . 'php-error.log';
        ini_set('log_errors', '1');
        ini_set('error_log', $logFile);

        // En debug, ne pas exclure les dépréciations
        if ((bool) AppConfig::get('app.debug', false)) {
            error_reporting(E_ALL);
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED);
        }
    }
}
