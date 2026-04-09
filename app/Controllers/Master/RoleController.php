<?php

declare(strict_types=1);

namespace App\Controllers\Master;

use App\Core\Controller;
use App\Models\UserRole;
use App\Models\UserPermission;
use App\Models\RolePermission;

/**
 * RoleController — manage roles and assign/revoke permissions
 * All routes are protected (AuthMiddleware required).
 */
class RoleController extends Controller
{
    private UserRole       $roleModel;
    private UserPermission $permissionModel;
    private RolePermission $rolePermissionModel;

    public function __construct()
    {
        $this->roleModel           = new UserRole();
        $this->permissionModel     = new UserPermission();
        $this->rolePermissionModel = new RolePermission();
    }

    /**
     * GET /api/roles
     */
    public function index(): void
    {
        $roles = $this->roleModel->all();
        $this->json(['message' => 'Roles retrieved', 'data' => $roles]);
    }

    /**
     * GET /api/roles/{id}
     * Returns the role with its permissions
     */
    public function show(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $role = $this->roleModel->findWithPermissions($id);

        if (!$role) {
            $this->json(['message' => 'Role not found'], 404);
            return;
        }

        $this->json(['message' => 'Role retrieved', 'data' => $role]);
    }

    /**
     * POST /api/roles
     * Body: { role_name, level }
     */
    public function store(): void
    {
        $body      = $this->getBody();
        $roleName  = trim($body['role_name'] ?? '');
        $level     = (int) ($body['level']    ?? 0);

        if (empty($roleName)) {
            $this->json(['message' => 'role_name is required'], 400);
            return;
        }

        $id = $this->roleModel->create([
            'role_name' => $roleName,
            'level'     => $level,
            'is_active' => 1,
            'is_delete' => 0,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => 'Role created', 'role_id' => $id], 201);
    }

    /**
     * POST /api/roles/{id}/permissions
     * Body: { permission_id }
     * Assign a permission to a role
     */
    public function assignPermission(array $params): void
    {
        $roleId       = (int) ($params['id'] ?? 0);
        $body         = $this->getBody();
        $permissionId = (int) ($body['permission_id'] ?? 0);

        if (!$roleId || !$permissionId) {
            $this->json(['message' => 'role_id and permission_id are required'], 400);
            return;
        }

        $this->rolePermissionModel->assign($roleId, $permissionId);

        $this->json(['message' => 'Permission assigned to role']);
    }

    /**
     * DELETE /api/roles/{id}/permissions/{permissionId}
     * Revoke a permission from a role
     */
    public function revokePermission(array $params): void
    {
        $roleId       = (int) ($params['id']           ?? 0);
        $permissionId = (int) ($params['permissionId'] ?? 0);

        $this->rolePermissionModel->revoke($roleId, $permissionId);

        $this->json(['message' => 'Permission revoked from role']);
    }
}
