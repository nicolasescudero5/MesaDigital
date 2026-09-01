<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

class LocalStorageAdapter implements StorageInterface
{
    private string $basePath;
    private int $maxSizeMb;
    private array $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function __construct(string $basePath, int $maxSizeMb = 10)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->maxSizeMb = $maxSizeMb;
    }

    public function saveUploadedFile(array $file, string $destinationDir): array
    {
        // 1. Validar error de upload
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new InvalidArgumentException("Parámetros de archivo subido inválidos.");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException("Error al subir el archivo (código de error PHP: {$file['error']}).");
        }

        // 2. Validar tamaño (≤ maxSizeMb)
        $sizeBytes = (int)$file['size'];
        if ($sizeBytes > $this->maxSizeMb * 1024 * 1024) {
            throw new InvalidArgumentException(sprintf("El archivo supera el tamaño máximo permitido de %d MB.", $this->maxSizeMb));
        }

        if ($sizeBytes <= 0) {
            throw new InvalidArgumentException("El archivo subido está vacío.");
        }

        // 3. Validar MIME real usando finfo (nunca confiar en client mime o extensión)
        $tmpPath = $file['tmp_name'];
        if (!is_file($tmpPath) || !is_readable($tmpPath)) {
            throw new InvalidArgumentException("No se pudo acceder al archivo temporal subido.");
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($tmpPath);

        if (!array_key_exists($realMime, $this->allowedMimes)) {
            throw new InvalidArgumentException("Formato no permitido ({$realMime}). Solo se aceptan imágenes (JPG, PNG, WebP) o documentos PDF.");
        }

        // 4. Sanitizar nombre original y generar nombre único
        $originalName = basename($file['name'] ?? 'adjunto');
        $extension = $this->allowedMimes[$realMime];
        $uniqueName = sprintf("%s_%s.%s", bin2hex(random_bytes(8)), time(), $extension);

        // 5. Crear directorio de destino
        $targetDir = $this->basePath . '/' . trim($destinationDir, '/');
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new \RuntimeException("No se pudo crear el directorio de destino para almacenar el archivo.");
            }
        }

        $targetPath = $targetDir . '/' . $uniqueName;

        // 6. Mover archivo
        if (is_uploaded_file($tmpPath)) {
            if (!move_uploaded_file($tmpPath, $targetPath)) {
                throw new \RuntimeException("No se pudo mover el archivo subido al almacenamiento.");
            }
        } else {
            // Para tests automatizados donde no se usa HTTP POST multipart real
            if (!copy($tmpPath, $targetPath)) {
                throw new \RuntimeException("No se pudo copiar el archivo al almacenamiento.");
            }
        }

        $relativePath = trim($destinationDir, '/') . '/' . $uniqueName;

        return [
            'path' => $relativePath,
            'original_name' => $originalName,
            'mime_type' => $realMime,
            'size_bytes' => $sizeBytes
        ];
    }

    public function getAbsolutePath(string $relativePath): string
    {
        return $this->basePath . '/' . ltrim($relativePath, '/');
    }

    public function exists(string $relativePath): bool
    {
        return file_exists($this->getAbsolutePath($relativePath));
    }

    public function delete(string $relativePath): bool
    {
        $abs = $this->getAbsolutePath($relativePath);
        if (file_exists($abs)) {
            return unlink($abs);
        }
        return false;
    }
}
