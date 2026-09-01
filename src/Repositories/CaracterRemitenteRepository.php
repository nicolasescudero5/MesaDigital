<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CaracterRemitente;
use PDO;

class CaracterRemitenteRepository
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?CaracterRemitente
    {
        $stmt = $this->pdo->prepare("SELECT * FROM caracteres_remitente WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? CaracterRemitente::fromArray($row) : null;
    }

    public function findByNombre(string $nombre): ?CaracterRemitente
    {
        $stmt = $this->pdo->prepare("SELECT * FROM caracteres_remitente WHERE LOWER(nombre) = LOWER(:nombre)");
        $stmt->execute(['nombre' => trim($nombre)]);
        $row = $stmt->fetch();

        return $row ? CaracterRemitente::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM caracteres_remitente";
        if ($onlyActive) {
            $sql .= " WHERE activo = 1";
        }
        $sql .= " ORDER BY orden ASC, nombre ASC";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => CaracterRemitente::fromArray($row), $rows);
    }

    public function create(CaracterRemitente $caracter): int
    {
        $sql = "INSERT INTO caracteres_remitente (nombre, orden, activo, creado_por) 
                VALUES (:nombre, :orden, :activo, :creado_por)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $caracter->nombre,
            'orden' => $caracter->orden,
            'activo' => $caracter->activo ? 1 : 0,
            'creado_por' => $caracter->creado_por
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(CaracterRemitente $caracter): bool
    {
        $sql = "UPDATE caracteres_remitente 
                SET nombre = :nombre, orden = :orden, activo = :activo, 
                    modificado_por = :modificado_por, modificado_el = :modificado_el 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $caracter->id,
            'nombre' => $caracter->nombre,
            'orden' => $caracter->orden,
            'activo' => $caracter->activo ? 1 : 0,
            'modificado_por' => $caracter->modificado_por,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    public function deactivate(int $id, int $modificadoPor): bool
    {
        $stmt = $this->pdo->prepare("UPDATE caracteres_remitente SET activo = 0, modificado_por = :modificado_por, modificado_el = :modificado_el WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'modificado_por' => $modificadoPor,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }
}
