<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * USER ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/users
 * Controller: App\Controllers\UserController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Employee\UserController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/users
 * Returns a list of all users (passwords stripped).
 * Requires: USER_VIEW
 */
$router->get('/api/users', [UserController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_VIEW'),
]);

/**
 * GET /api/users/{id}
 * Returns a single user with their role and permissions.
 * Requires: USER_VIEW
 */
$router->get('/api/users/{id}', [UserController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_VIEW'),
]);

/**
 * PUT /api/users/{id}
 * Updates allowed fields: service_number, role_id, is_active.
 * Requires: USER_EDIT
 */
$router->put('/api/users/{id}', [UserController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_EDIT'),
]);

/**
 * DELETE /api/users/{id}
 * Soft-deletes the user (sets is_delete=1, is_active=0).
 * Requires: USER_DELETE
 */
$router->delete('/api/users/{id}', [UserController::class, 'destroy'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_DELETE'),
]);

/**s
 * PATCH /api/users/{id}/toggle-status
 * Toggles the user's active status (is_active).
 * Requires: USER_EDIT
 */
$router->put('/api/users/{id}/toggle-status', [UserController::class, 'toggleStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_EDIT'),
]);
