<?php

$rootAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($rootAutoload)) {
    require_once $rootAutoload;
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

$dbHost = $_ENV['DB_HOST'] ?? $_ENV['DB_HOSTNAME'] ?? getenv('DB_HOST') ?: (getenv('DB_HOSTNAME') ?: '127.0.0.1');
$dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'mesa_digital';
$dbUser = $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'german');
$dbPass = $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: 'G3rm4n@-');
$dbSocket = $_ENV['DB_SOCKET'] ?? getenv('DB_SOCKET') ?: '';

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
