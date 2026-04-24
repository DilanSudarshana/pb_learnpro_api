<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * QUIZ QUESTION ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/quiz-questions
 * Controller: App\Controllers\Quiez\QuizQuestionController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Quiez\QuizQuestionController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/quiz-questions
 * Returns all active quiz questions with their answers.
 * Requires: QUIZ_QUESTION_VIEW
 */
$router->get('/api/quiz-questions', [QuizQuestionController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('QUIZ_QUESTION_VIEW'),
]);

/**
 * GET /api/quiz-questions/{id}
 * Returns a single quiz question with its answers.
 * Requires: QUIZ_QUESTION_VIEW
 */
$router->get('/api/quiz-questions/{id}', [QuizQuestionController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('QUIZ_QUESTION_VIEW'),
]);

/**
 * POST /api/quiz-questions
 * Creates a new quiz question and its answer rows.
 *
 * MCQ body:
 *   { question_text, question_type: "MCQ", marks, order_no, created_by,
 *     options: [{ option_label, option_text, is_correct }] }   ← 4 options, 1 correct
 *
 * True/False body:
 *   { question_text, question_type: "True/False", marks, order_no, created_by,
 *     options: [{ option_text: "True", is_correct: 1 }, { option_text: "False", is_correct: 0 }] }
 *
 * Short Answer body:
 *   { question_text, question_type: "Short Answer", marks, order_no, created_by,
 *     expected_answer: "optional text" }
 *
 * Requires: QUIZ_QUESTION_CREATE
 */
$router->post('/api/quiz-questions', [QuizQuestionController::class, 'store'], [
    AuthMiddleware::class,
    permissionMiddleware('QUIZ_QUESTION_CREATE'),
]);

/**
 * PUT /api/quiz-questions/{id}
 * Updates a quiz question. If answer payload is included, answers are replaced.
 * Body: same shape as POST (all fields optional except answer rules still apply).
 * Requires: QUIZ_QUESTION_EDIT
 */
$router->put('/api/quiz-questions/{id}', [QuizQuestionController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('QUIZ_QUESTION_EDIT'),
]);

/**
 * PUT /api/quiz-questions/{id}/toggle-status
 * Activate / Deactivate a quiz question.
 * Requires: QUIZ_QUESTION_EDIT
 */
$router->put('/api/quiz-questions/{id}/toggle-status', [QuizQuestionController::class, 'toggleStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('QUIZ_QUESTION_EDIT'),
]);

/**
 * DELETE /api/quiz-questions/{id}
 * Soft delete a quiz question (is_delete = 1).
 * Requires: QUIZ_QUESTION_DELETE
 */
$router->delete('/api/quiz-questions/{id}', [QuizQuestionController::class, 'destroy'], [
    AuthMiddleware::class,
    permissionMiddleware('QUIZ_QUESTION_DELETE'),
]);
