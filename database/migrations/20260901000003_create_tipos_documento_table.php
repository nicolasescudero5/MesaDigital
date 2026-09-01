<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTiposDocumentoTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('tipos_documento', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('nombre', 'string', ['limit' => 100])
              ->addColumn('orden', 'integer', ['default' => 0])
              ->addColumn('activo', 'boolean', ['default' => true])
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('creado_por', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('modificado_el', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('modificado_por', 'integer', ['signed' => false, 'null' => true])
              ->addIndex(['nombre'], ['unique' => true, 'name' => 'uq_tipos_documento_nombre'])
              ->create();
    }
}
