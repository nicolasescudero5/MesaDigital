<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNotificacionesEnviadasTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('notificaciones_enviadas', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
              ->addColumn('documento_id', 'integer', ['signed' => false])
              ->addColumn('destinatario_email', 'string', ['limit' => 150])
              ->addColumn('tipo_evento', 'enum', [
                  'values' => [
                      'Documento_cargado',
                      'Recordatorio_48h',
                      'Alerta_vencimiento_3d',
                      'Alerta_vencimiento_1d',
                      'Documento_reclasificado',
                      'Documento_resuelto',
                      'Sin_responsable'
                  ]
              ])
              ->addColumn('estado_envio', 'enum', [
                  'values' => ['Pendiente', 'Enviado', 'Fallido'],
                  'default' => 'Pendiente'
              ])
              ->addColumn('intento_numero', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'signed' => false, 'default' => 1])
              ->addColumn('fecha_envio', 'datetime', ['null' => true])
              ->addColumn('error_mensaje', 'text', ['null' => true])
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['documento_id'], ['name' => 'idx_notif_documento'])
              ->addIndex(['estado_envio'], ['name' => 'idx_notif_estado'])
              ->addForeignKey('documento_id', 'documentos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
