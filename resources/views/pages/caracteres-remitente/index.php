<?php
$pageTitle = 'Administración de Carácter de Remitente';
global $container;
$view = $container->get(\App\Support\View::class);

$caracteresMap = [];
foreach ($caracteres as $c) {
    $caracteresMap[$c->id] = $c->toArray();
}
?>

<?= $view->partial('page-header', [
    'title' => 'Carácter de Remitente',
    'subtitle' => 'Clasificación jurídica y contractual de remitentes externos',
    'breadcrumbs' => [
        ['label' => 'Administración'],
        ['label' => 'Carácter de Remitente']
    ]
]) ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data='{
    caracteres: <?= json_encode($caracteresMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    editingCar: null,
    nombre: "",
    orden: 0,
    activo: true,
    edit(id) {
        const c = this.caracteres[id];
        if (!c) return;
        this.editingCar = c;
        this.nombre = c.nombre;
        this.orden = c.orden;
        this.activo = !!c.activo;
    },
    resetForm() {
        this.editingCar = null;
        this.nombre = "";
        this.orden = 0;
        this.activo = true;
    }
}'>

    <!-- Lista de Caracteres -->
    <div class="lg:col-span-2 card p-0 overflow-hidden shadow-card">
        <div class="p-4 border-b border-ink-100 dark:border-ink-800 flex items-center justify-between">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Carácteres Habilitados</h2>
            <span class="text-xs text-ink-500 font-semibold"><?= count($caracteres) ?> carácteres en total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-100/70 dark:bg-ink-800/60 text-ink-600 dark:text-ink-400 text-[11px] font-bold uppercase tracking-wider border-b border-ink-200 dark:border-ink-800">
                    <tr>
                        <th scope="col" class="py-3 px-4">Orden</th>
                        <th scope="col" class="py-3 px-4">Carácter de Remitente</th>
                        <th scope="col" class="py-3 px-4">Estado</th>
                        <th scope="col" class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60 bg-white dark:bg-ink-900">
                    <?php foreach ($caracteres as $c): ?>
                        <tr class="hover:bg-ink-50 dark:hover:bg-ink-800/30 transition-colors <?= !$c->activo ? 'opacity-50' : '' ?>">
                            <td class="py-3 px-4 text-xs font-bold text-ink-500">
                                <?= $c->orden ?>
                            </td>
                            <td class="py-3 px-4 font-bold text-ink-900 dark:text-white">
                                <?= e($c->nombre) ?>
                            </td>
                            <td class="py-3 px-4">
                                <?= $c->activo ? '<span class="pill pill-resuelto">Activo</span>' : '<span class="pill pill-cerrado">Inactivo</span>' ?>
                            </td>
                            <td class="py-3 px-4 text-right space-x-1.5 whitespace-nowrap">
                                <button type="button" 
                                        @click="edit(<?= $c->id ?>)" 
                                        class="inline-flex items-center justify-center p-1.5 rounded-lg text-brand-600 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/40 dark:text-brand-300 transition-colors shadow-xs" title="Editar">
                                    <?= icon('edit-3', 'w-4 h-4 text-brand-600') ?>
                                </button>
                                <?php if ($c->activo): ?>
                                    <form action="/caracteres-remitente/<?= $c->id ?>/desactivar" method="POST" class="inline" onsubmit="return confirm('¿Dar de baja este carácter?')">
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
                x-text="editingCar ? 'Editar Carácter' : 'Nuevo Carácter'">Nuevo Carácter</h2>
            <button type="button" x-show="editingCar" @click="resetForm" class="text-xs text-brand-600 font-bold hover:underline">
                + Crear nuevo
            </button>
        </div>

        <form :action="editingCar ? '/caracteres-remitente/' + editingCar.id + '/actualizar' : '/caracteres-remitente'" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="nombre" class="form-label">Nombre del Carácter <span class="text-danger-500">*</span></label>
                <input type="text" id="nombre" name="nombre" x-model="nombre" required class="form-input" placeholder="Ej: Abogado / Estudio Jurídico">
            </div>

            <div>
                <label for="orden" class="form-label">Orden de Visualización</label>
                <input type="number" id="orden" name="orden" x-model="orden" class="form-input" min="0" max="99">
            </div>

            <div x-show="editingCar">
                <label class="flex items-center gap-2 text-xs font-semibold text-ink-700 dark:text-ink-300 cursor-pointer">
                    <input type="checkbox" name="activo" value="1" x-model="activo" class="rounded border-ink-300 text-brand-600">
                    <span>Carácter activo</span>
                </label>
            </div>

            <div class="pt-3 border-t border-ink-100 dark:border-ink-800 flex justify-end gap-2">
                <button type="button" x-show="editingCar" @click="resetForm" class="btn-ghost text-xs font-semibold">Cancelar</button>
                <button type="submit" class="btn-primary text-xs font-bold text-white shadow-xs">
                    <span x-text="editingCar ? 'Guardar Cambios' : 'Crear Carácter'">Crear Carácter</span>
                </button>
            </div>
        </form>
    </div>

</div>
