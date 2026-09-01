<?php $pageTitle = 'Acceso Denegado'; ?>
<div class="card max-w-lg mx-auto text-center p-8 mt-12">
    <div class="w-16 h-16 rounded-full bg-danger-50 dark:bg-danger-950/40 text-danger-600 dark:text-danger-400 flex items-center justify-center mx-auto mb-4">
        <?= icon('shield-alert', 'w-8 h-8') ?>
    </div>
    <h1 class="text-2xl font-bold text-ink-900 dark:text-white font-display mb-2">403 — Acceso Denegado</h1>
    <p class="text-sm text-ink-500 mb-6"><?= e($message ?? 'No tenés los permisos o el alcance de sede necesario para acceder a este recurso.') ?></p>
    <a href="/dashboard" class="btn-neutral inline-flex items-center gap-1.5 font-semibold">
        <?= icon('arrow-left', 'w-4 h-4') ?>
        <span>Volver al Dashboard</span>
    </a>
</div>
