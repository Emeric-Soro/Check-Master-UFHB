<?php
$name = (string) ($name ?? 'contenu');
$id = (string) ($id ?? $name);
$label = (string) ($label ?? '');
$value = (string) ($value ?? '');
$height = (string) ($height ?? '240px');
?>
<div class="cm-form-group cm-editor-group">
    <?php if ($label !== ''): ?>
    <label for="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" class="cm-form-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
    <?php endif; ?>
    <div class="cm-editor-wrapper">
        <div class="cm-editor-toolbar">
            <button type="button" data-cmd="bold" aria-label="Gras"><i class="fas fa-bold" aria-hidden="true"></i></button>
            <button type="button" data-cmd="italic" aria-label="Italique"><i class="fas fa-italic" aria-hidden="true"></i></button>
            <button type="button" data-cmd="underline" aria-label="Souligne"><i class="fas fa-underline" aria-hidden="true"></i></button>
            <span class="cm-editor-toolbar__sep"></span>
            <button type="button" data-cmd="insertUnorderedList" aria-label="Liste"><i class="fas fa-list-ul" aria-hidden="true"></i></button>
            <button type="button" data-cmd="insertOrderedList" aria-label="Liste ordonnee"><i class="fas fa-list-ol" aria-hidden="true"></i></button>
        </div>
        <div class="cm-rich-editor"
             id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>_editor"
             contenteditable="true"
             style="min-height: <?= htmlspecialchars($height, ENT_QUOTES, 'UTF-8') ?>;"><?= $value ?></div>
        <textarea class="cm-visually-hidden"
                  id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
                  name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
</div>
<script>
(function () {
    const editor = document.getElementById(<?= json_encode($id . '_editor') ?>);
    const input = document.getElementById(<?= json_encode($id) ?>);
    if (!editor || !input) {
        return;
    }

    const sync = function () {
        input.value = editor.innerHTML;
    };

    editor.addEventListener('input', sync);
    const toolbarButtons = editor.parentElement ? editor.parentElement.querySelectorAll('[data-cmd]') : [];
    toolbarButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const cmd = button.getAttribute('data-cmd');
            if (!cmd) {
                return;
            }
            document.execCommand(cmd, false);
            sync();
            editor.focus();
        });
    });
    sync();
})();
</script>
