<?php $pageTitle = 'Error 500 — Error Interno'; ?>
<div class="card max-w-lg mx-auto text-center p-8 mt-12 shadow-modal">
    <div class="w-16 h-16 rounded-full bg-danger-50 dark:bg-danger-950/40 text-danger-600 dark:text-danger-400 flex items-center justify-center mx-auto mb-4">
        <?= icon('alert-octagon', 'w-8 h-8 text-danger-600') ?>
    </div>
    <h1 class="text-2xl font-bold text-ink-900 dark:text-white font-display mb-2">Error 500</h1>
    <p class="text-sm text-ink-500 mb-6"><?= e($message ?? 'Ha ocurrido un error inesperado en el servidor. Por favor, intentá nuevamente.') ?></p>

    <?php if (($_ENV['APP_ENV'] ?? 'local') === 'local' && !empty($exception)): ?>
        <div class="text-left bg-ink-900 text-ink-100 p-4 rounded-xl text-xs font-mono mb-6 overflow-x-auto">
            <div class="text-danger-400 font-bold mb-1"><?= get_class($exception) ?>: <?= e($exception->getMessage()) ?></div>
            <div class="text-ink-400 mb-2">en <?= e($exception->getFile()) ?>:<?= $exception->getLine() ?></div>
            <div class="text-[11px] text-ink-400 whitespace-pre-wrap leading-tight"><?= e($exception->getTraceAsString()) ?></div>
        </div>
    <?php endif; ?>

    <a href="/dashboard" class="btn-neutral inline-flex items-center gap-1.5 font-semibold">
        <?= icon('arrow-left', 'w-4 h-4 text-ink-500') ?>
        <span>Volver al Panel Principal</span>
    </a>
</div>
