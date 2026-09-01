<?php

declare(strict_types=1);

namespace App\Middleware;

class SecurityHeadersMiddleware
{
    public function handle(callable $next): mixed
    {
        // Enviar encabezados de seguridad OWASP (§ 6.4)
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header("Permissions-Policy: geolocation=(), microphone=(), camera=(self)");
        }

        return $next();
    }
}
