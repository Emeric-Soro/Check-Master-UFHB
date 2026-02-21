<?php
/**
 * Static Input Component
 * Renders a read-only input-like div that can contain rich text/spans.
 * Compatible with existing JS that targets the inner span.
 * 
 * Parameters:
 * - id: ID of the wrapper div
 * - textId: ID of the inner span (defaults to id + '-text')
 * - value: Current value (if any)
 * - placeholder: Text to show when empty
 * - size: 'is-small', 'is-medium', etc.
 */
?>
<div class="input <?= $size ?? '' ?> has-background-white-ter" 
     id="<?= htmlspecialchars($id ?? '') ?>" 
     style="cursor: not-allowed; display: flex; align-items: center;">
    <span id="<?= htmlspecialchars($textId ?? ($id . '-text')) ?>" 
          class="<?= empty($value) ? 'has-text-grey is-italic' : 'has-text-dark' ?>">
        <?= htmlspecialchars($value ?? $placeholder ?? '') ?>
    </span>
</div>
