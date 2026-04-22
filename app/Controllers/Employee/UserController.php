<?php

declare(strict_types=1);

namespace App\Controllers\Employee;

use App\Core\Controller;
use App\Models\UserMain;
use App\Models\UserDetails;

class UserController extends Controller
{
    private UserMain    $userMain;
    private UserDetails $userDetails;
    private $db;

    public function __construct()
    {
        $this->userMain    = new UserMain();
        $this->userDetails = new UserDetails();
        $this->db = \App\Core\Database::getInstance();
    }

    // -------------------------------------------------------------------------
    // GET /api/users
    // Returns all non-deleted users (user_mains + user_details, no passwords).
    // -------------------------------------------------------------------------
    public function index(): void
    {
        $users = $this->userMain->getAllUsers();

        $this->jsonResponse([
            'status'  => 'success',
            'message' => 'Users retrieved successfully.',
            'data'    => $users,
        ], 200);
    }

    // -------------------------------------------------------------------------
    // GET /api/users/{id}
    // Returns a single user with both user_mains and user_details fields.
    // -------------------------------------------------------------------------
    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Invalid user ID.',
            ], 400);
            return;
        }

        $user = $this->userMain->getUserById($id);

        if (!$user) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
            return;
        }

        // Safety (good practice)
        unset($user['password']);

        $this->jsonResponse([
            'status'  => 'success',
            'message' => 'User retrieved successfully.',
            'data'    => $user,
        ], 200);
    }

    // -------------------------------------------------------------------------
    // PUT /api/users/{id}
    // Updates user_mains fields (service_number, role_id, is_active) and/or
    // any user_details fields supplied in the request body.
    // -------------------------------------------------------------------------
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Invalid user ID.',
            ], 400);
            return;
        }

        // Make sure the user actually exists and isn't deleted.
        $existing = $this->userMain->getUserById($id);

        if (!$existing) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
            return;
        }

        $body = $this->getRequestBody();

        if (empty($body)) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Request body is empty.',
            ], 400);
            return;
        }

        // user_mains fields 
        $mainAllowed = ['service_number', 'role_id', 'is_active'];
        $mainData    = array_intersect_key($body, array_flip($mainAllowed));

        // user_details fields 
        $detailDenied = array_merge($mainAllowed, ['user_id', 'password', 'email']);
        $detailData   = array_diff_key($body, array_flip($detailDenied));

        $mainUpdated   = false;
        $detailUpdated = false;
        $errors        = [];

        // OPTIONAL: transaction (recommended)
        $this->db->beginTransaction();

        try {

            if (!empty($mainData)) {
                $mainUpdated = $this->userMain->updateUserMain($id, $mainData);
                if (!$mainUpdated) {
                    throw new \Exception('Failed to update user main record.');
                }
            }

            if (!empty($detailData)) {
                $detailUpdated = $this->userDetails->updateByUserMainId($id, $detailData);
                if (!$detailUpdated) {
                    throw new \Exception('Failed to update user detail record.');
                }
            }

            if (!$mainUpdated && !$detailUpdated) {
                throw new \Exception('No valid fields provided to update.');
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();

            $this->jsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
            return;
        }

        // Return updated user
        $updated = $this->userMain->getUserById($id);
        unset($updated['password']);

        $this->jsonResponse([
            'status'  => 'success',
            'message' => 'User updated successfully.',
            'data'    => $updated,
        ], 200);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/users/{id}
    // Soft-deletes: sets is_delete = 1 and is_active = 0 on both tables.
    // -------------------------------------------------------------------------
    public function destroy(int $id): void
    {
        $existing = $this->userMain->getUserById($id);

        if (!$existing) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
            return;
        }

        // Soft-delete user_mains row.
        $mainDeleted = $this->userMain->updateUserMain($id, [
            'is_delete' => 1,
            'is_active' => 0,
        ]);

        // Soft-delete user_details row via the FK (user_main_id = $id).
        $detailDeleted = $this->userDetails->softDelete($id);

        // softDelete() targets user_details.user_id; if that PK differs from
        // user_main_id, use updateByUserMainId instead:
        // $detailDeleted = $this->userDetails->updateByUserMainId($id, [
        //     'is_delete' => 1,
        //     'is_active' => 0,
        // ]);

        if (!$mainDeleted) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to delete user.',
            ], 500);
            return;
        }

        $this->jsonResponse([
            'status'  => 'success',
            'message' => 'User deleted successfully.',
        ], 200);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Decode JSON from the raw request body.
     */
    private function getRequestBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Send a JSON response and terminate.
     */
    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * PATCH /api/users/{id}/toggle-status
     * Toggles the user's active status (is_active).
     * Requires: USER_EDIT
     */
    public function toggleStatus(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            return;
        }

        $result = $this->userMain->toggleStatus($id);

        if ($result === -1) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'success'   => true,
            'message'   => 'User status updated successfully',
            'is_active' => $result,
        ]);
    }

    /**
     * POST /api/users
     * Create a new user (user_mains + user_details)
     * Requires: USER_CREATE
     */
    public function createUser(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
            return;
        }

        // ── Required fields ──────────────────────────────────────────────
        $required = ['email', 'password', 'service_number', 'role_id', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                return;
            }
        }

        // ── Duplicate email check ────────────────────────────────────────
        if ($this->userMain->findByEmail($body['email'])) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email already in use']);
            return;
        }

        // ── Duplicate service number check ───────────────────────────────
        if ($this->userMain->findByServiceNumber($body['service_number'])) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Service number already in use'
            ]);
            return;
        }

        // ── Split payload ────────────────────────────────────────────────
        $mainFields = ['email', 'password', 'service_number', 'role_id'];
        $mainData   = array_intersect_key($body, array_flip($mainFields));

        $detailFields = [
            'first_name',
            'last_name',
            'phone_no',
            'profile_picture',
            'bio',
            'role_id',
            'department_id',
            'branch_id',
            'date_joined',
            'is_active',
            'is_delete',
            'is_online',
            'created_at',
            'updated_at',
        ];
        $detailData = array_intersect_key($body, array_flip($detailFields));

        // ── Create ───────────────────────────────────────────────────────
        try {
            $userId = $this->userMain->createFullUser($mainData, $detailData);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'User creation failed',
                'error'   => $e->getMessage(),
            ]);
            return;
        }

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $userId,
        ]);
    }
}
