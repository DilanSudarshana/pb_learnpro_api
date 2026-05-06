<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Represents the user_permissions table
 *
 * Columns:
 *   permission_id, name, display_name, description, is_active, createdAt, updatedAt
 */
class UserPermission extends Model
{
    protected string $table      = 'user_permissions';
    protected string $primaryKey = 'permission_id';

    /**
     * Check if a specific role has a specific permission (by name)
     */
    public function roleHasPermission(int $roleId, string $permissionName): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM user_permissions AS up
             INNER JOIN role_permissions AS rp
                 ON rp.permission_id = up.permission_id
             WHERE up.name = ?
               AND rp.role_id = ?
               AND rp.is_active = 1
               AND up.is_active = 1
             LIMIT 1"
        );
        $stmt->execute([$permissionName, $roleId]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get all active permissions
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM `{$this->table}` ORDER BY permission_id ASC"
        );
        return $stmt->fetchAll();
    }
}
