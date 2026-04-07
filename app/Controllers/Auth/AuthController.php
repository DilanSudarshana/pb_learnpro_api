<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Models\UserMain;
use App\Models\UserRole;
use App\Utils\JwtHelper;
use App\Utils\HttpClient;

class AuthController extends Controller
{
    private UserMain $userModel;
    private UserRole $roleModel;

    public function __construct()
    {
        $this->userModel = new UserMain();
        $this->roleModel = new UserRole();
    }

    /**
     * POST /api/auth/login
     *
     * Validates credentials against an external auth server,
     * auto-registers new users locally, and returns a JWT.
     */
    public function login(): void
    {
        $body     = $this->getBody();
        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        if (empty($email) || empty($password)) {
            $this->json(['message' => 'Email and password are required'], 400);
            return;
        }

        // ── Step 1: Call external auth server ────────────────────────────────
        $externalUrl = $_ENV['EXTERNAL_AUTH_URL'] ?? 'http://localhost:3000/ad-request/login';

        try {
            $response = HttpClient::postJson($externalUrl, [
                'email'    => $email,
                'password' => $password,
            ]);
        } catch (\RuntimeException $e) {
            $this->json(['message' => 'Authentication server unreachable: ' . $e->getMessage()], 503);
            return;
        }

        if (($response['body']['status'] ?? '') !== 'success') {
            $this->json(['message' => 'Invalid credentials'], 401);
            return;
        }

        $externalUser  = $response['body']['data'] ?? [];
        $externalEmail = $externalUser['email'] ?? $email;

        // ── Step 2: Find or auto-register user ───────────────────────────────
        $user = $this->userModel->findActiveByEmail($externalEmail);

        if (!$user) {
            $userId = $this->userModel->createUser([
                'email'          => $externalEmail,
                'password'       => 'EXTERNAL_AUTH',
                'service_number' => $externalUser['service_number'] ?? null,
                'role_id'        => 1, // default role
                'is_active'      => 1,
                'is_delete'      => 0,
            ]);
            $user = $this->userModel->find($userId);
        }

        if (!$user) {
            $this->json(['message' => 'Failed to load user account'], 500);
            return;
        }

        // ── Step 3: Load role permissions ────────────────────────────────────
        $roleId      = (int) ($user['role_id'] ?? 1);
        $permissions = $this->roleModel->getPermissionNames($roleId);

        // ── Step 4: Generate JWT ─────────────────────────────────────────────
        $token = JwtHelper::generateToken($user, $permissions);

        $this->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id'             => $user['id'],
                'email'          => $user['email'],
                'service_number' => $user['service_number'] ?? null,
                'role_id'        => $user['role_id'] ?? null,
            ],
        ]);
    }

    /**
     * GET /api/auth/me
     * Returns authenticated user data from the JWT payload.
     */
    public function me(): void
    {
        $user = $_REQUEST['auth_user'] ?? null;

        if (!$user) {
            $this->json(['message' => 'Unauthenticated'], 401);
            return;
        }

        $this->json([
            'message' => 'Authenticated user',
            'user'    => [
                'id'             => $user['user_id'],
                'email'          => $user['email'],
                'role_id'        => $user['role_id'],
                'service_number' => $user['service_number'] ?? null,
                'permissions'    => $user['role_permissions'] ?? [],
            ],
        ]);
    }

    /**
     * GET /api/auth/profile
     * Protected by PROFILE_MANAGEMENT permission (see routes).
     */
    public function profile(): void
    {
        $user = $_REQUEST['auth_user'];

        $this->json([
            'message' => 'You have PROFILE_MANAGEMENT permission',
            'user'    => $user,
        ]);
    }
}
