<?php
$title = (string) ($title ?? '');
$icon = (string) ($icon ?? 'fa-pen-to-square');
$content = (string) ($content ?? '');
?>
<div class="cm-pole-superieur">
    <?php if ($title !== ''): ?>
    <div class="cm-pole-superieur-title">
        <h2>
            <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
        </h2>
    </div>
    <?php endif; ?>
    <?= $content ?>
</div>
