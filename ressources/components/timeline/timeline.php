<?php
$steps = is_array($steps ?? null) ? $steps : [];
$orientation = strtolower((string) ($orientation ?? 'vertical'));
$orientation = $orientation === 'horizontal' ? 'horizontal' : 'vertical';
?>
<div class="cm-timeline is-<?= htmlspecialchars($orientation, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($steps as $index => $step): ?>
        <?php
        $state = strtolower((string) ($step['state'] ?? 'pending'));
        $label = (string) ($step['label'] ?? ('Etape ' . ($index + 1)));
        $date = (string) ($step['date'] ?? '');
        $desc = (string) ($step['desc'] ?? '');
        $icon = (string) ($step['icon'] ?? 'fa-circle');
        ?>
    <div class="cm-timeline__step is-<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>">
        <span class="cm-timeline__dot"><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></span>
        <?php if ($index < count($steps) - 1): ?><span class="cm-timeline__connector" aria-hidden="true"></span><?php endif; ?>
        <div class="cm-timeline__content">
            <div class="cm-timeline__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($date !== ''): ?><div class="cm-timeline__date"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if ($desc !== ''): ?><div class="cm-timeline__desc"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
