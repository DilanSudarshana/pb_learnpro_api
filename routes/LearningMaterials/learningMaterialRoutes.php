<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * LEARNING MATERIAL ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/learning-materials
 * Controller: App\Controllers\Learning_Materials\LearningMaterialController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 *
 * NOTE: Register specific routes BEFORE wildcard {id} routes so the router
 *       does not accidentally match "upload", "training", etc. as an {id}.
 */

use App\Controllers\Learning_Materials\LearningMaterialController;
use App\Middleware\AuthMiddleware;

// ── READ ─────────────────────────────────────────────────────────────────────

/**
 * GET /api/learning-materials
 * Returns all active, non-deleted learning materials.
 * Requires: LEARNING_MATERIAL_VIEW
 */
$router->get('/api/learning-materials', [LearningMaterialController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('LEARNING_MATERIAL_VIEW'),
]);

/**
 * GET /api/learning-materials/training/{training_id}
 * Returns all materials for a specific training session.
 * Requires: LEARNING_MATERIAL_VIEW
 */
$router->get('/api/learning-materials/training/{training_id}', [LearningMaterialController::class, 'byTraining'], [
    AuthMiddleware::class,
    permissionMiddleware('LEARNING_MATERIAL_VIEW'),
]);

/**
 * GET /api/learning-materials/{id}
 * Returns a single material record by its ID.
 * Requires: LEARNING_MATERIAL_VIEW
 */
$router->get('/api/learning-materials/{id}', [LearningMaterialController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('LEARNING_MATERIAL_VIEW'),
]);

// ── FILE OPERATIONS ───────────────────────────────────────────────────────────

/**
 * POST /api/learning-materials/upload
 * Upload a new learning material file (multipart/form-data).
 * Fields: file (binary), training_id, uploaded_by, additional_details?
 * Requires: LEARNING_MATERIAL_CREATE
 */
$router->post('/api/learning-materials/upload', [LearningMaterialController::class, 'upload'], [
    AuthMiddleware::class,
    permissionMiddleware('LEARNING_MATERIAL_CREATE'),
]);

/**
 * GET /api/learning-materials/{id}/download
 * Stream the file as a downloadable attachment (Content-Disposition: attachment).
 * Requires: LEARNING_MATERIAL_VIEW
 */
$router->get('/api/learning-materials/{id}/download', [LearningMaterialController::class, 'download'], [
    AuthMiddleware::class,
    permissionMiddleware('LEARNING_MATERIAL_VIEW'),
]);

/**
 * GET /api/learning-materials/{id}/preview
 * Stream the file inline in the browser (Content-Disposition: inline).
 * Requires: LEARNING_MATERIAL_VIEW
 */
$router->get('/api/learning-materials/{id}/preview', [LearningMaterialController::class, 'preview'], [
    AuthMiddleware::class,
    permissionMiddleware('LEARNING_MATERIAL_VIEW'),
]);

// ── UPDATE ────────────────────────────────────────────────────────────────────

/**
 * PUT /api/learning-materials/{id}
 * Update metadata (additional_details, is_active) or replace the file.
 * Supports multipart/form-data for optional file replacement (field: file).
 * Requires: LEARNING_MATERIAL_EDIT
 */
$router->put('/api/learning-materials/{id}', [LearningMaterialController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('LEARNING_MATERIAL_EDIT'),
]);

/**
 * PUT /api/learning-materials/{id}/toggle-status
 * Activate / Deactivate a learning material.
 * Requires: LEARNING_MATERIAL_EDIT
 */
$router->put('/api/learning-materials/{id}/toggle-status', [LearningMaterialController::class, 'toggleStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('LEARNING_MATERIAL_EDIT'),
]);

// ── DELETE ────────────────────────────────────────────────────────────────────

/**
 * DELETE /api/learning-materials/{id}
 * Soft delete (is_delete = 1). Physical file is retained on disk.
 * Requires: LEARNING_MATERIAL_DELETE
 */
$router->delete('/api/learning-materials/{id}', [LearningMaterialController::class, 'destroy'], [
    AuthMiddleware::class,
    permissionMiddleware('LEARNING_MATERIAL_DELETE'),
]);