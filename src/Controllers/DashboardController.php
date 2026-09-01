<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Repositories\DocumentoRepository;
use App\Support\View;

class DashboardController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private DocumentoRepository $documentoRepository,
        private View $view
    ) {}

    public function index(): void
    {
        $user = $this->authProvider->currentUser();
        $kpis = $this->documentoRepository->getKpis($user);
        $desgloseSedes = $this->documentoRepository->getBreakdownBySede($user);
        $desgloseCategorias = $this->documentoRepository->getBreakdownByCategoria($user);

        // Documentos recientes visibles para el usuario
        $recientes = $this->documentoRepository->findPaginatedWithScope($user, [], 5, 0);

        echo $this->view->render('dashboard/index', [
            'user' => $user,
            'kpis' => $kpis,
            'desgloseSedes' => $desgloseSedes,
            'desgloseCategorias' => $desgloseCategorias,
            'recientes' => $recientes['items'],
        ], 'app');
    }
}
