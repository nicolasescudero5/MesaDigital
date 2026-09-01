<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDocumentoComentariosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('documento_comentarios', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('documento_id', 'integer', ['signed' => false])
              ->addColumn('usuario_id', 'integer', ['signed' => false])
              ->addColumn('comentario', 'text')
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['documento_id'], ['name' => 'idx_comentarios_documento'])
              ->addForeignKey('documento_id', 'documentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
              ->create();
    }
}
