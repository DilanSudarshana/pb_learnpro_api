<?php

declare(strict_types=1);

namespace App\Controllers\Master;

use App\Core\Controller;
use App\Models\RolePermission;

/**
 * RolePermissionController
 * Handles assigning and revoking permissions for roles
 */
class RolePermissionController extends Controller
{
    private RolePermission $model;

    public function __construct()
    {
        $this->model = new RolePermission();
    }

    /**
     * POST /api/roles/{id}/permissions
     * Assign permission to role
     */
    public function assign(array $params): void
    {
        $roleId = (int) ($params['id'] ?? 0);
        $body = $this->getBody();

        $permissionId = (int) ($body['permission_id'] ?? 0);

        if (!$roleId || !$permissionId) {
            $this->json(['message' => 'role_id and permission_id are required'], 400);
            return;
        }

        $this->model->assign($roleId, $permissionId);

        $this->json(['message' => 'Permission assigned to role']);
    }

    /**
     * DELETE /api/roles/{id}/permissions/{permissionId}
     */
    public function revoke(array $params): void
    {
        $roleId = (int) ($params['id'] ?? 0);
        $permissionId = (int) ($params['permissionId'] ?? 0);

        if (!$roleId || !$permissionId) {
            $this->json(['message' => 'role_id and permission_id are required'], 400);
            return;
        }

        $this->model->revoke($roleId, $permissionId);

        $this->json(['message' => 'Permission revoked from role']);
    }
}
