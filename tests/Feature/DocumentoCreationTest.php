<?php

declare(strict_types=1);

namespace Tests\Feature;

use InvalidArgumentException;
use Tests\TestCase;

class DocumentoCreationTest extends TestCase
{
    /**
     * Criterio N°1: Carga válida con adjunto obligatorio queda en estado Recibido
     */
    public function test_crea_documento_con_foto_valida_queda_en_recibido(): void
    {
        $recepcionUser = $this->usuarioRepo->findById(2); // Recepción Benavidez (Sede 1)

        $data = [
            'sede_id' => 1,
            'categoria_id' => 1, // Legales — Laboral
            'tipo_documento_id' => 3, // Telegrama
            'caracter_remitente_id' => 1, // Empleado
            'remitente' => 'Juan Pérez',
            'asunto' => 'Telegrama laboral reclamo haberes',
            'descripcion' => 'Descripción detallada de la notificación recibida en recepción.',
            'fecha_recepcion' => date('Y-m-d'),
            'plazo_legal' => date('Y-m-d', strtotime('+5 days'))
        ];

        $files = [
            [
                'name' => 'telegrama_foto.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/telegrama_foto.jpg',
                'error' => UPLOAD_ERR_OK,
                'size' => 102400
            ]
        ];

        $doc = $this->documentoService->crear($data, $files, $recepcionUser);

        $this->assertNotNull($doc);
        $this->assertNotNull($doc->id);
        $this->assertEquals('Recibido', $doc->estado);
        $this->assertStringStartsWith('MD-' . date('Y') . '-', $doc->codigo);

        // Verificar que se guardó el adjunto
        $adjuntos = $this->adjuntoRepo->findByDocumentoId((int)$doc->id);
        $this->assertCount(1, $adjuntos);
        $this->assertEquals('telegrama_foto.jpg', $adjuntos[0]->nombre_original);
        $this->assertTrue((bool)$adjuntos[0]->es_principal);

        // Verificar que se registró el historial inmutable
        $historial = $this->historialRepo->findByDocumentoId((int)$doc->id);
        $this->assertNotEmpty($historial);
        $this->assertEquals('Cargado', $historial[0]->accion);
        $this->assertEquals('Recibido', $historial[0]->estado_nuevo);
    }

    /**
     * Criterio N°4: Carga con categoría válida dispara notificación a todos los responsables activos
     */
    public function test_notifica_a_todos_los_responsables_activos_al_cargar(): void
    {
        $recepcionUser = $this->usuarioRepo->findById(2);

        $data = [
            'sede_id' => 1,
            'categoria_id' => 1, // Tiene 2 responsables en seed: legales@reditinere.com y estudio.externo@legal.com
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente Test',
            'asunto' => 'Asunto Test',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ];

        $files = [
            ['name' => 'foto.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/foto.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]
        ];

        $doc = $this->documentoService->crear($data, $files, $recepcionUser);

        $pendientes = $this->notificacionRepo->findPendientes(50);
        $emailsNotificados = array_map(fn($n) => $n->destinatario_email, $pendientes);

        $this->assertContains('legales@reditinere.com', $emailsNotificados);
        $this->assertContains('estudio.externo@legal.com', $emailsNotificados);

        // Procesar cola y verificar que el mailer recibe los correos
        $resultado = $this->notificacionService->procesarCola(50);
        $this->assertEquals(2, $resultado['enviadas']);
        $this->assertCount(2, $this->mockMailer->sent);
    }

    /**
     * Criterio N°5: Sin adjunto no guarda nada
     */
    public function test_bloquea_creacion_sin_adjunto(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Debe adjuntar al menos una foto o archivo del documento.");

        $user = $this->usuarioRepo->findById(1);

        $data = [
            'sede_id' => 1,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente',
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ];

        // Archivos vacíos
        $this->documentoService->crear($data, [], $user);
    }

    /**
     * Valida que un documento en Sede Benavídez notifique al responsable específico de Benavídez
     * y en Sede Escobar notifique al responsable de Escobar
     */
    public function test_responsables_especificos_por_sede_son_derivados_correctamente(): void
    {
        $admin = $this->usuarioRepo->findById(1);

        // Crear una nueva categoría 'Oficios Especiales'
        $catId = $this->categoriaRepo->create(new \App\Models\Categoria(
            nombre: 'Oficios Especiales',
            descripcion: 'Oficios con responsable por sede',
            orden: 10,
            activo: true,
            creado_por: 1
        ));

        // Asignar responsable específico para Sede 1 (Benavídez)
        $this->categoriaRepo->addResponsable($catId, 'abogado.benavidez@reditinere.com', null, 1, 1);

        // Asignar responsable específico para Sede 2 (Escobar)
        $this->categoriaRepo->addResponsable($catId, 'abogado.escobar@reditinere.com', null, 2, 1);

        $files = [
            ['name' => 'doc.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/doc.pdf', 'error' => UPLOAD_ERR_OK, 'size' => 1024]
        ];

        // 1. Crear documento en Sede 1 (Benavidez)
        $doc1 = $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => $catId,
            'tipo_documento_id' => 1,
            'remitente' => 'Juzgado Benavidez',
            'asunto' => 'Oficio Benavidez',
            'descripcion' => 'Notificacion oficial',
            'fecha_recepcion' => date('Y-m-d')
        ], $files, $admin);

        // 2. Crear documento en Sede 2 (Escobar)
        $doc2 = $this->documentoService->crear([
            'sede_id' => 2,
            'categoria_id' => $catId,
            'tipo_documento_id' => 1,
            'remitente' => 'Juzgado Escobar',
            'asunto' => 'Oficio Escobar',
            'descripcion' => 'Notificacion oficial',
            'fecha_recepcion' => date('Y-m-d')
        ], $files, $admin);

        // Verificar notificaciones generadas
        $notifsDoc1 = $this->notificacionRepo->findByDocumentoId((int)$doc1->id);
        $notifsDoc2 = $this->notificacionRepo->findByDocumentoId((int)$doc2->id);

        $emailsDoc1 = array_map(fn($n) => $n->destinatario_email, $notifsDoc1);
        $emailsDoc2 = array_map(fn($n) => $n->destinatario_email, $notifsDoc2);

        $this->assertContains('abogado.benavidez@reditinere.com', $emailsDoc1);
        $this->assertNotContains('abogado.escobar@reditinere.com', $emailsDoc1);

        $this->assertContains('abogado.escobar@reditinere.com', $emailsDoc2);
        $this->assertNotContains('abogado.benavidez@reditinere.com', $emailsDoc2);
    }
}
