<?php
$isBoxed = ($style ?? '') === 'boxed';
$isToggle = ($style ?? '') === 'toggle';
$isPills = ($style ?? '') === 'pills';
$isFullwidth = $fullwidth ?? false;
?>
<div class="cm-tabs-container <?= $isFullwidth ? 'is-fullwidth' : '' ?>">
    <div class="tabs <?= $isBoxed ? 'is-boxed' : '' ?> <?= $isToggle ? 'is-toggle' : '' ?> <?= $isPills ? 'is-toggle-rounded' : '' ?> <?= $isFullwidth ? 'is-fullwidth' : '' ?>">
        <ul>
            <?php foreach ($tabs ?? [] as $tabId => $tab): ?>
            <?php
            $isActive = ($tab['is_active'] ?? false) || ($tabId === ($active_tab ?? null));
            $hasBadge = isset($tab['badge']);
            ?>
            <li class="<?= $isActive ? 'is-active' : '' ?>" data-tab="<?= htmlspecialchars($tabId) ?>">
                <a href="<?= htmlspecialchars($tab['url'] ?? '#') ?>">
                    <?php if (!empty($tab['icon'])): ?>
                    <span class="icon"><i class="fas <?= $tab['icon'] ?>"></i></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($tab['label'] ?? '') ?></span>
                    <?php if ($hasBadge): ?>
                    <span class="tag is-rounded is-info is-small"><?= htmlspecialchars($tab['badge']) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <?php if (($responsive ?? 'accordion') === 'accordion'): ?>
    <!-- Mobile accordion view -->
    <div class="cm-tabs-accordion is-hidden-tablet">
        <?php foreach ($tabs ?? [] as $tabId => $tab): ?>
        <?php $isActive = ($tab['is_active'] ?? false) || ($tabId === ($active_tab ?? null)); ?>
        <div class="cm-accordion-item <?= $isActive ? 'is-active' : '' ?>">
            <a href="<?= htmlspecialchars($tab['url'] ?? '#') ?>" class="cm-accordion-header">
                <?php if (!empty($tab['icon'])): ?>
                <span class="icon"><i class="fas <?= $tab['icon'] ?>"></i></span>
                <?php endif; ?>
                <span><?= htmlspecialchars($tab['label'] ?? '') ?></span>
                <?php if (isset($tab['badge'])): ?>
                <span class="tag is-rounded is-info is-small"><?= htmlspecialchars($tab['badge']) ?></span>
                <?php endif; ?>
                <span class="icon is-pulled-right"><i class="fas fa-chevron-right"></i></span>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
