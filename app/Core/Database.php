<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Get singleton PDO instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host   = $_ENV['DB_HOST']   ?? '127.0.0.1';
            $port   = $_ENV['DB_PORT']   ?? '3306';
            $name   = $_ENV['DB_NAME']   ?? 'pb_learnpro_db';
            $user   = $_ENV['DB_USER']   ?? 'root';
            $pass   = $_ENV['DB_PASS']   ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
                exit;
            }
        }

        return self::$instance;
    }

    // Prevent instantiation
    private function __construct() {}
    private function __clone() {}
}
