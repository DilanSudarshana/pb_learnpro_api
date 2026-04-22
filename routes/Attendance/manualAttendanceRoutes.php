<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * MANUAL ATTENDANCE ROUTES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Prefix    : /api/manual-attendance
 * Controller: App\Controllers\Attendance\ManualAttendanceController
 *
 * All routes require a valid JWT (AuthMiddleware).
 * Each route additionally requires a specific permission.
 */

use App\Controllers\Attendance\ManualAttendanceController;
use App\Middleware\AuthMiddleware;

// ── READ ─────────────────────────────────────────────────────────────────────

/**
 * GET /api/manual-attendance
 * Returns all manual attendance records with optional filters
 * Query params: training_allocation_id, user_id, status, attendance_date_from, attendance_date_to
 * Requires: MANUAL_ATTENDANCE_VIEW
 */
$router->get('/api/manual-attendance', [ManualAttendanceController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('MANUAL_ATTENDANCE_VIEW'),
]);

/**
 * GET /api/manual-attendance/{id}
 * Returns a single manual attendance record by ID
 * Requires: MANUAL_ATTENDANCE_VIEW
 */
$router->get('/api/manual-attendance/{id}', [ManualAttendanceController::class, 'show'], [
    AuthMiddleware::class,
    permissionMiddleware('MANUAL_ATTENDANCE_VIEW'),
]);

// ── CREATE ────────────────────────────────────────────────────────────────────

/**
 * POST /api/manual-attendance
 * Creates a new manual attendance record
 * Body: {
 *   training_allocation_id,
 *   user_id,
 *   attendance_date,
 *   in_time? (HH:MM:SS),
 *   out_time? (HH:MM:SS),
 *   attendance_type? (BY_USER | MANUAL),
 *   status? (0=pending, 1=present, 2=absent, 3=late, 4=half-day, 5=leave, default: 0),
 *   is_marked? (0 or 1, default: 0),
 *   is_late? (0 or 1, default: 0),
 *   is_early_leave? (0 or 1, default: 0)
 * }
 * Requires: MANUAL_ATTENDANCE_CREATE
 */
$router->post('/api/manual-attendance', [ManualAttendanceController::class, 'store'], [
    AuthMiddleware::class,
    permissionMiddleware('MANUAL_ATTENDANCE_CREATE'),
]);

// ── UPDATE ────────────────────────────────────────────────────────────────────

/**
 * PUT /api/manual-attendance/{id}
 * Updates an existing manual attendance record
 * Body: {
 *   in_time?,
 *   out_time?,
 *   attendance_type?,
 *   status?,
 *   is_marked?,
 *   is_late?,
 *   is_early_leave?
 * }
 * Requires: MANUAL_ATTENDANCE_EDIT
 */
$router->put('/api/manual-attendance/{id}', [ManualAttendanceController::class, 'update'], [
    AuthMiddleware::class,
    permissionMiddleware('MANUAL_ATTENDANCE_EDIT'),
]);

/**
 * PUT /api/manual-attendance/{id}/mark-status
 * Quick status update for manual attendance (mark as present, absent, late, etc.)
 * Body: { status }
 * Status values: 0=pending, 1=present, 2=absent, 3=late, 4=half-day, 5=leave
 * Requires: MANUAL_ATTENDANCE_EDIT
 */
$router->put('/api/manual-attendance/{id}/mark-status', [ManualAttendanceController::class, 'markStatus'], [
    AuthMiddleware::class,
    permissionMiddleware('MANUAL_ATTENDANCE_EDIT'),
]);

// ── DELETE ────────────────────────────────────────────────────────────────────

/**
 * DELETE /api/manual-attendance/{id}
 * Deletes a manual attendance record
 * Requires: MANUAL_ATTENDANCE_DELETE
 */
$router->delete('/api/manual-attendance/{id}', [ManualAttendanceController::class, 'destroy'], [
    AuthMiddleware::class,
    permissionMiddleware('MANUAL_ATTENDANCE_DELETE'),
]);

// ── SUMMARY ───────────────────────────────────────────────────────────────────

/**
 * GET /api/manual-attendance/allocation/{allocation_id}/summary
 * Get manual attendance summary for a specific training allocation
 * Returns: count by status, attendance percentage per user
 * Requires: MANUAL_ATTENDANCE_VIEW
 */
$router->get('/api/manual-attendance/allocation/{allocation_id}/summary', [ManualAttendanceController::class, 'allocationSummary'], [
    AuthMiddleware::class,
    permissionMiddleware('MANUAL_ATTENDANCE_VIEW'),
]);
