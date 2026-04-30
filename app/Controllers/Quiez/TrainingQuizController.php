<?php

declare(strict_types=1);

namespace App\Controllers\Quiez;

use App\Core\Controller;
use App\Models\TrainingQuiz;
use App\Utils\JwtHelper;

/**
 * TrainingQuizController — CRUD for training_quizzes
 * + question allocation management via quiz_question_allocations
 */
class TrainingQuizController extends Controller
{
    private TrainingQuiz $model;

    public function __construct()
    {
        $this->model = new TrainingQuiz();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/training-quizzes
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $quizzes = $this->model->getAllActive();
        $this->json(['message' => 'Training quizzes retrieved', 'data' => $quizzes]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/training-quizzes/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $quiz = $this->model->find($id);

        if (!$quiz) {
            $this->json(['message' => 'Training quiz not found'], 404);
            return;
        }

        $this->json(['message' => 'Training quiz retrieved', 'data' => $quiz]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/training-quizzes
    //
    // Body:
    // {
    //   "training_id": 1,
    //   "title": "Quiz 1",
    //   "time_limit": 30,
    //   "is_active": 1,
    //   "created_by": 1,
    //   "question_ids": [1, 2, 3]   ← optional, allocates questions immediately
    // }
    // ─────────────────────────────────────────────────────────────────────────

    public function store(): void
    {
        $body       = $this->getBody();
        $trainingId = (int) ($body['training_id'] ?? 0);
        $title      = trim($body['title']         ?? '');
        $timeLimit  = isset($body['time_limit'])  ? (int) $body['time_limit']  : null;
        $isActive   = isset($body['is_active'])   ? (int) $body['is_active']   : 1;
        $createdBy  = (int) ($body['created_by']  ?? 0);
        $questionIds = $body['question_ids']       ?? [];

        // ── Validate ──────────────────────────────────────────────────────────

        if ($trainingId <= 0) {
            $this->json(['message' => 'training_id is required'], 400);
            return;
        }

        if (empty($title)) {
            $this->json(['message' => 'title is required'], 400);
            return;
        }

        if ($createdBy <= 0) {
            $this->json(['message' => 'created_by (user_id) is required'], 400);
            return;
        }

        // ── Insert quiz ───────────────────────────────────────────────────────

        $quizId = $this->model->create([
            'training_id' => $trainingId,
            'title'       => $title,
            'time_limit'  => $timeLimit,
            'total_marks' => 0,           // recalculated after question allocation
            'is_active'   => $isActive,
            'is_delete'   => 0,
            'created_by'  => $createdBy,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // ── Allocate questions if provided ────────────────────────────────────

        if (!empty($questionIds) && is_array($questionIds)) {
            $this->model->syncQuestions((int) $quizId, $questionIds);
            $this->model->recalculateTotalMarks((int) $quizId);
        }

        $this->json(['message' => 'Training quiz created', 'quiz_id' => $quizId], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/training-quizzes/{id}
    //
    // Body: all fields optional
    // {
    //   "training_id": 1,
    //   "title": "Updated Quiz",
    //   "time_limit": 45,
    //   "is_active": 1,
    //   "question_ids": [1, 4, 5]   ← replaces all allocations if provided
    // }
    // ─────────────────────────────────────────────────────────────────────────

    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid quiz ID'], 400);
            return;
        }

        $quiz = $this->model->find($id);

        if (!$quiz) {
            $this->json(['message' => 'Training quiz not found'], 404);
            return;
        }

        $body = $this->getBody();
        $data = [];

        // Whitelist base fields
        foreach (['training_id', 'title', 'time_limit', 'is_active'] as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
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

        // ── Update quiz base fields ───────────────────────────────────────────

        if (count($data) > 2) { // more than just updated_at + updated_by
            $updated = $this->model->updateQuiz($id, $data);

            if (!$updated) {
                $this->json(['message' => 'Failed to update training quiz'], 500);
                return;
            }
        }

        // ── Sync question allocations if provided ─────────────────────────────

        if (array_key_exists('question_ids', $body) && is_array($body['question_ids'])) {
            $this->model->syncQuestions($id, $body['question_ids']);
            $this->model->recalculateTotalMarks($id);
        }

        $this->json(['message' => 'Training quiz updated successfully']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/training-quizzes/{id}/toggle-status
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleStatus(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $quiz = $this->model->find($id);

        if (!$quiz) {
            $this->json(['message' => 'Training quiz not found'], 404);
            return;
        }

        $newStatus = ((int) $quiz['is_active'] === 1) ? 0 : 1;

        $this->model->update($id, [
            'is_active'  => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message'   => $newStatus ? 'Training quiz activated' : 'Training quiz deactivated',
            'is_active' => $newStatus,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/training-quizzes/{id}/allocate-question
    // Add a single question to a quiz
    //
    // Body: { "question_id": 5 }
    // ─────────────────────────────────────────────────────────────────────────

    public function allocateQuestion(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $quiz = $this->model->find($id);

        if (!$quiz) {
            $this->json(['message' => 'Training quiz not found'], 404);
            return;
        }

        $body       = $this->getBody();
        $questionId = (int) ($body['question_id'] ?? 0);

        if ($questionId <= 0) {
            $this->json(['message' => 'question_id is required'], 400);
            return;
        }

        $this->model->allocateQuestion($id, $questionId);
        $this->model->recalculateTotalMarks($id);

        $this->json(['message' => 'Question allocated to quiz successfully']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /api/training-quizzes/{id}/remove-question
    // Remove a single question from a quiz
    //
    // Body: { "question_id": 5 }
    // ─────────────────────────────────────────────────────────────────────────

    public function removeQuestion(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $quiz = $this->model->find($id);

        if (!$quiz) {
            $this->json(['message' => 'Training quiz not found'], 404);
            return;
        }

        $body       = $this->getBody();
        $questionId = (int) ($body['question_id'] ?? 0);

        if ($questionId <= 0) {
            $this->json(['message' => 'question_id is required'], 400);
            return;
        }

        $this->model->removeQuestion($id, $questionId);
        $this->model->recalculateTotalMarks($id);

        $this->json(['message' => 'Question removed from quiz successfully']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/training-quizzes/{id}/questions
    // List all allocated questions for a quiz
    // ─────────────────────────────────────────────────────────────────────────

    public function questions(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $quiz = $this->model->find($id);

        if (!$quiz) {
            $this->json(['message' => 'Training quiz not found'], 404);
            return;
        }

        $questions = $this->model->getAllocatedQuestions($id);

        $this->json([
            'message' => 'Allocated questions retrieved',
            'quiz_id' => $id,
            'total'   => count($questions),
            'data'    => $questions,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /api/training-quizzes/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(array $params): void
    {
        $id   = (int) ($params['id'] ?? 0);
        $quiz = $this->model->find($id);

        if (!$quiz) {
            $this->json(['message' => 'Training quiz not found'], 404);
            return;
        }

        $this->model->softDelete($id);

        $this->json(['message' => 'Training quiz deleted']);
    }
}
