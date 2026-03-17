<?php
/**
 * Vue : Archives Réclamations
 * $data['reclamations'] - liste des réclamations archivées
 * $data['stats']        - statistiques globales
 *   Champs réclamation: id_reclamation, date_creation, num_carte_etud, etudiant,
 *                       objet_reclamation, description (100 chars), statut, date_mise_a_jour
 *   Champs stats: total, resolues, en_cours
 */
$reclamations = $data['reclamations'] ?? [];
$stats        = $data['stats'] ?? null;
?>

<div class="cm-archives-reclamations">

    <!-- En-tête -->
    <div class="cm-page-header cm-mb-5">
        <div>
            <p class="cm-page-subtitle">
                Historique des réclamations de l'année archivée
            </p>
        </div>
        <div class="cm-flex cm-gap-3">
            <a href="?page=admin_historique" class="cm-btn cm-btn-outline cm-btn-sm">
                <i class="fas fa-arrow-left cm-mr-1"></i> Retour à Historique
            </a>
            <a href="?page=archives_reclamations&export=csv" class="cm-btn cm-btn-primary cm-btn-sm">
                <i class="fas fa-file-csv cm-mr-1"></i> Exporter CSV
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <?php if ($stats): ?>
    <div class="cm-grid-3 cm-gap-4 cm-mb-5">
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-primary cm-mx-auto cm-mb-2">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold"><?= (int)$stats['total'] ?></div>
                <div class="cm-text-muted cm-text-sm">Total réclamations</div>
            </div>
        </div>
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-success cm-mx-auto cm-mb-2">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold cm-text-success"><?= (int)$stats['resolues'] ?></div>
                <div class="cm-text-muted cm-text-sm">Résolues</div>
            </div>
        </div>
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-warning cm-mx-auto cm-mb-2">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold cm-text-warning"><?= (int)$stats['en_cours'] ?></div>
                <div class="cm-text-muted cm-text-sm">En cours</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tableau -->
    <?php if (empty($reclamations)): ?>
        <div class="cm-card">
            <div class="cm-card-body cm-text-center cm-py-5">
                <div class="cm-icon-box cm-icon-box-lg cm-icon-box-secondary cm-mx-auto cm-mb-3">
                    <i class="fas fa-inbox fa-2x"></i>
                </div>
                <p class="cm-text-muted">Aucune réclamation trouvée pour cette période.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="cm-card">
            <div class="cm-card-body cm-p-0">
                <div class="cm-table-responsive">
                    <table class="cm-table cm-table-striped cm-table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Étudiant</th>
                                <th>Objet</th>
                                <th>Description</th>
                                <th>Date création</th>
                                <th>Statut</th>
                                <th>Mise à jour</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reclamations as $r): ?>
                                <?php
                                $statut = strtolower($r['statut'] ?? '');
                                $badgeClass = match(true) {
                                    str_contains($statut, 'résol') || str_contains($statut, 'resol') || str_contains($statut, 'clôt') => 'cm-badge-success',
                                    str_contains($statut, 'cours') || str_contains($statut, 'progress')                               => 'cm-badge-warning',
                                    str_contains($statut, 'rejet') || str_contains($statut, 'refus')                                  => 'cm-badge-danger',
                                    default                                                                                           => 'cm-badge-info',
                                };
                                ?>
                                <tr>
                                    <td><span class="cm-badge cm-badge-primary">#<?= htmlspecialchars($r['id_reclamation']) ?></span></td>
                                    <td>
                                        <a href="?page=fiche_etudiant_archive&matricule=<?= urlencode($r['num_carte_etud']) ?>">
                                            <?= htmlspecialchars($r['etudiant']) ?>
                                        </a>
                                        <br><small class="cm-text-muted"><?= htmlspecialchars($r['num_carte_etud']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($r['objet_reclamation'] ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($r['description'])): ?>
                                            <span class="cm-text-muted" title="<?= htmlspecialchars($r['description'] ?? '') ?>">
                                                <?= htmlspecialchars(mb_strimwidth($r['description'], 0, 60, '…')) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="cm-text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(!empty($r['date_creation']) ? date('d/m/Y', strtotime($r['date_creation'])) : '—') ?></td>
                                    <td><span class="cm-badge <?= $badgeClass ?>"><?= htmlspecialchars($r['statut'] ?? '—') ?></span></td>
                                    <td><?= htmlspecialchars(!empty($r['date_mise_a_jour']) ? date('d/m/Y', strtotime($r['date_mise_a_jour'])) : '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
