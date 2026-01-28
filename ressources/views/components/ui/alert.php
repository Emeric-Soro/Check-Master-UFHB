<?php
/**
 * CheckMaster Premium - Alert Component
 * 
 * Composant alerte/toast pour les messages flash et notifications.
 */

/**
 * Render an alert
 * 
 * @param string $message - Message à afficher
 * @param string $type - Type d'alerte (success, warning, danger, info)
 * @param string|null $title - Titre optionnel
 * @param bool $dismissible - Peut être fermée
 * @param bool $autoHide - Se ferme automatiquement
 * @param int $autoHideDuration - Durée avant fermeture (ms)
 */
function renderAlert(
    string $message,
    string $type = 'info',
    ?string $title = null,
    bool $dismissible = true,
    bool $autoHide = false,
    int $autoHideDuration = 5000
): string {
    $icons = [
        'success' => 'fa-check-circle',
        'warning' => 'fa-exclamation-triangle',
        'danger'  => 'fa-times-circle',
        'info'    => 'fa-info-circle'
    ];
    
    $icon = $icons[$type] ?? $icons['info'];
    $autoHideAttr = $autoHide ? ' data-auto-hide="' . $autoHideDuration . '"' : '';
    
    $titleHtml = $title ? '<div class="alert-title">' . htmlspecialchars($title) . '</div>' : '';
    $dismissHtml = $dismissible 
        ? '<button type="button" class="modal-close" onclick="this.closest(\'.alert\').remove()"><i class="fas fa-times"></i></button>' 
        : '';
    
    return sprintf(
        '<div class="alert alert-%s"%s>
            <div class="alert-icon">
                <i class="fas %s"></i>
            </div>
            <div class="alert-content">
                %s
                <div class="alert-description">%s</div>
            </div>
            %s
        </div>',
        htmlspecialchars($type),
        $autoHideAttr,
        $icon,
        $titleHtml,
        htmlspecialchars($message),
        $dismissHtml
    );
}

/**
 * Flash message from session
 */
function renderFlashMessages(): string {
    $html = '';
    
    // Success message
    if (isset($_SESSION['success']) && !empty($_SESSION['success'])) {
        $html .= renderAlert($_SESSION['success'], 'success', null, true, true);
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
        $html .= renderAlert($_SESSION['success_message'], 'success', null, true, true);
        unset($_SESSION['success_message']);
    }
    
    // Error message
    if (isset($_SESSION['error']) && !empty($_SESSION['error'])) {
        $html .= renderAlert($_SESSION['error'], 'danger', 'Erreur', true, false);
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
        $html .= renderAlert($_SESSION['error_message'], 'danger', 'Erreur', true, false);
        unset($_SESSION['error_message']);
    }
    
    // Warning message
    if (isset($_SESSION['warning']) && !empty($_SESSION['warning'])) {
        $html .= renderAlert($_SESSION['warning'], 'warning', null, true, true);
        unset($_SESSION['warning']);
    }
    
    // Info message
    if (isset($_SESSION['info']) && !empty($_SESSION['info'])) {
        $html .= renderAlert($_SESSION['info'], 'info', null, true, true);
        unset($_SESSION['info']);
    }
    
    return $html;
}

/**
 * Success alert shorthand
 */
function renderSuccess(string $message, ?string $title = null): string {
    return renderAlert($message, 'success', $title);
}

/**
 * Error alert shorthand
 */
function renderError(string $message, ?string $title = 'Erreur'): string {
    return renderAlert($message, 'danger', $title);
}

/**
 * Warning alert shorthand
 */
function renderWarning(string $message, ?string $title = null): string {
    return renderAlert($message, 'warning', $title);
}

/**
 * Info alert shorthand
 */
function renderInfo(string $message, ?string $title = null): string {
    return renderAlert($message, 'info', $title);
}

/**
 * Inline alert (smaller, for forms)
 */
function renderInlineAlert(string $message, string $type = 'info'): string {
    $icons = [
        'success' => 'fa-check',
        'warning' => 'fa-exclamation',
        'danger'  => 'fa-times',
        'info'    => 'fa-info'
    ];
    
    $icon = $icons[$type] ?? $icons['info'];
    
    return sprintf(
        '<div class="flex items-center gap-sm text-sm p-2 rounded bg-%s-light text-%s">
            <i class="fas %s"></i>
            <span>%s</span>
        </div>',
        $type,
        $type,
        $icon,
        htmlspecialchars($message)
    );
}
