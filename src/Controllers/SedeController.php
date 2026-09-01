<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Models\Sede;
use App\Repositories\SedeRepository;
use App\Support\View;

class SedeController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private SedeRepository $sedeRepository,
        private View $view
    ) {}

    public function index(): void
    {
        $user = $this->authProvider->currentUser();
        $sedes = $this->sedeRepository->findAll(false);

        echo $this->view->render('sedes/index', [
            'user' => $user,
            'sedes' => $sedes,
        ], 'app');
    }

    public function store(): void
    {
        $user = $this->authProvider->currentUser();
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $color = trim((string)($_POST['color_primario'] ?? '#4E47DD'));

        if (empty($nombre)) {
            flash('error', 'El nombre de la sede es obligatorio.');
            header("Location: /sedes");
            exit;
        }

        try {
            $sede = new Sede(
                nombre: $nombre,
                color_primario: $color,
                activo: true,
                creado_por: (int)$user->id
            );
            $this->sedeRepository->create($sede);
            flash('success', "Sede '{$nombre}' creada exitosamente.");
        } catch (\Throwable $e) {
            flash('error', 'Error al crear la sede: ' . $e->getMessage());
        }

        header("Location: /sedes");
        exit;
    }

    public function update(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $color = trim((string)($_POST['color_primario'] ?? '#4E47DD'));
        $activo = !empty($_POST['activo']);

        $sede = $this->sedeRepository->findById($id);
        if (!$sede) {
            flash('error', 'Sede no encontrada.');
            header("Location: /sedes");
            exit;
        }

        $sede->nombre = $nombre;
        $sede->color_primario = $color;
        $sede->activo = $activo;
        $sede->modificado_por = (int)$user->id;

        try {
            $this->sedeRepository->update($sede);
            flash('success', "Sede '{$nombre}' actualizada.");
        } catch (\Throwable $e) {
            flash('error', 'Error al actualizar la sede: ' . $e->getMessage());
        }

        header("Location: /sedes");
        exit;
    }

    public function deactivate(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);

        try {
            $this->sedeRepository->deactivate($id, (int)$user->id);
            flash('success', 'Sede dada de baja lógica.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /sedes");
        exit;
    }
}
