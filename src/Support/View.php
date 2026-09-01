<?php

declare(strict_types=1);

namespace App\Support;

class View
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
    }

    /**
     * Renderiza una vista con su layout correspondiente
     */
    public function render(string $viewPath, array $data = [], ?string $layout = 'app'): string
    {
        $viewFile = $this->basePath . '/pages/' . ltrim($viewPath, '/') . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("La vista no existe: {$viewPath} ({$viewFile})");
        }

        // Extraer variables para la vista
        extract($data, EXTR_SKIP);

        // Capturar contenido de la vista
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Si no se requiere layout, retornar contenido directo
        if ($layout === null) {
            return $content;
        }

        $layoutFile = $this->basePath . '/layouts/' . ltrim($layout, '/') . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("El layout no existe: {$layout} ({$layoutFile})");
        }

        // Capturar contenido envuelto en el layout
        ob_start();
        include $layoutFile;
        return ob_get_clean();
    }

    /**
     * Renderiza un partial reutilizable
     */
    public function partial(string $partialName, array $data = []): string
    {
        $partialFile = $this->basePath . '/partials/' . ltrim($partialName, '/') . '.php';

        if (!file_exists($partialFile)) {
            throw new \RuntimeException("El partial no existe: {$partialName} ({$partialFile})");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $partialFile;
        return ob_get_clean();
    }
}
