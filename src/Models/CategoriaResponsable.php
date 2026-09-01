<?php

declare(strict_types=1);

namespace App\Models;

class CategoriaResponsable
{
    public function __construct(
        public ?int $id = null,
        public int $categoria_id = 0,
        public ?int $sede_id = null,
        public string $email = '',
        public ?int $usuario_id = null,
        public bool $activo = true,
        public ?string $creado_el = null,
        public ?int $creado_por = null,
        public ?string $modificado_el = null,
        public ?int $modificado_por = null,
        public ?string $usuario_nombre = null,
        public ?string $sede_nombre = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            categoria_id: (int)($data['categoria_id'] ?? 0),
            sede_id: isset($data['sede_id']) && $data['sede_id'] !== null && $data['sede_id'] !== '' ? (int)$data['sede_id'] : null,
            email: (string)($data['email'] ?? ''),
            usuario_id: isset($data['usuario_id']) && $data['usuario_id'] !== null ? (int)$data['usuario_id'] : null,
            activo: (bool)($data['activo'] ?? true),
            creado_el: $data['creado_el'] ?? null,
            creado_por: isset($data['creado_por']) && $data['creado_por'] !== null ? (int)$data['creado_por'] : null,
            modificado_el: $data['modificado_el'] ?? null,
            modificado_por: isset($data['modificado_por']) && $data['modificado_por'] !== null ? (int)$data['modificado_por'] : null,
            usuario_nombre: $data['usuario_nombre'] ?? null,
            sede_nombre: $data['sede_nombre'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'categoria_id' => $this->categoria_id,
            'sede_id' => $this->sede_id,
            'email' => $this->email,
            'usuario_id' => $this->usuario_id,
            'activo' => $this->activo ? 1 : 0,
            'creado_el' => $this->creado_el,
            'creado_por' => $this->creado_por,
            'modificado_el' => $this->modificado_el,
            'modificado_por' => $this->modificado_por,
        ];
    }
}
