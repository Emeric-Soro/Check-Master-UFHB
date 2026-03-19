<?php
/**
 * Inline confirmation component (NO MODAL)
 * 
 * Renders a button that, when clicked, shows an inline confirmation message
 * with confirm/cancel buttons.
 * 
 * Usage:
 *   cm_component('ui/inline-confirm', [
 *       'id' => 'delete-item-123',
 *       'action' => 'delete', // 'delete' | 'submit' | 'custom'
 *       'button_text' => 'Supprimer',
 *       'button_icon' => 'fa-trash',
 *       'button_class' => 'cm-btn is-danger is-sm',
 *       'confirm_text' => 'Confirmer la suppression ?',
 *       'confirm_button_text' => 'Confirmer',
 *       'cancel_button_text' => 'Annuler',
 *       'form_action' => '?page=...', // URL for form action
 *       'form_method' => 'POST',
 *       'hidden_fields' => ['id' => '123', 'delete' => '1'],
 *       'on_confirm' => 'submit', // 'submit' | 'callback' | 'link'
 *       'callback' => 'functionName()', // JS function to call on confirm
 *       'link_url' => '?page=...', // URL to navigate to on confirm
 *   ]);
 */

$id = (string) ($id ?? 'inline-confirm-' . uniqid());
$action = (string) ($action ?? 'delete');
$buttonText = (string) ($button_text ?? 'Confirmer');
$buttonIcon = (string) ($button_icon ?? '');
$buttonClass = (string) ($button_class ?? 'cm-btn is-light is-sm');
$confirmText = (string) ($confirm_text ?? 'Êtes-vous sûr ?');
$confirmButtonText = (string) ($confirm_button_text ?? 'Confirmer');
$cancelButtonText = (string) ($cancel_button_text ?? 'Annuler');
$formAction = (string) ($form_action ?? '');
$formMethod = strtoupper((string) ($form_method ?? 'POST'));
$hiddenFields = is_array($hidden_fields ?? null) ? $hidden_fields : [];
$onConfirm = (string) ($on_confirm ?? 'submit');
$callback = (string) ($callback ?? '');
$linkUrl = (string) ($link_url ?? '');

// Color based on action type
$confirmClass = 'cm-btn is-danger is-sm';
if ($action === 'submit') {
    $confirmClass = 'cm-btn is-primary is-sm';
} elseif ($action === 'custom') {
    $confirmClass = 'cm-btn is-warning is-sm';
}
?>
<div class="cm-inline-confirm" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
    <!-- Trigger button -->
    <button type="button" 
            class="cm-inline-confirm__trigger <?= htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8') ?>"
            onclick="cmInlineConfirm_<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>.show()">
        <?php if ($buttonIcon !== ''): ?>
            <i class="fas <?= htmlspecialchars($buttonIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
        <?php endif; ?>
        <span><?= htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8') ?></span>
    </button>

    <!-- Inline confirmation panel (hidden by default) -->
    <div class="cm-inline-confirm__panel" style="display: none;">
        <div class="cm-inline-confirm__message">
            <i class="fas fa-question-circle cm-inline-confirm__icon" aria-hidden="true"></i>
            <span><?= htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="cm-inline-confirm__actions">
            <?php if ($onConfirm === 'submit' && $formAction !== ''): ?>
                <form method="<?= htmlspecialchars($formMethod, ENT_QUOTES, 'UTF-8') ?>" 
                      action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
                      class="cm-inline-confirm__form">
                    <?php foreach ($hiddenFields as $fieldName => $fieldValue): ?>
                        <input type="hidden" 
                               name="<?= htmlspecialchars((string) $fieldName, ENT_QUOTES, 'UTF-8') ?>" 
                               value="<?= htmlspecialchars((string) $fieldValue, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="<?= htmlspecialchars($confirmClass, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($confirmButtonText, ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                </form>
            <?php elseif ($onConfirm === 'callback' && $callback !== ''): ?>
                <button type="button" 
                        class="<?= htmlspecialchars($confirmClass, ENT_QUOTES, 'UTF-8') ?>"
                        onclick="<?= htmlspecialchars($callback, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas fa-check" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($confirmButtonText, ENT_QUOTES, 'UTF-8') ?></span>
                </button>
            <?php elseif ($onConfirm === 'link' && $linkUrl !== ''): ?>
                <a href="<?= htmlspecialchars($linkUrl, ENT_QUOTES, 'UTF-8') ?>" 
                   class="<?= htmlspecialchars($confirmClass, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas fa-check" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($confirmButtonText, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endif; ?>
            <button type="button" 
                    class="cm-btn is-light is-sm"
                    onclick="cmInlineConfirm_<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>.hide()">
                <i class="fas fa-times" aria-hidden="true"></i>
                <span><?= htmlspecialchars($cancelButtonText, ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var id = <?= json_encode($id) ?>;
    var container = document.getElementById(id);
    if (!container) return;
    
    var trigger = container.querySelector('.cm-inline-confirm__trigger');
    var panel = container.querySelector('.cm-inline-confirm__panel');
    
    window['cmInlineConfirm_' + id] = {
        show: function() {
            if (trigger) trigger.style.display = 'none';
            if (panel) panel.style.display = 'flex';
        },
        hide: function() {
            if (trigger) trigger.style.display = '';
            if (panel) panel.style.display = 'none';
        }
    };
})();
</script>
