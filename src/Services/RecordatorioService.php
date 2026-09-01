<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DocumentoRepository;
use Psr\Log\LoggerInterface;

class RecordatorioService
{
    public function __construct(
        private DocumentoRepository $documentoRepository,
        private DocumentoService $documentoService,
        private NotificacionService $notificacionService,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Procesa y encola recordatorios para documentos en estado "Recibido" hace más de N horas (§ 8.2 & § 8.3)
     */
    public function procesarRecordatorios48h(int $horas = 48): int
    {
        $docs = $this->documentoRepository->getDocumentosSinTomar($horas);
        $count = 0;

        foreach ($docs as $doc) {
            $this->notificacionService->notificarRecordatorio48h($doc);
            $count++;
            if ($this->logger) {
                $this->logger->info("Recordatorio 48h encolado para documento {$doc->codigo}");
            }
        }

        return $count;
    }

    /**
     * Procesa y encola alertas de vencimiento para plazos legales a 3 y 1 día (§ 8.2 & § 8.3)
     */
    public function procesarAlertasVencimiento(array $diasAlerta = [3, 1]): array
    {
        $resultados = [];

        foreach ($diasAlerta as $dias) {
            $docs = $this->documentoRepository->getDocumentosPorVencer($dias);
            $count = 0;

            foreach ($docs as $doc) {
                $this->notificacionService->notificarAlertaVencimiento($doc, $dias);
                $count++;
                if ($this->logger) {
                    $this->logger->info("Alerta de vencimiento a {$dias} día(s) encolada para documento {$doc->codigo}");
                }
            }

            $resultados[$dias] = $count;
        }

        return $resultados;
    }

    /**
     * Procesa el auto-cierre de documentos en 'Resuelto' tras N días (§ Supuesto #4 y § 8.3)
     */
    public function procesarAutoCierre(int $diasAutoCierre): int
    {
        if ($diasAutoCierre <= 0) {
            return 0; // Desactivado por configuración
        }

        $docs = $this->documentoRepository->getDocumentosParaAutoCierre($diasAutoCierre);
        $count = 0;

        foreach ($docs as $doc) {
            $this->documentoService->cerrar((int)$doc->id, null, "Auto-cierre automático tras {$diasAutoCierre} días en Resuelto");
            $count++;
            if ($this->logger) {
                $this->logger->info("Auto-cierre ejecutado para documento {$doc->codigo}");
            }
        }

        return $count;
    }
}
