<?php
/**
 * Vue : Archives Candidatures
 * $data['candidatures'] - liste des candidatures archivées
 * $data['stats']        - statistiques globales
 *   Champs candidature: id_candidature, date_candidature, num_carte_etud, etudiant,
 *                       niveau, statut_candidature, date_traitement, traite_par,
 *                       commentaire_admin, delai_traitement
 *   Champs stats: total, validees, rejetees, en_attente, delai_moyen
 */
$candidatures = $data['candidatures'] ?? [];
$stats        = $data['stats'] ?? null;
?>

<div class="cm-archives-candidatures">

    <!-- En-tête -->
    <div class="cm-page-header cm-mb-5">
        <div>
            <h1 class="cm-page-title">
                <i class="fas fa-clipboard-list cm-mr-2"></i>Archives Candidatures
            </h1>
            <p class="cm-page-subtitle">
                Historique des candidatures de l'année archivée
            </p>
        </div>
        <div class="cm-flex cm-gap-3">
            <a href="?page=admin_historique" class="cm-btn cm-btn-outline cm-btn-sm">
                <i class="fas fa-arrow-left cm-mr-1"></i> Retour à Historique
            </a>
            <a href="?page=archives_candidatures&export=csv" class="cm-btn cm-btn-primary cm-btn-sm">
                <i class="fas fa-file-csv cm-mr-1"></i> Exporter CSV
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <?php if ($stats): ?>
    <div class="cm-grid-4 cm-gap-4 cm-mb-5">
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-primary cm-mx-auto cm-mb-2">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold"><?= (int)$stats['total'] ?></div>
                <div class="cm-text-muted cm-text-sm">Total</div>
            </div>
        </div>
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-success cm-mx-auto cm-mb-2">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold cm-text-success"><?= (int)$stats['validees'] ?></div>
                <div class="cm-text-muted cm-text-sm">Validées</div>
            </div>
        </div>
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-danger cm-mx-auto cm-mb-2">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold cm-text-danger"><?= (int)$stats['rejetees'] ?></div>
                <div class="cm-text-muted cm-text-sm">Rejetées</div>
            </div>
        </div>
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-warning cm-mx-auto cm-mb-2">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold cm-text-warning"><?= (int)$stats['en_attente'] ?></div>
                <div class="cm-text-muted cm-text-sm">En attente</div>
            </div>
        </div>
    </div>
    <?php if (!empty($stats['delai_moyen'])): ?>
    <div class="cm-card cm-mb-4">
        <div class="cm-card-body">
            <span class="cm-text-muted cm-mr-2">Délai moyen de traitement :</span>
            <span class="cm-badge cm-badge-info"><?= number_format((float)$stats['delai_moyen'], 1) ?> jour(s)</span>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Tableau -->
    <?php if (empty($candidatures)): ?>
        <div class="cm-card">
            <div class="cm-card-body cm-text-center cm-py-5">
                <div class="cm-icon-box cm-icon-box-lg cm-icon-box-secondary cm-mx-auto cm-mb-3">
                    <i class="fas fa-inbox fa-2x"></i>
                </div>
                <p class="cm-text-muted">Aucune candidature trouvée pour cette période.</p>
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
                                <th>Niveau</th>
                                <th>Date candidature</th>
                                <th>Statut</th>
                                <th>Traité par</th>
                                <th>Date traitement</th>
                                <th>Délai</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidatures as $c): ?>
                                <?php
                                $statut = strtolower($c['statut_candidature'] ?? '');
                                $badgeClass = match($statut) {
                                    'validée', 'validee', 'valide' => 'cm-badge-success',
                                    'rejetée', 'rejetee', 'rejet'  => 'cm-badge-danger',
                                    default                         => 'cm-badge-warning',
                                };
                                ?>
                                <tr>
                                    <td><span class="cm-badge cm-badge-primary">#<?= htmlspecialchars($c['id_candidature']) ?></span></td>
                                    <td>
                                        <a href="?page=fiche_etudiant_archive&matricule=<?= urlencode($c['num_carte_etud']) ?>">
                                            <?= htmlspecialchars($c['etudiant']) ?>
                                        </a>
                                        <br><small class="cm-text-muted"><?= htmlspecialchars($c['num_carte_etud']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($c['niveau'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars(!empty($c['date_candidature']) ? date('d/m/Y', strtotime($c['date_candidature'])) : '—') ?></td>
                                    <td><span class="cm-badge <?= $badgeClass ?>"><?= htmlspecialchars($c['statut_candidature'] ?? '—') ?></span></td>
                                    <td><?= htmlspecialchars($c['traite_par'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars(!empty($c['date_traitement']) ? date('d/m/Y', strtotime($c['date_traitement'])) : '—') ?></td>
                                    <td>
                                        <?php if (!empty($c['delai_traitement'])): ?>
                                            <span class="cm-badge cm-badge-info"><?= htmlspecialchars($c['delai_traitement']) ?>j</span>
                                        <?php else: ?>
                                            <span class="cm-text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['commentaire_admin'])): ?>
                                            <span title="<?= htmlspecialchars($c['commentaire_admin']) ?>">
                                                <?= htmlspecialchars(mb_strimwidth($c['commentaire_admin'], 0, 40, '…')) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="cm-text-muted">—</span>
                                        <?php endif; ?>
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
