<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * ROLE PERMISSION ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/role-permissions
 * Controller: App\Controllers\Master\RolePermissionController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Master\RolePermissionController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/role-permissions
 * Returns all roles with their assigned permissions (categorized).
 * Requires: ROLE_PERMISSION_VIEW
 */
$router->get('/api/role-permissions', [RolePermissionController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('ROLE_PERMISSION_VIEW'),
]);

/**
 * PUT /api/role-permissions-toggle/{id}
 * Toggles the active status of a role-permission record (1 ↔ 0).
 * Requires: ROLE_PERMISSION_EDIT
 */
$router->put('/api/role-permissions-toggle/{id}', [RolePermissionController::class, 'toggleStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('ROLE_PERMISSION_EDIT'),
]);

/**
 * PUT /api/role-permissions-activate/{id}
 * Activates a role-permission record (sets is_active = 1).
 * Requires: ROLE_PERMISSION_EDIT
 */
$router->put('/api/role-permissions-activate/{id}', [RolePermissionController::class, 'activate'], [
    AuthMiddleware::class,
    permissionMiddleware('ROLE_PERMISSION_EDIT'),
]);

/**
 * PUT /api/role-permissions-deactivate/{id}
 * Deactivates a role-permission record (sets is_active = 0).
 * Requires: ROLE_PERMISSION_EDIT
 */
$router->put('/api/role-permissions-deactivate/{id}', [RolePermissionController::class, 'deactivate'], [
    AuthMiddleware::class,
    permissionMiddleware('ROLE_PERMISSION_EDIT'),
]);
