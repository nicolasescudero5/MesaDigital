<?php

declare(strict_types=1);

namespace App\Models;

class Categoria
{
    public function __construct(
        public ?int $id = null,
        public string $nombre = '',
        public ?string $descripcion = null,
        public int $orden = 0,
        public bool $es_reserva = false,
        public bool $activo = true,
        public ?string $creado_el = null,
        public ?int $creado_por = null,
        public ?string $modificado_el = null,
        public ?int $modificado_por = null,
        public array $responsables = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            nombre: (string)($data['nombre'] ?? ''),
            descripcion: $data['descripcion'] ?? null,
            orden: (int)($data['orden'] ?? 0),
            es_reserva: (bool)($data['es_reserva'] ?? false),
            activo: (bool)($data['activo'] ?? true),
            creado_el: $data['creado_el'] ?? null,
            creado_por: isset($data['creado_por']) && $data['creado_por'] !== null ? (int)$data['creado_por'] : null,
            modificado_el: $data['modificado_el'] ?? null,
            modificado_por: isset($data['modificado_por']) && $data['modificado_por'] !== null ? (int)$data['modificado_por'] : null,
            responsables: $data['responsables'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'orden' => $this->orden,
            'es_reserva' => $this->es_reserva ? 1 : 0,
            'activo' => $this->activo ? 1 : 0,
            'creado_el' => $this->creado_el,
            'creado_por' => $this->creado_por,
            'modificado_el' => $this->modificado_el,
            'modificado_por' => $this->modificado_por,
        ];
    }
}
