<?php
/**
 * CheckMaster Premium - Navbar Component
 * 
 * Barre de navigation supérieure avec titre de page et informations utilisateur.
 */

/**
 * Render the top navbar
 * 
 * @param string $pageTitle - Titre de la page courante
 * @param array $user - Informations utilisateur ['name' => '', 'role' => '']
 * @param array $options - Options additionnelles
 */
function renderNavbar(string $pageTitle, array $user = [], array $options = []): string {
    $defaults = [
        'showNotifications' => true,
        'showSearch' => false,
        'showMobileToggle' => true,
        'breadcrumb' => []
    ];
    
    $opts = array_merge($defaults, $options);
    
    // Get user info from session if not provided
    $userName = $user['name'] ?? $_SESSION['nom_utilisateur'] ?? 'Utilisateur';
    $userRole = $user['role'] ?? $_SESSION['lib_GU'] ?? '';
    
    $html = '<header class="navbar">';
    
    // Left side: Mobile toggle + Title
    $html .= '<div class="flex items-center gap-md">';
    
    // Mobile menu toggle
    if ($opts['showMobileToggle']) {
        $html .= '
        <button type="button" class="md:hidden btn btn-ghost" onclick="CM.Sidebar.toggleMobile()" aria-label="Toggle menu">
            <i class="fas fa-bars text-xl"></i>
        </button>';
    }
    
    // Page title and optional breadcrumb
    $html .= '<div>';
    
    if (!empty($opts['breadcrumb'])) {
        $html .= renderBreadcrumb($opts['breadcrumb']);
    }
    
    $html .= sprintf(
        '<h1 class="navbar-title">%s</h1>',
        htmlspecialchars($pageTitle)
    );
    
    $html .= '</div>';
    $html .= '</div>';
    
    // Right side: Actions + User info
    $html .= '<div class="navbar-actions">';
    
    // Search (optional)
    if ($opts['showSearch']) {
        $html .= '
        <div class="hidden md:block">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Rechercher...">
            </div>
        </div>';
    }
    
    // Notifications
    if ($opts['showNotifications']) {
        $html .= '
        <button type="button" class="btn btn-ghost relative" aria-label="Notifications">
            <i class="fas fa-bell text-xl text-primary/70"></i>
        </button>';
    }
    
    // Divider
    $html .= '<div class="navbar-divider"></div>';
    
    // User info
    $html .= sprintf(
        '<div class="navbar-user">
            <div class="navbar-user-info hidden md:block">
                <span class="navbar-user-name">Bienvenue, %s</span>
                <span class="navbar-user-role">%s</span>
            </div>
        </div>',
        htmlspecialchars($userName),
        htmlspecialchars($userRole)
    );
    
    $html .= '</div>';
    $html .= '</header>';
    
    return $html;
}

/**
 * Render compact navbar (for smaller screens)
 */
function renderNavbarCompact(string $pageTitle): string {
    $userName = $_SESSION['nom_utilisateur'] ?? 'Utilisateur';
    
    return sprintf(
        '<header class="navbar py-3">
            <div class="flex items-center gap-md">
                <button type="button" class="btn btn-ghost md:hidden" onclick="CM.Sidebar.toggleMobile()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="navbar-title text-lg">%s</h1>
            </div>
            <div class="flex items-center gap-sm">
                <span class="text-sm font-medium text-primary hidden sm:inline">%s</span>
                <button type="button" class="btn btn-ghost">
                    <i class="fas fa-bell"></i>
                </button>
            </div>
        </header>',
        htmlspecialchars($pageTitle),
        htmlspecialchars($userName)
    );
}

/**
 * Navbar with actions
 */
function renderNavbarWithActions(string $pageTitle, string $actions = ''): string {
    $userName = $_SESSION['nom_utilisateur'] ?? 'Utilisateur';
    $userRole = $_SESSION['lib_GU'] ?? '';
    
    return sprintf(
        '<header class="navbar">
            <div class="flex items-center gap-md">
                <button type="button" class="md:hidden btn btn-ghost" onclick="CM.Sidebar.toggleMobile()">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="navbar-title">%s</h1>
            </div>
            <div class="navbar-actions">
                <div class="flex items-center gap-sm">
                    %s
                </div>
                <div class="navbar-divider"></div>
                <div class="navbar-user">
                    <div class="navbar-user-info">
                        <span class="navbar-user-name">%s</span>
                        <span class="navbar-user-role">%s</span>
                    </div>
                </div>
            </div>
        </header>',
        htmlspecialchars($pageTitle),
        $actions,
        htmlspecialchars($userName),
        htmlspecialchars($userRole)
    );
}
