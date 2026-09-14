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
            $usuario = $this->usuarioRepository->findByEmail(trim($portalEmail));
            
            if ($usuario && $usuario->activo) {
                $_SESSION['user_id'] = $usuario->id;
                $_SESSION['user_rol'] = $usuario->rol;
                $_SESSION['user_sede_id'] = $usuario->sede_id;
                $this->cachedUser = $usuario;
                return $usuario;
            }

            // If user logged in via Portal/Global Admin does not exist in Mesa DB, auto-provision
            $portalName = $_SESSION['portal_user_name'] ?? ($_SESSION['global_admin_name'] ?? 'Usuario Portal');
            $isAdmin = isset($_SESSION['global_admin_id']) 
                    || ($_SESSION['portal_user_type'] ?? '') === 'client_admin' 
                    || ($_SESSION['user_role'] ?? '') === 'admin';

            $rol = $isAdmin ? 'administrador' : 'recepcion_sede';

            $newUsuario = new Usuario(
                nombre: $portalName,
                email: trim($portalEmail),
                rol: $rol,
                activo: true
            );

            try {
                $newId = $this->usuarioRepository->create($newUsuario);
                $newUsuario->id = $newId;
                $_SESSION['user_id'] = $newId;
                $_SESSION['user_rol'] = $rol;
                $this->cachedUser = $newUsuario;
                return $newUsuario;
            } catch (\Throwable $e) {
                // If creation fails (e.g. duplicate key or DB lock), fallback to find
                $existing = $this->usuarioRepository->findByEmail(trim($portalEmail));
                if ($existing) {
                    $this->cachedUser = $existing;
                    return $existing;
                }
            }
        }

        // 2. Local/Simulated Session Fallback
        return $this->simulatedFallback->currentUser();
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }
}
