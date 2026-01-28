<?php
/**
 * CheckMaster Premium - Components Index
 * 
 * Ce fichier charge tous les composants disponibles.
 * Incluez ce fichier une seule fois dans votre layout pour avoir accès à tous les composants.
 */

$componentsPath = __DIR__;

// UI Components
require_once $componentsPath . '/ui/button.php';
require_once $componentsPath . '/ui/badge.php';
require_once $componentsPath . '/ui/card.php';
require_once $componentsPath . '/ui/stats-card.php';
require_once $componentsPath . '/ui/data-table.php';
require_once $componentsPath . '/ui/pagination.php';
require_once $componentsPath . '/ui/alert.php';
require_once $componentsPath . '/ui/modal.php';
require_once $componentsPath . '/ui/progress.php';
require_once $componentsPath . '/ui/avatar.php';
require_once $componentsPath . '/ui/timeline.php';
require_once $componentsPath . '/ui/tabs.php';
require_once $componentsPath . '/ui/dropdown.php';
require_once $componentsPath . '/ui/skeleton.php';
require_once $componentsPath . '/ui/tooltip.php';
require_once $componentsPath . '/ui/separator.php';
require_once $componentsPath . '/ui/search-bar.php';
require_once $componentsPath . '/ui/empty-state.php';

// Form Components
require_once $componentsPath . '/form/input.php';
require_once $componentsPath . '/form/select.php';
require_once $componentsPath . '/form/checkbox.php';

// Layout Components
require_once $componentsPath . '/layout/sidebar.php';
require_once $componentsPath . '/layout/navbar.php';
require_once $componentsPath . '/layout/breadcrumb.php';

/**
 * Helper: Get CSRF token if available
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (class_exists('\CheckMaster\Core\Csrf')) {
            return \CheckMaster\Core\Csrf::token();
        }
        return '';
    }
}

/**
 * Helper: Check permission
 */
if (!function_exists('canCreate')) {
    function canCreate(): bool {
        return function_exists('\canCreate') ? \canCreate() : true;
    }
}

if (!function_exists('canEdit')) {
    function canEdit(): bool {
        return function_exists('\canEdit') ? \canEdit() : true;
    }
}

if (!function_exists('canDelete')) {
    function canDelete(): bool {
        return function_exists('\canDelete') ? \canDelete() : true;
    }
}

if (!function_exists('canRead')) {
    function canRead(): bool {
        return function_exists('\canRead') ? \canRead() : true;
    }
}

/**
 * Render a page header with title and optional action button
 */
function renderPageHeader(
    string $title,
    string $actionButton = '',
    string $description = ''
): string {
    $descHtml = $description 
        ? '<p class="text-muted mt-1">' . htmlspecialchars($description) . '</p>' 
        : '';
    
    return sprintf(
        '<div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-foreground">%s</h1>
                %s
            </div>
            <div class="flex gap-sm">%s</div>
        </div>',
        htmlspecialchars($title),
        $descHtml,
        $actionButton
    );
}

/**
 * Render grid of clickable cards (for parameter pages)
 */
function renderCardGrid(array $cards, int $columns = 3): string {
    $colClass = [
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    ];
    
    $gridClass = $colClass[$columns] ?? $colClass[3];
    
    $html = '<div class="grid ' . $gridClass . ' gap-lg">';
    
    foreach ($cards as $card) {
        $html .= renderClickableCard(
            $card['title'] ?? '',
            $card['description'] ?? '',
            $card['link'] ?? '#',
            $card['icon'] ?? ''
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Wrap content in a card with optional header
 */
function wrapInCard(string $content, string $title = '', string $actions = ''): string {
    if (empty($title)) {
        return '<div class="card"><div class="card-content">' . $content . '</div></div>';
    }
    
    return renderFullCard($title, $content, $actions);
}

/**
 * Quick form wrapper
 */
function renderFormWrapper(
    string $content,
    string $action,
    string $method = 'POST',
    string $submitText = 'Enregistrer',
    string $cancelUrl = ''
): string {
    $cancelHtml = $cancelUrl 
        ? '<a href="' . htmlspecialchars($cancelUrl) . '" class="btn btn-secondary">Annuler</a>' 
        : '';
    
    return sprintf(
        '<form action="%s" method="%s" class="space-y-4">
            <input type="hidden" name="csrf_token" value="%s">
            %s
            <div class="flex justify-end gap-sm pt-4">
                %s
                <button type="submit" class="btn btn-primary">%s</button>
            </div>
        </form>',
        htmlspecialchars($action),
        htmlspecialchars($method),
        htmlspecialchars(csrf_token()),
        $content,
        $cancelHtml,
        htmlspecialchars($submitText)
    );
}
