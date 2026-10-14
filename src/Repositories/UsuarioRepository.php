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

        if (!$row) {
            return null;
        }

        $user = Usuario::fromArray($row);
        $this->hydrateUserSedes($user);
        return $user;
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

        if (!$row) {
            return null;
        }

        $user = Usuario::fromArray($row);
        $this->hydrateUserSedes($user);
        return $user;
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

        if (!$row) {
            return null;
        }

        $user = Usuario::fromArray($row);
        $this->hydrateUserSedes($user);
        return $user;
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

        if (empty($rows)) {
            return [];
        }

        $users = array_map(fn($row) => Usuario::fromArray($row), $rows);

        // Cargar todas las asignaciones de usuario_sedes en una única consulta
        try {
            $pivotStmt = $this->pdo->query("
                SELECT us.usuario_id, us.sede_id, s.nombre AS sede_nombre 
                FROM usuario_sedes us 
                JOIN sedes s ON us.sede_id = s.id 
                ORDER BY s.nombre ASC
            ");
            $pivotRows = $pivotStmt ? $pivotStmt->fetchAll() : [];

            $sedesByUser = [];
            foreach ($pivotRows as $p) {
                $uId = (int)$p['usuario_id'];
                $sedesByUser[$uId]['ids'][] = (int)$p['sede_id'];
                $sedesByUser[$uId]['nombres'][] = (string)$p['sede_nombre'];
            }

            foreach ($users as $u) {
                if (isset($sedesByUser[$u->id])) {
                    $u->sedes_ids = $sedesByUser[$u->id]['ids'];
                    $u->sedes_nombres = $sedesByUser[$u->id]['nombres'];
                    if ($u->sede_id === null && !empty($u->sedes_ids)) {
                        $u->sede_id = $u->sedes_ids[0];
                    }
                    if ($u->sede_nombre === null && !empty($u->sedes_nombres)) {
                        $u->sede_nombre = $u->sedes_nombres[0];
                    }
                } elseif ($u->sede_id !== null && empty($u->sedes_ids)) {
                    $u->sedes_ids = [(int)$u->sede_id];
                    if ($u->sede_nombre !== null) {
                        $u->sedes_nombres = [$u->sede_nombre];
                    }
                }
            }
        } catch (\Throwable) {
            // Si la tabla aún no estuviera accesible, conserva los datos base
        }

        return $users;
    }

    public function create(Usuario $user, array $sedesIds = []): int
    {
        if (empty($sedesIds)) {
            $sedesIds = $user->getSedesIds();
        }
        if (!empty($sedesIds)) {
            $user->sedes_ids = array_values(array_unique(array_filter(array_map('intval', $sedesIds))));
            $user->sede_id = $user->sedes_ids[0];
        }

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

        $userId = (int)$this->pdo->lastInsertId();
        $user->id = $userId;

        if (!empty($user->sedes_ids)) {
            $this->syncSedes($userId, $user->sedes_ids);
        }

        return $userId;
    }

    public function update(Usuario $user, ?array $sedesIds = null): bool
    {
        if ($sedesIds !== null) {
            $user->sedes_ids = array_values(array_unique(array_filter(array_map('intval', $sedesIds))));
            $user->sede_id = !empty($user->sedes_ids) ? $user->sedes_ids[0] : null;
        }

        $sql = "UPDATE usuarios 
                SET nombre = :nombre, email = :email, rol = :rol, sede_id = :sede_id, 
                    activo = :activo, modificado_por = :modificado_por, modificado_el = :modificado_el 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute([
            'id' => $user->id,
            'nombre' => $user->nombre,
            'email' => $user->email,
            'rol' => $user->rol,
            'sede_id' => $user->sede_id,
            'activo' => $user->activo ? 1 : 0,
            'modificado_por' => $user->modificado_por,
            'modificado_el' => date('Y-m-d H:i:s'),
        ]);

        if ($res && $sedesIds !== null && $user->id) {
            $this->syncSedes((int)$user->id, $user->sedes_ids);
        }

        return $res;
    }

    public function syncSedes(int $userId, array $sedesIds): void
    {
        $del = $this->pdo->prepare("DELETE FROM usuario_sedes WHERE usuario_id = :usuario_id");
        $del->execute(['usuario_id' => $userId]);

        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $sedesIds))));
        if (empty($uniqueIds)) {
            return;
        }

        $ins = $this->pdo->prepare("INSERT INTO usuario_sedes (usuario_id, sede_id) VALUES (:usuario_id, :sede_id)");
        foreach ($uniqueIds as $sId) {
            $ins->execute([
                'usuario_id' => $userId,
                'sede_id' => $sId,
            ]);
        }
    }

    private function hydrateUserSedes(Usuario $user): void
    {
        if (!$user->id) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT us.sede_id, s.nombre AS sede_nombre 
                FROM usuario_sedes us 
                JOIN sedes s ON us.sede_id = s.id 
                WHERE us.usuario_id = :usuario_id 
                ORDER BY s.nombre ASC
            ");
            $stmt->execute(['usuario_id' => $user->id]);
            $rows = $stmt->fetchAll();

            if (!empty($rows)) {
                $user->sedes_ids = array_map(fn($r) => (int)$r['sede_id'], $rows);
                $user->sedes_nombres = array_map(fn($r) => (string)$r['sede_nombre'], $rows);
                if ($user->sede_id === null && !empty($user->sedes_ids)) {
                    $user->sede_id = $user->sedes_ids[0];
                }
                if ($user->sede_nombre === null && !empty($user->sedes_nombres)) {
                    $user->sede_nombre = $user->sedes_nombres[0];
                }
            } elseif ($user->sede_id !== null && empty($user->sedes_ids)) {
                $user->sedes_ids = [(int)$user->sede_id];
                if ($user->sede_nombre !== null) {
                    $user->sedes_nombres = [$user->sede_nombre];
                }
            }
        } catch (\Throwable) {
            // Si la tabla aún no estuviera creada, conserva valores de usuarios.sede_id
        }
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
