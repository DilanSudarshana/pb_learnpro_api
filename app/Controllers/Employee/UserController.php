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

    public function __construct()
    {
        $this->userMain    = new UserMain();
        $this->userDetails = new UserDetails();
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
    public function show(int $id): void
    {
        $user = $this->userMain->getUserById($id);

        if (!$user) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
            return;
        }

        // Remove password from response (getUserById doesn't select it,
        // but guard here as a safety net).
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
    public function update(int $id): void
    {
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

        // ── Fields that belong to user_mains ─────────────────────────────────
        $mainAllowed  = ['service_number', 'role_id', 'is_active'];
        $mainData     = array_intersect_key($body, array_flip($mainAllowed));

        // ── Fields that belong to user_details ───────────────────────────────
        // Everything except the user_mains-specific fields (and id/passwords).
        $detailDenied = array_merge($mainAllowed, ['user_id', 'password', 'email']);
        $detailData   = array_diff_key($body, array_flip($detailDenied));

        $mainUpdated   = false;
        $detailUpdated = false;
        $errors        = [];

        if (!empty($mainData)) {
            $mainUpdated = $this->userMain->updateUserMain($id, $mainData);
            if (!$mainUpdated) {
                $errors[] = 'Failed to update user main record.';
            }
        }

        if (!empty($detailData)) {
            $detailUpdated = $this->userDetails->updateByUserMainId($id, $detailData);
            if (!$detailUpdated) {
                $errors[] = 'Failed to update user detail record.';
            }
        }

        if (!empty($errors)) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => implode(' ', $errors),
            ], 500);
            return;
        }

        if (!$mainUpdated && !$detailUpdated) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => 'No valid fields provided to update.',
            ], 400);
            return;
        }

        // Return the freshly updated user record.
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
}
