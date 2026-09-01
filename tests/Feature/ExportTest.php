<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExportTest extends TestCase
{
    /**
     * Criterio N°15: Exportación Excel respeta filtros aplicados
     */
    public function test_exportacion_excel_respeta_filtros(): void
    {
        $admin = $this->usuarioRepo->findById(1);

        // Doc 1 en Sede 1, Recibido
        $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente Sede 1',
            'asunto' => 'Asunto Doc 1',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f1.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f1.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        // Doc 2 en Sede 2, Recibido
        $this->documentoService->crear([
            'sede_id' => 2,
            'categoria_id' => 2,
            'tipo_documento_id' => 2,
            'remitente' => 'Remitente Sede 2',
            'asunto' => 'Asunto Doc 2',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f2.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f2.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        // Filtro: Solo Sede 1
        $filtros = ['sede_id' => 1];
        $result = $this->documentoRepo->findPaginatedWithScope($admin, $filtros, 100, 0);

        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['items'][0]->sede_id);

        $excelHtml = $this->exportService->exportToExcel($result['items']);
        $this->assertStringContainsString('Remitente Sede 1', $excelHtml);
        $this->assertStringNotContainsString('Remitente Sede 2', $excelHtml);
    }
}
