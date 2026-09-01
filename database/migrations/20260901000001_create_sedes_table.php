<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSedesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sedes', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('nombre', 'string', ['limit' => 100])
              ->addColumn('color_primario', 'char', ['limit' => 7, 'default' => '#4E47DD'])
              ->addColumn('activo', 'boolean', ['default' => true])
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('creado_por', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('modificado_el', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('modificado_por', 'integer', ['signed' => false, 'null' => true])
              ->addIndex(['nombre'], ['unique' => true, 'name' => 'uq_sedes_nombre'])
              ->create();
    }
}
