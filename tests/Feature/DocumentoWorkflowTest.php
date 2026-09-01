<?php

declare(strict_types=1);

namespace Tests\Feature;

use InvalidArgumentException;
use Tests\TestCase;

class DocumentoWorkflowTest extends TestCase
{
    /**
     * Criterio N°8: Responsable marca En curso y registra en historial
     */
    public function test_responsable_marca_en_curso_y_registra_historial(): void
    {
        $admin = $this->usuarioRepo->findById(1);
        $responsable = $this->usuarioRepo->findById(5); // Laura Legales (responsable categoría 1)

        // Crear documento en Recibido
        $doc = $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente',
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        $this->assertEquals('Recibido', $doc->estado);

        // Responsable toma el documento
        $docEnCurso = $this->documentoService->tomarDocumento((int)$doc->id, $responsable);
        $this->assertEquals('En curso', $docEnCurso->estado);

        // Validar historial
        $historial = $this->historialRepo->findByDocumentoId((int)$doc->id);
        $acciones = array_map(fn($h) => $h->accion, $historial);
        $this->assertContains('Marcado_en_curso', $acciones);
    }

    /**
     * Criterio N°10: Reclasificación exige motivo (≥ 10 chars), notifica a nuevos responsables y loguea
     */
    public function test_reclasificacion_exige_motivo_y_renotifica(): void
    {
        $admin = $this->usuarioRepo->findById(1);
        $responsable = $this->usuarioRepo->findById(5); // Laura Legales

        $doc = $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => 1, // Cat 1: Legales
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente RRHH',
            'asunto' => 'Asunto para RRHH',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        // 1. Validar que motivo corto falla
        try {
            $this->documentoService->reclasificar((int)$doc->id, 3, 'corto', $responsable);
            $this->fail("Debería haber fallado por motivo menor a 10 caracteres");
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("al menos 10 caracteres", $e->getMessage());
        }

        // 2. Reclasificar con motivo válido a Cat 3 (Capital Humano, responsable rrhh@reditinere.com)
        $docReclasificado = $this->documentoService->reclasificar(
            (int)$doc->id,
            3,
            'El trámite corresponde al área de Recursos Humanos para su legajo.',
            $responsable
        );

        $this->assertEquals(3, $docReclasificado->categoria_id);

        // Verificar historial
        $historial = $this->historialRepo->findByDocumentoId((int)$doc->id);
        $acciones = array_map(fn($h) => $h->accion, $historial);
        $this->assertContains('Reclasificado', $acciones);

        // Verificar notificación encolada para rrhh@reditinere.com
        $pendientes = $this->notificacionRepo->findPendientes(50);
        $notifsReclasif = array_filter($pendientes, fn($n) => $n->tipo_evento === 'Documento_reclasificado');
        $this->assertNotEmpty($notifsReclasif);
        $this->assertEquals('rrhh@reditinere.com', reset($notifsReclasif)->destinatario_email);
    }
}
