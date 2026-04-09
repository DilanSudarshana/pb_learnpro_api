<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Represents the role_permissions table (pivot)
 *
 * Columns:
 *   role_id, permission_id, is_active, createdAt, updatedAt
 */
class RolePermission extends Model
{
    protected string $table      = 'role_permissions';
    protected string $primaryKey = 'role_id'; // composite, but use role_id as primary

    /**
     * Get all permission entries for a given role
     */
    public function getByRole(int $roleId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE role_id = ? AND is_active = 1"
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll();
    }

    /**
     * Assign a permission to a role
     */
    public function assign(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}` (role_id, permission_id, is_active, createdAt, updatedAt)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE is_active = 1, updatedAt = NOW()"
        );
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Revoke a permission from a role
     */
    public function revoke(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET is_active = 0, updatedAt = NOW()
             WHERE role_id = ? AND permission_id = ?"
        );
        return $stmt->execute([$roleId, $permissionId]);
    }

    public function getPermissionsByRole(int $roleId): array
    {
        $sql = "SELECT 
                rp.role_id,
                rp.is_active,
                p.permission_id AS permission_id,
                p.name,
                p.display_name
            FROM role_permissions rp
            JOIN user_permissions p ON p.permission_id = rp.permission_id
            WHERE rp.role_id = :role_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role_id' => $roleId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
