<?php
// TableHelper déjà inclus par layout.php

$id = (string) ($id ?? 'cmDataTable');
$headers = is_array($headers ?? null) ? $headers : [];
$columns = is_array($columns ?? null) ? $columns : [];
$rows = is_array($rows ?? null) ? $rows : [];
$empty_title = (string) ($empty_title ?? 'Aucune donnée');
$empty_message = (string) ($empty_message ?? 'Aucune ligne à afficher.');
$table_class = (string) ($table_class ?? 'cm-data-table');
$wrapper_class = (string) ($wrapper_class ?? 'cm-table-wrapper');
$selectable = !empty($selectable);
$row_key = (string) ($row_key ?? 'id');
$actions = is_array($actions ?? null) ? $actions : [];

if (empty($columns) && !empty($headers)) {
    $columns = [];
    foreach ($headers as $idx => $header) {
        $columns[] = cm_column((string) $idx, (string) $header);
    }
}
?>
<div class="<?= htmlspecialchars(trim($wrapper_class), ENT_QUOTES, 'UTF-8') ?>">
    <table class="<?= htmlspecialchars($table_class, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
        <thead>
            <tr>
                <?php if ($selectable): ?>
                <th class="cm-data-table__th cm-data-table__th--check">
                    <input type="checkbox" class="cm-table-check-all" aria-label="Tout sélectionner">
                </th>
                <?php endif; ?>

                <?php foreach ($columns as $column): ?>
                <?php
                $label = (string) ($column['label'] ?? '');
                $align = (string) ($column['align'] ?? 'left');
                $colClass = (string) ($column['class'] ?? '');
                $thClass = 'cm-data-table__th is-' . htmlspecialchars($align, ENT_QUOTES, 'UTF-8');
                if ($colClass) {
                    $thClass .= ' ' . htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8');
                }
                ?>
                <th class="<?= $thClass ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </th>
                <?php endforeach; ?>

                <?php if (!empty($actions)): ?>
                <th class="cm-data-table__th is-center is-actions">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <?php cm_component('ui/empty-state', [
                    'in_table' => true,
                    'colspan' => count($columns) + ($selectable ? 1 : 0) + (!empty($actions) ? 1 : 0),
                    'title' => $empty_title,
                    'message' => $empty_message,
                ]); ?>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <?php $rowData = is_array($row) ? $row : (array) $row; ?>
                <tr class="cm-data-table__row">
                    <?php if ($selectable): ?>
                    <?php $rowId = (string) ($rowData[$row_key] ?? ''); ?>
                    <td class="cm-data-table__td cm-data-table__td--check">
                        <input type="checkbox" 
                               class="cm-table-check-row" 
                               value="<?= htmlspecialchars($rowId, ENT_QUOTES, 'UTF-8') ?>"
                               aria-label="Sélectionner la ligne">
                    </td>
                    <?php endif; ?>

                    <?php foreach ($columns as $column): ?>
                    <?php
                    $key = (string) ($column['key'] ?? '');
                    $align = (string) ($column['align'] ?? 'left');
                    $colClass = (string) ($column['class'] ?? '');
                    $type = (string) ($column['type'] ?? 'text');
                    $format = $column['format'] ?? null;
                    $value = $rowData[$key] ?? '';
                    $rendered = '';

                    if (is_callable($format)) {
                        $rendered = (string) $format($value, $rowData);
                    } elseif ($type === 'badge') {
                        $rendered = is_array($value) ? (string) ($value['label'] ?? '') : (string) $value;
                    } elseif ($type === 'number') {
                        $rendered = number_format((float) $value, 0, ',', ' ');
                    } else {
                        $rendered = (string) $value;
                    }
                    $tdClass = 'cm-data-table__td is-' . htmlspecialchars($align, ENT_QUOTES, 'UTF-8');
                    if ($colClass) {
                        $tdClass .= ' ' . htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8');
                    }
                    ?>
                    <td class="<?= $tdClass ?>">
                        <?php if ($type === 'badge'): ?>
                            <?php 
                            $badgeType = is_array($value) ? (string) ($value['type'] ?? 'info') : 'info';
                            $badgeText = is_array($value) ? (string) ($value['label'] ?? '') : $rendered;
                            cm_component('ui/badge', ['text' => $badgeText, 'type' => $badgeType]); 
                            ?>
                        <?php else: ?>
                            <?= htmlspecialchars($rendered, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>

                    <?php if (!empty($actions)): ?>
                    <td class="cm-data-table__td is-center is-actions">
                        <div class="cm-table-actions">
                            <?php foreach ($actions as $action): ?>
                                <?php
                                $label = (string) ($action['label'] ?? 'Action');
                                $icon = (string) ($action['icon'] ?? 'fa-circle');
                                $href = (string) ($action['href'] ?? '#');
                                $class = (string) ($action['class'] ?? 'cm-btn-action is-info');
                                $tag = strtolower((string) ($action['tag'] ?? 'a'));
                                ?>
                                <?php if ($tag === 'button'): ?>
                                <button type="<?= htmlspecialchars((string) ($action['type'] ?? 'button'), ENT_QUOTES, 'UTF-8') ?>"
                                        class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>"
                                        data-row-id="<?= htmlspecialchars((string) ($rowData[$row_key] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                </button>
                                <?php else: ?>
                                <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                                   class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>"
                                   aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
