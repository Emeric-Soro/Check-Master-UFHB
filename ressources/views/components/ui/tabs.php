<?php
/**
 * CheckMaster Premium - Tabs Component
 * 
 * Composant onglets pour la navigation entre vues.
 */

/**
 * Render tabs navigation
 * 
 * @param array $tabs - Array of tabs [['id' => '', 'label' => '', 'icon' => ''], ...]
 * @param string $activeTab - ID of the active tab
 * @param string $tabsId - Unique ID for the tabs group
 */
function renderTabs(array $tabs, string $activeTab = '', string $tabsId = 'tabs'): string {
    // Default to first tab if no active specified
    if (empty($activeTab) && !empty($tabs)) {
        $activeTab = $tabs[0]['id'] ?? '';
    }
    
    $html = sprintf('<div class="tabs" data-tabs="%s">', htmlspecialchars($tabsId));
    $html .= '<div class="tabs-list" role="tablist">';
    
    foreach ($tabs as $tab) {
        $id = $tab['id'] ?? '';
        $label = $tab['label'] ?? '';
        $icon = $tab['icon'] ?? '';
        $count = $tab['count'] ?? null;
        
        $isActive = ($id === $activeTab);
        $activeClass = $isActive ? ' active' : '';
        
        $iconHtml = $icon 
            ? '<i class="fas ' . htmlspecialchars($icon) . ' mr-2"></i>' 
            : '';
        
        $countHtml = ($count !== null) 
            ? '<span class="badge badge-muted ml-2">' . (int)$count . '</span>' 
            : '';
        
        $html .= sprintf(
            '<button type="button" class="tab%s" data-tab="%s" role="tab" aria-selected="%s">
                %s%s%s
            </button>',
            $activeClass,
            htmlspecialchars($id),
            $isActive ? 'true' : 'false',
            $iconHtml,
            htmlspecialchars($label),
            $countHtml
        );
    }
    
    $html .= '</div></div>';
    
    return $html;
}

/**
 * Render a tab panel container
 */
function renderTabPanel(string $id, string $content, bool $active = false): string {
    $activeClass = $active ? ' active' : '';
    
    return sprintf(
        '<div class="tab-panel%s" data-tab-panel="%s" role="tabpanel">
            %s
        </div>',
        $activeClass,
        htmlspecialchars($id),
        $content
    );
}

/**
 * Complete tabs with panels
 */
function renderTabsWithPanels(array $tabs, array $panels, string $activeTab = ''): string {
    if (empty($activeTab) && !empty($tabs)) {
        $activeTab = $tabs[0]['id'] ?? '';
    }
    
    $tabsId = 'tabs-' . uniqid();
    
    $html = '<div data-tab-container="' . $tabsId . '">';
    
    // Tabs navigation
    $html .= renderTabs($tabs, $activeTab, $tabsId);
    
    // Panels
    foreach ($panels as $id => $content) {
        $html .= renderTabPanel($id, $content, $id === $activeTab);
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * URL-based tabs (for page navigation)
 */
function renderUrlTabs(array $tabs, string $currentTab = ''): string {
    $html = '<div class="tabs">';
    $html .= '<div class="tabs-list">';
    
    foreach ($tabs as $tab) {
        $id = $tab['id'] ?? '';
        $label = $tab['label'] ?? '';
        $url = $tab['url'] ?? '#';
        $icon = $tab['icon'] ?? '';
        
        $isActive = ($id === $currentTab);
        $activeClass = $isActive ? ' active' : '';
        
        $iconHtml = $icon 
            ? '<i class="fas ' . htmlspecialchars($icon) . ' mr-2"></i>' 
            : '';
        
        $html .= sprintf(
            '<a href="%s" class="tab%s">%s%s</a>',
            htmlspecialchars($url),
            $activeClass,
            $iconHtml,
            htmlspecialchars($label)
        );
    }
    
    $html .= '</div></div>';
    
    return $html;
}

/**
 * Pill tabs (rounded style)
 */
function renderPillTabs(array $tabs, string $activeTab = ''): string {
    if (empty($activeTab) && !empty($tabs)) {
        $activeTab = $tabs[0]['id'] ?? '';
    }
    
    $html = '<div class="flex gap-sm bg-muted-light p-1 rounded-lg inline-flex">';
    
    foreach ($tabs as $tab) {
        $id = $tab['id'] ?? '';
        $label = $tab['label'] ?? '';
        
        $isActive = ($id === $activeTab);
        $activeClass = $isActive 
            ? 'bg-white shadow text-foreground' 
            : 'text-muted hover:text-foreground';
        
        $html .= sprintf(
            '<button type="button" class="px-4 py-2 text-sm font-medium rounded-md transition-all %s" data-tab="%s">
                %s
            </button>',
            $activeClass,
            htmlspecialchars($id),
            htmlspecialchars($label)
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Vertical tabs
 */
function renderVerticalTabs(array $tabs, string $activeTab = ''): string {
    if (empty($activeTab) && !empty($tabs)) {
        $activeTab = $tabs[0]['id'] ?? '';
    }
    
    $html = '<div class="flex flex-col gap-1">';
    
    foreach ($tabs as $tab) {
        $id = $tab['id'] ?? '';
        $label = $tab['label'] ?? '';
        $icon = $tab['icon'] ?? '';
        
        $isActive = ($id === $activeTab);
        $activeClass = $isActive 
            ? 'bg-accent-light text-accent border-l-2 border-accent' 
            : 'text-muted hover:text-foreground hover:bg-muted-light';
        
        $iconHtml = $icon 
            ? '<i class="fas ' . htmlspecialchars($icon) . ' w-5 text-center"></i>' 
            : '';
        
        $html .= sprintf(
            '<button type="button" class="flex items-center gap-3 px-4 py-2 text-sm font-medium text-left rounded-r-md transition-all %s" data-tab="%s">
                %s<span>%s</span>
            </button>',
            $activeClass,
            htmlspecialchars($id),
            $iconHtml,
            htmlspecialchars($label)
        );
    }
    
    $html .= '</div>';
    
    return $html;
}
