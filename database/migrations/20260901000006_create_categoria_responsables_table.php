<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCategoriaResponsablesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('categoria_responsables', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('categoria_id', 'integer', ['signed' => false])
              ->addColumn('email', 'string', ['limit' => 150])
              ->addColumn('usuario_id', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('activo', 'boolean', ['default' => true])
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('creado_por', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('modificado_el', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('modificado_por', 'integer', ['signed' => false, 'null' => true])
              ->addIndex(['categoria_id', 'email'], ['unique' => true, 'name' => 'uq_categoria_email'])
              ->addIndex(['usuario_id'], ['name' => 'idx_categoria_responsables_usuario'])
              ->addForeignKey('categoria_id', 'categorias', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
              ->create();
    }
}
