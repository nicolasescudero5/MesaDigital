<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDocumentoHistorialTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('documento_historial', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
              ->addColumn('documento_id', 'integer', ['signed' => false])
              ->addColumn('fecha_hora', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('accion', 'enum', [
                  'values' => [
                      'Cargado',
                      'Notificacion_enviada',
                      'Notificacion_fallida',
                      'Marcado_en_curso',
                      'Reclasificado',
                      'Comentario',
                      'Adjunto_agregado',
                      'Plazo_legal_modificado',
                      'Marcado_resuelto',
                      'Cerrado',
                      'Anulado'
                  ]
              ])
              ->addColumn('estado_anterior', 'enum', [
                  'values' => ['Recibido', 'En curso', 'Resuelto', 'Cerrado'],
                  'null' => true
              ])
              ->addColumn('estado_nuevo', 'enum', [
                  'values' => ['Recibido', 'En curso', 'Resuelto', 'Cerrado'],
                  'null' => true
              ])
              ->addColumn('usuario_id', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('detalle', 'text', ['null' => true])
              ->addIndex(['documento_id'], ['name' => 'idx_historial_documento'])
              ->addIndex(['fecha_hora'], ['name' => 'idx_historial_fecha'])
              ->addForeignKey('documento_id', 'documentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
              ->create();
    }
}
