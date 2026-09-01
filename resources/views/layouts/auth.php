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

    <!-- Compiled CSS & JS -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js"></script>
    <script defer src="/assets/js/alpine.min.js"></script>
</head>
<body class="bg-ink-50 text-ink-700 min-h-screen flex flex-col justify-center items-center p-4 antialiased selection:bg-brand-500 selection:text-white dark:bg-ink-950 font-sans">

    <?php if (($_ENV['APP_ENV'] ?? 'local') !== 'production'): ?>
        <div class="danger-band fixed top-0 left-0 right-0 z-50" title="Entorno: <?= e($_ENV['APP_ENV'] ?? 'local') ?>"></div>
    <?php endif; ?>

    <main id="main-content" class="w-full max-w-[480px] my-auto">
        <?php global $container; $view = $container->get(\App\Support\View::class); ?>
        <?= $view->partial('toast') ?>
        <?= $content ?>
    </main>

    <footer class="mt-8 text-center text-xs text-ink-400">
        &copy; <?= date('Y') ?> Suite Red Itínere — Mesa Digital
    </footer>
</body>
</html>
