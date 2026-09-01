<?php

declare(strict_types=1);

namespace App\Models;

class DocumentoHistorial
{
    public function __construct(
        public ?int $id = null,
        public int $documento_id = 0,
        public ?string $fecha_hora = null,
        public string $accion = 'Cargado',
        public ?string $estado_anterior = null,
        public ?string $estado_nuevo = null,
        public ?int $usuario_id = null,
        public ?string $detalle = null,
        public ?string $usuario_nombre = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            documento_id: (int)($data['documento_id'] ?? 0),
            fecha_hora: $data['fecha_hora'] ?? null,
            accion: (string)($data['accion'] ?? 'Cargado'),
            estado_anterior: $data['estado_anterior'] ?? null,
            estado_nuevo: $data['estado_nuevo'] ?? null,
            usuario_id: isset($data['usuario_id']) && $data['usuario_id'] !== null ? (int)$data['usuario_id'] : null,
            detalle: $data['detalle'] ?? null,
            usuario_nombre: $data['usuario_nombre'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'documento_id' => $this->documento_id,
            'fecha_hora' => $this->fecha_hora,
            'accion' => $this->accion,
            'estado_anterior' => $this->estado_anterior,
            'estado_nuevo' => $this->estado_nuevo,
            'usuario_id' => $this->usuario_id,
            'detalle' => $this->detalle,
        ];
    }
}
