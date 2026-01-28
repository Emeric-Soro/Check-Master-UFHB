<?php
/**
 * CheckMaster Premium - Sidebar Component
 * 
 * Composant barre latérale de navigation.
 * Boucle sur $menuHierarchique pour générer les menus/sous-menus.
 */

/**
 * Render the complete sidebar
 * 
 * @param array $menuHierarchique - Menu structure from MenuController
 * @param string $currentPage - Current page slug for active state
 * @param array $user - User information
 */
function renderSidebar(array $menuHierarchique, string $currentPage = '', array $user = []): string {
    $html = '<aside class="sidebar" id="sidebar">';
    
    // Header with logo
    $html .= renderSidebarHeader();
    
    // Navigation
    $html .= '<nav class="sidebar-nav">';
    $html .= renderSidebarMenu($menuHierarchique, $currentPage);
    $html .= '</nav>';
    
    // Footer with logout
    $html .= renderSidebarFooter();
    
    $html .= '</aside>';
    
    return $html;
}

/**
 * Render sidebar header with logo
 */
function renderSidebarHeader(): string {
    return '
    <div class="sidebar-header">
        <img src="image/logo_cm_sbg.png" alt="Logo CheckMaster" class="sidebar-logo">
        <span class="sidebar-brand">CHECK MASTER</span>
    </div>';
}

/**
 * Render sidebar menu from hierarchical structure
 */
function renderSidebarMenu(array $menuHierarchique, string $currentPage = ''): string {
    $html = '';
    
    // Get candidature status for students
    $statutCandidature = null;
    if (isset($_SESSION['id_GU']) && $_SESSION['id_GU'] == 13 && isset($_SESSION['num_etu'])) {
        if (class_exists('CandidatureSoutenance')) {
            $statutCandidature = CandidatureSoutenance::getStatutByEtudiant($_SESSION['num_etu']);
        }
    }
    
    foreach ($menuHierarchique as $index => $item) {
        $categorie = $item['categorie'];
        $fonctionnalites = $item['fonctionnalites'];
        
        $collapseId = 'collapse-' . $categorie->code_categorie;
        $hasActive = false;
        
        // Check if any item in this category is active
        foreach ($fonctionnalites as $fonc) {
            if (isMenuItemActive($currentPage, $fonc)) {
                $hasActive = true;
                break;
            }
        }
        
        $html .= '<div class="sidebar-section">';
        
        // Category toggle button
        $html .= sprintf(
            '<button type="button" class="sidebar-section-toggle" onclick="CM.Sidebar.toggleCategory(\'%s\')" id="btn-%s">
                <span class="flex items-center gap-sm">
                    <i class="%s"></i>
                    <span>%s</span>
                </span>
                <i class="fas fa-chevron-down text-xs transition-transform" id="icon-%s" style="%s"></i>
            </button>',
            $collapseId,
            $collapseId,
            htmlspecialchars($categorie->icone_categorie ?? 'fas fa-folder'),
            htmlspecialchars($categorie->lib_categorie ?? ''),
            $collapseId,
            ($index === 0 || $hasActive) ? 'transform: rotate(180deg)' : ''
        );
        
        // Category content
        $displayStyle = ($index === 0 || $hasActive) ? '' : 'display: none;';
        $html .= sprintf('<div id="%s" class="sidebar-section-content" style="%s">', $collapseId, $displayStyle);
        
        foreach ($fonctionnalites as $fonc) {
            $html .= renderSidebarLink($fonc, $currentPage, $statutCandidature);
        }
        
        $html .= '</div>'; // End section content
        $html .= '</div>'; // End section
    }
    
    return $html;
}

/**
 * Render a single sidebar link
 */
function renderSidebarLink($fonc, string $currentPage, ?string $statutCandidature = null): string {
    $isActive = isMenuItemActive($currentPage, $fonc);
    $url = $fonc->url_fonctionnalite ?? '#';
    $label = getMenuLabel($fonc);
    $icon = $fonc->icone_fonctionnalite ?? 'fa-circle';
    
    // Check if locked (for gestion_rapports before candidature validation)
    $isLocked = (strpos($url, 'gestion_rapports') !== false && $statutCandidature !== 'Validée');
    
    if ($isLocked) {
        return sprintf(
            '<span class="sidebar-link text-white/40 cursor-not-allowed" title="Accessible après validation de la candidature">
                <i class="fas fa-lock sidebar-link-icon"></i>
                <span>%s</span>
            </span>',
            htmlspecialchars($label)
        );
    }
    
    $activeClass = $isActive ? ' active' : '';
    
    // Check for children (sub-menu)
    $children = $fonc->children ?? [];
    if (!empty($children)) {
        return renderSidebarSubMenu($fonc, $children, $currentPage, $statutCandidature);
    }
    
    return sprintf(
        '<a href="%s" class="sidebar-link%s">
            <i class="fas %s sidebar-link-icon"></i>
            <span>%s</span>
        </a>',
        htmlspecialchars($url),
        $activeClass,
        htmlspecialchars($icon),
        htmlspecialchars($label)
    );
}

/**
 * Render a submenu
 */
function renderSidebarSubMenu($fonc, array $children, string $currentPage, ?string $statutCandidature = null): string {
    $isActive = isMenuItemActive($currentPage, $fonc);
    $label = getMenuLabel($fonc);
    $icon = $fonc->icone_fonctionnalite ?? 'fa-folder';
    $subId = 'sub-' . preg_replace('/[^a-zA-Z0-9_\-]/', '-', $fonc->code_fonctionnalite ?? uniqid());
    
    $html = sprintf(
        '<button type="button" class="sidebar-link w-full justify-between%s" onclick="CM.Sidebar.toggleSubMenu(\'%s\')">
            <span class="flex items-center gap-sm">
                <i class="fas %s sidebar-link-icon"></i>
                <span>%s</span>
            </span>
            <i class="fas fa-chevron-down text-xs transition-transform" id="icon-%s" style="%s"></i>
        </button>',
        $isActive ? ' active' : '',
        $subId,
        htmlspecialchars($icon),
        htmlspecialchars($label),
        $subId,
        $isActive ? 'transform: rotate(180deg)' : ''
    );
    
    $subDisplay = $isActive ? '' : 'display: none;';
    $html .= sprintf('<div id="%s" class="ml-4 space-y-1" style="%s">', $subId, $subDisplay);
    
    foreach ($children as $child) {
        $childActive = isPageActiveExact($currentPage, $child->url_fonctionnalite ?? '');
        $childUrl = $child->url_fonctionnalite ?? '#';
        $childLocked = (strpos($childUrl, 'gestion_rapports') !== false && $statutCandidature !== 'Validée');
        
        if ($childLocked) {
            $html .= sprintf(
                '<span class="sidebar-link text-white/40 cursor-not-allowed">
                    <i class="fas fa-lock sidebar-link-icon"></i>
                    <span>%s</span>
                </span>',
                htmlspecialchars(getMenuLabel($child))
            );
        } else {
            $html .= sprintf(
                '<a href="%s" class="sidebar-link%s">
                    <i class="fas %s sidebar-link-icon"></i>
                    <span>%s</span>
                </a>',
                htmlspecialchars($childUrl),
                $childActive ? ' active' : '',
                htmlspecialchars($child->icone_fonctionnalite ?? 'fa-circle'),
                htmlspecialchars(getMenuLabel($child))
            );
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render sidebar footer with logout button
 */
function renderSidebarFooter(): string {
    $csrfToken = class_exists('\CheckMaster\Core\Csrf') 
        ? \CheckMaster\Core\Csrf::token() 
        : '';
    
    return sprintf(
        '<div class="sidebar-footer">
            <form action="index.php?_path=/logout" method="POST" id="logoutForm">
                <input type="hidden" name="csrf_token" value="%s">
                <button type="submit" class="sidebar-link w-full text-white/70 hover:text-white">
                    <i class="fas fa-sign-out-alt sidebar-link-icon"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </div>',
        htmlspecialchars($csrfToken)
    );
}

/**
 * Helper: Check if menu item is active
 */
function isMenuItemActive(string $currentPage, $fonc): bool {
    if (isPageActiveExact($currentPage, $fonc->url_fonctionnalite ?? '')) {
        return true;
    }
    
    $children = $fonc->children ?? [];
    foreach ($children as $child) {
        if (isPageActiveExact($currentPage, $child->url_fonctionnalite ?? '')) {
            return true;
        }
    }
    
    return false;
}

/**
 * Helper: Check if page is exactly active
 */
function isPageActiveExact(string $currentPage, string $url): bool {
    $query = parse_url($url, PHP_URL_QUERY);
    if (!$query) {
        return false;
    }
    
    $params = [];
    parse_str($query, $params);
    $pageName = $params['page'] ?? '';
    $action = $params['action'] ?? '';
    
    if (empty($pageName)) {
        return false;
    }
    
    $currentGetPage = $_GET['page'] ?? '';
    $currentGetAction = $_GET['action'] ?? '';
    
    $pageMatches = ($currentPage === $pageName) || ($currentGetPage === $pageName);
    
    if (!$pageMatches) {
        return false;
    }
    
    if (!empty($action)) {
        return $currentGetAction === $action;
    }
    
    return true;
}

/**
 * Helper: Get menu label
 */
function getMenuLabel($fonc): string {
    $label = trim($fonc->label_fonctionnalite ?? '');
    $lib = trim($fonc->lib_fonctionnalite ?? '');
    $code = trim($fonc->code_fonctionnalite ?? '');
    
    if ($label !== '' && $label !== $code) {
        return $label;
    }
    if ($lib !== '' && $lib !== $code) {
        return $lib;
    }
    if ($code !== '') {
        return prettyLabelFromCode($code);
    }
    return '';
}

/**
 * Helper: Convert code to pretty label
 */
function prettyLabelFromCode(string $code): string {
    $code = preg_replace('/^(SCOL|ETU|COM|ADM)_/u', '', $code);
    $code = str_replace('_', ' ', $code);
    $code = strtolower($code);
    return mb_convert_case($code, MB_CASE_TITLE, 'UTF-8');
}
