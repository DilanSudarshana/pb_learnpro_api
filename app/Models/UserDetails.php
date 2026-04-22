<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Represents the user_details table
 *
 * Columns (assumed):
 *   user_id, user_main_id, first_name, last_name, phone_no, nic, dob,
 *   address, gender, marital_status, blood_group, department_id, branch_id,
 *   employment_type, date_joined, probation_end_date, date_left,
 *   basic_salary, bank_account_number, tax_id, epf_no, manager_id,
 *   emergency_contact_name, emergency_contact_relationship,
 *   emergency_contact_phone, additional_details, pro_pic,
 *   is_active, is_delete, is_online, createdAt, updatedAt
 */
class UserDetails extends Model
{
    protected string $table      = 'user_details';
    protected string $primaryKey = 'user_id';

    /**
     * Create a detail record linked to a user_mains row.
     * Only non-null fields from $data are written; timestamps are auto-set.
     */
    public function createDetail(array $data): int
    {
        $data['createdAt'] = date('Y-m-d H:i:s');
        $data['updatedAt'] = date('Y-m-d H:i:s');
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['is_delete'] = $data['is_delete'] ?? 0;
        $data['is_online'] = $data['is_online'] ?? 0;

        return $this->create($data);
    }

    /**
     * Find detail row by the FK to user_mains.
     */
    public function findByUserMainId(int $userMainId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE user_main_id = ? AND is_delete = 0
             LIMIT 1"
        );
        $stmt->execute([$userMainId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Soft-delete a detail record by its own PK.
     */
    public function softDelete(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET is_delete = 1, updatedAt = ?
             WHERE user_id = ?"
        );
        return $stmt->execute([date('Y-m-d H:i:s'), $userId]);
    }

    /**
     * Update online status.
     */
    public function setOnlineStatus(int $userId, bool $online): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET is_online = ?, updatedAt = ?
             WHERE user_id = ?"
        );
        return $stmt->execute([(int) $online, date('Y-m-d H:i:s'), $userId]);
    }

    /**
     * Generic update — pass any column => value pairs.
     * Skips user_id to prevent PK mutation.
     */
    public function updateByUserMainId(int $userMainId, array $data): bool
    {
        $setParts = [];
        $values   = [];

        foreach ($data as $key => $value) {
            if ($key === 'user_id' || $key === 'user_main_id') {
                continue;
            }
            $setParts[] = "`{$key}` = ?";
            $values[]   = $value;
        }

        if (empty($setParts)) {
            return false;
        }

        $setParts[] = '`updated_AT` = ?';
        $values[]   = date('Y-m-d H:i:s');
        $values[]   = $userMainId;

        $sql = "UPDATE `{$this->table}`
                SET " . implode(', ', $setParts) . "
                WHERE `user_id` = ?";

        return $this->db->prepare($sql)->execute($values);
    }
}
