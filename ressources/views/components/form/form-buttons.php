<div class="field cm-form-buttons">
    <div class="control">
        <div class="buttons">
            <button type="submit" class="button is-primary <?= ($loading ?? false) ? 'is-loading' : '' ?>">
                <span class="icon"><i class="fas fa-<?= ($mode ?? '') === 'edition' ? 'save' : 'plus' ?>"></i></span>
                <span><?= htmlspecialchars($submit_label ?? (($mode ?? '') === 'edition' ? 'Enregistrer' : 'Créer')) ?></span>
            </button>
            <?php if (($show_new ?? false) && ($mode ?? '') !== 'creation'): ?>
            <button type="button" class="button is-light" data-action="new">
                <span class="icon"><i class="fas fa-plus"></i></span>
                <span>Nouveau</span>
            </button>
            <?php endif; ?>
            <?php if (($show_cancel ?? false) && !empty($cancel_url)): ?>
            <a href="<?= htmlspecialchars($cancel_url) ?>" class="button is-light">
                <span class="icon"><i class="fas fa-times"></i></span>
                <span>Annuler</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
