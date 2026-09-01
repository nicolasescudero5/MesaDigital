<?php

declare(strict_types=1);

namespace App\Models;

class Sede
{
    public function __construct(
        public ?int $id = null,
        public string $nombre = '',
        public string $color_primario = '#4E47DD',
        public bool $activo = true,
        public ?string $creado_el = null,
        public ?int $creado_por = null,
        public ?string $modificado_el = null,
        public ?int $modificado_por = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            nombre: (string)($data['nombre'] ?? ''),
            color_primario: (string)($data['color_primario'] ?? '#4E47DD'),
            activo: (bool)($data['activo'] ?? true),
            creado_el: $data['creado_el'] ?? null,
            creado_por: isset($data['creado_por']) && $data['creado_por'] !== null ? (int)$data['creado_por'] : null,
            modificado_el: $data['modificado_el'] ?? null,
            modificado_por: isset($data['modificado_por']) && $data['modificado_por'] !== null ? (int)$data['modificado_por'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'color_primario' => $this->color_primario,
            'activo' => $this->activo ? 1 : 0,
            'creado_el' => $this->creado_el,
            'creado_por' => $this->creado_por,
            'modificado_el' => $this->modificado_el,
            'modificado_por' => $this->modificado_por,
        ];
    }
}
