<?php

if (!class_exists('Database', false)) {
class Database {
    // Configuration de la base de données (sans Docker)
    private static $host = 'localhost';
    private static $db = 'ufrmi1802974_2q2mpf';
    private static $user = 'root';
    private static $pass = '';
    private static $charset = 'utf8';

    public static function getConfig(): array
    {
        return [
            'host' => self::$host,
            'db' => self::$db,
            'user' => self::$user,
            'pass' => self::$pass,
            'charset' => self::$charset,
        ];
    }

    public static function getConnection() {
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
            $pdo = new PDO($dsn, self::$user, self::$pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            // Ne pas exposer les détails techniques (DSN, creds, etc.)
            error_log("Erreur de connexion DB: " . $e->getMessage());
            die("Erreur de connexion à la base de données.");
        }
    }
}
}
