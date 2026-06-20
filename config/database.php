<?php
/** GuardReport — Database | File: config/database.php */
class Database {
    private static ?PDO $connection = null;

    public static function getInstance(): PDO { return self::getConnection(); }

    public static function getConnection(): PDO {
        if (self::$connection === null) {
            // ── Adjust to match YOUR local MySQL/MariaDB setup ──
            // Default XAMPP port is 3306. Only use 3308 if you intentionally
            // changed your MySQL port (e.g. to run alongside another MySQL instance).
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3308';
            $db   = $_ENV['DB_NAME'] ?? 'guardreport_db';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            try {
                self::$connection = new PDO(
                    "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
                    $user,
                    $pass
                );
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                // NOTE: we deliberately do NOT disable emulated prepares here.
                // (UserModel queries are also written to use unique placeholders,
                // so this works correctly either way — but emulated mode is the
                // safest default for shared hosting / older MySQL versions.)
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
            }
        }
        return self::$connection;
    }
}