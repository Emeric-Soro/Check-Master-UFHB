<?php
/**
 * CheckMaster Premium - Card Component
 * 
 * Composant carte avec header, content et footer optionnels.
 */

/**
 * Render a card container
 * @param string $content - Contenu HTML de la carte
 * @param string $class - Classes CSS additionnelles
 */
function renderCard(string $content, string $class = ''): string {
    return sprintf(
        '<div class="card %s">%s</div>',
        htmlspecialchars($class),
        $content
    );
}

/**
 * Card Header
 */
function renderCardHeader(string $title, string $actions = '', string $description = ''): string {
    $descHtml = $description ? '<p class="card-description">' . htmlspecialchars($description) . '</p>' : '';
    
    return sprintf(
        '<div class="card-header">
            <div>
                <h3 class="card-title">%s</h3>
                %s
            </div>
            <div class="flex gap-sm">%s</div>
        </div>',
        htmlspecialchars($title),
        $descHtml,
        $actions
    );
}

/**
 * Card Content
 */
function renderCardContent(string $content): string {
    return sprintf('<div class="card-content">%s</div>', $content);
}

/**
 * Card Footer
 */
function renderCardFooter(string $content): string {
    return sprintf('<div class="card-footer">%s</div>', $content);
}

/**
 * Complete Card with all sections
 */
function renderFullCard(
    string $title, 
    string $content, 
    string $headerActions = '', 
    string $footerContent = '',
    string $description = '',
    string $class = ''
): string {
    $html = '<div class="card ' . htmlspecialchars($class) . '">';
    
    // Header
    if (!empty($title)) {
        $html .= renderCardHeader($title, $headerActions, $description);
    }
    
    // Content
    $html .= renderCardContent($content);
    
    // Footer
    if (!empty($footerContent)) {
        $html .= renderCardFooter($footerContent);
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Clickable Card (pour navigation)
 */
function renderClickableCard(
    string $title,
    string $description,
    string $href,
    string $icon = '',
    string $class = ''
): string {
    $iconHtml = '';
    if (!empty($icon)) {
        // Check if icon is a path (image) or class (FontAwesome)
        if (strpos($icon, '/') !== false || strpos($icon, '.') !== false) {
            $iconHtml = '<img src="' . htmlspecialchars($icon) . '" alt="" class="w-12 h-12 mb-3">';
        } else {
            $iconHtml = '<div class="stat-card-icon primary mb-3"><i class="' . htmlspecialchars($icon) . '"></i></div>';
        }
    }
    
    return sprintf(
        '<a href="%s" class="card p-6 hover:shadow-lg transition-all block %s">
            %s
            <h4 class="font-semibold text-foreground mb-2">%s</h4>
            <p class="text-sm text-muted">%s</p>
        </a>',
        htmlspecialchars($href),
        htmlspecialchars($class),
        $iconHtml,
        htmlspecialchars($title),
        htmlspecialchars($description)
    );
}
