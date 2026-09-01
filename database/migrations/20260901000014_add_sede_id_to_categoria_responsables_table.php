<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSedeIdToCategoriaResponsablesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('categoria_responsables');

        if ($table->hasIndex(['categoria_id', 'email'])) {
            $table->removeIndex(['categoria_id', 'email']);
        }

        $table->addColumn('sede_id', 'integer', [
            'signed' => false,
            'null' => true,
            'after' => 'categoria_id'
        ]);

        $table->addIndex(['sede_id'], ['name' => 'idx_categoria_responsables_sede']);
        $table->addForeignKey('sede_id', 'sedes', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION']);

        $table->update();
    }
}
