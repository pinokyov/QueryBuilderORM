<?php

namespace App\Orm;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    private function __construct() {}
    private function __clone() {}

    public static function setConfig(array $config): void
    {
        self::$config = array_merge([
            'host' => DB_HOST,
            'database' => DB_DATABASE,
            'username' => DB_USERNAME,
            'password' => DB_PASSWORD,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ], $config);
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $config = self::$config;
                $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options'] ?? []
                );
            } catch (PDOException $e) {
                throw new \RuntimeException("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    public static function query(string $query, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($query);
        
        // Convert DateTime objects to string for database
        foreach ($params as $key => $value) {
            if ($value instanceof \DateTime) {
                $params[$key] = $value->format('Y-m-d H:i:s');
            }
        }
        
        $stmt->execute($params);
        return $stmt;
    }

    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }
}
