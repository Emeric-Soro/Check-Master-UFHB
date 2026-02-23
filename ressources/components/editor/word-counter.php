<?php
$target_id = (string) ($target_id ?? '');
$min_words = isset($min_words) ? max(0, (int) $min_words) : 0;
$show_chars = !isset($show_chars) || (bool) $show_chars;
if ($target_id === '') {
    return;
}
$counter_id = $target_id . '_counter';
?>
<div class="cm-word-counter" id="<?= htmlspecialchars($counter_id, ENT_QUOTES, 'UTF-8') ?>">
    <span class="cm-word-counter__words">0 mot(s)</span>
    <?php if ($show_chars): ?><span class="cm-word-counter__chars">0 caractere(s)</span><?php endif; ?>
    <?php if ($min_words > 0): ?><span class="cm-word-counter__min">Minimum: <?= $min_words ?> mots</span><?php endif; ?>
</div>
<script>
(function () {
    const field = document.getElementById(<?= json_encode($target_id) ?>);
    const box = document.getElementById(<?= json_encode($counter_id) ?>);
    if (!field || !box) {
        return;
    }
    const wordsEl = box.querySelector('.cm-word-counter__words');
    const charsEl = box.querySelector('.cm-word-counter__chars');
    const minEl = box.querySelector('.cm-word-counter__min');
    const minWords = <?= (int) $min_words ?>;
    const update = function () {
        const text = String(field.value || '').trim();
        const words = text === '' ? 0 : text.split(/\s+/).length;
        const chars = text.length;
        if (wordsEl) {
            wordsEl.textContent = words + ' mot(s)';
        }
        if (charsEl) {
            charsEl.textContent = chars + ' caractere(s)';
        }
        if (minEl) {
            minEl.classList.toggle('is-met', words >= minWords);
            minEl.classList.toggle('is-unmet', words < minWords);
        }
    };
    field.addEventListener('input', update);
    update();
})();
</script>
