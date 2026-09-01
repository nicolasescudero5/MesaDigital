<?php
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
$user = $user ?? current_user();

function is_active(string $path, string $currentPath): bool {
    if ($path === '/dashboard' && ($currentPath === '/' || $currentPath === '/dashboard')) {
        return true;
    }
    return str_starts_with($currentPath, $path);
}
?>

<!-- Backdrop Mobile -->
<div x-show="sidebarOpen" 
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false" 
     class="fixed inset-0 bg-ink-900/60 z-40 lg:hidden" 
     style="display: none;"></div>

<!-- Sidebar Fixed 260px (§ 7.3) -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed top-0 bottom-0 left-0 w-[260px] bg-white dark:bg-ink-900 border-r border-ink-200 dark:border-ink-800 flex flex-col z-40 transition-transform duration-200 ease-in-out">
    
    <!-- Logo / Marca -->
    <div class="h-16 px-6 flex items-center gap-3 border-b border-ink-100 dark:border-ink-800">
        <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white font-display font-extrabold text-lg shadow-sm">
            MD
        </div>
        <div>
            <div class="font-display font-bold text-ink-900 dark:text-white text-base leading-tight">Mesa Digital</div>
            <div class="text-[10px] font-semibold tracking-wider uppercase text-brand-600 dark:text-brand-400">Suite Red Itínere</div>
        </div>
    </div>

    <!-- Navegación con scroll -->
    <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
        
        <!-- Botón de Acción Principal Único -->
        <?php if ($user): ?>
            <div>
                <a href="/documentos/nuevo" class="w-full btn-primary flex items-center justify-center gap-2 shadow-sm font-semibold text-white">
                    <?= icon('plus-circle', 'w-4 h-4 text-white') ?>
                    <span>Nuevo Documento</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Grupo: NAVEGACIÓN -->
        <div>
            <div class="px-3 mb-2 text-[11px] font-bold tracking-wider uppercase text-ink-400 dark:text-ink-500">
                Navegación
            </div>
            <nav class="space-y-1">
                <a href="/dashboard" class="nav-item <?= is_active('/dashboard', $currentPath) ? 'active' : '' ?>">
                    <?= icon('layout-dashboard', 'w-4 h-4 ' . (is_active('/dashboard', $currentPath) ? 'text-brand-600 dark:text-brand-400' : 'text-ink-400 dark:text-ink-500')) ?>
                    <span>Dashboard</span>
                </a>
                <a href="/documentos" class="nav-item <?= is_active('/documentos', $currentPath) && !str_contains($currentPath, 'nuevo') ? 'active' : '' ?>">
                    <?= icon('inbox', 'w-4 h-4 ' . (is_active('/documentos', $currentPath) && !str_contains($currentPath, 'nuevo') ? 'text-brand-600 dark:text-brand-400' : 'text-ink-400 dark:text-ink-500')) ?>
                    <span>Bandeja de Documentos</span>
                </a>
            </nav>
        </div>

        <!-- Grupo: ADMINISTRACIÓN (Solo Administrador § 7.3) -->
        <?php if ($user && $user->isAdministrador()): ?>
            <div>
                <div class="px-3 mb-2 text-[11px] font-bold tracking-wider uppercase text-ink-400 dark:text-ink-500">
                    Administración
                </div>
                <nav class="space-y-1">
                    <a href="/sedes" class="nav-item <?= is_active('/sedes', $currentPath) ? 'active' : '' ?>">
                        <?= icon('building-2', 'w-4 h-4 ' . (is_active('/sedes', $currentPath) ? 'text-brand-600 dark:text-brand-400' : 'text-ink-400 dark:text-ink-500')) ?>
                        <span>Sedes</span>
                    </a>
                    <a href="/categorias" class="nav-item <?= is_active('/categorias', $currentPath) ? 'active' : '' ?>">
                        <?= icon('layers', 'w-4 h-4 ' . (is_active('/categorias', $currentPath) ? 'text-brand-600 dark:text-brand-400' : 'text-ink-400 dark:text-ink-500')) ?>
                        <span>Categorías y Resp.</span>
                    </a>
                    <a href="/tipos-documento" class="nav-item <?= is_active('/tipos-documento', $currentPath) ? 'active' : '' ?>">
                        <?= icon('file-text', 'w-4 h-4 ' . (is_active('/tipos-documento', $currentPath) ? 'text-brand-600 dark:text-brand-400' : 'text-ink-400 dark:text-ink-500')) ?>
                        <span>Tipos de Documento</span>
                    </a>
                    <a href="/caracteres-remitente" class="nav-item <?= is_active('/caracteres-remitente', $currentPath) ? 'active' : '' ?>">
                        <?= icon('tag', 'w-4 h-4 ' . (is_active('/caracteres-remitente', $currentPath) ? 'text-brand-600 dark:text-brand-400' : 'text-ink-400 dark:text-ink-500')) ?>
                        <span>Carácter de Remitente</span>
                    </a>
                    <a href="/usuarios" class="nav-item <?= is_active('/usuarios', $currentPath) ? 'active' : '' ?>">
                        <?= icon('users', 'w-4 h-4 ' . (is_active('/usuarios', $currentPath) ? 'text-brand-600 dark:text-brand-400' : 'text-ink-400 dark:text-ink-500')) ?>
                        <span>Usuarios y Roles</span>
                    </a>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pie del Sidebar: Usuario, Modo Oscuro y Salida (§ 7.3) -->
    <div class="p-4 border-t border-ink-200 dark:border-ink-800 bg-ink-50/50 dark:bg-ink-900/50">
        <?php if ($user): ?>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-brand-100 dark:bg-brand-900/40 text-brand-700 dark:text-brand-300 font-bold flex items-center justify-center text-sm uppercase">
                    <?= mb_substr($user->nombre, 0, 2) ?>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-ink-900 dark:text-white truncate"><?= e($user->nombre) ?></div>
                    <div class="text-[11px] text-ink-500 truncate capitalize"><?= str_replace('_', ' ', $user->rol) ?></div>
                    <?php if ($user->sede_nombre): ?>
                        <div class="text-[10px] text-brand-600 dark:text-brand-400 font-medium truncate"><?= e($user->sede_nombre) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between pt-2 border-t border-ink-200 dark:border-ink-800/80">
            <!-- Botón Tema Oscuro / Claro -->
            <button onclick="toggleTheme()" class="btn-icon text-ink-400 hover:text-ink-700" aria-label="Cambiar tema oscuro/claro" title="Cambiar tema">
                <span class="dark:hidden"><?= icon('moon', 'w-4 h-4 text-ink-500') ?></span>
                <span class="hidden dark:inline text-warning-400"><?= icon('sun', 'w-4 h-4') ?></span>
            </button>

            <!-- Cerrar Sesión (Siempre POST con CSRF § 6.2) -->
            <form action="/logout" method="POST" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold text-danger-600 hover:text-danger-700 p-1.5 rounded hover:bg-danger-50 dark:hover:bg-danger-950/30 transition-colors">
                    <?= icon('log-out', 'w-4 h-4 text-danger-500') ?>
                    <span>Cerrar sesión</span>
                </button>
            </form>
        </div>
    </div>
</aside>
