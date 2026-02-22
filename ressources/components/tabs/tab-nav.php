<?php
$tabs = is_array($tabs ?? null) ? $tabs : [];
$active = (string) ($active ?? ($tabs[0]['id'] ?? ''));
?>
<div class="cm-tab-nav" role="tablist">
    <?php foreach ($tabs as $tab): ?>
        <?php
        $id = (string) ($tab['id'] ?? '');
        $label = (string) ($tab['label'] ?? $id);
        if ($id === '') {
            continue;
        }
        $isActive = $id === $active;
        ?>
    <button type="button"
            class="cm-tab-nav__item <?= $isActive ? 'is-active' : '' ?>"
            data-tab-target="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
            role="tab"
            aria-selected="<?= $isActive ? 'true' : 'false' ?>">
        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
    </button>
    <?php endforeach; ?>
</div>
<script>
(function () {
    const nav = document.currentScript ? document.currentScript.previousElementSibling : null;
    if (!nav || !nav.classList.contains('cm-tab-nav')) {
        return;
    }
    const buttons = nav.querySelectorAll('[data-tab-target]');
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            const id = button.getAttribute('data-tab-target');
            if (!id) {
                return;
            }
            buttons.forEach(function (b) {
                const active = b === button;
                b.classList.toggle('is-active', active);
                b.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-tab-panel') !== id;
            });
        });
    });
})();
</script>
