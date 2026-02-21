<?php
$editorId = 'editor-' . uniqid();
$editorHeight = $height ?? 300;
$toolbar = $toolbar ?? 'standard';
$isReadonly = $readonly ?? false;
?>
<div class="cm-rich-editor field">
    <div class="cm-editor-wrapper">
        <?php if (!$isReadonly): ?>
        <div class="cm-editor-toolbar buttons are-small mb-0">
            <?php if ($toolbar === 'full' || $toolbar === 'standard'): ?>
            <button type="button" class="button is-light" data-command="bold" title="Gras">
                <span class="icon"><i class="fas fa-bold"></i></span>
            </button>
            <button type="button" class="button is-light" data-command="italic" title="Italique">
                <span class="icon"><i class="fas fa-italic"></i></span>
            </button>
            <button type="button" class="button is-light" data-command="underline" title="Souligné">
                <span class="icon"><i class="fas fa-underline"></i></span>
            </button>
            <span class="cm-toolbar-separator"></span>
            <?php endif; ?>
            <?php if ($toolbar === 'full'): ?>
            <button type="button" class="button is-light" data-command="strikethrough" title="Barré">
                <span class="icon"><i class="fas fa-strikethrough"></i></span>
            </button>
            <button type="button" class="button is-light" data-command="subscript" title="Indice">
                <span class="icon"><i class="fas fa-subscript"></i></span>
            </button>
            <button type="button" class="button is-light" data-command="superscript" title="Exposant">
                <span class="icon"><i class="fas fa-superscript"></i></span>
            </button>
            <span class="cm-toolbar-separator"></span>
            <?php endif; ?>
            <button type="button" class="button is-light" data-command="insertUnorderedList" title="Liste">
                <span class="icon"><i class="fas fa-list-ul"></i></span>
            </button>
            <button type="button" class="button is-light" data-command="insertOrderedList" title="Liste numérotée">
                <span class="icon"><i class="fas fa-list-ol"></i></span>
            </button>
            <?php if ($toolbar === 'full' || $toolbar === 'standard'): ?>
            <span class="cm-toolbar-separator"></span>
            <button type="button" class="button is-light" data-command="justifyLeft" title="Aligner à gauche">
                <span class="icon"><i class="fas fa-align-left"></i></span>
            </button>
            <button type="button" class="button is-light" data-command="justifyCenter" title="Centrer">
                <span class="icon"><i class="fas fa-align-center"></i></span>
            </button>
            <button type="button" class="button is-light" data-command="justifyRight" title="Aligner à droite">
                <span class="icon"><i class="fas fa-align-right"></i></span>
            </button>
            <?php endif; ?>
            <?php if ($toolbar === 'full'): ?>
            <span class="cm-toolbar-separator"></span>
            <button type="button" class="button is-light" data-command="formatBlock" data-value="h1" title="Titre 1">
                <strong>H1</strong>
            </button>
            <button type="button" class="button is-light" data-command="formatBlock" data-value="h2" title="Titre 2">
                <strong>H2</strong>
            </button>
            <button type="button" class="button is-light" data-command="formatBlock" data-value="h3" title="Titre 3">
                <strong>H3</strong>
            </button>
            <span class="cm-toolbar-separator"></span>
            <button type="button" class="button is-light" data-action="insertLink" title="Lien">
                <span class="icon"><i class="fas fa-link"></i></span>
            </button>
            <button type="button" class="button is-light" data-action="insertImage" title="Image">
                <span class="icon"><i class="fas fa-image"></i></span>
            </button>
            <button type="button" class="button is-light" data-action="insertTable" title="Tableau">
                <span class="icon"><i class="fas fa-table"></i></span>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="cm-editor-area control">
            <div 
                id="<?= $editorId ?>" 
                class="textarea cm-editor-content" 
                contenteditable="<?= $isReadonly ? 'false' : 'true' ?>"
                style="height: <?= $editorHeight ?>px; overflow-y: auto;"
                placeholder="<?= htmlspecialchars($placeholder ?? 'Rédigez votre texte ici...') ?>"
                data-name="<?= htmlspecialchars($name ?? 'content') ?>"
                <?= ($required ?? false) ? 'data-required="true"' : '' ?>
                <?= !empty($limit) ? 'data-limit="' . $limit . '"' : '' ?>
            ><?= $value ?? '' ?></div>
            <!-- Hidden textarea for form submission -->
            <textarea 
                name="<?= htmlspecialchars($name ?? 'content') ?>" 
                id="<?= $editorId ?>-hidden"
                class="is-hidden"
                <?= ($required ?? false) ? 'required' : '' ?>
            ><?= htmlspecialchars($value ?? '') ?></textarea>
        </div>
        
        <?php if (!empty($limit)): ?>
        <p class="help has-text-right">
            <span class="cm-editor-count">0</span> / <?= number_format($limit) ?> caractères
        </p>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const editor = document.getElementById('<?= $editorId ?>');
    const hidden = document.getElementById('<?= $editorId ?>-hidden');
    const limit = <?= $limit ?? 0 ?>;
    
    // Sync content to hidden textarea
    function syncContent() {
        hidden.value = editor.innerHTML;
        if (limit > 0) {
            const count = editor.innerText.length;
            const countEl = editor.closest('.cm-rich-editor').querySelector('.cm-editor-count');
            if (countEl) {
                countEl.textContent = count;
                countEl.classList.toggle('has-text-danger', count > limit);
            }
        }
    }
    
    editor.addEventListener('input', syncContent);
    editor.addEventListener('blur', syncContent);
    
    // Toolbar commands
    document.querySelectorAll('.cm-editor-toolbar button[data-command]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.execCommand(this.dataset.command, false, this.dataset.value || null);
            editor.focus();
        });
    });
})();
</script>
