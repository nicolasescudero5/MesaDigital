<!DOCTYPE html>
<html lang="es-AR" class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>Mesa Digital — Red Itínere</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Alpine.js Local -->
    <script defer src="/assets/js/alpine.min.js"></script>
    
    <!-- Compiled CSS & JS -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js"></script>
</head>
<body class="bg-ink-50 text-ink-700 min-h-screen flex flex-col antialiased selection:bg-brand-500 selection:text-white font-sans" x-data="{ sidebarOpen: false }">

    <!-- Enlace de accesibilidad para saltar al contenido (§ 7.8) -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:p-3 focus:bg-brand-600 focus:text-white focus:rounded-lg">
        Saltar al contenido principal
    </a>

    <!-- Banda de entorno no productivo (§ 7.3) -->
    <?php if (($_ENV['APP_ENV'] ?? 'local') !== 'production'): ?>
        <div class="danger-band z-50 sticky top-0" title="Entorno: <?= e($_ENV['APP_ENV'] ?? 'local') ?>"></div>
    <?php endif; ?>

    <div class="flex flex-1 min-h-0">
        <!-- Sidebar Navigation (§ 7.3) -->
        <?php global $container; $view = $container->get(\App\Support\View::class); ?>
        <?= $view->partial('sidebar', ['user' => current_user()]) ?>

        <!-- Contenedor Principal -->
        <div class="flex-1 flex flex-col min-w-0 lg:ml-[260px]">
            <!-- Top bar mobile -->
            <header class="lg:hidden flex items-center justify-between p-4 bg-white dark:bg-ink-900 border-b border-ink-200 dark:border-ink-800">
                <button @click="sidebarOpen = !sidebarOpen" class="btn-icon" aria-label="Abrir menú">
                    <?= icon('menu', 'w-6 h-6') ?>
                </button>
                <span class="font-display font-bold text-ink-900 dark:text-white text-lg">Mesa Digital</span>
                <button onclick="toggleTheme()" class="btn-icon" aria-label="Cambiar tema">
                    <span class="dark:hidden"><?= icon('moon', 'w-5 h-5') ?></span>
                    <span class="hidden dark:inline text-warning-400"><?= icon('sun', 'w-5 h-5') ?></span>
                </button>
            </header>

            <!-- Main Content Area -->
            <main id="main-content" class="flex-1 p-6 md:p-8 max-w-[1440px] w-full mx-auto">
                <!-- Mensajes Flash / Notificaciones -->
                <?= $view->partial('toast') ?>

                <!-- Contenido inyectado de la vista -->
                <?= $content ?>
            </main>
        </div>
    </div>
</body>
</html>
