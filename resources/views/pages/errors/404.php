<?php $pageTitle = 'Página No Encontrada'; ?>
<div class="card max-w-lg mx-auto text-center p-8 mt-12">
    <div class="w-16 h-16 rounded-full bg-ink-100 dark:bg-ink-800 text-ink-600 dark:text-ink-300 flex items-center justify-center mx-auto mb-4">
        <?= icon('file-text', 'w-8 h-8') ?>
    </div>
    <h1 class="text-2xl font-bold text-ink-900 dark:text-white font-display mb-2">404 — Página No Encontrada</h1>
    <p class="text-sm text-ink-500 mb-6">El documento, página o recurso solicitado no existe o fue movido.</p>
    <a href="/dashboard" class="btn-neutral inline-flex items-center gap-1.5 font-semibold">
        <?= icon('arrow-left', 'w-4 h-4') ?>
        <span>Volver al Dashboard</span>
    </a>
</div>
