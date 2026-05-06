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

        $detailData  = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // ============================================================
        // BRANCH 1: application/json — base64 image from frontend
        // ============================================================
        if (str_contains($contentType, 'application/json')) {

            $body = json_decode(file_get_contents('php://input'), true) ?? [];

            // Collect allowed text fields
            foreach ($allowedDetailFields as $field) {
                if (isset($body[$field]) && trim((string)$body[$field]) !== '') {
                    $detailData[$field] = trim((string)$body[$field]);
                }
            }

            // ✅ Handle base64 profile picture
            if (!empty($body['profile_picture'])) {
                $base64Image = $body['profile_picture'];

                // Validate format: data:image/xxx;base64,...
                if (!preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $base64Image)) {
                    $this->json(['message' => 'Invalid image format. Please provide a valid base64 image (JPG, PNG, GIF).'], 400);
                    return;
                }

                // Strip prefix and decode with strict mode
                $base64Data = preg_replace('/^data:image\/(jpeg|jpg|png|gif);base64,/', '', $base64Image);
                $imageData  = base64_decode($base64Data, true);

                if ($imageData === false) {
                    $this->json(['message' => 'Invalid base64 image data. Decoding failed.'], 400);
                    return;
                }

                // ✅ Validate actual image bytes (prevent spoofed base64)
                $imageInfo = @getimagesizefromstring($imageData);
                if ($imageInfo === false) {
                    $this->json(['message' => 'Uploaded file is not a valid image.'], 400);
                    return;
                }

                // ✅ Whitelist MIME types
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($imageInfo['mime'], $allowedMimeTypes)) {
                    $this->json(['message' => 'Invalid image type. Only JPG, PNG, GIF are allowed.'], 400);
                    return;
                }

                // ✅ Check decoded byte size (2MB limit)
                if (strlen($imageData) > 2 * 1024 * 1024) {
                    $this->json(['message' => 'Image size exceeds 2MB limit.'], 400);
                    return;
                }

                // ✅ Store full base64 string directly in DB
                $detailData['profile_picture'] = $base64Image;
            }

            // ============================================================
            // BRANCH 2: multipart/form-data — legacy file upload fallback
            // ============================================================
        } else {

            foreach ($allowedDetailFields as $field) {
                if (isset($_POST[$field]) && trim($_POST[$field]) !== '') {
                    $detailData[$field] = trim($_POST[$field]);
                }
            }

            // ✅ Convert uploaded file to base64 for consistent DB storage
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $this->json(['message' => 'Invalid image type. JPG, PNG, GIF allowed.'], 400);
                    return;
                }

                if ($file['size'] > 2 * 1024 * 1024) {
                    $this->json(['message' => 'Image size exceeds 2MB limit.'], 400);
                    return;
                }

                // ✅ Validate actual image content
                $imageInfo = @getimagesize($file['tmp_name']);
                if ($imageInfo === false) {
                    $this->json(['message' => 'Uploaded file is not a valid image.'], 400);
                    return;
                }

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($imageInfo['mime'], $allowedMimeTypes)) {
                    $this->json(['message' => 'Invalid image type. Only JPG, PNG, GIF are allowed.'], 400);
                    return;
                }

                // ✅ Read and convert to base64 — same format as JSON branch
                $imageData   = file_get_contents($file['tmp_name']);
                $mimeType    = $imageInfo['mime'];
                $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

                $detailData['profile_picture'] = $base64Image;
            }
        }

        // ============================================================
        // GUARD: nothing to update
        // ============================================================
        if (empty($detailData)) {
            $this->json(['message' => 'No updatable fields provided.'], 400);
            return;
        }

        $detailData['updatedAt'] = date('Y-m-d H:i:s');

        $detailUpdated = $this->model->updateUserDetails($userId, $detailData);

        if (!$detailUpdated) {
            $this->json(['message' => 'Failed to update profile.'], 500);
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
