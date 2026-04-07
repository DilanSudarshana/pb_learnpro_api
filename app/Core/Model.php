<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a single record by primary key
     */
    public function find(int|string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a record by a specific column value
     */
    public function findBy(string $column, mixed $value): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `{$column}` = ? LIMIT 1"
        );
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all records
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM `{$this->table}`");
        return $stmt->fetchAll();
    }

    /**
     * Create a new record
     */
    public function create(array $data): int
    {
        $columns     = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}` (`{$columns}`) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a record by primary key
     */
    public function update(int|string $id, array $data): bool
    {
        $setParts = implode(', ', array_map(fn($col) => "`{$col}` = ?", array_keys($data)));
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET {$setParts} WHERE `{$this->primaryKey}` = ?"
        );
        return $stmt->execute([...array_values($data), $id]);
    }

    /**
     * Delete a record by primary key
     */
    public function delete(int|string $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?"
        );
        return $stmt->execute([$id]);
    }
}
