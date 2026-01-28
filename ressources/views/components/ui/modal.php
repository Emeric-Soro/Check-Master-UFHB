<?php
/**
 * CheckMaster Premium - Modal/Dialog Component
 * 
 * Composant modal pour les dialogues et formulaires.
 */

/**
 * Render a modal container
 * 
 * @param string $id - ID unique de la modale
 * @param string $title - Titre de la modale
 * @param string $content - Contenu HTML
 * @param string $footer - Contenu du footer (boutons)
 * @param string $size - Taille (sm, default, lg, xl, full)
 */
function renderModal(
    string $id,
    string $title,
    string $content,
    string $footer = '',
    string $size = ''
): string {
    $sizeClass = $size ? ' modal-' . $size : '';
    
    return sprintf(
        '<div id="%s" class="modal-overlay">
            <div class="modal%s">
                <div class="modal-header">
                    <h3 class="modal-title">%s</h3>
                    <button type="button" class="modal-close" onclick="CM.Modal.hide(\'%s\')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    %s
                </div>
                %s
            </div>
        </div>',
        htmlspecialchars($id),
        $sizeClass,
        htmlspecialchars($title),
        htmlspecialchars($id),
        $content,
        $footer ? '<div class="modal-footer">' . $footer . '</div>' : ''
    );
}

/**
 * Modal with form
 */
function renderFormModal(
    string $id,
    string $title,
    string $formContent,
    string $action,
    string $method = 'POST',
    string $submitText = 'Enregistrer',
    string $size = ''
): string {
    $sizeClass = $size ? ' modal-' . $size : '';
    $csrfToken = function_exists('csrf_token') ? csrf_token() : '';
    $csrfField = $csrfToken ? '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">' : '';
    
    return sprintf(
        '<div id="%s" class="modal-overlay">
            <div class="modal%s">
                <form action="%s" method="%s">
                    %s
                    <div class="modal-header">
                        <h3 class="modal-title">%s</h3>
                        <button type="button" class="modal-close" onclick="CM.Modal.hide(\'%s\')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        %s
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="CM.Modal.hide(\'%s\')">Annuler</button>
                        <button type="submit" class="btn btn-primary">%s</button>
                    </div>
                </form>
            </div>
        </div>',
        htmlspecialchars($id),
        $sizeClass,
        htmlspecialchars($action),
        htmlspecialchars($method),
        $csrfField,
        htmlspecialchars($title),
        htmlspecialchars($id),
        $formContent,
        htmlspecialchars($id),
        htmlspecialchars($submitText)
    );
}

/**
 * Confirm modal (dynamically generated via JS)
 */
function renderConfirmModal(
    string $id,
    string $title,
    string $message,
    string $confirmUrl,
    string $confirmText = 'Confirmer',
    string $type = 'warning'
): string {
    $icons = [
        'warning' => 'fa-exclamation-triangle',
        'danger'  => 'fa-trash-alt',
        'info'    => 'fa-info-circle'
    ];
    
    $btnClass = $type === 'danger' ? 'btn-danger' : 'btn-primary';
    $iconClass = $icons[$type] ?? $icons['warning'];
    
    return sprintf(
        '<div id="%s" class="modal-overlay">
            <div class="modal modal-sm">
                <div class="modal-header">
                    <h3 class="modal-title">%s</h3>
                    <button type="button" class="modal-close" onclick="CM.Modal.hide(\'%s\')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="flex items-center gap-md">
                        <div class="stat-card-icon %s">
                            <i class="fas %s"></i>
                        </div>
                        <p>%s</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="CM.Modal.hide(\'%s\')">Annuler</button>
                    <a href="%s" class="btn %s">%s</a>
                </div>
            </div>
        </div>',
        htmlspecialchars($id),
        htmlspecialchars($title),
        htmlspecialchars($id),
        $type,
        $iconClass,
        htmlspecialchars($message),
        htmlspecialchars($id),
        htmlspecialchars($confirmUrl),
        $btnClass,
        htmlspecialchars($confirmText)
    );
}

/**
 * Delete confirm modal
 */
function renderDeleteModal(
    string $id,
    string $itemName,
    string $deleteUrl
): string {
    return renderConfirmModal(
        $id,
        'Confirmer la suppression',
        sprintf('Êtes-vous sûr de vouloir supprimer "%s" ? Cette action est irréversible.', $itemName),
        $deleteUrl,
        'Supprimer',
        'danger'
    );
}

/**
 * Loading modal (for async operations)
 */
function renderLoadingModal(string $id, string $message = 'Chargement en cours...'): string {
    return sprintf(
        '<div id="%s" class="modal-overlay">
            <div class="modal modal-sm">
                <div class="modal-body text-center py-8">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-4"></i>
                    <p class="text-muted">%s</p>
                </div>
            </div>
        </div>',
        htmlspecialchars($id),
        htmlspecialchars($message)
    );
}
