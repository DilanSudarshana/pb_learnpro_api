<?php

declare(strict_types=1);

namespace App\Controllers\Master;

use App\Core\Controller;
use App\Models\RolePermission;
use App\Models\UserRole;

/**
 * RolePermissionController
 */
class RolePermissionController extends Controller
{
    private RolePermission $model;
    private UserRole $roleModel;

    public function __construct()
    {
        $this->model = new RolePermission();
        $this->roleModel = new UserRole();
    }

    /**
     * GET /api/role-permissions
     * Get all roles with their permissions
     */
    public function index(): void
    {
        $roles = $this->roleModel->all();
        $data = [];

        foreach ($roles as $role) {
            $roleId = (int)$role['role_id'];

            $permissions = $this->model->getPermissionsByRole($roleId);

            $data[] = [
                'role_id' => $roleId,
                'role_name' => $role['role_name'],
                'permissions' => $permissions
            ];
        }

        $this->json([
            'message' => 'Role permissions retrieved',
            'data' => $data
        ]);
    }

    /**
     * PUT /api/role-permissions-toggle/{id}
     */
    public function toggleStatus(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        $record = $this->model->find($id);

        if (!$record) {
            $this->json(['message' => 'Record not found'], 404);
            return;
        }

        $newStatus = ((int)$record['is_active'] === 1) ? 0 : 1;

        $this->model->update($id, [
            'is_active' => $newStatus,
            'updatedAt' => date('Y-m-d H:i:s')
        ]);

        $this->json([
            'message' => $newStatus ? 'Activated' : 'Deactivated',
            'is_active' => $newStatus
        ]);
    }

    /**
     * PUT /api/role-permissions-activate/{id}
     */
    public function activate(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        $this->model->update($id, [
            'is_active' => 1,
            'updatedAt' => date('Y-m-d H:i:s')
        ]);

        $this->json(['message' => 'Activated']);
    }

    /**
     * PUT /api/role-permissions-deactivate/{id}
     */
    public function deactivate(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        $this->model->update($id, [
            'is_active' => 0,
            'updatedAt' => date('Y-m-d H:i:s')
        ]);

        $this->json(['message' => 'Deactivated']);
    }
}
