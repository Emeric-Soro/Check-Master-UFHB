<?php
/**
 * Dashboard Direction — KPIs et indicateurs clés pour la direction
 */
$kpis = $GLOBALS['kpis'] ?? [];

$anneeActive = $kpis['annee_active'] ?? null;
$anneeLabel = $anneeActive['label'] ?? date('Y') . '-' . (date('Y') + 1);
$anneeActiveId = isset($anneeActive['id_annee_acad']) ? (int) $anneeActive['id_annee_acad'] : 0;
$nbEtudiants = (int) ($kpis['nb_etudiants_inscrits'] ?? 0);
$candidatures = $kpis['candidatures'] ?? ['total' => 0, 'en_attente' => 0, 'validee' => 0, 'rejetee' => 0];
$nbSoutenancesProg = (int) ($kpis['soutenances_programmees'] ?? 0);
$nbSoutenancesRea = (int) ($kpis['soutenances_realisees'] ?? 0);
$tauxReussite = (float) ($kpis['taux_reussite_global'] ?? 0);
$recettesEncaissees = (float) ($kpis['recettes_encaissees'] ?? 0);
$recettesAttendues = (float) ($kpis['recettes_attendues'] ?? 0);
$nbEnseignants = (int) ($kpis['nb_enseignants_actifs'] ?? 0);
$nbUtilisateurs = (int) ($kpis['nb_utilisateurs_total'] ?? 0);
$reclamations = $kpis['nb_reclamations'] ?? ['total' => 0, 'en_attente' => 0, 'en_cours' => 0, 'resolue' => 0];
$evolution = $kpis['evolution_par_annee'] ?? [];
$repartitionFiliere = $kpis['repartition_filiere'] ?? [];
$dernieresInscriptions = $kpis['dernieres_inscriptions'] ?? [];
$activitesRecentes = $kpis['activites_recentes'] ?? [];

$tauxRecouvrement = $recettesAttendues > 0
    ? round(($recettesEncaissees / $recettesAttendues) * 100, 1)
    : 0;

$messageSuccess = $_SESSION['success_message'] ?? '';
$messageErreur = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<section class="cm-prd3-screen">
    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <!-- En-tête -->
    <div class="cm-card cm-mb-4">
        <div class="cm-card-body" style="padding:1rem;">
            <h2 class="cm-text-lg cm-font-bold">
                <i class="fas fa-chart-pie cm-mr-2"></i>Dashboard Direction
                <span class="cm-text-sm cm-font-normal cm-text-gray-500 cm-ml-2">
                    — Année académique <strong><?= htmlspecialchars($anneeLabel) ?></strong>
                </span>
            </h2>
        </div>
    </div>

    <!-- Grille de widgets KPI (4x2) -->
    <div class="cm-grid-4 cm-gap-4 cm-mb-4">
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($nbEtudiants, 0, ',', ' '),
            'label' => 'Étudiants inscrits',
            'icon' => 'fa-user-graduate',
            'color' => 'primary',
            'url' => '?page=gestion_scolarite',
            'ajax' => true,
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($candidatures['total'], 0, ',', ' '),
            'label' => 'Candidatures soutenance',
            'subtitle' => $candidatures['en_attente'] . ' en attente',
            'icon' => 'fa-folder-open',
            'color' => 'info',
            'url' => '?page=gestion_candidatures',
            'ajax' => true,
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($nbSoutenancesProg, 0, ',', ' '),
            'label' => 'Soutenances programmées',
            'subtitle' => $nbSoutenancesRea . ' réalisées',
            'icon' => 'fa-calendar-check',
            'color' => 'success',
            'url' => '?page=programmation_soutenance',
            'ajax' => true,
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => $tauxReussite . ' %',
            'label' => 'Taux de réussite global',
            'icon' => 'fa-trophy',
            'color' => $tauxReussite >= 70 ? 'success' : ($tauxReussite >= 40 ? 'warning' : 'danger'),
            'url' => '?page=archives_documents',
            'ajax' => true,
        ]); ?>
    </div>

    <div class="cm-grid-4 cm-gap-4 cm-mb-4">
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($recettesEncaissees, 0, ',', ' ') . ' FCFA',
            'label' => 'Recettes encaissées',
            'subtitle' => 'Taux recouvr. ' . $tauxRecouvrement . '%',
            'icon' => 'fa-money-bill-wave',
            'color' => 'success',
            'url' => '?page=fiche_financiere_annee',
            'ajax' => true,
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($recettesAttendues, 0, ',', ' ') . ' FCFA',
            'label' => 'Recettes attendues',
            'icon' => 'fa-calculator',
            'color' => 'info',
            'url' => '?page=fiche_financiere_annee',
            'ajax' => true,
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($nbEnseignants, 0, ',', ' '),
            'label' => 'Enseignants actifs',
            'subtitle' => $nbUtilisateurs . ' utilisateurs totaux',
            'icon' => 'fa-chalkboard-teacher',
            'color' => 'warning',
            'url' => '?page=gestion_rh',
            'ajax' => true,
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($reclamations['total'], 0, ',', ' '),
            'label' => 'Réclamations',
            'subtitle' => $reclamations['en_attente'] . ' en attente',
            'icon' => 'fa-exclamation-circle',
            'color' => 'danger',
            'url' => '?page=gestion_reclamations_scolarite',
            'ajax' => true,
        ]); ?>
    </div>

    <!-- Graphiques et tableaux -->
    <div class="cm-grid-2 cm-gap-4 cm-mb-4">
        <!-- Évolution par année (barres) -->
        <div class="cm-card">
            <div class="cm-card-header">
                <h3 class="cm-card-title"><i class="fas fa-chart-line cm-mr-2"></i>Évolution des inscriptions</h3>
            </div>
            <div class="cm-card-body">
                <div style="position:relative;height:280px;">
                    <canvas id="chartEvolution"></canvas>
                </div>
            </div>
        </div>

        <!-- Répartition par filière (camembert) -->
        <div class="cm-card">
            <div class="cm-card-header">
                <h3 class="cm-card-title"><i class="fas fa-chart-pie cm-mr-2"></i>Répartition par filière</h3>
            </div>
            <div class="cm-card-body">
                <div style="position:relative;height:280px;">
                    <canvas id="chartRepartition"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Activités récentes et dernières inscriptions -->
    <div class="cm-grid-2 cm-gap-4 cm-mb-4">
        <!-- Dernières inscriptions (clickable) -->
        <div class="cm-card">
            <div class="cm-card-header">
                <h3 class="cm-card-title"><i class="fas fa-receipt cm-mr-2"></i>Derniers versements</h3>
            </div>
            <div class="cm-card-body" style="max-height:320px;overflow-y:auto;">
                <?php if (empty($dernieresInscriptions)): ?>
                    <p class="cm-text-sm cm-text-gray-500">Aucun versement récent.</p>
                <?php else: ?>
                    <table class="cm-data-table cm-data-table--compact">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th is-left">Étudiant</th>
                                <th class="cm-data-table__th is-left">Niveau</th>
                                <th class="cm-data-table__th is-right">Montant</th>
                                <th class="cm-data-table__th is-center">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dernieresInscriptions as $ins): ?>
                            <?php
                                $nom = htmlspecialchars(trim(($ins['prenom'] ?? '') . ' ' . ($ins['nom'] ?? '')));
                                $niveau = htmlspecialchars($ins['niveau'] ?? '');
                                $montant = (float) ($ins['montant_verser'] ?? 0);
                                $date = !empty($ins['date_versement']) ? date('d/m/Y', strtotime($ins['date_versement'])) : '-';
                                $detailHref = '?page=fiche_financiere_annee&id_annee_acad=' . $anneeActiveId . '&num_etu=' . urlencode((string) ($ins['num_carte_etud'] ?? ''));
                            ?>
                            <tr class="cm-data-table__row cm-clickable-row"
                                data-href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>">
                                <td class="cm-data-table__td is-left"><strong><?= $nom ?: htmlspecialchars($ins['num_carte_etud'] ?? '') ?></strong></td>
                                <td class="cm-data-table__td is-left"><?= $niveau ?></td>
                                <td class="cm-data-table__td is-right"><?= number_format($montant, 0, ',', ' ') ?> FCFA</td>
                                <td class="cm-data-table__td is-center"><?= $date ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activités récentes -->
        <div class="cm-card">
            <div class="cm-card-header">
                <h3 class="cm-card-title"><i class="fas fa-clock-rotate-left cm-mr-2"></i>Activités récentes</h3>
            </div>
            <div class="cm-card-body" style="max-height:320px;overflow-y:auto;">
                <?php if (empty($activitesRecentes)): ?>
                    <p class="cm-text-sm cm-text-gray-500">Aucune activité récente.</p>
                <?php else: ?>
                    <table class="cm-data-table cm-data-table--compact">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th is-left">Action</th>
                                <th class="cm-data-table__th is-left">Table</th>
                                <th class="cm-data-table__th is-left">Utilisateur</th>
                                <th class="cm-data-table__th is-center">Date</th>
                                <th class="cm-data-table__th is-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activitesRecentes as $act): ?>
                            <?php
                                $dateAct = !empty($act['date_creation'])
                                    ? date('d/m/Y H:i', strtotime($act['date_creation']))
                                    : '-';
                                $statut = strtolower(trim((string) ($act['statut_action'] ?? '')));
                                $badgeType = $statut === 'succès' || $statut === 'succes' ? 'success' : ($statut === 'erreur' ? 'danger' : 'info');
                            ?>
                            <tr class="cm-data-table__row cm-clickable-row"
                                data-href="?page=piste_audit">
                                <td class="cm-data-table__td is-left"><?= htmlspecialchars($act['action'] ?? '') ?></td>
                                <td class="cm-data-table__td is-left">
                                    <code class="cm-text-xs"><?= htmlspecialchars($act['nom_table'] ?? '') ?></code>
                                </td>
                                <td class="cm-data-table__td is-left"><?= htmlspecialchars($act['utilisateur'] ?? '') ?></td>
                                <td class="cm-data-table__td is-center cm-text-xs"><?= $dateAct ?></td>
                                <td class="cm-data-table__td is-center">
                                    <?php cm_component('ui/badge', ['text' => $act['statut_action'] ?? '', 'type' => $badgeType]); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Graphiques Chart.js -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') return;

        // ---- Évolution par année (barres) ----
        var evolutionData = <?= json_encode($evolution) ?>;
        var evoCtx = document.getElementById('chartEvolution');
        if (evoCtx && evolutionData.length > 0) {
            new Chart(evoCtx, {
                type: 'bar',
                data: {
                    labels: evolutionData.map(function(d) { return d.annee || ''; }),
                    datasets: [
                        {
                            label: 'Inscrits',
                            data: evolutionData.map(function(d) { return Number(d.inscrits || 0); }),
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 2,
                            order: 1,
                        },
                        {
                            label: 'Montant versé (FCFA)',
                            data: evolutionData.map(function(d) { return Number(d.total_verse || 0); }),
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 2,
                            yAxisID: 'y1',
                            order: 2,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    var label = ctx.dataset.label || '';
                                    var val = Number(ctx.parsed.y || 0).toLocaleString('fr-FR');
                                    if (ctx.dataset.label && ctx.dataset.label.indexOf('FCFA') !== -1) {
                                        return label + ': ' + val + ' FCFA';
                                    }
                                    return label + ': ' + val;
                                }
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Inscrits' } },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: { display: true, text: 'Montant (FCFA)' },
                            grid: { drawOnChartArea: false },
                        }
                    }
                }
            });
        } else if (evoCtx) {
            evoCtx.parentNode.innerHTML = '<p class="cm-text-sm cm-text-gray-500 cm-text-center cm-py-4">Aucune donnée d\'évolution disponible.</p>';
        }

        // ---- Répartition par filière (doughnut) ----
        var repartData = <?= json_encode($repartitionFiliere) ?>;
        var repCtx = document.getElementById('chartRepartition');
        if (repCtx && repartData.length > 0) {
            var bgColors = [
                'rgba(59, 130, 246, 0.8)', 'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)', 'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)', 'rgba(236, 72, 153, 0.8)',
                'rgba(14, 165, 233, 0.8)', 'rgba(234, 179, 8, 0.8)',
            ];
            new Chart(repCtx, {
                type: 'doughnut',
                data: {
                    labels: repartData.map(function(d) { return d.filiere || ''; }),
                    datasets: [{
                        data: repartData.map(function(d) { return Number(d.nb || 0); }),
                        backgroundColor: bgColors.slice(0, repartData.length),
                        borderColor: '#ffffff',
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    var label = ctx.label || '';
                                    var val = Number(ctx.parsed || 0);
                                    var total = ctx.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                    var pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + val + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        } else if (repCtx) {
            repCtx.parentNode.innerHTML = '<p class="cm-text-sm cm-text-gray-500 cm-text-center cm-py-4">Aucune donnée de répartition disponible.</p>';
        }
    });
    </script>
</section>
