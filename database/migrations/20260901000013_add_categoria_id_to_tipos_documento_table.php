<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCategoriaIdToTiposDocumentoTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('tipos_documento');
        $table->addColumn('categoria_id', 'integer', [
            'signed' => false,
            'null' => true,
            'after' => 'nombre'
        ])
        ->addForeignKey('categoria_id', 'categorias', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'CASCADE',
            'constraint' => 'fk_tipos_documento_categoria'
        ])
        ->update();
    }
}
