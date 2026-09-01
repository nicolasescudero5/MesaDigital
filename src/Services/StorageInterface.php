<?php

declare(strict_types=1);

namespace App\Services;

interface StorageInterface
{
    /**
     * Valida y guarda un archivo subido devolviendo la ruta de almacenamiento relativa y metadatos
     * @param array $file Array de $_FILES['...']
     * @param string $destinationDir Subdirectorio destino (año/mes/sede_id/documento_id)
     * @return array{path: string, original_name: string, mime_type: string, size_bytes: int}
     * @throws \InvalidArgumentException
     */
    public function saveUploadedFile(array $file, string $destinationDir): array;

    /**
     * Obtiene la ruta física absoluta de un archivo almacenado
     */
    public function getAbsolutePath(string $relativePath): string;

    /**
     * Verifica si un archivo existe en el almacenamiento
     */
    public function exists(string $relativePath): bool;

    /**
     * Elimina un archivo del almacenamiento
     */
    public function delete(string $relativePath): bool;
}
