<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Represents the user_mains table
 *
 * Columns:
 *   id, email, password, service_number, is_active, is_delete, created_at, updated_at
 */
class UserMain extends Model
{
    protected string $table      = 'user_mains';
    protected string $primaryKey = 'id';

    /**
     * Find a user by their email address
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Create a new user with given data.
     * Sets created_at / updated_at automatically.
     */
    public function createUser(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['is_active']  = $data['is_active']  ?? 1;
        $data['is_delete']  = $data['is_delete']  ?? 0;

        return $this->create($data);
    }

    /**
     * Find active (non-deleted) user by email
     */
    public function findActiveByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE email = ? AND is_active = 1 AND is_delete = 0
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getAllUsers(): array
    {
        $stmt = $this->db->prepare(
            "SELECT 
            um.user_id,
            um.email,
            um.service_number,
            um.role_id,
            um.password,

            ud.user_id AS detail_user_id,
            ud.first_name,
            ud.last_name,
            ud.phone_no,
            ud.nic,
            ud.dob,
            ud.address,
            ud.gender,
            ud.marital_status,
            ud.blood_group,
            ud.department_id,
            ud.branch_id,
            ud.employment_type,
            ud.date_joined,
            ud.probation_end_date,
            ud.date_left,
            ud.basic_salary,
            ud.bank_account_number,
            ud.tax_id,
            ud.epf_no,
            ud.manager_id,
            ud.emergency_contact_name,
            ud.emergency_contact_relationship,
            ud.emergency_contact_phone,
            ud.additional_details,
            ud.pro_pic,
            ud.is_active,
            ud.is_delete,
            ud.is_online,
            ud.createdAt,
            ud.updatedAt

        FROM user_mains um
        INNER JOIN user_details ud 
            ON ud.user_id = um.user_id
        WHERE ud.is_delete = 0"
        );

        $stmt->execute();

        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // remove password
        foreach ($users as &$user) {
            unset($user['password']);
        }

        return $users;
    }

    public function getUserById(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT 
            um.user_id,
            um.email,
            um.service_number,
            um.role_id,

            ud.user_id AS detail_user_id,
            ud.first_name,
            ud.last_name,
            ud.phone_no,
            ud.nic,
            ud.dob,
            ud.address,
            ud.gender,
            ud.marital_status,
            ud.blood_group,
            ud.department_id,
            ud.branch_id,
            ud.employment_type,
            ud.date_joined,
            ud.basic_salary,
            ud.manager_id,
            ud.is_active,
            ud.is_delete

        FROM user_mains um
        LEFT JOIN user_details ud 
            ON ud.user_id = um.user_id
        WHERE um.user_id = :user_id
        LIMIT 1"
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function updateUserDetails(int $userId, array $data): bool
    {
        $setParts = [];
        $values = [];


        foreach ($data as $key => $value) {

            if ($key === 'user_id') {
                continue;
            }

            $setParts[] = "`$key` = ?";
            $values[] = $value;
        }

        if (empty($setParts)) {
            return false; // nothing to update
        }

        // always update timestamp automatically
        $setParts[] = "`updatedAt` = ?";
        $values[] = date('Y-m-d H:i:s');

        $values[] = $userId;

        $sql = "UPDATE `user_details`
            SET " . implode(', ', $setParts) . "
            WHERE `user_id` = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($values);
    }

    public function updateUserMain(int $userId, array $data): bool
    {
        $setParts = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key === 'user_id') {
                continue;
            }

            $setParts[] = "`$key` = ?";
            $values[] = $value;
        }

        if (empty($setParts)) {
            return false;
        }

        $setParts[] = "`updated_at` = ?";
        $values[] = date('Y-m-d H:i:s');

        $values[] = $userId;

        $sql = "UPDATE `user_mains`
            SET " . implode(', ', $setParts) . "
            WHERE `user_id` = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($values);
    }

    public function toggleStatus(int $id): int
    {
        $user = $this->getUserById($id);

        if (!$user) {
            return -1; // user not found
        }

        $newStatus = 1 - (int)$user['is_active'];

        $this->updateUserMain($id, [
            'is_active' => $newStatus
        ]);

        return $newStatus;
    }
}
