<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TrainingSession extends Model
{
    protected string $table      = 'training_session';
    protected string $primaryKey = 'session_id';

    /**
     * Fetch all active sessions joined with category, trainer, and creator details.
     */
    public function getAllActive(): array
    {
        $sql = "
            SELECT
                ts.session_id,
                ts.location,
                ts.session_date,
                ts.session_time,
                ts.check_in,
                ts.check_out,
                ts.total_hours,
                ts.additional_details,
                ts.is_active,
                ts.created_at,
                ts.updated_at,
                tc.category_id,
                tc.category_name,
                CONCAT(tr.first_name, ' ', tr.last_name) AS trainer_name,
                tr.user_id AS trainer_id,
                CONCAT(ud.first_name, ' ', ud.last_name) AS created_by_name,
                ud.user_id AS created_by_id
            FROM {$this->table} ts
            LEFT JOIN training_category  tc ON tc.category_id = ts.category_id
            LEFT JOIN user_details       tr ON tr.user_id     = ts.trainer_id
            LEFT JOIN user_details       ud ON ud.user_id     = ts.created_by
            WHERE ts.is_active = 1
              AND (ts.is_delete IS NULL OR ts.is_delete = 0)
            ORDER BY ts.session_date DESC, ts.session_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find a single session by primary key.
     */
    public function find($id): ?array
    {
        $sql = "
            SELECT
                ts.session_id,
                ts.location,
                ts.session_date,
                ts.session_time,
                ts.check_in,
                ts.check_out,
                ts.total_hours,
                ts.additional_details,
                ts.is_active,
                ts.created_at,
                ts.updated_at,
                tc.category_id,
                tc.category_name,
                CONCAT(tr.first_name, ' ', tr.last_name) AS trainer_name,
                tr.user_id AS trainer_id,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS created_by_name,
                ud.user_id AS created_by_id
            FROM {$this->table} ts
            LEFT JOIN training_category  tc ON tc.category_id = ts.category_id
            LEFT JOIN user_details       tr ON tr.user_id     = ts.trainer_id
            LEFT JOIN user_details       ud ON ud.user_id     = ts.created_by
            WHERE ts.session_id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Soft delete a session by setting is_delete = 1.
     */
    public function softDelete(int $id): bool
    {
        $sql  = "UPDATE {$this->table} SET is_delete = 1, updated_at = :updated_at WHERE session_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update an existing training session.
     */
    public function updateSession(int $id, array $data): bool
    {
        $allowedFields = [
            'category_id',
            'trainer_id',
            'location',
            'session_date',
            'session_time',
            'check_in',
            'check_out',
            'total_hours',
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
