<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Usuario;
use App\Repositories\CaracterRemitenteRepository;
use App\Repositories\CategoriaRepository;
use App\Repositories\DocumentoAdjuntoRepository;
use App\Repositories\DocumentoComentarioRepository;
use App\Repositories\DocumentoHistorialRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\NotificacionRepository;
use App\Repositories\SedeRepository;
use App\Repositories\TipoDocumentoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\DocumentoService;
use App\Services\ExportService;
use App\Services\MailerInterface;
use App\Services\NotificacionService;
use App\Services\RecordatorioService;
use App\Services\StorageInterface;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected PDO $pdo;
    protected UsuarioRepository $usuarioRepo;
    protected SedeRepository $sedeRepo;
    protected CategoriaRepository $categoriaRepo;
    protected TipoDocumentoRepository $tipoDocRepo;
    protected CaracterRemitenteRepository $caracterRepo;
    protected DocumentoRepository $documentoRepo;
    protected DocumentoHistorialRepository $historialRepo;
    protected DocumentoAdjuntoRepository $adjuntoRepo;
    protected DocumentoComentarioRepository $comentarioRepo;
    protected NotificacionRepository $notificacionRepo;
    
    protected MailerInterface $mockMailer;
    protected StorageInterface $mockStorage;
    protected NotificacionService $notificacionService;
    protected DocumentoService $documentoService;
    protected RecordatorioService $recordatorioService;
    protected ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->setUpRepositories();
        $this->setUpServices();
    }

    protected function setUpDatabase(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Crear tablas
        $this->pdo->exec("
            CREATE TABLE sedes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                color_primario TEXT NOT NULL DEFAULT '#4E47DD',
                activo INTEGER NOT NULL DEFAULT 1,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                creado_por INTEGER NULL,
                modificado_el TEXT NULL,
                modificado_por INTEGER NULL
            );

            CREATE TABLE categorias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                descripcion TEXT NULL,
                orden INTEGER NOT NULL DEFAULT 0,
                es_reserva INTEGER NOT NULL DEFAULT 0,
                activo INTEGER NOT NULL DEFAULT 1,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                creado_por INTEGER NULL,
                modificado_el TEXT NULL,
                modificado_por INTEGER NULL
            );

            CREATE TABLE tipos_documento (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                categoria_id INTEGER NULL,
                orden INTEGER NOT NULL DEFAULT 0,
                activo INTEGER NOT NULL DEFAULT 1,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                creado_por INTEGER NULL,
                modificado_el TEXT NULL,
                modificado_por INTEGER NULL
            );

            CREATE TABLE caracteres_remitente (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                orden INTEGER NOT NULL DEFAULT 0,
                activo INTEGER NOT NULL DEFAULT 1,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                creado_por INTEGER NULL,
                modificado_el TEXT NULL,
                modificado_por INTEGER NULL
            );

            CREATE TABLE usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                rol TEXT NOT NULL,
                sede_id INTEGER NULL,
                password_hash TEXT NULL,
                google_sub TEXT NULL,
                activo INTEGER NOT NULL DEFAULT 1,
                ultimo_login TEXT NULL,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                creado_por INTEGER NULL,
                modificado_el TEXT NULL,
                modificado_por INTEGER NULL
            );

            CREATE TABLE categoria_responsables (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                categoria_id INTEGER NOT NULL,
                sede_id INTEGER NULL,
                email TEXT NOT NULL,
                usuario_id INTEGER NULL,
                activo INTEGER NOT NULL DEFAULT 1,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                creado_por INTEGER NULL,
                modificado_el TEXT NULL,
                modificado_por INTEGER NULL
            );

            CREATE TABLE documentos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo TEXT NOT NULL UNIQUE,
                sede_id INTEGER NOT NULL,
                categoria_id INTEGER NOT NULL,
                tipo_documento_id INTEGER NOT NULL,
                caracter_remitente_id INTEGER NULL,
                remitente TEXT NOT NULL,
                asunto TEXT NOT NULL,
                descripcion TEXT NOT NULL,
                fecha_recepcion TEXT NOT NULL,
                plazo_legal TEXT NULL,
                estado TEXT NOT NULL DEFAULT 'Recibido',
                constancia_cierre TEXT NULL,
                creado_por INTEGER NOT NULL,
                activo INTEGER NOT NULL DEFAULT 1,
                motivo_anulacion TEXT NULL,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                modificado_el TEXT NULL,
                modificado_por INTEGER NULL
            );

            CREATE TABLE documento_adjuntos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                documento_id INTEGER NOT NULL,
                nombre_original TEXT NOT NULL,
                ruta_almacenamiento TEXT NOT NULL,
                tipo_mime TEXT NOT NULL,
                tamano_bytes INTEGER NOT NULL,
                es_principal INTEGER NOT NULL DEFAULT 0,
                subido_por INTEGER NOT NULL,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE documento_historial (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                documento_id INTEGER NOT NULL,
                fecha_hora TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                accion TEXT NOT NULL,
                estado_anterior TEXT NULL,
                estado_nuevo TEXT NULL,
                usuario_id INTEGER NULL,
                detalle TEXT NULL
            );

            CREATE TABLE documento_comentarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                documento_id INTEGER NOT NULL,
                usuario_id INTEGER NOT NULL,
                comentario TEXT NOT NULL,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE notificaciones_enviadas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                documento_id INTEGER NOT NULL,
                destinatario_email TEXT NOT NULL,
                tipo_evento TEXT NOT NULL,
                estado_envio TEXT NOT NULL DEFAULT 'Pendiente',
                intento_numero INTEGER NOT NULL DEFAULT 1,
                fecha_envio TEXT NULL,
                error_mensaje TEXT NULL,
                creado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE login_intentos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                email TEXT NOT NULL,
                intentado_el TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $this->seedBasicData();
    }

    protected function seedBasicData(): void
    {
        // 1. Sedes
        $this->pdo->exec("
            INSERT INTO sedes (id, nombre, color_primario, activo) VALUES
            (1, 'Colegio del Faro Benavidez', '#295E48', 1),
            (2, 'Colegio del Faro Escobar', '#3E7D61', 1),
            (3, 'Lighthouse Campus Puertos', '#3A82C2', 1),
            (4, 'Northfield Nordelta', '#CF364C', 1),
            (5, 'Northfield Puertos', '#4E47DD', 1);
        ");

        // 2. Categorías
        $this->pdo->exec("
            INSERT INTO categorias (id, nombre, descripcion, orden, es_reserva, activo) VALUES
            (1, 'Legales — Laboral', 'Laboral', 1, 0, 1),
            (2, 'Legales — Civil y Comercial', 'Civil', 2, 0, 1),
            (3, 'Capital Humano (RRHH)', 'RRHH', 3, 0, 1),
            (4, 'Sin Responsables', 'Prueba', 4, 0, 1),
            (11, 'Sin Clasificar', 'Reserva', 99, 1, 1);
        ");

        // 3. Tipos y Caracteres
        $this->pdo->exec("
            INSERT INTO tipos_documento (id, nombre, categoria_id, orden, activo) VALUES
            (1, 'Oficio', 1, 1, 1), (2, 'Carta documento', 1, 2, 1), (3, 'Telegrama', 3, 3, 1);

            INSERT INTO caracteres_remitente (id, nombre, orden, activo) VALUES
            (1, 'Empleado', 1, 1), (2, 'Familia', 2, 1);
        ");

        // 4. Usuarios
        $pass = password_hash('password123', PASSWORD_BCRYPT);
        $this->pdo->exec("
            INSERT INTO usuarios (id, nombre, email, rol, sede_id, password_hash, activo) VALUES
            (1, 'Admin', 'admin@reditinere.com', 'administrador', NULL, '{$pass}', 1),
            (2, 'Recepción Benavidez', 'recepcion.benavidez@reditinere.com', 'recepcion_sede', 1, '{$pass}', 1),
            (3, 'Recepción Escobar', 'recepcion.escobar@reditinere.com', 'recepcion_sede', 2, '{$pass}', 1),
            (4, 'Dirección Benavidez', 'direccion.benavidez@reditinere.com', 'direccion_sede', 1, '{$pass}', 1),
            (5, 'Laura Legales', 'legales@reditinere.com', 'responsable_categoria', NULL, '{$pass}', 1),
            (6, 'Supervisión', 'supervision@reditinere.com', 'supervision_general', NULL, '{$pass}', 1);
        ");

        // 5. Categoria Responsables
        $this->pdo->exec("
            INSERT INTO categoria_responsables (categoria_id, email, usuario_id, activo) VALUES
            (1, 'legales@reditinere.com', 5, 1),
            (1, 'estudio.externo@legal.com', NULL, 1),
            (2, 'legales@reditinere.com', 5, 1),
            (3, 'rrhh@reditinere.com', NULL, 1);
        ");
    }

    protected function setUpRepositories(): void
    {
        $this->usuarioRepo = new UsuarioRepository($this->pdo);
        $this->sedeRepo = new SedeRepository($this->pdo);
        $this->categoriaRepo = new CategoriaRepository($this->pdo);
        $this->tipoDocRepo = new TipoDocumentoRepository($this->pdo);
        $this->caracterRepo = new CaracterRemitenteRepository($this->pdo);
        $this->documentoRepo = new DocumentoRepository($this->pdo, $this->categoriaRepo);
        $this->historialRepo = new DocumentoHistorialRepository($this->pdo);
        $this->adjuntoRepo = new DocumentoAdjuntoRepository($this->pdo);
        $this->comentarioRepo = new DocumentoComentarioRepository($this->pdo);
        $this->notificacionRepo = new NotificacionRepository($this->pdo);
    }

    protected function setUpServices(): void
    {
        $this->mockMailer = new class implements MailerInterface {
            public array $sent = [];
            public function send(string $to, string $subject, string $htmlBody, ?string $altBody = null): bool {
                $this->sent[] = ['to' => $to, 'subject' => $subject, 'body' => $htmlBody];
                return true;
            }
            public function getLastError(): ?string { return null; }
        };

        $this->mockStorage = new class implements StorageInterface {
            public array $files = [];
            public function saveUploadedFile(array $file, string $destinationDir): array {
                $path = $destinationDir . '/' . ($file['name'] ?? 'test.jpg');
                $this->files[$path] = $file;
                return [
                    'path' => $path,
                    'original_name' => $file['name'] ?? 'test.jpg',
                    'mime_type' => $file['type'] ?? 'image/jpeg',
                    'size_bytes' => $file['size'] ?? 1024,
                ];
            }
            public function getAbsolutePath(string $relativePath): string { return '/tmp/' . $relativePath; }
            public function exists(string $relativePath): bool { return isset($this->files[$relativePath]); }
            public function delete(string $relativePath): bool { unset($this->files[$relativePath]); return true; }
        };

        $this->notificacionService = new NotificacionService(
            $this->notificacionRepo,
            $this->categoriaRepo,
            $this->documentoRepo,
            $this->usuarioRepo,
            $this->mockMailer,
            null,
            'http://localhost:8080'
        );

        $this->documentoService = new DocumentoService(
            $this->pdo,
            $this->documentoRepo,
            $this->historialRepo,
            $this->adjuntoRepo,
            $this->comentarioRepo,
            $this->categoriaRepo,
            $this->tipoDocRepo,
            $this->mockStorage,
            $this->notificacionService
        );

        $this->recordatorioService = new RecordatorioService(
            $this->documentoRepo,
            $this->documentoService,
            $this->notificacionService
        );

        $this->exportService = new ExportService();
    }
}
