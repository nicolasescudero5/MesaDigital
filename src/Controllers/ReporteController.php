<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Repositories\DocumentoRepository;
use App\Services\ExportService;

class ReporteController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private DocumentoRepository $documentoRepository,
        private ExportService $exportService
    ) {}

    public function exportCsv(): void
    {
        $user = $this->authProvider->currentUser();
        $filters = $this->getFiltersFromRequest();

        // Obtener todos los documentos respetando el scope del usuario y los filtros
        $result = $this->documentoRepository->findPaginatedWithScope($user, $filters, 10000, 0);

        $csv = $this->exportService->exportToCsv($result['items']);

        $filename = 'mesa_digital_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $csv;
        exit;
    }

    public function exportExcel(): void
    {
        $user = $this->authProvider->currentUser();
        $filters = $this->getFiltersFromRequest();

        $result = $this->documentoRepository->findPaginatedWithScope($user, $filters, 10000, 0);

        $excel = $this->exportService->exportToExcel($result['items']);

        $filename = 'mesa_digital_export_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $excel;
        exit;
    }

    public function exportPdf(): void
    {
        $user = $this->authProvider->currentUser();
        $filters = $this->getFiltersFromRequest();

        $result = $this->documentoRepository->findPaginatedWithScope($user, $filters, 10000, 0);

        $pdfHtml = $this->exportService->exportToPdf($result['items']);

        header('Content-Type: text/html; charset=UTF-8');
        echo $pdfHtml;
        exit;
    }

    private function getFiltersFromRequest(): array
    {
        return [
            'sede_id' => $_GET['sede_id'] ?? null,
            'categoria_id' => $_GET['categoria_id'] ?? null,
            'tipo_documento_id' => $_GET['tipo_documento_id'] ?? null,
            'estado' => $_GET['estado'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
            'search' => $_GET['search'] ?? null,
            'solo_vencidos' => !empty($_GET['solo_vencidos']),
            'incluir_anulados' => !empty($_GET['incluir_anulados']),
        ];
    }
}
