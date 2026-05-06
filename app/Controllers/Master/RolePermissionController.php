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
        try {
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
        } catch (\Exception $e) {
            $this->json([
                'message' => 'Failed to retrieve role permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/role-permissions-toggle/{roleId}/{permissionId}
     * Toggle permission for a role (grant/revoke)
     * If permission doesn't exist, it will be created and granted
     */
    public function toggleStatus(array $params): void
    {
        try {
            $roleId = (int)($params['role_id'] ?? 0);
            $permissionId = (int)($params['permission_id'] ?? 0);

            if (!$roleId || !$permissionId) {
                $this->json([
                    'success' => false,
                    'message' => 'role_id and permission_id are required'
                ], 400);
                return;
            }

            // Find using composite key
            $record = $this->model->findByRoleAndPermission($roleId, $permissionId);

            // If record doesn't exist, create it with is_active = 1 (granted)
            if (!$record) {
                $created = $this->model->assign($roleId, $permissionId);

                if (!$created) {
                    $this->json([
                        'success' => false,
                        'message' => 'Failed to create and assign permission'
                    ], 500);
                    return;
                }

                $this->json([
                    'success' => true,
                    'message' => 'Granted successfully',
                    'data' => [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'is_active' => 1,
                        'action' => 'granted'
                    ]
                ]);
                return;
            }

            // If record exists, toggle between 0 and 1
            $currentStatus = (int)$record['is_active'];
            $newStatus = $currentStatus === 1 ? 0 : 1;
            $action = $newStatus === 1 ? 'granted' : 'revoked';

            $updated = $this->model->updateByRoleAndPermission(
                $roleId,
                $permissionId,
                [
                    'is_active' => $newStatus,
                    'updatedAt' => date('Y-m-d H:i:s')
                ]
            );

            if (!$updated) {
                $this->json([
                    'success' => false,
                    'message' => 'Failed to update permission'
                ], 500);
                return;
            }

            $this->json([
                'success' => true,
                'message' => ucfirst($action) . ' successfully',
                'data' => [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'is_active' => $newStatus,
                    'action' => $action
                ]
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error updating permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/role-permissions
     * Grant a permission to a role
     */
    public function grant(array $params): void
    {
        try {
            $roleId = (int)($params['role_id'] ?? 0);
            $permissionId = (int)($params['permission_id'] ?? 0);

            if (!$roleId || !$permissionId) {
                $this->json([
                    'success' => false,
                    'message' => 'role_id and permission_id are required'
                ], 400);
                return;
            }

            $assigned = $this->model->assign($roleId, $permissionId);

            if (!$assigned) {
                $this->json([
                    'success' => false,
                    'message' => 'Failed to assign permission'
                ], 500);
                return;
            }

            $this->json([
                'success' => true,
                'message' => 'Permission granted successfully',
                'data' => [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'is_active' => 1,
                    'action' => 'granted'
                ]
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error granting permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/role-permissions/{roleId}/{permissionId}
     * Revoke a permission from a role
     */
    public function revoke(array $params): void
    {
        try {
            $roleId = (int)($params['role_id'] ?? 0);
            $permissionId = (int)($params['permission_id'] ?? 0);

            if (!$roleId || !$permissionId) {
                $this->json([
                    'success' => false,
                    'message' => 'role_id and permission_id are required'
                ], 400);
                return;
            }

            $revoked = $this->model->revoke($roleId, $permissionId);

            if (!$revoked) {
                $this->json([
                    'success' => false,
                    'message' => 'Failed to revoke permission'
                ], 500);
                return;
            }

            $this->json([
                'success' => true,
                'message' => 'Permission revoked successfully',
                'data' => [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'is_active' => 0,
                    'action' => 'revoked'
                ]
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error revoking permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
