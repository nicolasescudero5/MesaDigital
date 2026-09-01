<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Sede;
use PDO;

class SedeRepository
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?Sede
    {
        $stmt = $this->pdo->prepare("SELECT * FROM sedes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Sede::fromArray($row) : null;
    }

    public function findByNombre(string $nombre): ?Sede
    {
        $stmt = $this->pdo->prepare("SELECT * FROM sedes WHERE LOWER(nombre) = LOWER(:nombre)");
        $stmt->execute(['nombre' => trim($nombre)]);
        $row = $stmt->fetch();

        return $row ? Sede::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM sedes";
        if ($onlyActive) {
            $sql .= " WHERE activo = 1";
        }
        $sql .= " ORDER BY nombre ASC";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Sede::fromArray($row), $rows);
    }

    public function create(Sede $sede): int
    {
        $sql = "INSERT INTO sedes (nombre, color_primario, activo, creado_por) 
                VALUES (:nombre, :color_primario, :activo, :creado_por)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $sede->nombre,
            'color_primario' => $sede->color_primario,
            'activo' => $sede->activo ? 1 : 0,
            'creado_por' => $sede->creado_por
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(Sede $sede): bool
    {
        $sql = "UPDATE sedes 
                SET nombre = :nombre, color_primario = :color_primario, activo = :activo, 
                    modificado_por = :modificado_por, modificado_el = :modificado_el 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $sede->id,
            'nombre' => $sede->nombre,
            'color_primario' => $sede->color_primario,
            'activo' => $sede->activo ? 1 : 0,
            'modificado_por' => $sede->modificado_por,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    public function deactivate(int $id, int $modificadoPor): bool
    {
        $stmt = $this->pdo->prepare("UPDATE sedes SET activo = 0, modificado_por = :modificado_por, modificado_el = :modificado_el WHERE id = :id");
        return $stmt->execute([
            'id' => $id, 
            'modificado_por' => $modificadoPor,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }
}
