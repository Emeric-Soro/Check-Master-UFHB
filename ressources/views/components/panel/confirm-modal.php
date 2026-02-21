<?php $modalId = $id ?? 'confirm-modal-' . uniqid(); ?>
<div id="<?= htmlspecialchars($modalId) ?>" class="modal cm-modal">
    <div class="modal-background cm-modal-overlay"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">
                <?php if (!empty($icon)): ?>
                <span class="icon"><i class="fas <?= $icon ?>"></i></span>
                <?php endif; ?>
                <?= htmlspecialchars($title ?? 'Confirmation') ?>
            </p>
            <button class="delete cm-modal-close" aria-label="close"></button>
        </header>
        
        <section class="modal-card-body">
            <p class="cm-modal-message">
                <?= htmlspecialchars($message ?? 'Êtes-vous sûr de vouloir effectuer cette action ?') ?>
            </p>
        </section>
        
        <footer class="modal-card-foot">
            <button class="button cm-modal-cancel">
                <?= htmlspecialchars($cancel_label ?? 'Annuler') ?>
            </button>
            <?php if (!empty($confirm_url) && ($confirm_method ?? 'GET') !== 'GET'): ?>
            <form action="<?= htmlspecialchars($confirm_url) ?>" method="POST" class="cm-modal-form" style="display:inline;">
                <?php if (!empty($csrf_token)): ?>
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <?php endif; ?>
                <?php if (($confirm_method ?? 'POST') !== 'POST'): ?>
                <input type="hidden" name="_method" value="<?= htmlspecialchars($confirm_method) ?>">
                <?php endif; ?>
                <button type="submit" class="button <?= $confirm_class ?? 'is-danger' ?>">
                    <?php if (!empty($confirm_icon)): ?>
                    <span class="icon"><i class="fas <?= $confirm_icon ?>"></i></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($confirm_label ?? 'Confirmer') ?></span>
                </button>
            </form>
            <?php else: ?>
            <a href="<?= htmlspecialchars($confirm_url ?? '#') ?>" 
               class="button <?= $confirm_class ?? 'is-danger' ?> cm-modal-confirm">
                <?php if (!empty($confirm_icon ?? '')): ?>
                <span class="icon"><i class="fas <?= $confirm_icon ?>"></i></span>
                <?php endif; ?>
                <span><?= htmlspecialchars($confirm_label ?? 'Confirmer') ?></span>
            </a>
            <?php endif; ?>
        </footer>
    </div>
</div>

<script>
// Initialize modal close handlers
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('<?= htmlspecialchars($modalId) ?>');
    if (modal) {
        // Close on background click
        modal.querySelector('.cm-modal-overlay')?.addEventListener('click', () => {
            modal.classList.remove('is-active');
        });
        // Close on cancel button
        modal.querySelector('.cm-modal-cancel')?.addEventListener('click', () => {
            modal.classList.remove('is-active');
        });
        // Close on X button
        modal.querySelector('.cm-modal-close')?.addEventListener('click', () => {
            modal.classList.remove('is-active');
        });
    }
});
</script>
