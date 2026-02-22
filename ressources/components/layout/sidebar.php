<?php
$current_page = (string) ($current_page ?? '');
$user = is_array($user ?? null) ? $user : [];
$menu_html = (string) ($menu_html ?? '');
$items = is_array($items ?? null) ? $items : [];

if (empty($items)) {
    $items = [
        ['label' => 'Dashboard', 'url' => '?page=dashboard', 'icon' => 'fa-house', 'page' => 'dashboard'],
        ['label' => 'Etudiants', 'url' => '?page=gestion_etudiants&action=ajouter_des_etudiants', 'icon' => 'fa-user-graduate', 'page' => 'gestion_etudiants'],
        ['label' => 'Scolarite', 'url' => '?page=gestion_scolarite', 'icon' => 'fa-credit-card', 'page' => 'gestion_scolarite'],
        ['label' => 'Notes', 'url' => '?page=gestion_notes_evaluations', 'icon' => 'fa-calculator', 'page' => 'gestion_notes_evaluations'],
        ['label' => 'Reclamations', 'url' => '?page=gestion_reclamations_scolarite', 'icon' => 'fa-circle-exclamation', 'page' => 'gestion_reclamations_scolarite'],
    ];
}
?>
<aside class="cm-sidebar" id="cmSidebar">
    <div class="cm-sidebar__logo">
        <img src="ressources/uploads/logo_mathInfo_fond_blanc.png" alt="Logo" class="cm-sidebar__logo-img">
        <span class="cm-sidebar__logo-text">CheckMaster</span>
    </div>

    <nav class="cm-sidebar__nav">
        <?php if ($menu_html !== ''): ?>
            <?= $menu_html ?>
        <?php else: ?>
        <ul class="cm-sidebar__menu">
            <?php foreach ($items as $item): ?>
                <?php
                $page = (string) ($item['page'] ?? '');
                $isActive = $page !== '' && $page === $current_page;
                ?>
            <li class="cm-sidebar__item <?= $isActive ? 'is-active' : '' ?>">
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
            <span class="cm-sidebar__user-role"><?= htmlspecialchars((string) ($user['role'] ?? 'Connecte'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
</aside>
