<?php
$separatorClass = '';
switch ($separator ?? 'arrow') {
    case 'bullet': $separatorClass = 'has-bullet-separator'; break;
    case 'dot': $separatorClass = 'has-dot-separator'; break;
    case 'succeeds': $separatorClass = 'has-succeeds-separator'; break;
    default: $separatorClass = 'has-arrow-separator';
}
?>
<nav class="breadcrumb <?= $separatorClass ?>" aria-label="breadcrumbs">
    <ul>
        <?php if (!empty($home_url)): ?>
        <li>
            <a href="<?= htmlspecialchars($home_url) ?>">
                <span class="icon"><i class="fas fa-home"></i></span>
                <span>Accueil</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php 
        $total = count($items ?? []);
        $index = 0;
        ?>
        <?php foreach ($items ?? [] as $item): ?>
        <?php $isLast = (++$index === $total); ?>
        <li class="<?= $isLast ? 'is-active' : '' ?>">
            <?php if ($isLast || empty($item['url'])): ?>
            <a href="#" aria-current="page"><?= htmlspecialchars($item['label'] ?? '') ?></a>
            <?php else: ?>
            <a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label'] ?? '') ?></a>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
