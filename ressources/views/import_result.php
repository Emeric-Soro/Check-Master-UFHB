<?php
$importSummary = $GLOBALS['importSummary'] ?? ['total_success' => 0, 'total_errors' => 0, 'successes' => [], 'errors' => []];
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
$hasErrors = ($importSummary['total_errors'] ?? 0) > 0;
$totalLines = count($importSummary['successes'] ?? []) + count($importSummary['errors'] ?? []);
?>

<section class="cm-prd3-crud-screen">
    <div class="cm-crud-wrapper">
        <!-- En-tête avec statut -->
        <?php ob_start(); ?>
        <div class="cm-text-center">
            <?php if ($hasErrors): ?>
                <div class="cm-inline-flex cm-items-center cm-gap-2 cm-px-4 cm-py-2 cm-rounded-full cm-bg-warning-light cm-text-warning cm-mb-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="cm-font-medium">Importation terminée avec des erreurs</span>
                </div>
                <p class="cm-text-muted">Certaines lignes n'ont pas pu être importées. Veuillez consulter les détails ci-dessous.</p>
            <?php else: ?>
                <div class="cm-inline-flex cm-items-center cm-gap-2 cm-px-4 cm-py-2 cm-rounded-full cm-bg-success-light cm-text-success cm-mb-3">
                    <i class="fas fa-check-circle"></i>
                    <span class="cm-font-medium">Importation terminée avec succès</span>
                </div>
                <p class="cm-text-muted">Toutes les lignes ont été importées avec succès.</p>
            <?php endif; ?>
        </div>
        <?php
        cm_component('crud/form-pole', [
            'title' => 'Résultat de l\'import',
            'icon' => 'fa-file-import',
            'content' => ob_get_clean(),
        ]);
        ?>

        <!-- Statistiques -->
        <div class="cm-grid-3 cm-gap-4 cm-mb-4">
            <div class="cm-card cm-card-hover cm-p-4 cm-text-center">
                <div class="cm-text-3xl cm-font-bold cm-text-primary cm-mb-1">
                    <?= number_format($totalLines) ?>
                </div>
                <div class="cm-text-muted cm-text-sm">Lignes lues</div>
            </div>
            <div class="cm-card cm-card-hover cm-p-4 cm-text-center">
                <div class="cm-text-3xl cm-font-bold cm-text-success cm-mb-1">
                    <?= number_format($importSummary['total_success'] ?? 0) ?>
                </div>
                <div class="cm-text-muted cm-text-sm">Succès</div>
            </div>
            <div class="cm-card cm-card-hover cm-p-4 cm-text-center">
                <div class="cm-text-3xl cm-font-bold <?= $hasErrors ? 'cm-text-danger' : 'cm-text-success' ?> cm-mb-1">
                    <?= number_format($importSummary['total_errors'] ?? 0) ?>
                </div>
                <div class="cm-text-muted cm-text-sm">Échecs</div>
            </div>
        </div>

        <!-- Détails du fichier -->
        <div class="cm-card cm-mb-4">
            <div class="cm-card-header">
                <h3 class="cm-card-title"><i class="fas fa-file-alt cm-mr-2"></i>Informations du fichier</h3>
            </div>
            <div class="cm-card-body">
                <div class="cm-grid-2 cm-gap-4">
                    <div class="cm-flex cm-items-center cm-gap-3">
                        <i class="fas fa-file cm-text-muted"></i>
                        <div>
                            <p class="cm-text-sm cm-text-muted cm-mb-0">Fichier traité</p>
                            <p class="cm-font-medium"><?= htmlspecialchars($importSummary['fileName'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    <div class="cm-flex cm-items-center cm-gap-3">
                        <i class="fas fa-clock cm-text-muted"></i>
                        <div>
                            <p class="cm-text-sm cm-text-muted cm-mb-0">Date d'import</p>
                            <p class="cm-font-medium"><?= date('d/m/Y H:i:s') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Erreurs détaillées -->
        <?php if ($hasErrors): ?>
        <div class="cm-card cm-mb-4">
            <div class="cm-card-header cm-flex cm-justify-between cm-items-center">
                <h3 class="cm-card-title"><i class="fas fa-times-circle cm-mr-2 cm-text-danger"></i>Erreurs détectées</h3>
                <?php if (canView()): ?>
                <button class="cm-btn is-light is-sm" onclick="exportErrors()">
                    <i class="fas fa-download cm-mr-1"></i>Exporter les erreurs
                </button>
                <?php endif; ?>
            </div>
            <div class="cm-card-body">
                <div class="cm-table-wrapper" style="max-height: 300px;">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th" style="width: 80px;">Ligne</th>
                                <th class="cm-data-table__th">Erreur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($importSummary['errors'] as $error): ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td">
                                    <code class="cm-bg-gray-100 cm-px-2 cm-py-1 cm-rounded"><?= htmlspecialchars($error['line'] ?? 'N/A') ?></code>
                                </td>
                                <td class="cm-data-table__td cm-text-danger">
                                    <i class="fas fa-exclamation-circle cm-mr-1"></i>
                                    <?= htmlspecialchars($error['message'] ?? 'Erreur inconnue') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Succès détaillés -->
        <?php if (!empty($importSummary['successes'])): ?>
        <div class="cm-card cm-mb-4">
            <div class="cm-card-header">
                <h3 class="cm-card-title"><i class="fas fa-check-circle cm-mr-2 cm-text-success"></i>Lignes importées avec succès</h3>
            </div>
            <div class="cm-card-body">
                <div class="cm-stack cm-gap-2" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach (array_slice($importSummary['successes'], 0, 10) as $success): ?>
                    <div class="cm-flex cm-items-center cm-gap-2 cm-p-2 cm-bg-success-light cm-rounded">
                        <i class="fas fa-check-circle cm-text-success"></i>
                        <span class="cm-text-sm"><?= htmlspecialchars($success) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($importSummary['successes']) > 10): ?>
                    <p class="cm-text-muted cm-text-sm cm-text-center cm-mt-2">
                        et <?= count($importSummary['successes']) - 10 ?> autres lignes...
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="cm-flex cm-justify-center cm-gap-3 cm-flex-wrap">
            <a href="?page=admin_historique" class="cm-btn is-primary">
                <i class="fas fa-list cm-mr-1"></i>
                Voir les données importées
            </a>
            <a href="?page=admin_historique&tab=import" class="cm-btn is-light">
                <i class="fas fa-upload cm-mr-1"></i>
                Lancer un nouvel import
            </a>
        </div>
    </div>
</section>

<script>
function exportErrors() {
    const errors = <?= json_encode($importSummary['errors'] ?? []) ?>;
    if (errors.length === 0) return;

    let csv = 'Ligne,Erreur\n';
    errors.forEach(error => {
        csv += `"${error.line || 'N/A'}","${(error.message || '').replace(/"/g, '""')}"\n`;
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'erreurs_import_<?= date('Y-m-d_H-i-s') ?>.csv';
    link.click();
}
</script>
