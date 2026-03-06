<?php
/**
 * GRILLE D'ÉVALUATION — composant très compact.
 *
 * Props:
 *   criteria          array   [['id'=>int, 'label'=>string, 'abbrev'=>string|null, 'bareme'=>float], ...]
 *   values            array   map id_critere => note (valeurs pré-remplies)
 *   commentaire       string  valeur du champ commentaire
 *   moyenne           float|string  moyenne déjà calculée (affichée en lecture seule)
 *   name_prefix       string  préfixe des inputs (défaut: 'note')
 *   id_prefix         string  préfixe des IDs (défaut: 'cmEvalGrid')
 *   show_header       bool    afficher le titre ── GRILLE D'ÉVALUATION (défaut: true)
 *   show_buttons      bool    afficher les boutons d'action (défaut: true)
 *   submit_label      string  label du bouton principal (défaut: 'Enregistrer évaluation')
 *   commentaire_label string  label de la zone de commentaire (défaut: 'Commentaire du jury')
 *   readonly          bool    grille en lecture seule (défaut: false)
 *   max_note          float   note maximale par critère (défaut: 20)
 *   step              string  pas de l'input (défaut: '0.5')
 *   commentaire_name  string  nom HTML de la textarea (défaut: '{name_prefix}_commentaire')
 */

$criteria          = is_array($criteria ?? null) ? $criteria : [];
$values            = is_array($values ?? null) ? $values : [];
$commentaire       = (string) ($commentaire ?? '');
$moyenne           = isset($moyenne) && $moyenne !== null && $moyenne !== '' ? (string) $moyenne : '';
$name_prefix       = trim((string) ($name_prefix ?? 'note'));
$id_prefix         = trim((string) ($id_prefix ?? 'cmEvalGrid'));
$commentaire_name  = trim((string) ($commentaire_name ?? '')) ?: ($name_prefix . '_commentaire');
$show_header       = isset($show_header) ? (bool) $show_header : true;
$show_buttons      = isset($show_buttons) ? (bool) $show_buttons : true;
$submit_label      = (string) ($submit_label ?? 'Enregistrer évaluation');
$commentaire_label = (string) ($commentaire_label ?? 'Commentaire du jury');
$readonly          = !empty($readonly);
$max_note          = (float) ($max_note ?? 20);
$step              = (string) ($step ?? '0.5');

$gridId      = htmlspecialchars($id_prefix, ENT_QUOTES, 'UTF-8');
$moyenneId   = $gridId . 'Moyenne';
$commentId   = $gridId . 'Comment';
$totalBareme = 0.0;
foreach ($criteria as $c) {
    $totalBareme += (float) ($c['bareme'] ?? 0);
}
?>
<div class="cm-eval-grid" id="<?= $gridId ?>">
    <?php if ($show_header): ?>
    <div class="cm-eval-grid__header">
        <i class="fas fa-list-check" aria-hidden="true"></i>
        GRILLE D'ÉVALUATION
    </div>
    <?php endif; ?>

    <div class="cm-eval-grid__table-wrap">
        <table class="cm-eval-grid__table">
            <thead>
                <tr>
                    <th class="cm-eval-grid__th cm-eval-grid__th--label">Critère</th>
                    <th class="cm-eval-grid__th cm-eval-grid__th--bareme">Barème</th>
                    <th class="cm-eval-grid__th cm-eval-grid__th--note">Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($criteria as $critere):
                    $cid     = (int) ($critere['id'] ?? 0);
                    $label   = (string) ($critere['label'] ?? '');
                    $abbrev  = (string) ($critere['abbrev'] ?? '');
                    $bareme  = (float) ($critere['bareme'] ?? 0);
                    $curVal  = isset($values[$cid]) && $values[$cid] !== '' ? (string) $values[$cid] : '';
                    $inputId = $gridId . 'Crit' . $cid;
                    $inputName = htmlspecialchars($name_prefix . '[' . $cid . ']', ENT_QUOTES, 'UTF-8');
                ?>
                <tr class="cm-eval-grid__row" data-bareme="<?= htmlspecialchars((string) $bareme, ENT_QUOTES, 'UTF-8') ?>">
                    <td class="cm-eval-grid__td cm-eval-grid__td--label">
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($abbrev !== ''): ?>
                            <span class="cm-eval-grid__abbrev">(<?= htmlspecialchars($abbrev, ENT_QUOTES, 'UTF-8') ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="cm-eval-grid__td cm-eval-grid__td--bareme">
                        <?= htmlspecialchars(number_format($bareme, 0), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="cm-eval-grid__td cm-eval-grid__td--note">
                        <div class="cm-eval-grid__note-wrap">
                            <input
                                type="number"
                                id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>"
                                name="<?= $inputName ?>"
                                class="cm-eval-grid__note-input cm-eval-grid__note-field"
                                value="<?= htmlspecialchars($curVal, ENT_QUOTES, 'UTF-8') ?>"
                                min="0"
                                max="<?= $bareme ?>"
                                step="<?= htmlspecialchars($step, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="—"
                                <?= $readonly ? 'readonly' : '' ?>
                                data-critere-id="<?= $cid ?>"
                            >
                            <span class="cm-eval-grid__note-unit">/<?= number_format($bareme, 0) ?></span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="cm-eval-grid__total-row">
                    <td class="cm-eval-grid__td cm-eval-grid__td--total-label">TOTAL</td>
                    <td class="cm-eval-grid__td cm-eval-grid__td--bareme">/<?= (int) $totalBareme ?></td>
                    <td class="cm-eval-grid__td cm-eval-grid__td--note">
                        <div class="cm-eval-grid__note-wrap">
                            <input
                                type="text"
                                id="<?= $moyenneId ?>"
                                name="<?= htmlspecialchars($name_prefix . '_moyenne', ENT_QUOTES, 'UTF-8') ?>"
                                class="cm-eval-grid__moyenne-display"
                                value="<?= htmlspecialchars($moyenne, ENT_QUOTES, 'UTF-8') ?>"
                                readonly
                                placeholder="—"
                            >
                            <span class="cm-eval-grid__note-unit">/<?= (int) $totalBareme ?></span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="cm-eval-grid__comment-section">
        <label class="cm-eval-grid__comment-label" for="<?= $commentId ?>">
            <?= htmlspecialchars($commentaire_label, ENT_QUOTES, 'UTF-8') ?>
        </label>
        <textarea
            id="<?= $commentId ?>"
            name="<?= htmlspecialchars($commentaire_name, ENT_QUOTES, 'UTF-8') ?>"
            class="cm-eval-grid__comment-input"
            rows="2"
            <?= $readonly ? 'readonly' : '' ?>
        ><?= htmlspecialchars($commentaire, ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <?php if ($show_buttons && !$readonly): ?>
    <div class="cm-eval-grid__actions">
        <button type="submit" class="cm-btn is-success is-sm">
            <i class="fas fa-check" aria-hidden="true"></i>
            <?= htmlspecialchars($submit_label, ENT_QUOTES, 'UTF-8') ?>
        </button>
    </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var grid = document.getElementById('<?= $gridId ?>');
    if (!grid) return;
    var moyenneField = document.getElementById('<?= $moyenneId ?>');

    function calcMoyenne() {
        var rows = grid.querySelectorAll('.cm-eval-grid__row');
        var total = 0, allFilled = true;
        rows.forEach(function (row) {
            var bareme = parseFloat(row.dataset.bareme) || 0;
            var input = row.querySelector('.cm-eval-grid__note-field');
            var val = input ? parseFloat(input.value) : NaN;
            if (!isNaN(val) && val >= 0 && val <= bareme) {
                total += val;
            } else {
                allFilled = false;
            }
        });
        if (!moyenneField) return;
        moyenneField.value = allFilled ? total.toFixed(2) : '';
    }

    grid.querySelectorAll('.cm-eval-grid__note-field').forEach(function (input) {
        input.addEventListener('input', calcMoyenne);
        input.addEventListener('change', calcMoyenne);
    });

    calcMoyenne();
})();
</script>
