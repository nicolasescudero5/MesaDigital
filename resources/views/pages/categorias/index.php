<?php
$pageTitle = 'Administración de Categorías';
global $container;
$view = $container->get(\App\Support\View::class);

$categoriasMap = [];
foreach ($categorias as $cat) {
    $resps = [];
    foreach ($cat->responsables as $r) {
        $resps[] = [
            'id' => $r->id,
            'email' => $r->email,
            'usuario_nombre' => $r->usuario_nombre,
            'sede_id' => $r->sede_id,
            'sede_nombre' => $r->sede_nombre,
        ];
    }
    $categoriasMap[$cat->id] = [
        'id' => $cat->id,
        'nombre' => $cat->nombre,
        'descripcion' => $cat->descripcion ?? '',
        'orden' => $cat->orden,
        'activo' => (bool)$cat->activo,
        'es_reserva' => (bool)$cat->es_reserva,
        'responsables' => $resps
    ];
}
?>

<div x-data='{
    categorias: <?= json_encode($categoriasMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    modalNew: false,
    modalManage: false,
    selectedCat: null,
    nombre: "",
    descripcion: "",
    orden: 0,
    activo: true,
    openManage(id) {
        const c = this.categorias[id];
        if (!c) return;
        this.selectedCat = c;
        this.nombre = c.nombre;
        this.descripcion = c.descripcion || "";
        this.orden = c.orden;
        this.activo = !!c.activo;
        this.modalManage = true;
    }
}'>

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-ink-500 mb-1">
                <span>Administración</span>
                <span>/</span>
                <span class="text-ink-800 dark:text-ink-200">Categorías y Responsables</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900 dark:text-white font-display tracking-tight">
                Categorías y Responsables
            </h1>
            <p class="text-sm text-ink-500 mt-1 font-medium">
                Clasificación de correspondencia y derivación automática de notificaciones por correo
            </p>
        </div>

        <div>
            <button type="button" @click="modalNew = true" class="btn-primary inline-flex items-center gap-2 shadow-sm font-semibold text-white">
                <?= icon('plus-circle', 'w-4 h-4 text-white') ?>
                <span>Nueva Categoría</span>
            </button>
        </div>
    </div>

    <!-- Callout informativo sobre el flujo de notificaciones -->
    <div class="p-4 rounded-xl border border-brand-200 bg-brand-50/70 dark:bg-brand-950/20 dark:border-brand-900/60 flex items-start gap-3 shadow-xs mb-6">
        <span class="text-brand-600 shrink-0 mt-0.5"><?= icon('info', 'w-5 h-5') ?></span>
        <div class="text-xs text-ink-800 dark:text-ink-200 leading-relaxed font-medium">
            <strong>¿Cómo funciona la notificación automática?</strong> Al registrarse un documento en una categoría, el sistema encola automáticamente un correo electrónico individual para todos los responsables activos asignados. Podés hacer clic en cualquier fila para editar sus propiedades o agregar/quitar responsables en el popup interactivo.
        </div>
    </div>

    <!-- Tabla Principal de Categorías en Filas -->
    <div class="card p-0 overflow-hidden shadow-card">
        <div class="p-4 border-b border-ink-100 dark:border-ink-800 flex items-center justify-between">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Catálogo de Categorías</h2>
            <span class="text-xs text-ink-500 font-semibold"><?= count($categorias) ?> categorías registradas</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-100/70 dark:bg-ink-800/60 text-ink-600 dark:text-ink-400 text-[11px] font-bold uppercase tracking-wider border-b border-ink-200 dark:border-ink-800">
                    <tr>
                        <th scope="col" class="py-3.5 px-4 w-16 text-center">Orden</th>
                        <th scope="col" class="py-3.5 px-4">Categoría / Alcance</th>
                        <th scope="col" class="py-3.5 px-4">Responsables Notificados</th>
                        <th scope="col" class="py-3.5 px-4">Estado</th>
                        <th scope="col" class="py-3.5 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60 bg-white dark:bg-ink-900">
                    <?php foreach ($categorias as $cat): ?>
                        <tr class="hover:bg-brand-50/40 dark:hover:bg-ink-800/30 transition-colors cursor-pointer <?= !$cat->activo ? 'opacity-50' : '' ?>"
                            @click="openManage(<?= $cat->id ?>)">
                            
                            <!-- Orden -->
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-xs text-ink-500">
                                <?= $cat->orden ?>
                            </td>

                            <!-- Nombre y Descripción -->
                            <td class="py-3.5 px-4 max-w-sm">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-ink-900 dark:text-white"><?= e($cat->nombre) ?></span>
                                    <?php if ($cat->es_reserva): ?>
                                        <span class="pill pill-en-curso text-[10px] py-0.5 px-1.5">Reserva</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($cat->descripcion): ?>
                                    <div class="text-xs text-ink-500 mt-0.5 truncate"><?= e($cat->descripcion) ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Responsables Notificados -->
                            <td class="py-3.5 px-4" @click.stop>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <?php if (empty($cat->responsables)): ?>
                                        <button type="button" @click="openManage(<?= $cat->id ?>)" class="text-xs text-danger-600 font-semibold flex items-center gap-1 hover:underline">
                                            <?= icon('alert-triangle', 'w-3.5 h-3.5') ?>
                                            <span>Sin responsables (+ asignar)</span>
                                        </button>
                                    <?php else: ?>
                                        <?php foreach ($cat->responsables as $resp): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-ink-100 dark:bg-ink-800 text-[11px] font-medium text-ink-700 dark:text-ink-200 border border-ink-200 dark:border-ink-700">
                                                <?= icon('mail', 'w-3 h-3 text-ink-400') ?>
                                                <span><?= e($resp->email) ?></span>
                                                <?php if ($resp->sede_nombre): ?>
                                                    <span class="px-1 py-0.2 text-[9px] font-bold rounded bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300"><?= e($resp->sede_nombre) ?></span>
                                                <?php else: ?>
                                                    <span class="px-1 py-0.2 text-[9px] font-semibold rounded bg-ink-200/60 text-ink-600 dark:bg-ink-700 dark:text-ink-300">Global</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <button type="button" @click="openManage(<?= $cat->id ?>)" class="text-[11px] font-bold text-brand-600 hover:text-brand-700 ml-1">
                                            + Administrar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Estado -->
                            <td class="py-3.5 px-4">
                                <?= $cat->activo ? '<span class="pill pill-resuelto">Activo</span>' : '<span class="pill pill-cerrado">Inactivo</span>' ?>
                            </td>

                            <!-- Acciones -->
                            <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap" @click.stop>
                                <button type="button" 
                                        @click="openManage(<?= $cat->id ?>)" 
                                        class="btn-neutral text-xs px-2.5 py-1.5 h-auto inline-flex items-center gap-1 font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/40 dark:text-brand-300 transition-colors shadow-xs" title="Gestionar categoría">
                                    <?= icon('edit-3', 'w-3.5 h-3.5 text-brand-600') ?>
                                    <span>Gestionar</span>
                                </button>

                                <?php if ($cat->activo && !$cat->es_reserva): ?>
                                    <form action="/categorias/<?= $cat->id ?>/desactivar" method="POST" class="inline" onsubmit="return confirm('¿Dar de baja esta categoría?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="inline-flex items-center justify-center p-1.5 rounded-lg text-danger-600 bg-danger-50 hover:bg-danger-100 dark:bg-danger-950/40 dark:text-danger-300 transition-colors shadow-xs" title="Dar de baja">
                                            <?= icon('trash-2', 'w-4 h-4 text-danger-600') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL POPUP: GESTIÓN INTEGRAL DE CATEGORÍA -->
    <div x-show="modalManage" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-xl w-full p-6 shadow-modal max-h-[90vh] overflow-y-auto" @click.away="modalManage = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-5">
                <div>
                    <span class="text-xs uppercase font-bold text-brand-600 dark:text-brand-400">Gestión de Categoría</span>
                    <h3 class="text-lg font-bold text-ink-900 dark:text-white" x-text="selectedCat ? selectedCat.nombre : ''"></h3>
                </div>
                <button type="button" @click="modalManage = false" class="btn-icon">
                    <?= icon('x', 'w-5 h-5 text-ink-500') ?>
                </button>
            </div>

            <!-- Sección 1: Edición de Datos de la Categoría -->
            <form :action="'/categorias/' + (selectedCat ? selectedCat.id : '') + '/actualizar'" method="POST" class="space-y-4 mb-6">
                <?= csrf_field() ?>

                <div class="text-xs font-bold uppercase tracking-wider text-ink-500 mb-2">Datos Principales</div>

                <div>
                    <label class="form-label">Nombre de la Categoría <span class="text-danger-500">*</span></label>
                    <input type="text" name="nombre" x-model="nombre" required class="form-input font-semibold">
                </div>

                <div>
                    <label class="form-label">Descripción / Alcance</label>
                    <textarea name="descripcion" x-model="descripcion" rows="2" class="form-textarea" placeholder="Indicar el tipo de trámites o expedientes correspondientes a esta área..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Orden de Visualización</label>
                        <input type="number" name="orden" x-model="orden" class="form-input" min="0" max="99">
                    </div>
                    <div class="flex items-center pt-6">
                        <label class="flex items-center gap-2 text-xs font-semibold text-ink-700 dark:text-ink-300 cursor-pointer">
                            <input type="checkbox" name="activo" value="1" x-model="activo" class="rounded border-ink-300 text-brand-600">
                            <span>Categoría activa</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="btn-primary text-xs font-bold shadow-xs">
                        Guardar Datos de Categoría
                    </button>
                </div>
            </form>

            <!-- Sección 2: Responsables Asignados -->
            <div class="pt-6 border-t border-ink-200 dark:border-ink-800 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-ink-900 dark:text-white">Responsables Notificados</h4>
                        <p class="text-xs text-ink-500 mt-0.5">Reciben emails automáticos cuando entra correspondencia</p>
                    </div>
                    <span class="pill pill-recibido text-xs font-bold" x-text="(selectedCat && selectedCat.responsables ? selectedCat.responsables.length : 0) + ' asignados'"></span>
                </div>

                <!-- Lista de Responsables Actuales -->
                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <template x-if="!selectedCat || !selectedCat.responsables || selectedCat.responsables.length === 0">
                        <div class="p-3 rounded-lg bg-danger-50 dark:bg-danger-950/30 text-danger-700 dark:text-danger-300 text-xs flex items-center gap-2 border border-danger-200 dark:border-danger-800">
                            <?= icon('alert-triangle', 'w-4 h-4 text-danger-500') ?>
                            <span>Esta categoría no tiene responsables asignados.</span>
                        </div>
                    </template>

                    <template x-for="resp in (selectedCat ? selectedCat.responsables : [])" :key="resp.id || resp.email">
                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-ink-50 dark:bg-ink-800/40 border border-ink-200 dark:border-ink-700 text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-ink-400"><?= icon('mail', 'w-4 h-4 text-ink-400') ?></span>
                                <span class="font-bold text-ink-900 dark:text-white truncate" x-text="resp.email"></span>
                                <span class="text-ink-400 text-[11px]" x-show="resp.usuario_nombre" x-text="'(' + resp.usuario_nombre + ')'"></span>
                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded"
                                      :class="resp.sede_nombre ? 'bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300' : 'bg-ink-200/60 text-ink-600 dark:bg-ink-700 dark:text-ink-300'"
                                      x-text="resp.sede_nombre ? resp.sede_nombre : 'Todas las Sedes'"></span>
                            </div>
                            <form :action="'/categorias/' + selectedCat.id + '/responsables/eliminar'" method="POST" onsubmit="return confirm('¿Remover este responsable?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="responsable_id" :value="resp.id">
                                <input type="hidden" name="email" :value="resp.email">
                                <input type="hidden" name="sede_id" :value="resp.sede_id || ''">
                                <button type="submit" class="text-xs text-danger-600 hover:text-danger-700 font-semibold p-1 rounded hover:bg-danger-50 transition-colors" title="Remover">
                                    <?= icon('trash-2', 'w-3.5 h-3.5 text-danger-500') ?>
                                </button>
                            </form>
                        </div>
                    </template>
                </div>

                <!-- Formulario Agregar Nuevo Responsable -->
                <form :action="'/categorias/' + (selectedCat ? selectedCat.id : '') + '/responsables'" method="POST" class="p-4 rounded-xl bg-ink-50 dark:bg-ink-800/60 border border-ink-200 dark:border-ink-700 space-y-3">
                    <?= csrf_field() ?>
                    <div class="text-xs font-bold text-ink-800 dark:text-white flex items-center gap-1.5">
                        <?= icon('user-plus', 'w-3.5 h-3.5 text-brand-600') ?>
                        <span>Asignar Nuevo Responsable</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="form-label text-[10px]">Usuario del Sistema</label>
                            <select name="usuario_id" class="form-select text-xs">
                                <option value="">O ingresar email...</option>
                                <?php foreach ($usuariosResponsables as $u): ?>
                                    <option value="<?= $u->id ?>"><?= e($u->nombre) ?> (<?= e($u->email) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-[10px]">O Correo Directo</label>
                            <input type="email" name="email" class="form-input text-xs" placeholder="ejemplo@abogados.com">
                        </div>
                        <div>
                            <label class="form-label text-[10px]">Sede de Alcance</label>
                            <select name="sede_id" class="form-select text-xs font-medium">
                                <option value="">Todas las Sedes (Global)</option>
                                <?php foreach ($sedes as $s): ?>
                                    <option value="<?= $s->id ?>"><?= e($s->nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn-neutral text-xs font-bold inline-flex items-center gap-1.5 shadow-xs">
                            <?= icon('plus', 'w-3.5 h-3.5 text-brand-600') ?>
                            <span>+ Asignar Responsable</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="pt-5 border-t border-ink-100 dark:border-ink-800 flex justify-end mt-4">
                <button type="button" @click="modalManage = false" class="btn-ghost text-xs font-semibold">Cerrar Ventana</button>
            </div>
        </div>
    </div>

    <!-- MODAL POPUP: NUEVA CATEGORÍA -->
    <div x-show="modalNew" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal" @click.away="modalNew = false">
            <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
                <h3 class="text-lg font-bold text-ink-900 dark:text-white">Nueva Categoría</h3>
                <button type="button" @click="modalNew = false" class="btn-icon">
                    <?= icon('x', 'w-4 h-4 text-ink-500') ?>
                </button>
            </div>

            <form action="/categorias" method="POST" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label for="new_nombre" class="form-label">Nombre de la Categoría <span class="text-danger-500">*</span></label>
                    <input type="text" id="new_nombre" name="nombre" required class="form-input" placeholder="Ej: Legales — Laboral">
                </div>

                <div>
                    <label for="new_descripcion" class="form-label">Descripción / Alcance</label>
                    <textarea id="new_descripcion" name="descripcion" rows="2" class="form-textarea" placeholder="Ej: Telegramas, reclamos de personal docente y convenios"></textarea>
                </div>

                <div>
                    <label for="new_orden" class="form-label">Orden de Visualización</label>
                    <input type="number" id="new_orden" name="orden" value="0" class="form-input" min="0" max="99">
                </div>

                <div class="pt-3 border-t border-ink-100 dark:border-ink-800 flex justify-end gap-2">
                    <button type="button" @click="modalNew = false" class="btn-ghost text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="btn-primary text-xs font-bold text-white shadow-xs">Crear Categoría</button>
                </div>
            </form>
        </div>
    </div>

</div>
