<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Autoloader (Composer or manual)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    // Manual PSR-4 autoloader fallback
    spl_autoload_register(function (string $class) {
        $prefix = 'App\\';
        $baseDir = BASE_PATH . '/app/';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}

// Load environment variables
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// CORS Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Bootstrap the router
$router = new App\Core\Router();
require_once BASE_PATH . '/routes/api.php';
$router->dispatch();
