<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class RecordatorioServiceTest extends TestCase
{
    /**
     * Criterio N°11: Alertas de vencimiento a 3 y 1 día
     */
    public function test_job_alerta_vencimiento_dispara_a_3_y_1_dia(): void
    {
        $user = $this->usuarioRepo->findById(1);

        // Documento venciendo en 3 días
        $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente 3d',
            'asunto' => 'Asunto 3d',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d'),
            'plazo_legal' => date('Y-m-d', strtotime('+3 days'))
        ], [['name' => 'doc1.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/doc1.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $user);

        // Documento venciendo en 1 día
        $this->documentoService->crear([
            'sede_id' => 2,
            'categoria_id' => 2,
            'tipo_documento_id' => 2,
            'remitente' => 'Remitente 1d',
            'asunto' => 'Asunto 1d',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d'),
            'plazo_legal' => date('Y-m-d', strtotime('+1 day'))
        ], [['name' => 'doc2.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/doc2.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $user);

        // Ejecutar alertas de vencimiento para 3 y 1 día
        $resultados = $this->recordatorioService->procesarAlertasVencimiento([3, 1]);

        $this->assertEquals(1, $resultados[3]);
        $this->assertEquals(1, $resultados[1]);

        // Verificar que las notificaciones se encolaron
        $pendientes = $this->notificacionRepo->findPendientes(50);
        $tiposEncolados = array_map(fn($n) => $n->tipo_evento, $pendientes);

        $this->assertContains('Alerta_vencimiento_3d', $tiposEncolados);
        $this->assertContains('Alerta_vencimiento_1d', $tiposEncolados);
    }
}
