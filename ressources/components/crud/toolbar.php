<?php
$left_html = (string) ($left_html ?? '');
$center_html = (string) ($center_html ?? '');
$right_html = (string) ($right_html ?? '');
?>
<div class="cm-barre-intermediaire">
    <div class="cm-toolbar">
        <div class="cm-toolbar-left"><?= $left_html ?></div>
        <div class="cm-toolbar-center"><?= $center_html ?></div>
        <div class="cm-toolbar-right"><?= $right_html ?></div>
    </div>
</div>
