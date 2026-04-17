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
     * Optional: Toggle user active status (for quick enable/disable).
     * PUT /api/users/{id}/toggle-status
     * Requires: USER_EDIT
     */
    public function toggleStatus(int $id): int
    {
        // Toggle directly in DB
        $stmt = $this->db->prepare("
        UPDATE user_mains 
        SET is_active = 1 - is_active 
        WHERE user_id = :id
    ");

        $stmt->execute(['id' => $id]);

        // If no row affected → user not found
        if ($stmt->rowCount() === 0) {
            return -1;
        }

        // Get updated status
        $stmt = $this->db->prepare("
        SELECT is_active FROM user_mains WHERE user_id = :id
    ");
        $stmt->execute(['id' => $id]);

        return (int)$stmt->fetchColumn();
    }
}
