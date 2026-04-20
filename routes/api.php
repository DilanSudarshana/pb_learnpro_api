<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * MAIN API ROUTER — pb_learnpro_db
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * This file only loads individual route group files.
 * Add new route files here as the project grows.
 *
 * $router is injected from public/index.php
 */

// Shared helpers (permissionMiddleware function, etc.)
require_once __DIR__ . '/helpers.php';

// Route groups — add new files here as the project grows
$routeFiles = [
    'authRoutes.php',
    'userRoutes.php',
    'roleRoutes.php',
    'permissionRoutes.php',
    'rolePermissionRoutes.php',
    'trainningCategoryRoutes.php',
    'trainingSessionRoutes.php',
];

foreach ($routeFiles as $file) {
    require_once __DIR__ . '/' . $file;
}
