<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AuthProviderInterface;

class AuthMiddleware
{
    public function __construct(private AuthProviderInterface $authProvider) {}

    public function handle(callable $next): mixed
    {
        if (!$this->authProvider->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';
            header('Location: /login');
            exit;
        }

        return $next();
    }
}
