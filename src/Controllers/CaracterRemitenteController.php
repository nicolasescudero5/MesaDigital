<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Models\CaracterRemitente;
use App\Repositories\CaracterRemitenteRepository;
use App\Support\View;

class CaracterRemitenteController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private CaracterRemitenteRepository $caracterRemitenteRepository,
        private View $view
    ) {}

    public function index(): void
    {
        $user = $this->authProvider->currentUser();
        $caracteres = $this->caracterRemitenteRepository->findAll(false);

        echo $this->view->render('caracteres-remitente/index', [
            'user' => $user,
            'caracteres' => $caracteres,
        ], 'app');
    }

    public function store(): void
    {
        $user = $this->authProvider->currentUser();
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $orden = (int)($_POST['orden'] ?? 0);

        if (empty($nombre)) {
            flash('error', 'El nombre es obligatorio.');
            header("Location: /caracteres-remitente");
            exit;
        }

        try {
            $caracter = new CaracterRemitente(
                nombre: $nombre,
                orden: $orden,
                activo: true,
                creado_por: (int)$user->id
            );
            $this->caracterRemitenteRepository->create($caracter);
            flash('success', "Carácter de remitente '{$nombre}' creado.");
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /caracteres-remitente");
        exit;
    }

    public function update(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $orden = (int)($_POST['orden'] ?? 0);
        $activo = !empty($_POST['activo']);

        $caracter = $this->caracterRemitenteRepository->findById($id);
        if (!$caracter) {
            flash('error', 'Carácter no encontrado.');
            header("Location: /caracteres-remitente");
            exit;
        }

        $caracter->nombre = $nombre;
        $caracter->orden = $orden;
        $caracter->activo = $activo;
        $caracter->modificado_por = (int)$user->id;

        try {
            $this->caracterRemitenteRepository->update($caracter);
            flash('success', "Carácter '{$nombre}' actualizado.");
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /caracteres-remitente");
        exit;
    }

    public function deactivate(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);

        try {
            $this->caracterRemitenteRepository->deactivate($id, (int)$user->id);
            flash('success', 'Carácter de remitente dado de baja lógica.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /caracteres-remitente");
        exit;
    }
}
