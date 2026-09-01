<?php

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'mesa_digital';
$dbUser = $_ENV['DB_USERNAME'] ?? 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';
$dbSocket = $_ENV['DB_SOCKET'] ?? '';

$connection = [
    'adapter' => 'mysql',
    'host' => $dbHost,
    'name' => $dbName,
    'user' => $dbUser,
    'pass' => $dbPass,
    'port' => (int)$dbPort,
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];

if (!empty($dbSocket)) {
    $connection['unix_socket'] = $dbSocket;
}

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => $connection,
        'production' => $connection,
        'testing' => [
            'adapter' => 'mysql',
            'host' => $dbHost,
            'name' => $dbName . '_test',
            'user' => $dbUser,
            'pass' => $dbPass,
            'port' => (int)$dbPort,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]
    ],
    'version_order' => 'creation'
];
