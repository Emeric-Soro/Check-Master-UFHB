<?php
/**
 * CheckMaster Premium - Dropdown Component
 * 
 * Menus déroulants pour actions groupées.
 */

/**
 * Render a dropdown menu
 * 
 * @param string $trigger - HTML du bouton déclencheur
 * @param array $items - Items du menu [['label' => '', 'icon' => '', 'url' => '', 'type' => ''], ...]
 * @param string $id - ID unique du dropdown
 */
function renderDropdown(string $trigger, array $items, string $id = ''): string {
    $dropdownId = $id ?: 'dropdown-' . uniqid();
    
    $html = '<div class="dropdown" id="' . htmlspecialchars($dropdownId) . '">';
    
    // Trigger button
    $html .= '<div data-dropdown="' . htmlspecialchars($dropdownId) . '">' . $trigger . '</div>';
    
    // Menu
    $html .= '<div class="dropdown-menu" data-dropdown-menu="' . htmlspecialchars($dropdownId) . '">';
    
    foreach ($items as $item) {
        if (isset($item['divider']) && $item['divider']) {
            $html .= '<div class="dropdown-divider"></div>';
            continue;
        }
        
        $label = $item['label'] ?? '';
        $icon = $item['icon'] ?? '';
        $url = $item['url'] ?? '#';
        $type = $item['type'] ?? '';
        $onclick = $item['onclick'] ?? '';
        
        $typeClass = $type === 'danger' ? ' danger' : '';
        $iconHtml = $icon ? '<i class="fas ' . htmlspecialchars($icon) . '"></i>' : '';
        $onclickAttr = $onclick ? ' onclick="' . htmlspecialchars($onclick) . '"' : '';
        
        if ($url === '#' || !empty($onclick)) {
            $html .= sprintf(
                '<button type="button" class="dropdown-item%s"%s>%s%s</button>',
                $typeClass,
                $onclickAttr,
                $iconHtml,
                htmlspecialchars($label)
            );
        } else {
            $html .= sprintf(
                '<a href="%s" class="dropdown-item%s">%s%s</a>',
                htmlspecialchars($url),
                $typeClass,
                $iconHtml,
                htmlspecialchars($label)
            );
        }
    }
    
    $html .= '</div></div>';
    
    return $html;
}

/**
 * Actions dropdown (common pattern for tables)
 */
function renderActionsDropdown(array $actions, string $id = ''): string {
    $trigger = '<button type="button" class="btn btn-ghost btn-sm">
        <i class="fas fa-ellipsis-v"></i>
    </button>';
    
    return renderDropdown($trigger, $actions, $id);
}

/**
 * Export dropdown
 */
function renderExportDropdown(string $baseUrl = ''): string {
    $items = [
        ['label' => 'Exporter en Excel', 'icon' => 'fa-file-excel', 'url' => $baseUrl . '&export=excel'],
        ['label' => 'Exporter en PDF', 'icon' => 'fa-file-pdf', 'url' => $baseUrl . '&export=pdf'],
        ['label' => 'Exporter en CSV', 'icon' => 'fa-file-csv', 'url' => $baseUrl . '&export=csv'],
        ['divider' => true],
        ['label' => 'Imprimer', 'icon' => 'fa-print', 'onclick' => 'window.print()']
    ];
    
    $trigger = '<button type="button" class="btn btn-outline">
        <i class="fas fa-download"></i> Exporter
    </button>';
    
    return renderDropdown($trigger, $items);
}

/**
 * User dropdown (for navbar)
 */
function renderUserDropdown(string $userName, string $userRole = '', string $avatarUrl = ''): string {
    $items = [
        ['label' => 'Mon profil', 'icon' => 'fa-user', 'url' => '?page=profil'],
        ['label' => 'Paramètres', 'icon' => 'fa-cog', 'url' => '?page=parametres'],
        ['divider' => true],
        ['label' => 'Déconnexion', 'icon' => 'fa-sign-out-alt', 'type' => 'danger', 'url' => 'logout.php']
    ];
    
    // Avatar or initials
    $initials = getInitials($userName);
    $avatarHtml = $avatarUrl 
        ? '<img src="' . htmlspecialchars($avatarUrl) . '" alt="" class="w-8 h-8 rounded-full">'
        : '<div class="avatar">' . htmlspecialchars($initials) . '</div>';
    
    $trigger = sprintf(
        '<button type="button" class="flex items-center gap-2 hover:bg-muted-light p-2 rounded-lg transition-colors">
            %s
            <div class="text-left hidden md:block">
                <div class="text-sm font-medium">%s</div>
                <div class="text-xs text-muted">%s</div>
            </div>
            <i class="fas fa-chevron-down text-xs text-muted"></i>
        </button>',
        $avatarHtml,
        htmlspecialchars($userName),
        htmlspecialchars($userRole)
    );
    
    return renderDropdown($trigger, $items, 'user-dropdown');
}

/**
 * Filter dropdown
 */
function renderFilterDropdown(array $filters, array $currentFilters = []): string {
    $activeCount = count(array_filter($currentFilters));
    $badge = $activeCount > 0 ? ' <span class="badge badge-primary">' . $activeCount . '</span>' : '';
    
    $trigger = '<button type="button" class="btn btn-outline">
        <i class="fas fa-filter"></i> Filtres' . $badge . '
    </button>';
    
    return renderDropdown($trigger, $filters);
}

/**
 * Helper: Get initials from name (if not already defined)
 */
if (!function_exists('getInitials')) {
    function getInitials(string $name): string {
        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
        }
        return strtoupper(mb_substr($name, 0, 2));
    }
}
