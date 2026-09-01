<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Documento;
use App\Models\Usuario;
use PDO;

class DocumentoRepository
{
    public function __construct(
        private PDO $pdo,
        private CategoriaRepository $categoriaRepository
    ) {}

    public function findById(int $id): ?Documento
    {
        $sql = "SELECT d.*, 
                       s.nombre AS sede_nombre, s.color_primario AS sede_color,
                       c.nombre AS categoria_nombre, 
                       t.nombre AS tipo_documento_nombre, 
                       cr.nombre AS caracter_remitente_nombre,
                       u.nombre AS creador_nombre
                FROM documentos d
                JOIN sedes s ON d.sede_id = s.id
                JOIN categorias c ON d.categoria_id = c.id
                JOIN tipos_documento t ON d.tipo_documento_id = t.id
                LEFT JOIN caracteres_remitente cr ON d.caracter_remitente_id = cr.id
                JOIN usuarios u ON d.creado_por = u.id
                WHERE d.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Documento::fromArray($row) : null;
    }

    public function findByCodigo(string $codigo): ?Documento
    {
        $sql = "SELECT d.*, 
                       s.nombre AS sede_nombre, s.color_primario AS sede_color,
                       c.nombre AS categoria_nombre, 
                       t.nombre AS tipo_documento_nombre, 
                       cr.nombre AS caracter_remitente_nombre,
                       u.nombre AS creador_nombre
                FROM documentos d
                JOIN sedes s ON d.sede_id = s.id
                JOIN categorias c ON d.categoria_id = c.id
                JOIN tipos_documento t ON d.tipo_documento_id = t.id
                LEFT JOIN caracteres_remitente cr ON d.caracter_remitente_id = cr.id
                JOIN usuarios u ON d.creado_por = u.id
                WHERE d.codigo = :codigo";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['codigo' => $codigo]);
        $row = $stmt->fetch();

        return $row ? Documento::fromArray($row) : null;
    }

    /**
     * Verifica si un usuario tiene acceso para ver un documento específico
     */
    public function canUserView(Usuario $usuario, Documento $documento): bool
    {
        if ($usuario->isAdministrador() || $usuario->isSupervision()) {
            return true;
        }

        if ($usuario->isRecepcion() || $usuario->isDireccion()) {
            return $documento->sede_id === $usuario->sede_id;
        }

        if ($usuario->isResponsable()) {
            $categoriasAsignadas = $this->categoriaRepository->getCategoriasByUsuarioId((int)$usuario->id);
            return in_array($documento->categoria_id, $categoriasAsignadas, true);
        }

        return false;
    }

    /**
     * Busca documentos respetando el alcance (RBAC) del usuario y filtros de búsqueda
     *
     * @param Usuario $usuario
     * @param array $filters [sede_id, categoria_id, tipo_documento_id, estado, remitente, search, fecha_desde, fecha_hasta, incluir_anulados]
     * @param int $limit
     * @param int $offset
     * @param string $orderBy
     * @param string $orderDir
     * @return array{items: Documento[], total: int}
     */
    public function findPaginatedWithScope(
        Usuario $usuario,
        array $filters = [],
        int $limit = 25,
        int $offset = 0,
        string $orderBy = 'd.id',
        string $orderDir = 'DESC'
    ): array {
        [$whereClauses, $params] = $this->buildScopeAndFilters($usuario, $filters);

        $whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

        // Conteo total
        $countSql = "SELECT COUNT(*) FROM documentos d" . $whereSql;
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Lista paginada
        $allowedOrderColumns = ['d.id', 'd.codigo', 'd.fecha_recepcion', 'd.plazo_legal', 'd.estado', 'd.remitente', 's.nombre', 'c.nombre'];
        if (!in_array($orderBy, $allowedOrderColumns, true)) {
            $orderBy = 'd.id';
        }
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $dataSql = "SELECT d.*, 
                           s.nombre AS sede_nombre, s.color_primario AS sede_color,
                           c.nombre AS categoria_nombre, 
                           t.nombre AS tipo_documento_nombre, 
                           cr.nombre AS caracter_remitente_nombre,
                           u.nombre AS creador_nombre
                    FROM documentos d
                    JOIN sedes s ON d.sede_id = s.id
                    JOIN categorias c ON d.categoria_id = c.id
                    JOIN tipos_documento t ON d.tipo_documento_id = t.id
                    LEFT JOIN caracteres_remitente cr ON d.caracter_remitente_id = cr.id
                    JOIN usuarios u ON d.creado_por = u.id
                    {$whereSql}
                    ORDER BY {$orderBy} {$orderDir}
                    LIMIT {$limit} OFFSET {$offset}";

        $dataStmt = $this->pdo->prepare($dataSql);
        $dataStmt->execute($params);
        $rows = $dataStmt->fetchAll();

        $items = array_map(fn($row) => Documento::fromArray($row), $rows);

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    /**
     * Construye las cláusulas WHERE y parámetros vinculados para scope + filtros
     */
    private function buildScopeAndFilters(Usuario $usuario, array $filters): array
    {
        $clauses = [];
        $params = [];

        // 1. Control de alcance RBAC obligatorio (§ 4.1)
        if ($usuario->isAdministrador() || $usuario->isSupervision()) {
            // Ve todo el sistema
        } elseif ($usuario->isRecepcion() || $usuario->isDireccion()) {
            $clauses[] = "d.sede_id = :scope_sede_id";
            $params['scope_sede_id'] = $usuario->sede_id;
        } elseif ($usuario->isResponsable()) {
            $categoriasAsignadas = $this->categoriaRepository->getCategoriasByUsuarioId((int)$usuario->id);
            if (empty($categoriasAsignadas)) {
                // Sin categorías asignadas no ve nada
                $clauses[] = "1 = 0";
            } else {
                $placeholders = [];
                foreach ($categoriasAsignadas as $idx => $catId) {
                    $key = "cat_scope_{$idx}";
                    $placeholders[] = ":{$key}";
                    $params[$key] = $catId;
                }
                $clauses[] = "d.categoria_id IN (" . implode(',', $placeholders) . ")";
            }
        }

        // 2. Control de activos / anulados
        $incluirAnulados = ($usuario->isAdministrador()) && !empty($filters['incluir_anulados']);
        if (!$incluirAnulados) {
            $clauses[] = "d.activo = 1";
        }

        // 3. Filtros adicionales de la consulta
        if (!empty($filters['sede_id'])) {
            $clauses[] = "d.sede_id = :filter_sede_id";
            $params['filter_sede_id'] = (int)$filters['sede_id'];
        }

        if (!empty($filters['categoria_id'])) {
            $clauses[] = "d.categoria_id = :filter_categoria_id";
            $params['filter_categoria_id'] = (int)$filters['categoria_id'];
        }

        if (!empty($filters['tipo_documento_id'])) {
            $clauses[] = "d.tipo_documento_id = :filter_tipo_doc_id";
            $params['filter_tipo_doc_id'] = (int)$filters['tipo_documento_id'];
        }

        if (!empty($filters['estado'])) {
            $clauses[] = "d.estado = :filter_estado";
            $params['filter_estado'] = $filters['estado'];
        }

        if (!empty($filters['fecha_desde'])) {
            $clauses[] = "d.fecha_recepcion >= :filter_fecha_desde";
            $params['filter_fecha_desde'] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $clauses[] = "d.fecha_recepcion <= :filter_fecha_hasta";
            $params['filter_fecha_hasta'] = $filters['fecha_hasta'];
        }

        if (!empty($filters['search'])) {
            $clauses[] = "(d.codigo LIKE :search OR d.remitente LIKE :search OR d.asunto LIKE :search OR d.descripcion LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['solo_vencidos'])) {
            $clauses[] = "d.plazo_legal IS NOT NULL AND d.plazo_legal < :today_vencido AND d.estado NOT IN ('Resuelto', 'Cerrado')";
            $params['today_vencido'] = date('Y-m-d');
        }

        return [$clauses, $params];
    }

    /**
     * Genera el siguiente código secuencial único: MD-YYYY-NNNNNN
     */
    public function generateNextCodigo(): string
    {
        $year = date('Y');
        $prefix = "MD-{$year}-%";

        $stmt = $this->pdo->prepare("SELECT codigo FROM documentos WHERE codigo LIKE :prefix ORDER BY id DESC LIMIT 1");
        $stmt->execute(['prefix' => $prefix]);
        $lastCodigo = $stmt->fetchColumn();

        $nextNum = 1;
        if ($lastCodigo) {
            $parts = explode('-', (string)$lastCodigo);
            if (isset($parts[2])) {
                $nextNum = (int)$parts[2] + 1;
            }
        }

        return sprintf("MD-%s-%06d", $year, $nextNum);
    }

    /**
     * Busca posible duplicado en las últimas 24 horas para alerta no bloqueante (§ 4.5)
     */
    public function findPossibleDuplicate(string $remitente, int $sedeId): ?Documento
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $sql = "SELECT * FROM documentos 
                WHERE LOWER(TRIM(remitente)) = LOWER(TRIM(:remitente)) 
                  AND sede_id = :sede_id 
                  AND creado_el >= :cutoff 
                  AND activo = 1 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'remitente' => $remitente,
            'sede_id' => $sedeId,
            'cutoff' => $cutoff
        ]);
        $row = $stmt->fetch();

        return $row ? Documento::fromArray($row) : null;
    }

    public function create(Documento $doc): int
    {
        $sql = "INSERT INTO documentos (codigo, sede_id, categoria_id, tipo_documento_id, caracter_remitente_id,
                                        remitente, asunto, descripcion, fecha_recepcion, plazo_legal, estado,
                                        constancia_cierre, creado_por, activo)
                VALUES (:codigo, :sede_id, :categoria_id, :tipo_documento_id, :caracter_remitente_id,
                        :remitente, :asunto, :descripcion, :fecha_recepcion, :plazo_legal, :estado,
                        :constancia_cierre, :creado_por, :activo)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'codigo' => $doc->codigo,
            'sede_id' => $doc->sede_id,
            'categoria_id' => $doc->categoria_id,
            'tipo_documento_id' => $doc->tipo_documento_id,
            'caracter_remitente_id' => $doc->caracter_remitente_id,
            'remitente' => $doc->remitente,
            'asunto' => $doc->asunto,
            'descripcion' => $doc->descripcion,
            'fecha_recepcion' => $doc->fecha_recepcion,
            'plazo_legal' => $doc->plazo_legal,
            'estado' => $doc->estado,
            'constancia_cierre' => $doc->constancia_cierre,
            'creado_por' => $doc->creado_por,
            'activo' => $doc->activo ? 1 : 0
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateEstado(int $id, string $nuevoEstado, ?string $constanciaCierre = null, ?int $modificadoPor = null): bool
    {
        $sql = "UPDATE documentos 
                SET estado = :estado, 
                    constancia_cierre = COALESCE(:constancia, constancia_cierre),
                    modificado_por = :modificado_por, 
                    modificado_el = :modificado_el 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'estado' => $nuevoEstado,
            'constancia' => $constanciaCierre,
            'modificado_por' => $modificadoPor,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    public function updateCategoria(int $id, int $nuevaCategoriaId, ?int $modificadoPor = null): bool
    {
        $sql = "UPDATE documentos 
                SET categoria_id = :categoria_id, 
                    modificado_por = :modificado_por, 
                    modificado_el = :modificado_el 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'categoria_id' => $nuevaCategoriaId,
            'modificado_por' => $modificadoPor,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    public function updatePlazoLegal(int $id, ?string $plazoLegal, ?int $modificadoPor = null): bool
    {
        $sql = "UPDATE documentos 
                SET plazo_legal = :plazo_legal, 
                    modificado_por = :modificado_por, 
                    modificado_el = :modificado_el 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'plazo_legal' => $plazoLegal,
            'modificado_por' => $modificadoPor,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    public function anular(int $id, string $motivo, int $anuladoPor): bool
    {
        $sql = "UPDATE documentos 
                SET activo = 0, 
                    motivo_anulacion = :motivo, 
                    modificado_por = :anulado_por, 
                    modificado_el = :modificado_el 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'motivo' => $motivo,
            'anulado_por' => $anuladoPor,
            'modificado_el' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Métricas y KPIs para Dashboard respetando el scope del usuario
     */
    public function getKpis(Usuario $usuario): array
    {
        [$whereClauses, $params] = $this->buildScopeAndFilters($usuario, []);
        $whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

        $today = date('Y-m-d');
        $threeDays = date('Y-m-d', strtotime('+3 days'));

        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN d.estado = 'Recibido' THEN 1 ELSE 0 END) AS recibidos,
                    SUM(CASE WHEN d.estado = 'En curso' THEN 1 ELSE 0 END) AS en_curso,
                    SUM(CASE WHEN d.estado = 'Resuelto' THEN 1 ELSE 0 END) AS resueltos,
                    SUM(CASE WHEN d.estado = 'Cerrado' THEN 1 ELSE 0 END) AS cerrados,
                    SUM(CASE WHEN d.plazo_legal IS NOT NULL AND d.plazo_legal < '{$today}' AND d.estado NOT IN ('Resuelto', 'Cerrado') THEN 1 ELSE 0 END) AS vencidos,
                    SUM(CASE WHEN d.plazo_legal IS NOT NULL AND d.plazo_legal BETWEEN '{$today}' AND '{$threeDays}' AND d.estado NOT IN ('Resuelto', 'Cerrado') THEN 1 ELSE 0 END) AS por_vencer
                FROM documentos d {$whereSql}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $kpi = $stmt->fetch() ?: [];

        return [
            'total' => (int)($kpi['total'] ?? 0),
            'recibidos' => (int)($kpi['recibidos'] ?? 0),
            'en_curso' => (int)($kpi['en_curso'] ?? 0),
            'resueltos' => (int)($kpi['resueltos'] ?? 0),
            'cerrados' => (int)($kpi['cerrados'] ?? 0),
            'vencidos' => (int)($kpi['vencidos'] ?? 0),
            'por_vencer' => (int)($kpi['por_vencer'] ?? 0),
        ];
    }

    /**
     * Desglose por sede para dashboard
     */
    public function getBreakdownBySede(Usuario $usuario): array
    {
        [$whereClauses, $params] = $this->buildScopeAndFilters($usuario, []);
        $whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

        $sql = "SELECT s.id, s.nombre, s.color_primario, COUNT(d.id) AS total_documentos,
                       SUM(CASE WHEN d.estado = 'Recibido' THEN 1 ELSE 0 END) AS recibidos,
                       SUM(CASE WHEN d.estado = 'En curso' THEN 1 ELSE 0 END) AS en_curso,
                       SUM(CASE WHEN d.estado = 'Resuelto' THEN 1 ELSE 0 END) AS resueltos
                FROM sedes s
                LEFT JOIN documentos d ON d.sede_id = s.id AND d.activo = 1
                GROUP BY s.id, s.nombre, s.color_primario
                ORDER BY total_documentos DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Desglose por categoría para dashboard
     */
    public function getBreakdownByCategoria(Usuario $usuario): array
    {
        $sql = "SELECT c.id, c.nombre, COUNT(d.id) AS total_documentos,
                       SUM(CASE WHEN d.estado = 'Recibido' THEN 1 ELSE 0 END) AS recibidos,
                       SUM(CASE WHEN d.estado = 'En curso' THEN 1 ELSE 0 END) AS en_curso,
                       SUM(CASE WHEN d.estado = 'Resuelto' THEN 1 ELSE 0 END) AS resueltos
                FROM categorias c
                LEFT JOIN documentos d ON d.categoria_id = c.id AND d.activo = 1
                WHERE c.activo = 1
                GROUP BY c.id, c.nombre
                ORDER BY total_documentos DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Documentos sin tomar hace más de N horas en estado 'Recibido'
     */
    public function getDocumentosSinTomar(int $horas): array
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$horas} hours"));
        $sql = "SELECT d.*, s.nombre AS sede_nombre, c.nombre AS categoria_nombre 
                FROM documentos d 
                JOIN sedes s ON d.sede_id = s.id
                JOIN categorias c ON d.categoria_id = c.id
                WHERE d.estado = 'Recibido' 
                  AND d.activo = 1 
                  AND d.creado_el <= :cutoff";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cutoff' => $cutoff]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Documento::fromArray($row), $rows);
    }

    /**
     * Documentos próximos a vencer a N días exactos
     */
    public function getDocumentosPorVencer(int $dias): array
    {
        $targetDate = date('Y-m-d', strtotime("+{$dias} days"));
        $sql = "SELECT d.*, s.nombre AS sede_nombre, c.nombre AS categoria_nombre 
                FROM documentos d 
                JOIN sedes s ON d.sede_id = s.id
                JOIN categorias c ON d.categoria_id = c.id
                WHERE d.estado NOT IN ('Resuelto', 'Cerrado') 
                  AND d.activo = 1 
                  AND d.plazo_legal = :target_date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['target_date' => $targetDate]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Documento::fromArray($row), $rows);
    }

    /**
     * Documentos en estado 'Resuelto' listos para auto-cierre tras N días
     */
    public function getDocumentosParaAutoCierre(int $dias): array
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
        $sql = "SELECT d.* FROM documentos d 
                WHERE d.estado = 'Resuelto' 
                  AND d.activo = 1 
                  AND d.modificado_el <= :cutoff";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cutoff' => $cutoff]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Documento::fromArray($row), $rows);
    }
}
