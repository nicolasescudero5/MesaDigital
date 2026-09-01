<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthProviderInterface;
use App\Repositories\CaracterRemitenteRepository;
use App\Repositories\CategoriaRepository;
use App\Repositories\DocumentoAdjuntoRepository;
use App\Repositories\DocumentoComentarioRepository;
use App\Repositories\DocumentoHistorialRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\SedeRepository;
use App\Repositories\TipoDocumentoRepository;
use App\Services\DocumentoService;
use App\Services\StorageInterface;
use App\Support\View;

class DocumentoController
{
    public function __construct(
        private AuthProviderInterface $authProvider,
        private DocumentoRepository $documentoRepository,
        private DocumentoHistorialRepository $historialRepository,
        private DocumentoAdjuntoRepository $adjuntoRepository,
        private DocumentoComentarioRepository $comentarioRepository,
        private SedeRepository $sedeRepository,
        private CategoriaRepository $categoriaRepository,
        private TipoDocumentoRepository $tipoDocumentoRepository,
        private CaracterRemitenteRepository $caracterRemitenteRepository,
        private DocumentoService $documentoService,
        private StorageInterface $storage,
        private View $view
    ) {}

    public function index(): void
    {
        $user = $this->authProvider->currentUser();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $rawLimit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
        $limit = in_array($rawLimit, [25, 50, 100], true) ? $rawLimit : 25;
        $offset = ($page - 1) * $limit;

        $filters = [
            'sede_id' => $_GET['sede_id'] ?? null,
            'categoria_id' => $_GET['categoria_id'] ?? null,
            'tipo_documento_id' => $_GET['tipo_documento_id'] ?? null,
            'estado' => $_GET['estado'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
            'search' => $_GET['search'] ?? null,
            'solo_vencidos' => !empty($_GET['solo_vencidos']),
            'incluir_anulados' => !empty($_GET['incluir_anulados']),
        ];

        $result = $this->documentoRepository->findPaginatedWithScope($user, $filters, $limit, $offset);
        $totalPages = max(1, (int)ceil($result['total'] / $limit));

        $sedes = $this->sedeRepository->findAll(true);
        $categorias = $this->categoriaRepository->findAll(true, false);
        $tipos = $this->tipoDocumentoRepository->findAll(true);

        echo $this->view->render('documentos/index', [
            'user' => $user,
            'documentos' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'sedes' => $sedes,
            'categorias' => $categorias,
            'tipos' => $tipos,
        ], 'app');
    }

    public function create(): void
    {
        $user = $this->authProvider->currentUser();

        $sedes = $this->sedeRepository->findAll(true);
        $categorias = $this->categoriaRepository->findAll(true, true);
        $tipos = $this->tipoDocumentoRepository->findAll(true);
        $caracteres = $this->caracterRemitenteRepository->findAll(true);

        echo $this->view->render('documentos/nuevo', [
            'user' => $user,
            'sedes' => $sedes,
            'categorias' => $categorias,
            'tipos' => $tipos,
            'caracteres' => $caracteres,
        ], 'app');
    }

    public function store(): void
    {
        $user = $this->authProvider->currentUser();

        // Si el usuario pertenece a una sede específica y no se envió otra, asignar su sede
        if ($user->sede_id && empty($_POST['sede_id'])) {
            $_POST['sede_id'] = $user->sede_id;
        }

        // Validación de posible duplicado en las últimas 24h (§ 4.5)
        if (empty($_POST['confirm_duplicate'])) {
            $remitente = trim((string)($_POST['remitente'] ?? ''));
            $sedeId = (int)($_POST['sede_id'] ?? 0);
            if (!empty($remitente) && $sedeId > 0) {
                $posibleDuplicado = $this->documentoRepository->findPossibleDuplicate($remitente, $sedeId);
                if ($posibleDuplicado) {
                    $_SESSION['_old_input'] = $_POST;
                    flash('warning', "Ya existe un documento cargado hoy para el remitente '{$remitente}' (Código: {$posibleDuplicado->codigo}). ¿Deseás registrarlo de todas formas?");
                    header("Location: /documentos/nuevo?duplicate_warning=1");
                    exit;
                }
            }
        }

        try {
            $files = $_FILES['adjuntos'] ?? $_FILES['adjunto'] ?? [];
            $documento = $this->documentoService->crear($_POST, $files, $user);

            flash('success', "Documento {$documento->codigo} registrado exitosamente. Se notificó a los responsables asignados.");
            header("Location: /documentos/{$documento->id}");
            exit;
        } catch (\Throwable $e) {
            $_SESSION['_old_input'] = $_POST;
            flash('error', $e->getMessage());
            header("Location: /documentos/nuevo");
            exit;
        }
    }

    public function show(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);

        $documento = $this->documentoRepository->findById($id);
        if (!$documento) {
            http_response_code(404);
            echo $this->view->render('errors/404', ['user' => $user], 'app');
            return;
        }

        // Control de alcance RBAC en servidor (§ 4.1)
        if (!$this->documentoRepository->canUserView($user, $documento)) {
            http_response_code(403);
            echo $this->view->render('errors/403', ['user' => $user, 'message' => 'No tenés permisos para ver este documento.'], 'app');
            return;
        }

        $adjuntos = $this->adjuntoRepository->findByDocumentoId($id);
        $historial = $this->historialRepository->findByDocumentoId($id);
        $comentarios = $this->comentarioRepository->findByDocumentoId($id);
        $categorias = $this->categoriaRepository->findAll(true, false);

        echo $this->view->render('documentos/ficha', [
            'user' => $user,
            'documento' => $documento,
            'adjuntos' => $adjuntos,
            'historial' => $historial,
            'comentarios' => $comentarios,
            'categorias' => $categorias,
        ], 'app');
    }

    public function tomar(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);

        try {
            $this->documentoService->tomarDocumento($id, $user);
            flash('success', 'Tomaste el documento. El estado pasó a "En curso".');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /documentos/{$id}");
        exit;
    }

    public function reclasificar(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $nuevaCatId = (int)($_POST['nueva_categoria_id'] ?? 0);
        $motivo = (string)($_POST['motivo'] ?? '');

        try {
            $this->documentoService->reclasificar($id, $nuevaCatId, $motivo, $user);
            flash('success', 'Documento reclasificado exitosamente. Se notificó a los nuevos responsables.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /documentos/{$id}");
        exit;
    }

    public function modificarPlazo(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $plazo = !empty($_POST['plazo_legal']) ? (string)$_POST['plazo_legal'] : null;

        try {
            $this->documentoService->modificarPlazoLegal($id, $plazo, $user);
            flash('success', 'Plazo legal actualizado.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /documentos/{$id}");
        exit;
    }

    public function resolver(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $constancia = (string)($_POST['constancia_cierre'] ?? '');

        try {
            $this->documentoService->resolver($id, $constancia, $user);
            flash('success', 'Documento marcado como Resuelto.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /documentos/{$id}");
        exit;
    }

    public function cerrar(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);

        try {
            $this->documentoService->cerrar($id, $user);
            flash('success', 'Documento archivado como Cerrado.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /documentos/{$id}");
        exit;
    }

    public function anular(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $motivo = (string)($_POST['motivo_anulacion'] ?? '');

        try {
            $this->documentoService->anular($id, $motivo, $user);
            flash('success', 'Documento anulado.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /documentos/{$id}");
        exit;
    }

    public function comentar(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $comentario = (string)($_POST['comentario'] ?? '');

        try {
            $this->documentoService->agregarComentario($id, $comentario, $user);
            flash('success', 'Comentario agregado.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /documentos/{$id}");
        exit;
    }

    public function adjuntar(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $id = (int)($vars['id'] ?? 0);
        $file = $_FILES['nuevo_adjunto'] ?? [];

        try {
            $this->documentoService->agregarAdjunto($id, $file, $user);
            flash('success', 'Nuevo archivo adjuntado.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        header("Location: /documentos/{$id}");
        exit;
    }

    /**
     * Endpoint protegido por RBAC para servir adjuntos de forma segura (§ 6.5)
     */
    public function serveAdjunto(array $vars): void
    {
        $user = $this->authProvider->currentUser();
        $docId = (int)($vars['id'] ?? 0);
        $adjuntoId = (int)($vars['adjunto_id'] ?? 0);

        $doc = $this->documentoRepository->findById($docId);
        if (!$doc || !$this->documentoRepository->canUserView($user, $doc)) {
            http_response_code(403);
            echo "Acceso denegado.";
            exit;
        }

        $adjunto = $this->adjuntoRepository->findById($adjuntoId);
        if (!$adjunto || $adjunto->documento_id !== $docId) {
            http_response_code(404);
            echo "Archivo no encontrado.";
            exit;
        }

        $absPath = $this->storage->getAbsolutePath($adjunto->ruta_almacenamiento);
        if (!file_exists($absPath)) {
            http_response_code(404);
            echo "El archivo no se encuentra en el almacenamiento.";
            exit;
        }

        header("Content-Type: " . $adjunto->tipo_mime);
        header("Content-Length: " . filesize($absPath));
        header('Content-Disposition: inline; filename="' . rawurlencode($adjunto->nombre_original) . '"');
        header("X-Content-Type-Options: nosniff");
        readfile($absPath);
        exit;
    }
}
