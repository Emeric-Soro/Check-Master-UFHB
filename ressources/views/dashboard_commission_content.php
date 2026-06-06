<?php
if (!is_array($stats ?? null)) {
    try {
        require_once __DIR__ . '/../../app/controllers/DashboardCommissionController.php';
        $fallbackController = new DashboardCommissionController();
        $stats = $fallbackController->getDashboardData();
    } catch (Throwable $e) {
        $stats = [];
    }
}

$dashboardData = is_array($stats ?? null) ? $stats : [];
$anneeAcademique = $dashboardData['annee_academique'] ?? null;
$enAttente = (int) ($dashboardData['en_attente'] ?? 0);
$rapportsRejetes = (int) ($dashboardData['rapports_rejetes'] ?? 0);
$repartition = is_array($dashboardData['repartition_statuts'] ?? null) ? $dashboardData['repartition_statuts'] : [];
$activites = is_array($dashboardData['activites_recentes'] ?? null) ? $dashboardData['activites_recentes'] : [];
$rapportsDetails = is_array($dashboardData['rapports_details'] ?? null) ? $dashboardData['rapports_details'] : [];

$countByStatut = static function (array $rows, string $needle): int {
    $count = 0;
    foreach ($rows as $row) {
        if (strtolower((string) ($row['statut'] ?? '')) === strtolower($needle)) {
            $count += (int) ($row['nombre'] ?? 0);
        }
    }
    return $count;
};

// Priorité : clé rapports_valides (basée sur statut_rapport) > repartition_statuts (basée sur table valider)
$valides = isset($dashboardData['rapports_valides'])
    ? (int) $dashboardData['rapports_valides']
    : $countByStatut($repartition, 'valider');
$rejetes = $rapportsRejetes;
$crRediges = count($rapportsDetails);
$totalRapports = max(1, $enAttente + $valides + $rejetes);
$pctValides = (int) round(($valides / $totalRapports) * 100);
$pctAttente = (int) round(($enAttente / $totalRapports) * 100);
$pctRejetes = (int) round(($rejetes / $totalRapports) * 100);

// Préparer les activités récentes pour le format attendu
$activityItems = [];
foreach (array_slice($activites, 0, 8) as $activite) {
    $titre = trim((string) ($activite['titre'] ?? 'Rapport'));
    $etudiant = trim((string) ($activite['prenom_etudiant'] ?? '') . ' ' . (string) ($activite['nom_etudiant'] ?? ''));
    $date = !empty($activite['date_validation'])
        ? date('d/m/Y', strtotime((string) $activite['date_validation']))
        : '';

    $text = $titre;
    if ($etudiant !== '') {
        $text .= ' - ' . $etudiant;
    }

    $statut = strtolower((string) ($activite['statut'] ?? ''));
    $type = 'info';
    $icon = 'fa-file-lines';

    if ($statut === 'valider') {
        $type = 'success';
        $icon = 'fa-circle-check';
    } elseif ($statut === 'rejeter') {
        $type = 'danger';
        $icon = 'fa-circle-xmark';
    }

    $activityItems[] = [
        'type' => $type,
        'icon' => $icon,
        'text' => $text,
        'time' => $date,
    ];
}

// Année académique pour l'affichage
$yearLabel = '';
if ($anneeAcademique && is_array($anneeAcademique)) {
    $dateDeb = date('Y', strtotime($anneeAcademique['date_deb']));
    $dateFin = date('Y', strtotime($anneeAcademique['date_fin']));
    $yearLabel = $dateDeb . '-' . $dateFin;
}
?>

<section class="cm-prd3-screen">

    <!-- Statistiques principales -->
    <div class="cm-grid-4">
        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($enAttente, 0, ',', ' '),
                'label' => 'En attente',
                'icon' => 'fa-clipboard-list',
                'color' => 'info',
                'url' => canView() ? '?page=reception_rapport_com' : ''
            ]); ?>
        </div>

        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($valides, 0, ',', ' '),
                'label' => 'Validés',
                'icon' => 'fa-circle-check',
                'color' => 'success',
                'url' => canView() ? '?page=processus_validation' : ''
            ]); ?>
        </div>

        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($rejetes, 0, ',', ' '),
                'label' => 'Rejetés',
                'icon' => 'fa-circle-xmark',
                'color' => 'danger',
                'url' => canView() ? '?page=processus_validation&status=rejete' : ''
            ]); ?>
        </div>

        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($crRediges, 0, ',', ' '),
                'label' => 'CR rédigés',
                'icon' => 'fa-file-signature',
                'color' => 'primary',
                'url' => canCreate() ? '?page=redaction_compte_rendu&cr_view=redaction' : ''
            ]); ?>
        </div>
    </div>

    <!-- Graphique de répartition -->
    <div class="cm-card cm-mt-md">
        <div class="cm-card__header">
            <h3 class="cm-card__title">Répartition des rapports</h3>
        </div>
        <div class="cm-card__body">
            <div class="cm-grid-2" style="align-items: center;">
                <!-- Diagramme circulaire -->
                <div style="position: relative; height: 300px; max-width: 400px; margin: 0 auto;">
                    <canvas id="chartRepartitionRapports"></canvas>
                </div>

                <!-- Légende avec barres de progression -->
                <div>
                    <?php
                    $progressRows = [
                        ['label' => 'Validés', 'count' => $valides, 'pct' => $pctValides, 'color' => '#10b981'],
                        ['label' => 'En attente', 'count' => $enAttente, 'pct' => $pctAttente, 'color' => '#3b82f6'],
                        ['label' => 'Rejetés', 'count' => $rejetes, 'pct' => $pctRejetes, 'color' => '#ef4444'],
                    ];
                    foreach ($progressRows as $row):
                        ?>
                        <div class="cm-mb-md">
                            <div class="cm-flex-between cm-text-sm cm-mb-sm">
                                <span style="font-weight: 500;">
                                    <span
                                        style="display: inline-block; width: 12px; height: 12px; background: <?php echo htmlspecialchars($row['color'], ENT_QUOTES, 'UTF-8'); ?>; border-radius: 3px; margin-right: 8px;"></span>
                                    <?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo (int) $row['count']; ?>)
                                </span>
                                <strong><?php echo (int) $row['pct']; ?>%</strong>
                            </div>
                            <div style="height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;">
                                <span
                                    style="display:block;height:100%;width:<?php echo (int) $row['pct']; ?>%;background:<?php echo htmlspecialchars($row['color'], ENT_QUOTES, 'UTF-8'); ?>;transition:width 0.3s ease;"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            function initChart() {
                var ctx = document.getElementById('chartRepartitionRapports');
                if (ctx && typeof Chart !== 'undefined') {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Validés', 'En attente', 'Rejetés'],
                            datasets: [{
                                label: 'Rapports',
                                data: [<?php echo $valides; ?>, <?php echo $enAttente; ?>, <?php echo $rejetes; ?>],
                                backgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
                                borderColor: ['#059669', '#2563eb', '#dc2626'],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            var label = context.label || '';
                                            var value = context.parsed.y || 0;
                                            var total = <?php echo $totalRapports; ?>;
                                            var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                            return label + ': ' + value + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, ticks: { precision: 0 } }
                            }
                        }
                    });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initChart);
            } else {
                initChart();
            }
        })();
    </script>

    <!-- Activités récentes et actions rapides -->
    <div class="cm-grid-2 cm-mt-md">
        <?php cm_component('dashboard/activity-list', [
            'title' => 'Activités récentes',
            'items' => $activityItems
        ]); ?>

        <div class="cm-chart-container">
            <div class="cm-chart-container__header">
                <h3 class="cm-chart-container__title">Actions rapides</h3>
                <p class="cm-chart-container__subtitle">Navigation directe</p>
            </div>
            <div class="cm-chart-container__body">
                <div class="cm-flex cm-flex-wrap cm-flex-gap-sm">
                    <?php if (canView()): ?>
                        <a class="cm-btn is-primary-accent" href="?page=reception_rapport_com" data-cm-ajax-link="true">
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                            Réception rapports
                        </a>
                        <a class="cm-btn is-primary-deep" href="?page=processus_validation" data-cm-ajax-link="true">
                            <i class="fas fa-check-double" aria-hidden="true"></i>
                            Processus validation
                        </a>
                    <?php endif; ?>
                    <?php if (canCreate()): ?>
                        <a class="cm-btn is-primary-dark" href="?page=redaction_compte_rendu" data-cm-ajax-link="true">
                            <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                            Rédaction CR
                        </a>
                    <?php endif; ?>
                    <?php if (canView()): ?>
                        <a class="cm-btn is-primary-sky" href="?page=programmation_soutenance" data-cm-ajax-link="true">
                            <i class="fas fa-calendar-days" aria-hidden="true"></i>
                            Soutenances
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($rapportsDetails)): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header cm-flex-between">
                <h3 class="cm-card__title"><i class="fas fa-file-lines cm-mr-sm"></i>Derniers rapports traités</h3>
                <?php if (canView()): ?>
                    <a href="?page=processus_validation" class="cm-btn cm-btn--primary cm-btn--sm" data-cm-ajax-link="true">
                        <i class="fas fa-external-link-alt cm-mr-sm"></i> Voir tout
                    </a>
                <?php endif; ?>
            </div>
            <div class="cm-card__body">
                <div style="overflow-x:auto">
                    <table class="cm-table">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Thème / Titre</th>
                                <th>Validé par</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rapportsDetails as $r):
                                $statut = strtolower((string) ($r['statut'] ?? ''));
                                $badgeClass = $statut === 'valider' ? 'cm-badge--success' : ($statut === 'rejeter' ? 'cm-badge--danger' : 'cm-badge--info');
                                $label = $statut === 'valider' ? 'Validé' : ($statut === 'rejeter' ? 'Rejeté' : ucfirst($statut));
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars(trim(($r['nom_etudiant'] ?? '') . ' ' . ($r['prenom_etudiant'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td><small><?= htmlspecialchars($r['titre'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                                    <td><?= htmlspecialchars(trim(($r['nom_enseignant'] ?? '') . ' ' . ($r['prenom_enseignant'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td><?= !empty($r['date_validation']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $r['date_validation'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </td>
                                    <td><span
                                            class="cm-badge <?= $badgeClass ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>