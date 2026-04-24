<?php

declare(strict_types=1);

namespace App\Controllers\Quiez;

use App\Core\Controller;
use App\Models\QuizQuestion;
use App\Utils\JwtHelper;

/**
 * QuizQuestionController — CRUD for quiez_questions
 * Handles answer sub-tables per question type:
 *   MCQ         → quiez_question_options  (4 options, option_label A/B/C/D)
 *   True/False  → quiez_question_options  (2 options, option_label NULL)
 *   Short Answer→ quiez_question_short_answers
 */
class QuizQuestionController extends Controller
{
    private QuizQuestion $model;

    public function __construct()
    {
        $this->model = new QuizQuestion();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/quiz-questions
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $questions = $this->model->getAllActive();
        $this->json(['message' => 'Quiz questions retrieved', 'data' => $questions]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/quiz-questions/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $question = $this->model->find($id);

        if (!$question) {
            $this->json(['message' => 'Quiz question not found'], 404);
            return;
        }

        $this->json(['message' => 'Quiz question retrieved', 'data' => $question]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/quiz-questions
    //
    // MCQ body:
    // {
    //   question_text, question_type: "MCQ", marks, order_no, created_by,
    //   options: [
    //     { option_label: "A", option_text: "Paris",  is_correct: 0 },
    //     { option_label: "B", option_text: "London", is_correct: 1 },
    //     { option_label: "C", option_text: "Berlin", is_correct: 0 },
    //     { option_label: "D", option_text: "Madrid", is_correct: 0 }
    //   ]
    // }
    //
    // True/False body:
    // {
    //   question_text, question_type: "True/False", marks, order_no, created_by,
    //   options: [
    //     { option_text: "True",  is_correct: 1 },
    //     { option_text: "False", is_correct: 0 }
    //   ]
    // }
    //
    // Short Answer body:
    // {
    //   question_text, question_type: "Short Answer", marks, order_no, created_by,
    //   expected_answer: "optional text"
    // }
    // ─────────────────────────────────────────────────────────────────────────

    public function store(): void
    {
        $body         = $this->getBody();
        $questionText = trim($body['question_text'] ?? '');
        $questionType = trim($body['question_type'] ?? '');
        $marks        = isset($body['marks'])    ? (int) $body['marks']    : 0;
        $orderNo      = isset($body['order_no']) ? (int) $body['order_no'] : 0;
        $createdBy    = (int) ($body['created_by'] ?? 0);

        // ── Validate base fields ──────────────────────────────────────────────

        if (empty($questionText)) {
            $this->json(['message' => 'question_text is required'], 400);
            return;
        }

        $allowedTypes = ['MCQ', 'True/False', 'Short Answer'];
        if (empty($questionType) || !in_array($questionType, $allowedTypes, true)) {
            $this->json([
                'message' => 'question_type is required and must be one of: ' . implode(', ', $allowedTypes),
            ], 400);
            return;
        }

        if ($marks <= 0) {
            $this->json(['message' => 'marks is required and must be greater than 0'], 400);
            return;
        }

        if ($createdBy <= 0) {
            $this->json(['message' => 'created_by (user_id) is required'], 400);
            return;
        }

        // ── Validate answer fields per type ───────────────────────────────────

        $validationError = $this->validateAnswerPayload($questionType, $body);
        if ($validationError) {
            $this->json(['message' => $validationError], 400);
            return;
        }

        // ── Insert question ───────────────────────────────────────────────────

        $questionId = $this->model->create([
            'question_text' => $questionText,
            'question_type' => $questionType,
            'marks'         => $marks,
            'order_no'      => $orderNo,
            'created_by'    => $createdBy,
            'is_active'     => 1,
            'is_delete'     => 0,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        // ── Insert answers ────────────────────────────────────────────────────

        $this->saveAnswers($questionType, (int) $questionId, $body, $createdBy);

        $this->json(['message' => 'Quiz question created', 'question_id' => $questionId], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/quiz-questions/{id}
    // Same body shape as store() — answers are replaced on update.
    // ─────────────────────────────────────────────────────────────────────────

    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid question ID'], 400);
            return;
        }

        $question = $this->model->find($id);

        if (!$question) {
            $this->json(['message' => 'Quiz question not found'], 404);
            return;
        }

        $body = $this->getBody();
        $data = [];

        // Whitelist base fields
        foreach (['question_text', 'question_type', 'marks', 'order_no', 'is_active'] as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }

        // Validate question_type if being changed
        if (isset($data['question_type'])) {
            $allowedTypes = ['MCQ', 'True/False', 'Short Answer'];
            if (!in_array($data['question_type'], $allowedTypes, true)) {
                $this->json([
                    'message' => 'question_type must be one of: ' . implode(', ', $allowedTypes),
                ], 400);
                return;
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

        // ── Update question ───────────────────────────────────────────────────

        $updated = $this->model->updateQuestion($id, $data);

        if (!$updated) {
            $this->json(['message' => 'Failed to update quiz question'], 500);
            return;
        }

        // ── Replace answers if provided ───────────────────────────────────────

        // Use updated type if changed, otherwise fall back to existing type
        $activeType = $data['question_type'] ?? $question['question_type'];

        $hasAnswerPayload = match ($activeType) {
            'MCQ', 'True/False' => isset($body['options']),
            'Short Answer'      => array_key_exists('expected_answer', $body),
            default             => false,
        };

        if ($hasAnswerPayload) {
            $validationError = $this->validateAnswerPayload($activeType, $body);
            if ($validationError) {
                $this->json(['message' => $validationError], 400);
                return;
            }

            // Delete old options before re-inserting (MCQ / True/False only)
            if (in_array($activeType, ['MCQ', 'True/False'], true)) {
                $this->model->deleteOptions($id);
            }

            $this->saveAnswers($activeType, $id, $body, $authUser['user_id']);
        }

        $this->json(['message' => 'Quiz question updated successfully']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/quiz-questions/{id}/toggle-status
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleStatus(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $question = $this->model->find($id);

        if (!$question) {
            $this->json(['message' => 'Quiz question not found'], 404);
            return;
        }

        $newStatus = ((int) $question['is_active'] === 1) ? 0 : 1;

        $this->model->update($id, [
            'is_active'  => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message'   => $newStatus ? 'Quiz question activated' : 'Quiz question deactivated',
            'is_active' => $newStatus,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /api/quiz-questions/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $question = $this->model->find($id);

        if (!$question) {
            $this->json(['message' => 'Quiz question not found'], 404);
            return;
        }

        $this->model->softDelete($id);

        $this->json(['message' => 'Quiz question deleted']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validate the answer payload for a given question type.
     * Returns an error string or null if valid.
     */
    private function validateAnswerPayload(string $type, array $body): ?string
    {
        if ($type === 'MCQ') {
            if (empty($body['options']) || !is_array($body['options'])) {
                return 'options array is required for MCQ';
            }
            if (count($body['options']) !== 4) {
                return 'MCQ must have exactly 4 options';
            }
            $correctCount = count(array_filter($body['options'], fn($o) => !empty($o['is_correct'])));
            if ($correctCount !== 1) {
                return 'MCQ must have exactly 1 correct option';
            }
        }

        if ($type === 'True/False') {
            if (empty($body['options']) || !is_array($body['options'])) {
                return 'options array is required for True/False';
            }
            if (count($body['options']) !== 2) {
                return 'True/False must have exactly 2 options';
            }
            $correctCount = count(array_filter($body['options'], fn($o) => !empty($o['is_correct'])));
            if ($correctCount !== 1) {
                return 'True/False must have exactly 1 correct option';
            }
        }

        // Short Answer: expected_answer is optional — no hard validation needed

        return null;
    }

    /**
     * Persist answer rows for the given type.
     */
    private function saveAnswers(string $type, int $questionId, array $body, int $userId): void
    {
        $now = date('Y-m-d H:i:s');

        if (in_array($type, ['MCQ', 'True/False'], true)) {
            foreach ($body['options'] as $index => $option) {
                $this->model->createOption([
                    'question_id'  => $questionId,
                    'option_label' => $type === 'MCQ' ? ($option['option_label'] ?? null) : null,
                    'option_text'  => trim($option['option_text'] ?? ''),
                    'is_correct'   => (int) ($option['is_correct'] ?? 0),
                    'order_no'     => $index + 1,
                    'created_by'   => $userId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }

        if ($type === 'Short Answer') {
            $this->model->saveShortAnswer([
                'question_id'     => $questionId,
                'expected_answer' => trim($body['expected_answer'] ?? ''),
                'created_by'      => $userId,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }
}
