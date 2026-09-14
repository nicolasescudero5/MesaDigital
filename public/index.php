<?php

declare(strict_types=1);

@set_time_limit(0);
@ini_set('max_execution_time', '0');
@ini_set('max_input_time', '0');
@ini_set('memory_limit', '512M');

// Servir assets estáticos inmediatamente (sin requerir sesión, auth o TenantContext)
$reqUri = $_SERVER['REQUEST_URI'] ?? '/';
if (false !== $pos = strpos($reqUri, '?')) {
    $reqUri = substr($reqUri, 0, $pos);
}
$reqUri = rawurldecode($reqUri);
$normPath = !empty($_GET['url']) && is_string($_GET['url']) ? trim($_GET['url'], '/') : trim($reqUri, '/');

if (str_starts_with($normPath, 'assets/') || strpos($normPath, '/assets/') !== false) {
    $assetSubPath = strstr($normPath, 'assets/');
    $assetFile = __DIR__ . '/' . $assetSubPath;
    if (file_exists($assetFile) && !is_dir($assetFile)) {
        $ext = pathinfo($assetFile, PATHINFO_EXTENSION);
        $mimes = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'ico'   => 'image/x-icon',
            'svg'   => 'image/svg+xml',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'eot'   => 'font/vnd.ms-fontobject',
            'pdf'   => 'application/pdf',
        ];
        $mime = $mimes[$ext] ?? 'text/plain';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        readfile($assetFile);
        exit;
    }
}

// Soporte para servir assets estáticos directamente en el servidor embebido de PHP
if (php_sapi_name() === 'cli-server') {
    $urlPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $filePath = __DIR__ . $urlPath;
    if ($urlPath !== '/' && file_exists($filePath) && !is_dir($filePath)) {
        return false;
    }
}

// Iniciar buffering de salida para inyectar URL_BASE a enlaces, scripts, imágenes y form actions
ob_start(function($buffer) {
    if (defined('URL_BASE') && URL_BASE !== '') {
        $pattern = '/(href|src|action)="\/((?:assets|documentos|dashboard|sedes|categorias|tipos-documento|caracteres-remitente|usuarios|reportes|logout|login|cargar|health)(?:[\/?"#][^"]*)?|[\?#]?)"/i';
        $buffer = preg_replace_callback($pattern, function($matches) {
            $attribute = $matches[1];
            $path = $matches[2];
            return $attribute . '="' . URL_BASE . '/' . ltrim($path, '/') . '"';
        }, $buffer);
    }
    return $buffer;
});

$rootAutoload = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
if (file_exists($rootAutoload)) {
    require_once $rootAutoload;
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

if (file_exists(__DIR__ . '/../src/Support/Helpers.php')) {
    require_once __DIR__ . '/../src/Support/Helpers.php';
}

// 1. Variables de entorno globales y locales
$rootEnvFile = dirname(dirname(__DIR__)) . '/.env';
if (file_exists($rootEnvFile)) {
    $lines = file($rootEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

// 2. Bootstrap Portal TenantContext si está ejecutando en el ecosistema appcolegios
$portalTenantFile = dirname(dirname(__DIR__)) . '/portal/app/core/TenantContext.php';
if (file_exists($portalTenantFile)) {
    require_once $portalTenantFile;
    if (class_exists('\TenantContext')) {
        if (method_exists('\TenantContext', 'startPortalSession')) {
            \TenantContext::startPortalSession();
        }
        \TenantContext::init();
    }
}

$appConfig = require __DIR__ . '/../config/app.php';
if (($appConfig['env'] ?? 'local') === 'local') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// 3. Zona horaria y localización
date_default_timezone_set($appConfig['timezone'] ?? 'America/Argentina/Buenos_Aires');

// 4. Configuración de sesión (TenantContext o Fallback)
if (session_status() === PHP_SESSION_NONE) {
    session_name('APPCOLEGIOS_SESSID');
    $savePath = dirname(dirname(__DIR__)) . '/storage/sessions';
    if (is_dir($savePath) && is_writable($savePath)) {
        session_save_path($savePath);
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', '86400');

    if (!empty($appConfig['session_secure']) || ($appConfig['env'] === 'production')) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

// 5. Inyección de dependencias
$container = \App\Support\Container::build();

// 6. Guardarraíl de seguridad obligatorio (§ 6.1 y Criterio N°13)
if (($appConfig['env'] === 'production') && !empty($appConfig['login_simulado_habilitado'])) {
    http_response_code(500);
    die("FATAL: El login simulado está habilitado en entorno de producción. La aplicación no puede iniciar.");
}

// 7. Enrutamiento y Normalización de URI
$router = new \App\Support\Router($container);
require_once __DIR__ . '/../routes/web.php';

$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// Calcular URL_BASE para links estáticos y redirecciones
$urlBase = '';
$posMesa = strpos(strtolower($uri), '/mesa');
if ($posMesa !== false) {
    $urlBase = substr($uri, 0, $posMesa + 5);
}
$urlBase = rtrim($urlBase, '/');
if (!defined('URL_BASE')) {
    define('URL_BASE', $urlBase);
}

// Normalizar URI quitando /appcolegios, /nuevo_portal, /{subdomain}, /mesa
if (!empty($_GET['url']) && is_string($_GET['url'])) {
    $normalized = trim($_GET['url'], '/');
} else {
    $normalized = trim($uri, '/');
}

if (str_starts_with($normalized, 'appcolegios')) {
    $normalized = trim(substr($normalized, 11), '/');
}
if (str_starts_with($normalized, 'nuevo_portal')) {
    $normalized = trim(substr($normalized, 12), '/');
}

$is_tenant = class_exists('\TenantContext') && \TenantContext::hasCurrentTenant();
$tenant = $is_tenant ? \TenantContext::getCurrentTenant() : null;
if ($tenant && str_starts_with(strtolower($normalized), strtolower($tenant->subdomain))) {
    $normalized = trim(substr($normalized, strlen($tenant->subdomain)), '/');
} elseif (defined('RESOLVED_SUBDOMAIN') && RESOLVED_SUBDOMAIN && str_starts_with(strtolower($normalized), strtolower(RESOLVED_SUBDOMAIN))) {
    $normalized = trim(substr($normalized, strlen(RESOLVED_SUBDOMAIN)), '/');
}

if (str_starts_with($normalized, 'mesa')) {
    $normalized = trim(substr($normalized, 4), '/');
}

// Servir assets estáticos directamente si coincide en la ruta (para enrutamiento Apache en producción)
if (str_starts_with($normalized, 'assets/')) {
    $assetFile = __DIR__ . '/' . $normalized;
    if (file_exists($assetFile) && !is_dir($assetFile)) {
        $ext = pathinfo($assetFile, PATHINFO_EXTENSION);
        $mimes = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'ico'   => 'image/x-icon',
            'svg'   => 'image/svg+xml',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'eot'   => 'font/vnd.ms-fontobject',
            'pdf'   => 'application/pdf',
        ];
        $mime = $mimes[$ext] ?? 'text/plain';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        readfile($assetFile);
        exit;
    }
}

$dispatchUri = '/' . $normalized;

$router->dispatch($httpMethod, $dispatchUri);
