<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Documento;

class ExportService
{
    /**
     * Exporta listado de documentos a formato CSV compatible con Excel es-AR
     * @param Documento[] $documentos
     */
    public function exportToCsv(array $documentos): string
    {
        $output = fopen('php://temp', 'r+');
        
        // UTF-8 BOM para apertura correcta en Microsoft Excel
        fputs($output, "\xEF\xBB\xBF");

        // Cabeceras
        fputcsv($output, [
            'Código',
            'Sede',
            'Categoría',
            'Tipo de Documento',
            'Remitente',
            'Carácter Remitente',
            'Asunto',
            'Fecha Recepción',
            'Plazo Legal',
            'Estado',
            'Cargado Por',
            'Fecha Creación'
        ], ';');

        foreach ($documentos as $doc) {
            fputcsv($output, [
                $doc->codigo,
                $doc->sede_nombre ?? '',
                $doc->categoria_nombre ?? '',
                $doc->tipo_documento_nombre ?? '',
                $doc->remitente,
                $doc->caracter_remitente_nombre ?? '',
                $doc->asunto,
                $doc->fecha_recepcion,
                $doc->plazo_legal ?? 'N/A',
                $doc->estado,
                $doc->creador_nombre ?? '',
                $doc->creado_el ?? ''
            ], ';');
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent ?: '';
    }

    /**
     * Exporta listado de documentos a formato XML / XLS compatible con Excel
     * @param Documento[] $documentos
     */
    public function exportToExcel(array $documentos): string
    {
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $html .= '<style>table { border-collapse: collapse; font-family: sans-serif; font-size: 12px; } th { background-color: #4E47DD; color: white; border: 1px solid #372FA0; padding: 6px; } td { border: 1px solid #CDD5E0; padding: 6px; }</style>';
        $html .= '</head><body>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Código</th><th>Sede</th><th>Categoría</th><th>Tipo</th><th>Remitente</th><th>Carácter</th><th>Asunto</th><th>Fecha Recepción</th><th>Plazo Legal</th><th>Estado</th><th>Cargado Por</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($documentos as $doc) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($doc->codigo) . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->sede_nombre ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->categoria_nombre ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->tipo_documento_nombre ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->remitente) . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->caracter_remitente_nombre ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->asunto) . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->fecha_recepcion) . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->plazo_legal ?? 'N/A') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->estado) . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->creador_nombre ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        return $html;
    }

    /**
     * Genera documento HTML optimizado para vista de impresión / exportación PDF
     * @param Documento[] $documentos
     */
    public function exportToPdf(array $documentos): string
    {
        $fecha = date('d/m/Y H:i');
        $html = '<!DOCTYPE html><html lang="es-AR"><head><meta charset="utf-8">';
        $html .= '<title>Reporte de Documentos — Mesa Digital</title>';
        $html .= '<style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 11px; color: #20293A; margin: 20px; }
            h1 { font-size: 18px; margin-bottom: 4px; color: #101729; }
            p { margin-top: 0; color: #677489; font-size: 11px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background-color: #F2F5F9; color: #4A5567; font-weight: 600; text-align: left; padding: 6px 8px; border-bottom: 2px solid #CDD5E0; font-size: 10px; text-transform: uppercase; }
            td { padding: 6px 8px; border-bottom: 1px solid #E3E8EF; }
            .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 10px; }
            @media print { @page { size: landscape; } button { display: none; } }
        </style></head><body>';
        $html .= '<div style="display:flex;justify-content:space-between;align-items:center;">';
        $html .= '<div><h1>Mesa Digital — Reporte de Documentos</h1><p>Generado el ' . $fecha . ' | Total: ' . count($documentos) . ' documentos</p></div>';
        $html .= '<button onclick="window.print()" style="background:#4E47DD;color:white;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:bold;">Imprimir / Guardar como PDF</button>';
        $html .= '</div>';
        $html .= '<table><thead><tr>';
        $html .= '<th>Código</th><th>Sede</th><th>Categoría</th><th>Tipo</th><th>Remitente</th><th>Asunto</th><th>Recepción</th><th>Plazo Legal</th><th>Estado</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($documentos as $doc) {
            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars($doc->codigo) . '</strong></td>';
            $html .= '<td>' . htmlspecialchars($doc->sede_nombre ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->categoria_nombre ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->tipo_documento_nombre ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->remitente) . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->asunto) . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->fecha_recepcion) . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->plazo_legal ?? '—') . '</td>';
            $html .= '<td>' . htmlspecialchars($doc->estado) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        return $html;
    }
}
