<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TrainingAttendance extends Model
{
    protected string $table      = 'training_attendance';
    protected string $primaryKey = 'attendance_id';

    /**
     * Get all attendance records with optional filters
     * Filters: training_allocation_id, user_id, status, attendance_date_from, attendance_date_to
     */
    public function getAllWithFilters(array $filters = []): array
    {
        $sql = "
            SELECT
                ta.attendance_id,
                ta.training_allocation_id,
                ta.user_id,
                ta.attendance_date,
                ta.in_time,
                ta.out_time,
                ta.attendance_type,
                ta.status,
                ta.is_marked,
                ta.is_late,
                ta.is_early_leave,
                ta.created_at,
                ta.updated_at,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS user_name,
                TRIM(CONCAT(cbd.first_name, ' ', cbd.last_name)) AS created_by_name,
                TRIM(CONCAT(ubd.first_name, ' ', ubd.last_name)) AS updated_by_name
            FROM {$this->table} ta
            LEFT JOIN user_details ud ON ud.user_id = ta.user_id
            LEFT JOIN user_details cbd ON cbd.user_id = ta.created_by
            LEFT JOIN user_details ubd ON ubd.user_id = ta.updated_by
            WHERE 1=1
        ";

        $params = [];

        // Apply filters
        if (!empty($filters['training_allocation_id'])) {
            $sql .= " AND ta.training_allocation_id = :training_allocation_id";
            $params[':training_allocation_id'] = $filters['training_allocation_id'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND ta.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (isset($filters['status'])) {
            $sql .= " AND ta.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['attendance_date_from'])) {
            $sql .= " AND ta.attendance_date >= :attendance_date_from";
            $params[':attendance_date_from'] = $filters['attendance_date_from'];
        }

        if (!empty($filters['attendance_date_to'])) {
            $sql .= " AND ta.attendance_date <= :attendance_date_to";
            $params[':attendance_date_to'] = $filters['attendance_date_to'];
        }

        $sql .= " ORDER BY ta.attendance_date DESC, ta.user_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find a single attendance record by primary key
     */
    public function find($id): ?array
    {
        $sql = "
            SELECT
                ta.attendance_id,
                ta.training_allocation_id,
                ta.user_id,
                ta.attendance_date,
                ta.in_time,
                ta.out_time,
                ta.attendance_type,
                ta.status,
                ta.is_marked,
                ta.is_late,
                ta.is_early_leave,
                ta.created_at,
                ta.updated_at,
                ta.created_by,
                ta.updated_by,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS user_name,
                TRIM(CONCAT(cbd.first_name, ' ', cbd.last_name)) AS created_by_name,
                TRIM(CONCAT(ubd.first_name, ' ', ubd.last_name)) AS updated_by_name
            FROM {$this->table} ta
            LEFT JOIN user_details ud ON ud.user_id = ta.user_id
            LEFT JOIN user_details cbd ON cbd.user_id = ta.created_by
            LEFT JOIN user_details ubd ON ubd.user_id = ta.updated_by
            WHERE ta.{$this->primaryKey} = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Find attendance record by training allocation, user, and date
     */
    public function findByAllocationUserDate(int $allocationId, int $userId, string $date): ?array
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE training_allocation_id = :allocation_id
              AND user_id = :user_id
              AND attendance_date = :attendance_date
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':allocation_id'   => $allocationId,
            ':user_id'         => $userId,
            ':attendance_date' => $date,
        ]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update an existing attendance record
     */
    public function updateAttendance(int $id, array $data): bool
    {
        $allowedFields = [
            'in_time',
            'out_time',
            'attendance_type',
            'status',
            'is_marked',
            'is_late',
            'is_early_leave',
            'updated_at',
            'updated_by',
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            // Enforce whitelist
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

    /**
     * Delete an attendance record
     */
    public function delete_attendance(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get attendance summary for a training allocation
     * Returns count by status for all users in the allocation
     */
    public function getAttendanceSummary(int $allocationId): array
    {
        $sql = "
            SELECT
                ta.user_id,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS user_name,
                COUNT(*) AS total_days,
                SUM(CASE WHEN ta.status = 1 THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN ta.status = 2 THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN ta.status = 3 THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN ta.status = 4 THEN 1 ELSE 0 END) AS half_day,
                SUM(CASE WHEN ta.status = 5 THEN 1 ELSE 0 END) AS leave,
                SUM(CASE WHEN ta.status = 0 THEN 1 ELSE 0 END) AS pending,
                ROUND(
                    (SUM(CASE WHEN ta.status = 1 THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
                    2
                ) AS attendance_percentage
            FROM {$this->table} ta
            LEFT JOIN user_details ud ON ud.user_id = ta.user_id
            WHERE ta.training_allocation_id = :allocation_id
            GROUP BY ta.user_id, ud.first_name, ud.last_name
            ORDER BY user_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':allocation_id' => $allocationId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get attendance records for a specific user and date range
     */
    public function getUserAttendanceByDateRange(
        int $userId,
        string $dateFrom,
        string $dateTo
    ): array {
        $sql = "
            SELECT
                ta.attendance_id,
                ta.training_allocation_id,
                ta.attendance_date,
                ta.in_time,
                ta.out_time,
                ta.status,
                ta.is_marked,
                ta.is_late,
                ta.is_early_leave,
                ta.created_at,
                ta.updated_at
            FROM {$this->table} ta
            WHERE ta.user_id = :user_id
              AND ta.attendance_date >= :date_from
              AND ta.attendance_date <= :date_to
            ORDER BY ta.attendance_date ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'   => $userId,
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Bulk create attendance records for multiple users
     */
    public function bulkCreate(array $recordsData): int
    {
        $createdCount = 0;

        foreach ($recordsData as $data) {
            try {
                $this->create($data);
                $createdCount++;
            } catch (\Exception $e) {
                // Log error or skip failed records
                continue;
            }
        }

        return $createdCount;
    }

    /**
     * Get pending attendance records (status = 0)
     */
    public function getPendingRecords(int $allocationId): array
    {
        $sql = "
            SELECT
                ta.attendance_id,
                ta.training_allocation_id,
                ta.user_id,
                ta.attendance_date,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS user_name
            FROM {$this->table} ta
            LEFT JOIN user_details ud ON ud.user_id = ta.user_id
            WHERE ta.training_allocation_id = :allocation_id
              AND ta.status = 0
            ORDER BY ta.attendance_date DESC, ta.user_id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':allocation_id' => $allocationId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* *
     * Soft delete an attendance record by setting status to a specific value (e.g., 99)
     * and optionally recording who performed the deletion
     */
    public function softDelete(int $id, int $userId): bool
    {
        $sql = "
        UPDATE {$this->table}
        SET
            is_delete = 1,
            updated_by = :updated_by,
            updated_at = :updated_at
        WHERE {$this->primaryKey} = :id
    ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':updated_by' => $userId,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
