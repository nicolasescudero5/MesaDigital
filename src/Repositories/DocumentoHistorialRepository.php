<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DocumentoHistorial;
use PDO;

/**
 * Repositorio de Historial de Auditoría
 * REGLA ESTRICTA DE ARQUITECTURA: ESTE REPOSITORIO ES EXCLUSIVAMENTE DE INSERCIÓN Y LECTURA.
 * JAMÁS CONTIENE MÉTODOS DE ACTUALIZACIÓN NI ELIMINACIÓN (§ 5.2).
 */
class DocumentoHistorialRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByDocumentoId(int $documentoId): array
    {
        $sql = "SELECT h.*, u.nombre AS usuario_nombre, u.rol AS usuario_rol 
                FROM documento_historial h
                LEFT JOIN usuarios u ON h.usuario_id = u.id
                WHERE h.documento_id = :documento_id
                ORDER BY h.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['documento_id' => $documentoId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => DocumentoHistorial::fromArray($row), $rows);
    }

    public function create(
        DocumentoHistorial|int $documentoOrId,
        ?string $accion = null,
        ?string $estadoAnterior = null,
        ?string $estadoNuevo = null,
        ?int $usuarioId = null,
        ?string $detalle = null
    ): int {
        if ($documentoOrId instanceof DocumentoHistorial) {
            $documentoId = $documentoOrId->documento_id;
            $accion = $documentoOrId->accion;
            $estadoAnterior = $documentoOrId->estado_anterior;
            $estadoNuevo = $documentoOrId->estado_nuevo;
            $usuarioId = $documentoOrId->usuario_id;
            $detalle = $documentoOrId->detalle;
            $fechaHora = $documentoOrId->fecha_hora ?: date('Y-m-d H:i:s');
        } else {
            $documentoId = $documentoOrId;
            $fechaHora = date('Y-m-d H:i:s');
        }

        $sql = "INSERT INTO documento_historial (documento_id, fecha_hora, accion, estado_anterior, estado_nuevo, usuario_id, detalle)
                VALUES (:documento_id, :fecha_hora, :accion, :estado_anterior, :estado_nuevo, :usuario_id, :detalle)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'documento_id' => $documentoId,
            'fecha_hora' => $fechaHora,
            'accion' => $accion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'usuario_id' => $usuarioId,
            'detalle' => $detalle
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
