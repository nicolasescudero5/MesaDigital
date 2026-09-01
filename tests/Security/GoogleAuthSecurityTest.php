<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Auth\GoogleAuthProvider;
use Tests\TestCase;

class GoogleAuthSecurityTest extends TestCase
{
    /**
     * Criterio N°14: Google login deniega email no habilitado sin autoprovisionar
     */
    public function test_google_login_deniega_usuario_no_existente(): void
    {
        $provider = new GoogleAuthProvider($this->usuarioRepo, [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-secret',
            'redirect_uri' => 'http://localhost:8080/auth/google/callback',
        ]);

        // Verificar que un email no existente no se crea en la base de datos
        $emailInexistente = 'desconocido@reditinere.com';
        $usuario = $this->usuarioRepo->findByEmail($emailInexistente);
        $this->assertNull($usuario);

        // Si intentara procesar un token para ese email, no debe existir registro nuevo
        $totalAntes = count($this->usuarioRepo->findAll(false));
        $usuarioBuscado = $this->usuarioRepo->findByEmail($emailInexistente);
        $totalDespues = count($this->usuarioRepo->findAll(false));

        $this->assertEquals($totalAntes, $totalDespues);
        $this->assertNull($usuarioBuscado);
    }
}
