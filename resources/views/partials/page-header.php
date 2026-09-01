<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <?php if (!empty($breadcrumbs)): ?>
            <nav class="flex items-center gap-1.5 text-xs text-ink-400 mb-1">
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <?php if (!empty($crumb['url'])): ?>
                        <a href="<?= e($crumb['url']) ?>" class="hover:text-brand-600"><?= e($crumb['label']) ?></a>
                        <span>/</span>
                    <?php else: ?>
                        <span class="text-ink-600 dark:text-ink-300 font-medium"><?= e($crumb['label']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900 dark:text-white font-display tracking-tight">
            <?= e($title ?? 'Mesa Digital') ?>
        </h1>
        <?php if (!empty($subtitle)): ?>
            <p class="text-sm text-ink-500 mt-1"><?= e($subtitle) ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($actions)): ?>
        <div class="flex items-center gap-3">
            <?= $actions ?>
        </div>
    <?php endif; ?>
</div>
