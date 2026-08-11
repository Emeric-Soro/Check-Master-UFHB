<?php
$data = is_array($GLOBALS['candidatureEvaluationsData'] ?? null) ? $GLOBALS['candidatureEvaluationsData'] : [];
$years = is_array($data['years'] ?? null) ? $data['years'] : [];
$students = is_array($data['students'] ?? null) ? $data['students'] : [];
$student = is_array($data['student'] ?? null) ? $data['student'] : null;
$grid = is_array($data['grid'] ?? null) ? $data['grid'] : ['rows' => []];
$rows = is_array($grid['rows'] ?? null) ? $grid['rows'] : [];
$selectedYear = (int) ($data['year_id'] ?? 0);
$studentId = (string) ($student['num_carte_etud'] ?? '');
$canEdit = function_exists('canEdit') && canEdit('gestion_candidatures_soutenance');
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$yearLabel = static function (array $year): string {
    if (!empty($year['date_deb']) && !empty($year['date_fin'])) {
        return date('Y', strtotime((string) $year['date_deb'])) . '-' . date('Y', strtotime((string) $year['date_fin']));
    }
    return (string) ($year['id_annee_acad'] ?? '');
};
?>
<section class="cm-prd3-screen cm-candidature-evaluations-screen">
    <?php cm_component('layout/page-header', [
        'title' => 'Évaluations des candidatures — M2/S1',
        'subtitle' => 'Grille Excel des UE, notes, crédits et résultats par étudiant.',
        'icon' => 'fa-table-list',
    ]); ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => (string) $_SESSION['success']]); unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => (string) $_SESSION['error']]); unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['messageErreur'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => (string) $GLOBALS['messageErreur']]); ?>
    <?php endif; ?>

    <?php if (array_key_exists('credits_are_compliant', $grid) && !$grid['credits_are_compliant']): ?>
        <?php cm_component('ui/alert-box', ['type' => 'warning', 'message' => 'Le référentiel sélectionné totalise ' . number_format((float) ($grid['expected_credits'] ?? 0), 2, ',', ' ') . ' crédits au lieu des 30 attendus.']); ?>
    <?php endif; ?>

    <div class="cm-card cm-mb-md cm-candidatures-submenu">
        <div class="cm-card__body cm-flex cm-flex-wrap cm-flex-gap-sm">
            <a class="cm-btn is-light is-sm" href="?page=gestion_candidatures"><i class="fas fa-folder-open" aria-hidden="true"></i> Dossiers de candidatures</a>
            <span class="cm-btn is-primary is-sm"><i class="fas fa-table-list" aria-hidden="true"></i> Évaluations M2/S1</span>
        </div>
    </div>

    <div class="cm-card cm-mb-md">
        <div class="cm-card__header cm-flex-between cm-flex-wrap cm-flex-gap-sm">
            <div>
                <h3 class="cm-card__title">Sélection de l’étudiant</h3>
                <p class="cm-card__subtitle">Choisissez une année et un étudiant pour charger ses notes enregistrées.</p>
            </div>
            <a class="cm-btn is-light is-sm" href="?page=gestion_candidatures">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Retour aux candidatures
            </a>
        </div>
        <div class="cm-card__body">
            <form method="GET" class="cm-grid-3 cm-candidature-evaluations-filter">
                <input type="hidden" name="page" value="gestion_candidatures">
                <input type="hidden" name="action" value="evaluations_m2_s1">
                <div class="cm-form-group">
                    <label class="cm-form-label" for="candidatureEvalYear">Année académique</label>
                    <select class="cm-form-control" id="candidatureEvalYear" name="annee" required>
                        <?php foreach ($years as $year): ?>
                            <option value="<?= $escape($year['id_annee_acad'] ?? '') ?>" <?= (int) ($year['id_annee_acad'] ?? 0) === $selectedYear ? 'selected' : '' ?>><?= $escape($yearLabel($year)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cm-form-group">
                    <label class="cm-form-label" for="candidatureEvalStudent">Étudiant</label>
                    <select class="cm-form-control" id="candidatureEvalStudent" name="student">
                        <option value="">-- Sélectionner un étudiant --</option>
                        <?php foreach ($students as $item): ?>
                            <?php $itemId = (string) ($item['num_carte_etud'] ?? ''); ?>
                            <option value="<?= $escape($itemId) ?>" <?= $itemId === $studentId ? 'selected' : '' ?>><?= $escape(trim((string) ($item['nom_etu'] ?? '') . ' ' . (string) ($item['prenom_etu'] ?? '')) . ' — ' . $itemId) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cm-form-group">
                    <label class="cm-form-label" for="candidatureEvalSearch">Recherche étudiant</label>
                    <input class="cm-form-control" id="candidatureEvalSearch" name="search" value="<?= $escape($_GET['search'] ?? '') ?>" placeholder="Nom, prénom ou matricule">
                </div>
                <div class="cm-form-buttons cm-grid-span-all">
                    <button class="cm-btn is-primary" type="submit"><i class="fas fa-folder-open" aria-hidden="true"></i> Charger la grille</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($student !== null): ?>
        <div class="cm-card cm-mb-md">
            <div class="cm-card__header cm-candidature-evaluations-student-header">
                <div>
                    <h3 class="cm-card__title"><?= $escape(trim((string) ($student['nom_etu'] ?? '') . ' ' . (string) ($student['prenom_etu'] ?? ''))) ?></h3>
                    <p class="cm-card__subtitle">Matricule : <?= $escape($studentId) ?> · Identifiant : <?= $escape($student['num_ident_etud'] ?? '-') ?></p>
                </div>
                <span class="cm-badge <?= !empty($student['nouveau']) ? 'is-success' : 'is-warning' ?>"><?= !empty($student['nouveau']) ? 'Nouveau' : 'Redoublant' ?> en Master 2</span>
            </div>
            <div class="cm-card__body">
                <form method="POST" action="?page=gestion_candidatures&action=evaluations_m2_s1&student=<?= urlencode($studentId) ?>&annee=<?= $selectedYear ?>">
                    <?php cm_component('form/csrf-token'); ?>
                    <input type="hidden" name="student" value="<?= $escape($studentId) ?>">
                    <input type="hidden" name="id_annee_acad" value="<?= $selectedYear ?>">
                    <div class="cm-table-wrapper cm-candidature-evaluations-table-wrapper">
                        <table class="cm-data-table cm-candidature-evaluations-table">
                            <thead>
                            <tr>
                                <th rowspan="2">Code UE</th>
                                <th rowspan="2">Intitulé UE</th>
                                <th colspan="3" class="is-normal">Session normale</th>
                                <th colspan="3" class="is-rattrapage">Session rattrapage</th>
                            </tr>
                            <tr>
                                <th class="is-normal">Note /20</th><th class="is-normal">Crédits</th><th class="is-normal">Total</th>
                                <th class="is-rattrapage">Note /20</th><th class="is-rattrapage">Crédits</th><th class="is-rattrapage">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($rows as $row): ?>
                                <?php
                                $ue = is_array($row['ue'] ?? null) ? $row['ue'] : [];
                                $ueId = (int) ($ue['id_ue'] ?? 0);
                                $credit = (float) ($ue['credit_ue'] ?? 0);
                                $normal = is_array($row['normal'] ?? null) ? $row['normal'] : [];
                                $rattrapage = is_array($row['rattrapage'] ?? null) ? $row['rattrapage'] : [];
                                ?>
                                <tr>
                                    <td><strong><?= $escape($ue['code_ue'] ?? '') ?></strong></td>
                                    <td class="ue-label"><?= $escape($ue['libelle_ue'] ?? '') ?></td>
                                    <td class="note-cell"><input class="cm-form-control eval-note" type="number" min="0" max="20" step="0.01" name="evaluations[<?= $ueId ?>][normal][note]" value="<?= $escape($normal['note'] ?? '') ?>" <?= $canEdit ? '' : 'disabled' ?>></td>
                                    <td class="number-cell"><?= number_format($credit, 2, ',', ' ') ?></td>
                                    <td class="number-cell calc-normal"><?= $row['normal_points'] !== null ? number_format((float) $row['normal_points'], 2, ',', ' ') : '-' ?></td>
                                    <td class="note-cell"><input class="cm-form-control eval-note" type="number" min="0" max="20" step="0.01" name="evaluations[<?= $ueId ?>][rattrapage][note]" value="<?= $escape($rattrapage['note'] ?? '') ?>" <?= $canEdit ? '' : 'disabled' ?>></td>
                                    <td class="number-cell"><?= number_format($credit, 2, ',', ' ') ?></td>
                                    <td class="number-cell calc-rattrapage"><?= $row['rattrapage_points'] !== null ? number_format((float) $row['rattrapage_points'], 2, ',', ' ') : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                            <tr><th colspan="2">Crédits saisis / attendus</th><th colspan="2" class="number-cell"><?= number_format((float) ($grid['normal_credits'] ?? 0), 2, ',', ' ') ?> / <?= number_format((float) ($grid['expected_credits'] ?? 0), 2, ',', ' ') ?></th><th class="number-cell"><?= number_format((float) ($grid['normal_total'] ?? 0), 2, ',', ' ') ?></th><th colspan="2" class="number-cell"><?= number_format((float) ($grid['rattrapage_credits'] ?? 0), 2, ',', ' ') ?> / <?= number_format((float) ($grid['expected_credits'] ?? 0), 2, ',', ' ') ?></th><th class="number-cell"><?= number_format((float) ($grid['rattrapage_total'] ?? 0), 2, ',', ' ') ?></th></tr>
                            <tr><th colspan="2">Moyenne /20</th><th colspan="3" class="number-cell average-cell"><?= $grid['normal_average'] !== null ? number_format((float) $grid['normal_average'], 2, ',', ' ') . ' / 20' : 'Non calculée' ?></th><th colspan="3" class="number-cell average-cell"><?= $grid['rattrapage_average'] !== null ? number_format((float) $grid['rattrapage_average'], 2, ',', ' ') . ' / 20' : 'Non calculée' ?></th></tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="cm-form-buttons cm-mt-md">
                        <?php if ($canEdit): ?><button class="cm-btn is-primary" type="submit"><i class="fas fa-save" aria-hidden="true"></i> Enregistrer les notes</button><?php else: ?><span class="cm-text-muted">Consultation seule pour cette année académique.</span><?php endif; ?>
                        <a class="cm-btn is-light" target="_blank" href="?page=gestion_notes_evaluations&tab=evaluations_s3&action=generer_pv_ecrits&student=<?= urlencode($studentId) ?>&annee=<?= $selectedYear ?>"><i class="fas fa-file-pdf" aria-hidden="true"></i> Prévisualiser le PV</a>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="cm-card"><div class="cm-card__body"><p class="cm-empty-state">Sélectionnez un étudiant pour afficher ses UE et ses notes.</p></div></div>
    <?php endif; ?>
</section>
<style>
.cm-candidature-evaluations-filter { align-items: end; }
.cm-candidature-evaluations-screen .cm-grid-span-all { grid-column: 1 / -1; }
.cm-candidature-evaluations-student-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
.cm-candidature-evaluations-table th, .cm-candidature-evaluations-table td { vertical-align:middle; }
.cm-candidature-evaluations-table .is-normal { background:rgba(31,111,90,.09); }
.cm-candidature-evaluations-table .is-rattrapage { background:rgba(178,105,24,.1); }
.cm-candidature-evaluations-table .ue-label { min-width:18rem; text-align:left; }
.cm-candidature-evaluations-table .note-cell { min-width:7rem; }
.cm-candidature-evaluations-table .eval-note { min-width:5.5rem; text-align:center; }
.cm-candidature-evaluations-table .number-cell { text-align:center; white-space:nowrap; }
.cm-candidature-evaluations-table .average-cell { font-size:1rem; }
@media (max-width: 950px) { .cm-candidature-evaluations-table-wrapper { overflow-x:auto; } .cm-candidature-evaluations-table { min-width:980px; } }
</style>
<script>
(function () {
    const table = document.querySelector('.cm-candidature-evaluations-table');
    if (!table) return;
    const format = value => Number.isFinite(value) ? value.toFixed(2).replace('.', ',') : '-';
    const recalculate = () => {
        let normalPoints = 0, normalCredits = 0, normalWeighted = 0;
        let catchupPoints = 0, catchupCredits = 0, catchupWeighted = 0;
        table.querySelectorAll('tbody tr').forEach(row => {
            const creditCells = row.querySelectorAll('.number-cell');
            const credit = parseFloat((creditCells[0]?.textContent || '0').replace(',', '.')) || 0;
            const normalInput = row.querySelector('input[name*="[normal][note]"]');
            const catchupInput = row.querySelector('input[name*="[rattrapage][note]"]');
            const normal = normalInput && normalInput.value !== '' ? parseFloat(normalInput.value) : NaN;
            const catchup = catchupInput && catchupInput.value !== '' ? parseFloat(catchupInput.value) : NaN;
            const normalCell = row.querySelector('.calc-normal');
            const catchupCell = row.querySelector('.calc-rattrapage');
            if (Number.isFinite(normal) && normal >= 0 && normal <= 20) {
                const points = normal / 20 * credit;
                normalPoints += points; normalCredits += credit; normalWeighted += points;
                if (normalCell) normalCell.textContent = format(points);
            } else if (normalCell) normalCell.textContent = '-';
            if (Number.isFinite(catchup) && catchup >= 0 && catchup <= 20) {
                const points = catchup / 20 * credit;
                catchupPoints += points; catchupCredits += credit; catchupWeighted += points;
                if (catchupCell) catchupCell.textContent = format(points);
            } else if (catchupCell) catchupCell.textContent = '-';
        });
        const footer = table.querySelector('tfoot');
        if (!footer) return;
        const rows = footer.querySelectorAll('tr');
        const creditCells = rows[0]?.querySelectorAll('.number-cell') || [];
        const averageCells = rows[1]?.querySelectorAll('.average-cell') || [];
        if (creditCells[0]) creditCells[0].textContent = format(normalCredits) + ' / ' + creditCells[0].textContent.split('/').pop().trim();
        if (creditCells[1]) creditCells[1].textContent = format(normalWeighted);
        if (creditCells[2]) creditCells[2].textContent = format(catchupCredits) + ' / ' + creditCells[2].textContent.split('/').pop().trim();
        if (creditCells[3]) creditCells[3].textContent = format(catchupWeighted);
        if (averageCells[0]) averageCells[0].textContent = normalCredits > 0 ? format(normalWeighted / normalCredits * 20) + ' / 20' : 'Non calculée';
        if (averageCells[1]) averageCells[1].textContent = catchupCredits > 0 ? format(catchupWeighted / catchupCredits * 20) + ' / 20' : 'Non calculée';
    };
    table.querySelectorAll('input.eval-note').forEach(input => input.addEventListener('input', recalculate));
    recalculate();
})();
</script>
