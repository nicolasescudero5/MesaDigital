<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Middleware\RateLimitMiddleware;
use App\Repositories\UsuarioRepository;
use App\Support\View;

class AuthController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private UsuarioRepository $usuarioRepository,
        private RateLimitMiddleware $rateLimiter,
        private View $view
    ) {}

    public function showLogin(): void
    {
        if ($this->authProvider->isAuthenticated()) {
            header("Location: /dashboard");
            exit;
        }

        $appConfig = require __DIR__ . '/../../config/app.php';
        $authConfig = require __DIR__ . '/../../config/auth.php';

        $isSimulatedEnabled = ($appConfig['env'] !== 'production') && ($appConfig['login_simulado_habilitado'] ?? false);
        $usuariosSembrados = $isSimulatedEnabled ? $this->usuarioRepository->findAll(true) : [];

        $loginUrlGoogle = $authConfig['driver'] === 'google' ? $this->authProvider->loginUrl() : null;

        echo $this->view->render('auth/login', [
            'isSimulatedEnabled' => $isSimulatedEnabled,
            'usuariosSembrados' => $usuariosSembrados,
            'loginUrlGoogle' => $loginUrlGoogle,
            'driver' => $authConfig['driver'] ?? 'simulado',
        ], 'auth');
    }

    public function login(): void
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if (empty($email) || empty($password)) {
            flash('error', 'Por favor, completá todos los campos.');
            header("Location: /login");
            exit;
        }

        // Rate limiting check (§ 6.3)
        if ($this->rateLimiter->isBlocked($ip, $email)) {
            flash('error', 'Demasiados intentos fallidos. Por razones de seguridad, tu acceso ha sido bloqueado temporalmente por 15 minutos.');
            header("Location: /login");
            exit;
        }

        $usuario = $this->authProvider->attempt($email, $password);

        if (!$usuario) {
            $this->rateLimiter->recordFailedAttempt($ip, $email);
            // Mensaje genérico para no revelar existencia de cuenta (OWASP)
            flash('error', 'Las credenciales ingresadas no son válidas.');
            header("Location: /login");
            exit;
        }

        $this->rateLimiter->clearAttempts($ip, $email);

        $redirect = $_GET['redirect'] ?? '/dashboard';
        header("Location: " . $redirect);
        exit;
    }

    public function simulatedLogin(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            flash('error', 'Usuario no seleccionado.');
            header("Location: /login");
            exit;
        }

        try {
            $usuario = $this->authProvider->attemptSimulated($userId);
            if ($usuario) {
                header("Location: /dashboard");
                exit;
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            header("Location: /login");
            exit;
        }

        flash('error', 'No se pudo iniciar sesión con el usuario seleccionado.');
        header("Location: /login");
        exit;
    }

    public function googleCallback(): void
    {
        try {
            $usuario = $this->authProvider->handleCallback($_GET);
            if ($usuario) {
                header("Location: /dashboard");
                exit;
            }

            flash('error', 'Su cuenta de Google no está habilitada en Mesa Digital. Contacte al administrador.');
            header("Location: /login");
            exit;
        } catch (\Throwable $e) {
            flash('error', 'Error de autenticación con Google: ' . $e->getMessage());
            header("Location: /login");
            exit;
        }
    }

    public function logout(): void
    {
        $this->authProvider->logout();
        header("Location: /login");
        exit;
    }
}
