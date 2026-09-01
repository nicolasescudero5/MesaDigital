<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UsuariosSeeder extends AbstractSeed
{
    public function run(): void
    {
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);

        $data = [
            // Administrador
            [
                'id' => 1,
                'nombre' => 'Nicolás Administrador',
                'email' => 'admin@reditinere.com',
                'rol' => 'administrador',
                'sede_id' => null,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            // Recepción de Sedes
            [
                'id' => 2,
                'nombre' => 'Recepción Benavidez',
                'email' => 'recepcion.benavidez@reditinere.com',
                'rol' => 'recepcion_sede',
                'sede_id' => 1,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 3,
                'nombre' => 'Recepción Escobar',
                'email' => 'recepcion.escobar@reditinere.com',
                'rol' => 'recepcion_sede',
                'sede_id' => 2,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 4,
                'nombre' => 'Recepción Lighthouse',
                'email' => 'recepcion.lighthouse@reditinere.com',
                'rol' => 'recepcion_sede',
                'sede_id' => 3,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 5,
                'nombre' => 'Recepción Nordelta',
                'email' => 'recepcion.nordelta@reditinere.com',
                'rol' => 'recepcion_sede',
                'sede_id' => 4,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 6,
                'nombre' => 'Recepción Puertos',
                'email' => 'recepcion.puertos@reditinere.com',
                'rol' => 'recepcion_sede',
                'sede_id' => 5,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            // Dirección de Sedes
            [
                'id' => 7,
                'nombre' => 'Dirección Benavidez',
                'email' => 'direccion.benavidez@reditinere.com',
                'rol' => 'direccion_sede',
                'sede_id' => 1,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 8,
                'nombre' => 'Dirección Escobar',
                'email' => 'direccion.escobar@reditinere.com',
                'rol' => 'direccion_sede',
                'sede_id' => 2,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 9,
                'nombre' => 'Dirección Lighthouse',
                'email' => 'direccion.lighthouse@reditinere.com',
                'rol' => 'direccion_sede',
                'sede_id' => 3,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 10,
                'nombre' => 'Dirección Nordelta',
                'email' => 'direccion.nordelta@reditinere.com',
                'rol' => 'direccion_sede',
                'sede_id' => 4,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 11,
                'nombre' => 'Dirección Puertos',
                'email' => 'direccion.puertos@reditinere.com',
                'rol' => 'direccion_sede',
                'sede_id' => 5,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            // Responsables de Categorías
            [
                'id' => 12,
                'nombre' => 'Dra. Laura Legales',
                'email' => 'legales@reditinere.com',
                'rol' => 'responsable_categoria',
                'sede_id' => null,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 13,
                'nombre' => 'Martín Capital Humano',
                'email' => 'rrhh@reditinere.com',
                'rol' => 'responsable_categoria',
                'sede_id' => null,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 14,
                'nombre' => 'Esteban Mantenimiento',
                'email' => 'mantenimiento@reditinere.com',
                'rol' => 'responsable_categoria',
                'sede_id' => null,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 15,
                'nombre' => 'Carla Administración',
                'email' => 'administracion@reditinere.com',
                'rol' => 'responsable_categoria',
                'sede_id' => null,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            [
                'id' => 16,
                'nombre' => 'Tomás Sistemas',
                'email' => 'sistemas@reditinere.com',
                'rol' => 'responsable_categoria',
                'sede_id' => null,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
            // Supervisión General
            [
                'id' => 17,
                'nombre' => 'Sofía Supervisora',
                'email' => 'supervision@reditinere.com',
                'rol' => 'supervision_general',
                'sede_id' => null,
                'password_hash' => $passwordHash,
                'activo' => 1
            ],
        ];

        $table = $this->table('usuarios');
        $table->insert($data)->saveData();
    }
}
