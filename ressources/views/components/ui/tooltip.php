<?php
/**
 * CheckMaster Premium - Tooltip Component
 * 
 * Infobulles pour afficher des informations supplémentaires.
 */

/**
 * Add tooltip to an element
 * 
 * @param string $content - Contenu HTML de l'élément
 * @param string $tooltip - Texte du tooltip
 * @param string $position - Position (top, bottom, left, right)
 */
function renderTooltip(string $content, string $tooltip, string $position = 'top'): string {
    return sprintf(
        '<span data-tooltip="%s" class="tooltip-%s">%s</span>',
        htmlspecialchars($tooltip),
        htmlspecialchars($position),
        $content
    );
}

/**
 * Icon with tooltip
 */
function renderInfoIcon(string $tooltip, string $icon = 'fa-info-circle'): string {
    return sprintf(
        '<span data-tooltip="%s" class="text-muted cursor-help">
            <i class="fas %s"></i>
        </span>',
        htmlspecialchars($tooltip),
        htmlspecialchars($icon)
    );
}

/**
 * Truncated text with tooltip showing full text
 */
function renderTruncatedText(string $text, int $maxLength = 50): string {
    if (mb_strlen($text) <= $maxLength) {
        return htmlspecialchars($text);
    }
    
    $truncated = mb_substr($text, 0, $maxLength) . '...';
    
    return sprintf(
        '<span data-tooltip="%s" class="cursor-help">%s</span>',
        htmlspecialchars($text),
        htmlspecialchars($truncated)
    );
}

/**
 * Help tooltip (question mark icon)
 */
function renderHelpTooltip(string $tooltip): string {
    return sprintf(
        '<span data-tooltip="%s" class="ml-1 text-muted cursor-help">
            <i class="fas fa-question-circle text-sm"></i>
        </span>',
        htmlspecialchars($tooltip)
    );
}
