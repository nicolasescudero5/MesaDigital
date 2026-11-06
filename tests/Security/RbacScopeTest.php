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

    /**
     * Responsable de categoría asignado a múltiples sedes (ej. Sede 1 y Sede 2) solo ve documentos de esas sedes
     */
    public function test_responsable_con_multiples_sedes_en_categoria_ve_y_recibe_solo_sus_sedes(): void
    {
        $admin = $this->usuarioRepo->findById(1);

        // Crear usuario responsable con permiso en Sede 1 y Sede 2
        $respUser = new \App\Models\Usuario(
            nombre: 'Responsable Sedes 1 y 2',
            email: 'resp.sede1y2@reditinere.com',
            rol: 'responsable_categoria',
            activo: true,
            sedes_ids: [1, 2]
        );
        $respUserId = $this->usuarioRepo->create($respUser, [1, 2]);
        $loadedResp = $this->usuarioRepo->findById($respUserId);

        // Validar que el usuario solo tiene acceso a sedes 1 y 2
        $this->assertEquals([1, 2], $loadedResp->getSedesIds());
        $this->assertTrue($loadedResp->canAccessSede(1));
        $this->assertTrue($loadedResp->canAccessSede(2));
        $this->assertFalse($loadedResp->canAccessSede(3));

        // Crear categoría y asignarle a este responsable las sedes 1 y 2
        $catId = $this->categoriaRepo->create(new \App\Models\Categoria(
            nombre: 'Mantenimiento Zonal',
            descripcion: 'Categoría con responsable multisede',
            orden: 20,
            activo: true,
            creado_por: 1
        ));

        $this->categoriaRepo->addResponsable($catId, $loadedResp->email, $respUserId, 1, 1);
        $this->categoriaRepo->addResponsable($catId, $loadedResp->email, $respUserId, 2, 1);

        // Asignar otro responsable para la Sede 3
        $this->categoriaRepo->addResponsable($catId, 'resp.sede3@reditinere.com', null, 3, 1);

        $responsablesCat = $this->categoriaRepo->getResponsablesByCategoriaId($catId);
        $this->assertCount(3, $responsablesCat);

        // Crear documentos en Sede 1, Sede 2 y Sede 3 en esta categoría
        $files = [['name' => 'f.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/f.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 1024]];

        $doc1 = $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => $catId,
            'tipo_documento_id' => 1,
            'remitente' => 'Proveedor 1',
            'asunto' => 'Asunto Sede 1',
            'descripcion' => 'Desc 1',
            'fecha_recepcion' => date('Y-m-d')
        ], $files, $admin);

        $doc2 = $this->documentoService->crear([
            'sede_id' => 2,
            'categoria_id' => $catId,
            'tipo_documento_id' => 1,
            'remitente' => 'Proveedor 2',
            'asunto' => 'Asunto Sede 2',
            'descripcion' => 'Desc 2',
            'fecha_recepcion' => date('Y-m-d')
        ], $files, $admin);

        $doc3 = $this->documentoService->crear([
            'sede_id' => 3,
            'categoria_id' => $catId,
            'tipo_documento_id' => 1,
            'remitente' => 'Proveedor 3',
            'asunto' => 'Asunto Sede 3',
            'descripcion' => 'Desc 3',
            'fecha_recepcion' => date('Y-m-d')
        ], $files, $admin);

        // Verificar que loadedResp ve doc1 y doc2 pero NO doc3
        $result = $this->documentoRepo->findPaginatedWithScope($loadedResp, [], 100, 0);
        $ids = array_map(fn($d) => $d->id, $result['items']);

        $this->assertContains($doc1->id, $ids);
        $this->assertContains($doc2->id, $ids);
        $this->assertNotContains($doc3->id, $ids);

        $this->assertTrue($this->documentoRepo->canUserView($loadedResp, $doc1));
        $this->assertTrue($this->documentoRepo->canUserView($loadedResp, $doc2));
        $this->assertFalse($this->documentoRepo->canUserView($loadedResp, $doc3));
    }

    /**
     * Directores de Sede y Directores de Nivel: notificaciones opcionales y permisos por documento
     */
    public function test_directores_sede_y_nivel_notificaciones_y_permisos_por_documento(): void
    {
        $recepcionSede1 = $this->usuarioRepo->findById(2); // Recepción Benavidez (Sede 1)
        $directorSede1 = $this->usuarioRepo->findById(4);  // Dirección Benavidez (Sede 1, email: direccion.benavidez@reditinere.com)

        // Crear dos Directores de Nivel en Sede 1 y uno en Sede 2
        $dirNivelPrimariaId = $this->usuarioRepo->create(new \App\Models\Usuario(
            nombre: 'Directora Nivel Primario Benavidez',
            email: 'primaria.benavidez@reditinere.com',
            rol: 'direccion_nivel',
            cargo_nivel: 'Nivel Primario',
            activo: true
        ), [1]);
        $dirNivelPrimaria = $this->usuarioRepo->findById($dirNivelPrimariaId);

        $dirNivelSecundariaId = $this->usuarioRepo->create(new \App\Models\Usuario(
            nombre: 'Director Nivel Secundario Benavidez',
            email: 'secundaria.benavidez@reditinere.com',
            rol: 'direccion_nivel',
            cargo_nivel: 'Nivel Secundario',
            activo: true
        ), [1]);
        $dirNivelSecundaria = $this->usuarioRepo->findById($dirNivelSecundariaId);

        $dirNivelEscobarId = $this->usuarioRepo->create(new \App\Models\Usuario(
            nombre: 'Directora Nivel Inicial Escobar',
            email: 'inicial.escobar@reditinere.com',
            rol: 'direccion_nivel',
            cargo_nivel: 'Nivel Inicial',
            activo: true
        ), [2]);
        $dirNivelEscobar = $this->usuarioRepo->findById($dirNivelEscobarId);

        $files = [['name' => 'nota.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/nota.pdf', 'error' => UPLOAD_ERR_OK, 'size' => 2048]];

        // 1. Documento cargado SIN notificar a Director de Sede ni Director de Nivel
        $docSinDirectores = $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Familia García',
            'asunto' => 'Consulta general',
            'descripcion' => 'Documento solo para el responsable de categoría',
            'fecha_recepcion' => date('Y-m-d')
        ], $files, $recepcionSede1);

        $this->assertFalse($this->documentoRepo->canUserView($directorSede1, $docSinDirectores));
        $this->assertFalse($this->documentoRepo->canUserView($dirNivelPrimaria, $docSinDirectores));
        $this->assertFalse($this->documentoRepo->canUserView($dirNivelSecundaria, $docSinDirectores));

        // 2. Documento cargado marcando "Notificar a Director de Sede" y "Notificar a Director de Nivel" (seleccionando solo Nivel Primario)
        $docConDirectores = $this->documentoService->crear([
            'sede_id' => 1,
            'categoria_id' => 1,
            'tipo_documento_id' => 1,
            'remitente' => 'Familia Rodríguez',
            'asunto' => 'Nota urgente para Dirección',
            'descripcion' => 'Requiere conocimiento de Dirección de Sede y Nivel Primario',
            'fecha_recepcion' => date('Y-m-d'),
            'notificar_director_sede' => '1',
            'notificar_director_nivel' => '1',
            'directores_nivel_ids' => [$dirNivelPrimariaId, $dirNivelEscobarId] // Escobar debe ser ignorado por no pertenecer a Sede 1
        ], $files, $recepcionSede1);

        // Permisos canUserView
        $this->assertTrue($this->documentoRepo->canUserView($directorSede1, $docConDirectores));
        $this->assertTrue($this->documentoRepo->canUserView($dirNivelPrimaria, $docConDirectores));
        $this->assertFalse($this->documentoRepo->canUserView($dirNivelSecundaria, $docConDirectores));
        $this->assertFalse($this->documentoRepo->canUserView($dirNivelEscobar, $docConDirectores));

        // Visibilidad en Bandeja (findPaginatedWithScope)
        $bandejaDirSede = $this->documentoRepo->findPaginatedWithScope($directorSede1, [], 100, 0);
        $idsDirSede = array_map(fn($d) => $d->id, $bandejaDirSede['items']);
        $this->assertContains($docConDirectores->id, $idsDirSede);
        $this->assertNotContains($docSinDirectores->id, $idsDirSede);

        $bandejaDirPrimaria = $this->documentoRepo->findPaginatedWithScope($dirNivelPrimaria, [], 100, 0);
        $idsDirPrimaria = array_map(fn($d) => $d->id, $bandejaDirPrimaria['items']);
        $this->assertContains($docConDirectores->id, $idsDirPrimaria);
        $this->assertNotContains($docSinDirectores->id, $idsDirPrimaria);

        // Procesar cola de notificaciones y verificar destinatarios enviados
        $this->notificacionService->procesarCola(50, (int)$docConDirectores->id);
        $emailsEnviados = array_column($this->mockMailer->sent, 'to');

        $this->assertContains('legales@reditinere.com', $emailsEnviados);
        $this->assertContains('direccion.benavidez@reditinere.com', $emailsEnviados);
        $this->assertContains('primaria.benavidez@reditinere.com', $emailsEnviados);
        $this->assertNotContains('secundaria.benavidez@reditinere.com', $emailsEnviados);
        $this->assertNotContains('inicial.escobar@reditinere.com', $emailsEnviados);
    }
}
