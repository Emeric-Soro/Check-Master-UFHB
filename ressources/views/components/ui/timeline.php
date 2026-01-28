<?php
/**
 * CheckMaster Premium - Timeline Component
 * 
 * Composant timeline pour les workflows et historiques.
 */

/**
 * Render a timeline
 * 
 * @param array $items - Array of timeline items
 *   [
 *     ['title' => '', 'description' => '', 'date' => '', 'status' => 'complete|active|pending']
 *   ]
 */
function renderTimeline(array $items): string {
    $html = '<div class="timeline">';
    
    foreach ($items as $item) {
        $status = $item['status'] ?? 'pending';
        $markerClass = '';
        
        if ($status === 'complete') {
            $markerClass = ' complete';
        } elseif ($status === 'active') {
            $markerClass = ' active';
        }
        
        $dateHtml = isset($item['date']) 
            ? '<span class="timeline-date">' . htmlspecialchars($item['date']) . '</span>' 
            : '';
        
        $descHtml = isset($item['description']) 
            ? '<p class="timeline-description">' . htmlspecialchars($item['description']) . '</p>' 
            : '';
        
        $html .= sprintf(
            '<div class="timeline-item">
                <div class="timeline-marker%s"></div>
                <div class="timeline-content">
                    <h4 class="timeline-title">%s</h4>
                    %s
                    %s
                </div>
            </div>',
            $markerClass,
            htmlspecialchars($item['title'] ?? ''),
            $descHtml,
            $dateHtml
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Workflow timeline (horizontal)
 */
function renderWorkflowTimeline(array $steps, int $currentStep): string {
    $html = '<div class="flex items-start justify-between">';
    
    foreach ($steps as $index => $step) {
        $stepNum = $index + 1;
        $isComplete = $stepNum < $currentStep;
        $isActive = $stepNum === $currentStep;
        
        // Status classes
        if ($isComplete) {
            $circleClass = 'bg-success border-success text-white';
            $lineClass = 'bg-success';
        } elseif ($isActive) {
            $circleClass = 'bg-primary border-primary text-white';
            $lineClass = 'bg-muted-light';
        } else {
            $circleClass = 'bg-muted-light border-muted text-muted';
            $lineClass = 'bg-muted-light';
        }
        
        $html .= '<div class="flex-1 text-center">';
        
        // Circle and line container
        $html .= '<div class="flex items-center">';
        
        // Line before (except first)
        if ($index > 0) {
            $prevComplete = $stepNum <= $currentStep;
            $html .= sprintf(
                '<div class="flex-1 h-1 %s"></div>',
                $prevComplete ? 'bg-success' : 'bg-muted-light'
            );
        }
        
        // Circle
        $html .= sprintf(
            '<div class="w-10 h-10 rounded-full border-2 flex items-center justify-center font-bold %s">
                %s
            </div>',
            $circleClass,
            $isComplete ? '<i class="fas fa-check"></i>' : $stepNum
        );
        
        // Line after (except last)
        if ($index < count($steps) - 1) {
            $html .= sprintf('<div class="flex-1 h-1 %s"></div>', $lineClass);
        }
        
        $html .= '</div>';
        
        // Label
        $labelClass = ($isActive || $isComplete) ? 'font-medium text-foreground' : 'text-muted';
        $html .= sprintf(
            '<div class="mt-2 text-sm %s">%s</div>',
            $labelClass,
            htmlspecialchars($step['title'] ?? $step)
        );
        
        // Description if provided
        if (isset($step['description'])) {
            $html .= sprintf(
                '<div class="text-xs text-muted mt-1">%s</div>',
                htmlspecialchars($step['description'])
            );
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Activity timeline (with icons)
 */
function renderActivityTimeline(array $activities): string {
    $html = '<div class="space-y-4">';
    
    foreach ($activities as $activity) {
        $icon = $activity['icon'] ?? 'fa-circle';
        $type = $activity['type'] ?? 'info';
        $time = $activity['time'] ?? '';
        $user = $activity['user'] ?? '';
        
        $iconColors = [
            'success' => 'bg-success-light text-success',
            'warning' => 'bg-warning-light text-warning',
            'danger'  => 'bg-danger-light text-danger',
            'info'    => 'bg-info-light text-info',
            'primary' => 'bg-accent-light text-accent'
        ];
        
        $iconClass = $iconColors[$type] ?? $iconColors['info'];
        
        $html .= sprintf(
            '<div class="flex gap-md">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 %s">
                    <i class="fas %s text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm">%s</p>
                    <div class="flex items-center gap-2 mt-1">
                        %s
                        <span class="text-xs text-muted">%s</span>
                    </div>
                </div>
            </div>',
            $iconClass,
            htmlspecialchars($icon),
            htmlspecialchars($activity['description'] ?? ''),
            $user ? '<span class="text-xs font-medium">' . htmlspecialchars($user) . '</span><span class="text-muted">·</span>' : '',
            htmlspecialchars($time)
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Soutenance workflow specific timeline
 */
function renderSoutenanceWorkflow(array $statuses): string {
    $steps = [
        ['key' => 'depot', 'title' => 'Dépôt', 'icon' => 'fa-upload'],
        ['key' => 'validation_com', 'title' => 'Validation COM', 'icon' => 'fa-user-check'],
        ['key' => 'commission', 'title' => 'Commission', 'icon' => 'fa-users'],
        ['key' => 'soutenance', 'title' => 'Soutenance', 'icon' => 'fa-graduation-cap']
    ];
    
    $html = '<div class="flex items-center justify-between">';
    
    foreach ($steps as $index => $step) {
        $status = $statuses[$step['key']] ?? 'pending';
        
        if ($status === 'complete' || $status === 'valide') {
            $circleClass = 'bg-success text-white';
        } elseif ($status === 'active' || $status === 'en_cours') {
            $circleClass = 'bg-primary text-white animate-pulse';
        } elseif ($status === 'rejected' || $status === 'rejete') {
            $circleClass = 'bg-danger text-white';
        } else {
            $circleClass = 'bg-muted-light text-muted';
        }
        
        $html .= '<div class="text-center">';
        $html .= sprintf(
            '<div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto %s">
                <i class="fas %s"></i>
            </div>
            <div class="mt-2 text-xs font-medium">%s</div>',
            $circleClass,
            htmlspecialchars($step['icon']),
            htmlspecialchars($step['title'])
        );
        $html .= '</div>';
        
        // Connector
        if ($index < count($steps) - 1) {
            $lineStatus = ($status === 'complete' || $status === 'valide') ? 'bg-success' : 'bg-muted-light';
            $html .= sprintf('<div class="flex-1 h-1 mx-2 %s"></div>', $lineStatus);
        }
    }
    
    $html .= '</div>';
    
    return $html;
}
