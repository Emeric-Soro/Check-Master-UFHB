<?php $hasError = !empty($error); ?>
<div class="field cm-field cm-select-search <?= $hasError ? 'has-error' : '' ?>">
    <?php if (!empty($label)): ?>
    <label class="label" for="field-<?= htmlspecialchars($name) ?>">
        <?= htmlspecialchars($label) ?>
        <?php if ($required ?? false): ?><span class="has-text-danger">*</span><?php endif; ?>
    </label>
    <?php endif; ?>
    <div class="control has-icons-left">
        <input 
            class="input cm-select-search-input <?= $hasError ? 'is-danger' : '' ?>" 
            type="text"
            name="<?= htmlspecialchars($name) ?>_search"
            id="field-<?= htmlspecialchars($name) ?>"
            value="<?= htmlspecialchars($selected_label ?? '') ?>"
            placeholder="<?= htmlspecialchars($placeholder ?? 'Tapez pour rechercher...') ?>"
            data-api-url="<?= htmlspecialchars($api_url ?? '') ?>"
            data-min-chars="<?= $min_chars ?? 2 ?>"
            <?= ($required ?? false) ? 'required' : '' ?>
            <?= ($disabled ?? false) ? 'disabled' : '' ?>
            autocomplete="off"
        >
        <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value ?? '') ?>">
        <span class="icon is-small is-left">
            <i class="fas fa-search"></i>
        </span>
        <div class="cm-select-search-results"></div>
        <?php if (!empty($autofill_targets)): ?>
        <script>
        window.CM?.selectSearch?.registerAutofill('<?= htmlspecialchars($name) ?>', <?= json_encode($autofill_targets) ?>);
        </script>
        <?php endif; ?>
    </div>
    <?php if ($hasError): ?>
    <p class="help is-danger"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($help)): ?>
    <p class="help"><?= htmlspecialchars($help) ?></p>
    <?php endif; ?>
</div>
