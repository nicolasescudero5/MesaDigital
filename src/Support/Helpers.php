<?php

declare(strict_types=1);

use App\Models\Usuario;

if (!function_exists('e')) {
    /**
     * Escapa caracteres especiales para prevenir XSS en salida HTML
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Obtiene o genera el token CSRF para la sesión activa
     */
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['_csrf_token']) && empty($_SESSION['csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $_SESSION['_csrf_token'];
        } elseif (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = $_SESSION['csrf_token'];
        } elseif (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = $_SESSION['_csrf_token'];
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Genera el input oculto con el token CSRF
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }
}

if (!function_exists('fmt_num')) {
    /**
     * Formatea números con separador de miles punto (es-AR: 1.655)
     */
    function fmt_num(int|float $number, int $decimals = 0): string
    {
        return number_format($number, $decimals, ',', '.');
    }
}

if (!function_exists('fmt_date')) {
    /**
     * Formatea fechas a formato argentino DD/MM/AAAA
     */
    function fmt_date(?string $dateString): string
    {
        if (!$dateString) {
            return '—';
        }
        $time = strtotime($dateString);
        return $time ? date('d/m/Y', $time) : $dateString;
    }
}

if (!function_exists('fmt_datetime')) {
    /**
     * Formatea fecha y hora a DD/MM/AAAA HH:mm
     */
    function fmt_datetime(?string $datetimeString): string
    {
        if (!$datetimeString) {
            return '—';
        }
        $time = strtotime($datetimeString);
        return $time ? date('d/m/Y H:i', $time) : $datetimeString;
    }
}

if (!function_exists('app_url')) {
    /**
     * Retorna la URL de una ruta (opcionalmente absoluta con esquema y host)
     */
    function app_url(string $path = '', bool $absolute = false): string
    {
        if (defined('URL_BASE') && URL_BASE !== '') {
            $base = rtrim(URL_BASE, '/');
        } else {
            $base = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8080', '/');
        }

        if ($absolute && !str_starts_with($base, 'http://') && !str_starts_with($base, 'https://')) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = "{$scheme}://{$host}" . ($base !== '' ? (str_starts_with($base, '/') ? $base : "/{$base}") : '');
        }

        $path = ltrim($path, '/');
        return $path ? "{$base}/{$path}" : $base;
    }
}

if (!function_exists('portal_url')) {
    /**
     * Retorna la URL del Portal institucional del tenant actual
     */
    function portal_url(string $path = ''): string
    {
        $tenantSubdomain = '';
        if (defined('RESOLVED_SUBDOMAIN') && RESOLVED_SUBDOMAIN) {
            $tenantSubdomain = RESOLVED_SUBDOMAIN;
        } elseif (class_exists('\TenantContext') && \TenantContext::hasCurrentTenant()) {
            $tenantSubdomain = \TenantContext::getCurrentTenant()->subdomain;
        } elseif (!empty($_SESSION['user_tenant'])) {
            $tenantSubdomain = $_SESSION['user_tenant'];
        } elseif (defined('URL_BASE') && URL_BASE !== '') {
            $parts = explode('/', trim(URL_BASE, '/'));
            if (count($parts) >= 2 && end($parts) === 'mesa') {
                $tenantSubdomain = $parts[count($parts) - 2];
            }
        }

        $globalRoot = defined('URLROOT_GLOBAL') && URLROOT_GLOBAL !== '' ? URLROOT_GLOBAL : '';
        if ($globalRoot === '') {
            $reqUri = $_SERVER['REQUEST_URI'] ?? '/';
            if (str_starts_with($reqUri, '/nuevo_portal')) {
                $globalRoot = '/nuevo_portal';
            } else {
                $globalRoot = '';
            }
        }

        $base = rtrim($globalRoot, '/');
        if ($tenantSubdomain !== '') {
            $base .= '/' . trim($tenantSubdomain, '/');
        }

        $path = ltrim($path, '/');
        return $path ? "{$base}/{$path}" : ($base ? "{$base}/" : '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirige a una ruta interna respetando URL_BASE
     */
    function redirect(string $path): void
    {
        $url = app_url($path);
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('asset_url')) {
    /**
     * Retorna la URL de un asset estático
     */
    function asset_url(string $path): string
    {
        return app_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('current_user')) {
    /**
     * Obtiene el usuario autenticado desde el contenedor o sesión
     */
    function current_user(): ?Usuario
    {
        global $container;
        if ($container && $container->has(\App\Auth\AuthProviderInterface::class)) {
            return $container->get(\App\Auth\AuthProviderInterface::class)->currentUser();
        }
        return null;
    }
}

if (!function_exists('old')) {
    /**
     * Retorna valor previo de formulario o valor por defecto
     */
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    /**
     * Establece o recupera un mensaje flash de sesión
     */
    function flash(?string $key = null, ?string $message = null, string $type = 'success'): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($key !== null && $message !== null) {
            $_SESSION['_flash'][$key] = [
                'message' => $message,
                'type' => $type
            ];
            return null;
        }

        if ($key !== null) {
            $val = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $val;
        }

        $all = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
        return $all;
    }
}

if (!function_exists('render_status_pill')) {
    /**
     * Renderiza la píldora de estado de documento según la Guía de Estilos (§ 7.4)
     */
    function render_status_pill(string $estado): string
    {
        return match ($estado) {
            'Recibido' => '<span class="pill pill-recibido"><span class="pill-dot"></span>Recibido</span>',
            'En curso' => '<span class="pill pill-en-curso"><span class="pill-dot"></span>En curso</span>',
            'Resuelto' => '<span class="pill pill-resuelto"><span class="pill-dot"></span>Resuelto</span>',
            'Cerrado'  => '<span class="pill pill-cerrado">Cerrado</span>',
            default    => '<span class="pill pill-cerrado">' . htmlspecialchars($estado, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>',
        };
    }
}

if (!function_exists('icon')) {
    /**
     * Renderiza un icono SVG inline garantizado
     */
    function icon(string $name, string $class = 'w-4 h-4'): string
    {
        return \App\Support\Icons::render($name, $class);
    }
}

