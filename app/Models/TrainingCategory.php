<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TrainingCategory extends Model
{
    protected string $table      = 'training_category';
    protected string $primaryKey = 'category_id';

    /**
     * Fetch all active categories joined with creator's user details.
     */
    public function getAllActive(): array
    {
        $sql = "
                SELECT
                    tc.category_id,
                    tc.category_name,
                    tc.additional_details,
                    tc.is_active,
                    tc.created_at,
                    tc.updated_at,
                    ud.user_id AS created_by_id,
                    CONCAT(ud.first_name, ' ', ud.last_name) AS created_by_name
                FROM {$this->table} tc
                LEFT JOIN user_details ud ON ud.user_id = tc.created_by
                WHERE tc.is_delete = 0
                ORDER BY tc.category_name ASC
            ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find a single category by primary key.
     */
    public function find($id): ?array
    {
        $sql = "
            SELECT
                tc.category_id,
                tc.category_name,
                tc.additional_details,
                tc.is_active,
                tc.created_at,
                tc.updated_at,
                ud.user_id AS created_by_id,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS created_by_name
            FROM {$this->table} tc
            LEFT JOIN user_details ud ON ud.user_id = tc.created_by
            WHERE tc.category_id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Soft delete a category by setting is_delete = 1
     */
    public function softDelete(int $id): bool
    {
        $sql  = "UPDATE {$this->table} SET is_delete = 1, updated_at = :updated_at WHERE category_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update an existing training category.
     */
    public function updateCategory(int $id, array $data): bool
    {
        $allowedFields = [
            'category_name',
            'additional_details',
            'is_active',
            'updated_at',
            'updated_by'
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {

            // enforce whitelist
            if (!in_array($key, $allowedFields, true)) {
                continue;
            }

            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "
                UPDATE {$this->table}
                SET " . implode(', ', $fields) . "
                WHERE {$this->primaryKey} = :id
            ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
