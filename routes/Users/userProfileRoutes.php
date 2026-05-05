<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * PROFILE ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/profile
 * Controller: App\Controllers\Employee\UserProfileController
 *
 * All routes require a valid JWT (AuthMiddleware).
 */

use App\Controllers\Employee\UserProfileController;
use App\Middleware\AuthMiddleware;

/**
 * GET /api/profile
 * Returns the authenticated user's full profile.
 * Requires: PROFILE_VIEW
 */
$router->get('/api/profile', [UserProfileController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('PROFILE_VIEW'),
]);

/**
 * PUT /api/profile
 * Updates the authenticated user's personal & contact details.
 * Body: { first_name?, last_name?, phone_no?, nic?, dob?, address?,
 *         gender?, marital_status?, blood_group?,
 *         emergency_contact_name?, emergency_contact_relationship?,
 *         emergency_contact_phone?, additional_details? }
 * Requires: PROFILE_EDIT
 */
$router->post('/api/profile', [UserProfileController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('PROFILE_EDIT'),
]);

/**
 * PUT /api/profile/change-password
 * Changes the authenticated user's password.
 * Body: { current_password, new_password, confirm_password }
 * Requires: Bearer token (AuthMiddleware)
 */
$router->put('/api/profile/change-password', [UserProfileController::class, 'changePassword'], [
    AuthMiddleware::class,
    permissionMiddleware('PROFILE_EDIT'),
]);
