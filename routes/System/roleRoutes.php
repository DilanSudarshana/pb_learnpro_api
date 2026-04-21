<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * ROLE ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/roles
 * Controller: App\Controllers\RoleController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Master\RoleController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/roles
 * Returns a list of all roles.
 * Requires: ROLE_VIEW
 */
$router->get('/api/roles', [RoleController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('ROLE_VIEW'),
]);

/**
 * GET /api/roles/{id}
 * Returns a single role with all its active permissions.
 * Requires: ROLE_VIEW
 */
$router->get('/api/roles/{id}', [RoleController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('ROLE_VIEW'),
]);

/**
 * POST /api/roles
 * Creates a new role.
 * Body: { role_name, level }
 * Requires: ROLE_CREATE
 */
$router->post('/api/roles', [RoleController::class, 'store'], [
    AuthMiddleware::class,
    permissionMiddleware('ROLE_CREATE'),
]);

/**
 * POST /api/roles/{id}/permissions
 * Assigns a permission to a role (upsert — safe to call repeatedly).
 * Body: { permission_id }
 * Requires: ROLE_EDIT
 */
$router->post('/api/roles/{id}/permissions', [RoleController::class, 'assignPermission'], [
    AuthMiddleware::class,
    permissionMiddleware('ROLE_EDIT'),
]);

/**
 * DELETE /api/roles/{id}/permissions/{permissionId}
 * Revokes a permission from a role (sets is_active = 0).
 * Requires: ROLE_EDIT
 */
$router->put('/api/roles/{id}/permissions/{permissionId}', [RoleController::class, 'revokePermission'], [
    AuthMiddleware::class,
    permissionMiddleware('ROLE_EDIT'),
]);
