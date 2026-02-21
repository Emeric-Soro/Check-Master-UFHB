<aside class="cm-sidebar">
    <div class="cm-sidebar-header">
        <div class="cm-logo">UFRMI</div>
        <button class="cm-burger" aria-label="menu">
            <span></span><span></span><span></span>
        </button>
    </div>
    <nav class="cm-sidebar-nav">
        <?php foreach ($menus as $item): ?>
            <?php if (isset($item['children'])): ?>
                <!-- Section with submenu -->
                <div class="cm-menu-parent <?= isset($item['is_active']) && $item['is_active'] ? 'is-open' : '' ?>">
                    <a href="#" class="cm-menu-toggle <?= isset($item['is_active']) && $item['is_active'] ? 'is-active' : '' ?>">
                        <i class="fas <?= $item['icon'] ?? 'fa-circle' ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                        <i class="fas fa-chevron-down cm-chevron"></i>
                    </a>
                    <ul class="cm-submenu" style="<?= isset($item['is_active']) && $item['is_active'] ? 'display: block;' : 'display: none;' ?>">
                        <?php foreach ($item['children'] as $child): ?>
                        <li>
                            <a href="<?= htmlspecialchars($child['url']) ?>" 
                               class="cm-menu-item <?= ($child['is_active'] ?? false) ? 'is-active' : '' ?>">
                                <?= htmlspecialchars($child['label']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Single item -->
                <a href="<?= htmlspecialchars($item['url']) ?>" 
                   class="cm-menu-item <?= ($item['is_active'] ?? false) ? 'is-active' : '' ?>">
                    <i class="fas <?= $item['icon'] ?? 'fa-circle' ?>"></i>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
