<?php

declare(strict_types=1);

namespace App\Controllers\Trainings;

use App\Core\Controller;
use App\Models\TrainingAllocation;
use App\Utils\JwtHelper;

/**
 * TrainingAllocationController — CRUD for training_allocations
 */
class TrainingAllocationController extends Controller
{
    private TrainingAllocation $model;

    public function __construct()
    {
        $this->model = new TrainingAllocation();
    }

    /**
     * GET /api/training-allocations
     */
    public function index(): void
    {
        $allocations = $this->model->getAllActive();
        $this->json(['message' => 'Training allocations retrieved', 'data' => $allocations]);
    }

    /**
     * GET /api/training-allocations/{id}
     */
    public function show(array $params): void
    {
        $id         = (int) ($params['id'] ?? 0);
        $allocation = $this->model->find($id);

        if (!$allocation) {
            $this->json(['message' => 'Training allocation not found'], 404);
            return;
        }

        $this->json(['message' => 'Training allocation retrieved', 'data' => $allocation]);
    }

    /**
     * POST /api/training-allocations
     * Body: { trainee_id, session_id, training_date, start_time?, end_time?, status? }
     */
    public function store(): void
    {
        $authUser = JwtHelper::getAuthUserFromRequest();

        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized user'], 401);
            return;
        }

        $body         = $this->getBody();
        $traineeId    = (int) ($body['trainee_id']    ?? 0);
        $sessionId    = (int) ($body['session_id']    ?? 0);
        $trainingDate = trim($body['training_date']   ?? '');
        $startTime    = trim($body['start_time']      ?? '');
        $endTime      = trim($body['end_time']        ?? '');
        $status       = (int) ($body['status']        ?? 0);

        if ($traineeId <= 0) {
            $this->json(['message' => 'trainee_id is required'], 400);
            return;
        }

        if ($sessionId <= 0) {
            $this->json(['message' => 'session_id is required'], 400);
            return;
        }

        if (empty($trainingDate)) {
            $this->json(['message' => 'training_date is required'], 400);
            return;
        }

        $id = $this->model->create([
            'trainee_id'    => $traineeId,
            'session_id'    => $sessionId,
            'created_by'    => $authUser['user_id'],
            'training_date' => $trainingDate,
            'start_time'    => $startTime ?: null,
            'end_time'      => $endTime   ?: null,
            'status'        => $status,
            'is_active'     => 1,
            'is_delete'     => 0,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => 'Training allocation created', 'training_allocation_id' => $id], 201);
    }

    /**
     * PUT /api/training-allocations/{id}
     * Body: { trainee_id?, session_id?, training_date?, start_time?, end_time?, status? }
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid training allocation ID'], 400);
            return;
        }

        $allocation = $this->model->find($id);

        if (!$allocation) {
            $this->json(['message' => 'Training allocation not found'], 404);
            return;
        }

        $body = $this->getBody();
        $data = [];

        foreach (['trainee_id', 'session_id', 'training_date', 'start_time', 'end_time', 'status', 'is_active'] as $field) {
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

        $updated = $this->model->updateAllocation($id, $data);

        if (!$updated) {
            $this->json(['message' => 'Failed to update training allocation'], 500);
            return;
        }

        $this->json(['message' => 'Training allocation updated successfully']);
    }

    /**
     * PUT /api/training-allocations/{id}/toggle-status
     * Toggle is_active (1 ↔ 0)
     * Requires: TRAINING_ALLOCATION_EDIT
     */
    public function toggleStatus(array $params): void
    {
        $id         = (int) ($params['id'] ?? 0);
        $allocation = $this->model->find($id);

        if (!$allocation) {
            $this->json(['message' => 'Training allocation not found'], 404);
            return;
        }

        $newStatus = ((int) $allocation['is_active'] === 1) ? 0 : 1;

        $this->model->update($id, [
            'is_active'  => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message'   => $newStatus ? 'Training allocation activated' : 'Training allocation deactivated',
            'is_active' => $newStatus,
        ]);
    }

    /**
     * DELETE /api/training-allocations/{id}
     * Soft delete — sets is_delete = 1
     * Requires: TRAINING_ALLOCATION_DELETE
     */
    public function destroy(array $params): void
    {
        $id         = (int) ($params['id'] ?? 0);
        $allocation = $this->model->find($id);

        if (!$allocation) {
            $this->json(['message' => 'Training allocation not found'], 404);
            return;
        }

        $this->model->softDelete($id);

        $this->json(['message' => 'Training allocation deleted']);
    }
}
