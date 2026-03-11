<?php
$title = (string) ($title ?? 'Alertes');
$items = is_array($items ?? null) ? $items : [];
$max_show = isset($max_show) ? max(1, (int) $max_show) : 5;
$items = array_slice($items, 0, $max_show);
?>
<section class="cm-alert-list">
    <header class="cm-alert-list__header">
        <h3 class="cm-alert-list__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
    </header>
    <ul class="cm-alert-list__items">
        <?php if (empty($items)): ?>
        <li class="cm-alert-list__item is-info">Aucune alerte.</li>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <?php
                $type = strtolower((string) ($item['type'] ?? 'info'));
                $message = (string) ($item['message'] ?? '');
                $action_url = (string) ($item['action_url'] ?? '');
                $action_label = (string) ($item['action_label'] ?? 'Voir');
                ?>
            <li class="cm-alert-list__item is-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                <div class="cm-alert-list__message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($action_url !== ''): ?>
                <a class="cm-alert-list__action" href="<?= htmlspecialchars($action_url, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($action_label, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</section>
