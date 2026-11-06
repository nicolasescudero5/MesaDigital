<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Usuario;
use PDO;

class UsuarioRepository
{
    private bool $schemaChecked = false;

    public function __construct(private PDO $pdo)
    {
        $this->ensureDirectoresSchema();
    }

    private function ensureDirectoresSchema(): void
    {
        if ($this->schemaChecked) {
            return;
        }
        $this->schemaChecked = true;

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                // Asegurar columna cargo_nivel y rol direccion_nivel en usuarios
                $colStmt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'cargo_nivel'");
                if (!$colStmt || !$colStmt->fetch()) {
                    try {
                        $this->pdo->exec("ALTER TABLE `usuarios` MODIFY COLUMN `rol` ENUM('administrador','recepcion_sede','responsable_categoria','direccion_sede','direccion_nivel','supervision_general') COLLATE utf8mb4_unicode_ci DEFAULT NULL");
                    } catch (\Throwable) {
                    }
                    try {
                        $this->pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `cargo_nivel` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `rol`");
                    } catch (\Throwable) {
                    }
                }

                // Asegurar tabla documento_accesos
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS `documento_accesos` (
                        `id` int unsigned NOT NULL AUTO_INCREMENT,
                        `documento_id` int unsigned NOT NULL,
                        `usuario_id` int unsigned NOT NULL,
                        `tipo_acceso` enum('director_sede','director_nivel') COLLATE utf8mb4_unicode_ci NOT NULL,
                        `creado_el` datetime DEFAULT CURRENT_TIMESTAMP,
                        `creado_por` int unsigned DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `uq_documento_acceso_usuario` (`documento_id`, `usuario_id`),
                        KEY `idx_documento_accesos_usuario` (`usuario_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            } elseif ($driver === 'sqlite') {
                $cols = $this->pdo->query("PRAGMA table_info(usuarios)")->fetchAll();
                $hasCargoNivel = false;
                foreach ($cols as $col) {
                    if (($col['name'] ?? '') === 'cargo_nivel') {
                        $hasCargoNivel = true;
                        break;
                    }
                }
                if (!$hasCargoNivel) {
                    $this->pdo->exec("ALTER TABLE usuarios ADD COLUMN cargo_nivel TEXT NULL");
                }
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS documento_accesos (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        documento_id INTEGER NOT NULL,
                        usuario_id INTEGER NOT NULL,
                        tipo_acceso TEXT NOT NULL,
                        creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        creado_por INTEGER NULL,
                        UNIQUE (documento_id, usuario_id)
                    )
                ");
            }
        } catch (\Throwable) {
            // Continuar normalmente si ya existe o no tiene permisos DDL
        }
    }

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

    /**
     * @return Usuario[]
     */
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
        $this->hydrateMultipleUsersSedes($users);

        return $users;
    }

    /**
     * @return Usuario[]
     */
    public function findByRol(string $rol, bool $onlyActive = true): array
    {
        $all = $this->findAll($onlyActive);
        return array_values(array_filter($all, fn(Usuario $u) => $u->rol === $rol));
    }

    /**
     * @return Usuario[]
     */
    public function findDirectoresSedeActivos(?int $sedeId = null): array
    {
        $directores = $this->findByRol('direccion_sede', true);
        if ($sedeId !== null && $sedeId > 0) {
            $directores = array_values(array_filter(
                $directores,
                fn(Usuario $u) => in_array($sedeId, $u->getSedesIds(), true)
            ));
        }
        return $directores;
    }

    /**
     * @return Usuario[]
     */
    public function findDirectoresNivelActivos(?int $sedeId = null): array
    {
        $directores = $this->findByRol('direccion_nivel', true);
        if ($sedeId !== null && $sedeId > 0) {
            $directores = array_values(array_filter(
                $directores,
                fn(Usuario $u) => in_array($sedeId, $u->getSedesIds(), true)
            ));
        }
        return $directores;
    }

    /**
     * @param Usuario[] $users
     */
    private function hydrateMultipleUsersSedes(array $users): void
    {
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

        $sql = "INSERT INTO usuarios (nombre, email, rol, cargo_nivel, sede_id, password_hash, google_sub, activo, creado_por)
                VALUES (:nombre, :email, :rol, :cargo_nivel, :sede_id, :password_hash, :google_sub, :activo, :creado_por)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $user->nombre,
            'email' => $user->email,
            'rol' => $user->rol,
            'cargo_nivel' => $user->cargo_nivel,
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
                SET nombre = :nombre, email = :email, rol = :rol, cargo_nivel = :cargo_nivel, sede_id = :sede_id, 
                    activo = :activo, modificado_por = :modificado_por, modificado_el = :modificado_el 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute([
            'id' => $user->id,
            'nombre' => $user->nombre,
            'email' => $user->email,
            'rol' => $user->rol,
            'cargo_nivel' => $user->cargo_nivel,
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
