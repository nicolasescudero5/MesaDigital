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

    /**
     * Recepción solo ve su sede asociada en el desglose de sedes del dashboard
     */
    public function test_recepcion_solo_ve_su_sede_en_desglose_sedes_dashboard(): void
    {
        $recepcionSede1 = $this->usuarioRepo->findById(2); // Sede 1 (Benavidez)
        $recepcionSede2 = $this->usuarioRepo->findById(3); // Sede 2 (Escobar)
        $admin = $this->usuarioRepo->findById(1);

        // Recepción Sede 1 solo ve Sede 1
        $breakdownRecepcion1 = $this->documentoRepo->getBreakdownBySede($recepcionSede1);
        $this->assertCount(1, $breakdownRecepcion1);
        $this->assertEquals(1, $breakdownRecepcion1[0]['id']);
        $this->assertEquals('Colegio del Faro Benavidez', $breakdownRecepcion1[0]['nombre']);

        // Recepción Sede 2 solo ve Sede 2
        $breakdownRecepcion2 = $this->documentoRepo->getBreakdownBySede($recepcionSede2);
        $this->assertCount(1, $breakdownRecepcion2);
        $this->assertEquals(2, $breakdownRecepcion2[0]['id']);
        $this->assertEquals('Colegio del Faro Escobar', $breakdownRecepcion2[0]['nombre']);

        // Administrador ve todas las sedes
        $breakdownAdmin = $this->documentoRepo->getBreakdownBySede($admin);
        $this->assertCount(5, $breakdownAdmin);
    }

    /**
     * Recepción con múltiples sedes asignadas ve documentos de todas sus sedes y no de terceros
     */
    public function test_recepcion_con_multiples_sedes_ve_documentos_de_todas_sus_sedes_pero_no_de_otras(): void
    {
        $admin = $this->usuarioRepo->findById(1);

        // Crear usuario recepcionista asignado a Sede 1 y Sede 2
        $userMulti = new \App\Models\Usuario(
            nombre: 'Recepcionista Flotante',
            email: 'flotante@reditinere.com',
            rol: 'recepcion_sede',
            activo: true,
            sedes_ids: [1, 2]
        );
        $userId = $this->usuarioRepo->create($userMulti, [1, 2]);
        $loadedUser = $this->usuarioRepo->findById($userId);

        $this->assertEquals([1, 2], $loadedUser->getSedesIds());
        $this->assertTrue($loadedUser->canAccessSede(1));
        $this->assertTrue($loadedUser->canAccessSede(2));
        $this->assertFalse($loadedUser->canAccessSede(3));

        // Crear documentos en Sede 1, Sede 2 y Sede 3
        $docSede1 = $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente Sede 1',
            'asunto' => 'Asunto 1',
            'descripcion' => 'Descripción 1',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f1.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f1.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        $docSede2 = $this->documentoService->crear([
            'sede_id' => 2,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente Sede 2',
            'asunto' => 'Asunto 2',
            'descripcion' => 'Descripción 2',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f2.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f2.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        $docSede3 = $this->documentoService->crear([
            'sede_id' => 3,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente Sede 3',
            'asunto' => 'Asunto 3',
            'descripcion' => 'Descripción 3',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f3.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f3.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $admin);

        // findPaginatedWithScope: debe ver Sede 1 y Sede 2, pero no Sede 3
        $result = $this->documentoRepo->findPaginatedWithScope($loadedUser, [], 100, 0);
        $ids = array_map(fn($d) => $d->id, $result['items']);

        $this->assertContains($docSede1->id, $ids);
        $this->assertContains($docSede2->id, $ids);
        $this->assertNotContains($docSede3->id, $ids);

        // canUserView
        $this->assertTrue($this->documentoRepo->canUserView($loadedUser, $docSede1));
        $this->assertTrue($this->documentoRepo->canUserView($loadedUser, $docSede2));
        $this->assertFalse($this->documentoRepo->canUserView($loadedUser, $docSede3));

        // Permiso de creación: crear en Sede 2 es válido
        $docCreadoPorMulti = $this->documentoService->crear([
            'sede_id' => 2,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente Flotante',
            'asunto' => 'Asunto Flotante',
            'descripcion' => 'Descripción Flotante',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f4.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f4.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $loadedUser);

        $this->assertNotNull($docCreadoPorMulti->id);

        // Permiso de creación: intentar crear en Sede 3 (no autorizada) lanza excepción
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("No tenés permiso para crear documentos en otra sede.");

        $this->documentoService->crear([
            'sede_id' => 3,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Remitente No Autorizado',
            'asunto' => 'Asunto No Autorizado',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => date('Y-m-d')
        ], [['name' => 'f5.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f5.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]], $loadedUser);
    }

    /**
     * Dashboard y desglose para usuario con múltiples sedes
     */
    public function test_recepcion_con_multiples_sedes_desglose_dashboard(): void
    {
        $userMulti = new \App\Models\Usuario(
            nombre: 'Directora Multi',
            email: 'directora.multi@reditinere.com',
            rol: 'direccion_sede',
            activo: true,
            sedes_ids: [1, 3]
        );
        $userId = $this->usuarioRepo->create($userMulti, [1, 3]);
        $loadedUser = $this->usuarioRepo->findById($userId);

        $breakdownSedes = $this->documentoRepo->getBreakdownBySede($loadedUser);
        $this->assertCount(2, $breakdownSedes);

        $sedeIds = array_column($breakdownSedes, 'id');
        $this->assertContains(1, $sedeIds);
        $this->assertContains(3, $sedeIds);
        $this->assertNotContains(2, $sedeIds);
    }

    /**
     * Sincronización al actualizar sedes de usuario
     */
    public function test_sincronizacion_sedes_al_actualizar_usuario(): void
    {
        $user = new \App\Models\Usuario(
            nombre: 'Recepcionista Cambio',
            email: 'recep.cambio@reditinere.com',
            rol: 'recepcion_sede',
            activo: true
        );
        $userId = $this->usuarioRepo->create($user, [1]);
        $loaded = $this->usuarioRepo->findById($userId);
        $this->assertEquals([1], $loaded->getSedesIds());

        // Actualizar a sedes 2, 4 y 5
        $this->usuarioRepo->update($loaded, [2, 4, 5]);
        $updated = $this->usuarioRepo->findById($userId);
        $this->assertEquals([2, 4, 5], $updated->getSedesIds());

        // Validar que findAll también carga las múltiples sedes
        $all = $this->usuarioRepo->findAll();
        $found = null;
        foreach ($all as $u) {
            if ($u->id === $userId) {
                $found = $u;
                break;
            }
        }
        $this->assertNotNull($found);
        $this->assertEquals([2, 4, 5], $found->getSedesIds());
    }
}
