<?php

/**
 * Configuration de la base de données.
 * Les valeurs sont lues depuis AppConfig (fichier .env).
 * La classe Database est maintenue pour compatibilité ascendante.
 */

if (!class_exists('Database', false)) {
    class Database
    {
        private static ?PDO $instance = null;

        public static function getConfig(): array
        {
            return [
                'host'    => \CheckMaster\Core\AppConfig::dbHost(),
                'db'      => \CheckMaster\Core\AppConfig::dbName(),
                'user'    => \CheckMaster\Core\AppConfig::dbUser(),
                'pass'    => \CheckMaster\Core\AppConfig::dbPass(),
                'charset' => \CheckMaster\Core\AppConfig::dbCharset(),
            ];
        }

        public static function getConnection(): PDO
        {
            if (self::$instance !== null) {
                return self::$instance;
            }

            try {
                $host    = \CheckMaster\Core\AppConfig::dbHost();
                $db      = \CheckMaster\Core\AppConfig::dbName();
                $user    = \CheckMaster\Core\AppConfig::dbUser();
                $pass    = \CheckMaster\Core\AppConfig::dbPass();
                $charset = \CheckMaster\Core\AppConfig::dbCharset();
                $tz      = \CheckMaster\Core\AppConfig::dbTimezone();

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $pdo = new PDO($dsn, $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec("SET time_zone = '$tz'");

                self::$instance = $pdo;
                return $pdo;
            } catch (PDOException $e) {
                error_log("Erreur de connexion DB: " . $e->getMessage());
                throw new \RuntimeException("Erreur de connexion à la base de données.", 0, $e);
            }
        }

        /**
         * Réinitialiser la connexion (utile en tests).
         */
        public static function reset(): void
        {
            self::$instance = null;
        }
    }
}
