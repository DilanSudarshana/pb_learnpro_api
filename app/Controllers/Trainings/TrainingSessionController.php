<?php

declare(strict_types=1);

namespace App\Controllers\Trainings;

use App\Core\Controller;
use App\Models\TrainingSession;
use App\Utils\JwtHelper;

/**
 * TrainingSessionController — CRUD for training_session
 */
class TrainingSessionController extends Controller
{
    private TrainingSession $model;

    public function __construct()
    {
        $this->model = new TrainingSession();
    }

    /**
     * GET /api/training-sessions
     */
    public function index(): void
    {
        $sessions = $this->model->getAllActive();
        $this->json(['message' => 'Training sessions retrieved', 'data' => $sessions]);
    }

    /**
     * GET /api/training-sessions/{id}
     */
    public function show(array $params): void
    {
        $id      = (int) ($params['id'] ?? 0);
        $session = $this->model->find($id);

        if (!$session) {
            $this->json(['message' => 'Training session not found'], 404);
            return;
        }

        $this->json(['message' => 'Training session retrieved', 'data' => $session]);
    }

    /**
     * POST /api/training-sessions
     * Body: {
     *   category_id, trainer_id, location,
     *   session_date, session_time,
     *   check_in?, check_out?, total_hours?,
     *   additional_details?, created_by
     * }
     */
    public function store(): void
    {
        $body = $this->getBody();

        $categoryId        = (int)   ($body['category_id']        ?? 0);
        $trainerId         = (int)   ($body['trainer_id']         ?? 0);
        $location          = trim(($body['location']           ?? ''));
        $sessionDate       = trim(($body['session_date']       ?? ''));
        $sessionTime       = trim(($body['session_time']       ?? ''));
        $checkIn           = trim(($body['check_in']           ?? ''));
        $checkOut          = trim(($body['check_out']          ?? ''));
        $totalHours        = isset($body['total_hours'])
            ? (float) $body['total_hours']
            : null;
        $additionalDetails = trim(($body['additional_details'] ?? ''));
        $createdBy         = (int)   ($body['created_by']         ?? 0);

        // ── Validation ────────────────────────────────────────────────────────
        if ($categoryId <= 0) {
            $this->json(['message' => 'category_id is required'], 400);
            return;
        }

        if ($trainerId <= 0) {
            $this->json(['message' => 'trainer_id is required'], 400);
            return;
        }

        if (empty($location)) {
            $this->json(['message' => 'location is required'], 400);
            return;
        }

        if (empty($sessionDate)) {
            $this->json(['message' => 'session_date is required'], 400);
            return;
        }

        if (empty($sessionTime)) {
            $this->json(['message' => 'session_time is required'], 400);
            return;
        }

        if ($createdBy <= 0) {
            $this->json(['message' => 'created_by (user_id) is required'], 400);
            return;
        }
        // ──────────────────────────────────────────────────────────────────────

        $id = $this->model->create([
            'category_id'        => $categoryId,
            'trainer_id'         => $trainerId,
            'location'           => $location,
            'session_date'       => $sessionDate,
            'session_time'       => $sessionTime,
            'check_in'           => $checkIn    ?: null,
            'check_out'          => $checkOut   ?: null,
            'total_hours'        => $totalHours,
            'additional_details' => $additionalDetails,
            'created_by'         => $createdBy,
            'is_active'          => 1,
            'is_delete'          => 0,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => 'Training session created', 'session_id' => $id], 201);
    }

    /**
     * PUT /api/training-sessions/{id}
     * Body: { category_id?, trainer_id?, location?, session_date?, session_time?,
     *         check_in?, check_out?, total_hours?, additional_details?, is_active? }
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid session ID'], 400);
            return;
        }

        $session = $this->model->find($id);

        if (!$session) {
            $this->json(['message' => 'Training session not found'], 404);
            return;
        }

        $body = $this->getBody();
        $data = [];

        foreach (
            [
                'category_id',
                'trainer_id',
                'location',
                'session_date',
                'session_time',
                'check_in',
                'check_out',
                'total_hours',
                'additional_details',
                'is_active',
            ] as $field
        ) {
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

        $updated = $this->model->updateSession($id, $data);

        if (!$updated) {
            $this->json(['message' => 'Failed to update training session'], 500);
            return;
        }

        $this->json(['message' => 'Training session updated successfully']);
    }

    /**
     * PUT /api/training-sessions/{id}/toggle-status
     * Toggle training session active status (1 ↔ 0)
     * Requires: TRAINING_SESSION_EDIT
     */
    public function toggleStatus(array $params): void
    {
        $id      = (int) ($params['id'] ?? 0);
        $session = $this->model->find($id);

        if (!$session) {
            $this->json(['message' => 'Training session not found'], 404);
            return;
        }

        $newStatus = ((int) $session['is_active'] === 1) ? 0 : 1;

        $this->model->update($id, [
            'is_active'  => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message'   => $newStatus ? 'Training session activated' : 'Training session deactivated',
            'is_active' => $newStatus,
        ]);
    }

    /**
     * DELETE /api/training-sessions/{id}
     * Soft delete — sets is_delete = 1
     * Requires: TRAINING_SESSION_DELETE
     */
    public function destroy(array $params): void
    {
        $id      = (int) ($params['id'] ?? 0);
        $session = $this->model->find($id);

        if (!$session) {
            $this->json(['message' => 'Training session not found'], 404);
            return;
        }

        $this->model->softDelete($id);

        $this->json(['message' => 'Training session deleted']);
    }
}
