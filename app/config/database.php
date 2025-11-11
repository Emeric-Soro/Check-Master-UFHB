<?php
class Database {
    private static $host;
    private static $db;
    private static $user;
    private static $pass;
    private static $charset = 'utf8';

    private static function loadConfig() {
        // Load configuration from environment variables with fallbacks for development
        self::$host = getenv('DB_HOST') ?: 'db';
        self::$db = getenv('DB_NAME') ?: 'soutenance_manager';
        self::$user = getenv('DB_USER') ?: 'root';
        self::$pass = getenv('DB_PASSWORD') ?: 'password';
    }

    public static function getConnection() {
        try {
            if (!self::$host) {
                self::loadConfig();
            }
            
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
            $pdo = new PDO($dsn, self::$user, self::$pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
}