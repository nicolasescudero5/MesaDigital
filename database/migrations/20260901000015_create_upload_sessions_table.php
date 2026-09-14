<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUploadSessionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('upload_sessions', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
              ->addColumn('token', 'string', ['limit' => 64])
              ->addColumn('usuario_id', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('estado', 'enum', ['values' => ['pendiente', 'completado', 'expirado'], 'default' => 'pendiente'])
              ->addColumn('archivos_json', 'text', ['null' => true])
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('expira_el', 'datetime', ['null' => true])
              ->addIndex(['token'], ['unique' => true, 'name' => 'uq_upload_sessions_token'])
              ->addIndex(['estado'], ['name' => 'idx_upload_sessions_estado'])
              ->create();
    }
}
