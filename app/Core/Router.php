<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    /**
     * Register a GET route
     */
    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        $this->routes[] = [
            'method'      => $method,
            'path'        => $path,
            'handler'     => $handler,
            'middlewares' => $middlewares,
            'pattern'     => $this->buildPattern($path),
        ];
    }

    /**
     * Convert route path like /users/{id} into regex
     */
    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch the current HTTP request
     */
    public function dispatch(): void
    {
        $method     = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base path if running in a subdirectory
        $requestUri = '/' . ltrim($requestUri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $requestUri, $matches)) {
                // Extract named params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Build middleware pipeline
                $handler = function () use ($route, $params) {
                    $this->callHandler($route['handler'], $params);
                };

                $pipeline = array_reduce(
                    array_reverse($route['middlewares']),
                    function ($carry, $middlewareClass) {
                        return function () use ($middlewareClass, $carry) {
                            $middleware = new $middlewareClass();
                            $middleware->handle($carry);
                        };
                    },
                    $handler
                );

                $pipeline();
                return;
            }
        }

        // No route matched
        http_response_code(404);
        echo json_encode(['message' => 'Route not found']);
    }

    private function callHandler(array|callable $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func($handler, $params);
            return;
        }

        [$controllerClass, $method] = $handler;
        $controller = new $controllerClass();
        $controller->$method($params);
    }
}
