<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * AUTH ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix  : /api/auth
 * Controller: App\Controllers\Auth\AuthController
 *
 * Public routes  — no JWT required
 * Protected routes — JWT required via AuthMiddleware
 */

use App\Controllers\Auth\AuthController;
use App\Middleware\AuthMiddleware;

// ── Public ────────────────────────────────────────────────────────────────────

/**
 * POST /api/auth/login
 * Body : { email, password }
 * Calls external AD server → auto-registers user → returns JWT
 */
$router->post('/api/auth/login', [AuthController::class, 'login']);

// ── Protected ─────────────────────────────────────────────────────────────────

/**
 * GET /api/auth/me
 * Returns the authenticated user's data decoded from the JWT payload.
 */
$router->get('/api/auth/me', [AuthController::class, 'me'], [
    AuthMiddleware::class,
]);

/**
 * GET /api/auth/profile
 * Requires PROFILE_MANAGEMENT permission.
 */
$router->get('/api/auth/profile', [AuthController::class, 'profile'], [
    AuthMiddleware::class,
    permissionMiddleware('PROFILE_MANAGEMENT'),
]);

/**
 * POST /api/auth/register
 * Body : { email, password, first_name, last_name, ...optional detail fields }
 * Creates user_mains + user_details rows. No JWT required (admin-driven
 * registration — add AuthMiddleware + permission if you want it protected).
 */
$router->post('/api/auth/register', [AuthController::class, 'register']);

/**
 * POST /api/auth/logout
 * Logs out the authenticated user. Client should clear token from storage.
 */
$router->post('/api/auth/logout', [AuthController::class, 'logout'], [
    AuthMiddleware::class,
]);
