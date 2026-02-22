<?php
$id = (string) ($id ?? 'cmDataTable');
$headers = is_array($headers ?? null) ? $headers : [];
$rows = is_array($rows ?? null) ? $rows : [];
$empty_title = (string) ($empty_title ?? 'Aucune donnee');
$empty_message = (string) ($empty_message ?? 'Aucune ligne a afficher.');
?>
<div class="cm-table-wrapper">
    <table class="cm-data-table" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
        <thead>
        <tr>
            <?php foreach ($headers as $head): ?>
            <th class="cm-data-table__th"><?= htmlspecialchars((string) $head, ENT_QUOTES, 'UTF-8') ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <?php cm_component('ui/empty-state', [
                'in_table' => true,
                'colspan' => max(1, count($headers)),
                'title' => $empty_title,
                'message' => $empty_message,
            ]); ?>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
            <tr class="cm-data-table__row">
                <?php foreach ((array) $row as $cell): ?>
                <td class="cm-data-table__td"><?= htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
