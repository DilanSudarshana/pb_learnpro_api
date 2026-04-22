<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Models\UserMain;
use App\Models\UserDetails;
use App\Models\UserRole;
use App\Utils\JwtHelper;
use App\Utils\HttpClient;

class AuthController extends Controller
{
    private UserMain $userModel;
    private UserRole $roleModel;

    private UserDetails $detailModel;


    public function __construct()
    {
        $this->userModel = new UserMain();
        $this->roleModel = new UserRole();
        $this->detailModel = new UserDetails();
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
        $email    = trim($body['email'] ?? '');
        $password = trim($body['password'] ?? '');

        if (empty($email) || empty($password)) {
            $this->json(['message' => 'Email and password are required'], 400);
            return;
        }

        // ── Step 1: Check user in local DB ───────────────────────────────
        $user = $this->userModel->findActiveByEmail($email);

        if (!$user) {
            $this->json(['message' => 'Invalid credentials'], 401);
            return;
        }

        // ── Step 2: Verify password (IMPORTANT: hashed password check) ──
        if (!password_verify($password, $user['password'])) {
            $this->json(['message' => 'Invalid credentials'], 401);
            return;
        }

        // ── Step 3: Load role permissions ────────────────────────────────
        $roleId      = (int) ($user['role_id'] ?? 1);
        $permissions = $this->roleModel->getPermissionNames($roleId);

        // ── Step 4: Generate JWT ─────────────────────────────────────────
        $token = JwtHelper::generateToken($user, $permissions);

        $this->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'user_id'        => $user['user_id'],
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
                'user_id'             => $user['user_id'],
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

    /**
     * POST /api/auth/register
     *
     * 1. Validates email + password against external AD server.
     * 2. Blocks duplicate emails.
     * 3. Creates user_mains + user_details in one transaction.
     * 4. Returns JWT on success.
     *
     * Required body : email, password, first_name, last_name
     * Optional body : service_number, phone_no, nic, dob, address,
     *                 gender, marital_status, blood_group, department_id,
     *                 branch_id, employment_type, date_joined,
     *                 probation_end_date, basic_salary, bank_account_number,
     *                 tax_id, epf_no, manager_id, emergency_contact_name,
     *                 emergency_contact_relationship, emergency_contact_phone,
     *                 additional_details, role_id
     */
    public function register(): void
    {
        $body = $this->getBody();

        // ── Validate required fields ──────────────────────────────────────────
        $email     = trim($body['email']      ?? '');
        $password  = trim($body['password']   ?? '');
        $firstName = trim($body['first_name'] ?? '');
        $lastName  = trim($body['last_name']  ?? '');

        if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
            $this->json(['message' => 'email, password, first_name and last_name are required'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['message' => 'Invalid email address'], 400);
            return;
        }

        // ── Step 1: Verify against external AD server ─────────────────────────
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
            $this->json(['message' => 'Invalid credentials — not recognised by AD server'], 401);
            return;
        }

        $externalUser  = $response['body']['data'] ?? [];
        $externalEmail = $externalUser['email']    ?? $email;

        // ── Step 2: Block duplicate registrations ─────────────────────────────
        if ($this->userModel->findByEmail($externalEmail)) {
            $this->json(['message' => 'Email is already registered'], 409);
            return;
        }

        // ── Step 3: Create user_mains + user_details in one transaction ────────
        try {
            $userId = $this->userModel->createFullUser(
                // ── user_mains ─────────────────────────────────────────────────
                [
                    'email'          => $externalEmail,
                    'password'       => 'EXTERNAL_AUTH',
                    'service_number' => $externalUser['service_number'] ?? $body['service_number'] ?? null,
                    'role_id'        => (int) ($body['role_id'] ?? 1),
                    'is_active'      => 1,
                    'is_delete'      => 0,
                ],
                // ── user_details ───────────────────────────────────────────────
                [
                    'first_name'                     => $firstName,
                    'last_name'                      => $lastName,
                    'phone_no'                       => $body['phone_no']                       ?? null,
                    'nic'                            => $body['nic']                            ?? null,
                    'dob'                            => $body['dob']                            ?? null,
                    'address'                        => $body['address']                        ?? null,
                    'gender'                         => $body['gender']                         ?? null,
                    'marital_status'                 => $body['marital_status']                 ?? null,
                    'blood_group'                    => $body['blood_group']                    ?? null,
                    'department_id'                  => $body['department_id']                  ?? null,
                    'branch_id'                      => $body['branch_id']                      ?? null,
                    'employment_type'                => $body['employment_type']                ?? null,
                    'date_joined'                    => $body['date_joined']                    ?? date('Y-m-d'),
                    'probation_end_date'             => $body['probation_end_date']             ?? null,
                    'basic_salary'                   => $body['basic_salary']                   ?? null,
                    'bank_account_number'            => $body['bank_account_number']            ?? null,
                    'tax_id'                         => $body['tax_id']                         ?? null,
                    'epf_no'                         => $body['epf_no']                         ?? null,
                    'manager_id'                     => $body['manager_id']                     ?? null,
                    'emergency_contact_name'         => $body['emergency_contact_name']         ?? null,
                    'emergency_contact_relationship' => $body['emergency_contact_relationship'] ?? null,
                    'emergency_contact_phone'        => $body['emergency_contact_phone']        ?? null,
                    'additional_details'             => $body['additional_details']             ?? null,
                ]
            );
        } catch (\Throwable $e) {
            $this->json(['message' => 'Registration failed: ' . $e->getMessage()], 500);
            return;
        }

        // ── Step 4: Load created user with details join ───────────────────────
        $user = $this->userModel->getUserById($userId);

        if (!$user) {
            $this->json(['message' => 'Account created but failed to load user'], 500);
            return;
        }

        // ── Step 5: Load role permissions ─────────────────────────────────────
        $roleId      = (int) ($user['role_id'] ?? 1);
        $permissions = $this->roleModel->getPermissionNames($roleId);

        // ── Step 6: Generate JWT ───────────────────────────────────────────────
        $token = JwtHelper::generateToken($user, $permissions);

        $this->json([
            'message' => 'Registration successful',
            'token'   => $token,
            'user'    => [
                'user_id'        => $user['user_id'],
                'email'          => $user['email'],
                'service_number' => $user['service_number'] ?? null,
                'role_id'        => $user['role_id']         ?? null,
                'first_name'     => $user['first_name']      ?? null,
                'last_name'      => $user['last_name']        ?? null,
            ],
        ], 201);
    }
}
