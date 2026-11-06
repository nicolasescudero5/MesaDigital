<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDirectoresNivelAndDocumentoAccesos extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('usuarios')) {
            $usuarios = $this->table('usuarios');

            // Ampliar enum rol para incluir direccion_nivel
            $this->execute("ALTER TABLE `usuarios` MODIFY COLUMN `rol` ENUM('administrador','recepcion_sede','responsable_categoria','direccion_sede','direccion_nivel','supervision_general') COLLATE utf8mb4_unicode_ci DEFAULT NULL");

            if (!$usuarios->hasColumn('cargo_nivel')) {
                $usuarios->addColumn('cargo_nivel', 'string', [
                    'limit' => 100,
                    'null' => true,
                    'after' => 'rol'
                ])->update();
            }
        }

        if (!$this->hasTable('documento_accesos')) {
            $table = $this->table('documento_accesos', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
            $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
                  ->addColumn('documento_id', 'integer', ['signed' => false])
                  ->addColumn('usuario_id', 'integer', ['signed' => false])
                  ->addColumn('tipo_acceso', 'enum', ['values' => ['director_sede', 'director_nivel']])
                  ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addColumn('creado_por', 'integer', ['signed' => false, 'null' => true])
                  ->addIndex(['documento_id', 'usuario_id'], ['unique' => true, 'name' => 'uq_documento_acceso_usuario'])
                  ->addIndex(['usuario_id'], ['name' => 'idx_documento_accesos_usuario'])
                  ->addForeignKey('documento_id', 'documentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                  ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                  ->create();
        }
    }
}
