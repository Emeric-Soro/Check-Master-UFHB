<?php
$title = (string) ($title ?? '');
$items = is_array($items ?? null) ? $items : [];
$footer = (string) ($footer ?? '');
$icon = (string) ($icon ?? 'fa-circle-info');

if ($title === '' && empty($items) && $footer === '') {
    return;
}
?>
<section class="cm-detail-card">
    <?php if ($title !== ''): ?>
    <header class="cm-detail-card__header">
        <h3 class="cm-detail-card__title">
            <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
        </h3>
    </header>
    <?php endif; ?>

    <div class="cm-detail-card__body">
        <?php if (empty($items)): ?>
        <p class="cm-detail-card__empty">Aucune information disponible.</p>
        <?php else: ?>
        <dl class="cm-detail-card__list">
            <?php foreach ($items as $item): ?>
            <?php
            $label = (string) ($item['label'] ?? '');
            $value = (string) ($item['value'] ?? '');
            ?>
            <div class="cm-detail-card__row">
                <dt class="cm-detail-card__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
                <dd class="cm-detail-card__value"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <?php endforeach; ?>
        </dl>
        <?php endif; ?>
    </div>

    <?php if ($footer !== ''): ?>
    <footer class="cm-detail-card__footer"><?= $footer ?></footer>
    <?php endif; ?>
</section>
