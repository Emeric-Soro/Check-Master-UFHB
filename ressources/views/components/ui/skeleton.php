<?php
/**
 * CheckMaster Premium - Skeleton Component
 * 
 * États de chargement visuels pendant les requêtes AJAX.
 */

/**
 * Text skeleton line
 */
function renderSkeleton(string $width = '100%', string $height = '1rem'): string {
    return sprintf(
        '<div class="skeleton" style="width: %s; height: %s;"></div>',
        htmlspecialchars($width),
        htmlspecialchars($height)
    );
}

/**
 * Multiple text lines skeleton
 */
function renderSkeletonText(int $lines = 3): string {
    $html = '';
    $widths = ['100%', '90%', '75%', '85%', '60%'];
    
    for ($i = 0; $i < $lines; $i++) {
        $width = $widths[$i % count($widths)];
        $html .= '<div class="skeleton skeleton-text" style="width: ' . $width . ';"></div>';
    }
    
    return $html;
}

/**
 * Title skeleton
 */
function renderSkeletonTitle(): string {
    return '<div class="skeleton skeleton-title"></div>';
}

/**
 * Avatar skeleton
 */
function renderSkeletonAvatar(string $size = '3rem'): string {
    return sprintf(
        '<div class="skeleton skeleton-avatar" style="width: %s; height: %s;"></div>',
        htmlspecialchars($size),
        htmlspecialchars($size)
    );
}

/**
 * Card skeleton
 */
function renderSkeletonCard(): string {
    return '
    <div class="card p-6">
        <div class="flex items-center gap-md mb-4">
            <div class="skeleton skeleton-avatar"></div>
            <div class="flex-1">
                <div class="skeleton skeleton-title" style="width: 40%;"></div>
                <div class="skeleton skeleton-text" style="width: 60%;"></div>
            </div>
        </div>
        <div class="skeleton skeleton-text"></div>
        <div class="skeleton skeleton-text" style="width: 80%;"></div>
        <div class="skeleton skeleton-text" style="width: 60%;"></div>
    </div>';
}

/**
 * Stats card skeleton
 */
function renderSkeletonStatsCard(): string {
    return '
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="skeleton" style="width: 3rem; height: 3rem; border-radius: var(--radius);"></div>
        </div>
        <div class="skeleton skeleton-text mt-3" style="width: 60%;"></div>
        <div class="skeleton" style="width: 40%; height: 2rem; margin-top: 0.5rem;"></div>
    </div>';
}

/**
 * Stats grid skeleton
 */
function renderSkeletonStatsGrid(int $count = 4): string {
    $html = '<div class="stats-grid">';
    for ($i = 0; $i < $count; $i++) {
        $html .= renderSkeletonStatsCard();
    }
    $html .= '</div>';
    return $html;
}

/**
 * Table row skeleton
 */
function renderSkeletonTableRow(int $columns = 5): string {
    $html = '<tr>';
    for ($i = 0; $i < $columns; $i++) {
        $width = ($i === 0) ? '40%' : (($i === $columns - 1) ? '20%' : '60%');
        $html .= '<td><div class="skeleton skeleton-text" style="width: ' . $width . ';"></div></td>';
    }
    $html .= '</tr>';
    return $html;
}

/**
 * Table skeleton
 */
function renderSkeletonTable(int $rows = 5, int $columns = 5): string {
    $html = '<div class="table-wrapper">';
    $html .= '<table class="data-table">';
    
    // Header
    $html .= '<thead><tr>';
    for ($i = 0; $i < $columns; $i++) {
        $html .= '<th><div class="skeleton" style="width: 80%; height: 1rem;"></div></th>';
    }
    $html .= '</tr></thead>';
    
    // Body
    $html .= '<tbody>';
    for ($i = 0; $i < $rows; $i++) {
        $html .= renderSkeletonTableRow($columns);
    }
    $html .= '</tbody></table></div>';
    
    return $html;
}

/**
 * Form skeleton
 */
function renderSkeletonForm(int $fields = 4): string {
    $html = '<div class="space-y-4">';
    
    for ($i = 0; $i < $fields; $i++) {
        $html .= '
        <div class="form-group">
            <div class="skeleton" style="width: 30%; height: 1rem; margin-bottom: 0.5rem;"></div>
            <div class="skeleton" style="width: 100%; height: 2.5rem; border-radius: var(--radius);"></div>
        </div>';
    }
    
    // Submit button
    $html .= '
    <div class="flex justify-end gap-2 mt-6">
        <div class="skeleton" style="width: 5rem; height: 2.5rem; border-radius: var(--radius);"></div>
        <div class="skeleton" style="width: 6rem; height: 2.5rem; border-radius: var(--radius);"></div>
    </div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Dashboard skeleton (complete page)
 */
function renderSkeletonDashboard(): string {
    $html = '';
    
    // Stats grid
    $html .= renderSkeletonStatsGrid(4);
    
    // Two column layout
    $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-lg mt-lg">';
    
    // Chart skeleton
    $html .= '
    <div class="card">
        <div class="card-header">
            <div class="skeleton skeleton-title" style="width: 40%;"></div>
        </div>
        <div class="card-content">
            <div class="skeleton" style="width: 100%; height: 200px;"></div>
        </div>
    </div>';
    
    // List skeleton
    $html .= '
    <div class="card">
        <div class="card-header">
            <div class="skeleton skeleton-title" style="width: 50%;"></div>
        </div>
        <div class="card-content">';
    
    for ($i = 0; $i < 4; $i++) {
        $html .= '
        <div class="flex items-center gap-md py-3 border-b last:border-0">
            <div class="skeleton skeleton-avatar"></div>
            <div class="flex-1">
                <div class="skeleton skeleton-text" style="width: 70%;"></div>
                <div class="skeleton skeleton-text" style="width: 40%; margin-top: 0.25rem;"></div>
            </div>
        </div>';
    }
    
    $html .= '</div></div>';
    $html .= '</div>';
    
    return $html;
}
