<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TipoDocumento;
use PDO;

class TipoDocumentoRepository
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?TipoDocumento
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*, c.nombre as categoria_nombre 
            FROM tipos_documento t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            WHERE t.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? TipoDocumento::fromArray($row) : null;
    }

    public function findByNombre(string $nombre): ?TipoDocumento
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*, c.nombre as categoria_nombre 
            FROM tipos_documento t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            WHERE LOWER(t.nombre) = LOWER(:nombre)
        ");
        $stmt->execute(['nombre' => trim($nombre)]);
        $row = $stmt->fetch();

        return $row ? TipoDocumento::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = true): array
    {
        $sql = "
            SELECT t.*, c.nombre as categoria_nombre 
            FROM tipos_documento t
            LEFT JOIN categorias c ON t.categoria_id = c.id
        ";
        if ($onlyActive) {
            $sql .= " WHERE t.activo = 1";
        }
        $sql .= " ORDER BY t.orden ASC, t.nombre ASC";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => TipoDocumento::fromArray($row), $rows);
    }

    public function create(TipoDocumento $tipo): int
    {
        $sql = "INSERT INTO tipos_documento (nombre, categoria_id, orden, activo, creado_por) 
                VALUES (:nombre, :categoria_id, :orden, :activo, :creado_por)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $tipo->nombre,
            'categoria_id' => $tipo->categoria_id ?: null,
            'orden' => $tipo->orden,
            'activo' => $tipo->activo ? 1 : 0,
            'creado_por' => $tipo->creado_por
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(TipoDocumento $tipo): bool
    {
        $sql = "UPDATE tipos_documento 
                SET nombre = :nombre, categoria_id = :categoria_id, orden = :orden, activo = :activo, 
                    modificado_por = :modificado_por, modificado_el = :modificado_el 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $tipo->id,
            'nombre' => $tipo->nombre,
            'categoria_id' => $tipo->categoria_id ?: null,
            'orden' => $tipo->orden,
            'activo' => $tipo->activo ? 1 : 0,
            'modificado_por' => $tipo->modificado_por,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    public function deactivate(int $id, int $modificadoPor): bool
    {
        $stmt = $this->pdo->prepare("UPDATE tipos_documento SET activo = 0, modificado_por = :modificado_por, modificado_el = :modificado_el WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'modificado_por' => $modificadoPor,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }
}
