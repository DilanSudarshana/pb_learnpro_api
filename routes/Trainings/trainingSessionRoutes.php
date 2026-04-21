<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * TRAINING SESSION ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/training-sessions
 * Controller: App\Controllers\Trainings\TrainingSessionController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Trainings\TrainingSessionController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/training-sessions
 * Returns all active training sessions.
 * Requires: TRAINING_SESSION_VIEW
 */
$router->get('/api/training-sessions', [TrainingSessionController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_SESSION_VIEW'),
]);

/**
 * GET /api/training-sessions/{id}
 * Returns a single training session by its ID.
 * Requires: TRAINING_SESSION_VIEW
 */
$router->get('/api/training-sessions/{id}', [TrainingSessionController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_SESSION_VIEW'),
]);

/**
 * POST /api/training-sessions
 * Creates a new training session.
 * Body: { category_id, trainer_id, location, session_date, session_time,
 *         check_in?, check_out?, total_hours?, additional_details?, created_by }
 * Requires: TRAINING_SESSION_CREATE
 */
$router->post('/api/training-sessions', [TrainingSessionController::class, 'store'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_SESSION_CREATE'),
]);

/**
 * PUT /api/training-sessions/{id}
 * Updates an existing training session.
 * Body: { category_id?, trainer_id?, location?, session_date?, session_time?,
 *         check_in?, check_out?, total_hours?, additional_details?, is_active? }
 * Requires: TRAINING_SESSION_EDIT
 */
$router->put('/api/training-sessions/{id}', [TrainingSessionController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_SESSION_EDIT'),
]);

/**
 * PUT /api/training-sessions/{id}/toggle-status
 * Activate / Deactivate training session
 * Requires: TRAINING_SESSION_EDIT
 */
$router->put('/api/training-sessions/{id}/toggle-status', [TrainingSessionController::class, 'toggleStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_SESSION_EDIT'),
]);

/**
 * DELETE /api/training-sessions/{id}
 * Soft delete a training session (is_delete = 1)
 * Requires: TRAINING_SESSION_DELETE
 */
$router->delete('/api/training-sessions/{id}', [TrainingSessionController::class, 'destroy'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_SESSION_DELETE'),
]);