<?php
/**
 * CheckMaster Premium - Progress Component
 * 
 * Barres de progression et indicateurs.
 */

/**
 * Render a progress bar
 * 
 * @param int $value - Valeur actuelle (0-100)
 * @param string $type - Type de couleur (primary, success, warning, danger)
 * @param string $size - Taille (default, lg, xl)
 * @param bool $showLabel - Afficher le pourcentage
 */
function renderProgress(
    int $value,
    string $type = 'primary',
    string $size = '',
    bool $showLabel = false
): string {
    $value = max(0, min(100, $value)); // Clamp 0-100
    $sizeClass = $size ? ' progress-' . $size : '';
    
    $labelHtml = $showLabel 
        ? '<span class="text-sm text-muted ml-2">' . $value . '%</span>' 
        : '';
    
    return sprintf(
        '<div class="flex items-center">
            <div class="progress flex-1%s">
                <div class="progress-bar %s" style="width: %d%%"></div>
            </div>
            %s
        </div>',
        $sizeClass,
        htmlspecialchars($type),
        $value,
        $labelHtml
    );
}

/**
 * Progress with label and value
 */
function renderProgressLabeled(
    string $label,
    int $current,
    int $total,
    string $type = 'primary'
): string {
    $percentage = $total > 0 ? round(($current / $total) * 100) : 0;
    
    return sprintf(
        '<div class="mb-md">
            <div class="flex justify-between mb-sm">
                <span class="text-sm font-medium">%s</span>
                <span class="text-sm text-muted">%d / %d</span>
            </div>
            <div class="progress">
                <div class="progress-bar %s" style="width: %d%%"></div>
            </div>
        </div>',
        htmlspecialchars($label),
        $current,
        $total,
        htmlspecialchars($type),
        $percentage
    );
}

/**
 * Multiple progress bars (stacked)
 */
function renderProgressStacked(array $segments): string {
    $html = '<div class="progress">';
    
    foreach ($segments as $segment) {
        $value = $segment['value'] ?? 0;
        $type = $segment['type'] ?? 'primary';
        
        $html .= sprintf(
            '<div class="progress-bar %s" style="width: %d%%"></div>',
            htmlspecialchars($type),
            $value
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Circular progress (using CSS)
 */
function renderCircularProgress(int $value, string $size = '4rem', string $type = 'primary'): string {
    $value = max(0, min(100, $value));
    $circumference = 2 * 3.14159 * 45; // r = 45
    $offset = $circumference - ($value / 100) * $circumference;
    
    $colors = [
        'primary' => 'var(--primary)',
        'success' => 'var(--success)',
        'warning' => 'var(--warning)',
        'danger' => 'var(--danger)'
    ];
    
    $color = $colors[$type] ?? $colors['primary'];
    
    return sprintf(
        '<div class="relative inline-flex items-center justify-center" style="width: %s; height: %s;">
            <svg class="transform -rotate-90" style="width: 100%%; height: 100%%;">
                <circle cx="50%%" cy="50%%" r="45%%" fill="none" stroke="var(--border)" stroke-width="8"></circle>
                <circle cx="50%%" cy="50%%" r="45%%" fill="none" stroke="%s" stroke-width="8" 
                    stroke-dasharray="%f" stroke-dashoffset="%f" stroke-linecap="round"></circle>
            </svg>
            <span class="absolute text-sm font-bold">%d%%</span>
        </div>',
        htmlspecialchars($size),
        htmlspecialchars($size),
        $color,
        $circumference,
        $offset,
        $value
    );
}

/**
 * Step progress (wizard)
 */
function renderStepProgress(array $steps, int $currentStep): string {
    $html = '<div class="flex items-center justify-between mb-lg">';
    
    foreach ($steps as $index => $step) {
        $stepNum = $index + 1;
        $isComplete = $stepNum < $currentStep;
        $isActive = $stepNum === $currentStep;
        
        $circleClass = $isComplete 
            ? 'bg-success text-white' 
            : ($isActive ? 'bg-primary text-white' : 'bg-muted-light text-muted');
        
        $html .= '<div class="flex items-center">';
        
        // Circle
        $html .= sprintf(
            '<div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold %s">
                %s
            </div>',
            $circleClass,
            $isComplete ? '<i class="fas fa-check"></i>' : $stepNum
        );
        
        // Label
        $labelClass = ($isActive || $isComplete) ? 'text-foreground font-medium' : 'text-muted';
        $html .= sprintf(
            '<span class="ml-2 text-sm %s hidden sm:inline">%s</span>',
            $labelClass,
            htmlspecialchars($step)
        );
        
        $html .= '</div>';
        
        // Connector (except last)
        if ($index < count($steps) - 1) {
            $lineClass = $isComplete ? 'bg-success' : 'bg-muted-light';
            $html .= sprintf('<div class="flex-1 h-1 mx-4 %s"></div>', $lineClass);
        }
    }
    
    $html .= '</div>';
    
    return $html;
}
