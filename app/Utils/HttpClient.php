<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Simple HTTP client using cURL
 */
class HttpClient
{
    /**
     * POST JSON to a URL and return decoded response
     *
     * @return array{status: int, body: array}
     */
    public static function postJson(string $url, array $data): array
    {
        $ch = curl_init($url);

        $payload = json_encode($data);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response   = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error      = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            throw new \RuntimeException('HTTP request failed: ' . $error);
        }

        $body = json_decode($response, true);

        return [
            'status' => $statusCode,
            'body'   => is_array($body) ? $body : [],
        ];
    }
}
