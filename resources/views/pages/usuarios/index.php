<?php
$pageTitle = 'Administración de Usuarios';
global $container;
$view = $container->get(\App\Support\View::class);

$usersMap = [];
foreach ($usuarios as $u) {
    $usersMap[$u->id] = $u->toArray();
}
?>

<div x-data='{
    modalNew: false,
    modalEdit: false,
    users: <?= json_encode($usersMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    editingUser: null,
    nombre: "",
    email: "",
    rol: "recepcion_sede",
    sedeId: "",
    activo: true,
    edit(id) {
        const u = this.users[id];
        if (!u) return;
        this.editingUser = u;
        this.nombre = u.nombre;
        this.email = u.email;
        this.rol = u.rol;
        this.sedeId = u.sede_id || "";
        this.activo = !!u.activo;
        this.modalEdit = true;
    }
}'>

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-ink-500 mb-1">
                <span>Administración</span>
                <span>/</span>
                <span class="text-ink-800 dark:text-ink-200">Usuarios</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900 dark:text-white font-display tracking-tight">
                Usuarios y Permisos (RBAC)
            </h1>
            <p class="text-sm text-ink-500 mt-1 font-medium">
                Gestión de accesos, roles institucionales y sedes asignadas
            </p>
        </div>

        <div>
            <button type="button" @click="modalNew = true" class="btn-primary inline-flex items-center gap-2 shadow-sm font-semibold text-white">
                <?= icon('user-plus', 'w-4 h-4 text-white') ?>
                <span>Nuevo Usuario</span>
            </button>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="card p-0 overflow-hidden shadow-card">
        <div class="p-4 border-b border-ink-100 dark:border-ink-800 flex items-center justify-between">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Usuarios Habilitados</h2>
            <span class="text-xs text-ink-500 font-semibold"><?= count($usuarios) ?> usuarios registrados</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-100/70 dark:bg-ink-800/60 text-ink-600 dark:text-ink-400 text-[11px] font-bold uppercase tracking-wider border-b border-ink-200 dark:border-ink-800">
                    <tr>
                        <th scope="col" class="py-3.5 px-4">Usuario / Email</th>
                        <th scope="col" class="py-3.5 px-4">Rol del Sistema</th>
                        <th scope="col" class="py-3.5 px-4">Sede Asignada</th>
                        <th scope="col" class="py-3.5 px-4">Último Acceso</th>
                        <th scope="col" class="py-3.5 px-4">Estado</th>
                        <th scope="col" class="py-3.5 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60 bg-white dark:bg-ink-900">
                    <?php foreach ($usuarios as $u): ?>
                        <tr class="hover:bg-ink-50 dark:hover:bg-ink-800/30 transition-colors <?= !$u->activo ? 'opacity-50' : '' ?>">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-ink-900 dark:text-white"><?= e($u->nombre) ?></div>
                                <div class="text-xs text-ink-500 dark:text-ink-400"><?= e($u->email) ?></div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="pill pill-recibido text-xs font-semibold">
                                    <?= str_replace('_', ' ', $u->rol) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs font-medium">
                                <?= $u->sede_nombre ? e($u->sede_nombre) : '<span class="text-ink-400">Todas las sedes</span>' ?>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-ink-500">
                                <?= fmt_datetime($u->ultimo_login) ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?= $u->activo ? '<span class="pill pill-resuelto">Activo</span>' : '<span class="pill pill-cerrado">Inactivo</span>' ?>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                <button type="button" 
                                        @click="edit(<?= $u->id ?>)" 
                                        class="inline-flex items-center justify-center p-1.5 rounded-lg text-brand-600 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/40 dark:text-brand-300 transition-colors shadow-xs" title="Editar">
                                    <?= icon('edit-3', 'w-4 h-4 text-brand-600') ?>
                                </button>
                                <?php if ($u->id !== $user->id): ?>
                                    <form action="/usuarios/<?= $u->id ?>/estado" method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" 
                                                class="inline-flex items-center justify-center p-1.5 rounded-lg <?= $u->activo ? 'text-danger-600 bg-danger-50 hover:bg-danger-100 dark:bg-danger-950/40 dark:text-danger-300' : 'text-success-600 bg-success-50 hover:bg-success-100 dark:bg-success-950/40 dark:text-success-300' ?> transition-colors shadow-xs" 
                                                title="<?= $u->activo ? 'Dar de baja' : 'Reactivar' ?>">
                                            <?= icon($u->activo ? 'user-x' : 'user-check', 'w-4 h-4 ' . ($u->activo ? 'text-danger-600' : 'text-success-600')) ?>
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

    <!-- MODAL NUEVO USUARIO -->
    <div x-show="modalNew" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal" @click.away="modalNew = false">
            <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
                <h3 class="text-lg font-bold text-ink-900 dark:text-white">Alta de Usuario</h3>
                <button type="button" @click="modalNew = false" class="btn-icon">
                    <?= icon('x', 'w-4 h-4 text-ink-500') ?>
                </button>
            </div>
            <form action="/usuarios" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Nombre y Apellido <span class="text-danger-500">*</span></label>
                    <input type="text" name="nombre" required class="form-input" placeholder="Ej: María Gómez">
                </div>
                <div>
                    <label class="form-label">Correo Electrónico <span class="text-danger-500">*</span></label>
                    <input type="email" name="email" required class="form-input" placeholder="mgomez@reditinere.com">
                </div>
                <div>
                    <label class="form-label">Rol en el Sistema <span class="text-danger-500">*</span></label>
                    <select name="rol" x-model="rol" required class="form-select">
                        <option value="recepcion_sede">Recepción de Sede</option>
                        <option value="responsable_categoria">Responsable de Categoría</option>
                        <option value="direccion_sede">Dirección de Sede</option>
                        <option value="supervision_general">Supervisión General</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
                <div x-show="rol === 'recepcion_sede' || rol === 'direccion_sede'">
                    <label class="form-label">Sede Asignada <span class="text-danger-500">*</span></label>
                    <select name="sede_id" class="form-select">
                        <option value="">Seleccionar sede...</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?= $s->id ?>"><?= e($s->nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Contraseña (Entorno Local)</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-ink-100 dark:border-ink-800">
                    <button type="button" @click="modalNew = false" class="btn-ghost text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="btn-primary text-xs font-bold shadow-xs">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR USUARIO -->
    <div x-show="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal" @click.away="modalEdit = false">
            <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
                <h3 class="text-lg font-bold text-ink-900 dark:text-white">Editar Usuario</h3>
                <button type="button" @click="modalEdit = false" class="btn-icon">
                    <?= icon('x', 'w-4 h-4 text-ink-500') ?>
                </button>
            </div>
            <form :action="'/usuarios/' + (editingUser ? editingUser.id : '') + '/actualizar'" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Nombre y Apellido <span class="text-danger-500">*</span></label>
                    <input type="text" name="nombre" x-model="nombre" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Correo Electrónico <span class="text-danger-500">*</span></label>
                    <input type="email" name="email" x-model="email" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Rol en el Sistema <span class="text-danger-500">*</span></label>
                    <select name="rol" x-model="rol" required class="form-select">
                        <option value="recepcion_sede">Recepción de Sede</option>
                        <option value="responsable_categoria">Responsable de Categoría</option>
                        <option value="direccion_sede">Dirección de Sede</option>
                        <option value="supervision_general">Supervisión General</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
                <div x-show="rol === 'recepcion_sede' || rol === 'direccion_sede'">
                    <label class="form-label">Sede Asignada</label>
                    <select name="sede_id" x-model="sedeId" class="form-select">
                        <option value="">Seleccionar sede...</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?= $s->id ?>"><?= e($s->nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Nueva Contraseña (Opcional)</label>
                    <input type="password" name="password" class="form-input" placeholder="Dejar en blanco para no cambiar">
                </div>
                <div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-ink-700 dark:text-ink-300 cursor-pointer">
                        <input type="checkbox" name="activo" value="1" x-model="activo" class="rounded border-ink-300 text-brand-600">
                        <span>Usuario activo</span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-ink-100 dark:border-ink-800">
                    <button type="button" @click="modalEdit = false" class="btn-ghost text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="btn-primary text-xs font-bold shadow-xs">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

</div>
