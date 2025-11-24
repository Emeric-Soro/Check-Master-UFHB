<?php
// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

class Database {
    private static $host;
    private static $db;
    private static $user;
    private static $pass;
    private static $charset;

    private static function loadConfig() {
        if (self::$host === null) {
            self::$host = $_ENV['DB_HOST'] ?? 'localhost';
            self::$db = $_ENV['DB_NAME'] ?? 'soutenance_manager';
            self::$user = $_ENV['DB_USER'] ?? 'root';
            self::$pass = $_ENV['DB_PASS'] ?? '';
            self::$charset = $_ENV['DB_CHARSET'] ?? 'utf8';
        }
    }

    public static function getConnection() {
        self::loadConfig();
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
            $pdo = new PDO($dsn, self::$user, self::$pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
}