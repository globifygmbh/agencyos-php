<?php

declare(strict_types=1);

namespace AgencyOS\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                Config::dbHost(),
                Config::dbPort(),
                Config::dbName()
            );
            try {
                self::$instance = new PDO($dsn, Config::dbUser(), Config::dbPass(), [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['detail' => 'Database connection failed: ' . $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }

    /**
     * Execute a query and return all rows.
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return a single row.
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Execute a write query (INSERT / UPDATE / DELETE) and return affected rows.
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Insert a row and return the generated ID (or empty string for UUID-keyed tables).
     */
    public static function insert(string $table, array $data): string
    {
        $columns = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
        $stmt = self::get()->prepare($sql);
        $stmt->execute(array_values($data));
        return self::get()->lastInsertId() ?: ($data['id'] ?? '');
    }

    /**
     * Update rows matching $where conditions.
     */
    public static function update(string $table, array $data, array $where): int
    {
        $sets   = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $wheres = implode(' AND ', array_map(fn($c) => "`$c` = ?", array_keys($where)));
        $sql = "UPDATE `$table` SET $sets WHERE $wheres";
        $params = array_merge(array_values($data), array_values($where));
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Delete rows matching $where conditions.
     */
    public static function delete(string $table, array $where): int
    {
        $wheres = implode(' AND ', array_map(fn($c) => "`$c` = ?", array_keys($where)));
        $sql = "DELETE FROM `$table` WHERE $wheres";
        $stmt = self::get()->prepare($sql);
        $stmt->execute(array_values($where));
        return $stmt->rowCount();
    }
}
