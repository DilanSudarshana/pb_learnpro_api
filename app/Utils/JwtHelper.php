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
            'iss'              => $_ENV['APP_URL'] ?? 'learnpro-api',
            'iat'              => time(),
            'exp'              => time() + self::getExpire(),
            'user_id'          => $user['id'],
            'email'            => $user['email'],
            'role_id'          => $user['role_id'] ?? null,
            'service_number'   => $user['service_number'] ?? null,
            'role_permissions' => $permissions,
        ]));

        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", self::getSecret(), true)
        );

        return "{$header}.{$payload}.{$signature}";
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
}
