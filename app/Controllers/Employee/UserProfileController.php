<?php

declare(strict_types=1);

namespace App\Controllers\Employee;

use App\Core\Controller;
use App\Models\UserProfile;
use App\Utils\JwtHelper;

/**
 * UserProfileController — Show and update authenticated user's profile.
 */
class UserProfileController extends Controller
{
    private UserProfile $model;

    public function __construct()
    {
        $this->model = new UserProfile();
    }

    /**
     * GET /api/profile
     * Returns the authenticated user's full profile.
     */
    public function show(): void
    {
        $authUser = JwtHelper::getAuthUserFromRequest();

        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized'], 401);
            return;
        }

        $profile = $this->model->getProfile((int) $authUser['user_id']);

        if (!$profile) {
            $this->json(['message' => 'Profile not found'], 404);
            return;
        }

        $this->json(['message' => 'Profile retrieved', 'data' => $profile]);
    }

    /**
     * PUT /api/profile
     * Updates the authenticated user's personal & contact details.
     * Body: { first_name?, last_name?, phone_no?, nic?, dob?, address?,
     *         gender?, marital_status?, blood_group?,
     *         emergency_contact_name?, emergency_contact_relationship?,
     *         emergency_contact_phone?, additional_details? }
     */
    public function update(): void
    {
        $authUser = JwtHelper::getAuthUserFromRequest();

        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized'], 401);
            return;
        }

        $userId = (int) $authUser['user_id'];

        $allowedDetailFields = [
            'first_name',
            'last_name',
            'phone_no',
            'bio',
        ];

        $detailData = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            // ✅ JSON body (no file upload)
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            foreach ($allowedDetailFields as $field) {
                if (isset($body[$field]) && trim((string)$body[$field]) !== '') {
                    $detailData[$field] = trim((string)$body[$field]);
                }
            }
        } else {
            // ✅ multipart/form-data (with or without file)
            foreach ($allowedDetailFields as $field) {
                if (isset($_POST[$field]) && trim($_POST[$field]) !== '') {
                    $detailData[$field] = trim($_POST[$field]);
                }
            }
        }

        // ✅ Handle profile picture upload from $_FILES
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['profile_picture'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed)) {
                $this->json(['message' => 'Invalid image type. JPG, PNG, GIF allowed.'], 400);
                return;
            }

            if ($file['size'] > 2 * 1024 * 1024) {
                $this->json(['message' => 'Image size exceeds 2MB limit.'], 400);
                return;
            }

            $uploadDir  = __DIR__ . '/../../public/uploads/profiles/';
            $fileName   = uniqid('profile_', true) . '.' . $ext;
            $uploadPath = $uploadDir . $fileName;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $this->json(['message' => 'Failed to upload profile picture.'], 500);
                return;
            }

            $detailData['profile_picture'] = $fileName;
        }

        if (empty($detailData)) {
            $this->json(['message' => 'No updatable fields provided'], 400);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $detailData['updatedAt'] = $now;

        $detailUpdated = $this->model->updateUserDetails($userId, $detailData);

        if (!$detailUpdated) {
            $this->json(['message' => 'Failed to update profile'], 500);
            return;
        }

        $profile = $this->model->getProfile($userId);

        $this->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'data'    => $profile
        ]);
    }

    public function changePassword(): void
    {
        $authUser = JwtHelper::getAuthUserFromRequest();

        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized'], 401);
            return;
        }

        $userId = (int) $authUser['user_id'];
        $body   = $this->getBody();

        // Validate required fields
        if (empty($body['current_password']) || empty($body['new_password']) || empty($body['confirm_password'])) {
            $this->json(['message' => 'current_password, new_password and confirm_password are required'], 400);
            return;
        }

        // Check new password and confirm match
        if ($body['new_password'] !== $body['confirm_password']) {
            $this->json(['message' => 'new_password and confirm_password do not match'], 400);
            return;
        }

        // Enforce minimum password strength
        if (strlen($body['new_password']) < 8) {
            $this->json(['message' => 'new_password must be at least 8 characters'], 400);
            return;
        }

        // Fetch current hashed password from DB
        $user = $this->model->getUserById($userId);

        if (!$user) {
            $this->json(['message' => 'User not found'], 404);
            return;
        }

        // Verify current password
        if (!password_verify($body['current_password'], $user['password'])) {
            $this->json(['message' => 'Current password is incorrect'], 401);
            return;
        }

        // Hash the new password
        $hashedPassword = password_hash($body['new_password'], PASSWORD_BCRYPT);

        $updated = $this->model->updatePassword($userId, $hashedPassword);

        if (!$updated) {
            $this->json(['message' => 'Failed to update password'], 500);
            return;
        }

        $this->json(['message' => 'Password changed successfully'], 200);
    }
}
