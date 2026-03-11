<?php
$cols = isset($cols) ? max(1, min(4, (int) $cols)) : 3;
$content = (string) ($content ?? '');
?>
<div class="cm-hub-grid has-<?= $cols ?>-cols">
    <?= $content ?>
</div>
