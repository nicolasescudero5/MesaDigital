<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\NotificacionEnviada;
use PDO;

class NotificacionRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(
        NotificacionEnviada|int $notifOrDocId,
        ?string $destinatarioEmail = null,
        ?string $tipoEvento = null,
        string $estadoEnvio = 'Pendiente'
    ): int {
        if ($notifOrDocId instanceof NotificacionEnviada) {
            $documentoId = $notifOrDocId->documento_id;
            $destinatarioEmail = $notifOrDocId->destinatario_email;
            $tipoEvento = $notifOrDocId->tipo_evento;
            $estadoEnvio = $notifOrDocId->estado_envio ?: 'Pendiente';
        } else {
            $documentoId = $notifOrDocId;
        }

        $sql = "INSERT INTO notificaciones_enviadas (documento_id, destinatario_email, tipo_evento, estado_envio)
                VALUES (:documento_id, :destinatario_email, :tipo_evento, :estado_envio)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'documento_id' => $documentoId,
            'destinatario_email' => trim($destinatarioEmail),
            'tipo_evento' => $tipoEvento,
            'estado_envio' => $estadoEnvio
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findPendientes(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM notificaciones_enviadas WHERE estado_envio = 'Pendiente' ORDER BY id ASC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => NotificacionEnviada::fromArray($row), $rows);
    }

    public function markAsSent(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notificaciones_enviadas SET estado_envio = 'Enviado', fecha_envio = :fecha_envio, error_mensaje = NULL WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'fecha_envio' => date('Y-m-d H:i:s')
        ]);
    }

    public function markEnviado(int $id): bool
    {
        return $this->markAsSent($id);
    }

    public function markAsFailed(int $id, string $errorMessage): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notificaciones_enviadas SET estado_envio = 'Fallido', intento_numero = intento_numero + 1, error_mensaje = :error WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'error' => $errorMessage
        ]);
    }

    public function markFallido(int $id, string $errorMessage): bool
    {
        return $this->markAsFailed($id, $errorMessage);
    }

    public function findByDocumentoId(int $documentoId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM notificaciones_enviadas WHERE documento_id = :documento_id ORDER BY id DESC");
        $stmt->execute(['documento_id' => $documentoId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => NotificacionEnviada::fromArray($row), $rows);
    }
}
