<?php
/**
 * CheckMaster Premium - Separator Component
 * 
 * Séparateurs visuels pour diviser les sections.
 */

/**
 * Horizontal separator
 */
function renderSeparator(): string {
    return '<hr class="separator">';
}

/**
 * Separator with text
 */
function renderSeparatorText(string $text): string {
    return sprintf(
        '<div class="separator-text">%s</div>',
        htmlspecialchars($text)
    );
}

/**
 * Vertical separator
 */
function renderVerticalSeparator(): string {
    return '<div class="separator-vertical"></div>';
}

/**
 * Section divider with title
 */
function renderSectionDivider(string $title, string $subtitle = ''): string {
    $subtitleHtml = $subtitle 
        ? '<p class="text-sm text-muted mt-1">' . htmlspecialchars($subtitle) . '</p>' 
        : '';
    
    return sprintf(
        '<div class="py-4">
            <h3 class="text-lg font-semibold">%s</h3>
            %s
            <hr class="separator mt-3">
        </div>',
        htmlspecialchars($title),
        $subtitleHtml
    );
}

/**
 * Form section separator
 */
function renderFormSection(string $title, string $description = ''): string {
    $descHtml = $description 
        ? '<p class="text-sm text-muted">' . htmlspecialchars($description) . '</p>' 
        : '';
    
    return sprintf(
        '<div class="mb-6 pb-2 border-b">
            <h4 class="font-semibold text-foreground">%s</h4>
            %s
        </div>',
        htmlspecialchars($title),
        $descHtml
    );
}
