<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class DocumentosSeeder extends AbstractSeed
{
    public function run(): void
    {
        // 1. Documentos
        $year = date('Y');
        $docs = [
            // Doc 1: Recibido (Sede Benavidez, Legales Laboral, con plazo legal a 3 días)
            [
                'id' => 1,
                'codigo' => "MD-{$year}-000001",
                'sede_id' => 1,
                'categoria_id' => 1,
                'tipo_documento_id' => 3, // Telegrama
                'caracter_remitente_id' => 1, // Empleado
                'remitente' => 'Juan Carlos Pérez (Docente)',
                'asunto' => 'Telegrama laboral - Reclamo de haberes',
                'descripcion' => 'Se recibe telegrama laboral intimando aclaración de situación registral.',
                'fecha_recepcion' => date('Y-m-d'),
                'plazo_legal' => date('Y-m-d', strtotime('+3 days')),
                'estado' => 'Recibido',
                'constancia_cierre' => null,
                'creado_por' => 2, // Recepción Benavidez
                'activo' => 1,
                'motivo_anulacion' => null,
                'creado_el' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            ],
            // Doc 2: En curso (Sede Escobar, Legales Civil, tomado por responsable)
            [
                'id' => 2,
                'codigo' => "MD-{$year}-000002",
                'sede_id' => 2,
                'categoria_id' => 2,
                'tipo_documento_id' => 2, // Carta Documento
                'caracter_remitente_id' => 2, // Familia
                'remitente' => 'Estudio Jurídico Gómez & Asoc.',
                'asunto' => 'Carta documento - Solicitud de información contractual',
                'descripcion' => 'Requerimiento sobre aranceles ciclo lectivo 2026.',
                'fecha_recepcion' => date('Y-m-d', strtotime('-2 days')),
                'plazo_legal' => date('Y-m-d', strtotime('+1 day')),
                'estado' => 'En curso',
                'constancia_cierre' => null,
                'creado_por' => 3, // Recepción Escobar
                'activo' => 1,
                'motivo_anulacion' => null,
                'creado_el' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            // Doc 3: Resuelto (Sede Lighthouse, Real Estate, con constancia)
            [
                'id' => 3,
                'codigo' => "MD-{$year}-000003",
                'sede_id' => 3,
                'categoria_id' => 5,
                'tipo_documento_id' => 6, // Nota
                'caracter_remitente_id' => 6, // Proveedor
                'remitente' => 'Ascensores del Norte S.A.',
                'asunto' => 'Certificado de mantenimiento trimestral',
                'descripcion' => 'Entrega de certificado y protocolo de inspección técnica obligatoria.',
                'fecha_recepcion' => date('Y-m-d', strtotime('-5 days')),
                'plazo_legal' => null,
                'estado' => 'Resuelto',
                'constancia_cierre' => 'Se recepcionó el certificado de mantenimiento y se archivó copia en la carpeta de infraestructura.',
                'creado_por' => 4, // Recepción Lighthouse
                'activo' => 1,
                'motivo_anulacion' => null,
                'creado_el' => date('Y-m-d H:i:s', strtotime('-5 days')),
            ],
            // Doc 4: Cerrado (Sede Nordelta, Administración, archivado)
            [
                'id' => 4,
                'codigo' => "MD-{$year}-000004",
                'sede_id' => 4,
                'categoria_id' => 7,
                'tipo_documento_id' => 5, // Paquete
                'caracter_remitente_id' => 6, // Proveedor
                'remitente' => 'Librería Escolar Central',
                'asunto' => 'Factura y remito de materiales de arte',
                'descripcion' => 'Paquete con insumos para el taller de plástica del nivel primario.',
                'fecha_recepcion' => date('Y-m-d', strtotime('-15 days')),
                'plazo_legal' => null,
                'estado' => 'Cerrado',
                'constancia_cierre' => 'Materiales entregados al departamento de arte y factura derivada a contabilidad.',
                'creado_por' => 5, // Recepción Nordelta
                'activo' => 1,
                'motivo_anulacion' => null,
                'creado_el' => date('Y-m-d H:i:s', strtotime('-15 days')),
            ],
        ];

        $this->table('documentos')->insert($docs)->saveData();

        // 2. Adjuntos de prueba
        $adjuntos = [
            [
                'id' => 1,
                'documento_id' => 1,
                'nombre_original' => 'telegrama_laboral.jpg',
                'ruta_almacenamiento' => 'storage/documentos/seed_placeholder.jpg',
                'tipo_mime' => 'image/jpeg',
                'tamano_bytes' => 102400,
                'es_principal' => 1,
                'subido_por' => 2,
                'creado_el' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            ],
            [
                'id' => 2,
                'documento_id' => 2,
                'nombre_original' => 'carta_documento.pdf',
                'ruta_almacenamiento' => 'storage/documentos/seed_placeholder.pdf',
                'tipo_mime' => 'application/pdf',
                'tamano_bytes' => 204800,
                'es_principal' => 1,
                'subido_por' => 3,
                'creado_el' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'id' => 3,
                'documento_id' => 3,
                'nombre_original' => 'certificado_inspeccion.jpg',
                'ruta_almacenamiento' => 'storage/documentos/seed_placeholder.jpg',
                'tipo_mime' => 'image/jpeg',
                'tamano_bytes' => 150000,
                'es_principal' => 1,
                'subido_por' => 4,
                'creado_el' => date('Y-m-d H:i:s', strtotime('-5 days')),
            ],
            [
                'id' => 4,
                'documento_id' => 4,
                'nombre_original' => 'remito_factura.jpg',
                'ruta_almacenamiento' => 'storage/documentos/seed_placeholder.jpg',
                'tipo_mime' => 'image/jpeg',
                'tamano_bytes' => 120000,
                'es_principal' => 1,
                'subido_por' => 5,
                'creado_el' => date('Y-m-d H:i:s', strtotime('-15 days')),
            ],
        ];

        $this->table('documento_adjuntos')->insert($adjuntos)->saveData();

        // 3. Historial de eventos
        $historial = [
            // Doc 1
            [
                'documento_id' => 1,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'accion' => 'Cargado',
                'estado_anterior' => null,
                'estado_nuevo' => 'Recibido',
                'usuario_id' => 2,
                'detalle' => 'Documento ingresado en Mesa Digital con 1 adjunto.'
            ],
            [
                'documento_id' => 1,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-59 minutes')),
                'accion' => 'Notificacion_enviada',
                'estado_anterior' => null,
                'estado_nuevo' => null,
                'usuario_id' => null,
                'detalle' => 'Notificación enviada por email a los responsables de Legales — Laboral.'
            ],

            // Doc 2
            [
                'documento_id' => 2,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'accion' => 'Cargado',
                'estado_anterior' => null,
                'estado_nuevo' => 'Recibido',
                'usuario_id' => 3,
                'detalle' => 'Documento ingresado en Mesa Digital.'
            ],
            [
                'documento_id' => 2,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'accion' => 'Marcado_en_curso',
                'estado_anterior' => 'Recibido',
                'estado_nuevo' => 'En curso',
                'usuario_id' => 12, // Laura Legales
                'detalle' => 'Documento tomado para gestión legal.'
            ],

            // Doc 3
            [
                'documento_id' => 3,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'accion' => 'Cargado',
                'estado_anterior' => null,
                'estado_nuevo' => 'Recibido',
                'usuario_id' => 4,
                'detalle' => 'Documento ingresado en Mesa Digital.'
            ],
            [
                'documento_id' => 3,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-4 days')),
                'accion' => 'Marcado_en_curso',
                'estado_anterior' => 'Recibido',
                'estado_nuevo' => 'En curso',
                'usuario_id' => 14, // Esteban Mantenimiento
                'detalle' => 'Tomado por responsable de mantenimiento.'
            ],
            [
                'documento_id' => 3,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'accion' => 'Marcado_resuelto',
                'estado_anterior' => 'En curso',
                'estado_nuevo' => 'Resuelto',
                'usuario_id' => 14,
                'detalle' => 'Constancia: Se recepcionó el certificado de mantenimiento y se archivó copia en la carpeta de infraestructura.'
            ],

            // Doc 4
            [
                'documento_id' => 4,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'accion' => 'Cargado',
                'estado_anterior' => null,
                'estado_nuevo' => 'Recibido',
                'usuario_id' => 5,
                'detalle' => 'Documento ingresado en Mesa Digital.'
            ],
            [
                'documento_id' => 4,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-14 days')),
                'accion' => 'Marcado_en_curso',
                'estado_anterior' => 'Recibido',
                'estado_nuevo' => 'En curso',
                'usuario_id' => 15,
                'detalle' => 'Tomado para gestión contable.'
            ],
            [
                'documento_id' => 4,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-13 days')),
                'accion' => 'Marcado_resuelto',
                'estado_anterior' => 'En curso',
                'estado_nuevo' => 'Resuelto',
                'usuario_id' => 15,
                'detalle' => 'Constancia: Materiales entregados al departamento de arte y factura derivada a contabilidad.'
            ],
            [
                'documento_id' => 4,
                'fecha_hora' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'accion' => 'Cerrado',
                'estado_anterior' => 'Resuelto',
                'estado_nuevo' => 'Cerrado',
                'usuario_id' => 1,
                'detalle' => 'Archivo definitivo del documento.'
            ],
        ];

        $this->table('documento_historial')->insert($historial)->saveData();
    }
}
