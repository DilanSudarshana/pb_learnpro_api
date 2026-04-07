<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\UserPermission;

/**
 * PermissionMiddleware — checks that the authenticated user's role
 * has a specific permission before allowing access.
 *
 * Usage in routes:
 *   new PermissionMiddleware('PROFILE_MANAGEMENT')
 *
 * NOTE: AuthMiddleware MUST run before this middleware.
 */
class PermissionMiddleware
{
    private string $requiredPermission;

    public function __construct(string $requiredPermission)
    {
        $this->requiredPermission = $requiredPermission;
    }

    public function handle(callable $next): void
    {
        $user = $_REQUEST['auth_user'] ?? null;

        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthenticated']);
            return;
        }

        // Fast path: permission already encoded in token payload
        $tokenPermissions = $user['role_permissions'] ?? [];
        if (in_array($this->requiredPermission, $tokenPermissions, true)) {
            $next();
            return;
        }

        // Fallback: query the DB
        $roleId = (int) ($user['role_id'] ?? 0);

        if ($roleId === 0) {
            http_response_code(403);
            echo json_encode(['message' => 'Access denied. No role assigned.']);
            return;
        }

        $permissionModel = new UserPermission();
        $hasPermission   = $permissionModel->roleHasPermission($roleId, $this->requiredPermission);

        if (!$hasPermission) {
            http_response_code(403);
            echo json_encode([
                'message'    => 'Access denied. Permission not granted.',
                'required'   => $this->requiredPermission,
            ]);
            return;
        }

        $next();
    }
}
