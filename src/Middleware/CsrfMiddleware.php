<?php

declare(strict_types=1);

namespace App\Middleware;

use RuntimeException;

class CsrfMiddleware
{
    public function handle(callable $next): mixed
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Solo validar en peticiones mutables (POST, PUT, DELETE, PATCH)
        if (in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $token = $_POST['_csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $sessionToken = $_SESSION['_csrf_token'] ?? $_SESSION['csrf_token'] ?? '';

            if (empty($sessionToken) || empty($token) || !hash_equals($sessionToken, (string)$token)) {
                http_response_code(419);
                if (file_exists(__DIR__ . '/../../resources/views/pages/errors/419.php')) {
                    require __DIR__ . '/../../resources/views/pages/errors/419.php';
                    exit;
                }
                die("Token de seguridad CSRF inválido o expirado. Por favor recargá la página.");
            }
        }

        return $next();
    }
}
