<header class="cm-header">
    <div class="cm-header-left">
        <button class="cm-burger cm-burger-mobile" aria-label="menu">
            <span></span><span></span><span></span>
        </button>
        <h1 class="cm-header-title"><?= htmlspecialchars($page_title ?? '') ?></h1>
    </div>
    <div class="cm-header-center">
        <?php if (!empty($annee_academique['est_active'])): ?>
        <span class="cm-academic-year">
            <i class="fas fa-calendar-alt"></i>
            <?= htmlspecialchars($annee_academique['libelle'] ?? '') ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="cm-header-right">
        <div class="cm-notifications">
            <button class="cm-notif-btn">
                <i class="fas fa-bell"></i>
                <?php if (($notifications_count ?? 0) > 0): ?>
                <span class="cm-notif-badge"><?= $notifications_count ?></span>
                <?php endif; ?>
            </button>
        </div>
        <div class="cm-header-user dropdown is-right">
            <div class="dropdown-trigger">
                <button class="cm-user-btn">
                    <span class="cm-user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </span>
                    <span class="cm-user-name">
                        <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                    </span>
                    <span class="cm-user-role">
                        <?= htmlspecialchars($user['groupe'] ?? '') ?>
                    </span>
                </button>
            </div>
            <div class="dropdown-menu">
                <div class="dropdown-content">
                    <a href="?page=profil" class="dropdown-item">
                        <span class="icon"><i class="fas fa-user-circle"></i></span>
                        <span>Mon Profil</span>
                    </a>
                    <a href="?page=parametres" class="dropdown-item">
                        <span class="icon"><i class="fas fa-cog"></i></span>
                        <span>Paramètres</span>
                    </a>
                    <hr class="dropdown-divider">
                    <a href="?page=logout" class="dropdown-item has-text-danger">
                        <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                        <span>Déconnexion</span>
                    </a>
                    <a href="/parametres" class="dropdown-item">
                        <i class="fas fa-cog"></i> Paramètres
                    </a>
                    <hr class="dropdown-divider">
                    <a href="/logout" class="dropdown-item has-text-danger">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
