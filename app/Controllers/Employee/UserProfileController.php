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
        $body   = $this->getBody();

        // Fields belonging to user_mains
        $mainFields = [
            'email',
            'password',
            'service_number',
            'role_id',
            'is_active',
        ];

        // Fields belonging to user_details
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
            'updated_at'
        ];

        // Separate incoming body fields into their respective table buckets
        $mainData   = [];
        $detailData = [];

        foreach ($body as $key => $value) {
            if (in_array($key, $mainFields, true)) {
                $mainData[$key] = $value;
            } elseif (in_array($key, $detailFields, true)) {
                $detailData[$key] = $value;
            }
        }

        if (empty($mainData) && empty($detailData)) {
            $this->json(['message' => 'No updatable fields provided'], 400);
            return;
        }

        $now = date('Y-m-d H:i:s');

        // Inject timestamps
        if (!empty($mainData)) {
            $mainData['updated_at'] = $now;          // user_mains uses snake_case
        }
        if (!empty($detailData)) {
            $detailData['updatedAt'] = $now;          // user_details uses camelCase
        }

        // Run both updates
        $mainUpdated   = !empty($mainData)   ? $this->model->updateUserMain($userId, $mainData)     : true;
        $detailUpdated = !empty($detailData) ? $this->model->updateUserDetails($userId, $detailData) : true;

        if (!$mainUpdated || !$detailUpdated) {
            $this->json(['message' => 'Failed to update profile'], 500);
            return;
        }

        $profile = $this->model->getProfile($userId);

        $this->json(['message' => 'Profile updated successfully', 'data' => $profile]);
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
