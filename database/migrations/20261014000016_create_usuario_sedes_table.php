<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuarioSedesTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('usuario_sedes')) {
            return;
        }

        $table = $this->table('usuario_sedes', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('usuario_id', 'integer', ['signed' => false])
              ->addColumn('sede_id', 'integer', ['signed' => false])
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['usuario_id', 'sede_id'], ['unique' => true, 'name' => 'uq_usuario_sede'])
              ->addIndex(['sede_id'], ['name' => 'idx_usuario_sedes_sede'])
              ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('sede_id', 'sedes', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
