<?php
$actions = is_array($actions ?? null) ? $actions : [];
?>
<div class="cm-form-buttons">
    <?php foreach ($actions as $action): ?>
        <?php
        $tag = strtolower((string) ($action['tag'] ?? 'button'));
        $class = (string) ($action['class'] ?? 'cm-btn is-info');
        $label = (string) ($action['label'] ?? 'Action');
        $icon = (string) ($action['icon'] ?? '');
        $type = (string) ($action['type'] ?? 'button');
        $href = (string) ($action['href'] ?? '#');
        ?>
        <?php if ($tag === 'a'): ?>
        <a class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($icon !== ''): ?><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?php endif; ?>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php else: ?>
        <button class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>" type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($icon !== ''): ?><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?php endif; ?>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </button>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
