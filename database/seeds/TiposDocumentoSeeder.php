<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class TiposDocumentoSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'nombre' => 'Oficio', 'orden' => 1, 'activo' => 1],
            ['id' => 2, 'nombre' => 'Carta documento', 'orden' => 2, 'activo' => 1],
            ['id' => 3, 'nombre' => 'Telegrama', 'orden' => 3, 'activo' => 1],
            ['id' => 4, 'nombre' => 'Cédula de notificación', 'orden' => 4, 'activo' => 1],
            ['id' => 5, 'nombre' => 'Paquete/Encomienda', 'orden' => 5, 'activo' => 1],
            ['id' => 6, 'nombre' => 'Nota de reclamo o pedido', 'orden' => 6, 'activo' => 1],
            ['id' => 7, 'nombre' => 'Otro', 'orden' => 7, 'activo' => 1],
        ];

        $table = $this->table('tipos_documento');
        $table->insert($data)->saveData();
    }
}
