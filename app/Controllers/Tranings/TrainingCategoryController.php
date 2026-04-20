<?php

declare(strict_types=1);

namespace App\Controllers\Tranings;

use App\Core\Controller;
use App\Models\TrainingCategory;
use App\Utils\JwtHelper;

/**
 * TrainingCategoryController — CRUD for training_category
 */
class TrainingCategoryController extends Controller
{
    private TrainingCategory $model;

    public function __construct()
    {
        $this->model = new TrainingCategory();
    }

    /**
     * GET /api/training-categories
     */
    public function index(): void
    {
        $categories = $this->model->getAllActive();
        $this->json(['message' => 'Training categories retrieved', 'data' => $categories]);
    }

    /**
     * GET /api/training-categories/{id}
     */
    public function show(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $category = $this->model->find($id);

        if (!$category) {
            $this->json(['message' => 'Training category not found'], 404);
            return;
        }

        $this->json(['message' => 'Training category retrieved', 'data' => $category]);
    }

    /**
     * POST /api/training-categories
     * Body: { category_name, additional_details, created_by }
     */
    public function store(): void
    {
        $body              = $this->getBody();
        $categoryName      = trim($body['category_name']     ?? '');
        $additionalDetails = trim($body['additional_details'] ?? '');
        $createdBy         = (int) ($body['created_by']      ?? 0);

        if (empty($categoryName)) {
            $this->json(['message' => 'category_name is required'], 400);
            return;
        }

        if ($createdBy <= 0) {
            $this->json(['message' => 'created_by (user_id) is required'], 400);
            return;
        }

        $id = $this->model->create([
            'category_name'      => $categoryName,
            'additional_details' => $additionalDetails,
            'created_by'         => $createdBy,
            'is_active'          => 1,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => 'Training category created', 'category_id' => $id], 201);
    }

    /**
     * PUT /api/training-categories/{id}
     * Body: { category_name?, additional_details? }
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid category ID'], 400);
            return;
        }

        $category = $this->model->find($id);

        if (!$category) {
            $this->json(['message' => 'Training category not found'], 404);
            return;
        }

        $body = $this->getBody();

        $data = [];

        // whitelist input fields
        foreach (['category_name', 'additional_details', 'is_active'] as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }

        if (empty($data)) {
            $this->json(['message' => 'No updatable fields provided'], 400);
            return;
        }

        $authUser = JwtHelper::getAuthUserFromRequest();

        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized user'], 401);
            return;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $authUser['user_id'];

        
        if (!$data['updated_by']) {
            $this->json(['message' => 'Unauthorized user'], 401);
            return;
        }

        $updated = $this->model->updateCategory($id, $data);

        if (!$updated) {
            $this->json(['message' => 'Failed to update training category'], 500);
            return;
        }

        $this->json(['message' => 'Training category updated successfully']);
    }

    /**
     * PUT /api/training-categories/{id}/toggle-status
     * Toggle training category active status (1 ↔ 0)
     * Requires: TRAINING_CATEGORY_EDIT
     */
    public function toggleStatus(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $category = $this->model->find($id);

        if (!$category) {
            $this->json(['message' => 'Training category not found'], 404);
            return;
        }

        $newStatus = ((int) $category['is_active'] === 1) ? 0 : 1;

        $this->model->update($id, [
            'is_active'  => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message'   => $newStatus ? 'Training category activated' : 'Training category deactivated',
            'is_active' => $newStatus,
        ]);
    }

    /**
     * DELETE /api/training-categories/{id}
     * Soft delete — sets is_delete = 1
     * Requires: TRAINING_CATEGORY_DELETE
     */
    public function destroy(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $category = $this->model->find($id);

        if (!$category) {
            $this->json(['message' => 'Training category not found'], 404);
            return;
        }

        $this->model->softDelete($id);

        $this->json(['message' => 'Training category deleted']);
    }
}
