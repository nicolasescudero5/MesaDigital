<?php
$pageTitle = 'Administración de Sedes';
global $container;
$view = $container->get(\App\Support\View::class);

$sedesMap = [];
foreach ($sedes as $s) {
    $sedesMap[$s->id] = $s->toArray();
}
?>

<?= $view->partial('page-header', [
    'title' => 'Gestión de Sedes',
    'subtitle' => 'Administración de colegios y sedes de la Red Itínere',
    'breadcrumbs' => [
        ['label' => 'Administración'],
        ['label' => 'Sedes']
    ]
]) ?>

<!-- Configuración Dual (Patrón P3 § 7.7) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data='{
    sedes: <?= json_encode($sedesMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    editingSede: null,
    nombre: "",
    color: "#4E47DD",
    activo: true,
    edit(id) {
        const s = this.sedes[id];
        if (!s) return;
        this.editingSede = s;
        this.nombre = s.nombre;
        this.color = s.color_primario;
        this.activo = !!s.activo;
    },
    resetForm() {
        this.editingSede = null;
        this.nombre = "";
        this.color = "#4E47DD";
        this.activo = true;
    }
}'>

    <!-- Lista de Sedes (2 Columnas en escritorio) -->
    <div class="lg:col-span-2 card p-0 overflow-hidden shadow-card">
        <div class="p-4 border-b border-ink-100 dark:border-ink-800 flex items-center justify-between">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Sedes Registradas</h2>
            <span class="text-xs text-ink-500 font-semibold"><?= count($sedes) ?> sedes en total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-100/70 dark:bg-ink-800/60 text-ink-600 dark:text-ink-400 text-[11px] font-bold uppercase tracking-wider border-b border-ink-200 dark:border-ink-800">
                    <tr>
                        <th scope="col" class="py-3 px-4">Color</th>
                        <th scope="col" class="py-3 px-4">Nombre de la Sede</th>
                        <th scope="col" class="py-3 px-4">Estado</th>
                        <th scope="col" class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60 bg-white dark:bg-ink-900">
                    <?php foreach ($sedes as $s): ?>
                        <tr class="hover:bg-ink-50 dark:hover:bg-ink-800/30 transition-colors <?= !$s->activo ? 'opacity-50' : '' ?>">
                            <td class="py-3 px-4">
                                <span class="w-4 h-4 rounded-full inline-block shadow-xs" style="background-color: <?= e($s->color_primario) ?>"></span>
                            </td>
                            <td class="py-3 px-4 font-bold text-ink-900 dark:text-white">
                                <?= e($s->nombre) ?>
                            </td>
                            <td class="py-3 px-4">
                                <?= $s->activo ? '<span class="pill pill-resuelto">Activo</span>' : '<span class="pill pill-cerrado">Inactivo</span>' ?>
                            </td>
                            <td class="py-3 px-4 text-right space-x-1.5 whitespace-nowrap">
                                <button type="button" 
                                        @click="edit(<?= $s->id ?>)" 
                                        class="inline-flex items-center justify-center p-1.5 rounded-lg text-brand-600 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/40 dark:text-brand-300 transition-colors shadow-xs" title="Editar">
                                    <?= icon('edit-3', 'w-4 h-4 text-brand-600') ?>
                                </button>
                                <?php if ($s->activo): ?>
                                    <form action="/sedes/<?= $s->id ?>/desactivar" method="POST" class="inline" onsubmit="return confirm('¿Dar de baja esta sede?')">
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

    <!-- Panel de Alta / Edición (1 Columna a la derecha) -->
    <div class="lg:col-span-1 card p-6 h-fit shadow-card">
        <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white" 
                x-text="editingSede ? 'Editar Sede' : 'Nueva Sede'">Nueva Sede</h2>
            <button type="button" x-show="editingSede" @click="resetForm" class="text-xs text-brand-600 font-bold hover:underline">
                + Crear nueva
            </button>
        </div>

        <form :action="editingSede ? '/sedes/' + editingSede.id + '/actualizar' : '/sedes'" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="nombre" class="form-label">Nombre de la Sede <span class="text-danger-500">*</span></label>
                <input type="text" id="nombre" name="nombre" x-model="nombre" required class="form-input" placeholder="Ej: Colegio del Faro Benavidez">
            </div>

            <div>
                <label for="color_primario" class="form-label">Color de Acento Institucional <span class="text-danger-500">*</span></label>
                <div class="flex items-center gap-3">
                    <input type="color" id="color_primario" name="color_primario" x-model="color" class="w-10 h-10 rounded-lg cursor-pointer border-0 p-0 shadow-xs">
                    <input type="text" x-model="color" class="form-input font-mono text-xs uppercase" maxlength="7">
                </div>
            </div>

            <div x-show="editingSede">
                <label class="flex items-center gap-2 text-xs font-semibold text-ink-700 dark:text-ink-300 cursor-pointer">
                    <input type="checkbox" name="activo" value="1" x-model="activo" class="rounded border-ink-300 text-brand-600">
                    <span>Sede activa en el sistema</span>
                </label>
            </div>

            <div class="pt-3 border-t border-ink-100 dark:border-ink-800 flex justify-end gap-2">
                <button type="button" x-show="editingSede" @click="resetForm" class="btn-ghost text-xs font-semibold">Cancelar</button>
                <button type="submit" class="btn-primary text-xs font-bold text-white shadow-xs">
                    <span x-text="editingSede ? 'Guardar Cambios' : 'Crear Sede'">Crear Sede</span>
                </button>
            </div>
        </form>
    </div>

</div>
