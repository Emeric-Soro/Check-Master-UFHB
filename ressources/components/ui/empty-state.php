<?php
$title = (string) ($title ?? 'Aucune donnee');
$message = (string) ($message ?? '');
$icon = (string) ($icon ?? 'fa-inbox');
$in_table = !empty($in_table);
$colspan = isset($colspan) ? max(1, (int) $colspan) : 1;
?>
<?php if ($in_table): ?>
<tr>
    <td colspan="<?= (int) $colspan ?>">
        <div class="cm-empty-state">
            <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?> cm-empty-state__icon" aria-hidden="true"></i>
            <p class="cm-empty-state__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($message !== ''): ?>
            <p class="cm-empty-state__message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php else: ?>
<div class="cm-empty-state is-standalone">
    <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?> cm-empty-state__icon" aria-hidden="true"></i>
    <p class="cm-empty-state__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></p>
    <?php if ($message !== ''): ?>
    <p class="cm-empty-state__message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>
