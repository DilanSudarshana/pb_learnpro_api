<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

use PDO;

class UserProfile extends Model
{
    protected string $table      = 'user_details';
    protected string $primaryKey = 'user_id';

    /**
     * Fetch full profile for a given user_id.
     */
    public function getProfile(int $userId): ?array
    {
        $sql = "
        SELECT
            -- Identity
            um.user_id,
            um.email,
            um.service_number,
            um.is_active,

            -- Personal (LMS)
            ud.first_name,
            ud.last_name,
            CONCAT(ud.first_name, ' ', ud.last_name) AS full_name,
            ud.phone_no,
            ud.profile_picture,
            ud.bio,

            -- Org structure
            ud.role_id,
            r.role_name,
            ud.department_id,
            ud.branch_id,

            -- LMS meta
            ud.date_joined,
            ud.is_active AS profile_active,
            ud.is_delete,
            ud.is_online,

            ud.created_at,
            ud.updated_at

        FROM user_mains um
        INNER JOIN user_details ud ON ud.user_id = um.user_id
        LEFT JOIN user_roles r ON r.role_id = ud.role_id

        WHERE um.user_id = :user_id
          AND um.is_delete = 0
          AND ud.is_delete = 0
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Update fields in the user_mains table.
     */
    public function updateUserMain(int $userId, array $data): bool
    {
        $allowedFields = [
            'email',
            'password',
            'service_number',
            'role_id',
            'is_active',
            'updated_at',
        ];

        $fields = [];
        $params = [':user_id' => $userId];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue;
            }
            $fields[]        = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (empty($fields)) {
            return true; // Nothing to update — not an error
        }

        $sql  = "UPDATE user_mains
             SET " . implode(', ', $fields) . "
             WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update fields in the user_details table.
     */
    public function updateUserDetails(int $userId, array $data): bool
    {
        $allowedFields = [
            'first_name',
            'last_name',
            'phone_no',
            'profile_picture',
            'bio',
            'role_id',
            'department_id',
            'branch_id',
            'date_joined',
            'is_active',
            'is_delete',
            'is_online',
            'updated_at'
        ];

        $fields = [];
        $params = [':user_id' => $userId];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue;
            }
            $fields[]        = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (empty($fields)) {
            return true; // Nothing to update — not an error
        }

        $sql  = "UPDATE user_details
             SET " . implode(', ', $fields) . "
             WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Fetch a user row from user_mains by user_id.
     */
    public function getUserById(int $userId): array|false
    {
        $sql  = "SELECT * FROM user_mains WHERE user_id = :user_id AND is_delete = 0 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update the password in user_mains table.
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $sql  = "UPDATE user_mains
             SET password = :password, updated_at = :updated_at
             WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':password'   => $hashedPassword,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':user_id'    => $userId,
        ]);
    }
}
