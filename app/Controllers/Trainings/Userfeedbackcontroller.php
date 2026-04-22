<?php

declare(strict_types=1);

namespace App\Controllers\Trainings;

use App\Core\Controller;
use App\Models\UserFeedback;
use App\Utils\JwtHelper;

/**
 * UserFeedbackController — CRUD for user_feedback
 */
class UserFeedbackController extends Controller
{
    private UserFeedback $model;

    public function __construct()
    {
        $this->model = new UserFeedback();
    }

    /**
     * GET /api/user-feedbacks
     */
    public function index(): void
    {
        $feedbacks = $this->model->getAllActive();
        $this->json(['message' => 'User feedbacks retrieved', 'data' => $feedbacks]);
    }

    /**
     * GET /api/user-feedbacks/{id}
     */
    public function show(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $feedback = $this->model->find($id);

        if (!$feedback) {
            $this->json(['message' => 'User feedback not found'], 404);
            return;
        }

        $this->json(['message' => 'User feedback retrieved', 'data' => $feedback]);
    }

    /**
     * GET /api/user-feedbacks/training/{training_id}
     * Returns all feedback for a specific training.
     */
    public function byTraining(array $params): void
    {
        $trainingId = (int) ($params['training_id'] ?? 0);

        if ($trainingId <= 0) {
            $this->json(['message' => 'Invalid training ID'], 400);
            return;
        }

        $feedbacks = $this->model->getByTraining($trainingId);
        $this->json(['message' => 'Training feedbacks retrieved', 'data' => $feedbacks]);
    }

    /**
     * POST /api/user-feedbacks
     * Body: { user_id, training_id, rating, comment? }
     */
    public function store(): void
    {
        $body       = $this->getBody();
        $userId     = (int) ($body['user_id']     ?? 0);
        $trainingId = (int) ($body['training_id'] ?? 0);
        $rating     = (int) ($body['rating']      ?? 0);
        $comment    = trim($body['comment']        ?? '');

        if ($userId <= 0) {
            $this->json(['message' => 'user_id is required'], 400);
            return;
        }

        if ($trainingId <= 0) {
            $this->json(['message' => 'training_id is required'], 400);
            return;
        }

        if ($rating < 1 || $rating > 5) {
            $this->json(['message' => 'rating must be between 1 and 5'], 400);
            return;
        }

        $id = $this->model->create([
            'user_id'     => $userId,
            'training_id' => $trainingId,
            'rating'      => $rating,
            'comment'     => $comment ?: null,
            'is_active'   => 1,
            'is_delete'   => 0,
            'created_by'  => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->json(['message' => 'User feedback created', 'review_id' => $id], 201);
    }

    /**
     * PUT /api/user-feedbacks/{id}
     * Body: { rating?, comment? }
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid feedback ID'], 400);
            return;
        }

        $feedback = $this->model->find($id);

        if (!$feedback) {
            $this->json(['message' => 'User feedback not found'], 404);
            return;
        }

        $body = $this->getBody();
        $data = [];

        foreach (['rating', 'comment', 'is_active'] as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }

        if (isset($data['rating'])) {
            $rating = (int) $data['rating'];
            if ($rating < 1 || $rating > 5) {
                $this->json(['message' => 'rating must be between 1 and 5'], 400);
                return;
            }
            $data['rating'] = $rating;
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

        $updated = $this->model->updateFeedback($id, $data);

        if (!$updated) {
            $this->json(['message' => 'Failed to update user feedback'], 500);
            return;
        }

        $this->json(['message' => 'User feedback updated successfully']);
    }

    /**
     * PUT /api/user-feedbacks/{id}/toggle-status
     * Toggle feedback active status (1 ↔ 0)
     */
    public function toggleStatus(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $feedback = $this->model->find($id);

        if (!$feedback) {
            $this->json(['message' => 'User feedback not found'], 404);
            return;
        }

        $newStatus = ((int) $feedback['is_active'] === 1) ? 0 : 1;

        $this->model->update($id, [
            'is_active'  => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message'   => $newStatus ? 'User feedback activated' : 'User feedback deactivated',
            'is_active' => $newStatus,
        ]);
    }

    /**
     * DELETE /api/user-feedbacks/{id}
     * Soft delete — sets is_delete = 1
     */
    public function destroy(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $feedback = $this->model->find($id);

        if (!$feedback) {
            $this->json(['message' => 'User feedback not found'], 404);
            return;
        }

        $this->model->softDelete($id);

        $this->json(['message' => 'User feedback deleted']);
    }
}
