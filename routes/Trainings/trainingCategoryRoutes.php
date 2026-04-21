<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * TRAINING CATEGORY ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/training-categories
 * Controller: App\Controllers\Trainings\TrainingCategoryController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Trainings\TrainingCategoryController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/training-categories
 * Returns all active training categories.
 * Requires: TRAINING_CATEGORY_VIEW
 */
$router->get('/api/training-categories', [TrainingCategoryController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_CATEGORY_VIEW'),
]);

/**
 * GET /api/training-categories/{id}
 * Returns a single training category by its ID.
 * Requires: TRAINING_CATEGORY_VIEW
 */
$router->get('/api/training-categories/{id}', [TrainingCategoryController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_CATEGORY_VIEW'),
]);

/**
 * POST /api/training-categories
 * Creates a new training category.
 * Body: { category_name, additional_details, created_by }
 * Requires: TRAINING_CATEGORY_CREATE
 */
$router->post('/api/training-categories', [TrainingCategoryController::class, 'store'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_CATEGORY_CREATE'),
]);

/**
 * PUT /api/training-categories/{id}
 * Updates an existing training category.
 * Body: { category_name?, additional_details? }
 * Requires: TRAINING_CATEGORY_EDIT
 */
$router->put('/api/training-categories/{id}', [TrainingCategoryController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_CATEGORY_EDIT'),
]);

/**
 * PUT /api/training-categories/{id}/toggle-status
 * Activate / Deactivate training category
 * Requires: TRAINING_CATEGORY_EDIT
 */
$router->put('/api/training-categories/{id}/toggle-status', [TrainingCategoryController::class, 'toggleStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_CATEGORY_EDIT'),
]);

/**
 * DELETE /api/training-categories/{id}
 * Soft delete a training category (is_delete = 1)
 * Requires: TRAINING_CATEGORY_DELETE
 */
$router->delete('/api/training-categories/{id}', [TrainingCategoryController::class, 'destroy'], [
    AuthMiddleware::class,
    permissionMiddleware('TRAINING_CATEGORY_DELETE'),
]);
