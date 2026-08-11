<?php
$data = is_array($GLOBALS['ueReferentielData'] ?? null) ? $GLOBALS['ueReferentielData'] : [];
$ues = is_array($data['ues'] ?? null) ? $data['ues'] : [];
$semesters = is_array($data['semesters'] ?? null) ? $data['semesters'] : [];
$canEditUe = function_exists('canEdit') && canEdit('gestion_notes_evaluations');
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<section class="cm-prd3-screen cm-ue-screen">
    <?php cm_component('layout/page-header', [
        'title' => 'Référentiel UE M2 / S1',
        'subtitle' => 'Saisie préalable des codes officiels et versionnement des crédits.',
        'icon' => 'fa-book-open',
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

    <div class="cm-card cm-mb-md">
        <div class="cm-card__header cm-flex-between cm-flex-wrap cm-flex-gap-sm">
            <div><h3 class="cm-card__title">Nouvelle version UE</h3><p class="cm-card__subtitle">Une UE déjà utilisée ne sera jamais modifiée : une nouvelle version sera créée.</p></div>
            <a class="cm-btn is-light is-sm" href="?page=gestion_notes_evaluations&tab=evaluations_s3"><i class="fas fa-table-list" aria-hidden="true"></i> Saisie des évaluations</a>
        </div>
        <div class="cm-card__body">
            <?php if ($canEditUe): ?>
                <form method="POST" action="?page=gestion_notes_evaluations&tab=ue&action=enregistrer_ue" class="cm-grid-3">
                    <?php cm_component('form/csrf-token'); ?>
                    <input type="hidden" name="action" value="enregistrer_ue">
                    <input type="hidden" name="semestre_code" value="M2_S1">
                    <div class="cm-form-group"><label class="cm-form-label" for="ueCode">Code UE officiel</label><input class="cm-form-control" id="ueCode" name="code_ue" required maxlength="50" placeholder="Ex. MIAGE-S9-01"></div>
                    <div class="cm-form-group"><label class="cm-form-label" for="ueLabel">Libellé UE</label><input class="cm-form-control" id="ueLabel" name="libelle_ue" required maxlength="255"></div>
                    <div class="cm-form-group"><label class="cm-form-label" for="ueCredit">Crédit UE</label><input class="cm-form-control" id="ueCredit" name="credit_ue" type="number" min="0.01" max="60" step="0.01" required></div>
                    <div class="cm-form-group"><label class="cm-form-label" for="ueDate">Date d’application</label><input class="cm-form-control" id="ueDate" name="date_credit" type="date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="cm-form-group"><label class="cm-form-label" for="ueLevel">Niveau (facultatif)</label><input class="cm-form-control" id="ueLevel" name="id_niv_etude" maxlength="2" placeholder="M2"></div>
                    <div class="cm-form-group"><label class="cm-form-label" for="ueOrder">Ordre d’affichage</label><input class="cm-form-control" id="ueOrder" name="ordre_ue" type="number" min="0" value="0"></div>
                    <div class="cm-form-group cm-grid-span-all"><label class="cm-form-label" for="ueParcours">Parcours (facultatif)</label><input class="cm-form-control" id="ueParcours" name="parcours" maxlength="100" placeholder="MIAGE"></div>
                    <div class="cm-form-buttons cm-grid-span-all"><button class="cm-btn is-primary" type="submit"><i class="fas fa-save" aria-hidden="true"></i> Enregistrer la version UE</button></div>
                </form>
            <?php else: ?>
                <p class="cm-text-muted">Consultation seule.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="cm-card">
        <div class="cm-card__header"><h3 class="cm-card__title">Versions enregistrées</h3></div>
        <div class="cm-card__body">
            <div class="cm-table-wrapper">
                <table class="cm-data-table">
                    <thead><tr><th>Code</th><th>Libellé</th><th>Crédits</th><th>Date effet</th><th>Version</th><th>Parcours</th><th>État</th></tr></thead>
                    <tbody>
                    <?php if ($ues === []): ?><tr><td colspan="7" class="cm-empty-state">Aucune UE enregistrée. Saisissez les codes officiels de la maquette avant l’import.</td></tr><?php endif; ?>
                    <?php foreach ($ues as $ue): ?>
                        <tr>
                            <td><strong><?= $escape($ue['code_ue'] ?? '') ?></strong></td>
                            <td><?= $escape($ue['libelle_ue'] ?? '') ?></td>
                            <td><?= number_format((float) ($ue['credit_ue'] ?? 0), 2, ',', ' ') ?></td>
                            <td><?= $escape($ue['date_credit'] ?? '') ?></td>
                            <td><?= (int) ($ue['version_ue'] ?? 0) ?></td>
                            <td><?= $escape($ue['parcours'] ?? '-') ?></td>
                            <td><?= !empty($ue['deja_utilisee']) ? '<span class="cm-badge is-info">Historique protégé</span>' : '<span class="cm-badge is-success">Disponible</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<style>
    .cm-ue-screen .cm-grid-span-all { grid-column: 1 / -1; }
    .cm-ue-screen .cm-form-buttons { align-items: center; }
</style>
