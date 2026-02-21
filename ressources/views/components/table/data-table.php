<?php
$sortColumn = $sort_column ?? '';
$sortDir = $sort_direction ?? 'asc';
$tableClass = 'table is-fullwidth is-hoverable is-striped cm-data-table';
?>
<div class="cm-table-wrapper <?= ($responsive ?? true) ? 'table-container' : '' ?>">
    <table class="<?= $tableClass ?>">
        <thead>
            <tr>
                <?php foreach ($columns ?? [] as $col): ?>
                <?php
                $colKey = $col['key'] ?? '';
                $isSortable = $col['sortable'] ?? false;
                $isSorted = ($sortColumn === $colKey);
                $thClass = $isSortable ? 'cm-sortable' : '';
                $thClass .= $isSorted ? ' is-sorted ' . $sortDir : '';
                ?>
                <th class="<?= $thClass ?>" 
                    <?= $isSortable ? 'data-sort-column="' . htmlspecialchars($colKey) . '"' : '' ?>>
                    <?php if ($isSortable): ?>
                    <a href="#" class="cm-sort-link">
                        <?= htmlspecialchars($col['label'] ?? $colKey) ?>
                        <span class="icon cm-sort-icon">
                            <i class="fas fa-<?= $isSorted ? ($sortDir === 'asc' ? 'sort-up' : 'sort-down') : 'sort' ?>"></i>
                        </span>
                    </a>
                    <?php else: ?>
                    <?= htmlspecialchars($col['label'] ?? $colKey) ?>
                    <?php endif; ?>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?= count($columns ?? []) ?>" class="has-text-centered">
                    <?php
                    $empty_message = $empty_message ?? 'Aucune donnée disponible';
                    include dirname(__FILE__) . '/empty-state.php';
                    ?>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr data-id="<?= htmlspecialchars($row[$row_id_field ?? 'id'] ?? '') ?>">
                    <?php foreach ($columns ?? [] as $col): ?>
                    <?php
                    $colKey = $col['key'] ?? '';
                    $value = $row[$colKey] ?? '';
                    $renderer = $col['renderer'] ?? 'text';
                    ?>
                    <td>
                        <?php switch ($renderer):
                            case 'badge': ?>
                                <?php
                                $badge_entity = $col['badge_entity'] ?? '';
                                $badge_type = 'info';
                                // Map common status values to badge types
                                if (in_array(strtolower($value), ['actif', 'active', 'validé', 'validée', 'complet', 'réussi'])) {
                                    $badge_type = 'success';
                                } elseif (in_array(strtolower($value), ['inactif', 'inactive', 'rejeté', 'incomplet', 'échoué'])) {
                                    $badge_type = 'danger';
                                } elseif (in_array(strtolower($value), ['en attente', 'en cours', 'pending'])) {
                                    $badge_type = 'warning';
                                }
                                ?>
                                <span class="tag is-<?= $badge_type ?> is-light cm-badge"><?= htmlspecialchars($value) ?></span>
                            <?php break; ?>
                            <?php case 'date': ?>
                                <?php if (!empty($value) && $value !== '0000-00-00'): ?>
                                <?= date('d/m/Y', strtotime($value)) ?>
                                <?php endif; ?>
                            <?php break; ?>
                            <?php case 'datetime': ?>
                                <?php if (!empty($value) && $value !== '0000-00-00 00:00:00'): ?>
                                <?= date('d/m/Y H:i', strtotime($value)) ?>
                                <?php endif; ?>
                            <?php break; ?>
                            <?php case 'actions': ?>
                                <?php
                                $row_actions = $actions ?? [];
                                $row_id = $row[$row_id_field ?? 'id'] ?? '';
                                include dirname(__FILE__) . '/row-actions.php';
                                ?>
                            <?php break; ?>
                            <?php case 'boolean': ?>
                                <?php if ($value): ?>
                                <span class="icon has-text-success"><i class="fas fa-check"></i></span>
                                <?php else: ?>
                                <span class="icon has-text-danger"><i class="fas fa-times"></i></span>
                                <?php endif; ?>
                            <?php break; ?>
                            <?php case 'number': ?>
                                <span class="has-text-weight-semibold"><?= number_format((float)$value, ($col['decimals'] ?? 0), ',', ' ') ?></span>
                            <?php break; ?>
                            <?php case 'link': ?>
                                <a href="<?= htmlspecialchars(str_replace('{id}', $row[$row_id_field ?? 'id'] ?? '', $col['url_pattern'] ?? '#')) ?>">
                                    <?= htmlspecialchars($value) ?>
                                </a>
                            <?php break; ?>
                            <?php default: // text ?>
                                <?= htmlspecialchars($value) ?>
                        <?php endswitch; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
