<?php
$title = (string) ($title ?? 'Activites recentes');
$items = is_array($items ?? null) ? $items : [];
?>
<section class="cm-activity-list">
    <header class="cm-activity-list__header">
        <h3 class="cm-activity-list__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
    </header>
    <ul class="cm-activity-list__items">
        <?php if (empty($items)): ?>
        <li class="cm-activity-list__item">
            <span class="cm-activity-list__text">Aucune activite recente.</span>
        </li>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <?php
                $type = strtolower((string) ($item['type'] ?? 'info'));
                $icon = (string) ($item['icon'] ?? 'fa-bell');
                $text = (string) ($item['text'] ?? '');
                $time = (string) ($item['time'] ?? '');
                ?>
            <li class="cm-activity-list__item">
                <span class="cm-activity-list__icon is-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="cm-activity-list__text"><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($time !== ''): ?><span class="cm-activity-list__time"><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</section>
