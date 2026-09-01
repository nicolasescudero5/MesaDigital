<?php $pageTitle = 'Sesión Expirada'; ?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-ink-50 dark:bg-ink-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 — Sesión Expirada | Mesa Digital</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="h-full flex items-center justify-center p-4">
    <div class="card max-w-md w-full text-center p-8 bg-white dark:bg-ink-900 shadow-modal border border-ink-200 dark:border-ink-800 rounded-xl">
        <div class="w-16 h-16 rounded-full bg-warning-50 dark:bg-warning-950/40 text-warning-600 dark:text-warning-400 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-xl font-bold text-ink-900 dark:text-white font-display mb-2">419 — Sesión o Token Expirado</h1>
        <p class="text-xs text-ink-500 mb-6">El token de seguridad ha caducado o no es válido. Por favor recargá la página o volvé a iniciar sesión.</p>
        <div class="flex items-center justify-center gap-3">
            <a href="javascript:location.reload()" class="btn-primary inline-flex items-center gap-1.5 font-semibold text-xs px-4 py-2.5">
                <span>Recargar Página</span>
            </a>
            <a href="/login" class="btn-neutral inline-flex items-center gap-1.5 font-semibold text-xs px-4 py-2.5">
                <span>Ir al Login</span>
            </a>
        </div>
    </div>
</body>
</html>
