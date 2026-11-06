<?php
$pageTitle = 'Administración de Categorías y Directores';
global $container;
$view = $container->get(\App\Support\View::class);

$categoriasMap = [];
foreach ($categorias as $cat) {
    $resps = [];
    foreach ($cat->responsables as $r) {
        $resps[] = [
            'id' => $r->id,
            'email' => $r->email,
            'usuario_id' => $r->usuario_id,
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

$usuariosMap = [];
foreach ($usuariosResponsables as $u) {
    $usuariosMap[$u->id] = [
        'id' => (int)$u->id,
        'nombre' => $u->nombre,
        'email' => $u->email,
        'rol' => $u->rol,
        'sedes_ids' => $u->getSedesIds(),
    ];
}

$activeUsersMap = [];
foreach ($allActiveUsers ?? $usuariosResponsables as $u) {
    $activeUsersMap[$u->id] = [
        'id' => (int)$u->id,
        'nombre' => $u->nombre,
        'email' => $u->email,
        'rol' => $u->rol,
        'cargo_nivel' => $u->cargo_nivel ?? '',
        'sedes_ids' => $u->getSedesIds(),
    ];
}

$directoresMap = [];
foreach (array_merge($directoresSede ?? [], $directoresNivel ?? []) as $d) {
    $directoresMap[$d->id] = $d->toArray();
}

$sedesList = [];
foreach ($sedes as $s) {
    $sedesList[] = [
        'id' => (int)$s->id,
        'nombre' => $s->nombre,
        'color_primario' => $s->color_primario,
    ];
}
?>

<div x-data='{
    categorias: <?= json_encode($categoriasMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    usuarios: <?= json_encode($usuariosMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    activeUsers: <?= json_encode($activeUsersMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    directores: <?= json_encode($directoresMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    sedes: <?= json_encode($sedesList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    modalNew: false,
    modalManage: false,
    modalDirector: false,
    editingDirector: null,
    dirRol: "direccion_sede",
    dirUserId: "",
    dirSearchQuery: "",
    dirDropdownOpen: false,
    dirDefaultSedeId: null,
    dirCargoNivel: "",
    dirSedesIds: [],
    selectedCat: null,
    nombre: "",
    descripcion: "",
    orden: 0,
    activo: true,
    newRespUserId: "",
    respSearchQuery: "",
    respDropdownOpen: false,
    newRespEmail: "",
    newRespSedesIds: [],
    openManage(id) {
        const c = this.categorias[id];
        if (!c) return;
        this.selectedCat = c;
        this.nombre = c.nombre;
        this.descripcion = c.descripcion || "";
        this.orden = c.orden;
        this.activo = !!c.activo;
        this.newRespUserId = "";
        this.respSearchQuery = "";
        this.respDropdownOpen = false;
        this.newRespEmail = "";
        this.newRespSedesIds = [];
        this.modalManage = true;
    },
    openNewDirector(rol, defaultSedeId = null) {
        this.editingDirector = null;
        this.dirRol = rol;
        this.dirUserId = "";
        this.dirSearchQuery = "";
        this.dirDropdownOpen = false;
        this.dirDefaultSedeId = defaultSedeId ? Number(defaultSedeId) : null;
        this.dirCargoNivel = "";
        this.dirSedesIds = defaultSedeId ? [Number(defaultSedeId)] : [];
        this.modalDirector = true;
    },
    openEditDirector(id) {
        const d = this.directores[id] || this.activeUsers[id];
        if (!d) return;
        this.editingDirector = d;
        this.dirRol = d.rol;
        this.dirUserId = String(d.id);
        this.dirSearchQuery = d.nombre + " (" + d.email + ")";
        this.dirDropdownOpen = false;
        this.dirDefaultSedeId = null;
        this.dirCargoNivel = d.cargo_nivel || "";
        this.dirSedesIds = (d.sedes_ids && d.sedes_ids.length > 0) ? d.sedes_ids.map(Number) : (d.sede_id ? [Number(d.sede_id)] : []);
        this.modalDirector = true;
    },
    selectDirectorUser(u) {
        this.dirUserId = String(u.id);
        this.dirSearchQuery = u.nombre + " (" + u.email + ")";
        this.dirDropdownOpen = false;
        this.onSelectDirectorUser();
    },
    clearDirectorUser() {
        this.dirUserId = "";
        this.dirSearchQuery = "";
        this.dirDropdownOpen = true;
    },
    getFilteredDirectorUsers() {
        const q = (this.dirSearchQuery || "").trim().toLowerCase();
        const selectedUser = this.dirUserId ? this.activeUsers[this.dirUserId] : null;
        const isShowingSelectedLabel = selectedUser && (this.dirSearchQuery === (selectedUser.nombre + " (" + selectedUser.email + ")"));
        const effectiveQuery = isShowingSelectedLabel ? "" : q;

        const all = Object.values(this.activeUsers).filter(u => {
            if (!effectiveQuery) return true;
            const haystack = ((u.nombre || "") + " " + (u.email || "") + " " + (u.rol || "") + " " + (u.cargo_nivel || "")).toLowerCase();
            return haystack.includes(effectiveQuery);
        });
        return all.sort((a, b) => {
            const aMatch = a.rol === this.dirRol ? 0 : 1;
            const bMatch = b.rol === this.dirRol ? 0 : 1;
            if (aMatch !== bMatch) return aMatch - bMatch;
            return (a.nombre || "").localeCompare(b.nombre || "");
        });
    },
    selectRespUser(u) {
        this.newRespUserId = String(u.id);
        this.respSearchQuery = u.nombre + " (" + u.email + ")";
        this.respDropdownOpen = false;
        this.onSelectRespUser();
    },
    clearRespUser() {
        this.newRespUserId = "";
        this.respSearchQuery = "";
        this.respDropdownOpen = true;
        this.onSelectRespUser();
    },
    getFilteredRespUsers() {
        const q = (this.respSearchQuery || "").trim().toLowerCase();
        const selectedUser = this.newRespUserId ? this.usuarios[this.newRespUserId] : null;
        const isShowingSelectedLabel = selectedUser && (this.respSearchQuery === (selectedUser.nombre + " (" + selectedUser.email + ")"));
        const effectiveQuery = isShowingSelectedLabel ? "" : q;

        return Object.values(this.usuarios).filter(u => {
            if (!effectiveQuery) return true;
            const haystack = ((u.nombre || "") + " " + (u.email || "")).toLowerCase();
            return haystack.includes(effectiveQuery);
        });
    },
    onSelectDirectorUser() {
        const u = this.activeUsers[this.dirUserId];
        if (!u) return;
        if (u.cargo_nivel && !this.dirCargoNivel) {
            this.dirCargoNivel = u.cargo_nivel;
        }
        const existingSedes = (u.sedes_ids || []).map(Number);
        if (this.dirDefaultSedeId) {
            this.dirSedesIds = Array.from(new Set([...existingSedes, this.dirDefaultSedeId]));
        } else if (existingSedes.length > 0) {
            this.dirSedesIds = [...existingSedes];
        }
    },
    getAvailableSedes() {
        if (this.newRespUserId && this.usuarios[this.newRespUserId]) {
            const u = this.usuarios[this.newRespUserId];
            if (u.sedes_ids && u.sedes_ids.length > 0) {
                const allowed = u.sedes_ids.map(Number);
                return this.sedes.filter(s => allowed.includes(Number(s.id)));
            }
        }
        return this.sedes;
    },
    onSelectRespUser() {
        const avail = this.getAvailableSedes().map(s => Number(s.id));
        this.newRespSedesIds = this.newRespSedesIds.filter(id => avail.includes(Number(id)));
        if (this.newRespUserId && this.usuarios[this.newRespUserId]) {
            const u = this.usuarios[this.newRespUserId];
            if (u.sedes_ids && u.sedes_ids.length > 0 && this.newRespSedesIds.length === 0) {
                this.newRespSedesIds = u.sedes_ids.map(Number);
            }
        }
    },
    toggleAllAvailableSedes() {
        const avail = this.getAvailableSedes().map(s => Number(s.id));
        const allChecked = avail.length > 0 && avail.every(id => this.newRespSedesIds.includes(id));
        if (allChecked) {
            this.newRespSedesIds = [];
        } else {
            this.newRespSedesIds = [...avail];
        }
    }
}'>

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-ink-500 mb-1">
                <span>Administración</span>
                <span>/</span>
                <span class="text-ink-800 dark:text-ink-200">Categorías, Responsables y Directores</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900 dark:text-white font-display tracking-tight">
                Categorías, Responsables y Directores
            </h1>
            <p class="text-sm text-ink-500 mt-1 font-medium">
                Clasificación de correspondencia, responsables por categoría, Directores de Sede y Directores de Nivel por sede
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="openNewDirector('direccion_sede')" class="btn-neutral text-xs font-semibold inline-flex items-center gap-1.5 shadow-xs">
                <?= icon('user-check', 'w-4 h-4 text-brand-600') ?>
                <span>Asignar Director de Sede</span>
            </button>
            <button type="button" @click="openNewDirector('direccion_nivel')" class="btn-neutral text-xs font-semibold inline-flex items-center gap-1.5 shadow-xs">
                <?= icon('user-check', 'w-4 h-4 text-brand-600') ?>
                <span>Asignar Director de Nivel</span>
            </button>
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
            <strong>¿Cómo funciona la notificación y visibilidad?</strong> Al registrarse un documento en una categoría, el sistema notifica automáticamente al <strong>Responsable de Categoría</strong> asignado. Además, quien carga el documento puede marcar si desea <strong>Notificar a Director de Sede</strong> y/o <strong>Notificar a Director de Nivel</strong> de esa sede, lo que les envía un correo y les habilita permiso para ver ese documento.
        </div>
    </div>

    <!-- Tabla Principal de Categorías en Filas -->
    <div class="card p-0 overflow-hidden shadow-card mb-8">
        <div class="p-4 border-b border-ink-100 dark:border-ink-800 flex items-center justify-between">
            <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Catálogo de Categorías y Responsables</h2>
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
                                        <?php
                                        $groupedByEmail = [];
                                        foreach ($cat->responsables as $resp) {
                                            $key = strtolower($resp->email);
                                            if (!isset($groupedByEmail[$key])) {
                                                $groupedByEmail[$key] = [
                                                    'email' => $resp->email,
                                                    'sedes' => []
                                                ];
                                            }
                                            $groupedByEmail[$key]['sedes'][] = $resp->sede_nombre ?: 'Global';
                                        }
                                        ?>
                                        <?php foreach ($groupedByEmail as $grp): ?>
                                            <span class="inline-flex flex-wrap items-center gap-1 px-2 py-0.5 rounded-md bg-ink-100 dark:bg-ink-800 text-[11px] font-medium text-ink-700 dark:text-ink-200 border border-ink-200 dark:border-ink-700">
                                                <?= icon('mail', 'w-3 h-3 text-ink-400') ?>
                                                <span><?= e($grp['email']) ?></span>
                                                <?php foreach ($grp['sedes'] as $sedeLabel): ?>
                                                    <?php if ($sedeLabel !== 'Global'): ?>
                                                        <span class="px-1 py-0.2 text-[9px] font-bold rounded bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300"><?= e($sedeLabel) ?></span>
                                                    <?php else: ?>
                                                        <span class="px-1 py-0.2 text-[9px] font-semibold rounded bg-ink-200/60 text-ink-600 dark:bg-ink-700 dark:text-ink-300">Global</span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
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
                                    <form action="<?= app_url('/categorias/' . $cat->id . '/desactivar') ?>" method="POST" class="inline" onsubmit="return confirm('¿Dar de baja esta categoría?')">
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

    <!-- SECCIONES DE DIRECTORES DE SEDE Y DIRECTORES DE NIVEL POR SEDE -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Panel 1: Directores de Sede -->
        <div class="card p-0 overflow-hidden shadow-card">
            <div class="p-4 border-b border-ink-100 dark:border-ink-800 flex items-center justify-between">
                <div>
                    <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Directores de Sede</h2>
                    <p class="text-xs text-ink-500">Usuarios activos asignados como Director de Sede (se cargan al marcar "Notificar a Director de Sede")</p>
                </div>
                <button type="button" @click="openNewDirector('direccion_sede')" class="btn-primary text-xs py-1.5 px-3 inline-flex items-center gap-1 font-bold text-white shadow-xs">
                    <?= icon('user-check', 'w-3.5 h-3.5 text-white') ?>
                    <span>Asignar</span>
                </button>
            </div>
            <div class="divide-y divide-ink-100 dark:divide-ink-800/60">
                <?php foreach ($sedes as $s): ?>
                    <?php
                    $dirsOfSede = array_filter($directoresSede ?? [], fn($d) => in_array((int)$s->id, $d->getSedesIds(), true));
                    ?>
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full shrink-0" style="background-color: <?= e($s->color_primario) ?>"></span>
                            <span class="font-bold text-sm text-ink-900 dark:text-white"><?= e($s->nombre) ?></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if (empty($dirsOfSede)): ?>
                                <button type="button" @click="openNewDirector('direccion_sede', <?= $s->id ?>)" class="text-xs text-brand-600 font-semibold hover:underline flex items-center gap-1">
                                    <?= icon('user-plus', 'w-3.5 h-3.5') ?>
                                    <span>Asignar usuario activo</span>
                                </button>
                            <?php else: ?>
                                <?php foreach ($dirsOfSede as $dir): ?>
                                    <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-ink-100 dark:bg-ink-800 border border-ink-200 dark:border-ink-700 text-xs font-medium text-ink-800 dark:text-ink-200">
                                        <button type="button" @click="openEditDirector(<?= $dir->id ?>)" class="inline-flex items-center gap-1.5 hover:text-brand-600 transition-colors" title="Editar sedes asignadas">
                                            <?= icon('user', 'w-3.5 h-3.5 text-brand-600') ?>
                                            <span class="font-bold"><?= e($dir->nombre) ?></span>
                                            <span class="text-ink-500">(<?= e($dir->email) ?>)</span>
                                            <?= icon('edit-3', 'w-3 h-3 text-ink-400') ?>
                                        </button>
                                        <form action="<?= app_url('/categorias/directores/quitar') ?>" method="POST" class="inline ml-1" onsubmit="return confirm('¿Quitar este Director de Sede de <?= e($s->nombre) ?>?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="usuario_id" value="<?= $dir->id ?>">
                                            <input type="hidden" name="sede_id" value="<?= $s->id ?>">
                                            <button type="submit" class="text-danger-500 hover:text-danger-700 p-0.5 rounded" title="Quitar de esta sede">
                                                <?= icon('x', 'w-3 h-3') ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                                <button type="button" @click="openNewDirector('direccion_sede', <?= $s->id ?>)" class="text-[11px] font-bold text-brand-600 hover:text-brand-700">
                                    + Asignar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Panel 2: Directores de Nivel por Sede -->
        <div class="card p-0 overflow-hidden shadow-card">
            <div class="p-4 border-b border-ink-100 dark:border-ink-800 flex items-center justify-between">
                <div>
                    <h2 class="font-display font-bold text-base text-ink-900 dark:text-white">Directores de Nivel por Sede</h2>
                    <p class="text-xs text-ink-500">Usuarios activos asignados como Director de Nivel (aparecen al marcar "Notificar a Director de Nivel")</p>
                </div>
                <button type="button" @click="openNewDirector('direccion_nivel')" class="btn-primary text-xs py-1.5 px-3 inline-flex items-center gap-1 font-bold text-white shadow-xs">
                    <?= icon('user-check', 'w-3.5 h-3.5 text-white') ?>
                    <span>Asignar</span>
                </button>
            </div>
            <div class="divide-y divide-ink-100 dark:divide-ink-800/60">
                <?php foreach ($sedes as $s): ?>
                    <?php
                    $dirsNivelOfSede = array_filter($directoresNivel ?? [], fn($d) => in_array((int)$s->id, $d->getSedesIds(), true));
                    ?>
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full shrink-0" style="background-color: <?= e($s->color_primario) ?>"></span>
                                <span class="font-bold text-sm text-ink-900 dark:text-white"><?= e($s->nombre) ?></span>
                            </div>
                            <button type="button" @click="openNewDirector('direccion_nivel', <?= $s->id ?>)" class="text-xs text-brand-600 font-semibold hover:underline inline-flex items-center gap-1">
                                <?= icon('user-plus', 'w-3.5 h-3.5') ?>
                                <span>Asignar usuario activo</span>
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 pl-5">
                            <?php if (empty($dirsNivelOfSede)): ?>
                                <span class="text-xs text-ink-400 italic">Sin directores de nivel asignados en esta sede</span>
                            <?php else: ?>
                                <?php foreach ($dirsNivelOfSede as $dirN): ?>
                                    <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-ink-100 dark:bg-ink-800 border border-ink-200 dark:border-ink-700 text-xs font-medium text-ink-800 dark:text-ink-200">
                                        <button type="button" @click="openEditDirector(<?= $dirN->id ?>)" class="inline-flex items-center gap-1.5 hover:text-brand-600 transition-colors" title="Editar nivel o sedes asignadas">
                                            <?php if ($dirN->cargo_nivel): ?>
                                                <span class="px-1.5 py-0.2 rounded bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300 text-[10px] font-bold"><?= e($dirN->cargo_nivel) ?></span>
                                            <?php endif; ?>
                                            <span class="font-bold"><?= e($dirN->nombre) ?></span>
                                            <span class="text-ink-500">(<?= e($dirN->email) ?>)</span>
                                            <?= icon('edit-3', 'w-3 h-3 text-ink-400') ?>
                                        </button>
                                        <form action="<?= app_url('/categorias/directores/quitar') ?>" method="POST" class="inline ml-1" onsubmit="return confirm('¿Quitar este Director de Nivel de <?= e($s->nombre) ?>?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="usuario_id" value="<?= $dirN->id ?>">
                                            <input type="hidden" name="sede_id" value="<?= $s->id ?>">
                                            <button type="submit" class="text-danger-500 hover:text-danger-700 p-0.5 rounded" title="Quitar de esta sede">
                                                <?= icon('x', 'w-3 h-3') ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- MODAL POPUP: ASIGNACIÓN / EDICIÓN DE DIRECTOR DE SEDE O DE NIVEL DESDE USUARIOS ACTIVOS -->
    <div x-show="modalDirector" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 p-4" style="display: none;">
        <div class="card max-w-md w-full p-6 shadow-modal" @click.away="modalDirector = false">
            <div class="flex items-center justify-between pb-3 border-b border-ink-100 dark:border-ink-800 mb-4">
                <h3 class="text-lg font-bold text-ink-900 dark:text-white"
                    x-text="editingDirector ? 'Editar asignación de ' + (dirRol === 'direccion_nivel' ? 'Director de Nivel' : 'Director de Sede') : 'Asignar ' + (dirRol === 'direccion_nivel' ? 'Director de Nivel' : 'Director de Sede')"></h3>
                <button type="button" @click="modalDirector = false" class="btn-icon">
                    <?= icon('x', 'w-4 h-4 text-ink-500') ?>
                </button>
            </div>
            <form action="<?= app_url('/categorias/directores/asignar') ?>" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="rol" :value="dirRol">
                <input type="hidden" name="mode" :value="editingDirector ? 'edit' : 'add'">

                <div class="relative" @click.away="dirDropdownOpen = false">
                    <label class="form-label">Usuario Activo del Sistema <span class="text-danger-500">*</span></label>
                    <input type="hidden" name="usuario_id" :value="dirUserId">
                    <div class="relative">
                        <input type="text"
                               x-model="dirSearchQuery"
                               @focus="dirDropdownOpen = true"
                               @input="dirDropdownOpen = true; if (!dirSearchQuery) dirUserId = '';"
                               placeholder="Escribí nombre o correo para buscar..."
                               autocomplete="off"
                               class="form-input pr-8 font-medium">
                        <button type="button"
                                x-show="dirSearchQuery || dirUserId"
                                @click="clearDirectorUser()"
                                class="absolute inset-y-0 right-2 flex items-center text-ink-400 hover:text-ink-700"
                                title="Limpiar selección">
                            <?= icon('x', 'w-4 h-4') ?>
                        </button>
                    </div>

                    <!-- Dropdown de resultados filtrados -->
                    <div x-show="dirDropdownOpen"
                         class="absolute z-50 mt-1 w-full max-h-56 overflow-y-auto rounded-xl bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-700 shadow-lg divide-y divide-ink-100 dark:divide-ink-800">
                        <template x-if="getFilteredDirectorUsers().length === 0">
                            <div class="p-3 text-xs text-ink-500 italic text-center">
                                No se encontraron usuarios activos con ese criterio.
                            </div>
                        </template>
                        <template x-for="u in getFilteredDirectorUsers()" :key="'dir_opt_' + u.id">
                            <button type="button"
                                    @click="selectDirectorUser(u)"
                                    class="w-full text-left px-3 py-2 hover:bg-brand-50 dark:hover:bg-ink-800 transition-colors flex items-center justify-between gap-2 text-xs"
                                    :class="String(u.id) === String(dirUserId) ? 'bg-brand-50/80 dark:bg-brand-950/40 font-bold' : ''">
                                <div class="min-w-0">
                                    <div class="font-bold text-ink-900 dark:text-white truncate" x-text="u.nombre"></div>
                                    <div class="text-[11px] text-ink-500 truncate" x-text="u.email"></div>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-semibold"
                                      :class="u.rol === dirRol ? 'bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300' : 'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300'"
                                      x-text="u.cargo_nivel ? u.cargo_nivel : u.rol.replace('_', ' ')"></span>
                            </button>
                        </template>
                    </div>

                    <div class="mt-1.5 text-[11px] text-ink-500 flex items-center justify-between">
                        <span x-show="dirUserId && activeUsers[dirUserId]" class="text-success-700 dark:text-success-400 font-semibold" x-text="'Seleccionado: ' + (activeUsers[dirUserId] ? activeUsers[dirUserId].nombre : '')"></span>
                        <span x-show="!dirUserId">Buscá y seleccioná un usuario activo del sistema.</span>
                        <a href="<?= app_url('/usuarios') ?>" class="text-brand-600 font-bold hover:underline">Crear en Usuarios →</a>
                    </div>
                </div>

                <div x-show="dirRol === 'direccion_nivel'">
                    <label class="form-label">Nivel en la Sede <span class="text-danger-500">*</span></label>
                    <input type="text" name="cargo_nivel" x-model="dirCargoNivel" :required="dirRol === 'direccion_nivel'" class="form-input" placeholder="Ej: Nivel Inicial, Nivel Primario, Nivel Secundario...">
                </div>

                <div class="space-y-1.5">
                    <label class="form-label">Sede Asignada <span class="text-danger-500">*</span></label>
                    <div class="max-h-44 overflow-y-auto p-2.5 rounded-xl border border-ink-200 dark:border-ink-700 bg-ink-50/50 dark:bg-ink-800/40 space-y-1">
                        <?php foreach ($sedes as $s): ?>
                            <label class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-white dark:hover:bg-ink-700/60 cursor-pointer text-xs font-medium text-ink-800 dark:text-ink-200 transition-colors">
                                <input type="checkbox" name="sedes_ids[]" value="<?= $s->id ?>"
                                       :checked="dirSedesIds.includes(<?= (int)$s->id ?>)"
                                       @change="if ($event.target.checked) { if (!dirSedesIds.includes(<?= (int)$s->id ?>)) dirSedesIds.push(<?= (int)$s->id ?>); } else { dirSedesIds = dirSedesIds.filter(x => x !== <?= (int)$s->id ?>); }"
                                       class="rounded border-ink-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: <?= e($s->color_primario) ?>"></span>
                                <span><?= e($s->nombre) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-ink-100 dark:border-ink-800">
                    <button type="button" @click="modalDirector = false" class="btn-ghost text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="btn-primary text-xs font-bold shadow-xs text-white">Guardar Asignación</button>
                </div>
            </form>
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
            <form :action="'<?= app_url('/categorias') ?>/' + (selectedCat ? selectedCat.id : '') + '/actualizar'" method="POST" class="space-y-4 mb-6">
                <?= csrf_field() ?>
                <input type="hidden" name="usuario_id" :value="newRespUserId">
                <input type="hidden" name="email" :value="newRespEmail">
                <template x-for="sid in newRespSedesIds" :key="'cat_upd_sede_' + sid">
                    <input type="hidden" name="sedes_ids[]" :value="sid">
                </template>

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
                            <form :action="'<?= app_url('/categorias') ?>/' + selectedCat.id + '/responsables/eliminar'" method="POST" onsubmit="return confirm('¿Remover este responsable?')">
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
                <form :action="'<?= app_url('/categorias') ?>/' + (selectedCat ? selectedCat.id : '') + '/responsables'" method="POST" class="p-4 rounded-xl bg-ink-50 dark:bg-ink-800/60 border border-ink-200 dark:border-ink-700 space-y-3">
                    <?= csrf_field() ?>
                    <div class="text-xs font-bold text-ink-800 dark:text-white flex items-center gap-1.5">
                        <?= icon('user-plus', 'w-3.5 h-3.5 text-brand-600') ?>
                        <span>Asignar Nuevo Responsable</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="relative" @click.away="respDropdownOpen = false">
                            <label class="form-label text-[10px]">Usuario del Sistema</label>
                            <input type="hidden" name="usuario_id" :value="newRespUserId">
                            <div class="relative">
                                <input type="text"
                                       x-model="respSearchQuery"
                                       @focus="respDropdownOpen = true"
                                       @input="respDropdownOpen = true; if (!respSearchQuery) { newRespUserId = ''; onSelectRespUser(); }"
                                       placeholder="Buscar por nombre o correo..."
                                       autocomplete="off"
                                       class="form-input text-xs pr-7">
                                <button type="button"
                                        x-show="respSearchQuery || newRespUserId"
                                        @click="clearRespUser()"
                                        class="absolute inset-y-0 right-2 flex items-center text-ink-400 hover:text-ink-700"
                                        title="Limpiar selección">
                                    <?= icon('x', 'w-3.5 h-3.5') ?>
                                </button>
                            </div>
                            <div x-show="respDropdownOpen"
                                 class="absolute z-50 mt-1 w-full max-h-48 overflow-y-auto rounded-xl bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-700 shadow-lg divide-y divide-ink-100 dark:divide-ink-800">
                                <template x-if="getFilteredRespUsers().length === 0">
                                    <div class="p-2.5 text-xs text-ink-500 italic text-center">
                                        Sin coincidencias.
                                    </div>
                                </template>
                                <template x-for="u in getFilteredRespUsers()" :key="'resp_opt_' + u.id">
                                    <button type="button"
                                            @click="selectRespUser(u)"
                                            class="w-full text-left px-3 py-2 hover:bg-brand-50 dark:hover:bg-ink-800 transition-colors text-xs"
                                            :class="String(u.id) === String(newRespUserId) ? 'bg-brand-50/80 dark:bg-brand-950/40 font-bold' : ''">
                                        <div class="font-bold text-ink-900 dark:text-white truncate" x-text="u.nombre"></div>
                                        <div class="text-[11px] text-ink-500 truncate" x-text="u.email"></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="form-label text-[10px]">O Correo Directo</label>
                            <input type="email" name="email" x-model="newRespEmail" :disabled="!!newRespUserId" class="form-input text-xs disabled:opacity-50" placeholder="ejemplo@abogados.com">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="form-label text-[10px] mb-0">Sedes de Alcance</label>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-ink-400" x-show="newRespSedesIds.length === 0 && (!newRespUserId || !usuarios[newRespUserId] || !usuarios[newRespUserId].sedes_ids || usuarios[newRespUserId].sedes_ids.length === 0)">
                                    Sin marcar = Todas las Sedes (Global)
                                </span>
                                <button type="button" @click="toggleAllAvailableSedes()" class="text-[10px] font-bold text-brand-600 hover:text-brand-700 underline">
                                    Marcar / Desmarcar todas
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-40 overflow-y-auto p-2 rounded-xl border border-ink-200 dark:border-ink-700 bg-white dark:bg-ink-900/60">
                            <template x-for="s in getAvailableSedes()" :key="s.id">
                                <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-ink-50 dark:hover:bg-ink-800/60 cursor-pointer text-xs font-medium text-ink-800 dark:text-ink-200 transition-colors">
                                    <input type="checkbox" name="sedes_ids[]" :value="s.id"
                                           :checked="newRespSedesIds.includes(Number(s.id))"
                                           @change="if ($event.target.checked) { if (!newRespSedesIds.includes(Number(s.id))) newRespSedesIds.push(Number(s.id)); } else { newRespSedesIds = newRespSedesIds.filter(x => x !== Number(s.id)); }"
                                           class="rounded border-ink-300 text-brand-600 focus:ring-brand-500 w-3.5 h-3.5">
                                    <span class="w-2 h-2 rounded-full shrink-0" :style="'background-color: ' + s.color_primario"></span>
                                    <span class="truncate" x-text="s.nombre"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary text-xs font-bold inline-flex items-center gap-1.5 shadow-xs text-white">
                            <?= icon('plus', 'w-3.5 h-3.5 text-white') ?>
                            <span>Guardar Responsable</span>
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

            <form action="<?= app_url('/categorias') ?>" method="POST" class="space-y-4">
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
