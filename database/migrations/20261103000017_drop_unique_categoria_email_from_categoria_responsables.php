<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropUniqueCategoriaEmailFromCategoriaResponsables extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('categoria_responsables')) {
            return;
        }

        $table = $this->table('categoria_responsables');

        // Asegurar que la columna sede_id exista
        if (!$table->hasColumn('sede_id')) {
            $table->addColumn('sede_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'categoria_id'
            ])
            ->addIndex(['sede_id'], ['name' => 'idx_categoria_responsables_sede'])
            ->addForeignKey('sede_id', 'sedes', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
        }

        // Asegurar que exista un índice normal sobre categoria_id antes de quitar uq_categoria_email (requerido por FK en MySQL)
        if (!$table->hasIndexByName('idx_categoria_responsables_cat_email')) {
            $table->addIndex(['categoria_id', 'email', 'sede_id'], ['name' => 'idx_categoria_responsables_cat_email'])
                  ->update();
        }

        // Eliminar el índice único antiguo uq_categoria_email si todavía existe
        if ($table->hasIndexByName('uq_categoria_email')) {
            $table->removeIndexByName('uq_categoria_email')
                  ->update();
        } elseif ($table->hasIndex(['categoria_id', 'email'])) {
            $table->removeIndex(['categoria_id', 'email'])
                  ->update();
        }
    }
}
