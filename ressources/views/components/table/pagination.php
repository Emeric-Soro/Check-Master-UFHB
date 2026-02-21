<?php
$current = (int)($current_page ?? 1);
$total = (int)($total_pages ?? 1);
$perPage = (int)($per_page ?? 25);
$totalResults = (int)($total_results ?? 0);
$baseUrl = $base_url ?? '?';
$separator = strpos($baseUrl, '?') !== false ? '&' : '?';
?>
<?php if ($total > 1 || ($show_info ?? true)): ?>
<nav class="cm-pagination pagination is-small" role="navigation" aria-label="pagination">
    <?php if ($show_info ?? true): ?>
    <div class="cm-pagination-info">
        <?php
        $start = ($current - 1) * $perPage + 1;
        $end = min($current * $perPage, $totalResults);
        ?>
        <span class="has-text-grey">
            Affichage de <strong><?= $start ?></strong> à <strong><?= $end ?></strong> sur <strong><?= number_format($totalResults) ?></strong>
        </span>
    </div>
    <?php endif; ?>
    
    <?php if ($total > 1): ?>
    <ul class="pagination-list">
        <?php if ($current > 1): ?>
        <li>
            <a class="pagination-link" href="<?= htmlspecialchars($baseUrl . $separator . 'page=1') ?>" title="Première page">
                <span class="icon"><i class="fas fa-angle-double-left"></i></span>
            </a>
        </li>
        <li>
            <a class="pagination-link" href="<?= htmlspecialchars($baseUrl . $separator . 'page=' . ($current - 1)) ?>" title="Page précédente">
                <span class="icon"><i class="fas fa-angle-left"></i></span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $current - 2);
        $endPage = min($total, $current + 2);
        if ($startPage > 1) echo '<li><span class="pagination-ellipsis">&hellip;</span></li>';
        ?>
        
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <li>
            <a class="pagination-link <?= $i === $current ? 'is-current' : '' ?>" 
               href="<?= htmlspecialchars($baseUrl . $separator . 'page=' . $i) ?>"
               <?= $i === $current ? 'aria-current="page"' : '' ?>>
                <?= $i ?>
            </a>
        </li>
        <?php endfor; ?>
        
        <?php if ($endPage < $total) echo '<li><span class="pagination-ellipsis">&hellip;</span></li>'; ?>
        
        <?php if ($current < $total): ?>
        <li>
            <a class="pagination-link" href="<?= htmlspecialchars($baseUrl . $separator . 'page=' . ($current + 1)) ?>" title="Page suivante">
                <span class="icon"><i class="fas fa-angle-right"></i></span>
            </a>
        </li>
        <li>
            <a class="pagination-link" href="<?= htmlspecialchars($baseUrl . $separator . 'page=' . $total) ?>" title="Dernière page">
                <span class="icon"><i class="fas fa-angle-double-right"></i></span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <?php endif; ?>
</nav>
<?php endif; ?>
