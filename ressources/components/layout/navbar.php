<?php
$user = is_array($user ?? null) ? $user : [];
$annee = (string) ($annee ?? (date('Y') . '-' . (date('Y') + 1)));
$nom = trim((string) ($user['nom'] ?? '') . ' ' . (string) ($user['prenom'] ?? ''));
if ($nom === '') {
    $nom = (string) ($user['username'] ?? 'Utilisateur');
}
$role = (string) ($user['role'] ?? 'Administrateur');
$avatar = (string) ($user['avatar'] ?? '');
$profile_url = (string) ($user['profile_url'] ?? '?page=profil');
$logout_url = (string) ($user['logout_url'] ?? 'logout.php');
$notification_count = isset($user['notifications']) ? (int) $user['notifications'] : 0;
?>
<header class="cm-navbar" id="cmNavbar">
    <div class="cm-navbar__left">
        <button class="cm-navbar__toggle" id="sidebarToggle" aria-label="Ouvrir/fermer le menu">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
        <span class="cm-navbar__app-name">CheckMaster</span>
    </div>

    <div class="cm-navbar__center">
        <span class="cm-navbar__year-badge">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            <?= htmlspecialchars($annee, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>

    <div class="cm-navbar__right">
        <button class="cm-navbar__icon-btn" type="button" aria-label="Notifications">
            <i class="fas fa-bell" aria-hidden="true"></i>
            <?php if ($notification_count > 0): ?>
            <span class="cm-navbar__badge"><?= $notification_count ?></span>
            <?php endif; ?>
        </button>

        <div class="cm-navbar__user" id="userDropdown" tabindex="0" aria-label="Menu utilisateur">
            <div class="cm-navbar__avatar" aria-hidden="true">
                <?php if ($avatar !== ''): ?>
                <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="cm-navbar__avatar-img">
                <?php else: ?>
                <?= htmlspecialchars(strtoupper(substr($nom, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
            <span class="cm-navbar__username"><?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?></span>
            <i class="fas fa-chevron-down cm-navbar__chevron" aria-hidden="true"></i>
            <div class="cm-navbar__dropdown" id="userDropdownMenu">
                <a href="<?= htmlspecialchars($profile_url, ENT_QUOTES, 'UTF-8') ?>" class="cm-navbar__dropdown-item">
                    <i class="fas fa-user" aria-hidden="true"></i> Profil (<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>)
                </a>
                <a href="<?= htmlspecialchars($logout_url, ENT_QUOTES, 'UTF-8') ?>" class="cm-navbar__dropdown-item is-danger">
                    <i class="fas fa-right-from-bracket" aria-hidden="true"></i> Deconnexion
                </a>
            </div>
        </div>
    </div>
</header>
