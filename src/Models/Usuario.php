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
        public ?string $sede_nombre = null,
        public array $sedes_ids = [],
        public array $sedes_nombres = []
    ) {
        if (empty($this->sedes_ids) && $this->sede_id !== null) {
            $this->sedes_ids = [(int)$this->sede_id];
        }
        if ($this->sede_id === null && !empty($this->sedes_ids)) {
            $this->sede_id = (int)$this->sedes_ids[0];
        }
        if (empty($this->sedes_nombres) && $this->sede_nombre !== null) {
            $this->sedes_nombres = [$this->sede_nombre];
        }
    }

    public static function fromArray(array $data): self
    {
        $sedesIds = [];
        if (isset($data['sedes_ids'])) {
            $sedesIds = array_values(array_filter(array_map('intval', (array)$data['sedes_ids'])));
        } elseif (isset($data['sede_id']) && $data['sede_id'] !== null) {
            $sedesIds = [(int)$data['sede_id']];
        }

        $sedesNombres = [];
        if (isset($data['sedes_nombres'])) {
            $sedesNombres = array_values((array)$data['sedes_nombres']);
        } elseif (!empty($data['sede_nombre'])) {
            $sedesNombres = [(string)$data['sede_nombre']];
        }

        $user = new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            nombre: (string)($data['nombre'] ?? ''),
            email: (string)($data['email'] ?? ''),
            rol: (string)($data['rol'] ?? 'recepcion_sede'),
            sede_id: isset($data['sede_id']) && $data['sede_id'] !== null ? (int)$data['sede_id'] : (!empty($sedesIds) ? $sedesIds[0] : null),
            password_hash: $data['password_hash'] ?? null,
            google_sub: $data['google_sub'] ?? null,
            activo: (bool)($data['activo'] ?? true),
            ultimo_login: $data['ultimo_login'] ?? null,
            creado_el: $data['creado_el'] ?? null,
            creado_por: isset($data['creado_por']) && $data['creado_por'] !== null ? (int)$data['creado_por'] : null,
            modificado_el: $data['modificado_el'] ?? null,
            modificado_por: isset($data['modificado_por']) && $data['modificado_por'] !== null ? (int)$data['modificado_por'] : null,
            sede_nombre: $data['sede_nombre'] ?? null,
            sedes_ids: $sedesIds,
            sedes_nombres: $sedesNombres
        );

        return $user;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'rol' => $this->rol,
            'sede_id' => $this->sede_id,
            'sedes_ids' => $this->getSedesIds(),
            'sedes_nombres' => $this->sedes_nombres,
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

    public function getSedesIds(): array
    {
        if (!empty($this->sedes_ids)) {
            return array_values(array_unique(array_map('intval', $this->sedes_ids)));
        }
        return $this->sede_id !== null ? [(int)$this->sede_id] : [];
    }

    public function canAccessSede(int $sedeId): bool
    {
        if ($this->isAdministrador() || $this->isSupervision() || $this->isResponsable()) {
            return true;
        }

        return in_array($sedeId, $this->getSedesIds(), true);
    }

    public function getSedesNombres(): string
    {
        if (!empty($this->sedes_nombres)) {
            return implode(', ', $this->sedes_nombres);
        }
        return $this->sede_nombre ?? 'Todas las sedes';
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
