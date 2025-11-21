<?php
// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Initialize Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

class Database {
    private static $host;
    private static $db;
    private static $user;
    private static $pass;
    private static $charset = 'utf8';

    private static function loadConfig() {
        // Load configuration from environment variables
        self::$host = $_ENV['DB_HOST'];
        self::$db = $_ENV['DB_NAME'];
        self::$user = $_ENV['DB_USER'];
        self::$pass = $_ENV['DB_PASSWORD'];
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