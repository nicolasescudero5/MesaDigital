<?php

declare(strict_types=1);

// Soporte para servir assets estáticos directamente en el servidor embebido de PHP
if (php_sapi_name() === 'cli-server') {
    $urlPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $filePath = __DIR__ . $urlPath;
    if ($urlPath !== '/' && file_exists($filePath) && !is_dir($filePath)) {
        return false;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

// 1. Variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

$appConfig = require __DIR__ . '/../config/app.php';

// Configuración de límites y errores para entorno local
@set_time_limit(86400);
@ini_set('max_execution_time', '86400');
@ini_set('max_input_time', '86400');
if (($appConfig['env'] ?? 'local') === 'local') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// 2. Zona horaria y localización
date_default_timezone_set($appConfig['timezone'] ?? 'America/Argentina/Buenos_Aires');

// 3. Configuración estricta de cookies de sesión (§ 6.2)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', '1800'); // 30 minutos

    if (!empty($appConfig['session_secure']) || ($appConfig['env'] === 'production')) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

// 4. Inyección de dependencias
$container = \App\Support\Container::build();

// 5. Guardarraíl de seguridad obligatorio (§ 6.1 y Criterio N°13)
if (($appConfig['env'] === 'production') && !empty($appConfig['login_simulado_habilitado'])) {
    http_response_code(500);
    die("FATAL: El login simulado está habilitado en entorno de producción. La aplicación no puede iniciar.");
}

// 6. Enrutamiento
$router = new \App\Support\Router($container);
require_once __DIR__ . '/../routes/web.php';

$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($httpMethod, $uri);
