<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CaracteresRemitenteSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'nombre' => 'Empleado', 'orden' => 1, 'activo' => 1],
            ['id' => 2, 'nombre' => 'Familia', 'orden' => 2, 'activo' => 1],
            ['id' => 3, 'nombre' => 'Sindicato', 'orden' => 3, 'activo' => 1],
            ['id' => 4, 'nombre' => 'Municipalidad/Organismo público', 'orden' => 4, 'activo' => 1],
            ['id' => 5, 'nombre' => 'Juzgado', 'orden' => 5, 'activo' => 1],
            ['id' => 6, 'nombre' => 'Proveedor', 'orden' => 6, 'activo' => 1],
            ['id' => 7, 'nombre' => 'Otro', 'orden' => 7, 'activo' => 1],
        ];

        $table = $this->table('caracteres_remitente');
        $table->insert($data)->saveData();
    }
}
