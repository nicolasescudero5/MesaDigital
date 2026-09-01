<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DocumentoAdjunto;
use PDO;

class DocumentoAdjuntoRepository
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?DocumentoAdjunto
    {
        $sql = "SELECT a.*, u.nombre AS subido_por_nombre 
                FROM documento_adjuntos a 
                JOIN usuarios u ON a.subido_por = u.id 
                WHERE a.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? DocumentoAdjunto::fromArray($row) : null;
    }

    /**
     * @return DocumentoAdjunto[]
     */
    public function findByDocumentoId(int $documentoId): array
    {
        $sql = "SELECT a.*, u.nombre AS subido_por_nombre 
                FROM documento_adjuntos a 
                JOIN usuarios u ON a.subido_por = u.id 
                WHERE a.documento_id = :documento_id 
                ORDER BY a.es_principal DESC, a.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['documento_id' => $documentoId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => DocumentoAdjunto::fromArray($row), $rows);
    }

    public function create(DocumentoAdjunto $adjunto): int
    {
        $sql = "INSERT INTO documento_adjuntos (documento_id, nombre_original, ruta_almacenamiento, tipo_mime, tamano_bytes, es_principal, subido_por) 
                VALUES (:documento_id, :nombre_original, :ruta_almacenamiento, :tipo_mime, :tamano_bytes, :es_principal, :subido_por)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'documento_id' => $adjunto->documento_id,
            'nombre_original' => $adjunto->nombre_original,
            'ruta_almacenamiento' => $adjunto->ruta_almacenamiento,
            'tipo_mime' => $adjunto->tipo_mime,
            'tamano_bytes' => $adjunto->tamano_bytes,
            'es_principal' => $adjunto->es_principal ? 1 : 0,
            'subido_por' => $adjunto->subido_por
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
