<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class LearningMaterial extends Model
{
    protected string $table      = 'learning_materials';
    protected string $primaryKey = 'material_id';

    /**
     * Fetch all active, non-deleted materials joined with uploader & session info.
     */
    public function getAllActive(): array
    {
        $sql = "
            SELECT
                lm.material_id,
                lm.training_id,
                lm.material_type,
                lm.file_name,
                lm.file_path,
                lm.additional_details,
                lm.is_active,
                lm.created_at,
                lm.updated_at,
                lm.uploaded_by,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS uploaded_by_name,
                ts.session_date
            FROM {$this->table} lm
            LEFT JOIN user_details   ud ON ud.user_id    = lm.uploaded_by
            LEFT JOIN training_session ts ON ts.session_id = lm.training_id
            WHERE lm.is_delete = 0
            ORDER BY lm.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find a single material by primary key (excludes soft-deleted rows).
     */
    public function find($id): ?array
    {
        $sql = "
            SELECT
                lm.material_id,
                lm.training_id,
                lm.material_type,
                lm.file_name,
                lm.file_path,
                lm.additional_details,
                lm.is_active,
                lm.created_at,
                lm.updated_at,
                lm.uploaded_by,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS uploaded_by_name
            FROM {$this->table} lm
            LEFT JOIN user_details    ud ON ud.user_id    = lm.uploaded_by
            LEFT JOIN training_session ts ON ts.session_id = lm.training_id
            WHERE lm.material_id = :id
              AND lm.is_delete   = 0
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Fetch all materials for a specific training session.
     */
    public function getByTraining(int $trainingId): array
    {
        $sql = "
            SELECT
                lm.material_id,
                lm.training_id,
                lm.material_type,
                lm.file_name,
                lm.file_path,
                lm.additional_details,
                lm.is_active,
                lm.created_at,
                lm.updated_at,
                lm.uploaded_by,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS uploaded_by_name
            FROM {$this->table} lm
            LEFT JOIN user_details ud ON ud.user_id = lm.uploaded_by
            WHERE lm.training_id = :training_id
              AND lm.is_delete   = 0
            ORDER BY lm.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':training_id' => $trainingId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Soft delete by setting is_delete = 1.
     */
    public function softDelete(int $id): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET is_delete  = 1,
                updated_at = :updated_at
            WHERE material_id = :id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update allowed fields of an existing material row.
     */
    public function updateMaterial(int $id, array $data): bool
    {
        $allowedFields = [
            'material_type',
            'file_name',
            'file_path',
            'additional_details',
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
