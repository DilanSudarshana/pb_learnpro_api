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
}
