<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * USER FEEDBACK ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/user-feedbacks
 * Controller: App\Controllers\Trainings\UserFeedbackController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Trainings\UserFeedbackController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/user-feedbacks
 * Returns all active, non-deleted feedback rows.
 * Requires: USER_FEEDBACK_VIEW
 */
$router->get('/api/user-feedbacks', [UserFeedbackController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_FEEDBACK_VIEW'),
]);

/**
 * GET /api/user-feedbacks/{id}
 * Returns a single feedback row by its ID.
 * Requires: USER_FEEDBACK_VIEW
 */
$router->get('/api/user-feedbacks/{id}', [UserFeedbackController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_FEEDBACK_VIEW'),
]);

/**
 * GET /api/user-feedbacks/training/{training_id}
 * Returns all feedback for a specific training.
 * Requires: USER_FEEDBACK_VIEW
 */
$router->get('/api/user-feedbacks/training/{training_id}', [UserFeedbackController::class, 'byTraining'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_FEEDBACK_VIEW'),
]);

/**
 * POST /api/user-feedbacks
 * Creates a new feedback entry.
 * Body: { user_id, training_id, rating, comment? }
 * Requires: USER_FEEDBACK_CREATE
 */
$router->post('/api/user-feedbacks', [UserFeedbackController::class, 'store'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_FEEDBACK_CREATE'),
]);

/**
 * PUT /api/user-feedbacks/{id}
 * Updates an existing feedback entry.
 * Body: { rating?, comment?, is_active? }
 * Requires: USER_FEEDBACK_EDIT
 */
$router->put('/api/user-feedbacks/{id}', [UserFeedbackController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_FEEDBACK_EDIT'),
]);

/**
 * PUT /api/user-feedbacks/{id}/toggle-status
 * Activate / Deactivate a feedback entry.
 * Requires: USER_FEEDBACK_EDIT
 */
$router->put('/api/user-feedbacks/{id}/toggle-status', [UserFeedbackController::class, 'toggleStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_FEEDBACK_EDIT'),
]);

/**
 * DELETE /api/user-feedbacks/{id}
 * Soft delete a feedback entry (is_delete = 1).
 * Requires: USER_FEEDBACK_DELETE
 */
$router->delete('/api/user-feedbacks/{id}', [UserFeedbackController::class, 'destroy'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_FEEDBACK_DELETE'),
]);
