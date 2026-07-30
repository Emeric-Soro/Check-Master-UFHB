<?php

declare(strict_types=1);

namespace CheckMaster\Core;

/**
 * Configuration centralisée de l'application.
 * Lit les valeurs depuis app/config/app.php.
 * AUCUN fichier .env — tout est en PHP pur.
 */
final class AppConfig
{
    private static ?array $cache = null;
    private static string $projectRoot = '';

    /**
     * Initialiser le chemin racine du projet.
     */
    public static function boot(string $projectRoot): void
    {
        self::$projectRoot = $projectRoot;
        self::$cache = null;
    }

    /**
     * Lire une valeur de configuration.
     *
     * @param string $key Notation pointée (ex: 'db.host')
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::all();
        $parts = explode('.', $key);
        $value = $config;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Récupérer toute la configuration.
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $configFile = self::$projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
        $fileConfig = [];
        if (is_file($configFile)) {
            $fileConfig = require $configFile;
            if (!is_array($fileConfig)) {
                $fileConfig = [];
            }
        }

        self::$cache = $fileConfig;
        return self::$cache;
    }

    /**
     * Vider le cache (utile en tests).
     */
    public static function reset(): void
    {
        self::$cache = null;
    }

    // ─── Raccourcis typés ───────────────────────────────────

    public static function dbHost(): string
    {
        return (string) self::get('db.host', 'localhost');
    }

    public static function dbName(): string
    {
        return (string) self::get('db.name', 'checkmaster');
    }

    public static function dbUser(): string
    {
        return (string) self::get('db.user', 'root');
    }

    public static function dbPass(): string
    {
        return (string) self::get('db.pass', '');
    }

    public static function dbCharset(): string
    {
        return (string) self::get('db.charset', 'utf8');
    }

    public static function dbTimezone(): string
    {
        return (string) self::get('db.timezone', '+00:00');
    }

    public static function appUrl(): string
    {
        return rtrim((string) self::get('app.url', ''), '/');
    }

    public static function appBasePath(): string
    {
        return (string) self::get('app.base_path', '/');
    }

    public static function smtpConfig(): array
    {
        return [
            'host'       => (string) self::get('smtp.host', 'smtp.gmail.com'),
            'port'       => (int) self::get('smtp.port', 587),
            'encryption' => (string) self::get('smtp.encryption', 'tls'),
            'username'   => (string) self::get('smtp.username', ''),
            'password'   => (string) self::get('smtp.password', ''),
            'from_email' => (string) self::get('smtp.from_email', ''),
            'from_name'  => (string) self::get('smtp.from_name', 'CheckMaster'),
        ];
    }

    public static function emailDelivery(): array
    {
        return [
            'redirect_all'   => (bool) self::get('email.redirect_all', false),
            'redirect_to'    => (string) self::get('email.redirect_to', ''),
            'subject_prefix' => (string) self::get('email.subject_prefix', ''),
        ];
    }

    // ─── Chemins centralisés ───────────────────────────────

    public static function projectRoot(): string
    {
        if (self::$projectRoot !== '') {
            return self::$projectRoot;
        }
        return dirname(__DIR__, 2);
    }

    public static function storagePath(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'storage';
    }

    public static function uploadPath(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'uploads';
    }

    public static function logPath(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'logs';
    }

    public static function viewPath(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'views';
    }

    public static function componentPath(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'components';
    }

    public static function publicPath(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'public';
    }

    public static function imagePath(): string
    {
        return self::publicPath() . DIRECTORY_SEPARATOR . 'image';
    }

    public static function logoPath(): string
    {
        return self::imagePath() . DIRECTORY_SEPARATOR . 'logo_cm_sbg.png';
    }

    public static function storageDocumentsPath(): string
    {
        return self::storagePath() . DIRECTORY_SEPARATOR . 'documents';
    }
}
