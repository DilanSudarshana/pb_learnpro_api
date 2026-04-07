<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * PERMISSION ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/permissions
 * Controller: App\Controllers\PermissionController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\PermissionController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/permissions
 * Returns all active permissions.
 * Requires: PERMISSION_VIEW
 */
$router->get('/api/permissions', [PermissionController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('PERMISSION_VIEW'),
]);

/**
 * GET /api/permissions/{id}
 * Returns a single permission by its ID.
 * Requires: PERMISSION_VIEW
 */
$router->get('/api/permissions/{id}', [PermissionController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('PERMISSION_VIEW'),
]);

/**
 * POST /api/permissions
 * Creates a new permission entry.
 * Body: { name, display_name, description }
 * Requires: PERMISSION_CREATE
 */
$router->post('/api/permissions', [PermissionController::class, 'store'], [
    AuthMiddleware::class,
    permissionMiddleware('PERMISSION_CREATE'),
]);

/**
 * PUT /api/permissions/{id}
 * Updates an existing permission.
 * Body: { name?, display_name?, description?, is_active? }
 * Requires: PERMISSION_EDIT
 */
$router->put('/api/permissions/{id}', [PermissionController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('PERMISSION_EDIT'),
]);
