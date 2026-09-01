<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Models\Usuario;
use App\Repositories\SedeRepository;
use App\Repositories\UsuarioRepository;
use App\Support\View;

class UsuarioController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private UsuarioRepository $usuarioRepository,
        private SedeRepository $sedeRepository,
        private View $view
    ) {}

    public function index(): void
    {
        $user = $this->authProvider->currentUser();
        $usuarios = $this->usuarioRepository->findAll(false);
        $sedes = $this->sedeRepository->findAll(true);

        echo $this->view->render('usuarios/index', [
            'user' => $user,
            'usuarios' => $usuarios,
            'sedes' => $sedes,
        ], 'app');
    }

    public function store(): void
    {
        $user = $this->authProvider->currentUser();
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $rol = (string)($_POST['rol'] ?? 'recepcion_sede');
        $sedeId = !empty($_POST['sede_id']) ? (int)$_POST['sede_id'] : null;
        $password = (string)($_POST['password'] ?? '');

        if (empty($nombre) || empty($email)) {
            flash('error', 'Nombre y correo electrónico son obligatorios.');
            header("Location: /usuarios");
            exit;
        }

        // Si el rol es recepcion_sede o direccion_sede, sede_id es obligatorio (§ 4.1 y § 5.2)
        if (in_array($rol, ['recepcion_sede', 'direccion_sede'], true) && !$sedeId) {
            flash('error', 'Para los roles de Recepción y Dirección de Sede es obligatorio asignar una sede.');
            header("Location: /usuarios");
            exit;
        }

        $existente = $this->usuarioRepository->findByEmail($email);
        if ($existente) {
            flash('error', "Ya existe un usuario con el correo {$email}.");
            header("Location: /usuarios");
            exit;
        }

        $passwordHash = !empty($password) ? password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]) : null;

        try {
            $nuevo = new Usuario(
                nombre: $nombre,
                email: $email,
                rol: $rol,
                sede_id: $sedeId,
                password_hash: $passwordHash,
                activo: true,
                creado_por: (int)$user->id
            );
            $this->usuarioRepository->create($nuevo);
            flash('success', "Usuario '{$nombre}' dado de alta exitosamente.");
        } catch (\Throwable $e) {
            flash('error', 'Error al crear usuario: ' . $e->getMessage());
        }

        header("Location: /usuarios");
        exit;
    }

    public function update(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $rol = (string)($_POST['rol'] ?? 'recepcion_sede');
        $sedeId = !empty($_POST['sede_id']) ? (int)$_POST['sede_id'] : null;
        $activo = !empty($_POST['activo']);
        $password = (string)($_POST['password'] ?? '');

        $usuario = $this->usuarioRepository->findById($id);
        if (!$usuario) {
            flash('error', 'Usuario no encontrado.');
            header("Location: /usuarios");
            exit;
        }

        if (in_array($rol, ['recepcion_sede', 'direccion_sede'], true) && !$sedeId) {
            flash('error', 'Para los roles de Recepción y Dirección de Sede es obligatorio asignar una sede.');
            header("Location: /usuarios");
            exit;
        }

        $usuario->nombre = $nombre;
        $usuario->email = $email;
        $usuario->rol = $rol;
        $usuario->sede_id = $sedeId;
        $usuario->activo = $activo;
        $usuario->modificado_por = (int)$user->id;

        try {
            $this->usuarioRepository->update($usuario);

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $this->usuarioRepository->updatePassword($id, $hash);
            }

            flash('success', "Usuario '{$nombre}' actualizado.");
        } catch (\Throwable $e) {
            flash('error', 'Error al actualizar usuario: ' . $e->getMessage());
        }

        header("Location: /usuarios");
        exit;
    }

    public function toggleStatus(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);

        if ($id === (int)$user->id) {
            flash('error', 'No podés dar de baja tu propio usuario.');
            header("Location: /usuarios");
            exit;
        }

        $usuario = $this->usuarioRepository->findById($id);
        if (!$usuario) {
            flash('error', 'Usuario no encontrado.');
            header("Location: /usuarios");
            exit;
        }

        try {
            if ($usuario->activo) {
                $this->usuarioRepository->deactivate($id, (int)$user->id);
                flash('success', "Usuario {$usuario->nombre} dado de baja.");
            } else {
                $this->usuarioRepository->activate($id, (int)$user->id);
                flash('success', "Usuario {$usuario->nombre} reactivado.");
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /usuarios");
        exit;
    }
}
