<?php
/**
 * =====================================================
 * MAX WIFI - Database Connection Class
 * Phase 1: Authentication + RBAC
 * =====================================================
 *
 * HOW TO USE THIS FILE:
 *
 * 1. Require the file:
 *    require_once __DIR__ . '/../includes/db.php';
 *
 * 2. Get the database connection:
 *    $db  = Database::getInstance();
 *    $pdo = $db->getConnection();
 *
 * 3. Example query:
 *    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 *    $stmt->execute([1]);
 *    $user = $stmt->fetch();
 *
 * =====================================================
 */

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';

        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=%s",
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get single database instance (Singleton Pattern)
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Return the PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserializing
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}