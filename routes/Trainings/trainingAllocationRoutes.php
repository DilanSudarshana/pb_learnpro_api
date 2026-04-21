<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * TRAINING ALLOCATION ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/training-allocations
 * Controller: App\Controllers\Trainings\TrainingAllocationController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Trainings\TrainingAllocationController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/training-allocations
 * Returns all active (non-deleted) training allocations.
 * Requires: TRAINING_ALLOCATION_VIEW
 */
$router->get('/api/training-allocations', [TrainingAllocationController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_ALLOCATION_VIEW'),
]);

/**
 * GET /api/training-allocations/{id}
 * Returns a single training allocation by its ID.
 * Requires: TRAINING_ALLOCATION_VIEW
 */
$router->get('/api/training-allocations/{id}', [TrainingAllocationController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_ALLOCATION_VIEW'),
]);

/**
 * POST /api/training-allocations
 * Creates a new training allocation.
 * Body: { trainee_id, session_id, training_date, start_time?, end_time?, status? }
 * Requires: TRAINING_ALLOCATION_CREATE
 */
$router->post('/api/training-allocations', [TrainingAllocationController::class, 'store'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_ALLOCATION_CREATE'),
]);

/**
 * PUT /api/training-allocations/{id}
 * Updates an existing training allocation.
 * Body: { trainee_id?, session_id?, training_date?, start_time?, end_time?, status?, is_active? }
 * Requires: TRAINING_ALLOCATION_EDIT
 */
$router->put('/api/training-allocations/{id}', [TrainingAllocationController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_ALLOCATION_EDIT'),
]);

/**
 * PUT /api/training-allocations/{id}/toggle-status
 * Activate / Deactivate a training allocation (is_active: 1 ↔ 0).
 * Requires: TRAINING_ALLOCATION_EDIT
 */
$router->put('/api/training-allocations/{id}/toggle-status', [TrainingAllocationController::class, 'toggleStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_ALLOCATION_EDIT'),
]);

/**
 * DELETE /api/training-allocations/{id}
 * Soft delete a training allocation (is_delete = 1).
 * Requires: TRAINING_ALLOCATION_DELETE
 */
$router->delete('/api/training-allocations/{id}', [TrainingAllocationController::class, 'destroy'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_ALLOCATION_DELETE'),
]);
