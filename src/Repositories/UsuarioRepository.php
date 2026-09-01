<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Usuario;
use PDO;

class UsuarioRepository
{
    public function __construct(private PDO $pdo) {}

    public function findById(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*, s.nombre AS sede_nombre 
            FROM usuarios u 
            LEFT JOIN sedes s ON u.sede_id = s.id 
            WHERE u.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Usuario::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?Usuario
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*, s.nombre AS sede_nombre 
            FROM usuarios u 
            LEFT JOIN sedes s ON u.sede_id = s.id 
            WHERE LOWER(u.email) = LOWER(:email)
        ");
        $stmt->execute(['email' => trim($email)]);
        $row = $stmt->fetch();

        return $row ? Usuario::fromArray($row) : null;
    }

    public function findByGoogleSub(string $sub): ?Usuario
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*, s.nombre AS sede_nombre 
            FROM usuarios u 
            LEFT JOIN sedes s ON u.sede_id = s.id 
            WHERE u.google_sub = :sub
        ");
        $stmt->execute(['sub' => $sub]);
        $row = $stmt->fetch();

        return $row ? Usuario::fromArray($row) : null;
    }

    public function findAll(bool $onlyActive = true): array
    {
        $sql = "SELECT u.*, s.nombre AS sede_nombre 
                FROM usuarios u 
                LEFT JOIN sedes s ON u.sede_id = s.id";
        if ($onlyActive) {
            $sql .= " WHERE u.activo = 1";
        }
        $sql .= " ORDER BY u.nombre ASC";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Usuario::fromArray($row), $rows);
    }

    public function create(Usuario $user): int
    {
        $sql = "INSERT INTO usuarios (nombre, email, rol, sede_id, password_hash, google_sub, activo, creado_por)
                VALUES (:nombre, :email, :rol, :sede_id, :password_hash, :google_sub, :activo, :creado_por)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $user->nombre,
            'email' => $user->email,
            'rol' => $user->rol,
            'sede_id' => $user->sede_id,
            'password_hash' => $user->password_hash,
            'google_sub' => $user->google_sub,
            'activo' => $user->activo ? 1 : 0,
            'creado_por' => $user->creado_por,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(Usuario $user): bool
    {
        $sql = "UPDATE usuarios 
                SET nombre = :nombre, email = :email, rol = :rol, sede_id = :sede_id, 
                    activo = :activo, modificado_por = :modificado_por, modificado_el = :modificado_el 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $user->id,
            'nombre' => $user->nombre,
            'email' => $user->email,
            'rol' => $user->rol,
            'sede_id' => $user->sede_id,
            'activo' => $user->activo ? 1 : 0,
            'modificado_por' => $user->modificado_por,
            'modificado_el' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET password_hash = :hash, modificado_el = :modificado_el WHERE id = :id");
        return $stmt->execute(['id' => $id, 'hash' => $passwordHash, 'modificado_el' => date('Y-m-d H:i:s')]);
    }

    public function updateGoogleSub(int $id, string $googleSub): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET google_sub = :sub, modificado_el = :modificado_el WHERE id = :id");
        return $stmt->execute(['id' => $id, 'sub' => $googleSub, 'modificado_el' => date('Y-m-d H:i:s')]);
    }

    public function updateUltimoLogin(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET ultimo_login = :modificado_el WHERE id = :id");
        return $stmt->execute(['id' => $id, 'modificado_el' => date('Y-m-d H:i:s')]);
    }

    public function deactivate(int $id, int $modificadoPor): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET activo = 0, modificado_por = :modificado_por, modificado_el = :modificado_el WHERE id = :id");
        return $stmt->execute(['id' => $id, 'modificado_por' => $modificadoPor, 'modificado_el' => date('Y-m-d H:i:s')]);
    }

    public function activate(int $id, int $modificadoPor): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET activo = 1, modificado_por = :modificado_por, modificado_el = :modificado_el WHERE id = :id");
        return $stmt->execute(['id' => $id, 'modificado_por' => $modificadoPor, 'modificado_el' => date('Y-m-d H:i:s')]);
    }
}
