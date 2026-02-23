<?php
$pagination = $pagination ?? [];
$base_url = $base_url ?? '?';
$param_name = $param_name ?? 'page_num';

if (!is_array($pagination) || empty($pagination)) {
    return;
}

$last = (int) ($pagination['last'] ?? 1);
$current = (int) ($pagination['current'] ?? 1);

if ($last <= 1) {
    return;
}

$build_url = static function (int $page) use ($base_url, $param_name): string {
    $separator = str_contains($base_url, '?') ? '&' : '?';
    return $base_url . $separator . rawurlencode($param_name) . '=' . $page;
};
?>
<nav class="cm-pagination" role="navigation" aria-label="Pagination" data-cm-ajax-pagination="true">
    <div class="cm-pagination__info">
        Affichage de <?= (int) ($pagination['offset'] ?? 0) + 1 ?> a
        <?= min(((int) ($pagination['offset'] ?? 0) + (int) ($pagination['per_page'] ?? 0)), (int) ($pagination['total'] ?? 0)) ?> sur
        <?= (int) ($pagination['total'] ?? 0) ?> entrees
    </div>

    <ul class="cm-pagination__list">
        <li class="cm-pagination__item <?= !empty($pagination['has_prev']) ? '' : 'is-disabled' ?>">
            <a href="<?= htmlspecialchars($build_url(max(1, $current - 1)), ENT_QUOTES, 'UTF-8') ?>"
               class="cm-pagination__link"
               aria-label="Page precedente"
               data-cm-ajax-link="true">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </a>
        </li>

        <?php foreach (($pagination['pages'] ?? []) as $p): ?>
        <?php if ((int) $p === -1): ?>
        <li class="cm-pagination__item is-ellipsis">
            <span class="cm-pagination__link">...</span>
        </li>
        <?php else: ?>
        <?php $page = (int) $p; ?>
        <li class="cm-pagination__item <?= $page === $current ? 'is-active' : '' ?>">
            <a href="<?= htmlspecialchars($build_url($page), ENT_QUOTES, 'UTF-8') ?>"
               class="cm-pagination__link"
               aria-label="Page <?= $page ?>"
               data-cm-ajax-link="true"
               <?= $page === $current ? 'aria-current="page"' : '' ?>>
                <?= $page ?>
            </a>
        </li>
        <?php endif; ?>
        <?php endforeach; ?>

        <li class="cm-pagination__item <?= !empty($pagination['has_next']) ? '' : 'is-disabled' ?>">
            <a href="<?= htmlspecialchars($build_url(min($last, $current + 1)), ENT_QUOTES, 'UTF-8') ?>"
               class="cm-pagination__link"
               aria-label="Page suivante"
               data-cm-ajax-link="true">
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
        </li>
    </ul>
</nav>
