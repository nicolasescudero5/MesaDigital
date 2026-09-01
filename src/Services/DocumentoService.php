<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Documento;
use App\Models\DocumentoAdjunto;
use App\Models\DocumentoComentario;
use App\Models\DocumentoHistorial;
use App\Models\Usuario;
use App\Repositories\CategoriaRepository;
use App\Repositories\DocumentoAdjuntoRepository;
use App\Repositories\DocumentoComentarioRepository;
use App\Repositories\DocumentoHistorialRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\TipoDocumentoRepository;
use App\Validation\Validator;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class DocumentoService
{
    public function __construct(
        private PDO $pdo,
        private DocumentoRepository $documentoRepository,
        private DocumentoHistorialRepository $historialRepository,
        private DocumentoAdjuntoRepository $adjuntoRepository,
        private DocumentoComentarioRepository $comentarioRepository,
        private CategoriaRepository $categoriaRepository,
        private TipoDocumentoRepository $tipoDocumentoRepository,
        private StorageInterface $storage,
        private NotificacionService $notificacionService
    ) {}

    /**
     * Crea un nuevo documento en el sistema
     *
     * @param array $data Datos del formulario
     * @param array $uploadedFiles Array de archivos $_FILES['adjuntos'] o array de archivos individuales
     * @param Usuario $creador Usuario que está registrando el documento
     * @return Documento
     * @throws InvalidArgumentException
     */
    public function crear(array $data, array $uploadedFiles, Usuario $creador): Documento
    {
        // Si no se envió categoria_id explícito, resolverlo automáticamente del tipo de documento
        if (empty($data['categoria_id']) && !empty($data['tipo_documento_id'])) {
            $tipoDoc = $this->tipoDocumentoRepository->findById((int)$data['tipo_documento_id']);
            if ($tipoDoc && $tipoDoc->categoria_id) {
                $data['categoria_id'] = $tipoDoc->categoria_id;
            }
        }
        // 1. Validaciones básicas con Validator
        $validator = Validator::make($data, [
            'sede_id' => 'required|integer',
            'categoria_id' => 'required|integer',
            'tipo_documento_id' => 'required|integer',
            'remitente' => 'required|min:2|max:200',
            'asunto' => 'required|min:3|max:200',
            'descripcion' => 'required|min:5',
            'fecha_recepcion' => 'required|date',
            'plazo_legal' => 'date|date_after_or_equal:fecha_recepcion',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->firstError() ?? 'Datos de documento inválidos.');
        }

        // Si el usuario es Recepción o Dirección, validar que la sede coincida con su sede asignada
        if (($creador->isRecepcion() || $creador->isDireccion()) && (int)$data['sede_id'] !== $creador->sede_id) {
            throw new InvalidArgumentException("No tenés permiso para crear documentos en otra sede.");
        }

        // 2. Validación de categoría con responsables activos (§ 4.5)
        $categoriaId = (int)$data['categoria_id'];
        if (!$this->categoriaRepository->tieneResponsablesActivos($categoriaId)) {
            throw new InvalidArgumentException("Esta categoría no tiene responsables activos, contactá al Administrador.");
        }

        // 3. Normalizar archivos subidos
        $filesToProcess = $this->normalizeFilesArray($uploadedFiles);
        if (empty($filesToProcess)) {
            throw new InvalidArgumentException("Debe adjuntar al menos una foto o archivo del documento.");
        }

        // 4. Iniciar transacción
        $this->pdo->beginTransaction();
        try {
            $codigo = $this->documentoRepository->generateNextCodigo();

            $doc = new Documento(
                codigo: $codigo,
                sede_id: (int)$data['sede_id'],
                categoria_id: $categoriaId,
                tipo_documento_id: (int)$data['tipo_documento_id'],
                caracter_remitente_id: !empty($data['caracter_remitente_id']) ? (int)$data['caracter_remitente_id'] : null,
                remitente: trim((string)$data['remitente']),
                asunto: trim((string)$data['asunto']),
                descripcion: trim((string)$data['descripcion']),
                fecha_recepcion: (string)$data['fecha_recepcion'],
                plazo_legal: !empty($data['plazo_legal']) ? (string)$data['plazo_legal'] : null,
                estado: 'Recibido',
                creado_por: (int)$creador->id,
                activo: true
            );

            $documentoId = $this->documentoRepository->create($doc);
            $doc->id = $documentoId;

            // Procesar y guardar adjuntos
            $year = date('Y');
            $month = date('m');
            $subDir = "documentos/{$year}/{$month}/{$doc->sede_id}/{$documentoId}";

            $isFirst = true;
            foreach ($filesToProcess as $file) {
                $savedMeta = $this->storage->saveUploadedFile($file, $subDir);
                $adjunto = new DocumentoAdjunto(
                    documento_id: $documentoId,
                    nombre_original: $savedMeta['original_name'],
                    ruta_almacenamiento: $savedMeta['path'],
                    tipo_mime: $savedMeta['mime_type'],
                    tamano_bytes: $savedMeta['size_bytes'],
                    es_principal: $isFirst,
                    subido_por: (int)$creador->id
                );
                $this->adjuntoRepository->create($adjunto);
                $isFirst = false;
            }

            // Registrar en historial de auditoría (§ 5.2)
            $this->historialRepository->create(new DocumentoHistorial(
                documento_id: $documentoId,
                accion: 'Cargado',
                estado_anterior: null,
                estado_nuevo: 'Recibido',
                usuario_id: (int)$creador->id,
                detalle: sprintf("Documento registrado en Mesa Digital con %d adjunto(s).", count($filesToProcess))
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // 5. Notificar asíncronamente a los responsables activos (§ 8.1)
        $destinatarioEmail = !empty($data['responsable_email']) ? trim((string)$data['responsable_email']) : null;
        $this->notificacionService->notificarCarga($doc, $destinatarioEmail);

        return $this->documentoRepository->findById($doc->id);
    }

    /**
     * Un responsable toma el documento pasando el estado a "En curso"
     */
    public function tomarDocumento(int $documentoId, Usuario $usuario): Documento
    {
        $doc = $this->documentoRepository->findById($documentoId);
        if (!$doc) {
            throw new InvalidArgumentException("Documento no encontrado.");
        }

        if (!$this->documentoRepository->canUserView($usuario, $doc)) {
            throw new InvalidArgumentException("No tenés permiso para acceder a este documento.");
        }

        if (!$usuario->isAdministrador() && !$usuario->isResponsable()) {
            throw new InvalidArgumentException("Solo un responsable de la categoría o el administrador puede tomar el documento.");
        }

        if ($doc->estado !== 'Recibido') {
            throw new InvalidArgumentException("El documento ya se encuentra en estado '{$doc->estado}'.");
        }

        $this->documentoRepository->updateEstado($documentoId, 'En curso', null, (int)$usuario->id);

        $this->historialRepository->create(new DocumentoHistorial(
            documento_id: $documentoId,
            accion: 'Marcado_en_curso',
            estado_anterior: 'Recibido',
            estado_nuevo: 'En curso',
            usuario_id: (int)$usuario->id,
            detalle: "El documento fue tomado para su gestión por {$usuario->nombre}."
        ));

        return $this->documentoRepository->findById($documentoId);
    }

    /**
     * Reclasifica el documento a otra categoría
     */
    public function reclasificar(int $documentoId, int $nuevaCategoriaId, string $motivo, Usuario $usuario): Documento
    {
        $doc = $this->documentoRepository->findById($documentoId);
        if (!$doc) {
            throw new InvalidArgumentException("Documento no encontrado.");
        }

        if (!$this->documentoRepository->canUserView($usuario, $doc)) {
            throw new InvalidArgumentException("No tenés permiso para acceder a este documento.");
        }

        if (!$usuario->isAdministrador() && !$usuario->isResponsable()) {
            throw new InvalidArgumentException("Solo un responsable de categoría o el administrador pueden reclasificar documentos.");
        }

        if (mb_strlen(trim($motivo)) < 10) {
            throw new InvalidArgumentException("El motivo de reclasificación es obligatorio y debe tener al menos 10 caracteres.");
        }

        if ($doc->categoria_id === $nuevaCategoriaId) {
            throw new InvalidArgumentException("La nueva categoría debe ser diferente a la actual.");
        }

        $nuevaCat = $this->categoriaRepository->findById($nuevaCategoriaId);
        if (!$nuevaCat || !$nuevaCat->activo) {
            throw new InvalidArgumentException("La categoría seleccionada no existe o no está activa.");
        }

        $catAnteriorNombre = $doc->categoria_nombre ?? 'Categoría previa';

        $this->documentoRepository->updateCategoria($documentoId, $nuevaCategoriaId, (int)$usuario->id);

        $this->historialRepository->create(new DocumentoHistorial(
            documento_id: $documentoId,
            accion: 'Reclasificado',
            estado_anterior: $doc->estado,
            estado_nuevo: $doc->estado,
            usuario_id: (int)$usuario->id,
            detalle: "Reclasificado de '{$catAnteriorNombre}' a '{$nuevaCat->nombre}'. Motivo: {$motivo}"
        ));

        // Re-notificar a los responsables de la nueva categoría (§ 4.2 y § 8.1)
        $docActualizado = $this->documentoRepository->findById($documentoId);
        $this->notificacionService->notificarReclasificacion($docActualizado, $catAnteriorNombre, $motivo);

        return $docActualizado;
    }

    /**
     * Modifica el plazo legal del documento
     */
    public function modificarPlazoLegal(int $documentoId, ?string $nuevoPlazo, Usuario $usuario): Documento
    {
        $doc = $this->documentoRepository->findById($documentoId);
        if (!$doc) {
            throw new InvalidArgumentException("Documento no encontrado.");
        }

        if (!$this->documentoRepository->canUserView($usuario, $doc)) {
            throw new InvalidArgumentException("No tenés permiso para acceder a este documento.");
        }

        if (!$usuario->isAdministrador() && !$usuario->isResponsable()) {
            throw new InvalidArgumentException("Solo los responsables o el administrador pueden modificar el plazo legal.");
        }

        if (!empty($nuevoPlazo)) {
            if (strtotime($nuevoPlazo) < strtotime($doc->fecha_recepcion)) {
                throw new InvalidArgumentException("El plazo legal no puede ser anterior a la fecha de recepción ({$doc->fecha_recepcion}).");
            }
        }

        $this->documentoRepository->updatePlazoLegal($documentoId, $nuevoPlazo, (int)$usuario->id);

        $this->historialRepository->create(new DocumentoHistorial(
            documento_id: $documentoId,
            accion: 'Plazo_legal_modificado',
            estado_anterior: $doc->estado,
            estado_nuevo: $doc->estado,
            usuario_id: (int)$usuario->id,
            detalle: "Plazo legal actualizado a: " . ($nuevoPlazo ?: 'Sin plazo')
        ));

        return $this->documentoRepository->findById($documentoId);
    }

    /**
     * Resuelve el documento cargando la constancia obligatoria
     */
    public function resolver(int $documentoId, string $constanciaCierre, Usuario $usuario): Documento
    {
        $doc = $this->documentoRepository->findById($documentoId);
        if (!$doc) {
            throw new InvalidArgumentException("Documento no encontrado.");
        }

        if (!$this->documentoRepository->canUserView($usuario, $doc)) {
            throw new InvalidArgumentException("No tenés permiso para acceder a este documento.");
        }

        if (!$usuario->isAdministrador() && !$usuario->isResponsable()) {
            throw new InvalidArgumentException("Solo los responsables o el administrador pueden resolver documentos.");
        }

        if (mb_strlen(trim($constanciaCierre)) < 10) {
            throw new InvalidArgumentException("La constancia de cierre es obligatoria y debe tener al menos 10 caracteres.");
        }

        if (!in_array($doc->estado, ['Recibido', 'En curso'], true)) {
            throw new InvalidArgumentException("Solo documentos en estado 'Recibido' o 'En curso' pueden ser resueltos.");
        }

        $this->documentoRepository->updateEstado($documentoId, 'Resuelto', trim($constanciaCierre), (int)$usuario->id);

        $this->historialRepository->create(new DocumentoHistorial(
            documento_id: $documentoId,
            accion: 'Marcado_resuelto',
            estado_anterior: $doc->estado,
            estado_nuevo: 'Resuelto',
            usuario_id: (int)$usuario->id,
            detalle: "Documento marcado como resuelto. Constancia: {$constanciaCierre}"
        ));

        $docResuelto = $this->documentoRepository->findById($documentoId);
        $this->notificacionService->notificarResolucion($docResuelto, $usuario->nombre);

        return $docResuelto;
    }

    /**
     * Archiva el documento pasando el estado a "Cerrado" (manual o automático por cron)
     */
    public function cerrar(int $documentoId, ?Usuario $usuario = null, string $motivo = 'Archivo definitivo'): Documento
    {
        $doc = $this->documentoRepository->findById($documentoId);
        if (!$doc) {
            throw new InvalidArgumentException("Documento no encontrado.");
        }

        if ($usuario) {
            if (!$usuario->isAdministrador() && !$usuario->isResponsable()) {
                throw new InvalidArgumentException("No tenés permiso para cerrar este documento.");
            }
        }

        if ($doc->estado !== 'Resuelto') {
            throw new InvalidArgumentException("Solo documentos en estado 'Resuelto' pueden ser archivados como 'Cerrado'.");
        }

        $userId = $usuario ? (int)$usuario->id : null;
        $this->documentoRepository->updateEstado($documentoId, 'Cerrado', null, $userId);

        $this->historialRepository->create(new DocumentoHistorial(
            documento_id: $documentoId,
            accion: 'Cerrado',
            estado_anterior: 'Resuelto',
            estado_nuevo: 'Cerrado',
            usuario_id: $userId,
            detalle: $usuario ? "Archivado manualmente por {$usuario->nombre}. ({$motivo})" : "Cierre automático del sistema. ({$motivo})"
        ));

        return $this->documentoRepository->findById($documentoId);
    }

    /**
     * Anulación lógica de un documento (solo Administrador)
     */
    public function anular(int $documentoId, string $motivoAnulacion, Usuario $usuario): Documento
    {
        if (!$usuario->isAdministrador()) {
            throw new InvalidArgumentException("Solo un Administrador puede anular documentos.");
        }

        if (mb_strlen(trim($motivoAnulacion)) < 5) {
            throw new InvalidArgumentException("El motivo de anulación es obligatorio.");
        }

        $doc = $this->documentoRepository->findById($documentoId);
        if (!$doc) {
            throw new InvalidArgumentException("Documento no encontrado.");
        }

        $this->documentoRepository->anular($documentoId, trim($motivoAnulacion), (int)$usuario->id);

        $this->historialRepository->create(new DocumentoHistorial(
            documento_id: $documentoId,
            accion: 'Anulado',
            estado_anterior: $doc->estado,
            estado_nuevo: $doc->estado,
            usuario_id: (int)$usuario->id,
            detalle: "Documento anulado por el Administrador. Motivo: {$motivoAnulacion}"
        ));

        return $this->documentoRepository->findById($documentoId);
    }

    /**
     * Agrega un comentario interno al documento
     */
    public function agregarComentario(int $documentoId, string $comentarioTexto, Usuario $usuario): DocumentoComentario
    {
        $comentarioTexto = trim($comentarioTexto);
        if (mb_strlen($comentarioTexto) < 2) {
            throw new InvalidArgumentException("El comentario no puede estar vacío.");
        }

        $doc = $this->documentoRepository->findById($documentoId);
        if (!$doc) {
            throw new InvalidArgumentException("Documento no encontrado.");
        }

        if (!$this->documentoRepository->canUserView($usuario, $doc)) {
            throw new InvalidArgumentException("No tenés permiso para comentar en este documento.");
        }

        $comentario = new DocumentoComentario(
            documento_id: $documentoId,
            usuario_id: (int)$usuario->id,
            comentario: $comentarioTexto
        );

        $comentarioId = $this->comentarioRepository->create($comentario);
        $comentario->id = $comentarioId;

        $this->historialRepository->create(new DocumentoHistorial(
            documento_id: $documentoId,
            accion: 'Comentario',
            estado_anterior: $doc->estado,
            estado_nuevo: $doc->estado,
            usuario_id: (int)$usuario->id,
            detalle: "Comentario agregado: \"{$comentarioTexto}\""
        ));

        return $comentario;
    }

    /**
     * Agrega un adjunto adicional al documento
     */
    public function agregarAdjunto(int $documentoId, array $file, Usuario $usuario): DocumentoAdjunto
    {
        $doc = $this->documentoRepository->findById($documentoId);
        if (!$doc) {
            throw new InvalidArgumentException("Documento no encontrado.");
        }

        if (!$this->documentoRepository->canUserView($usuario, $doc)) {
            throw new InvalidArgumentException("No tenés permiso para adjuntar archivos en este documento.");
        }

        $year = date('Y');
        $month = date('m');
        $subDir = "documentos/{$year}/{$month}/{$doc->sede_id}/{$documentoId}";

        $savedMeta = $this->storage->saveUploadedFile($file, $subDir);

        $adjunto = new DocumentoAdjunto(
            documento_id: $documentoId,
            nombre_original: $savedMeta['original_name'],
            ruta_almacenamiento: $savedMeta['path'],
            tipo_mime: $savedMeta['mime_type'],
            tamano_bytes: $savedMeta['size_bytes'],
            es_principal: false,
            subido_por: (int)$usuario->id
        );

        $adjuntoId = $this->adjuntoRepository->create($adjunto);
        $adjunto->id = $adjuntoId;

        $this->historialRepository->create(new DocumentoHistorial(
            documento_id: $documentoId,
            accion: 'Adjunto_agregado',
            estado_anterior: $doc->estado,
            estado_nuevo: $doc->estado,
            usuario_id: (int)$usuario->id,
            detalle: "Nuevo adjunto subido: {$savedMeta['original_name']} ({$savedMeta['mime_type']})"
        ));

        return $adjunto;
    }

    /**
     * Normaliza la estructura de $_FILES para soportar tanto un solo archivo como arrays multiples
     */
    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];

        // Si es un archivo directo: ['name' => ..., 'type' => ..., 'tmp_name' => ..., 'error' => ..., 'size' => ...]
        if (isset($files['name']) && is_string($files['name'])) {
            if ($files['error'] !== UPLOAD_ERR_NO_FILE && !empty($files['tmp_name'])) {
                $normalized[] = $files;
            }
            return $normalized;
        }

        // Si es un array de archivos: ['name' => [...], 'tmp_name' => [...], ...]
        if (isset($files['name']) && is_array($files['name'])) {
            foreach ($files['name'] as $idx => $name) {
                if ($files['error'][$idx] !== UPLOAD_ERR_NO_FILE && !empty($files['tmp_name'][$idx])) {
                    $normalized[] = [
                        'name' => $files['name'][$idx],
                        'type' => $files['type'][$idx],
                        'tmp_name' => $files['tmp_name'][$idx],
                        'error' => $files['error'][$idx],
                        'size' => $files['size'][$idx],
                    ];
                }
            }
            return $normalized;
        }

        // Si ya es un array de items normalizados: [ ['name' => ...], ['name' => ...] ]
        foreach ($files as $file) {
            if (is_array($file) && isset($file['tmp_name']) && !empty($file['tmp_name']) && ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_NO_FILE) {
                $normalized[] = $file;
            }
        }

        return $normalized;
    }
}
