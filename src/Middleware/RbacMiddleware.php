<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AuthProviderInterface;

class RbacMiddleware
{
    public function __construct(private AuthProviderInterface $authProvider) {}

    public function handle(callable $next, array $allowedRoles = []): mixed
    {
        $user = $this->authProvider->user();

        if (!$user) {
            header('Location: /login');
            exit;
        }

        if (!empty($allowedRoles) && !in_array($user->rol, $allowedRoles, true)) {
            http_response_code(403);
            if (file_exists(__DIR__ . '/../../resources/views/pages/errors/403.php')) {
                require __DIR__ . '/../../resources/views/pages/errors/403.php';
                exit;
            }
            die("Acceso denegado. No tenés permisos suficientes para realizar esta acción.");
        }

        return $next();
    }
}
