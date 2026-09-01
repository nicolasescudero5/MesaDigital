<?php
$flashes = flash();
?>

<div x-data="{
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
     }"
     @notify.window="add($event.detail.message, $event.detail.type)"
     class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
     aria-live="polite"
     role="region"
     aria-label="Notificaciones">
    
    <!-- Renderizado de flash messages del servidor -->
    <?php foreach ($flashes as $flash): ?>
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 6000)"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-raised border text-sm font-medium
                    <?= ($flash['type'] ?? 'success') === 'error' 
                        ? 'bg-danger-50 text-danger-900 border-danger-200 dark:bg-danger-950 dark:text-danger-200 dark:border-danger-800' 
                        : (($flash['type'] ?? 'success') === 'warning'
                            ? 'bg-warning-50 text-warning-900 border-warning-200 dark:bg-warning-950 dark:text-warning-200 dark:border-warning-800'
                            : 'bg-success-50 text-success-900 border-success-200 dark:bg-success-950 dark:text-success-200 dark:border-success-800') ?>">
            <div class="flex-1">
                <?= e($flash['message'] ?? '') ?>
            </div>
            <button @click="show = false" class="text-ink-400 hover:text-ink-700">
                <?= icon('x', 'w-4 h-4') ?>
            </button>
        </div>
    <?php endforeach; ?>

    <!-- Toasts dinámicos de Alpine.js -->
    <template x-for="t in toasts" :key="t.id">
        <div class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-raised border text-sm font-medium transition-all"
             :class="{
                'bg-success-50 text-success-900 border-success-200 dark:bg-success-950 dark:text-success-200': t.type === 'success',
                'bg-danger-50 text-danger-900 border-danger-200 dark:bg-danger-950 dark:text-danger-200': t.type === 'error',
                'bg-warning-50 text-warning-900 border-warning-200 dark:bg-warning-950 dark:text-warning-200': t.type === 'warning',
                'bg-info-50 text-info-900 border-info-200 dark:bg-info-950 dark:text-info-200': t.type === 'info'
             }">
            <div class="flex-1" x-text="t.message"></div>
            <button @click="remove(t.id)" class="text-ink-400 hover:text-ink-700">
                <?= icon('x', 'w-4 h-4') ?>
            </button>
        </div>
    </template>
</div>
