<?php

declare(strict_types=1);

namespace Tests\Security;

use InvalidArgumentException;
use Tests\TestCase;

class RbacScopeTest extends TestCase
{
    /**
     * Criterio N°7: Recepción no ve documentos de otra sede
     */
    public function test_recepcion_no_ve_documentos_de_otra_sede(): void
    {
        $admin = $this->usuarioRepo->findById(1);
        $recepcionSede1 = $this->usuarioRepo->findById(2); // Sede 1 (Benavidez)
        $recepcionSede2 = $this->usuarioRepo->findById(3); // Sede 2 (Escobar)

        // Crear doc en Sede 1
        $docSede1 = $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente Benavidez',
            'asunto' => 'Asunto Benavidez',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f1.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f1.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        // Crear doc en Sede 2
        $docSede2 = $this->documentoService->crear([
            'sede_id' => 2,
            'categoria_id' => 2,
            'tipo_documento_id' => 2,
            'remitente' => 'Remitente Escobar',
            'asunto' => 'Asunto Escobar',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f2.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f2.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        // 1. Recepción Sede 1 busca documentos
        $resultSede1 = $this->documentoRepo->findPaginatedWithScope($recepcionSede1, [], 100, 0);
        $idsSede1 = array_map(fn($d) => $d->id, $resultSede1['items']);

        $this->assertContains($docSede1->id, $idsSede1);
        $this->assertNotContains($docSede2->id, $idsSede1);

        // 2. Comprobar canUserView
        $this->assertTrue($this->documentoRepo->canUserView($recepcionSede1, $docSede1));
        $this->assertFalse($this->documentoRepo->canUserView($recepcionSede1, $docSede2));
    }

    /**
     * Criterio N°3: Recepción no puede editar ni gestionar documento ajeno a su sede (403 / Exception)
     */
    public function test_recepcion_no_puede_editar_documento_ajeno_a_su_sede(): void
    {
        $admin = $this->usuarioRepo->findById(1);
        $recepcionSede1 = $this->usuarioRepo->findById(2); // Sede 1

        // Crear doc en Sede 2
        $docSede2 = $this->documentoService->crear([
            'sede_id' => 2,
            'categoria_id' => 2,
            'tipo_documento_id' => 2,
            'remitente' => 'Remitente Escobar',
            'asunto' => 'Asunto Escobar',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f2.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f2.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("No tenés permiso para acceder a este documento.");

        $this->documentoService->tomarDocumento((int)$docSede2->id, $recepcionSede1);
    }
}
