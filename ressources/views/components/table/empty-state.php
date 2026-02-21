<div class="cm-empty-state has-text-centered py-6">
    <span class="icon is-large has-text-grey-light">
        <i class="fas <?= $icon ?? 'fa-inbox' ?> fa-3x"></i>
    </span>
    <p class="has-text-grey mt-3">
        <?= htmlspecialchars($empty_message ?? 'Aucune donnée disponible') ?>
    </p>
    <?php if (!empty($action_url) && !empty($action_label)): ?>
    <a href="<?= htmlspecialchars($action_url) ?>" class="button is-primary mt-3">
        <?= htmlspecialchars($action_label) ?>
    </a>
    <?php endif; ?>
</div>
