<?php

declare(strict_types=1);

namespace App\Models;

class NotificacionEnviada
{
    public function __construct(
        public ?int $id = null,
        public int $documento_id = 0,
        public string $destinatario_email = '',
        public string $tipo_evento = 'Documento_cargado',
        public string $estado_envio = 'Pendiente',
        public int $intento_numero = 1,
        public ?string $fecha_envio = null,
        public ?string $error_mensaje = null,
        public ?string $creado_el = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            documento_id: (int)($data['documento_id'] ?? 0),
            destinatario_email: (string)($data['destinatario_email'] ?? ''),
            tipo_evento: (string)($data['tipo_evento'] ?? 'Documento_cargado'),
            estado_envio: (string)($data['estado_envio'] ?? 'Pendiente'),
            intento_numero: (int)($data['intento_numero'] ?? 1),
            fecha_envio: $data['fecha_envio'] ?? null,
            error_mensaje: $data['error_mensaje'] ?? null,
            creado_el: $data['creado_el'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'documento_id' => $this->documento_id,
            'destinatario_email' => $this->destinatario_email,
            'tipo_evento' => $this->tipo_evento,
            'estado_envio' => $this->estado_envio,
            'intento_numero' => $this->intento_numero,
            'fecha_envio' => $this->fecha_envio,
            'error_mensaje' => $this->error_mensaje,
            'creado_el' => $this->creado_el,
        ];
    }
}
