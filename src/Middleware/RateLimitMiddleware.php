<?php

declare(strict_types=1);

namespace App\Middleware;

use PDO;

class RateLimitMiddleware
{
    public function __construct(private PDO $pdo) {}

    /**
     * Verifica si una combinación de IP + Email ha superado los 5 intentos en 15 minutos (§ 6.3)
     */
    public function isBlocked(string $ip, string $email): bool
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM login_intentos 
             WHERE ip_address = :ip AND email = :email 
               AND intentado_el >= :cutoff"
        );
        $stmt->execute(['ip' => $ip, 'email' => $email, 'cutoff' => $cutoff]);
        $attempts = (int)$stmt->fetchColumn();

        return $attempts >= 5;
    }

    /**
     * Registra un intento fallido
     */
    public function recordFailedAttempt(string $ip, string $email): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO login_intentos (ip_address, email, intentado_el) 
             VALUES (:ip, :email, :intentado_el)"
        );
        $stmt->execute(['ip' => $ip, 'email' => $email, 'intentado_el' => date('Y-m-d H:i:s')]);
    }

    /**
     * Limpia los intentos tras un inicio de sesión exitoso
     */
    public function clearAttempts(string $ip, string $email): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM login_intentos WHERE ip_address = :ip AND email = :email");
        $stmt->execute(['ip' => $ip, 'email' => $email]);
    }
}
