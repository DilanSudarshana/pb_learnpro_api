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
    'Auth/authRoutes.php',
    'Users/userRoutes.php',
    'Users/userProfileRoutes.php', 
    'System/roleRoutes.php',
    'System/permissionRoutes.php',
    'System/rolePermissionRoutes.php',
    'Trainings/trainingCategoryRoutes.php',
    'Trainings/trainingSessionRoutes.php',
    'Trainings/trainingAllocationRoutes.php',
    'Trainings/userFeedbackRoutes.php',
    'LearningMaterials/learningMaterialRoutes.php',
    'Attendance/manualAttendanceRoutes.php',    
    
];

foreach ($routeFiles as $file) {
    require_once __DIR__ . '/' . $file;
}
