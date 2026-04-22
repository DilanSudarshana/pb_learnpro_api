<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TrainingAllocation extends Model
{
    protected string $table      = 'training_allocations';
    protected string $primaryKey = 'training_allocation_id';

    /**
     * Fetch all active (non-deleted) allocations with related details.
     */
    public function getAllActive(): array
    {
        $sql = "
            SELECT
                ta.training_allocation_id,
                ta.status,
                ta.is_active,
                ta.created_at,
                ta.updated_at,

                -- Trainee
                trainee.user_id                                          AS trainee_id,
                TRIM(CONCAT(trainee.first_name, ' ', trainee.last_name)) AS trainee_name,

                -- Session (date & time come from training_session)
                ts.session_id,
                ts.session_date,
                ts.session_time,
                ts.location,

                -- Session name = category name (training_session has no own name)
                tc.category_id,
                tc.category_name                                         AS session_name,

                -- Created by
                creator.user_id                                          AS created_by_id,
                TRIM(CONCAT(creator.first_name, ' ', creator.last_name)) AS created_by_name

            FROM {$this->table} ta
            LEFT JOIN user_details     trainee ON trainee.user_id = ta.trainee_id
            LEFT JOIN training_session ts      ON ts.session_id   = ta.session_id
            LEFT JOIN training_category tc     ON tc.category_id  = ts.category_id
            LEFT JOIN user_details     creator ON creator.user_id = ta.created_by
            WHERE ta.is_delete = 0
            ORDER BY ts.session_date DESC, ts.session_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find a single allocation by primary key (excludes soft-deleted).
     */
    public function find($id): ?array
    {
        $sql = "
            SELECT
                ta.training_allocation_id,
                ta.status,
                ta.is_active,
                ta.created_at,
                ta.updated_at,

                -- Trainee
                trainee.user_id                                          AS trainee_id,
                TRIM(CONCAT(trainee.first_name, ' ', trainee.last_name)) AS trainee_name,

                -- Session (date & time come from training_session)
                ts.session_id,
                ts.session_date,
                ts.session_time,
                ts.location,

                -- Session name = category name (training_session has no own name)
                tc.category_id,
                tc.category_name                                         AS session_name,

                -- Created by
                creator.user_id                                          AS created_by_id,
                TRIM(CONCAT(creator.first_name, ' ', creator.last_name)) AS created_by_name

            FROM {$this->table} ta
            LEFT JOIN user_details     trainee ON trainee.user_id = ta.trainee_id
            LEFT JOIN training_session ts      ON ts.session_id   = ta.session_id
            LEFT JOIN training_category tc     ON tc.category_id  = ts.category_id
            LEFT JOIN user_details     creator ON creator.user_id = ta.created_by
            WHERE ta.training_allocation_id = :id
              AND ta.is_delete   = 0
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Soft-delete an allocation by setting is_delete = 1.
     */
    public function softDelete(int $id): bool
    {
        $sql  = "UPDATE {$this->table}
                 SET is_delete = 1, updated_at = :updated_at
                 WHERE training_allocation_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update an existing training allocation.
     */
    public function updateAllocation(int $id, array $data): bool
    {
        $allowedFields = [
            'trainee_id',
            'session_id',
            'status',
            'is_active',
            'updated_at',
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
