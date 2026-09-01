<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Categoria;
use App\Models\CategoriaResponsable;
use PDO;

class CategoriaRepository
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id, bool $withResponsables = true): ?Categoria
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $categoria = Categoria::fromArray($row);
        if ($withResponsables) {
            $categoria->responsables = $this->getResponsablesByCategoriaId($id);
        }

        return $categoria;
    }

    public function findByNombre(string $nombre): ?Categoria
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categorias WHERE LOWER(nombre) = LOWER(:nombre)");
        $stmt->execute(['nombre' => trim($nombre)]);
        $row = $stmt->fetch();

        return $row ? Categoria::fromArray($row) : null;
    }

    public function getCategoriaReserva(): ?Categoria
    {
        $stmt = $this->pdo->query("SELECT * FROM categorias WHERE es_reserva = 1 AND activo = 1 LIMIT 1");
        $row = $stmt->fetch();

        return $row ? Categoria::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = true, bool $withResponsables = true): array
    {
        $sql = "SELECT * FROM categorias";
        if ($onlyActive) {
            $sql .= " WHERE activo = 1";
        }
        $sql .= " ORDER BY orden ASC, nombre ASC";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        $categorias = array_map(fn($row) => Categoria::fromArray($row), $rows);

        if ($withResponsables && !empty($categorias)) {
            $responsablesMap = $this->getAllActiveResponsablesGroupedByCategoria();
            foreach ($categorias as $cat) {
                $cat->responsables = $responsablesMap[$cat->id] ?? [];
            }
        }

        return $categorias;
    }

    public function getResponsablesByCategoriaId(int $categoriaId, bool $onlyActive = true): array
    {
        $sql = "SELECT cr.*, u.nombre AS usuario_nombre, u.rol AS usuario_rol, s.nombre AS sede_nombre 
                FROM categoria_responsables cr
                LEFT JOIN usuarios u ON cr.usuario_id = u.id
                LEFT JOIN sedes s ON cr.sede_id = s.id
                WHERE cr.categoria_id = :categoria_id";
        if ($onlyActive) {
            $sql .= " AND cr.activo = 1";
        }
        $sql .= " ORDER BY s.nombre ASC, cr.email ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['categoria_id' => $categoriaId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => CategoriaResponsable::fromArray($row), $rows);
    }

    public function tieneResponsablesActivos(int $categoriaId, ?int $sedeId = null): bool
    {
        if ($sedeId !== null && $sedeId > 0) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM categoria_responsables WHERE categoria_id = :id AND activo = 1 AND (sede_id = :sede_id OR sede_id IS NULL)");
            $stmt->execute(['id' => $categoriaId, 'sede_id' => $sedeId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM categoria_responsables WHERE categoria_id = :id AND activo = 1");
            $stmt->execute(['id' => $categoriaId]);
        }
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function getEmailsResponsablesActivos(int $categoriaId, ?int $sedeId = null): array
    {
        if ($sedeId !== null && $sedeId > 0) {
            // Primero buscar si existen responsables específicos para esta sede
            $stmt = $this->pdo->prepare("SELECT email FROM categoria_responsables WHERE categoria_id = :id AND sede_id = :sede_id AND activo = 1");
            $stmt->execute(['id' => $categoriaId, 'sede_id' => $sedeId]);
            $sedeEmails = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // También obtener responsables globales (sede_id IS NULL)
            $stmtGlobal = $this->pdo->prepare("SELECT email FROM categoria_responsables WHERE categoria_id = :id AND sede_id IS NULL AND activo = 1");
            $stmtGlobal->execute(['id' => $categoriaId]);
            $globalEmails = $stmtGlobal->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $all = array_unique(array_merge($sedeEmails, $globalEmails));
            return array_values($all);
        }

        $stmt = $this->pdo->prepare("SELECT email FROM categoria_responsables WHERE categoria_id = :id AND activo = 1");
        $stmt->execute(['id' => $categoriaId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function getCategoriasByUsuarioId(int $usuarioId): array
    {
        $sql = "SELECT DISTINCT categoria_id FROM categoria_responsables WHERE usuario_id = :usuario_id AND activo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['usuario_id' => $usuarioId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function getAllActiveResponsablesGroupedByCategoria(): array
    {
        $sql = "SELECT cr.*, u.nombre AS usuario_nombre, u.rol AS usuario_rol, s.nombre AS sede_nombre 
                FROM categoria_responsables cr
                LEFT JOIN usuarios u ON cr.usuario_id = u.id
                LEFT JOIN sedes s ON cr.sede_id = s.id
                WHERE cr.activo = 1
                ORDER BY s.nombre ASC, cr.email ASC";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $catId = (int)$row['categoria_id'];
            $map[$catId][] = CategoriaResponsable::fromArray($row);
        }

        return $map;
    }

    public function create(Categoria $categoria): int
    {
        $sql = "INSERT INTO categorias (nombre, descripcion, orden, es_reserva, activo, creado_por)
                VALUES (:nombre, :descripcion, :orden, :es_reserva, :activo, :creado_por)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $categoria->nombre,
            'descripcion' => $categoria->descripcion,
            'orden' => $categoria->orden,
            'es_reserva' => $categoria->es_reserva ? 1 : 0,
            'activo' => $categoria->activo ? 1 : 0,
            'creado_por' => $categoria->creado_por
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(Categoria $categoria): bool
    {
        $sql = "UPDATE categorias 
                SET nombre = :nombre, descripcion = :descripcion, orden = :orden, 
                    activo = :activo, modificado_por = :modificado_por, modificado_el = :modificado_el 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
            'descripcion' => $categoria->descripcion,
            'orden' => $categoria->orden,
            'activo' => $categoria->activo ? 1 : 0,
            'modificado_por' => $categoria->modificado_por,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    public function deactivate(int $id, int $modificadoPor): bool
    {
        $stmt = $this->pdo->prepare("UPDATE categorias SET activo = 0, modificado_por = :modificado_por, modificado_el = :modificado_el WHERE id = :id AND es_reserva = 0");
        return $stmt->execute([
            'id' => $id,
            'modificado_por' => $modificadoPor,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    public function addResponsable(int $categoriaId, string $email, ?int $usuarioId, ?int $sedeId, int $creadoPor): bool
    {
        // Verificar si ya existe registro idéntico (misma categoría, mismo email, misma sede)
        $sqlCheck = "SELECT id FROM categoria_responsables 
                     WHERE categoria_id = :cat_id AND LOWER(email) = LOWER(:email) " .
                     ($sedeId !== null ? "AND sede_id = :sede_id" : "AND sede_id IS NULL");
        $paramsCheck = ['cat_id' => $categoriaId, 'email' => trim($email)];
        if ($sedeId !== null) {
            $paramsCheck['sede_id'] = $sedeId;
        }

        $checkStmt = $this->pdo->prepare($sqlCheck);
        $checkStmt->execute($paramsCheck);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            $stmt = $this->pdo->prepare("UPDATE categoria_responsables SET activo = 1, usuario_id = :usuario_id, modificado_el = :modificado_el WHERE id = :id");
            return $stmt->execute(['usuario_id' => $usuarioId, 'modificado_el' => date('Y-m-d H:i:s'), 'id' => $existingId]);
        }

        $stmt = $this->pdo->prepare("INSERT INTO categoria_responsables (categoria_id, sede_id, email, usuario_id, activo, creado_por) VALUES (:categoria_id, :sede_id, :email, :usuario_id, 1, :creado_por)");
        return $stmt->execute([
            'categoria_id' => $categoriaId,
            'sede_id' => $sedeId,
            'email' => strtolower(trim($email)),
            'usuario_id' => $usuarioId,
            'creado_por' => $creadoPor
        ]);
    }

    public function removeResponsableById(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE categoria_responsables SET activo = 0, modificado_el = :modificado_el WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    public function removeResponsable(int $categoriaId, string $email, ?int $sedeId = null): bool
    {
        $sql = "UPDATE categoria_responsables SET activo = 0, modificado_el = :modificado_el 
                WHERE categoria_id = :categoria_id AND LOWER(email) = LOWER(:email)";
        $params = [
            'categoria_id' => $categoriaId,
            'email' => trim($email),
            'modificado_el' => date('Y-m-d H:i:s')
        ];

        if ($sedeId !== null) {
            $sql .= " AND sede_id = :sede_id";
            $params['sede_id'] = $sedeId;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
