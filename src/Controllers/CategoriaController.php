<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Models\Categoria;
use App\Repositories\CategoriaRepository;
use App\Repositories\SedeRepository;
use App\Repositories\UsuarioRepository;
use App\Support\View;

class CategoriaController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private CategoriaRepository $categoriaRepository,
        private UsuarioRepository $usuarioRepository,
        private SedeRepository $sedeRepository,
        private View $view
    ) {}

    public function index(): void
    {
        $user = $this->authProvider->currentUser();
        $categorias = $this->categoriaRepository->findAll(false, true);
        $sedes = $this->sedeRepository->findAll(true);
        $usuariosResponsables = array_filter(
            $this->usuarioRepository->findAll(true),
            fn($u) => $u->isResponsable() || $u->isAdministrador()
        );

        echo $this->view->render('categorias/index', [
            'user' => $user,
            'categorias' => $categorias,
            'sedes' => $sedes,
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
            redirect('/categorias');
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
                $this->categoriaRepository->addResponsable($catId, $initialEmail, $initialUserId, null, (int)$user->id);
            }

            flash('success', "Categoría '{$nombre}' creada exitosamente.");
        } catch (\Throwable $e) {
            flash('error', 'Error al crear la categoría: ' . $e->getMessage());
        }

        redirect('/categorias');
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
            redirect('/categorias');
        }

        $cat->nombre = $nombre;
        $cat->descripcion = !empty($descripcion) ? $descripcion : null;
        $cat->orden = $orden;
        $cat->activo = $activo;
        $cat->modificado_por = (int)$user->id;

        try {
            $this->categoriaRepository->update($cat);

            // Si también se seleccionó un responsable o correo en el formulario de edición, guardarlo en el mismo paso
            if (!empty($_POST['usuario_id']) || !empty($_POST['email'])) {
                $this->saveResponsableFromPost($id, (int)$user->id);
            }

            flash('success', "Categoría '{$nombre}' actualizada.");
        } catch (\Throwable $e) {
            flash('error', 'Error al actualizar: ' . $e->getMessage());
        }

        redirect('/categorias');
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

        redirect('/categorias');
    }

    public function addResponsable(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $catId = (int)($vars['id'] ?? 0);

        try {
            $email = $this->saveResponsableFromPost($catId, (int)$user->id);
            if (!$email) {
                flash('error', 'Debe seleccionar un usuario o indicar un correo electrónico válido.');
                redirect('/categorias');
            }
            flash('success', "Responsable {$email} guardado en la categoría.");
        } catch (\Throwable $e) {
            flash('error', 'Error al guardar responsable: ' . $e->getMessage());
        }

        redirect('/categorias');
    }

    private function saveResponsableFromPost(int $catId, int $creadoPor): ?string
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $usuarioId = !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;

        // Soportar múltiples sedes (sedes_ids[]) o una sede única (sede_id)
        $sedesIds = [];
        if (isset($_POST['sedes_ids']) && is_array($_POST['sedes_ids'])) {
            $sedesIds = array_values(array_unique(array_filter(array_map('intval', $_POST['sedes_ids']))));
        } elseif (!empty($_POST['sede_id'])) {
            $sedesIds = [(int)$_POST['sede_id']];
        }

        $selectedUser = null;
        if ($usuarioId) {
            $selectedUser = $this->usuarioRepository->findById($usuarioId);
            if ($selectedUser && empty($email)) {
                $email = $selectedUser->email;
            }
        } elseif (!empty($email)) {
            $selectedUser = $this->usuarioRepository->findByEmail($email);
            if ($selectedUser) {
                $usuarioId = (int)$selectedUser->id;
            }
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // Si el usuario tiene permisos restringidos a ciertas sedes en /usuarios, filtrar/validar que solo se asignen esas sedes
        if ($selectedUser) {
            $allowedSedesIds = $selectedUser->getSedesIds();
            if (!empty($allowedSedesIds)) {
                if (!empty($sedesIds)) {
                    $sedesIds = array_values(array_intersect($sedesIds, $allowedSedesIds));
                    if (empty($sedesIds)) {
                        throw new \InvalidArgumentException('El usuario seleccionado no tiene permiso en las sedes indicadas.');
                    }
                } else {
                    // Si no marcó ninguna en particular pero el usuario solo tiene permiso a ciertas sedes, asignar sus sedes permitidas
                    $sedesIds = $allowedSedesIds;
                }
            }
        }

        if (empty($sedesIds)) {
            // Alcance Global (Todas las sedes)
            $this->categoriaRepository->addResponsable($catId, $email, $usuarioId, null, $creadoPor);
        } else {
            foreach ($sedesIds as $sId) {
                $this->categoriaRepository->addResponsable($catId, $email, $usuarioId, (int)$sId, $creadoPor);
            }
        }

        return $email;
    }

    public function removeResponsable(array $vars): void
    {
        $catId = (int)($vars['id'] ?? 0);
        $responsableId = !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : 0;
        $email = trim((string)($_POST['email'] ?? ''));
        $sedeId = !empty($_POST['sede_id']) ? (int)$_POST['sede_id'] : null;

        try {
            if ($responsableId > 0) {
                $this->categoriaRepository->removeResponsableById($responsableId);
            } else {
                $this->categoriaRepository->removeResponsable($catId, $email, $sedeId);
            }
            flash('success', "Responsable removido de la categoría.");
        } catch (\Throwable $e) {
            flash('error', 'Error al remover responsable: ' . $e->getMessage());
        }

        redirect('/categorias');
    }
}
