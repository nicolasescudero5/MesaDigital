<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDocumentosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('documentos', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
              ->addColumn('codigo', 'string', ['limit' => 20])
              ->addColumn('sede_id', 'integer', ['signed' => false])
              ->addColumn('categoria_id', 'integer', ['signed' => false])
              ->addColumn('tipo_documento_id', 'integer', ['signed' => false])
              ->addColumn('caracter_remitente_id', 'integer', ['signed' => false, 'null' => true])
              ->addColumn('remitente', 'string', ['limit' => 200])
              ->addColumn('asunto', 'string', ['limit' => 200])
              ->addColumn('descripcion', 'text')
              ->addColumn('fecha_recepcion', 'date')
              ->addColumn('plazo_legal', 'date', ['null' => true])
              ->addColumn('estado', 'enum', ['values' => ['Recibido', 'En curso', 'Resuelto', 'Cerrado'], 'default' => 'Recibido'])
              ->addColumn('constancia_cierre', 'text', ['null' => true])
              ->addColumn('creado_por', 'integer', ['signed' => false])
              ->addColumn('activo', 'boolean', ['default' => true])
              ->addColumn('motivo_anulacion', 'text', ['null' => true])
              ->addColumn('creado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('modificado_el', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('modificado_por', 'integer', ['signed' => false, 'null' => true])
              ->addIndex(['codigo'], ['unique' => true, 'name' => 'uq_documentos_codigo'])
              ->addIndex(['sede_id'], ['name' => 'idx_documentos_sede'])
              ->addIndex(['categoria_id'], ['name' => 'idx_documentos_categoria'])
              ->addIndex(['estado'], ['name' => 'idx_documentos_estado'])
              ->addIndex(['fecha_recepcion'], ['name' => 'idx_documentos_fecha_recepcion'])
              ->addIndex(['plazo_legal'], ['name' => 'idx_documentos_plazo_legal'])
              ->addIndex(['remitente'], ['name' => 'idx_documentos_remitente'])
              ->addForeignKey('sede_id', 'sedes', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
              ->addForeignKey('categoria_id', 'categorias', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
              ->addForeignKey('tipo_documento_id', 'tipos_documento', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
              ->addForeignKey('caracter_remitente_id', 'caracteres_remitente', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
              ->addForeignKey('creado_por', 'usuarios', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
              ->create();
    }
}
