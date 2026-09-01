<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use Tests\TestCase;

class DocumentoServiceTest extends TestCase
{
    /**
     * Criterio N°6: Categoría sin responsables activos bloquea creación
     */
    public function test_bloquea_creacion_con_categoria_sin_responsables(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Esta categoría no tiene responsables activos, contactá al Administrador.");

        $user = $this->usuarioRepo->findById(1); // Admin

        $data = [
            'sede_id' => 1,
            'categoria_id' => 4, // Categoría "Sin Responsables" (id 4 en seed)
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente Test',
            'asunto' => 'Asunto Test',
            'descripcion' => 'Descripción detallada del documento',
            'fecha_recepcion' => date('Y-m-d'),
        ];

        $files = [
            ['name' => 'foto.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/foto.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]
        ];

        $this->documentoService->crear($data, $files, $user);
    }

    /**
     * Criterio N°9: Resolución exige constancia ≥ 10 caracteres
     */
    public function test_bloquea_resolucion_sin_constancia(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("La constancia de cierre es obligatoria y debe tener al menos 10 caracteres.");

        $user = $this->usuarioRepo->findById(1); // Admin

        // Crear documento válido primero
        $doc = $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Test Remitente',
            'asunto' => 'Test Asunto',
            'descripcion' => 'Descripción de prueba',
            'fecha_recepcion' => date('Y-m-d')
        ], [
            ['name' => 'doc.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/doc.pdf', 'error' => UPLOAD_ERR_OK, 'size' => 2048]
        ], $user);

        // Intentar resolver con constancia inválida / vacía
        $this->documentoService->resolver((int)$doc->id, 'corto', $user);
    }
}
