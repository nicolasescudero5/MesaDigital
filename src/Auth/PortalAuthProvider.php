<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Usuario;
use App\Repositories\UsuarioRepository;

class PortalAuthProvider implements AuthProviderInterface
{
    private ?Usuario $cachedUser = null;

    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private SimulatedAuthProvider $simulatedFallback
    ) {}

    public function attempt(string $email, string $password): ?Usuario
    {
        return $this->simulatedFallback->attempt($email, $password);
    }

    public function attemptSimulated(int $userId): ?Usuario
    {
        return $this->simulatedFallback->attemptSimulated($userId);
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

        unset($_SESSION['portal_user_id']);
        unset($_SESSION['portal_user_email']);
        unset($_SESSION['portal_user_name']);
        unset($_SESSION['global_admin_id']);
        unset($_SESSION['user_id']);
        unset($_SESSION['user_rol']);
        unset($_SESSION['user_sede_id']);

        $this->cachedUser = null;
        $this->simulatedFallback->logout();
    }

    public function currentUser(): ?Usuario
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Prioritize Portal SSO Session
        $portalEmail = $_SESSION['portal_user_email'] ?? ($_SESSION['global_admin_email'] ?? ($_SESSION['user_email'] ?? null));
        
        if (!empty($portalEmail)) {
            $emailClean = trim((string)$portalEmail);
            $usuario = $this->usuarioRepository->findByEmail($emailClean);
            
            if ($usuario) {
                // Respetar siempre la baja lógica: un usuario inactivo NUNCA puede ingresar
                if (!$usuario->activo) {
                    unset($_SESSION['user_id'], $_SESSION['user_rol'], $_SESSION['user_sede_id']);
                    return null;
                }

                if (($_SESSION['user_id'] ?? null) !== $usuario->id) {
                    $this->usuarioRepository->updateUltimoLogin((int)$usuario->id);
                }

                $_SESSION['user_id'] = $usuario->id;
                $_SESSION['user_rol'] = $usuario->rol;
                $_SESSION['user_sede_id'] = $usuario->sede_id;
                $this->cachedUser = $usuario;
                return $usuario;
            }

            // REGLA ESTRICTA: NUNCA se crean usuarios automáticamente bajo ninguna circunstancia.
            // La única vía de alta de usuarios es manualmente por un Administrador dentro de Mesa Digital (/usuarios).
            unset($_SESSION['user_id'], $_SESSION['user_rol'], $_SESSION['user_sede_id']);
            return null;
        }

        // 2. Local/Simulated Session Fallback
        return $this->simulatedFallback->currentUser();
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }
}
