<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Pure PHP JWT implementation (HS256)
 * No external library required.
 */
class JwtHelper
{
    private static function getSecret(): string
    {
        return $_ENV['JWT_SECRET'] ?? 'change_this_secret_in_env';
    }

    private static function getExpire(): int
    {
        return (int) ($_ENV['JWT_EXPIRE'] ?? 3600);
    }

    /**
     * Base64Url encode
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64Url decode
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }

    /**
     * Generate a JWT token
     *
     * @param array $user        User record array
     * @param array $permissions List of permission name strings
     */
    public static function generateToken(array $user, array $permissions = []): string
    {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $payload = self::base64UrlEncode(json_encode([
            'iss'            => $_ENV['APP_URL'] ?? 'learnpro-api',
            'iat'            => time(),
            'exp'            => time() + self::getExpire(),
            'user_id'        => $user['user_id'],
            'email'          => $user['email'],
            'role_id'        => $user['role_id']        ?? null,
            'service_number' => $user['service_number'] ?? null,
            'permissions'    => self::formatPermissions($permissions),  // ← structured
        ]));

        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", self::getSecret(), true)
        );

        return "{$header}.{$payload}.{$signature}";
    }

    // ── Helper ────────────────────────────────────────────────────────────────────

    private static function formatPermissions(array $perms): array
    {
        $result = [];

        foreach ($perms as $perm) {
            $perm = trim($perm);

            if (!str_contains($perm, '_')) {
                $result[strtolower($perm)]['enabled'] = true;
                continue;
            }

            // Split on FIRST underscore only
            // "USER_VIEW"          → module=user,    action=view
            // "PROFILE_MANAGEMENT" → module=profile, action=management
            [$module, $action] = explode('_', $perm, 2);

            $result[strtolower($module)][strtolower($action)] = true;
        }

        return $result;
    }

    /**
     * Verify and decode a JWT token
     *
     * @throws \RuntimeException on invalid or expired token
     */
    public static function verifyToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \RuntimeException('Invalid token format');
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", self::getSecret(), true)
        );

        if (!hash_equals($expectedSig, $signature)) {
            throw new \RuntimeException('Invalid token signature');
        }

        // Decode payload
        $data = json_decode(self::base64UrlDecode($payload), true);

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid token payload');
        }

        // Check expiry
        if (isset($data['exp']) && $data['exp'] < time()) {
            throw new \RuntimeException('Token has expired');
        }

        return $data;
    }

    public static function getAuthUserFromRequest(): array
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            throw new \RuntimeException('Authorization header missing');
        }

        $authHeader = $headers['Authorization'];

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new \RuntimeException('Invalid authorization header');
        }

        $token = trim(substr($authHeader, 7));

        $payload = self::verifyToken($token);

        // Normalize structure (VERY IMPORTANT FIX)
        return [
            'user_id'        => $payload['user_id'] ?? null,
            'email'          => $payload['email'] ?? null,
            'role_id'        => $payload['role_id'] ?? null,
            'service_number' => $payload['service_number'] ?? null,
            'permissions'    => $payload['permissions'] ?? [],
        ];
    }
}
