<div class="card flex items-center justify-between">
    <div>
        <div class="text-[11px] font-bold uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-1">
            <?= e($label ?? '') ?>
        </div>
        <div class="kpi-value <?= $colorClass ?? 'text-ink-900 dark:text-white' ?>">
            <?= fmt_num($value ?? 0) ?>
        </div>
        <?php if (!empty($subtext)): ?>
            <div class="text-xs text-ink-400 mt-1"><?= e($subtext) ?></div>
        <?php endif; ?>
    </div>
    <?php if (!empty($icon)): ?>
        <div class="w-12 h-12 rounded-xl flex items-center justify-center <?= $iconBg ?? 'bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300' ?>">
            <?= icon($icon, 'w-6 h-6') ?>
        </div>
    <?php endif; ?>
</div>
