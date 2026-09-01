<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use RuntimeException;

class SimulatedAuthProvider implements AuthProviderInterface
{
    private string $env;
    private bool $simuladoHabilitado;
    private ?Usuario $cachedUser = null;

    public function __construct(
        private UsuarioRepository $usuarioRepository,
        string $env = 'local',
        bool $simuladoHabilitado = true
    ) {
        $this->env = $env;
        $this->simuladoHabilitado = $simuladoHabilitado;

        // Guardarraíl de seguridad obligatorio (§ 6.1 y Criterio N°13):
        // En producción JAMÁS puede estar habilitado el login simulado.
        if ($this->env === 'production' && $this->simuladoHabilitado) {
            throw new RuntimeException("CRITICAL SECURITY ERROR: El login simulado está habilitado en entorno de PRODUCCIÓN. El arranque ha sido abortado.");
        }
    }

    public function attempt(string $email, string $password): ?Usuario
    {
        $usuario = $this->usuarioRepository->findByEmail(trim($email));
        if (!$usuario || !$usuario->activo) {
            return null;
        }

        // Si no tiene password_hash o no coincide
        if (empty($usuario->password_hash) || !password_verify($password, $usuario->password_hash)) {
            return null;
        }

        $this->startSessionForUser($usuario);
        return $usuario;
    }

    public function attemptSimulated(int $userId): ?Usuario
    {
        if ($this->env === 'production' || !$this->simuladoHabilitado) {
            throw new RuntimeException("El acceso rápido simulado está deshabilitado en este entorno.");
        }

        $usuario = $this->usuarioRepository->findById($userId);
        if (!$usuario || !$usuario->activo) {
            return null;
        }

        $this->startSessionForUser($usuario);
        return $usuario;
    }

    public function loginUrl(): ?string
    {
        return null;
    }

    public function handleCallback(array $params): ?Usuario
    {
        return null;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        $this->cachedUser = null;
    }

    public function currentUser(): ?Usuario
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $usuario = $this->usuarioRepository->findById((int)$userId);
        if ($usuario && $usuario->activo) {
            $this->cachedUser = $usuario;
            return $usuario;
        }

        return null;
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }

    private function startSessionForUser(Usuario $usuario): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $usuario->id;
        $_SESSION['user_rol'] = $usuario->rol;
        $_SESSION['user_sede_id'] = $usuario->sede_id;
        $_SESSION['last_activity'] = time();

        $this->usuarioRepository->updateUltimoLogin((int)$usuario->id);
        $this->cachedUser = $usuario;
    }
}
