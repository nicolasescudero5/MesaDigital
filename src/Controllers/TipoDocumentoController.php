<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Models\TipoDocumento;
use App\Repositories\CategoriaRepository;
use App\Repositories\TipoDocumentoRepository;
use App\Support\View;

class TipoDocumentoController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private TipoDocumentoRepository $tipoDocumentoRepository,
        private CategoriaRepository $categoriaRepository,
        private View $view
    ) {}

    public function index(): void
    {
        $user = $this->authProvider->currentUser();
        $tipos = $this->tipoDocumentoRepository->findAll(false);
        $categorias = $this->categoriaRepository->findAll(true);

        echo $this->view->render('tipos-documento/index', [
            'user' => $user,
            'tipos' => $tipos,
            'categorias' => $categorias,
        ], 'app');
    }

    public function store(): void
    {
        $user = $this->authProvider->currentUser();
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $categoriaId = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $orden = (int)($_POST['orden'] ?? 0);

        if (empty($nombre)) {
            flash('error', 'El nombre es obligatorio.');
            header("Location: /tipos-documento");
            exit;
        }

        try {
            $tipo = new TipoDocumento(
                nombre: $nombre,
                categoria_id: $categoriaId,
                orden: $orden,
                activo: true,
                creado_por: (int)$user->id
            );
            $this->tipoDocumentoRepository->create($tipo);
            flash('success', "Tipo de documento '{$nombre}' creado.");
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /tipos-documento");
        exit;
    }

    public function update(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $categoriaId = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $orden = (int)($_POST['orden'] ?? 0);
        $activo = !empty($_POST['activo']);

        $tipo = $this->tipoDocumentoRepository->findById($id);
        if (!$tipo) {
            flash('error', 'Tipo no encontrado.');
            header("Location: /tipos-documento");
            exit;
        }

        $tipo->nombre = $nombre;
        $tipo->categoria_id = $categoriaId;
        $tipo->orden = $orden;
        $tipo->activo = $activo;
        $tipo->modificado_por = (int)$user->id;

        try {
            $this->tipoDocumentoRepository->update($tipo);
            flash('success', "Tipo '{$nombre}' actualizado.");
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /tipos-documento");
        exit;
    }

    public function deactivate(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);

        try {
            $this->tipoDocumentoRepository->deactivate($id, (int)$user->id);
            flash('success', 'Tipo de documento dado de baja lógica.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /tipos-documento");
        exit;
    }
}
