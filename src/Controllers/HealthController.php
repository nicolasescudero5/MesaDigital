<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

class HealthController
{
    public function __construct(private PDO $pdo) {}

    public function check(): void
    {
        $status = 'healthy';
        $dbStatus = 'ok';

        try {
            $this->pdo->query('SELECT 1');
        } catch (\Throwable $e) {
            $status = 'unhealthy';
            $dbStatus = 'error: ' . $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => $status,
            'database' => $dbStatus,
            'timestamp' => date('c'),
            'app_env' => $_ENV['APP_ENV'] ?? 'local',
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
