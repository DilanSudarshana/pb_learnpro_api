<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\JwtHelper;

/**
 * AuthMiddleware — verifies Bearer JWT on every protected route.
 * Attaches decoded token payload to $_REQUEST['auth_user'].
 */
class AuthMiddleware
{
    public function handle(callable $next): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Also check Apache-specific header
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers    = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? '';
        }

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization token required']);
            return;
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JwtHelper::verifyToken($token);

            // Store decoded user in a global so controllers can access it
            $_REQUEST['auth_user'] = $decoded;

            $next();
        } catch (\RuntimeException $e) {
            http_response_code(403);
            echo json_encode(['message' => 'Invalid or expired token']);
            return;
        }
    }
}
