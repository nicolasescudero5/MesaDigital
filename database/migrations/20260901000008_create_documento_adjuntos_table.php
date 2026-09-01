<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDocumentoAdjuntosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('documento_adjuntos', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('documento_id', 'integer', ['signed' => false])
              ->addColumn('nombre_original', 'string', ['limit' => 255])
              ->addColumn('ruta_almacenamiento', 'string', ['limit' => 500])
              ->addColumn('tipo_mime', 'string', ['limit' => 100])
              ->addColumn('tamano_bytes', 'integer', ['signed' => false])
              ->addColumn('es_principal', 'boolean', ['default' => false])
              ->addColumn('subido_por', 'integer', ['signed' => false])
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['documento_id'], ['name' => 'idx_adjuntos_documento'])
              ->addForeignKey('documento_id', 'documentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('subido_por', 'usuarios', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
              ->create();
    }
}
