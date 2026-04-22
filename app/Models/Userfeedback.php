<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class UserFeedback extends Model
{
    protected string $table      = 'user_feedback';
    protected string $primaryKey = 'review_id';

    /**
     * Fetch all active, non-deleted feedback rows joined with user & training info.
     */
    public function getAllActive(): array
    {
        $sql = "
            SELECT
                uf.review_id,
                uf.rating,
                uf.comment,
                uf.is_active,
                uf.created_at,
                uf.updated_at,
                uf.training_id,
                uf.user_id,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS user_name
            FROM {$this->table} uf
            LEFT JOIN user_details ud ON ud.user_id = uf.user_id
            WHERE uf.is_delete = 0
            ORDER BY uf.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find a single feedback row by primary key.
     */
    public function find($id): ?array
    {
        $sql = "
            SELECT
                uf.review_id,
                uf.rating,
                uf.comment,
                uf.is_active,
                uf.created_at,
                uf.updated_at,
                uf.training_id,
                uf.user_id,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS user_name
            FROM {$this->table} uf
            LEFT JOIN user_details ud ON ud.user_id = uf.user_id
            WHERE uf.review_id = :id
              AND uf.is_delete = 0
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Fetch all feedback for a specific training.
     */
    public function getByTraining(int $trainingId): array
    {
        $sql = "
            SELECT
                uf.review_id,
                uf.rating,
                uf.comment,
                uf.is_active,
                uf.created_at,
                uf.updated_at,
                uf.user_id,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS user_name
            FROM {$this->table} uf
            LEFT JOIN user_details ud ON ud.user_id = uf.user_id
            WHERE uf.training_id = :training_id
              AND uf.is_delete   = 0
            ORDER BY uf.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':training_id' => $trainingId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Soft delete a feedback row by setting is_delete = 1.
     */
    public function softDelete(int $id): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET is_delete  = 1,
                updated_at = :updated_at
            WHERE review_id = :id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update an existing feedback row.
     */
    public function updateFeedback(int $id, array $data): bool
    {
        $allowedFields = [
            'rating',
            'comment',
            'is_active',
            'updated_at',
            'updated_by',
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue;
            }

            $fields[]        = "$key = :$key";
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
