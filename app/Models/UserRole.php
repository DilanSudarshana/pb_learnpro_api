<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Represents the user_roles table
 *
 * Columns:
 *   role_id, level, is_active, is_delete, createdAt, updatedAt, role_name
 */
class UserRole extends Model
{
    protected string $table      = 'user_roles';
    protected string $primaryKey = 'role_id';

    /**
     * Get a role with all its active permissions
     *
     * @return array|null  Role record with 'permissions' array attached
     */
    public function findWithPermissions(int $roleId): ?array
    {
        // Get the role
        $role = $this->find($roleId);

        if (!$role) {
            return null;
        }

        // Get permissions via role_permissions pivot
        $stmt = $this->db->prepare(
            "SELECT up.permission_id, up.name, up.display_name, up.description
             FROM user_permissions AS up
             INNER JOIN role_permissions AS rp
                 ON rp.permission_id = up.permission_id
             WHERE rp.role_id = ?
               AND rp.is_active = 1
               AND up.is_active = 1"
        );
        $stmt->execute([$roleId]);

        $role['permissions'] = $stmt->fetchAll();

        return $role;
    }

    /**
     * Get just the permission names for a role
     */
    public function getPermissionNames(int $roleId): array
    {
        $stmt = $this->db->prepare(
            "SELECT up.name
             FROM user_permissions AS up
             INNER JOIN role_permissions AS rp
                 ON rp.permission_id = up.permission_id
             WHERE rp.role_id = ?
               AND rp.is_active = 1
               AND up.is_active = 1"
        );
        $stmt->execute([$roleId]);

        return array_column($stmt->fetchAll(), 'name');
    }
}
