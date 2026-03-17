<?php
/**
 * Vue : Archives Jurys
 * $data['stats_jurys'] - statistiques par enseignant jury
 *   Champs: id_enseignant, nom_complet, lib_grade, total_soutenances,
 *           nb_president, nb_examinateur, nb_directeur, nb_encadrant, moyenne_notes
 */
$stats_jurys = $data['stats_jurys'] ?? [];
?>

<div class="cm-archives-jurys">

    <!-- En-tête -->
    <div class="cm-page-header cm-mb-5">
        <div>
            <p class="cm-page-subtitle">
                Statistiques de participation des enseignants aux jurys de soutenance
            </p>
        </div>
        <div class="cm-flex cm-gap-3">
            <a href="?page=admin_historique" class="cm-btn cm-btn-outline cm-btn-sm">
                <i class="fas fa-arrow-left cm-mr-1"></i> Retour à Historique
            </a>
            <a href="?page=archives_jurys&export=csv" class="cm-btn cm-btn-primary cm-btn-sm">
                <i class="fas fa-file-csv cm-mr-1"></i> Exporter CSV
            </a>
        </div>
    </div>

    <!-- Résumé global -->
    <?php if (!empty($stats_jurys)): ?>
    <div class="cm-grid-4 cm-gap-4 cm-mb-5">
        <?php
        $totalEnseignants  = count($stats_jurys);
        $totalParticipations = array_sum(array_column($stats_jurys, 'total_soutenances'));
        $moyenneGlobale    = $totalParticipations > 0
            ? array_sum(array_map(fn($j) => (float)$j['moyenne_notes'] * (int)$j['total_soutenances'], $stats_jurys)) / $totalParticipations
            : 0;
        ?>
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-primary cm-mx-auto cm-mb-2">
                    <i class="fas fa-users"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold"><?= $totalEnseignants ?></div>
                <div class="cm-text-muted cm-text-sm">Enseignants jurés</div>
            </div>
        </div>
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-success cm-mx-auto cm-mb-2">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold"><?= $totalParticipations ?></div>
                <div class="cm-text-muted cm-text-sm">Participations totales</div>
            </div>
        </div>
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-warning cm-mx-auto cm-mb-2">
                    <i class="fas fa-star"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold"><?= number_format($moyenneGlobale, 2) ?></div>
                <div class="cm-text-muted cm-text-sm">Moyenne globale</div>
            </div>
        </div>
        <div class="cm-card cm-text-center">
            <div class="cm-card-body">
                <div class="cm-icon-box cm-icon-box-info cm-mx-auto cm-mb-2">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="cm-text-2xl cm-font-bold">
                    <?= $totalEnseignants > 0 ? number_format($totalParticipations / $totalEnseignants, 1) : 0 ?>
                </div>
                <div class="cm-text-muted cm-text-sm">Moy. participations/jury</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tableau des jurys -->
    <?php if (empty($stats_jurys)): ?>
        <div class="cm-card">
            <div class="cm-card-body cm-text-center cm-py-5">
                <div class="cm-icon-box cm-icon-box-lg cm-icon-box-secondary cm-mx-auto cm-mb-3">
                    <i class="fas fa-inbox fa-2x"></i>
                </div>
                <p class="cm-text-muted">Aucune donnée de jury disponible pour cette période.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="cm-card">
            <div class="cm-card-header">
                <h3 class="cm-card-title">
                    <i class="fas fa-table cm-mr-2"></i>Détail par enseignant
                </h3>
            </div>
            <div class="cm-card-body cm-p-0">
                <div class="cm-table-responsive">
                    <table class="cm-table cm-table-striped cm-table-hover">
                        <thead>
                            <tr>
                                <th>Enseignant</th>
                                <th>Grade</th>
                                <th class="cm-text-center">Total</th>
                                <th class="cm-text-center">Président</th>
                                <th class="cm-text-center">Examinateur</th>
                                <th class="cm-text-center">Directeur</th>
                                <th class="cm-text-center">Encadrant</th>
                                <th class="cm-text-center">Moy. notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_jurys as $j): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($j['nom_complet']) ?></strong></td>
                                    <td>
                                        <span class="cm-badge cm-badge-info">
                                            <?= htmlspecialchars($j['lib_grade'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td class="cm-text-center">
                                        <span class="cm-badge cm-badge-primary"><?= (int)$j['total_soutenances'] ?></span>
                                    </td>
                                    <td class="cm-text-center"><?= (int)$j['nb_president'] ?: '—' ?></td>
                                    <td class="cm-text-center"><?= (int)$j['nb_examinateur'] ?: '—' ?></td>
                                    <td class="cm-text-center"><?= (int)$j['nb_directeur'] ?: '—' ?></td>
                                    <td class="cm-text-center"><?= (int)$j['nb_encadrant'] ?: '—' ?></td>
                                    <td class="cm-text-center">
                                        <?php if ($j['moyenne_notes'] !== null): ?>
                                            <span class="cm-badge cm-badge-success">
                                                <?= number_format((float)$j['moyenne_notes'], 2) ?>
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
