<?php

declare(strict_types=1);

namespace App\Models;

class DocumentoAdjunto
{
    public function __construct(
        public ?int $id = null,
        public int $documento_id = 0,
        public string $nombre_original = '',
        public string $ruta_almacenamiento = '',
        public string $tipo_mime = '',
        public int $tamano_bytes = 0,
        public bool $es_principal = false,
        public int $subido_por = 0,
        public ?string $creado_el = null,
        public ?string $subido_por_nombre = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            documento_id: (int)($data['documento_id'] ?? 0),
            nombre_original: (string)($data['nombre_original'] ?? ''),
            ruta_almacenamiento: (string)($data['ruta_almacenamiento'] ?? ''),
            tipo_mime: (string)($data['tipo_mime'] ?? ''),
            tamano_bytes: (int)($data['tamano_bytes'] ?? 0),
            es_principal: (bool)($data['es_principal'] ?? false),
            subido_por: (int)($data['subido_por'] ?? 0),
            creado_el: $data['creado_el'] ?? null,
            subido_por_nombre: $data['subido_por_nombre'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'documento_id' => $this->documento_id,
            'nombre_original' => $this->nombre_original,
            'ruta_almacenamiento' => $this->ruta_almacenamiento,
            'tipo_mime' => $this->tipo_mime,
            'tamano_bytes' => $this->tamano_bytes,
            'es_principal' => $this->es_principal ? 1 : 0,
            'subido_por' => $this->subido_por,
            'creado_el' => $this->creado_el,
        ];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->tipo_mime, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->tipo_mime === 'application/pdf';
    }
}
