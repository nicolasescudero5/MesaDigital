<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Documento;
use App\Models\NotificacionEnviada;
use App\Repositories\CategoriaRepository;
use App\Repositories\DocumentoRepository;
use App\Repositories\NotificacionRepository;
use App\Repositories\UsuarioRepository;
use Psr\Log\LoggerInterface;

class NotificacionService
{
    private string $appUrl;

    public function __construct(
        private NotificacionRepository $notificacionRepository,
        private CategoriaRepository $categoriaRepository,
        private DocumentoRepository $documentoRepository,
        private UsuarioRepository $usuarioRepository,
        private MailerInterface $mailer,
        private ?LoggerInterface $logger = null,
        string $appUrl = 'http://localhost:8080'
    ) {
        $this->appUrl = rtrim($appUrl, '/');
    }

    /**
     * Encola notificación por nuevo documento cargado
     */
    public function notificarCarga(Documento $documento, ?string $destinatarioEspecifico = null): void
    {
        if (!empty($destinatarioEspecifico)) {
            $this->notificacionRepository->create(new NotificacionEnviada(
                documento_id: (int)$documento->id,
                destinatario_email: $destinatarioEspecifico,
                tipo_evento: 'Documento_cargado'
            ));
            return;
        }

        $emails = $this->categoriaRepository->getEmailsResponsablesActivos($documento->categoria_id, (int)$documento->sede_id);

        if (empty($emails)) {
            // Caso especial (§ 4.6 y § 8.1): Alerta al Administrador si la categoría no tiene responsables
            $adminUsers = array_filter(
                $this->usuarioRepository->findAll(true),
                fn($u) => $u->isAdministrador()
            );

            foreach ($adminUsers as $admin) {
                $this->notificacionRepository->create(new NotificacionEnviada(
                    documento_id: (int)$documento->id,
                    destinatario_email: $admin->email,
                    tipo_evento: 'Sin_responsable'
                ));
            }
            return;
        }

        foreach ($emails as $email) {
            $this->notificacionRepository->create(new NotificacionEnviada(
                documento_id: (int)$documento->id,
                destinatario_email: $email,
                tipo_evento: 'Documento_cargado'
            ));
        }
    }

    /**
     * Encola notificación por reclasificación de categoría
     */
    public function notificarReclasificacion(Documento $documento, string $categoriaAnteriorNombre, string $motivo): void
    {
        $emails = $this->categoriaRepository->getEmailsResponsablesActivos($documento->categoria_id, (int)$documento->sede_id);

        foreach ($emails as $email) {
            $this->notificacionRepository->create(new NotificacionEnviada(
                documento_id: (int)$documento->id,
                destinatario_email: $email,
                tipo_evento: 'Documento_reclasificado'
            ));
        }
    }

    /**
     * Encola notificación al usuario de Recepción cuando el documento es Resuelto
     */
    public function notificarResolucion(Documento $documento, string $responsableNombre): void
    {
        $creador = $this->usuarioRepository->findById($documento->creado_por);
        if ($creador && !empty($creador->email)) {
            $this->notificacionRepository->create(new NotificacionEnviada(
                documento_id: (int)$documento->id,
                destinatario_email: $creador->email,
                tipo_evento: 'Documento_resuelto'
            ));
        }
    }

    /**
     * Encola recordatorio de 48h sin tomar
     */
    public function notificarRecordatorio48h(Documento $documento): void
    {
        $emails = $this->categoriaRepository->getEmailsResponsablesActivos($documento->categoria_id, (int)$documento->sede_id);
        foreach ($emails as $email) {
            $this->notificacionRepository->create(new NotificacionEnviada(
                documento_id: (int)$documento->id,
                destinatario_email: $email,
                tipo_evento: 'Recordatorio_48h'
            ));
        }
    }

    /**
     * Encola alerta de vencimiento (3 días o 1 día)
     */
    public function notificarAlertaVencimiento(Documento $documento, int $dias): void
    {
        $evento = $dias === 1 ? 'Alerta_vencimiento_1d' : 'Alerta_vencimiento_3d';
        $emails = $this->categoriaRepository->getEmailsResponsablesActivos($documento->categoria_id, (int)$documento->sede_id);

        foreach ($emails as $email) {
            $this->notificacionRepository->create(new NotificacionEnviada(
                documento_id: (int)$documento->id,
                destinatario_email: $email,
                tipo_evento: $evento
            ));
        }
    }

    /**
     * Procesa la cola de notificaciones pendientes
     * @return array{procesadas: int, enviadas: int, fallidas: int}
     */
    public function procesarCola(int $limite = 50): array
    {
        $pendientes = $this->notificacionRepository->findPendientes($limite);
        $enviadas = 0;
        $fallidas = 0;

        foreach ($pendientes as $notif) {
            $doc = $this->documentoRepository->findById($notif->documento_id);
            if (!$doc) {
                $this->notificacionRepository->recordFailure((int)$notif->id, 'Documento no encontrado', 4);
                $fallidas++;
                continue;
            }

            [$subject, $htmlBody, $altBody] = $this->buildEmailContent($notif, $doc);

            $success = $this->mailer->send($notif->destinatario_email, $subject, $htmlBody, $altBody);

            if ($success) {
                $this->notificacionRepository->markEnviado((int)$notif->id);
                $enviadas++;
            } else {
                $errorMessage = $this->mailer->getLastError() ?? 'Error desconocido de envío';
                $nextAttempt = $notif->intento_numero + 1;
                $this->notificacionRepository->recordFailure((int)$notif->id, $errorMessage, $nextAttempt);
                $fallidas++;

                // Si alcanzó el 3er intento fallido (§ 4.6), alertar al Administrador y loguear
                if ($nextAttempt > 3) {
                    if ($this->logger) {
                        $this->logger->error("Fallo crítico de notificación ID {$notif->id} tras 3 intentos. Destinatario: {$notif->destinatario_email}. Error: {$errorMessage}");
                    }
                }
            }
        }

        return [
            'procesadas' => count($pendientes),
            'enviadas' => $enviadas,
            'fallidas' => $fallidas
        ];
    }

    /**
     * Construye las plantillas de notificación según § 8.2 en español rioplatense formal
     */
    private function buildEmailContent(NotificacionEnviada $notif, Documento $doc): array
    {
        $urlDoc = "{$this->appUrl}/documentos/{$doc->id}";

        switch ($notif->tipo_evento) {
            case 'Documento_cargado':
                $subject = "Nuevo documento {$doc->codigo} — {$doc->tipo_documento_nombre} recibido en {$doc->sede_nombre}";
                $body = "
                    <h2 style='color:#101729;font-family:sans-serif;'>Nuevo documento en Mesa Digital</h2>
                    <p style='color:#364154;font-size:14px;line-height:1.5;'>Se cargó un nuevo documento en Mesa Digital.</p>
                    <table style='width:100%;max-width:500px;border-collapse:collapse;margin:20px 0;font-family:sans-serif;font-size:14px;'>
                        <tr><td style='padding:8px 0;color:#677489;width:140px;'><strong>Código:</strong></td><td style='padding:8px 0;color:#101729;'>{$doc->codigo}</td></tr>
                        <tr><td style='padding:8px 0;color:#677489;'><strong>Sede:</strong></td><td style='padding:8px 0;color:#101729;'>{$doc->sede_nombre}</td></tr>
                        <tr><td style='padding:8px 0;color:#677489;'><strong>Tipo:</strong></td><td style='padding:8px 0;color:#101729;'>{$doc->tipo_documento_nombre}</td></tr>
                        <tr><td style='padding:8px 0;color:#677489;'><strong>Remitente:</strong></td><td style='padding:8px 0;color:#101729;'>{$doc->remitente}</td></tr>
                        <tr><td style='padding:8px 0;color:#677489;'><strong>Categoría:</strong></td><td style='padding:8px 0;color:#101729;'>{$doc->categoria_nombre}</td></tr>
                        <tr><td style='padding:8px 0;color:#677489;'><strong>Fecha recepción:</strong></td><td style='padding:8px 0;color:#101729;'>{$doc->fecha_recepcion}</td></tr>
                    </table>
                    <p style='margin-top:25px;'><a href='{$urlDoc}' style='background-color:#4E47DD;color:#ffffff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:14px;'>Ver documento en Mesa Digital</a></p>
                ";
                $alt = "Se cargó un nuevo documento en Mesa Digital.\n\nCódigo: {$doc->codigo}\nSede: {$doc->sede_nombre}\nTipo: {$doc->tipo_documento_nombre}\nRemitente: {$doc->remitente}\nCategoría: {$doc->categoria_nombre}\nFecha de recepción: {$doc->fecha_recepcion}\n\nVer documento: {$urlDoc}";
                break;

            case 'Documento_reclasificado':
                $subject = "Se te derivó el documento {$doc->codigo}";
                $body = "
                    <h2 style='color:#101729;font-family:sans-serif;'>Documento derivado</h2>
                    <p style='color:#364154;font-size:14px;line-height:1.5;'>El documento <strong>{$doc->codigo}</strong> fue reclasificado a tu categoría (<strong>{$doc->categoria_nombre}</strong>).</p>
                    <p style='margin-top:25px;'><a href='{$urlDoc}' style='background-color:#4E47DD;color:#ffffff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:14px;'>Ver documento</a></p>
                ";
                $alt = "El documento {$doc->codigo} fue reclasificado a tu categoría ({$doc->categoria_nombre}).\n\nVer documento: {$urlDoc}";
                break;

            case 'Documento_resuelto':
                $subject = "Tu documento {$doc->codigo} fue resuelto";
                $body = "
                    <h2 style='color:#101729;font-family:sans-serif;'>Documento resuelto</h2>
                    <p style='color:#364154;font-size:14px;line-height:1.5;'>El documento <strong>{$doc->codigo}</strong> que cargaste fue marcado como resuelto.</p>
                    <div style='background:#F2F5F9;border-left:4px solid #3E7D61;padding:12px;margin:15px 0;font-size:14px;color:#20293A;'>
                        <strong>Constancia de cierre:</strong><br>{$doc->constancia_cierre}
                    </div>
                    <p style='margin-top:25px;'><a href='{$urlDoc}' style='background-color:#4E47DD;color:#ffffff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:14px;'>Ver documento</a></p>
                ";
                $alt = "El documento {$doc->codigo} que cargaste fue marcado como resuelto.\nConstancia: {$doc->constancia_cierre}\n\nVer documento: {$urlDoc}";
                break;

            case 'Recordatorio_48h':
                $subject = "Documento {$doc->codigo} sigue sin tomar";
                $body = "
                    <h2 style='color:#A76322;font-family:sans-serif;'>Recordatorio: Documento pendiente</h2>
                    <p style='color:#364154;font-size:14px;line-height:1.5;'>El documento <strong>{$doc->codigo}</strong> sigue en estado Recibido hace más de 48 horas sin que ningún responsable lo haya tomado.</p>
                    <p style='margin-top:25px;'><a href='{$urlDoc}' style='background-color:#4E47DD;color:#ffffff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:14px;'>Tomar documento</a></p>
                ";
                $alt = "El documento {$doc->codigo} sigue en estado Recibido hace más de 48 horas.\n\nVer documento: {$urlDoc}";
                break;

            case 'Alerta_vencimiento_3d':
            case 'Alerta_vencimiento_1d':
                $dias = $notif->tipo_evento === 'Alerta_vencimiento_1d' ? 1 : 3;
                $subject = "URGENTE: {$doc->codigo} vence en {$dias} día(s)";
                $body = "
                    <h2 style='color:#CA3A32;font-family:sans-serif;'>URGENTE: Plazo legal próximo a vencer</h2>
                    <p style='color:#364154;font-size:14px;line-height:1.5;'>El documento <strong>{$doc->codigo}</strong> tiene plazo legal fijado para el <strong>{$doc->plazo_legal}</strong> (faltan {$dias} día(s)).</p>
                    <p style='margin-top:25px;'><a href='{$urlDoc}' style='background-color:#CA3A32;color:#ffffff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:14px;'>Ver documento urgente</a></p>
                ";
                $alt = "El documento {$doc->codigo} tiene plazo legal el {$doc->plazo_legal} (faltan {$dias} día(s)).\n\nVer documento: {$urlDoc}";
                break;

            case 'Sin_responsable':
            default:
                $subject = "Categoría {$doc->categoria_nombre} sin responsables activos";
                $body = "
                    <h2 style='color:#CA3A32;font-family:sans-serif;'>Alerta de Configuración</h2>
                    <p style='color:#364154;font-size:14px;line-height:1.5;'>La categoría <strong>{$doc->categoria_nombre}</strong> se quedó sin responsables activos y tiene documentos pendientes de gestión.</p>
                    <p style='margin-top:25px;'><a href='{$this->appUrl}/categorias' style='background-color:#4E47DD;color:#ffffff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:14px;'>Asignar responsables en Administración</a></p>
                ";
                $alt = "La categoría {$doc->categoria_nombre} se quedó sin responsables activos y tiene documentos pendientes.\n\nRevisar en Administración -> Categorías.";
                break;
        }

        return [$subject, $body, $alt];
    }
}
