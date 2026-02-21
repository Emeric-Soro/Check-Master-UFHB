<?php
$isSorted = ($current_sort ?? '') === $key;
$direction = $sort_direction ?? 'asc';
$newDirection = $isSorted && $direction === 'asc' ? 'desc' : 'asc';
$url = ($base_url ?? '?') . '&sort=' . urlencode($key) . '&dir=' . $newDirection;
?>
<?php if ($sortable ?? false): ?>
<a href="<?= htmlspecialchars($url) ?>" class="cm-column-header cm-sortable <?= $isSorted ? 'is-sorted is-' . $direction : '' ?>">
    <span><?= htmlspecialchars($label) ?></span>
    <span class="icon cm-sort-icon">
        <i class="fas fa-<?= $isSorted ? ($direction === 'asc' ? 'sort-up' : 'sort-down') : 'sort' ?>"></i>
    </span>
</a>
<?php else: ?>
<span class="cm-column-header"><?= htmlspecialchars($label) ?></span>
<?php endif; ?>
