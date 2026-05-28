<?php
/**
 * Vue principale — Parcours Étudiant Complet (page=cycle_etudiant)
 * Écran de recherche + tableau de bord des étudiants avec progression.
 */

$numEtu = (string) ($GLOBALS['cycle_num_etu'] ?? '');
$anneeAcad = (int) ($GLOBALS['cycle_annee_acad'] ?? 0);
$progression = $GLOBALS['cycle_progression'] ?? null;
$error = $GLOBALS['cycle_error'] ?? '';

// Si un étudiant est sélectionné, afficher le wizard
if ($numEtu !== '' && $progression !== null && $error === '') {
    $GLOBALS['cycle_etudiant_wizard_data'] = [
        'num_etu' => $numEtu,
        'annee_acad' => $anneeAcad,
        'progression' => $progression,
    ];
    include __DIR__ . '/partials/_wizard.php';
    return;
}
?>

<div class="cm-prd3-screen">
    <div class="cm-pole-superieur">
        <div class="cm-card" style="padding: 24px;">
            <h2 style="margin: 0 0 16px; font-size: 1.25rem; color: var(--cm-primary);">
                <i class="fas fa-route" style="margin-right: 8px;"></i> Parcours Étudiant Complet
            </h2>
            <p style="color: var(--cm-text-muted); margin-bottom: 16px; font-size: 0.9rem;">
                Recherchez un étudiant pour consulter son parcours complet de l'inscription au PV final.
            </p>
            <div style="position: relative; max-width: 500px;">
                <input type="text" id="cycleSearchInput" class="cm-form-control"
                       placeholder="Nom, prénom ou matricule..."
                       autocomplete="off"
                       style="padding-left: 36px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--cm-text-muted);"></i>
                <div id="cycleSearchResults" class="cm-hidden" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 100; background: var(--cm-box-bg); border: 1px solid var(--cm-border-color); border-radius: 8px; margin-top: 4px; max-height: 300px; overflow-y: auto; box-shadow: var(--cm-shadow-md);"></div>
            </div>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="cm-alert-box is-danger" style="margin: 16px;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var searchInput = document.getElementById('cycleSearchInput');
    var resultsDiv = document.getElementById('cycleSearchResults');
    var debounceTimer = null;

    if (!searchInput || !resultsDiv) return;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        var q = searchInput.value.trim();
        if (q.length < 2) {
            resultsDiv.classList.add('cm-hidden');
            resultsDiv.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(function() {
            fetch('<?= htmlspecialchars($_SERVER['PHP_SELF'] ?? 'layout.php') ?>?page=cycle_etudiant&action=search&q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.results || data.results.length === 0) {
                    resultsDiv.innerHTML = '<div style="padding: 12px; color: var(--cm-text-muted); text-align: center;">Aucun résultat</div>';
                    resultsDiv.classList.remove('cm-hidden');
                    return;
                }
                var html = '';
                data.results.forEach(function(item) {
                    html += '<a href="?page=cycle_etudiant&action=show&id=' + encodeURIComponent(item.id) + '" style="display: block; padding: 10px 16px; color: var(--cm-text); text-decoration: none; border-bottom: 1px solid var(--cm-border-color); transition: background 0.15s;" onmouseover="this.style.background=\'var(--cm-hover-bg)\'" onmouseout="this.style.background=\'transparent\'">';
                    html += '<strong>' + escapeHtml(item.text) + '</strong>';
                    if (item.promotion) {
                        html += ' <span class="cm-badge is-light" style="margin-left: 8px;">' + escapeHtml(item.promotion) + '</span>';
                    }
                    html += '</a>';
                });
                resultsDiv.innerHTML = html;
                resultsDiv.classList.remove('cm-hidden');
            })
            .catch(function() {
                resultsDiv.classList.add('cm-hidden');
            });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.classList.add('cm-hidden');
        }
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
})();
</script>
