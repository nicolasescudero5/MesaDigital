<?php

declare(strict_types=1);

namespace App\Models;

class Usuario
{
    public function __construct(
        public ?int $id = null,
        public string $nombre = '',
        public string $email = '',
        public string $rol = 'recepcion_sede',
        public ?int $sede_id = null,
        public ?string $password_hash = null,
        public ?string $google_sub = null,
        public bool $activo = true,
        public ?string $ultimo_login = null,
        public ?string $creado_el = null,
        public ?int $creado_por = null,
        public ?string $modificado_el = null,
        public ?int $modificado_por = null,
        public ?string $sede_nombre = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            nombre: (string)($data['nombre'] ?? ''),
            email: (string)($data['email'] ?? ''),
            rol: (string)($data['rol'] ?? 'recepcion_sede'),
            sede_id: isset($data['sede_id']) && $data['sede_id'] !== null ? (int)$data['sede_id'] : null,
            password_hash: $data['password_hash'] ?? null,
            google_sub: $data['google_sub'] ?? null,
            activo: (bool)($data['activo'] ?? true),
            ultimo_login: $data['ultimo_login'] ?? null,
            creado_el: $data['creado_el'] ?? null,
            creado_por: isset($data['creado_por']) && $data['creado_por'] !== null ? (int)$data['creado_por'] : null,
            modificado_el: $data['modificado_el'] ?? null,
            modificado_por: isset($data['modificado_por']) && $data['modificado_por'] !== null ? (int)$data['modificado_por'] : null,
            sede_nombre: $data['sede_nombre'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'rol' => $this->rol,
            'sede_id' => $this->sede_id,
            'password_hash' => $this->password_hash,
            'google_sub' => $this->google_sub,
            'activo' => $this->activo ? 1 : 0,
            'ultimo_login' => $this->ultimo_login,
            'creado_el' => $this->creado_el,
            'creado_por' => $this->creado_por,
            'modificado_el' => $this->modificado_el,
            'modificado_por' => $this->modificado_por,
        ];
    }

    public function isAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function isRecepcion(): bool
    {
        return $this->rol === 'recepcion_sede';
    }

    public function isResponsable(): bool
    {
        return $this->rol === 'responsable_categoria';
    }

    public function isDireccion(): bool
    {
        return $this->rol === 'direccion_sede';
    }

    public function isSupervision(): bool
    {
        return $this->rol === 'supervision_general';
    }
}
