<?php
$data = is_array($GLOBALS['evaluationS3Data'] ?? null) ? $GLOBALS['evaluationS3Data'] : [];
$years = is_array($data['years'] ?? null) ? $data['years'] : [];
$students = is_array($data['students'] ?? null) ? $data['students'] : [];
$student = is_array($data['student'] ?? null) ? $data['student'] : null;
$grid = is_array($data['grid'] ?? null) ? $data['grid'] : ['rows' => []];
$rows = is_array($grid['rows'] ?? null) ? $grid['rows'] : [];
$selectedYear = (int) ($data['year_id'] ?? 0);
$batch = is_array($data['import_batch'] ?? null) ? $data['import_batch'] : null;
$canEditS3 = function_exists('canEdit') && canEdit('gestion_notes_evaluations');
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$yearLabel = static function (array $year): string {
    if (!empty($year['date_deb']) && !empty($year['date_fin'])) {
        return date('Y', strtotime((string) $year['date_deb'])) . '-' . date('Y', strtotime((string) $year['date_fin']));
    }
    return (string) ($year['id_annee_acad'] ?? '');
};
$studentId = (string) ($student['num_carte_etud'] ?? '');
?>
<section class="cm-prd3-screen cm-s3-screen">
    <?php cm_component('layout/page-header', [
        'title' => 'Évaluations M2 / S1',
        'subtitle' => 'Saisie détaillée des épreuves écrites — S3, S9 et M2 S1 sont un même semestre.',
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
        <?php cm_component('ui/alert-box', ['type' => 'warning', 'message' => 'Le référentiel UE sélectionné totalise ' . number_format((float) ($grid['expected_credits'] ?? 0), 2, ',', ' ') . ' crédits au lieu des 30 crédits attendus. Vérifiez la maquette avant toute validation.']); ?>
    <?php endif; ?>

    <div class="cm-card cm-mb-md">
        <div class="cm-card__header cm-flex-between cm-flex-wrap cm-flex-gap-sm">
            <div>
                <h3 class="cm-card__title">Sélection du dossier</h3>
                <p class="cm-card__subtitle">Le référentiel officiel des UE est utilisé pour construire le PV.</p>
            </div>
            <div class="cm-flex cm-flex-wrap cm-flex-gap-sm">
                <a class="cm-btn is-light is-sm" href="?page=gestion_notes_evaluations">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Moyennes globales
                </a>
                <a class="cm-btn is-secondary is-sm" href="?page=gestion_notes_evaluations&tab=ue">
                    <i class="fas fa-book" aria-hidden="true"></i> Référentiel UE
                </a>
            </div>
        </div>
        <div class="cm-card__body">
            <form method="GET" class="cm-grid-3 cm-s3-filter-form">
                <input type="hidden" name="page" value="gestion_notes_evaluations">
                <input type="hidden" name="tab" value="evaluations_s3">
                <div class="cm-form-group">
                    <label class="cm-form-label" for="s3Year">Année académique</label>
                    <select class="cm-form-control" id="s3Year" name="annee" required>
                        <?php foreach ($years as $year): ?>
                            <option value="<?= $escape($year['id_annee_acad'] ?? '') ?>" <?= (int) ($year['id_annee_acad'] ?? 0) === $selectedYear ? 'selected' : '' ?>>
                                <?= $escape($yearLabel($year)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cm-form-group">
                    <label class="cm-form-label" for="s3Student">Étudiant</label>
                    <select class="cm-form-control" id="s3Student" name="student">
                        <option value="">-- Sélectionner un étudiant --</option>
                        <?php foreach ($students as $item): ?>
                            <?php $itemId = (string) ($item['num_carte_etud'] ?? ''); ?>
                            <option value="<?= $escape($itemId) ?>" <?= $itemId === $studentId ? 'selected' : '' ?>>
                                <?= $escape(trim((string) ($item['nom_etu'] ?? '') . ' ' . (string) ($item['prenom_etu'] ?? '')) . ' — ' . $itemId) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cm-form-group">
                    <label class="cm-form-label" for="s3Search">Recherche</label>
                    <input class="cm-form-control" id="s3Search" name="search" value="<?= $escape($_GET['search'] ?? '') ?>" placeholder="Nom, prénom ou matricule">
                </div>
                <div class="cm-form-buttons cm-grid-span-all">
                    <button class="cm-btn is-primary is-sm" type="submit"><i class="fas fa-filter" aria-hidden="true"></i> Afficher</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($student !== null): ?>
        <div class="cm-card cm-mb-md">
            <div class="cm-card__header cm-flex-between cm-flex-wrap cm-flex-gap-sm">
                <div>
                    <h3 class="cm-card__title"><?= $escape(trim((string) ($student['nom_etu'] ?? '') . ' ' . (string) ($student['prenom_etu'] ?? ''))) ?></h3>
                    <p class="cm-card__subtitle">Carte : <?= $escape($studentId) ?> · Identifiant permanent : <?= $escape($student['num_ident_etud'] ?? '-') ?></p>
                </div>
                <span class="cm-badge <?= !empty($student['nouveau']) ? 'is-success' : 'is-warning' ?>">
                    <?= !empty($student['nouveau']) ? 'Nouveau' : 'Redoublant' ?> en Master 2
                </span>
                <a class="cm-btn is-light is-sm" target="_blank" href="?page=gestion_notes_evaluations&tab=evaluations_s3&action=generer_pv_ecrits&student=<?= urlencode($studentId) ?>&annee=<?= (int) $selectedYear ?>">
                    <i class="fas fa-file-pdf" aria-hidden="true"></i> Prévisualiser le PV
                </a>
                <a class="cm-btn is-light is-sm" target="_blank" href="?page=gestion_notes_evaluations&tab=evaluations_s3&action=generer_autorisation&student=<?= urlencode($studentId) ?>&annee=<?= (int) $selectedYear ?>">Autorisation</a>
                <a class="cm-btn is-light is-sm" target="_blank" href="?page=gestion_notes_evaluations&tab=evaluations_s3&action=generer_suivi_directeur&student=<?= urlencode($studentId) ?>&annee=<?= (int) $selectedYear ?>">Suivi directeur</a>
                <a class="cm-btn is-light is-sm" target="_blank" href="?page=gestion_notes_evaluations&tab=evaluations_s3&action=generer_suivi_encadreur&student=<?= urlencode($studentId) ?>&annee=<?= (int) $selectedYear ?>">Suivi encadreur</a>
            </div>
            <div class="cm-card__body">
                <form method="POST" action="?page=gestion_notes_evaluations&tab=evaluations_s3&action=enregistrer_evaluations_s3&student=<?= urlencode($studentId) ?>">
                    <?php cm_component('form/csrf-token'); ?>
                    <input type="hidden" name="action" value="enregistrer_evaluations_s3">
                    <input type="hidden" name="student" value="<?= $escape($studentId) ?>">
                    <input type="hidden" name="id_annee_acad" value="<?= (int) $selectedYear ?>">
                    <div class="cm-table-wrapper cm-s3-table-wrapper">
                        <table class="cm-data-table cm-s3-table">
                            <thead>
                            <tr>
                                <th rowspan="2">Code UE</th>
                                <th rowspan="2">Intitulé UE</th>
                                <th colspan="3" class="is-session-normal">Session normale</th>
                                <th colspan="3" class="is-session-rattrapage">Session de rattrapage</th>
                            </tr>
                            <tr>
                                <th class="is-session-normal">Note /20</th>
                                <th class="is-session-normal">Crédits</th>
                                <th class="is-session-normal">Total</th>
                                <th class="is-session-rattrapage">Note /20</th>
                                <th class="is-session-rattrapage">Crédits</th>
                                <th class="is-session-rattrapage">Total</th>
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
                                    <td><?= $escape($ue['libelle_ue'] ?? '') ?></td>
                                    <td>
                                        <input class="cm-form-control cm-s3-note" type="number" min="0" max="20" step="0.01"
                                               name="evaluations[<?= $ueId ?>][normal][note]" value="<?= $escape($normal['note'] ?? '') ?>" <?= $canEditS3 ? '' : 'disabled' ?> aria-label="Note normale <?= $escape($ue['code_ue'] ?? '') ?>">
                                        <input class="cm-s3-date" type="date" name="evaluations[<?= $ueId ?>][normal][date]" value="<?= $escape($normal['date'] ?? date('Y-m-d')) ?>" <?= $canEditS3 ? '' : 'disabled' ?> aria-label="Date note normale">
                                    </td>
                                    <td class="cm-number"><?= number_format($credit, 2, ',', ' ') ?></td>
                                    <td class="cm-number"><?= $row['normal_points'] !== null ? number_format((float) $row['normal_points'], 2, ',', ' ') : '-' ?></td>
                                    <td>
                                        <input class="cm-form-control cm-s3-note" type="number" min="0" max="20" step="0.01"
                                               name="evaluations[<?= $ueId ?>][rattrapage][note]" value="<?= $escape($rattrapage['note'] ?? '') ?>" <?= $canEditS3 ? '' : 'disabled' ?> aria-label="Note de rattrapage <?= $escape($ue['code_ue'] ?? '') ?>">
                                        <input class="cm-s3-date" type="date" name="evaluations[<?= $ueId ?>][rattrapage][date]" value="<?= $escape($rattrapage['date'] ?? date('Y-m-d')) ?>" <?= $canEditS3 ? '' : 'disabled' ?> aria-label="Date note rattrapage">
                                    </td>
                                    <td class="cm-number"><?= number_format($credit, 2, ',', ' ') ?></td>
                                    <td class="cm-number"><?= $row['rattrapage_points'] !== null ? number_format((float) $row['rattrapage_points'], 2, ',', ' ') : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <th colspan="2">Total crédits saisis / attendus</th>
                                <th colspan="2" class="cm-number"><?= number_format((float) ($grid['normal_credits'] ?? 0), 2, ',', ' ') ?> / <?= number_format((float) ($grid['expected_credits'] ?? 0), 2, ',', ' ') ?></th>
                                <th class="cm-number"><?= number_format((float) ($grid['normal_total'] ?? 0), 2, ',', ' ') ?></th>
                                <th colspan="2" class="cm-number"><?= number_format((float) ($grid['rattrapage_credits'] ?? 0), 2, ',', ' ') ?> / <?= number_format((float) ($grid['expected_credits'] ?? 0), 2, ',', ' ') ?></th>
                                <th class="cm-number"><?= number_format((float) ($grid['rattrapage_total'] ?? 0), 2, ',', ' ') ?></th>
                            </tr>
                            <tr>
                                <th colspan="2">Moyenne /20</th>
                                <th colspan="3" class="cm-number cm-s3-average"><?= $grid['normal_average'] !== null ? number_format((float) $grid['normal_average'], 2, ',', ' ') . ' / 20' : 'Non calculée' ?></th>
                                <th colspan="3" class="cm-number cm-s3-average"><?= $grid['rattrapage_average'] !== null ? number_format((float) $grid['rattrapage_average'], 2, ',', ' ') . ' / 20' : 'Non calculée' ?></th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="cm-form-buttons cm-mt-md">
                        <?php if ($canEditS3): ?>
                            <button class="cm-btn is-primary" type="submit"><i class="fas fa-save" aria-hidden="true"></i> Enregistrer les évaluations</button>
                        <?php else: ?>
                            <span class="cm-text-muted">Consultation seule pour cette année académique.</span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="cm-card cm-mb-md"><div class="cm-card__body"><p class="cm-empty-state">Sélectionnez un étudiant pour saisir ses évaluations UE.</p></div></div>
    <?php endif; ?>

    <div class="cm-card cm-mb-md">
        <div class="cm-card__header"><h3 class="cm-card__title">Importation Excel des évaluations</h3></div>
        <div class="cm-card__body">
            <p class="cm-card__subtitle">Colonnes attendues : annee_academique, semestre, identifiant_etudiant, code_ue, note, date_evaluation, session_normale.</p>
            <?php if ($canEditS3): ?>
                <form method="POST" enctype="multipart/form-data" action="?page=gestion_notes_evaluations&tab=evaluations_s3&action=import_evaluations_s3" class="cm-flex cm-flex-wrap cm-flex-gap-sm cm-align-end">
                    <?php cm_component('form/csrf-token'); ?>
                    <input type="hidden" name="action" value="import_evaluations_s3">
                    <div class="cm-form-group"><label class="cm-form-label" for="evaluationFile">Fichier CSV/XLS/XLSX</label><input class="cm-form-control" id="evaluationFile" type="file" name="evaluation_file" accept=".csv,.xls,.xlsx" required></div>
                    <button class="cm-btn is-secondary" type="submit"><i class="fas fa-upload" aria-hidden="true"></i> Prévisualiser</button>
                </form>
            <?php endif; ?>
            <?php if ($batch !== null): ?>
                <div class="cm-import-summary cm-mt-md">
                    <strong>Lot #<?= (int) ($batch['id_batch'] ?? 0) ?> — <?= $escape($batch['nom_fichier'] ?? '') ?></strong>
                    <span><?= (int) ($batch['lignes_valides'] ?? 0) ?> valide(s), <?= (int) ($batch['lignes_erreur'] ?? 0) ?> erreur(s)</span>
                </div>
                <div class="cm-table-wrapper cm-mt-md">
                    <table class="cm-data-table cm-import-preview-table">
                        <thead><tr><th>Ligne</th><th>Étudiant</th><th>UE</th><th>Année</th><th>Note</th><th>Session</th><th>Statut</th><th>Message</th></tr></thead>
                        <tbody>
                        <?php foreach ((array) ($batch['rows'] ?? []) as $importRow): ?>
                            <tr>
                                <td><?= (int) ($importRow['numero_ligne'] ?? 0) ?></td>
                                <td><?= $escape($importRow['num_etu'] ?? '-') ?></td>
                                <td><?= (int) ($importRow['id_ue'] ?? 0) ?: '-' ?></td>
                                <td><?= (int) ($importRow['id_annee_acad'] ?? 0) ?: '-' ?></td>
                                <td><?= $escape($importRow['note'] ?? '-') ?></td>
                                <td><?= !empty($importRow['session_normale']) ? 'Normale' : 'Rattrapage' ?></td>
                                <td><span class="cm-badge <?= ($importRow['statut_ligne'] ?? '') === 'valide' ? 'is-success' : (($importRow['statut_ligne'] ?? '') === 'importe' ? 'is-info' : 'is-danger') ?>"><?= $escape($importRow['statut_ligne'] ?? '') ?></span></td>
                                <td><?= $escape($importRow['message_erreur'] ?? $importRow['message_avertissement'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (($batch['statut_batch'] ?? '') === 'previsualisation' && (int) ($batch['lignes_erreur'] ?? 0) === 0 && $canEditS3): ?>
                    <form method="POST" action="?page=gestion_notes_evaluations&tab=evaluations_s3&action=confirmer_import_evaluations_s3" class="cm-mt-md">
                        <?php cm_component('form/csrf-token'); ?>
                        <input type="hidden" name="action" value="confirmer_import_evaluations_s3">
                        <input type="hidden" name="batch_id" value="<?= (int) ($batch['id_batch'] ?? 0) ?>">
                        <button class="cm-btn is-primary" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Confirmer l’import transactionnel</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .cm-s3-screen .cm-s3-filter-form { align-items: end; }
    .cm-s3-screen .cm-grid-span-all { grid-column: 1 / -1; }
    .cm-s3-table th, .cm-s3-table td { vertical-align: middle; }
    .cm-s3-table .is-session-normal { background: rgba(31, 111, 90, .08); }
    .cm-s3-table .is-session-rattrapage { background: rgba(178, 105, 24, .09); }
    .cm-s3-table td:first-child, .cm-s3-table td:nth-child(n+3), .cm-s3-table th { text-align: center; }
    .cm-s3-table td:nth-child(2) { min-width: 16rem; text-align: left; }
    .cm-s3-note { min-width: 5.3rem; text-align: center; }
    .cm-s3-date { display: block; width: 100%; margin-top: .25rem; font-size: .72rem; }
    .cm-number { text-align: center; white-space: nowrap; }
    .cm-s3-average { font-size: 1rem; }
    .cm-import-summary { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: .75rem 1rem; border-left: 4px solid var(--cm-primary, #2a5f82); background: rgba(42,95,130,.06); }
    .cm-import-preview-table td, .cm-import-preview-table th { font-size: .84rem; }
    @media (max-width: 900px) { .cm-s3-table-wrapper { overflow-x: auto; } .cm-s3-table { min-width: 980px; } }
</style>
