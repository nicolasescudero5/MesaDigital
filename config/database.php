<?php

$tenantDbPrefix = (class_exists('\TenantContext') && method_exists('\TenantContext', 'getTenantDbPrefix')) ? \TenantContext::getTenantDbPrefix() : null;
$defaultDatabase = $_ENV['DB_DATABASE'] ?? 'mesa_digital';
$databaseName = $tenantDbPrefix ? "appcolegios__{$tenantDbPrefix}__mesa" : $defaultDatabase;

$dbHost = $_ENV['DB_HOST'] ?? $_ENV['DB_HOSTNAME'] ?? getenv('DB_HOST') ?: (getenv('DB_HOSTNAME') ?: '127.0.0.1');
$dbPort = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306);
$dbUser = $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'german');
$dbPass = $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: 'G3rm4n@-');
$dbSocket = $_ENV['DB_SOCKET'] ?? getenv('DB_SOCKET') ?: '';

return [
    'host' => $dbHost,
    'port' => $dbPort,
    'database' => $databaseName,
    'username' => $dbUser,
    'password' => $dbPass,
    'socket' => $dbSocket,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
