<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('nombre', 'string', ['limit' => 150])
              ->addColumn('email', 'string', ['limit' => 150])
              ->addColumn('rol', 'enum', ['values' => ['administrador', 'recepcion_sede', 'responsable_categoria', 'direccion_sede', 'supervision_general']])
              ->addColumn('sede_id', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('google_sub', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('activo', 'boolean', ['default' => true])
              ->addColumn('ultimo_login', 'datetime', ['null' => true])
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('creado_por', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('modificado_el', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('modificado_por', 'integer', ['signed' => false, 'null' => true])
              ->addIndex(['email'], ['unique' => true, 'name' => 'uq_usuarios_email'])
              ->addIndex(['rol'], ['name' => 'idx_usuarios_rol'])
              ->addForeignKey('sede_id', 'sedes', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
              ->create();
    }
}
