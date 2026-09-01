<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DocumentoComentario;
use PDO;

class DocumentoComentarioRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * @return DocumentoComentario[]
     */
    public function findByDocumentoId(int $documentoId): array
    {
        $sql = "SELECT c.*, u.nombre AS usuario_nombre, u.rol AS usuario_rol 
                FROM documento_comentarios c 
                JOIN usuarios u ON c.usuario_id = u.id 
                WHERE c.documento_id = :documento_id 
                ORDER BY c.creado_el ASC, c.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['documento_id' => $documentoId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => DocumentoComentario::fromArray($row), $rows);
    }

    public function create(DocumentoComentario $comentario): int
    {
        $sql = "INSERT INTO documento_comentarios (documento_id, usuario_id, comentario) 
                VALUES (:documento_id, :usuario_id, :comentario)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'documento_id' => $comentario->documento_id,
            'usuario_id' => $comentario->usuario_id,
            'comentario' => $comentario->comentario
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
