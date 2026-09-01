<?php
$pageTitle = 'Administración de Tipos de Documento';
global $container;
$view = $container->get(\App\Support\View::class);

$tiposMap = [];
foreach ($tipos as $t) {
    $tiposMap[$t->id] = $t->toArray();
}
?>

<?= $view->partial('page-header', [
    'title' => 'Tipos de Documento',
    'subtitle' => 'Catálogo institucional de correspondencia y vinculación automática a categorías',
    'breadcrumbs' => [
        ['label' => 'Administración'],
        ['label' => 'Tipos de Documento']
    ]
]) ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data='{
    tipos: <?= json_encode($tiposMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    editingTipo: null,
    nombre: "",
    categoriaId: "",
    orden: 0,
    activo: true,
    edit(id) {
        const t = this.tipos[id];
        if (!t) return;
        this.editingTipo = t;
        this.nombre = t.nombre;
        this.categoriaId = t.categoria_id || "";
        this.orden = t.orden;
        this.activo = !!t.activo;
    },
    resetForm() {
        this.editingTipo = null;
        this.nombre = "";
        this.categoriaId = "";
        this.orden = 0;
        this.activo = true;
    }
}'>

    <!-- Lista de Tipos de Documento -->
    <div class="lg:col-span-2 card p-0 overflow-hidden shadow-card">
        <div class="p-4 border-b border-ink-100 dark:border-ink-800 flex items-center justify-between">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Tipos Habilitados</h2>
            <span class="text-xs text-ink-500 font-semibold"><?= count($tipos) ?> tipos en total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-100/70 dark:bg-ink-800/60 text-ink-600 dark:text-ink-400 text-[11px] font-bold uppercase tracking-wider border-b border-ink-200 dark:border-ink-800">
                    <tr>
                        <th scope="col" class="py-3 px-4 w-12 text-center">Orden</th>
                        <th scope="col" class="py-3 px-4">Nombre del Tipo</th>
                        <th scope="col" class="py-3 px-4">Categoría Vinculada</th>
                        <th scope="col" class="py-3 px-4">Estado</th>
                        <th scope="col" class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60 bg-white dark:bg-ink-900">
                    <?php foreach ($tipos as $t): ?>
                        <tr class="hover:bg-ink-50 dark:hover:bg-ink-800/30 transition-colors <?= !$t->activo ? 'opacity-50' : '' ?>">
                            <td class="py-3 px-4 text-center font-mono font-bold text-xs text-ink-500">
                                <?= $t->orden ?>
                            </td>
                            <td class="py-3 px-4 font-bold text-ink-900 dark:text-white">
                                <?= e($t->nombre) ?>
                            </td>
                            <td class="py-3 px-4">
                                <?php if ($t->categoria_nombre): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-brand-50 text-brand-700 dark:bg-brand-950/40 dark:text-brand-300 text-xs font-semibold border border-brand-200 dark:border-brand-800">
                                        <?= icon('tag', 'w-3 h-3 text-brand-500') ?>
                                        <span><?= e($t->categoria_nombre) ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-ink-400 italic">Sin categoría</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4">
                                <?= $t->activo ? '<span class="pill pill-resuelto">Activo</span>' : '<span class="pill pill-cerrado">Inactivo</span>' ?>
                            </td>
                            <td class="py-3 px-4 text-right space-x-1.5 whitespace-nowrap">
                                <button type="button" 
                                        @click="edit(<?= $t->id ?>)" 
                                        class="inline-flex items-center justify-center p-1.5 rounded-lg text-brand-600 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/40 dark:text-brand-300 transition-colors shadow-xs" title="Editar">
                                    <?= icon('edit-3', 'w-4 h-4 text-brand-600') ?>
                                </button>
                                <?php if ($t->activo): ?>
                                    <form action="/tipos-documento/<?= $t->id ?>/desactivar" method="POST" class="inline" onsubmit="return confirm('¿Dar de baja este tipo de documento?')">
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

    <!-- Formulario de Alta / Edición -->
    <div class="lg:col-span-1 card p-6 h-fit shadow-card">
        <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white" 
                x-text="editingTipo ? 'Editar Tipo' : 'Nuevo Tipo'">Nuevo Tipo</h2>
            <button type="button" x-show="editingTipo" @click="resetForm" class="text-xs text-brand-600 font-bold hover:underline">
                + Crear nuevo
            </button>
        </div>

        <form :action="editingTipo ? '/tipos-documento/' + editingTipo.id + '/actualizar' : '/tipos-documento'" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="nombre" class="form-label">Nombre del Tipo <span class="text-danger-500">*</span></label>
                <input type="text" id="nombre" name="nombre" x-model="nombre" required class="form-input" placeholder="Ej: Carta Documento">
            </div>

            <div>
                <label for="categoria_id" class="form-label">Categoría Asociada <span class="text-danger-500">*</span></label>
                <select id="categoria_id" name="categoria_id" x-model="categoriaId" required class="form-select">
                    <option value="">Seleccionar categoría...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat->id ?>"><?= e($cat->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-ink-500 mt-1">Al crear un documento, esta categoría y sus responsables se asignarán automáticamente.</p>
            </div>

            <div>
                <label for="orden" class="form-label">Orden de Visualización</label>
                <input type="number" id="orden" name="orden" x-model="orden" class="form-input" min="0" max="99">
            </div>

            <div x-show="editingTipo">
                <label class="flex items-center gap-2 text-xs font-semibold text-ink-700 dark:text-ink-300 cursor-pointer">
                    <input type="checkbox" name="activo" value="1" x-model="activo" class="rounded border-ink-300 text-brand-600">
                    <span>Tipo de documento activo</span>
                </label>
            </div>

            <div class="pt-3 border-t border-ink-100 dark:border-ink-800 flex justify-end gap-2">
                <button type="button" x-show="editingTipo" @click="resetForm" class="btn-ghost text-xs font-semibold">Cancelar</button>
                <button type="submit" class="btn-primary text-xs font-bold text-white shadow-xs">
                    <span x-text="editingTipo ? 'Guardar Cambios' : 'Crear Tipo'">Crear Tipo</span>
                </button>
            </div>
        </form>
    </div>

</div>
