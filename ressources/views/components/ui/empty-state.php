<?php
/**
 * CheckMaster Premium - Empty State Component
 * 
 * Composant pour afficher un état vide (pas de données).
 */

/**
 * Empty state
 * 
 * @param string $title - Titre principal
 * @param string $description - Description
 * @param string $icon - Icône FontAwesome
 * @param string $action - HTML d'action (ex: bouton)
 * @param array $config - Configuration additionnelle
 */
function renderEmptyState(
    string $title = 'Aucune donnée',
    string $description = '',
    string $icon = 'fa-inbox',
    string $action = '',
    array $config = []
): string {
    $defaults = [
        'class' => '',
        'iconClass' => 'primary', // primary, muted, info, etc.
        'size' => 'default' // compact, default, large
    ];
    
    $opts = array_merge($defaults, $config);
    
    $sizeClass = '';
    switch ($opts['size']) {
        case 'compact':
            $sizeClass = ' empty-state-compact';
            break;
        case 'large':
            $sizeClass = ' empty-state-large';
            break;
    }
    
    $iconHtml = sprintf(
        '<div class="empty-state-icon %s">
            <i class="fas %s"></i>
        </div>',
        htmlspecialchars($opts['iconClass']),
        htmlspecialchars($icon)
    );
    
    $descriptionHtml = $description 
        ? '<p class="empty-state-description">' . htmlspecialchars($description) . '</p>' 
        : '';
    
    $actionHtml = $action 
        ? '<div class="empty-state-action">' . $action . '</div>' 
        : '';
    
    return sprintf(
        '<div class="empty-state%s %s">
            %s
            <h3 class="empty-state-title">%s</h3>
            %s
            %s
        </div>',
        $sizeClass,
        htmlspecialchars($opts['class']),
        $iconHtml,
        htmlspecialchars($title),
        $descriptionHtml,
        $actionHtml
    );
}

/**
 * Empty state pour les tableaux
 */
function renderTableEmptyState(
    string $message = 'Aucun résultat trouvé',
    int $colspan = 1
): string {
    return sprintf(
        '<tr>
            <td colspan="%d" class="text-center py-lg">
                %s
            </td>
        </tr>',
        $colspan,
        renderEmptyState($message, '', 'fa-search', '', ['size' => 'compact'])
    );
}

/**
 * Empty state pour recherche sans résultat
 */
function renderSearchEmptyState(
    string $query = '',
    string $action = ''
): string {
    $description = $query 
        ? "Aucun résultat trouvé pour « " . htmlspecialchars($query) . " »" 
        : "Aucun résultat trouvé";
    
    return renderEmptyState(
        'Aucun résultat',
        $description,
        'fa-search',
        $action,
        ['iconClass' => 'muted']
    );
}

/**
 * Empty state avec illustration
 */
function renderIllustratedEmptyState(
    string $title,
    string $description,
    string $imagePath,
    string $action = ''
): string {
    $imageHtml = sprintf(
        '<div class="empty-state-illustration">
            <img src="%s" alt="" class="empty-state-image">
        </div>',
        htmlspecialchars($imagePath)
    );
    
    $descriptionHtml = $description 
        ? '<p class="empty-state-description">' . htmlspecialchars($description) . '</p>' 
        : '';
    
    $actionHtml = $action 
        ? '<div class="empty-state-action">' . $action . '</div>' 
        : '';
    
    return sprintf(
        '<div class="empty-state empty-state-illustrated">
            %s
            <h3 class="empty-state-title">%s</h3>
            %s
            %s
        </div>',
        $imageHtml,
        htmlspecialchars($title),
        $descriptionHtml,
        $actionHtml
    );
}
