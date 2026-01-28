<?php
/**
 * CheckMaster Premium - Stats Card Component
 * 
 * Cartes de statistiques/KPI avec icônes et tendances.
 */

/**
 * Render a stats card
 * @param string $label - Label de la statistique
 * @param mixed $value - Valeur à afficher
 * @param string $icon - Icône FontAwesome (sans 'fa-')
 * @param string $type - Type de couleur (primary, success, warning, danger, info)
 * @param array $trend - Tendance optionnelle ['value' => '+12%', 'direction' => 'up']
 */
function renderStatsCard(
    string $label, 
    $value, 
    string $icon = 'chart-bar', 
    string $type = 'primary',
    array $trend = []
): string {
    $trendHtml = '';
    if (!empty($trend)) {
        $direction = $trend['direction'] ?? 'up';
        $trendValue = $trend['value'] ?? '';
        $trendIcon = $direction === 'up' ? 'fa-arrow-up' : 'fa-arrow-down';
        $trendHtml = sprintf(
            '<span class="stat-card-trend %s"><i class="fas %s"></i> %s</span>',
            $direction,
            $trendIcon,
            htmlspecialchars($trendValue)
        );
    }
    
    return sprintf(
        '<div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon %s">
                    <i class="fas fa-%s"></i>
                </div>
            </div>
            <div class="stat-card-label">%s</div>
            <div class="stat-card-value">%s</div>
            %s
        </div>',
        htmlspecialchars($type),
        htmlspecialchars($icon),
        htmlspecialchars($label),
        htmlspecialchars((string) $value),
        $trendHtml
    );
}

/**
 * Render a grid of stats cards
 * @param array $stats - Array of stats configurations
 */
function renderStatsGrid(array $stats): string {
    $html = '<div class="stats-grid">';
    
    foreach ($stats as $stat) {
        $html .= renderStatsCard(
            $stat['label'] ?? '',
            $stat['value'] ?? 0,
            $stat['icon'] ?? 'chart-bar',
            $stat['type'] ?? 'primary',
            $stat['trend'] ?? []
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Stats card with progress bar
 */
function renderStatsCardProgress(
    string $label,
    int $current,
    int $total,
    string $icon = 'tasks',
    string $type = 'primary'
): string {
    $percentage = $total > 0 ? round(($current / $total) * 100) : 0;
    
    return sprintf(
        '<div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon %s">
                    <i class="fas fa-%s"></i>
                </div>
            </div>
            <div class="stat-card-label">%s</div>
            <div class="stat-card-value">%d / %d</div>
            <div class="progress mt-md">
                <div class="progress-bar %s" style="width: %d%%"></div>
            </div>
            <span class="text-muted text-sm mt-sm">%d%% complété</span>
        </div>',
        htmlspecialchars($type),
        htmlspecialchars($icon),
        htmlspecialchars($label),
        $current,
        $total,
        htmlspecialchars($type),
        $percentage,
        $percentage
    );
}

/**
 * Mini stats card (compact)
 */
function renderMiniStats(string $label, $value, string $icon = '', string $type = 'primary'): string {
    $iconHtml = $icon 
        ? '<i class="fas fa-' . htmlspecialchars($icon) . ' text-' . htmlspecialchars($type) . '"></i>' 
        : '';
    
    return sprintf(
        '<div class="flex items-center gap-md p-3 bg-muted-light rounded-lg">
            %s
            <div>
                <div class="text-sm text-muted">%s</div>
                <div class="font-bold">%s</div>
            </div>
        </div>',
        $iconHtml,
        htmlspecialchars($label),
        htmlspecialchars((string) $value)
    );
}
