<?php
/**
 * Vue : Archives Soutenances
 * $data['soutenances'] - liste des soutenances archivées
 * $data['salles']      - liste des salles pour filtre
 */
$soutenances = $data['soutenances'] ?? [];
$salles      = $data['salles'] ?? [];
$filters     = $data['filters'] ?? [];
?>

<div class="cm-archive-soutenances">

    <!-- En-tête -->
    <div class="cm-page-header cm-mb-5">
        <div>
            <p class="cm-page-subtitle">
                Historique des soutenances de l'année archivée
            </p>
        </div>
        <div class="cm-flex cm-gap-3">
            <a href="?page=admin_historique" class="cm-btn cm-btn-outline cm-btn-sm">
                <i class="fas fa-arrow-left cm-mr-1"></i> Retour à Historique
            </a>
            <a href="?page=archives_soutenances&export=csv" class="cm-btn cm-btn-primary cm-btn-sm">
                <i class="fas fa-file-csv cm-mr-1"></i> Exporter CSV
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="cm-card cm-mb-4">
        <div class="cm-card-body">
            <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
.cm-content-area form .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form method="GET" class="cm-flex cm-gap-3 cm-flex-wrap cm-items-end">
                <input type="hidden" name="page" value="archives_soutenances">

                <div class="cm-form-group cm-mb-0">
                    <label class="cm-form-label">Salle</label>
                    <select name="salle" class="cm-select">
                        <option value="">Toutes les salles</option>
                        <?php foreach ($salles as $s): ?>
                            <option value="<?= htmlspecialchars($s['id_salle']) ?>"
                                <?= (($filters['salle'] ?? '') == $s['id_salle']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['lib_salle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cm-form-group cm-mb-0">
                    <label class="cm-form-label">Recherche</label>
                    <input type="text" name="search" class="cm-input"
                           placeholder="Étudiant, thème…"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>

                <button type="submit" class="cm-btn cm-btn-primary cm-btn-sm">
                    <i class="fas fa-search cm-mr-1"></i> Filtrer
                </button>
                <a href="?page=archives_soutenances" class="cm-btn cm-btn-outline cm-btn-sm">
                    <i class="fas fa-times cm-mr-1"></i> Réinitialiser
                </a>
            </form>
        </div>
    </div>

    <!-- Compteur -->
    <p class="cm-text-muted cm-mb-3">
        <?= count($soutenances) ?> soutenance<?= count($soutenances) > 1 ? 's' : '' ?> trouvée<?= count($soutenances) > 1 ? 's' : '' ?>
    </p>

    <!-- Tableau -->
    <?php if (empty($soutenances)): ?>
        <div class="cm-card">
            <div class="cm-card-body cm-text-center cm-py-5">
                <div class="cm-icon-box cm-icon-box-lg cm-icon-box-secondary cm-mx-auto cm-mb-3">
                    <i class="fas fa-inbox fa-2x"></i>
                </div>
                <p class="cm-text-muted">Aucune soutenance trouvée pour cette période.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="cm-card">
            <div class="cm-card-body cm-p-0">
                <div class="cm-table-responsive">
                    <table class="cm-table cm-table-striped cm-table-hover">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Étudiant</th>
                                <th>Promotion</th>
                                <th>Thème</th>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Salle</th>
                                <th>Session</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($soutenances as $s): ?>
                                <tr>
                                    <td>
                                        <span class="cm-badge cm-badge-primary"><?= htmlspecialchars($s['num_soutenance']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($s['etudiant']) ?></strong>
                                        <br><small class="cm-text-muted"><?= htmlspecialchars($s['num_carte_etud']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars(\FormattingUtils::formatPromotion($s['promotion_etu'])) ?></td>
                                    <td>
                                        <span title="<?= htmlspecialchars($s['theme_soutenance']) ?>">
                                            <?= htmlspecialchars(mb_strimwidth($s['theme_soutenance'], 0, 50, '…')) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($s['date_soutenance']))) ?></td>
                                    <td><?= htmlspecialchars(substr($s['heure_soutenance'], 0, 5)) ?></td>
                                    <td><?= htmlspecialchars($s['lib_salle'] ?? '—') ?></td>
                                    <td>
                                        <span class="cm-badge cm-badge-info">
                                            <?= htmlspecialchars($s['lib_session'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?page=fiche_soutenance&id=<?= urlencode($s['num_soutenance']) ?>"
                                           class="cm-btn cm-btn-primary cm-btn-sm" title="Voir fiche">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?page=fiche_etudiant_archive&matricule=<?= urlencode($s['num_carte_etud']) ?>"
                                           class="cm-btn cm-btn-outline cm-btn-sm" title="Fiche étudiant">
                                            <i class="fas fa-user"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

