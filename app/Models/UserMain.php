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
     * Find a user by their service number
     */
    public function findByServiceNumber(string $serviceNumber)
    {
        $stmt = $this->db->prepare("
        SELECT user_id 
        FROM user_mains 
        WHERE service_number = :service_number 
        LIMIT 1
    ");

        $stmt->execute([
            'service_number' => $serviceNumber
        ]);

        return $stmt->fetch();
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

            ur.role_name,
            ur.level,

            ud.user_id AS detail_user_id,
            ud.first_name,
            ud.last_name,
            ud.phone_no,
            ud.profile_picture,
            ud.bio,
            ud.department_id,
            ud.branch_id,
            ud.date_joined,
            ud.is_active,
            ud.is_delete,
            ud.is_online,
            ud.created_at,
            ud.updated_at

        FROM user_mains um

        INNER JOIN user_details ud 
            ON ud.user_id = um.user_id

        LEFT JOIN user_roles ur
            ON um.role_id = ur.role_id

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
            ud.profile_picture,
            ud.bio,
            ud.role_id,
            ud.department_id,
            ud.branch_id,
            ud.date_joined,
            ud.is_active,
            ud.is_delete,
            ud.is_online,
            ud.created_at,
            ud.updated_at

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

    /**
     * Toggle user active status
     * Returns: 1 (active), 0 (inactive), -1 (not found)
     */
    public function toggleStatus(int $id): int
    {
        try {
            $this->db->beginTransaction();

            // Toggle in user_details
            $stmt1 = $this->db->prepare("
            UPDATE user_details 
            SET is_active = 1 - is_active 
            WHERE user_id = :id
        ");
            $stmt1->execute(['id' => $id]);

            // Toggle in user_mains
            $stmt2 = $this->db->prepare("
            UPDATE user_mains 
            SET is_active = 1 - is_active 
            WHERE user_id = :id
        ");
            $stmt2->execute(['id' => $id]);

            // If no rows affected in BOTH → user not found
            if ($stmt1->rowCount() === 0 && $stmt2->rowCount() === 0) {
                $this->db->rollBack();
                return -1;
            }

            // Get updated status (single source of truth)
            $stmt = $this->db->prepare("
            SELECT is_active FROM user_mains WHERE user_id = :id
        ");
            $stmt->execute(['id' => $id]);

            $status = (int)$stmt->fetchColumn();

            $this->db->commit();

            return $status;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Create user_main + user_details in a single transaction
     * Returns new user_id or throws on failure
     */
    public function createFullUser(array $mainData, array $detailData): int
    {
        $this->db->beginTransaction();

        try {
            // 1. Insert into user_mains
            $mainData['created_at'] = date('Y-m-d H:i:s');
            $mainData['updated_at'] = date('Y-m-d H:i:s');
            $mainData['is_active']  = $mainData['is_active'] ?? 1;
            $mainData['is_delete']  = $mainData['is_delete'] ?? 0;
            $mainData['password']   = password_hash($mainData['password'], PASSWORD_BCRYPT);

            $columns    = implode(', ', array_map(fn($k) => "`$k`", array_keys($mainData)));
            $placeHolders = implode(', ', array_fill(0, count($mainData), '?'));

            $stmt = $this->db->prepare(
                "INSERT INTO `user_mains` ($columns) VALUES ($placeHolders)"
            );
            $stmt->execute(array_values($mainData));

            $userId = (int)$this->db->lastInsertId();

            // 2. Insert into user_details with the same user_id
            $detailData['user_id']   = $userId;
            $detailData['created_at'] = date('Y-m-d H:i:s');
            $detailData['updated_at'] = date('Y-m-d H:i:s');
            $detailData['is_active'] = $detailData['is_active'] ?? 1;
            $detailData['is_delete'] = $detailData['is_delete'] ?? 0;
            $detailData['is_online'] = $detailData['is_online'] ?? 0;

            $dColumns       = implode(', ', array_map(fn($k) => "`$k`", array_keys($detailData)));
            $dPlaceholders  = implode(', ', array_fill(0, count($detailData), '?'));

            $stmt = $this->db->prepare(
                "INSERT INTO `user_details` ($dColumns) VALUES ($dPlaceholders)"
            );
            $stmt->execute(array_values($detailData));

            $this->db->commit();

            return $userId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findUserDetailsByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT 
            first_name,
            user_id,
            last_name,
            phone_no,
            profile_picture,
            bio,
            role_id,
            department_id,
            branch_id,
            date_joined,
            is_active,
            is_delete,
            is_online
        FROM user_details
        WHERE user_id = :user_id
        LIMIT 1"
        );

        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}
