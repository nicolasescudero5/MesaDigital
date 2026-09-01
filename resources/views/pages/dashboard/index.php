<?php
$pageTitle = 'Dashboard';
global $container;
$view = $container->get(\App\Support\View::class);
?>

<?= $view->partial('page-header', [
    'title' => 'Dashboard General',
    'subtitle' => 'Monitoreo de correspondencia, trámites y documentación de la red',
    'actions' => '<a href="/documentos/nuevo" class="btn-primary inline-flex items-center gap-1.5">' . icon('plus-circle', 'w-4 h-4 mr-1 text-white') . '<span class="text-white">Nuevo Documento</span></a>'
]) ?>

<!-- Grid de KPIs Principales (§ 7.7 Patrón P1) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?= $view->partial('kpi-card', [
        'label' => 'Documentos Recibidos',
        'value' => $kpis['recibidos'] ?? 0,
        'subtext' => 'Pendientes de toma',
        'icon' => 'inbox',
        'iconBg' => 'bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400',
        'colorClass' => 'text-brand-600 dark:text-brand-400'
    ]) ?>

    <?= $view->partial('kpi-card', [
        'label' => 'En Gestión (En curso)',
        'value' => $kpis['en_curso'] ?? 0,
        'subtext' => 'Tomados por responsables',
        'icon' => 'clock',
        'iconBg' => 'bg-warning-50 text-warning-600 dark:bg-warning-950/40 dark:text-warning-400',
        'colorClass' => 'text-warning-600 dark:text-warning-400'
    ]) ?>

    <?= $view->partial('kpi-card', [
        'label' => 'Resueltos',
        'value' => $kpis['resueltos'] ?? 0,
        'subtext' => 'Con constancia de cierre',
        'icon' => 'check-circle-2',
        'iconBg' => 'bg-success-50 text-success-600 dark:bg-success-950/40 dark:text-success-400',
        'colorClass' => 'text-success-600 dark:text-success-400'
    ]) ?>

    <?= $view->partial('kpi-card', [
        'label' => 'Vencidos / Por Vencer',
        'value' => ($kpis['vencidos'] ?? 0) + ($kpis['por_vencer'] ?? 0),
        'subtext' => ($kpis['vencidos'] ?? 0) . ' vencidos · ' . ($kpis['por_vencer'] ?? 0) . ' por vencer (≤3d)',
        'icon' => 'alert-triangle',
        'iconBg' => 'bg-danger-50 text-danger-600 dark:bg-danger-950/40 dark:text-danger-400',
        'colorClass' => 'text-danger-600 dark:text-danger-400'
    ]) ?>
</div>

<!-- Grilla de Desgloses y Documentos Recientes -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Desglose por Categoría -->
    <div class="card lg:col-span-1 flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-ink-100 dark:border-ink-800 mb-4">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Por Categoría</h2>
            <a href="/documentos" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Ver todas</a>
        </div>
        <div class="space-y-3 overflow-y-auto max-h-[360px] pr-1">
            <?php foreach ($desgloseCategorias as $cat): ?>
                <?php if ($cat['total_documentos'] > 0): ?>
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-ink-50 dark:bg-ink-800/40 text-xs">
                        <div class="font-medium text-ink-800 dark:text-ink-200 truncate mr-2">
                            <?= e($cat['nombre']) ?>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="pill pill-recibido py-0.5 px-1.5 text-[10px]" title="Recibidos"><?= $cat['recibidos'] ?></span>
                            <span class="pill pill-en-curso py-0.5 px-1.5 text-[10px]" title="En curso"><?= $cat['en_curso'] ?></span>
                            <span class="pill pill-resuelto py-0.5 px-1.5 text-[10px]" title="Resueltos"><?= $cat['resueltos'] ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Desglose por Sede -->
    <div class="card lg:col-span-2 flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-ink-100 dark:border-ink-800 mb-4">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Distribución por Sede</h2>
            <span class="text-xs text-ink-400 font-semibold">Total Sedes: <?= count($desgloseSedes) ?></span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 overflow-y-auto max-h-[360px] pr-1">
            <?php foreach ($desgloseSedes as $sede): ?>
                <div class="p-4 rounded-xl border border-ink-200 dark:border-ink-800 bg-white dark:bg-ink-900 flex flex-col justify-between shadow-xs">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-3.5 h-3.5 rounded-full shadow-xs" style="background-color: <?= e($sede['color_primario']) ?>"></div>
                        <h3 class="font-bold text-sm text-ink-900 dark:text-white truncate"><?= e($sede['nombre']) ?></h3>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center text-xs pt-2 border-t border-ink-100 dark:border-ink-800">
                        <div>
                            <div class="text-ink-500 text-[10px] uppercase font-bold">Recibidos</div>
                            <div class="font-bold text-brand-600 dark:text-brand-400 mt-0.5"><?= $sede['recibidos'] ?></div>
                        </div>
                        <div>
                            <div class="text-ink-500 text-[10px] uppercase font-bold">En curso</div>
                            <div class="font-bold text-warning-600 dark:text-warning-400 mt-0.5"><?= $sede['en_curso'] ?></div>
                        </div>
                        <div>
                            <div class="text-ink-500 text-[10px] uppercase font-bold">Resueltos</div>
                            <div class="font-bold text-success-600 dark:text-success-400 mt-0.5"><?= $sede['resueltos'] ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Documentos Recientes -->
<div class="card mt-8 p-0 overflow-hidden">
    <div class="p-4 border-b border-ink-100 dark:border-ink-800 flex items-center justify-between">
        <div>
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Últimos Documentos Registrados</h2>
            <p class="text-xs text-ink-500 mt-0.5">Accesibles según tu alcance de usuario</p>
        </div>
        <a href="/documentos" class="btn-neutral text-xs font-semibold">
            <span>Ver bandeja completa</span>
            <?= icon('arrow-right', 'w-3.5 h-3.5 ml-1') ?>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-ink-100/70 dark:bg-ink-800/60 text-ink-600 dark:text-ink-400 text-[11px] font-bold uppercase tracking-wider border-b border-ink-200 dark:border-ink-800">
                <tr>
                    <th scope="col" class="py-3 px-4">Código</th>
                    <th scope="col" class="py-3 px-4">Sede / Tipo</th>
                    <th scope="col" class="py-3 px-4">Remitente / Asunto</th>
                    <th scope="col" class="py-3 px-4">Categoría</th>
                    <th scope="col" class="py-3 px-4">Estado</th>
                    <th scope="col" class="py-3 px-4 text-right">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60 bg-white dark:bg-ink-900">
                <?php if (empty($recientes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-ink-400">
                            No hay documentos registrados para tu perfil.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recientes as $doc): ?>
                        <tr class="hover:bg-ink-50 dark:hover:bg-ink-800/30 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-xs text-brand-600 dark:text-brand-400">
                                <a href="/documentos/<?= $doc->id ?>" class="hover:underline">
                                    <?= e($doc->codigo) ?>
                                </a>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-ink-900 dark:text-white"><?= e($doc->sede_nombre) ?></div>
                                <div class="text-xs text-ink-500 dark:text-ink-400"><?= e($doc->tipo_documento_nombre) ?></div>
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-bold text-ink-900 dark:text-ink-100 truncate"><?= e($doc->remitente) ?></div>
                                <div class="text-xs text-ink-500 truncate"><?= e($doc->asunto) ?></div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-xs font-medium text-ink-700 dark:text-ink-300"><?= e($doc->categoria_nombre) ?></span>
                            </td>
                            <td class="py-3.5 px-4">
                                <?= render_status_pill($doc->estado) ?>
                                <?php if ($doc->isVencido()): ?>
                                    <span class="pill pill-danger ml-1 text-[10px]">Vencido</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="/documentos/<?= $doc->id ?>" class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 hover:text-brand-700 p-1 rounded hover:bg-brand-50 transition-colors">
                                    <span>Ver</span>
                                    <?= icon('chevron-right', 'w-3.5 h-3.5') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
