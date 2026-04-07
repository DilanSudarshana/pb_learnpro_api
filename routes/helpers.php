<?php

declare(strict_types=1);

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * ROUTE HELPERS
 * ─────────────────────────────────────────────────────────────────────────────
 * Loaded once by api.php before any route file.
 * Provides permissionMiddleware() to every route group.
 */

use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;

/**
 * Returns a dynamically-generated class name that extends PermissionMiddleware
 * with a hard-coded constructor arg — so the Router can do `new $class()`.
 *
 * Example:
 *   permissionMiddleware('USER_VIEW')
 *   → returns 'DynamicPermission_USER_VIEW'
 */
if (!function_exists('permissionMiddleware')) {
    function permissionMiddleware(string $permission): string
    {
        static $cache = [];

        if (!isset($cache[$permission])) {
            $className = 'DynamicPermission_' . preg_replace('/[^a-zA-Z0-9]/', '_', $permission);

            if (!class_exists($className)) {
                eval("
                    class {$className} extends App\\Middleware\\PermissionMiddleware {
                        public function __construct() {
                            parent::__construct('{$permission}');
                        }
                    }
                ");
            }

            $cache[$permission] = $className;
        }

        return $cache[$permission];
    }
}
