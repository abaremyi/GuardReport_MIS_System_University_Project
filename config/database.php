<?php
/** GuardReport — Database | File: config/database.php */
class Database {
    private static ?PDO $connection = null;

    public static function getInstance(): PDO { return self::getConnection(); }

    public static function getConnection(): PDO {
        if (self::$connection === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $db   = $_ENV['DB_NAME'] ?? 'guardreport_db';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';
            try {
                self::$connection = new PDO(
                    "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
                    $user, $pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                     PDO::ATTR_EMULATE_PREPARES => false]
                );
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['success' => false, 'message' => 'Database connection failed']));
            }
        }
        return self::$connection;
    }
}