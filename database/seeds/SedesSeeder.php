<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class SedesSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'nombre' => 'Colegio del Faro Benavidez', 'color_primario' => '#295E48', 'activo' => 1],
            ['id' => 2, 'nombre' => 'Colegio del Faro Escobar', 'color_primario' => '#3E7D61', 'activo' => 1],
            ['id' => 3, 'nombre' => 'Lighthouse Campus Puertos', 'color_primario' => '#3A82C2', 'activo' => 1],
            ['id' => 4, 'nombre' => 'Northfield Nordelta', 'color_primario' => '#CF364C', 'activo' => 1],
            ['id' => 5, 'nombre' => 'Northfield Puertos', 'color_primario' => '#4E47DD', 'activo' => 1],
        ];

        $table = $this->table('sedes');
        $table->insert($data)->saveData();
    }
}
