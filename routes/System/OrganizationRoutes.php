<?php

declare(strict_types=1);

/**
 * Organization Routes - Branch & Department (GET Only)
 * 
 * Controller: App\Controllers\OrganizationController
 */

use App\Controllers\Master\OrganizationController;
use App\Middleware\AuthMiddleware;

// Branch Routes
$router->get('/api/branches', [OrganizationController::class, 'getAllBranches'], [
    AuthMiddleware::class
]);

$router->get('/api/branches/{id}', [OrganizationController::class, 'getBranchById'], [
    AuthMiddleware::class
]);

// Department Routes
$router->get('/api/departments', [OrganizationController::class, 'getAllDepartments'], [
    AuthMiddleware::class
]);

$router->get('/api/departments/{id}', [OrganizationController::class, 'getDepartmentById'], [
    AuthMiddleware::class
]);
