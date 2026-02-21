<?php $hasError = !empty($error); ?>
<div class="field cm-field cm-file-upload <?= $hasError ? 'has-error' : '' ?>">
    <?php if (!empty($label)): ?>
    <label class="label">
        <?= htmlspecialchars($label) ?>
        <?php if ($required ?? false): ?><span class="has-text-danger">*</span><?php endif; ?>
    </label>
    <?php endif; ?>
    <div class="control">
        <div class="file has-name <?= ($attrs['boxed'] ?? false) ? 'is-boxed' : '' ?> is-primary">
            <label class="file-label">
                <input 
                    class="file-input" 
                    type="file"
                    name="<?= htmlspecialchars($name) ?>"
                    <?= ($multiple ?? false) ? 'multiple' : '' ?>
                    <?= ($required ?? false) ? 'required' : '' ?>
                    <?= ($disabled ?? false) ? 'disabled' : '' ?>
                    <?= !empty($accept) ? 'accept="' . htmlspecialchars($accept) . '"' : '' ?>
                    <?php foreach ($attrs ?? [] as $attr => $val): ?>
                        <?php if ($attr !== 'boxed'): ?>
                        <?= htmlspecialchars($attr) ?>="<?= htmlspecialchars($val) ?>"
                        <?php endif; ?>
                    <?php endforeach; ?>
                >
                <span class="file-cta">
                    <span class="file-icon">
                        <i class="fas fa-upload"></i>
                    </span>
                    <span class="file-label">Choisir un fichier</span>
                </span>
                <span class="file-name"></span>
            </label>
        </div>
    </div>
    <?php if ($hasError): ?>
    <p class="help is-danger"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($help)): ?>
    <p class="help"><?= htmlspecialchars($help) ?></p>
    <?php elseif (!empty($max_size)): ?>
    <p class="help">Taille max: <?= htmlspecialchars($max_size) ?></p>
    <?php endif; ?>
</div>
