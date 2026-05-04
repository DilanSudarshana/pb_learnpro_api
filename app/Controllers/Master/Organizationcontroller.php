<?php

declare(strict_types=1);

namespace App\Controllers\Master;

use App\Models\BranchModel;
use App\Models\DepartmentModel;

class OrganizationController
{
    private BranchModel $branchModel;
    private DepartmentModel $departmentModel;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
        $this->departmentModel = new DepartmentModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BRANCH GET FUNCTIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/branches
     * Returns all branches
     */
    public function getAllBranches(): void
    {
        $branches = $this->branchModel->getAllBranches();

        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Branches retrieved successfully.',
            'data'    => $branches,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * GET /api/branches/{id}
     * Returns a single branch by ID
     */
    public function getBranchById(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Invalid branch ID.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $branch = $this->branchModel->getBranchById($id);

        if (!$branch) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Branch not found.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Branch retrieved successfully.',
            'data'    => $branch,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DEPARTMENT GET FUNCTIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/departments
     * Returns all departments
     */
    public function getAllDepartments(): void
    {
        $departments = $this->departmentModel->getAllDepartments();

        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Departments retrieved successfully.',
            'data'    => $departments,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * GET /api/departments/{id}
     * Returns a single department by ID
     */
    public function getDepartmentById(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Invalid department ID.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $department = $this->departmentModel->getDepartmentById($id);

        if (!$department) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Department not found.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Department retrieved successfully.',
            'data'    => $department,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
