<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * RolePermission Model
 * 
 * Represents the role_permissions table (pivot table)
 * Table structure:
 *   - role_id (int) - Foreign key to roles table
 *   - permission_id (int) - Foreign key to user_permissions table
 *   - is_active (int) - 0 or 1
 *   - createdAt (datetime)
 *   - updatedAt (datetime)
 * 
 * Composite primary key: (role_id, permission_id)
 */
class RolePermission extends Model
{
    protected string $table = 'role_permissions';
    protected string $primaryKey = 'role_id'; // Composite key - using role_id as base

    /**
     * Get all permissions for a specific role
     * 
     * @param int $roleId
     * @return array
     */
    public function getByRole(int $roleId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE role_id = ? AND is_active = 1"
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all permissions for a role with permission details
     * Joins with user_permissions table
     * 
     * @param int $roleId
     * @return array
     */
    public function getPermissionsByRole(int $roleId): array
    {
        $sql = "SELECT 
                    rp.role_id,
                    rp.permission_id,
                    rp.is_active,
                    rp.createdAt,
                    rp.updatedAt,
                    p.permission_id,
                    p.name,
                    p.display_name
                FROM `{$this->table}` rp
                INNER JOIN `user_permissions` p ON p.permission_id = rp.permission_id
                WHERE rp.role_id = :role_id
                ORDER BY p.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role_id' => $roleId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a permission record by composite key (role_id, permission_id)
     * 
     * @param int $roleId
     * @param int $permissionId
     * @return array|false
     */
    public function findByRoleAndPermission(int $roleId, int $permissionId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` 
             WHERE role_id = :role_id AND permission_id = :permission_id
             LIMIT 1"
        );

        $stmt->execute([
            'role_id' => $roleId,
            'permission_id' => $permissionId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update a permission record by composite key (role_id, permission_id)
     * 
     * @param int $roleId
     * @param int $permissionId
     * @param array $data - Data to update (is_active, updatedAt, etc.)
     * @return bool
     */
    public function updateByRoleAndPermission(int $roleId, int $permissionId, array $data): bool
    {
        // Build SET clause
        $setClause = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClause[] = "`{$key}` = ?";
            $params[] = $value;
        }

        $setClause = implode(', ', $setClause);

        // Add composite key parameters
        $params[] = $roleId;
        $params[] = $permissionId;

        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` 
             SET {$setClause} 
             WHERE role_id = ? AND permission_id = ?"
        );

        return $stmt->execute($params);
    }

    /**
     * Delete a permission record by composite key (role_id, permission_id)
     * Note: This performs a soft delete by setting is_active = 0
     * 
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function deleteByRoleAndPermission(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}` 
             WHERE role_id = :role_id AND permission_id = :permission_id"
        );

        return $stmt->execute([
            'role_id' => $roleId,
            'permission_id' => $permissionId
        ]);
    }

    /**
     * Assign a permission to a role
     * Uses INSERT ... ON DUPLICATE KEY UPDATE to handle existing records
     * 
     * @param int $roleId
     * @param int $permissionId
     * @return bool
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
     * Revoke a permission from a role (soft delete)
     * Sets is_active = 0 instead of hard delete
     * 
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function revoke(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` 
             SET is_active = 0, updatedAt = NOW()
             WHERE role_id = ? AND permission_id = ?"
        );
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Check if a role has a specific permission
     * 
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function hasPermission(int $roleId, int $permissionId): bool
    {
        $record = $this->findByRoleAndPermission($roleId, $permissionId);
        return $record && (int)$record['is_active'] === 1;
    }

    /**
     * Get all active permissions for a role as an array
     * 
     * @param int $roleId
     * @return array
     */
    public function getActivePermissionIds(int $roleId): array
    {
        $stmt = $this->db->prepare(
            "SELECT permission_id FROM `{$this->table}` 
             WHERE role_id = ? AND is_active = 1
             ORDER BY permission_id ASC"
        );
        $stmt->execute([$roleId]);

        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $permissions);
    }

    /**
     * Get all roles that have a specific permission
     * 
     * @param int $permissionId
     * @return array
     */
    public function getRolesByPermission(int $permissionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT role_id FROM `{$this->table}` 
             WHERE permission_id = ? AND is_active = 1
             ORDER BY role_id ASC"
        );
        $stmt->execute([$permissionId]);

        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $roles);
    }

    /**
     * Bulk assign permissions to a role
     * 
     * @param int $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function bulkAssign(int $roleId, array $permissionIds): bool
    {
        if (empty($permissionIds)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($permissionIds), '(?, ?, 1, NOW(), NOW())'));

        // Flatten the params array
        $params = [];
        foreach ($permissionIds as $permId) {
            $params[] = $roleId;
            $params[] = $permId;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}` (role_id, permission_id, is_active, createdAt, updatedAt)
             VALUES {$placeholders}
             ON DUPLICATE KEY UPDATE is_active = 1, updatedAt = NOW()"
        );

        return $stmt->execute($params);
    }

    /**
     * Bulk revoke permissions from a role
     * 
     * @param int $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function bulkRevoke(int $roleId, array $permissionIds): bool
    {
        if (empty($permissionIds)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($permissionIds), '?'));

        $params = [$roleId];
        array_push($params, ...$permissionIds);

        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` 
             SET is_active = 0, updatedAt = NOW()
             WHERE role_id = ? AND permission_id IN ({$placeholders})"
        );

        return $stmt->execute($params);
    }
}
