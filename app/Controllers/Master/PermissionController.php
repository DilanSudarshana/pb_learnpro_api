<?php

declare(strict_types=1);

namespace App\Controllers\Master;

use App\Core\Controller;
use App\Models\UserPermission;

/**
 * PermissionController — CRUD for user_permissions
 */
class PermissionController extends Controller
{
    private UserPermission $model;

    public function __construct()
    {
        $this->model = new UserPermission();
    }

    /**
     * GET /api/permissions
     */
    public function index(): void
    {
        $permissions = $this->model->getAllActive();
        $this->json(['message' => 'Permissions retrieved', 'data' => $permissions]);
    }

    /**
     * GET /api/permissions/{id}
     */
    public function show(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $perm = $this->model->find($id);

        if (!$perm) {
            $this->json(['message' => 'Permission not found'], 404);
            return;
        }

        $this->json(['message' => 'Permission retrieved', 'data' => $perm]);
    }

    /**
     * POST /api/permissions
     * Body: { name, display_name, description }
     */
    public function store(): void
    {
        $body        = $this->getBody();
        $name        = trim($body['name']         ?? '');
        $displayName = trim($body['display_name'] ?? '');
        $description = trim($body['description']  ?? '');

        if (empty($name)) {
            $this->json(['message' => 'name is required'], 400);
            return;
        }

        $id = $this->model->create([
            'name'         => $name,
            'display_name' => $displayName,
            'description'  => $description,
            'is_active'    => 1,
            'createdAt'    => date('Y-m-d H:i:s'),
            'updatedAt'    => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => 'Permission created', 'permission_id' => $id], 201);
    }

    /**
     * PUT /api/permissions/{id}
     */
    public function update(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $perm = $this->model->find($id);

        if (!$perm) {
            $this->json(['message' => 'Permission not found'], 404);
            return;
        }

        $body = $this->getBody();
        $data = [];

        foreach (['name', 'display_name', 'description', 'is_active'] as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }

        $data['updatedAt'] = date('Y-m-d H:i:s');
        $this->model->update($id, $data);

        $this->json(['message' => 'Permission updated']);
    }

    /**
     * PUT /api/permissions/{id}/toggle-status
     * Toggle permission active status (1 ↔ 0)
     * Requires: PERMISSION_EDIT
     */
    public function toggleStatus(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $perm = $this->model->find($id);

        if (!$perm) {
            $this->json(['message' => 'Permission not found'], 404);
            return;
        }

        // Toggle status
        $newStatus = ((int)$perm['is_active'] === 1) ? 0 : 1;

        $this->model->update($id, [
            'is_active' => $newStatus,
            'updatedAt' => date('Y-m-d H:i:s')
        ]);

        $this->json([
            'message' => $newStatus ? 'Permission activated' : 'Permission deactivated',
            'is_active' => $newStatus
        ]);
    }
}
