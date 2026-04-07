<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * Send a JSON response
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get JSON request body as array
     */
    protected function getBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get a single field from the request body
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        $body = $this->getBody();
        return $body[$key] ?? $_POST[$key] ?? $default;
    }
}
