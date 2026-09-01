<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Auth\SimulatedAuthProvider;
use RuntimeException;
use Tests\TestCase;

class ProductionGuardrailTest extends TestCase
{
    /**
     * Criterio N°13: App aborta el arranque si APP_ENV=production y login simulado activo
     */
    public function test_arranque_aborta_si_login_simulado_en_produccion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("CRITICAL SECURITY ERROR: El login simulado está habilitado en entorno de PRODUCCIÓN.");

        // Intentar instanciar provider simulado con env 'production' y simuladoHabilitado true
        new SimulatedAuthProvider($this->usuarioRepo, 'production', true);
    }
}
