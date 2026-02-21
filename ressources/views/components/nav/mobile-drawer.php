<div class="cm-mobile-drawer">
    <!-- Overlay -->
    <div class="cm-drawer-overlay"></div>
    
    <!-- Drawer panel -->
    <aside class="cm-drawer-panel">
        <!-- Drawer header -->
        <div class="cm-drawer-header">
            <?php if (!empty($logo_url)): ?>
            <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo" class="cm-drawer-logo">
            <?php else: ?>
            <span class="cm-drawer-logo-text">UFRMI</span>
            <?php endif; ?>
            <button class="delete cm-drawer-close" aria-label="Fermer"></button>
        </div>
        
        <?php if (!empty($user)): ?>
        <!-- User info -->
        <div class="cm-drawer-user">
            <div class="cm-drawer-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="cm-drawer-user-info">
                <span class="cm-drawer-user-name">
                    <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                </span>
                <span class="cm-drawer-user-role">
                    <?= htmlspecialchars($user['groupe'] ?? '') ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Navigation -->
        <nav class="cm-drawer-nav">
            <?php if (!empty($home_url)): ?>
            <a href="<?= htmlspecialchars($home_url) ?>" class="cm-drawer-item">
                <span class="icon"><i class="fas fa-home"></i></span>
                <span>Accueil</span>
            </a>
            <?php endif; ?>
            
            <?php foreach ($menus ?? [] as $item): ?>
            <?php if (isset($item['children']) && !empty($item['children'])): ?>
            <!-- Section with submenu -->
            <div class="cm-drawer-section">
                <a href="#" class="cm-drawer-section-toggle">
                    <?php if (!empty($item['icon'])): ?>
                    <span class="icon"><i class="fas <?= $item['icon'] ?>"></i></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                    <span class="icon is-pulled-right"><i class="fas fa-chevron-down"></i></span>
                </a>
                <ul class="cm-drawer-submenu">
                    <?php foreach ($item['children'] as $child): ?>
                    <li>
                        <a href="<?= htmlspecialchars($child['url'] ?? '#') ?>" 
                           class="cm-drawer-item <?= ($child['is_active'] ?? false) ? 'is-active' : '' ?>">
                            <?= htmlspecialchars($child['label']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php else: ?>
            <!-- Single item -->
            <a href="<?= htmlspecialchars($item['url'] ?? '#') ?>" 
               class="cm-drawer-item <?= ($item['is_active'] ?? false) ? 'is-active' : '' ?>">
                <?php if (!empty($item['icon'])): ?>
                <span class="icon"><i class="fas <?= $item['icon'] ?>"></i></span>
                <?php endif; ?>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        
        <?php if (!empty($user)): ?>
        <!-- Footer actions -->
        <div class="cm-drawer-footer">
            <a href="/logout" class="cm-drawer-item has-text-danger">
                <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                <span>Déconnexion</span>
            </a>
        </div>
        <?php endif; ?>
    </aside>
</div>
