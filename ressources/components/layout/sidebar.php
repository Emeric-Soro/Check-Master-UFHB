<?php
$current_page = (string) ($current_page ?? '');
$user = is_array($user ?? null) ? $user : [];
$menu_html = (string) ($menu_html ?? '');
$items = is_array($items ?? null) ? $items : [];
$logo_src = (string) ($logo_src ?? 'image/logo_cm_sbg.png');
$logo_label = (string) ($logo_label ?? 'CheckMaster');

if (empty($items)) {
    $items = [
        ['label' => 'Dashboard', 'url' => '?page=dashboard', 'icon' => 'fa-house', 'page' => 'dashboard'],
    ];
}
?>
<aside class="cm-sidebar" id="cmSidebar">
    <div class="cm-sidebar__logo">
        <img src="<?= htmlspecialchars($logo_src, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="cm-sidebar__logo-img">
        <span class="cm-sidebar__logo-text"><?= htmlspecialchars($logo_label, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <nav class="cm-sidebar__nav">
        <?php if ($menu_html !== ''): ?>
            <?= $menu_html ?>
        <?php else: ?>
        <ul class="cm-sidebar__menu">
            <?php foreach ($items as $item): ?>
            <?php
            $page = (string) ($item['page'] ?? '');
            $is_active = $page !== '' && $page === $current_page;
            ?>
            <li class="cm-sidebar__item <?= $is_active ? 'is-active' : '' ?>">
                <a class="cm-sidebar__link" href="<?= htmlspecialchars((string) ($item['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas <?= htmlspecialchars((string) ($item['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                    <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </nav>

    <div class="cm-sidebar__footer">
        <div class="cm-sidebar__user-info">
            <span class="cm-sidebar__user-name"><?= htmlspecialchars((string) ($user['username'] ?? 'Utilisateur'), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="cm-sidebar__user-role"><?= htmlspecialchars((string) ($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
</aside>
