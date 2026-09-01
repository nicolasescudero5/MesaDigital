<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Usuario;

interface AuthProviderInterface
{
    /**
     * Intenta autenticar a partir de los datos de la petición (email + password / token)
     */
    public function attempt(string $email, string $password): ?Usuario;

    /**
     * Intenta autenticación rápida simulada por ID de usuario (solo en entornos no productivos)
     */
    public function attemptSimulated(int $userId): ?Usuario;

    /**
     * Obtiene la URL de login para proveedores externos (null si es simulado/local)
     */
    public function loginUrl(): ?string;

    /**
     * Procesa el retorno (callback) de un proveedor OAuth externo
     */
    public function handleCallback(array $params): ?Usuario;

    /**
     * Cierra la sesión activa
     */
    public function logout(): void;

    /**
     * Obtiene el usuario autenticado en la sesión actual
     */
    public function currentUser(): ?Usuario;

    /**
     * Verifica si hay una sesión activa válida
     */
    public function isAuthenticated(): bool;
}
