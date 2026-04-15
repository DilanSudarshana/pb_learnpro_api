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
                'user_id'             => $user['user_id'],
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
     * Creates a user_mains row + a user_details row in one transaction.
     * Password is hashed with bcrypt before storage.
     *
     * Required body fields : email, password, first_name, last_name
     * Optional body fields : service_number, phone_no, nic, dob, address,
     *                        gender, department_id, branch_id, role_id, ...
     */
    public function register(): void
    {
        $body = $this->getBody();

        # Validate required fields 
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

        # Check for duplicate email
        if ($this->userModel->findByEmail($email)) {
            $this->json(['message' => 'Email is already registered'], 409);
            return;
        }

        # Create user_mains row
        $userId = $this->userModel->createUser([
            'email'          => $email,
            'password'       => password_hash($password, PASSWORD_BCRYPT),
            'service_number' => $body['service_number'] ?? null,
            'role_id'        => (int) ($body['role_id'] ?? 1),
            'is_active'      => 1,
            'is_delete'      => 0,
        ]);

        if (!$userId) {
            $this->json(['message' => 'Failed to create user account'], 500);
            return;
        }

        # Create user_details row 
        $detailFields = [
            'user_id'                   => $userId,
            'first_name'                     => $firstName,
            'last_name'                      => $lastName,
            'phone_no'                       => $body['phone_no']       ?? null,
            'nic'                            => $body['nic']            ?? null,
            'dob'                            => $body['dob']            ?? null,
            'address'                        => $body['address']        ?? null,
            'gender'                         => $body['gender']         ?? null,
            'marital_status'                 => $body['marital_status'] ?? null,
            'blood_group'                    => $body['blood_group']    ?? null,
            'department_id'                  => $body['department_id']  ?? null,
            'branch_id'                      => $body['branch_id']      ?? null,
            'employment_type'                => $body['employment_type'] ?? null,
            'date_joined'                    => $body['date_joined']    ?? date('Y-m-d'),
            'probation_end_date'             => $body['probation_end_date'] ?? null,
            'basic_salary'                   => $body['basic_salary']   ?? null,
            'bank_account_number'            => $body['bank_account_number'] ?? null,
            'tax_id'                         => $body['tax_id']         ?? null,
            'epf_no'                         => $body['epf_no']         ?? null,
            'manager_id'                     => $body['manager_id']     ?? null,
            'emergency_contact_name'         => $body['emergency_contact_name'] ?? null,
            'emergency_contact_relationship' => $body['emergency_contact_relationship'] ?? null,
            'emergency_contact_phone'        => $body['emergency_contact_phone'] ?? null,
            'additional_details'             => $body['additional_details'] ?? null,
        ];

        $detailId = $this->detailModel->createDetail($detailFields);

        // ── Return created user (no password) ────────────────────────────────
        $this->json([
            'message' => 'User registered successfully',
            'user'    => [
                'user_id'        => $userId,
                'email'          => $email,
                'service_number' => $body['service_number'] ?? null,
                'role_id'        => (int) ($body['role_id'] ?? 1),
                'first_name'     => $firstName,
                'last_name'      => $lastName,
            ],
        ], 201);
    }
}
