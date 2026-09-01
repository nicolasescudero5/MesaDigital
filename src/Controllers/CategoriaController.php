<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Models\Categoria;
use App\Repositories\CategoriaRepository;
use App\Repositories\UsuarioRepository;
use App\Support\View;

class CategoriaController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private CategoriaRepository $categoriaRepository,
        private UsuarioRepository $usuarioRepository,
        private View $view
    ) {}

    public function index(): void
    {
        $user = $this->authProvider->currentUser();
        $categorias = $this->categoriaRepository->findAll(false, true);
        $usuariosResponsables = array_filter(
            $this->usuarioRepository->findAll(true),
            fn($u) => $u->isResponsable() || $u->isAdministrador()
        );

        echo $this->view->render('categorias/index', [
            'user' => $user,
            'categorias' => $categorias,
            'usuariosResponsables' => $usuariosResponsables,
        ], 'app');
    }

    public function store(): void
    {
        $user = $this->authProvider->currentUser();
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $orden = (int)($_POST['orden'] ?? 0);

        if (empty($nombre)) {
            flash('error', 'El nombre de la categoría es obligatorio.');
            header("Location: /categorias");
            exit;
        }

        try {
            $cat = new Categoria(
                nombre: $nombre,
                descripcion: !empty($descripcion) ? $descripcion : null,
                orden: $orden,
                es_reserva: false,
                activo: true,
                creado_por: (int)$user->id
            );
            $catId = $this->categoriaRepository->create($cat);

            // Si se asignó un responsable inicial
            $initialEmail = trim((string)($_POST['responsable_email'] ?? ''));
            $initialUserId = !empty($_POST['responsable_usuario_id']) ? (int)$_POST['responsable_usuario_id'] : null;
            if (!empty($initialEmail)) {
                $this->categoriaRepository->addResponsable($catId, $initialEmail, $initialUserId, (int)$user->id);
            }

            flash('success', "Categoría '{$nombre}' creada exitosamente.");
        } catch (\Throwable $e) {
            flash('error', 'Error al crear la categoría: ' . $e->getMessage());
        }

        header("Location: /categorias");
        exit;
    }

    public function update(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $orden = (int)($_POST['orden'] ?? 0);
        $activo = !empty($_POST['activo']);

        $cat = $this->categoriaRepository->findById($id, false);
        if (!$cat) {
            flash('error', 'Categoría no encontrada.');
            header("Location: /categorias");
            exit;
        }

        $cat->nombre = $nombre;
        $cat->descripcion = !empty($descripcion) ? $descripcion : null;
        $cat->orden = $orden;
        $cat->activo = $activo;
        $cat->modificado_por = (int)$user->id;

        try {
            $this->categoriaRepository->update($cat);
            flash('success', "Categoría '{$nombre}' actualizada.");
        } catch (\Throwable $e) {
            flash('error', 'Error al actualizar: ' . $e->getMessage());
        }

        header("Location: /categorias");
        exit;
    }

    public function deactivate(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);

        try {
            $ok = $this->categoriaRepository->deactivate($id, (int)$user->id);
            if ($ok) {
                flash('success', 'Categoría dada de baja lógica.');
            } else {
                flash('error', 'No se puede desactivar la categoría de reserva del sistema.');
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /categorias");
        exit;
    }

    public function addResponsable(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $catId = (int)($vars['id'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));
        $usuarioId = !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;

        if (empty($email)) {
            // Si eligió un usuario de la lista desplegable, tomar su email
            if ($usuarioId) {
                $u = $this->usuarioRepository->findById($usuarioId);
                if ($u) {
                    $email = $u->email;
                }
            }
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Debe indicar un correo electrónico válido.');
            header("Location: /categorias");
            exit;
        }

        try {
            $this->categoriaRepository->addResponsable($catId, $email, $usuarioId, (int)$user->id);
            flash('success', "Responsable {$email} asignado a la categoría.");
        } catch (\Throwable $e) {
            flash('error', 'Error al asignar responsable: ' . $e->getMessage());
        }

        header("Location: /categorias");
        exit;
    }

    public function removeResponsable(array $vars): void
    {
        $catId = (int)($vars['id'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));

        try {
            $this->categoriaRepository->removeResponsable($catId, $email);
            flash('success', "Responsable {$email} removido de la categoría.");
        } catch (\Throwable $e) {
            flash('error', 'Error al remover responsable: ' . $e->getMessage());
        }

        header("Location: /categorias");
        exit;
    }
}
