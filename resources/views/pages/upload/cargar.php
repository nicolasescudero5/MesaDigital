<?php
$pageTitle = 'Cargar Foto / Archivo — Mesa Digital';
global $container;
$view = $container->get(\App\Support\View::class);
?>

<!DOCTYPE html>
<html lang="es" class="h-full bg-ink-50 dark:bg-ink-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Alpine.js & Tailwind Fonts -->
    <script defer src="<?= asset_url('js/alpine.min.js') ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS del Sistema -->
    <link rel="stylesheet" href="<?= asset_url('css/app.css') ?>">
</head>
<body class="h-full flex flex-col justify-center items-center p-4 font-sans text-ink-900 dark:text-white">

    <div class="w-full max-w-md bg-white dark:bg-ink-900 rounded-2xl shadow-xl border border-ink-200 dark:border-ink-800 p-6 md:p-8" x-data="{
        selectedCount: 0,
        fileNames: [],
        handleSelection(e) {
            const files = e.target.files;
            this.selectedCount = files.length;
            this.fileNames = Array.from(files).map(f => f.name + ' (' + (f.size / 1024 / 1024).toFixed(2) + ' MB)');
        }
    }">

        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 flex items-center justify-center mx-auto mb-3 shadow-inner">
                <?= icon('camera', 'w-8 h-8 text-brand-600') ?>
            </div>
            <h1 class="text-xl font-extrabold text-ink-900 dark:text-white tracking-tight">Mesa Digital</h1>
            <p class="text-xs font-semibold text-ink-500 dark:text-ink-400 mt-1">Carga móvil de fotos o archivos PDF</p>
        </div>

        <!-- Flash messages -->
        <?php if ($flashError = flash('error')): ?>
            <div class="mb-5 p-4 rounded-xl bg-danger-50 text-danger-900 border border-danger-200 text-xs font-semibold flex items-center gap-2">
                <?= icon('alert-triangle', 'w-4 h-4 text-danger-600 shrink-0') ?>
                <span><?= e($flashError['message'] ?? $flashError) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess = flash('success')): ?>
            <div class="mb-5 p-4 rounded-xl bg-success-50 text-success-900 border border-success-200 text-xs font-semibold flex items-center gap-2">
                <?= icon('check-circle', 'w-4 h-4 text-success-600 shrink-0') ?>
                <span><?= e($flashSuccess['message'] ?? $flashSuccess) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($alreadyCompleted): ?>
            <!-- Estado Completado -->
            <div class="p-5 rounded-2xl bg-success-50/70 dark:bg-success-950/40 border border-success-200 dark:border-success-800 text-center mb-6 shadow-xs">
                <div class="w-12 h-12 rounded-full bg-success-600 text-white flex items-center justify-center mx-auto mb-2 shadow-md">
                    <?= icon('check-circle', 'w-7 h-7 text-white') ?>
                </div>
                <h3 class="font-bold text-sm text-success-900 dark:text-success-200">¡Foto / Archivo transmitido a la PC!</h3>
                <p class="text-xs text-success-700 dark:text-success-300 mt-1">
                    Podés seguir adjuntando más fotos o archivos PDF a continuación.
                </p>

                <?php if (!empty($files)): ?>
                    <div class="mt-4 pt-3 border-t border-success-200 dark:border-success-800 text-left">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-success-800 dark:text-success-300 block mb-1">
                            Archivos enviados en esta sesión (<?= count($files) ?>):
                        </span>
                        <ul class="text-xs text-success-900 dark:text-success-200 space-y-1">
                            <?php foreach ($files as $f): ?>
                                <li class="flex items-center justify-between bg-white/80 dark:bg-ink-900/80 px-3 py-1.5 rounded-lg border border-success-200 dark:border-success-800">
                                    <span class="truncate max-w-[200px] font-medium"><?= e($f['original_name']) ?></span>
                                    <span class="text-[10px] text-ink-400 font-bold"><?= number_format($f['size_bytes'] / 1024 / 1024, 2) ?> MB</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="<?= app_url('/cargar') ?>" method="POST" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div>
                <label class="form-label text-xs font-bold text-ink-700 dark:text-ink-200 mb-2 block">
                    <?= $alreadyCompleted ? 'Tomar otra foto o adjuntar más archivos:' : 'Seleccioná o tomá una foto con la cámara:' ?>
                </label>

                <div class="border-2 border-dashed border-brand-300 dark:border-brand-700 hover:border-brand-500 rounded-2xl p-6 text-center bg-brand-50/30 dark:bg-brand-950/20 transition-colors">
                    <input type="file" id="archivos_input" name="archivos[]" accept="image/*,application/pdf" capture="environment" multiple @change="handleSelection" class="hidden">

                    <label for="archivos_input" class="cursor-pointer inline-flex flex-col items-center justify-center space-y-2">
                        <div class="w-14 h-14 rounded-full bg-brand-500 text-white flex items-center justify-center shadow-lg hover:scale-105 transition-transform">
                            <?= icon('camera', 'w-7 h-7 text-white') ?>
                        </div>
                        <span class="text-sm font-extrabold text-brand-600 dark:text-brand-400">
                            <?= $alreadyCompleted ? 'Tomar otra Foto / Elegir PDF' : 'Tomar Foto o Elegir PDF' ?>
                        </span>
                        <span class="text-[11px] text-ink-400 font-medium">Podés seleccionar 1 o varios archivos a la vez</span>
                    </label>

                    <template x-if="selectedCount > 0">
                        <div class="mt-4 p-3 bg-white dark:bg-ink-800 rounded-xl border border-brand-200 dark:border-brand-800 text-left shadow-xs">
                            <span class="text-xs font-bold text-brand-700 dark:text-brand-300 block mb-1" x-text="selectedCount + ' archivo(s) listo(s) para enviar:'"></span>
                            <ul class="text-xs text-ink-600 dark:text-ink-300 list-disc list-inside space-y-0.5">
                                <template x-for="name in fileNames" :key="name">
                                    <li x-text="name"></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>

            <button type="submit" :disabled="selectedCount === 0" class="btn-primary w-full py-3 text-sm font-extrabold flex items-center justify-center gap-2 shadow-md disabled:opacity-50 disabled:cursor-not-allowed text-white">
                <?= icon('upload-cloud', 'w-5 h-5 text-white') ?>
                <span><?= $alreadyCompleted ? 'Enviar archivo adicional a la PC' : 'Enviar a la PC' ?></span>
            </button>
        </form>

        <div class="mt-6 text-center pt-4 border-t border-ink-100 dark:border-ink-800">
            <span class="text-[11px] text-ink-400">Sistema de Recepción Escolar — AppColegios</span>
        </div>

    </div>

</body>
</html>
