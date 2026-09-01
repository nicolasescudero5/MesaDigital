<?php
$pageTitle = 'Bandeja de Documentos';
global $container;
$view = $container->get(\App\Support\View::class);

$queryString = http_build_query($filters);
$exportParams = $queryString ? '?' . $queryString : '';

$docsMap = [];
foreach ($documentos as $doc) {
    $docsMap[$doc->id] = [
        'id' => $doc->id,
        'codigo' => $doc->codigo,
        'sede' => $doc->sede_nombre,
        'tipo' => $doc->tipo_documento_nombre,
        'remitente' => $doc->remitente,
        'asunto' => $doc->asunto,
        'descripcion' => $doc->descripcion,
        'categoria' => $doc->categoria_nombre,
        'estado' => $doc->estado,
        'fecha' => fmt_date($doc->fecha_recepcion),
        'plazo' => fmt_date($doc->plazo_legal),
        'constancia' => $doc->constancia_cierre,
    ];
}
?>

<div x-data='{ 
    drawerOpen: false, 
    selectedDoc: null,
    docs: <?= json_encode($docsMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    openDrawer(id) {
        this.selectedDoc = this.docs[id] || null;
        if (this.selectedDoc) {
            this.drawerOpen = true;
        }
    }
}'>

    <!-- Cabecera y Acciones de Exportación (§ 7.7 Patrón P2) -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900 dark:text-white font-display tracking-tight">
                Bandeja de Documentos
            </h1>
            <p class="text-sm text-ink-500 mt-1 font-medium">
                <?= fmt_num($total) ?> documentos registrados según tu alcance de usuario
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Botones de Exportación respetando filtros (§ 9 y Criterio N°15) -->
            <div class="inline-flex rounded-lg shadow-xs border border-ink-200 dark:border-ink-800 bg-white dark:bg-ink-900 p-0.5">
                <a href="/reportes/documentos/excel<?= $exportParams ?>" class="btn-ghost text-xs px-2.5 py-1.5 h-auto text-ink-700 hover:text-success-700 dark:text-ink-300 font-semibold inline-flex items-center gap-1.5" title="Exportar a Excel">
                    <?= icon('file-spreadsheet', 'w-4 h-4 text-success-600') ?>
                    <span>Excel</span>
                </a>
                <a href="/reportes/documentos/csv<?= $exportParams ?>" class="btn-ghost text-xs px-2.5 py-1.5 h-auto text-ink-700 hover:text-brand-700 dark:text-ink-300 font-semibold inline-flex items-center gap-1.5" title="Exportar a CSV">
                    <?= icon('file-text', 'w-4 h-4 text-brand-500') ?>
                    <span>CSV</span>
                </a>
                <a href="/reportes/documentos/pdf<?= $exportParams ?>" target="_blank" class="btn-ghost text-xs px-2.5 py-1.5 h-auto text-ink-700 hover:text-ink-900 dark:text-ink-300 font-semibold inline-flex items-center gap-1.5" title="Imprimir / PDF">
                    <?= icon('printer', 'w-4 h-4 text-ink-500') ?>
                    <span>Imprimir</span>
                </a>
            </div>

            <a href="/documentos/nuevo" class="btn-primary inline-flex items-center gap-2 shadow-sm font-semibold text-white">
                <?= icon('plus-circle', 'w-4 h-4 text-white') ?>
                <span>Nuevo Documento</span>
            </a>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="card p-4 mb-6">
        <form action="/documentos" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            
            <!-- Búsqueda libre -->
            <div class="lg:col-span-2">
                <label for="search" class="form-label">Buscar</label>
                <div class="relative">
                    <input type="text" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" 
                           placeholder="Código, remitente, asunto..." class="form-input pl-9">
                    <span class="absolute left-3 top-3 pointer-events-none">
                        <?= icon('search', 'w-4 h-4 text-ink-400') ?>
                    </span>
                </div>
            </div>

            <!-- Filtro Sede -->
            <?php if ($user->isAdministrador() || $user->isSupervision() || $user->isResponsable()): ?>
                <div>
                    <label for="sede_id" class="form-label">Sede</label>
                    <select id="sede_id" name="sede_id" class="form-select">
                        <option value="">Todas las sedes</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($filters['sede_id'] ?? '') == $s->id ? 'selected' : '' ?>>
                                <?= e($s->nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Filtro Categoría -->
            <div>
                <label for="categoria_id" class="form-label">Categoría</label>
                <select id="categoria_id" name="categoria_id" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c->id ?>" <?= ($filters['categoria_id'] ?? '') == $c->id ? 'selected' : '' ?>>
                            <?= e($c->nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro Estado -->
            <div>
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="Recibido" <?= ($filters['estado'] ?? '') === 'Recibido' ? 'selected' : '' ?>>Recibido</option>
                    <option value="En curso" <?= ($filters['estado'] ?? '') === 'En curso' ? 'selected' : '' ?>>En curso</option>
                    <option value="Resuelto" <?= ($filters['estado'] ?? '') === 'Resuelto' ? 'selected' : '' ?>>Resuelto</option>
                    <option value="Cerrado" <?= ($filters['estado'] ?? '') === 'Cerrado' ? 'selected' : '' ?>>Cerrado</option>
                </select>
            </div>

            <!-- Botones de Acción de Filtro -->
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-neutral w-full justify-center inline-flex items-center gap-1.5 font-semibold">
                    <?= icon('filter', 'w-4 h-4 text-ink-500') ?>
                    <span>Filtrar</span>
                </button>
                <a href="/documentos" class="btn-ghost p-2.5 h-auto text-ink-400 hover:text-ink-700" title="Limpiar filtros">
                    <?= icon('rotate-ccw', 'w-4 h-4 text-ink-400') ?>
                </a>
            </div>

            <!-- Checkbox de solo vencidos o anulados -->
            <div class="sm:col-span-2 lg:col-span-6 flex flex-wrap items-center gap-4 pt-2 border-t border-ink-100 dark:border-ink-800 text-xs">
                <label class="inline-flex items-center gap-2 cursor-pointer text-ink-700 dark:text-ink-300 font-medium">
                    <input type="checkbox" name="solo_vencidos" value="1" <?= !empty($filters['solo_vencidos']) ? 'checked' : '' ?> class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                    <span>Solo documentos con plazo vencido</span>
                </label>

                <?php if ($user->isAdministrador()): ?>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-ink-700 dark:text-ink-300 font-medium">
                        <input type="checkbox" name="incluir_anulados" value="1" <?= !empty($filters['incluir_anulados']) ? 'checked' : '' ?> class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span>Incluir documentos anulados</span>
                    </label>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla Principal de Documentos -->
    <div class="card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-100/70 dark:bg-ink-800/60 text-ink-600 dark:text-ink-400 border-b border-ink-200 dark:border-ink-800 text-[11px] font-bold uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-4">Código</th>
                        <th scope="col" class="py-3.5 px-4">Sede / Tipo</th>
                        <th scope="col" class="py-3.5 px-4">Remitente / Asunto</th>
                        <th scope="col" class="py-3.5 px-4">Categoría</th>
                        <th scope="col" class="py-3.5 px-4">Fecha / Plazo</th>
                        <th scope="col" class="py-3.5 px-4">Estado</th>
                        <th scope="col" class="py-3.5 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60 bg-white dark:bg-ink-900">
                    <?php if (empty($documentos)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-12 text-ink-400">
                                <div class="w-12 h-12 rounded-full bg-ink-100 dark:bg-ink-800 flex items-center justify-center mx-auto mb-3 text-ink-400">
                                    <?= icon('inbox', 'w-6 h-6 text-ink-400') ?>
                                </div>
                                <div class="font-bold text-ink-700 dark:text-ink-200">No se encontraron documentos</div>
                                <div class="text-xs mt-1 text-ink-400">Probá ajustando los filtros de búsqueda.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($documentos as $doc): ?>
                            <tr class="hover:bg-ink-50 dark:hover:bg-ink-800/30 transition-colors <?= !$doc->activo ? 'opacity-60 bg-ink-100/40' : '' ?>">
                                
                                <!-- Código -->
                                <td class="py-3.5 px-4 font-mono font-bold text-xs text-brand-600 dark:text-brand-400">
                                    <a href="/documentos/<?= $doc->id ?>" class="hover:underline">
                                        <?= e($doc->codigo) ?>
                                    </a>
                                </td>

                                <!-- Sede / Tipo -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5 font-bold text-ink-900 dark:text-white">
                                        <?php if ($doc->sede_color): ?>
                                            <span class="w-2.5 h-2.5 rounded-full inline-block shrink-0 shadow-xs" style="background-color: <?= e($doc->sede_color) ?>"></span>
                                        <?php endif; ?>
                                        <span><?= e($doc->sede_nombre) ?></span>
                                    </div>
                                    <div class="text-xs text-ink-500 dark:text-ink-400 mt-0.5"><?= e($doc->tipo_documento_nombre) ?></div>
                                </td>

                                <!-- Remitente / Asunto -->
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="font-bold text-ink-900 dark:text-ink-100 truncate" title="<?= e($doc->remitente) ?>">
                                        <?= e($doc->remitente) ?>
                                    </div>
                                    <div class="text-xs text-ink-500 truncate mt-0.5" title="<?= e($doc->asunto) ?>">
                                        <?= e($doc->asunto) ?>
                                    </div>
                                </td>

                                <!-- Categoría -->
                                <td class="py-3.5 px-4">
                                    <span class="text-xs font-semibold text-ink-700 dark:text-ink-300">
                                        <?= e($doc->categoria_nombre) ?>
                                    </span>
                                </td>

                                <!-- Fecha Recepción / Plazo Legal -->
                                <td class="py-3.5 px-4 text-xs">
                                    <div class="text-ink-800 dark:text-ink-200 font-medium"><?= fmt_date($doc->fecha_recepcion) ?></div>
                                    <?php if ($doc->plazo_legal): ?>
                                        <div class="mt-0.5">
                                            <?php if ($doc->isVencido()): ?>
                                                <span class="text-danger-600 font-bold">Venció <?= fmt_date($doc->plazo_legal) ?></span>
                                            <?php else: ?>
                                                <?php $dias = $doc->diasParaVencimiento(); ?>
                                                <span class="<?= ($dias !== null && $dias <= 3) ? 'text-danger-600 font-bold' : 'text-ink-500' ?>">
                                                    Plazo: <?= fmt_date($doc->plazo_legal) ?> (<?= $dias ?>d)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-ink-400">Sin plazo</div>
                                    <?php endif; ?>
                                </td>

                                <!-- Estado -->
                                <td class="py-3.5 px-4">
                                    <?= render_status_pill($doc->estado) ?>
                                    <?php if (!$doc->activo): ?>
                                        <span class="pill pill-danger ml-1 text-[10px]">Anulado</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1.5">
                                    <button type="button" 
                                            @click="openDrawer(<?= $doc->id ?>)"
                                            class="inline-flex items-center justify-center p-1.5 rounded-lg text-ink-600 bg-ink-100 hover:bg-ink-200 hover:text-ink-900 dark:bg-ink-800 dark:text-ink-300 transition-colors shadow-xs" title="Vista rápida">
                                        <?= icon('eye', 'w-4 h-4 text-ink-600') ?>
                                    </button>
                                    <a href="/documentos/<?= $doc->id ?>" class="btn-neutral text-xs px-2.5 py-1.5 h-auto inline-flex items-center gap-1 font-semibold text-ink-700 shadow-xs">
                                        <span>Ficha</span>
                                        <?= icon('arrow-right', 'w-3.5 h-3.5 text-ink-500') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Barra de Paginación (§ 7.4) -->
        <div class="p-4 border-t border-ink-200 dark:border-ink-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-ink-600 dark:text-ink-400 bg-white dark:bg-ink-900">
            <div>
                Mostrando <strong class="text-ink-900 dark:text-white tabular-nums"><?= min($total, ($page - 1) * $limit + 1) ?>-<?= min($total, $page * $limit) ?></strong> de <strong class="text-ink-900 dark:text-white tabular-nums"><?= fmt_num($total) ?></strong> documentos
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="/documentos?<?= http_build_query(array_merge($filters, ['page' => $page - 1, 'limit' => $limit])) ?>" 
                           class="btn-neutral p-1.5 h-auto text-xs" title="Página anterior">
                            <?= icon('chevron-left', 'w-4 h-4 text-ink-500') ?>
                        </a>
                    <?php endif; ?>

                    <span class="px-2">Página <strong class="text-ink-900 dark:text-white"><?= $page ?></strong> de <strong class="text-ink-900 dark:text-white"><?= $totalPages ?></strong></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="/documentos?<?= http_build_query(array_merge($filters, ['page' => $page + 1, 'limit' => $limit])) ?>" 
                           class="btn-neutral p-1.5 h-auto text-xs" title="Página siguiente">
                            <?= icon('chevron-right', 'w-4 h-4 text-ink-500') ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-1.5">
                    <span>Ver:</span>
                    <?php foreach ([25, 50, 100] as $lim): ?>
                        <a href="/documentos?<?= http_build_query(array_merge($filters, ['page' => 1, 'limit' => $lim])) ?>" 
                           class="px-2 py-1 rounded <?= $limit === $lim ? 'bg-brand-600 text-white font-bold' : 'hover:bg-ink-100 dark:hover:bg-ink-800' ?>">
                            <?= $lim ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Drawer Lateral de Vista Rápida (§ 7.4) -->
    <div x-show="drawerOpen" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 max-w-[480px] w-full bg-white dark:bg-ink-900 border-l border-ink-200 dark:border-ink-800 shadow-float z-50 flex flex-col p-6 overflow-y-auto"
         style="display: none;">
        
        <div class="flex items-center justify-between pb-4 border-b border-ink-100 dark:border-ink-800 mb-6">
            <div>
                <span class="text-xs uppercase font-bold text-brand-600 dark:text-brand-400">Vista Rápida</span>
                <h2 class="text-xl font-bold font-mono text-ink-900 dark:text-white" x-text="selectedDoc ? selectedDoc.codigo : ''"></h2>
            </div>
            <button @click="drawerOpen = false" class="btn-icon">
                <?= icon('x', 'w-5 h-5 text-ink-500') ?>
            </button>
        </div>

        <template x-if="selectedDoc">
            <div class="space-y-4 text-sm">
                <div>
                    <span class="form-label">Remitente</span>
                    <div class="font-semibold text-ink-900 dark:text-white" x-text="selectedDoc.remitente"></div>
                </div>

                <div>
                    <span class="form-label">Asunto</span>
                    <div class="text-ink-800 dark:text-ink-200" x-text="selectedDoc.asunto"></div>
                </div>

                <div>
                    <span class="form-label">Descripción</span>
                    <div class="text-ink-700 dark:text-ink-300 whitespace-pre-line text-xs bg-ink-50 dark:bg-ink-800/40 p-3 rounded-lg border border-ink-200 dark:border-ink-700" x-text="selectedDoc.descripcion"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="form-label">Sede</span>
                        <div class="text-ink-800 dark:text-ink-200 font-medium" x-text="selectedDoc.sede"></div>
                    </div>
                    <div>
                        <span class="form-label">Categoría</span>
                        <div class="text-ink-800 dark:text-ink-200 font-medium" x-text="selectedDoc.categoria"></div>
                    </div>
                    <div>
                        <span class="form-label">Fecha Recepción</span>
                        <div class="text-ink-800 dark:text-ink-200 font-medium" x-text="selectedDoc.fecha"></div>
                    </div>
                    <div>
                        <span class="form-label">Plazo Legal</span>
                        <div class="text-ink-800 dark:text-ink-200 font-medium" x-text="selectedDoc.plazo || 'Sin plazo'"></div>
                    </div>
                </div>

                <template x-if="selectedDoc.constancia">
                    <div>
                        <span class="form-label">Constancia de Cierre</span>
                        <div class="p-3 bg-success-50 dark:bg-success-950/30 text-success-900 dark:text-success-200 border-l-4 border-success-500 rounded text-xs" x-text="selectedDoc.constancia"></div>
                    </div>
                </template>

                <div class="pt-6 border-t border-ink-100 dark:border-ink-800 flex justify-end gap-3">
                    <a :href="'/documentos/' + selectedDoc.id" class="btn-primary w-full justify-center inline-flex items-center gap-2 text-white">
                        <span>Ir a la Ficha Completa</span>
                        <?= icon('arrow-right', 'w-4 h-4 text-white') ?>
                    </a>
                </div>
            </div>
        </template>
    </div>

</div>
