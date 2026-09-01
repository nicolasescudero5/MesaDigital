<?php
$pageTitle = $documento->codigo . ' — Ficha de Documento';
global $container;
$view = $container->get(\App\Support\View::class);

$canManage = $user->isAdministrador() || $user->isResponsable();
?>

<?= $view->partial('page-header', [
    'title' => $documento->codigo,
    'subtitle' => 'Registrado el ' . fmt_datetime($documento->creado_el) . ' por ' . e($documento->creador_nombre),
    'breadcrumbs' => [
        ['label' => 'Bandeja', 'url' => '/documentos'],
        ['label' => $documento->codigo]
    ],
    'actions' => render_status_pill($documento->estado) . (!$documento->activo ? ' <span class="pill pill-danger">Anulado</span>' : '')
]) ?>

<div x-data="{
    modalTomar: false,
    modalReclasificar: false,
    modalPlazo: false,
    modalResolver: false,
    modalCerrar: false,
    modalAnular: false,
    anularConfirmation: '',
    modalAdjunto: false
}">

    <!-- Barra Superior de Acciones de Flujo de Estados (§ 4.3 y § 7.7) -->
    <?php if ($documento->activo && $canManage): ?>
        <div class="card p-4 mb-6 flex flex-wrap items-center justify-between gap-3 bg-brand-50/60 dark:bg-brand-950/20 border-brand-200 dark:border-brand-900/60">
            <div class="flex items-center gap-2 text-xs font-bold text-ink-800 dark:text-ink-200">
                <span class="text-brand-600"><?= icon('shuffle', 'w-4 h-4') ?></span>
                <span>Acciones de Gestión:</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Acción: Tomar documento (Recibido -> En curso) -->
                <?php if ($documento->estado === 'Recibido'): ?>
                    <form action="/documentos/<?= $documento->id ?>/tomar" method="POST" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-primary text-xs h-9 inline-flex items-center gap-1.5 shadow-xs">
                            <?= icon('play', 'w-3.5 h-3.5') ?>
                            <span>Tomar Documento (En curso)</span>
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Acción: Reclasificar Categoría -->
                <?php if (in_array($documento->estado, ['Recibido', 'En curso'], true)): ?>
                    <button type="button" @click="modalReclasificar = true" class="btn-neutral text-xs h-9 inline-flex items-center gap-1.5 shadow-xs">
                        <?= icon('shuffle', 'w-3.5 h-3.5') ?>
                        <span>Reclasificar</span>
                    </button>
                <?php endif; ?>

                <!-- Acción: Editar Plazo Legal -->
                <?php if (in_array($documento->estado, ['Recibido', 'En curso'], true)): ?>
                    <button type="button" @click="modalPlazo = true" class="btn-neutral text-xs h-9 inline-flex items-center gap-1.5 shadow-xs">
                        <?= icon('calendar', 'w-3.5 h-3.5') ?>
                        <span>Modificar Plazo</span>
                    </button>
                <?php endif; ?>

                <!-- Acción: Resolver documento (En curso -> Resuelto) -->
                <?php if (in_array($documento->estado, ['Recibido', 'En curso'], true)): ?>
                    <button type="button" @click="modalResolver = true" class="btn-success text-xs h-9 inline-flex items-center gap-1.5 shadow-xs">
                        <?= icon('check-circle', 'w-3.5 h-3.5') ?>
                        <span>Marcar como Resuelto</span>
                    </button>
                <?php endif; ?>

                <!-- Acción: Archivo definitivo (Resuelto -> Cerrado) -->
                <?php if ($documento->estado === 'Resuelto'): ?>
                    <button type="button" @click="modalCerrar = true" class="btn-dark text-xs h-9 inline-flex items-center gap-1.5 shadow-xs">
                        <?= icon('archive', 'w-3.5 h-3.5') ?>
                        <span>Archivar (Cerrado)</span>
                    </button>
                <?php endif; ?>

                <!-- Acción: Anular (Solo Administrador § 4.3) -->
                <?php if ($user->isAdministrador()): ?>
                    <button type="button" @click="modalAnular = true" class="btn-danger text-xs h-9 inline-flex items-center gap-1.5 shadow-xs">
                        <?= icon('trash-2', 'w-3.5 h-3.5') ?>
                        <span>Anular</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cuadrícula Principal (2 Columnas) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Columna Izquierda: Información, Adjuntos y Comentarios -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Ficha de Datos Principales -->
            <div class="card p-6">
                <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
                    <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Detalle de Correspondencia</h2>
                    <span class="text-xs text-ink-400 font-mono">ID #<?= $documento->id ?></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-6">
                    <div>
                        <span class="form-label">Sede</span>
                        <div class="flex items-center gap-2 font-bold text-ink-900 dark:text-white">
                            <?php if ($documento->sede_color): ?>
                                <span class="w-3 h-3 rounded-full inline-block shadow-xs" style="background-color: <?= e($documento->sede_color) ?>"></span>
                            <?php endif; ?>
                            <span><?= e($documento->sede_nombre) ?></span>
                        </div>
                    </div>

                    <div>
                        <span class="form-label">Tipo de Documento</span>
                        <div class="font-bold text-ink-900 dark:text-white"><?= e($documento->tipo_documento_nombre) ?></div>
                    </div>

                    <div>
                        <span class="form-label">Remitente</span>
                        <div class="font-bold text-ink-900 dark:text-white"><?= e($documento->remitente) ?></div>
                        <?php if ($documento->caracter_remitente_nombre): ?>
                            <div class="text-xs text-ink-500 mt-0.5 font-medium">Carácter: <?= e($documento->caracter_remitente_nombre) ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <span class="form-label">Categoría Asignada</span>
                        <div class="font-bold text-brand-600 dark:text-brand-400"><?= e($documento->categoria_nombre) ?></div>
                    </div>

                    <div>
                        <span class="form-label">Fecha de Recepción</span>
                        <div class="text-ink-800 dark:text-ink-200 font-medium"><?= fmt_date($documento->fecha_recepcion) ?></div>
                    </div>

                    <div>
                        <span class="form-label">Plazo Legal</span>
                        <?php if ($documento->plazo_legal): ?>
                            <div class="font-bold <?= $documento->isVencido() ? 'text-danger-600' : 'text-ink-900 dark:text-white' ?>">
                                <?= fmt_date($documento->plazo_legal) ?>
                                <?php if ($documento->isVencido()): ?>
                                    <span class="pill pill-danger text-[10px] ml-1">Vencido</span>
                                <?php else: ?>
                                    <span class="text-xs text-ink-500 font-normal">(Faltan <?= $documento->diasParaVencimiento() ?> días)</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-ink-400">Sin plazo establecido</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-4 border-t border-ink-100 dark:border-ink-800">
                    <span class="form-label">Asunto</span>
                    <h3 class="font-bold text-base text-ink-900 dark:text-white mb-2"><?= e($documento->asunto) ?></h3>

                    <span class="form-label mt-3">Descripción / Contenido</span>
                    <div class="p-4 rounded-lg bg-ink-50 dark:bg-ink-800/40 text-ink-800 dark:text-ink-200 text-sm whitespace-pre-line leading-relaxed border border-ink-200 dark:border-ink-700">
                        <?= e($documento->descripcion) ?>
                    </div>
                </div>

                <!-- Constancia de Cierre si está Resuelto/Cerrado -->
                <?php if (!empty($documento->constancia_cierre)): ?>
                    <div class="mt-6 p-4 rounded-xl border border-success-200 dark:border-success-800 bg-success-50/80 dark:bg-success-950/20">
                        <div class="flex items-center gap-2 font-bold text-success-900 dark:text-success-300 text-xs uppercase tracking-wider mb-1">
                            <span class="text-success-600"><?= icon('check-circle', 'w-4 h-4') ?></span>
                            <span>Constancia de Cierre / Resolución</span>
                        </div>
                        <p class="text-sm text-ink-900 dark:text-ink-100 mt-2 whitespace-pre-line leading-relaxed font-medium">
                            <?= e($documento->constancia_cierre) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Motivo de anulación si está anulado -->
                <?php if (!$documento->activo && !empty($documento->motivo_anulacion)): ?>
                    <div class="mt-6 p-4 rounded-xl border border-danger-200 dark:border-danger-800 bg-danger-50/80 dark:bg-danger-950/20">
                        <div class="flex items-center gap-2 font-bold text-danger-900 dark:text-danger-300 text-xs uppercase tracking-wider mb-1">
                            <span class="text-danger-600"><?= icon('trash-2', 'w-4 h-4') ?></span>
                            <span>Documento Anulado por Administrador</span>
                        </div>
                        <p class="text-sm text-danger-900 dark:text-danger-200 mt-2 font-medium">
                            <?= e($documento->motivo_anulacion) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sección de Adjuntos y Fotos (§ 4.5 y § 6.5) -->
            <div class="card p-6">
                <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
                    <div class="flex items-center gap-2">
                        <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Archivos Adjuntos</h2>
                        <span class="pill pill-recibido text-xs py-0.5 font-bold"><?= count($adjuntos) ?></span>
                    </div>

                    <?php if ($documento->activo && ($canManage || $user->isRecepcion())): ?>
                        <button type="button" @click="modalAdjunto = true" class="btn-neutral text-xs h-8 inline-flex items-center gap-1.5 shadow-xs font-semibold">
                            <?= icon('paperclip', 'w-3.5 h-3.5') ?>
                            <span>Agregar Adjunto</span>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($adjuntos as $adjunto): ?>
                        <div class="p-3 rounded-xl border border-ink-200 dark:border-ink-800 bg-white dark:bg-ink-900 flex flex-col justify-between shadow-xs">
                            <div>
                                <?php if ($adjunto->isImage()): ?>
                                    <div class="h-32 mb-2 rounded-lg bg-ink-100 dark:bg-ink-800 overflow-hidden flex items-center justify-center border border-ink-200 dark:border-ink-700">
                                        <img src="/documentos/<?= $documento->id ?>/adjuntos/<?= $adjunto->id ?>/descargar" 
                                             alt="<?= e($adjunto->nombre_original) ?>" 
                                             class="h-full w-full object-cover cursor-pointer"
                                             onclick="window.open('/documentos/<?= $documento->id ?>/adjuntos/<?= $adjunto->id ?>/descargar', '_blank')">
                                    </div>
                                <?php else: ?>
                                    <div class="h-32 mb-2 rounded-lg bg-ink-100 dark:bg-ink-800 flex flex-col items-center justify-center text-ink-500 border border-ink-200 dark:border-ink-700">
                                        <?= icon('file-text', 'w-10 h-10 mb-1 text-ink-400') ?>
                                        <span class="text-xs uppercase font-bold"><?= $adjunto->isPdf() ? 'Documento PDF' : 'Archivo' ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="font-bold text-xs text-ink-900 dark:text-white truncate" title="<?= e($adjunto->nombre_original) ?>">
                                    <?= e($adjunto->nombre_original) ?>
                                </div>
                                <div class="text-[11px] text-ink-500 mt-0.5">
                                    <?= fmt_num($adjunto->tamano_bytes / 1024, 1) ?> KB · Subido por <?= e($adjunto->subido_por_nombre) ?>
                                </div>
                            </div>

                            <div class="mt-3 pt-2 border-t border-ink-100 dark:border-ink-800 flex justify-end">
                                <a href="/documentos/<?= $documento->id ?>/adjuntos/<?= $adjunto->id ?>/descargar" target="_blank" class="btn-neutral text-xs px-2.5 py-1 h-auto inline-flex items-center gap-1 font-semibold shadow-xs">
                                    <?= icon('download', 'w-3.5 h-3.5') ?>
                                    <span>Ver / Descargar</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Hilo de Comentarios Internos -->
            <div class="card p-6">
                <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
                    <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Comentarios y Seguimiento Interno</h2>
                    <span class="text-xs text-ink-400 font-semibold"><?= count($comentarios) ?> comentarios</span>
                </div>

                <div class="space-y-3 mb-6">
                    <?php if (empty($comentarios)): ?>
                        <p class="text-xs text-ink-400 py-2">No hay comentarios cargados en este documento.</p>
                    <?php else: ?>
                        <?php foreach ($comentarios as $c): ?>
                            <div class="p-3.5 rounded-xl bg-ink-50 dark:bg-ink-800/40 border border-ink-200 dark:border-ink-800">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-ink-900 dark:text-white"><?= e($c->usuario_nombre) ?></span>
                                        <span class="pill pill-cerrado text-[10px] py-0 px-1.5"><?= str_replace('_', ' ', $c->usuario_rol ?? '') ?></span>
                                    </div>
                                    <span class="text-[11px] text-ink-500"><?= fmt_datetime($c->creado_el) ?></span>
                                </div>
                                <p class="text-xs text-ink-800 dark:text-ink-200 leading-relaxed whitespace-pre-line"><?= e($c->comentario) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Formulario de nuevo comentario -->
                <?php if ($documento->activo): ?>
                    <form action="/documentos/<?= $documento->id ?>/comentarios" method="POST" class="pt-4 border-t border-ink-100 dark:border-ink-800 space-y-3">
                        <?= csrf_field() ?>
                        <div>
                            <label for="comentario" class="form-label">Agregar Comentario de Seguimiento</label>
                            <textarea id="comentario" name="comentario" required rows="2" class="form-textarea" placeholder="Escribí un comentario interno sobre el estado o gestión de este documento..."></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="btn-neutral text-xs inline-flex items-center gap-1.5 font-semibold shadow-xs">
                                <?= icon('message-square', 'w-3.5 h-3.5') ?>
                                <span>Publicar Comentario</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

        </div>

        <!-- Columna Derecha: Línea de Tiempo del Historial Inmutable (§ 5.2) -->
        <div class="lg:col-span-1 space-y-6">
            <div class="card p-6">
                <div class="flex items-center gap-2 pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
                    <span class="text-ink-500"><?= icon('history', 'w-4 h-4') ?></span>
                    <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Historial de Auditoría</h2>
                </div>

                <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-ink-200 dark:before:bg-ink-800">
                    <?php foreach ($historial as $h): ?>
                        <div class="relative">
                            <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-brand-600 border-2 border-white dark:border-ink-900 flex items-center justify-center"></div>

                            <div class="text-xs font-bold text-ink-900 dark:text-white">
                                <?= str_replace('_', ' ', $h->accion) ?>
                            </div>
                            <div class="text-[11px] text-ink-500 mt-0.5">
                                <?= fmt_datetime($h->fecha_hora) ?> · <?= e($h->usuario_nombre ?? 'Sistema (Automático)') ?>
                            </div>
                            <?php if ($h->detalle): ?>
                                <div class="text-xs text-ink-700 dark:text-ink-300 mt-1 leading-normal font-medium">
                                    <?= e($h->detalle) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- MODALES -->
    <!-- Modal Reclasificar -->
    <div x-show="modalReclasificar" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal" @click.away="modalReclasificar = false">
            <h3 class="text-lg font-bold text-ink-900 dark:text-white mb-4">Reclasificar Documento</h3>
            <form action="/documentos/<?= $documento->id ?>/reclasificar" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Nueva Categoría Destino <span class="text-danger-500">*</span></label>
                    <select name="nueva_categoria_id" required class="form-select">
                        <?php foreach ($categorias as $cat): ?>
                            <?php if ($cat->id !== $documento->categoria_id): ?>
                                <option value="<?= $cat->id ?>"><?= e($cat->nombre) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Motivo de Reclasificación (Mínimo 10 caracteres) <span class="text-danger-500">*</span></label>
                    <textarea name="motivo" required minlength="10" rows="3" class="form-textarea" placeholder="Indicar el motivo por el cual corresponde derivar a otra categoría..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-ink-100 dark:border-ink-800">
                    <button type="button" @click="modalReclasificar = false" class="btn-ghost text-xs">Cancelar</button>
                    <button type="submit" class="btn-primary text-xs">Reclasificar y Notificar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Modificar Plazo Legal -->
    <div x-show="modalPlazo" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal" @click.away="modalPlazo = false">
            <h3 class="text-lg font-bold text-ink-900 dark:text-white mb-4">Modificar Plazo Legal</h3>
            <form action="/documentos/<?= $documento->id ?>/plazo-legal" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Fecha de Vencimiento / Plazo</label>
                    <input type="date" name="plazo_legal" value="<?= e($documento->plazo_legal) ?>" class="form-input">
                    <p class="text-xs text-ink-400 mt-1">Dejar vacío para remover el plazo.</p>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-ink-100 dark:border-ink-800">
                    <button type="button" @click="modalPlazo = false" class="btn-ghost text-xs">Cancelar</button>
                    <button type="submit" class="btn-primary text-xs">Guardar Plazo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Resolver -->
    <div x-show="modalResolver" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal" @click.away="modalResolver = false">
            <h3 class="text-lg font-bold text-ink-900 dark:text-white mb-4">Marcar Documento como Resuelto</h3>
            <form action="/documentos/<?= $documento->id ?>/resolver" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Constancia de Cierre / Respuesta (Mínimo 10 caracteres) <span class="text-danger-500">*</span></label>
                    <textarea name="constancia_cierre" required minlength="10" rows="4" class="form-textarea" placeholder="Describir las acciones tomadas, número de respuesta legal o constancia de entrega final..."></textarea>
                    <p class="text-xs text-ink-400 mt-1">Esta constancia se enviará por correo al usuario de Recepción que cargó el documento.</p>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-ink-100 dark:border-ink-800">
                    <button type="button" @click="modalResolver = false" class="btn-ghost text-xs">Cancelar</button>
                    <button type="submit" class="btn-success text-xs">Confirmar Resolución</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Cerrar -->
    <div x-show="modalCerrar" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal" @click.away="modalCerrar = false">
            <h3 class="text-lg font-bold text-ink-900 dark:text-white mb-2">Archivar Documento</h3>
            <p class="text-xs text-ink-500 mb-4">¿Confirmás el archivo definitivo de este documento? El estado pasará a <strong>Cerrado</strong>.</p>
            <form action="/documentos/<?= $documento->id ?>/cerrar" method="POST" class="flex justify-end gap-2">
                <?= csrf_field() ?>
                <button type="button" @click="modalCerrar = false" class="btn-ghost text-xs">Cancelar</button>
                <button type="submit" class="btn-dark text-xs">Archivar Definitivamente</button>
            </form>
        </div>
    </div>

    <!-- Modal Anular -->
    <div x-show="modalAnular" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal border-danger-300 dark:border-danger-800" @click.away="modalAnular = false">
            <div class="flex items-center gap-2 text-danger-600 mb-3">
                <?= icon('alert-octagon', 'w-6 h-6') ?>
                <h3 class="text-lg font-bold font-display">Confirmar Anulación Destructiva</h3>
            </div>
            <p class="text-xs text-ink-600 dark:text-ink-300 mb-4">Esta acción anulará lógicamente el documento del sistema. Por seguridad, escribí la palabra <strong class="font-mono text-danger-600">ANULAR</strong> para habilitar el botón.</p>
            
            <form action="/documentos/<?= $documento->id ?>/anular" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Motivo de Anulación <span class="text-danger-500">*</span></label>
                    <textarea name="motivo_anulacion" required rows="2" class="form-textarea" placeholder="Indicar el motivo obligatorio de anulación..."></textarea>
                </div>
                <div>
                    <label class="form-label">Escribí ANULAR</label>
                    <input type="text" x-model="anularConfirmation" class="form-input" placeholder="ANULAR">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-ink-100 dark:border-ink-800">
                    <button type="button" @click="modalAnular = false" class="btn-ghost text-xs">Cancelar</button>
                    <button type="submit" :disabled="anularConfirmation !== 'ANULAR'" class="btn-danger text-xs disabled:opacity-40 disabled:cursor-not-allowed">
                        Confirmar Anulación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Agregar Adjunto -->
    <div x-show="modalAdjunto" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal" @click.away="modalAdjunto = false">
            <h3 class="text-lg font-bold text-ink-900 dark:text-white mb-4">Agregar Adjunto Adicional</h3>
            <form action="/documentos/<?= $documento->id ?>/adjuntos" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Archivo (PDF, JPG, PNG, WebP) ≤ 10 MB <span class="text-danger-500">*</span></label>
                    <input type="file" name="nuevo_adjunto" required accept="image/jpeg,image/png,image/webp,application/pdf" class="form-input py-1.5 text-xs">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-ink-100 dark:border-ink-800">
                    <button type="button" @click="modalAdjunto = false" class="btn-ghost text-xs">Cancelar</button>
                    <button type="submit" class="btn-primary text-xs">Subir y Registrar</button>
                </div>
            </form>
        </div>
    </div>

</div>
