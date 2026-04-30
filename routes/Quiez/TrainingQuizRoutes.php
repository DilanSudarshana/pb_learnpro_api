<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * TRAINING QUIZ ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/training-quizzes
 * Controller: App\Controllers\Quiez\TrainingQuizController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Quiez\TrainingQuizController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/training-quizzes
 * Returns all active training quizzes with assigned question count.
 * Requires: TRAINING_QUIZ_VIEW
 */
$router->get('/api/training-quizzes', [TrainingQuizController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_QUIZ_VIEW'),
]);

/**
 * GET /api/training-quizzes/{id}
 * Returns a single training quiz with its allocated questions.
 * Requires: TRAINING_QUIZ_VIEW
 */
$router->get('/api/training-quizzes/{id}', [TrainingQuizController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_QUIZ_VIEW'),
]);

/**
 * GET /api/training-quizzes/{id}/questions
 * Returns all allocated questions for a quiz.
 * Requires: TRAINING_QUIZ_VIEW
 */
$router->get('/api/training-quizzes/{id}/questions', [TrainingQuizController::class, 'questions'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_QUIZ_VIEW'),
]);

/**
 * POST /api/training-quizzes
 * Creates a new training quiz.
 * Body: { training_id, title, time_limit?, is_active?, created_by, question_ids[]? }
 * Requires: TRAINING_QUIZ_CREATE
 */
$router->post('/api/training-quizzes', [TrainingQuizController::class, 'store'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_QUIZ_CREATE'),
]);

/**
 * PUT /api/training-quizzes/{id}
 * Updates a training quiz. If question_ids is provided, allocations are replaced.
 * Body: { training_id?, title?, time_limit?, is_active?, question_ids[]? }
 * Requires: TRAINING_QUIZ_EDIT
 */
$router->put('/api/training-quizzes/{id}', [TrainingQuizController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_QUIZ_EDIT'),
]);

/**
 * PUT /api/training-quizzes/{id}/toggle-status
 * Activate / Deactivate a training quiz.
 * Requires: TRAINING_QUIZ_EDIT
 */
$router->put('/api/training-quizzes/{id}/toggle-status', [TrainingQuizController::class, 'toggleStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_QUIZ_EDIT'),
]);

/**
 * POST /api/training-quizzes/{id}/allocate-question
 * Add a single question to a quiz.
 * Body: { question_id }
 * Requires: TRAINING_QUIZ_EDIT
 */
$router->post('/api/training-quizzes/{id}/allocate-question', [TrainingQuizController::class, 'allocateQuestion'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_QUIZ_EDIT'),
]);

/**
 * DELETE /api/training-quizzes/{id}/remove-question
 * Remove a single question from a quiz.
 * Body: { question_id }
 * Requires: TRAINING_QUIZ_EDIT
 */
$router->delete('/api/training-quizzes/{id}/remove-question', [TrainingQuizController::class, 'removeQuestion'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_QUIZ_EDIT'),
]);

/**
 * DELETE /api/training-quizzes/{id}
 * Soft delete a training quiz (is_delete = 1).
 * Requires: TRAINING_QUIZ_DELETE
 */
$router->delete('/api/training-quizzes/{id}', [TrainingQuizController::class, 'destroy'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_QUIZ_DELETE'),
]);
