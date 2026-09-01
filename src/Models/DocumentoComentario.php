<?php

declare(strict_types=1);

namespace App\Models;

class DocumentoComentario
{
    public function __construct(
        public ?int $id = null,
        public int $documento_id = 0,
        public int $usuario_id = 0,
        public string $comentario = '',
        public ?string $creado_el = null,
        public ?string $usuario_nombre = null,
        public ?string $usuario_rol = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            documento_id: (int)($data['documento_id'] ?? 0),
            usuario_id: (int)($data['usuario_id'] ?? 0),
            comentario: (string)($data['comentario'] ?? ''),
            creado_el: $data['creado_el'] ?? null,
            usuario_nombre: $data['usuario_nombre'] ?? null,
            usuario_rol: $data['usuario_rol'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'documento_id' => $this->documento_id,
            'usuario_id' => $this->usuario_id,
            'comentario' => $this->comentario,
            'creado_el' => $this->creado_el,
        ];
    }
}
