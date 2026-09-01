<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use Google\Client as GoogleClient;
use RuntimeException;

class GoogleAuthProvider implements AuthProviderInterface
{
    private ?GoogleClient $client = null;
    private ?Usuario $cachedUser = null;

    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private array $config
    ) {}

    private function getClient(): GoogleClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $this->client = new GoogleClient();
        $this->client->setClientId($this->config['client_id'] ?? '');
        $this->client->setClientSecret($this->config['client_secret'] ?? '');
        $this->client->setRedirectUri($this->config['redirect_uri'] ?? '');
        $this->client->addScope(['email', 'profile', 'openid']);

        if (!empty($this->config['hosted_domain'])) {
            $this->client->setHostedDomain($this->config['hosted_domain']);
        }

        return $this->client;
    }

    public function attempt(string $email, string $password): ?Usuario
    {
        throw new RuntimeException("El driver de autenticación Google no soporta login directo con contraseña.");
    }

    public function attemptSimulated(int $userId): ?Usuario
    {
        throw new RuntimeException("El acceso simulado no está permitido cuando el driver es Google.");
    }

    public function loginUrl(): ?string
    {
        return $this->getClient()->createAuthUrl();
    }

    public function handleCallback(array $params): ?Usuario
    {
        if (empty($params['code'])) {
            return null;
        }

        $client = $this->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($params['code']);

        if (isset($token['error'])) {
            throw new RuntimeException("Error en Google OAuth: " . ($token['error_description'] ?? $token['error']));
        }

        $payload = $client->verifyIdToken($token['id_token'] ?? '');
        if (!$payload || empty($payload['email'])) {
            throw new RuntimeException("Token de Google inválido o sin correo electrónico.");
        }

        $email = strtolower(trim((string)$payload['email']));
        $googleSub = (string)($payload['sub'] ?? '');

        // Validación de dominio si está configurado
        if (!empty($this->config['hosted_domain'])) {
            $hd = $payload['hd'] ?? '';
            if ($hd !== $this->config['hosted_domain']) {
                throw new RuntimeException("El correo no pertenece al dominio corporativo autorizado ({$this->config['hosted_domain']}).");
            }
        }

        // Buscar usuario en base de datos.
        // REGLA CRÍTICA (§ 6.1 y Criterio N°14): NUNCA se auto-provisiona un usuario no existente.
        $usuario = $this->usuarioRepository->findByEmail($email);
        if (!$usuario || !$usuario->activo) {
            return null; // Denegado
        }

        // Si existe pero no tenía google_sub asociado, vincularlo
        if (empty($usuario->google_sub) && !empty($googleSub)) {
            $this->usuarioRepository->updateGoogleSub((int)$usuario->id, $googleSub);
            $usuario->google_sub = $googleSub;
        }

        $this->startSessionForUser($usuario);
        return $usuario;
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
