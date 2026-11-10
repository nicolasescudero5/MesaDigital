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
        $cargoNivel = trim((string)($_POST['cargo_nivel'] ?? ''));
        $redirectTo = !empty($_POST['redirect_to']) && str_starts_with((string)$_POST['redirect_to'], '/') ? (string)$_POST['redirect_to'] : '/usuarios';

        $sedesIds = isset($_POST['sedes_ids']) ? array_values(array_unique(array_filter(array_map('intval', (array)$_POST['sedes_ids'])))) : [];
        if (empty($sedesIds) && !empty($_POST['sede_id'])) {
            $sedesIds = [(int)$_POST['sede_id']];
        }
        $sedeId = !empty($sedesIds) ? $sedesIds[0] : null;
        $password = (string)($_POST['password'] ?? '');

        if (empty($nombre) || empty($email)) {
            flash('error', 'Nombre y correo electrónico son obligatorios.');
            redirect($redirectTo);
        }

        // Para roles acotados por sede, es obligatorio asignar al menos una sede
        if (in_array($rol, ['recepcion_sede', 'direccion_sede', 'direccion_nivel'], true) && empty($sedesIds)) {
            flash('error', 'Para este rol es obligatorio asignar al menos una sede.');
            redirect($redirectTo);
        }

        $existente = $this->usuarioRepository->findByEmail($email);
        if ($existente) {
            flash('error', "Ya existe un usuario con el correo {$email}.");
            redirect($redirectTo);
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
                creado_por: (int)$user->id,
                sedes_ids: $sedesIds,
                cargo_nivel: $rol === 'direccion_nivel' && $cargoNivel !== '' ? $cargoNivel : null
            );
            $this->usuarioRepository->create($nuevo, $sedesIds);
            flash('success', "Usuario '{$nombre}' dado de alta exitosamente.");
        } catch (\Throwable $e) {
            flash('error', 'Error al crear usuario: ' . $e->getMessage());
        }

        redirect($redirectTo);
    }

    public function update(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $rol = (string)($_POST['rol'] ?? 'recepcion_sede');
        $cargoNivel = trim((string)($_POST['cargo_nivel'] ?? ''));
        $redirectTo = !empty($_POST['redirect_to']) && str_starts_with((string)$_POST['redirect_to'], '/') ? (string)$_POST['redirect_to'] : '/usuarios';

        $sedesIds = isset($_POST['sedes_ids']) ? array_values(array_unique(array_filter(array_map('intval', (array)$_POST['sedes_ids'])))) : [];
        if (empty($sedesIds) && !empty($_POST['sede_id'])) {
            $sedesIds = [(int)$_POST['sede_id']];
        }
        $sedeId = !empty($sedesIds) ? $sedesIds[0] : null;
        $activo = !empty($_POST['activo']);
        $password = (string)($_POST['password'] ?? '');

        $usuario = $this->usuarioRepository->findById($id);
        if (!$usuario) {
            flash('error', 'Usuario no encontrado.');
            redirect($redirectTo);
        }

        if (in_array($rol, ['recepcion_sede', 'direccion_sede', 'direccion_nivel'], true) && empty($sedesIds)) {
            flash('error', 'Para este rol es obligatorio asignar al menos una sede.');
            redirect($redirectTo);
        }

        $usuario->nombre = $nombre;
        $usuario->email = $email;
        $usuario->rol = $rol;
        $usuario->cargo_nivel = $rol === 'direccion_nivel' && $cargoNivel !== '' ? $cargoNivel : null;
        $usuario->sede_id = $sedeId;
        $usuario->sedes_ids = $sedesIds;
        $usuario->activo = $activo;
        $usuario->modificado_por = (int)$user->id;

        try {
            $this->usuarioRepository->update($usuario, $sedesIds);

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $this->usuarioRepository->updatePassword($id, $hash);
            }

            flash('success', "Usuario '{$nombre}' actualizado.");
        } catch (\Throwable $e) {
            flash('error', 'Error al actualizar usuario: ' . $e->getMessage());
        }

        redirect($redirectTo);
    }

    public function toggleStatus(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $redirectTo = !empty($_POST['redirect_to']) && str_starts_with((string)$_POST['redirect_to'], '/') ? (string)$_POST['redirect_to'] : '/usuarios';

        if ($id === (int)$user->id) {
            flash('error', 'No podés dar de baja tu propio usuario.');
            redirect($redirectTo);
        }

        $usuario = $this->usuarioRepository->findById($id);
        if (!$usuario) {
            flash('error', 'Usuario no encontrado.');
            redirect($redirectTo);
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

        redirect($redirectTo);
    }
}
