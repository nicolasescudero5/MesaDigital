<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Auth\SimulatedAuthProvider;
use Tests\TestCase;

class SimulatedLoginTest extends TestCase
{
    /**
     * Criterio N°12: Login simulado permite entrar como cada uno de los 5 roles con su alcance correcto
     */
    public function test_login_simulado_cubre_los_5_roles(): void
    {
        $provider = new SimulatedAuthProvider($this->usuarioRepo, 'local', true);

        // Rol 1: Administrador (ID 1)
        $admin = $provider->attemptSimulated(1);
        $this->assertNotNull($admin);
        $this->assertEquals('administrador', $admin->rol);
        $this->assertNull($admin->sede_id);
        $this->assertTrue($admin->isAdministrador());
        $provider->logout();

        // Rol 2: Recepción de Sede (ID 2, Sede 1)
        $recepcion = $provider->attemptSimulated(2);
        $this->assertNotNull($recepcion);
        $this->assertEquals('recepcion_sede', $recepcion->rol);
        $this->assertEquals(1, $recepcion->sede_id);
        $this->assertTrue($recepcion->isRecepcion());
        $provider->logout();

        // Rol 3: Dirección de Sede (ID 4, Sede 1)
        $direccion = $provider->attemptSimulated(4);
        $this->assertNotNull($direccion);
        $this->assertEquals('direccion_sede', $direccion->rol);
        $this->assertEquals(1, $direccion->sede_id);
        $this->assertTrue($direccion->isDireccion());
        $provider->logout();

        // Rol 4: Responsable de Categoría (ID 5)
        $responsable = $provider->attemptSimulated(5);
        $this->assertNotNull($responsable);
        $this->assertEquals('responsable_categoria', $responsable->rol);
        $this->assertTrue($responsable->isResponsable());
        $provider->logout();

        // Rol 5: Supervisión General (ID 6)
        $supervision = $provider->attemptSimulated(6);
        $this->assertNotNull($supervision);
        $this->assertEquals('supervision_general', $supervision->rol);
        $this->assertTrue($supervision->isSupervision());
        $provider->logout();
    }
}
