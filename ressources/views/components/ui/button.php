<?php
/**
 * CheckMaster Premium - Button Component
 * 
 * Composant bouton avec gestion des variantes et permissions.
 * 
 * @param string $text - Texte du bouton
 * @param string $type - Type/variante (primary, secondary, outline, ghost, danger, success, warning)
 * @param bool $permission - Permission d'affichage (sécurité centralisée)
 * @param string $attr - Attributs HTML additionnels
 * @param string $size - Taille (sm, default, lg)
 * @param string $icon - Classe d'icône FontAwesome (ex: 'fa-plus')
 * @param bool $iconOnly - Bouton icône seule
 */

function renderButton(
    string $text, 
    string $type = 'primary', 
    bool $permission = true, 
    string $attr = '', 
    string $size = '', 
    string $icon = '',
    bool $iconOnly = false
): string {
    // Si pas de permission, on ne renvoie rien (sécurité centralisée)
    if (!$permission) {
        return '';
    }
    
    // Mapping des variantes CSS
    $variants = [
        'primary'   => 'btn-primary',
        'secondary' => 'btn-secondary',
        'outline'   => 'btn-outline',
        'ghost'     => 'btn-ghost',
        'danger'    => 'btn-danger',
        'success'   => 'btn-success',
        'warning'   => 'btn-warning'
    ];
    
    // Mapping des tailles
    $sizes = [
        'sm' => 'btn-sm',
        'lg' => 'btn-lg'
    ];
    
    $variantClass = $variants[$type] ?? $variants['primary'];
    $sizeClass = isset($sizes[$size]) ? ' ' . $sizes[$size] : '';
    $iconOnlyClass = $iconOnly ? ' btn-icon' : '';
    
    $iconHtml = '';
    if (!empty($icon)) {
        $iconHtml = '<i class="fas ' . htmlspecialchars($icon) . '"></i>';
    }
    
    $textHtml = $iconOnly ? '' : htmlspecialchars($text);
    
    return sprintf(
        '<button class="btn %s%s%s" %s>%s%s</button>',
        $variantClass,
        $sizeClass,
        $iconOnlyClass,
        $attr,
        $iconHtml,
        $textHtml ? ($iconHtml ? ' ' . $textHtml : $textHtml) : ''
    );
}

/**
 * Bouton de lien (anchor)
 */
function renderButtonLink(
    string $text,
    string $href,
    string $type = 'primary',
    bool $permission = true,
    string $attr = '',
    string $size = '',
    string $icon = ''
): string {
    if (!$permission) {
        return '';
    }
    
    $variants = [
        'primary'   => 'btn-primary',
        'secondary' => 'btn-secondary',
        'outline'   => 'btn-outline',
        'ghost'     => 'btn-ghost',
        'danger'    => 'btn-danger',
        'success'   => 'btn-success',
        'warning'   => 'btn-warning'
    ];
    
    $sizes = [
        'sm' => 'btn-sm',
        'lg' => 'btn-lg'
    ];
    
    $variantClass = $variants[$type] ?? $variants['primary'];
    $sizeClass = isset($sizes[$size]) ? ' ' . $sizes[$size] : '';
    
    $iconHtml = '';
    if (!empty($icon)) {
        $iconHtml = '<i class="fas ' . htmlspecialchars($icon) . '"></i> ';
    }
    
    return sprintf(
        '<a href="%s" class="btn %s%s" %s>%s%s</a>',
        htmlspecialchars($href),
        $variantClass,
        $sizeClass,
        $attr,
        $iconHtml,
        htmlspecialchars($text)
    );
}

/**
 * Groupe de boutons
 */
function renderButtonGroup(array $buttons): string {
    $html = '<div class="flex gap-sm">';
    foreach ($buttons as $button) {
        $html .= $button;
    }
    $html .= '</div>';
    return $html;
}
