<?php

declare(strict_types=1);

namespace App\Models;

class Documento
{
    public function __construct(
        public ?int $id = null,
        public string $codigo = '',
        public int $sede_id = 0,
        public int $categoria_id = 0,
        public int $tipo_documento_id = 0,
        public ?int $caracter_remitente_id = null,
        public string $remitente = '',
        public string $asunto = '',
        public string $descripcion = '',
        public string $fecha_recepcion = '',
        public ?string $plazo_legal = null,
        public string $estado = 'Recibido',
        public ?string $constancia_cierre = null,
        public int $creado_por = 0,
        public bool $activo = true,
        public ?string $motivo_anulacion = null,
        public ?string $creado_el = null,
        public ?string $modificado_el = null,
        public ?int $modificado_por = null,
        // Joined / eager loaded properties
        public ?string $sede_nombre = null,
        public ?string $sede_color = null,
        public ?string $categoria_nombre = null,
        public ?string $tipo_documento_nombre = null,
        public ?string $caracter_remitente_nombre = null,
        public ?string $creador_nombre = null,
        public array $adjuntos = [],
        public array $historial = [],
        public array $comentarios = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            codigo: (string)($data['codigo'] ?? ''),
            sede_id: (int)($data['sede_id'] ?? 0),
            categoria_id: (int)($data['categoria_id'] ?? 0),
            tipo_documento_id: (int)($data['tipo_documento_id'] ?? 0),
            caracter_remitente_id: isset($data['caracter_remitente_id']) && $data['caracter_remitente_id'] !== null ? (int)$data['caracter_remitente_id'] : null,
            remitente: (string)($data['remitente'] ?? ''),
            asunto: (string)($data['asunto'] ?? ''),
            descripcion: (string)($data['descripcion'] ?? ''),
            fecha_recepcion: (string)($data['fecha_recepcion'] ?? date('Y-m-d')),
            plazo_legal: $data['plazo_legal'] ?? null,
            estado: (string)($data['estado'] ?? 'Recibido'),
            constancia_cierre: $data['constancia_cierre'] ?? null,
            creado_por: (int)($data['creado_por'] ?? 0),
            activo: (bool)($data['activo'] ?? true),
            motivo_anulacion: $data['motivo_anulacion'] ?? null,
            creado_el: $data['creado_el'] ?? null,
            modificado_el: $data['modificado_el'] ?? null,
            modificado_por: isset($data['modificado_por']) && $data['modificado_por'] !== null ? (int)$data['modificado_por'] : null,
            sede_nombre: $data['sede_nombre'] ?? null,
            sede_color: $data['sede_color'] ?? null,
            categoria_nombre: $data['categoria_nombre'] ?? null,
            tipo_documento_nombre: $data['tipo_documento_nombre'] ?? null,
            caracter_remitente_nombre: $data['caracter_remitente_nombre'] ?? null,
            creador_nombre: $data['creador_nombre'] ?? null,
            adjuntos: $data['adjuntos'] ?? [],
            historial: $data['historial'] ?? [],
            comentarios: $data['comentarios'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'sede_id' => $this->sede_id,
            'categoria_id' => $this->categoria_id,
            'tipo_documento_id' => $this->tipo_documento_id,
            'caracter_remitente_id' => $this->caracter_remitente_id,
            'remitente' => $this->remitente,
            'asunto' => $this->asunto,
            'descripcion' => $this->descripcion,
            'fecha_recepcion' => $this->fecha_recepcion,
            'plazo_legal' => $this->plazo_legal,
            'estado' => $this->estado,
            'constancia_cierre' => $this->constancia_cierre,
            'creado_por' => $this->creado_por,
            'activo' => $this->activo ? 1 : 0,
            'motivo_anulacion' => $this->motivo_anulacion,
            'creado_el' => $this->creado_el,
            'modificado_el' => $this->modificado_el,
            'modificado_por' => $this->modificado_por,
        ];
    }

    public function isVencido(): bool
    {
        if (!$this->plazo_legal || in_array($this->estado, ['Resuelto', 'Cerrado'], true)) {
            return false;
        }
        return strtotime($this->plazo_legal) < strtotime(date('Y-m-d'));
    }

    public function diasParaVencimiento(): ?int
    {
        if (!$this->plazo_legal) {
            return null;
        }
        $today = new \DateTime(date('Y-m-d'));
        $plazo = new \DateTime($this->plazo_legal);
        $diff = $today->diff($plazo);
        return $diff->invert ? -$diff->days : $diff->days;
    }
}
