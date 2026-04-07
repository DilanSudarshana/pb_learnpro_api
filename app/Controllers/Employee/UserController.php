<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserMain;
use App\Models\UserRole;

/**
 * UserController — CRUD for user_mains
 * All routes are protected (AuthMiddleware required).
 */
class UserController extends Controller
{
    private UserMain $userModel;
    private UserRole $roleModel;

    public function __construct()
    {
        $this->userModel = new UserMain();
        $this->roleModel = new UserRole();
    }

    /**
     * GET /api/users
     * Requires: USER_VIEW permission
     */
    public function index(): void
    {
        $users = $this->userModel->all();

        // Strip passwords before sending
        $users = array_map(function ($u) {
            unset($u['password']);
            return $u;
        }, $users);

        $this->json([
            'message' => 'Users retrieved successfully',
            'data'    => $users,
        ]);
    }

    /**
     * GET /api/users/{id}
     * Requires: USER_VIEW permission
     */
    public function show(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->json(['message' => 'User not found'], 404);
            return;
        }

        unset($user['password']);

        // Attach role with permissions
        $roleId = (int) ($user['role_id'] ?? 0);
        if ($roleId) {
            $user['role'] = $this->roleModel->findWithPermissions($roleId);
        }

        $this->json([
            'message' => 'User retrieved successfully',
            'data'    => $user,
        ]);
    }

    /**
     * PUT /api/users/{id}
     * Requires: USER_EDIT permission
     */
    public function update(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->json(['message' => 'User not found'], 404);
            return;
        }

        $body = $this->getBody();

        $allowedFields = ['service_number', 'role_id', 'is_active'];
        $updateData    = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $body)) {
                $updateData[$field] = $body[$field];
            }
        }

        if (empty($updateData)) {
            $this->json(['message' => 'No valid fields to update'], 400);
            return;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->userModel->update($id, $updateData);

        $this->json(['message' => 'User updated successfully']);
    }

    /**
     * DELETE /api/users/{id}  (soft delete)
     * Requires: USER_DELETE permission
     */
    public function destroy(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->json(['message' => 'User not found'], 404);
            return;
        }

        $this->userModel->update($id, [
            'is_delete'  => 1,
            'is_active'  => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => 'User deleted successfully']);
    }
}
