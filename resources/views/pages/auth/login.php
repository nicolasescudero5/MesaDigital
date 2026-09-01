<div class="card p-8 shadow-modal bg-white dark:bg-ink-900 border border-ink-200 dark:border-ink-800">
    
    <!-- Logo y Encabezado -->
    <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-xl bg-brand-600 flex items-center justify-center text-white font-display font-extrabold text-2xl mx-auto mb-3 shadow-sm">
            MD
        </div>
        <h1 class="text-xl font-bold font-display text-ink-900 dark:text-white">Mesa Digital</h1>
        <p class="text-xs text-ink-500 mt-1">Suite Red Itínere — Registro y Trazabilidad</p>
    </div>

    <?php if ($driver === 'google' && !empty($loginUrlGoogle)): ?>
        <!-- Flujo Google OAuth 2.0 (§ 6.1) -->
        <div class="mb-6">
            <a href="<?= e($loginUrlGoogle) ?>" class="w-full flex items-center justify-center gap-3 h-12 rounded-lg border border-ink-300 dark:border-ink-700 bg-white dark:bg-ink-800 text-ink-800 dark:text-white text-sm font-semibold hover:bg-ink-50 dark:hover:bg-ink-700 transition-all shadow-sm">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Iniciar sesión con cuenta Google</span>
            </a>
        </div>
        <div class="relative flex py-2 items-center mb-6">
            <div class="flex-grow border-t border-ink-200 dark:border-ink-800"></div>
            <span class="flex-shrink mx-4 text-[11px] font-bold uppercase tracking-wider text-ink-400">O con credenciales</span>
            <div class="flex-grow border-t border-ink-200 dark:border-ink-800"></div>
        </div>
    <?php endif; ?>

    <!-- Formulario de Login Estándar -->
    <form action="/login" method="POST" class="space-y-4">
        <?= csrf_field() ?>
        
        <div>
            <label for="email" class="form-label">
                Correo Electrónico <span class="text-danger-500">*</span>
            </label>
            <input type="email" id="email" name="email" required autocomplete="username" class="form-input" placeholder="usuario@reditinere.com">
        </div>

        <div>
            <label for="password" class="form-label">
                Contraseña <span class="text-danger-500">*</span>
            </label>
            <input type="password" id="password" name="password" required autocomplete="current-password" class="form-input" placeholder="••••••••">
        </div>

        <button type="submit" class="w-full btn-primary h-12 text-base font-bold shadow-md mt-2">
            Ingresar al Sistema
        </button>
    </form>

    <!-- Bloque de Acceso Rápido de Desarrollo (§ 6.1 y Guía § 13.1) -->
    <?php if ($isSimulatedEnabled && !empty($usuariosSembrados)): ?>
        <div class="mt-8 pt-6 border-t border-dashed border-warning-300 dark:border-warning-700/60 bg-warning-50/50 dark:bg-warning-950/20 -mx-8 -mb-8 p-6 rounded-b-xl">
            <div class="flex items-center gap-2 mb-3 text-warning-800 dark:text-warning-300">
                <?= icon('sparkles', 'w-4 h-4 text-warning-500') ?>
                <span class="text-xs font-bold uppercase tracking-wider">Acceso Rápido (Entorno Local)</span>
            </div>
            <p class="text-xs text-ink-500 dark:text-ink-400 mb-4 leading-relaxed">
                Seleccioná un perfil sembrado para ingresar instantáneamente con su alcance y permisos correspondientes:
            </p>

            <form action="/login/simulado" method="POST" class="grid grid-cols-1 gap-2 max-h-[300px] overflow-y-auto pr-1">
                <?= csrf_field() ?>
                <?php foreach ($usuariosSembrados as $u): ?>
                    <button type="submit" name="user_id" value="<?= $u->id ?>" 
                            class="flex items-center justify-between p-2.5 rounded-lg border border-ink-200 dark:border-ink-800 bg-white dark:bg-ink-900 hover:border-brand-500 dark:hover:border-brand-500 hover:shadow-sm text-left transition-all text-xs group">
                        <div class="min-w-0 pr-2">
                            <div class="font-bold text-ink-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 truncate">
                                <?= e($u->nombre) ?>
                            </div>
                            <div class="text-[11px] text-ink-400 truncate">
                                <?= e($u->email) ?>
                            </div>
                        </div>
                        <div class="flex flex-col items-end shrink-0">
                            <span class="pill pill-recibido text-[10px] py-0.5 px-2">
                                <?= str_replace('_', ' ', $u->rol) ?>
                            </span>
                            <?php if ($u->sede_nombre): ?>
                                <span class="text-[10px] text-ink-500 mt-0.5 truncate max-w-[120px]"><?= e($u->sede_nombre) ?></span>
                            <?php endif; ?>
                        </div>
                    </button>
                <?php endforeach; ?>
            </form>
        </div>
    <?php endif; ?>

</div>
