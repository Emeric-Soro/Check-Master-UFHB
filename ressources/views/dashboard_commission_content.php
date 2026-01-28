<?php
global $stats;
$dashboardData = $stats ?? [];
$totalRapports = $dashboardData['total_rapports'] ?? 0;
$tauxValidation = $dashboardData['taux_validation'] ?? 0;
$tempsMoyen = $dashboardData['temps_moyen'] ?? 0;
$enAttente = $dashboardData['en_attente'] ?? 0;
$evolutionData = $dashboardData['evolution_mensuelle'] ?? [];
$repartitionData = $dashboardData['repartition_statuts'] ?? [];
$performanceData = $dashboardData['performance_categories'] ?? [];
$activitesData = $dashboardData['activites_recentes'] ?? [];
$rapportsDetails = $dashboardData['rapports_details'] ?? [];

function getTimeAgo($date)
{
    if (!$date)
        return 'N/A';
    $time = time() - strtotime($date);
    if ($time < 3600)
        return floor($time / 60) . 'min';
    if ($time < 86400)
        return floor($time / 3600) . 'h';
    return floor($time / 86400) . 'j';
}

function getStatusBadgeType($status)
{
    switch ($status) {
        case 'valider':
            return 'success';
        case 'rejeter':
            return 'danger';
        case 'en_attente':
            return 'info';
        case 'en_cours':
            return 'info';
        default:
            return 'muted';
    }
}

$evolutionLabels = [];
$evolutionFinalises = [];
$evolutionRejetes = [];
foreach ($evolutionData as $data) {
    $evolutionLabels[] = date('M Y', strtotime($data['mois'] . '-01'));
    $evolutionFinalises[] = $data['finalises'];
    $evolutionRejetes[] = $data['rejetes'];
}

$statusLabels = [];
$statusData = [];
$statusColors = ['#10b981', '#1a5276', '#f59e0b', '#6b7280', '#1a5276'];

foreach ($repartitionData as $data) {
    $statusLabels[] = ucfirst($data['statut']);
    $statusData[] = $data['nombre'];
}
?>

<div class="container">
    <div class="stats-grid mb-lg">
        <?php
        echo renderStatsCard('Total Comptes Rendus', $totalRapports, 'file-alt', 'primary');
        echo renderStatsCard('Taux de Validation', $tauxValidation . '%', 'check-circle', 'success');
        echo renderStatsCard('Temps Moyen', $tempsMoyen . 'j', 'clock', 'info');
        echo renderStatsCard('En Attente', $enAttente, 'hourglass-half', 'warning');
        ?>
    </div>

    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list-alt text-success mr-2"></i>
                Détails des Performances (Évaluations des rapports)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Rapport</th>
                        <th>Évaluateur</th>
                        <th>Étudiant</th>
                        <th>Enseignant</th>
                        <th>Temps de traitement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rapportsDetails)): ?>
                        <?php foreach ($rapportsDetails as $rapport): ?>
                            <tr>
                                <td>
                                    <?php echo renderBadge(ucfirst($rapport['statut']), getStatusBadgeType($rapport['statut'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($rapport['titre']); ?></td>
                                <td><?php echo htmlspecialchars($rapport['prenom_enseignant'] . ' ' . $rapport['nom_enseignant']); ?></td>
                                <td><?php echo htmlspecialchars($rapport['prenom_etudiant'] . ' ' . $rapport['nom_etudiant']); ?></td>
                                <td><?php echo htmlspecialchars($rapport['prenom_enseignant'] . ' ' . $rapport['nom_enseignant']); ?></td>
                                <td><?php echo ($rapport['temps_traitement'] ?? 0); ?> jours</td>
                                <td>
                                    <?php echo renderButton('<i class="fas fa-eye"></i>', 'ghost', true, '', 'sm'); ?>
                                    <?php echo renderButton('<i class="fas fa-download"></i>', 'ghost', true, '', 'sm'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <?php echo renderEmptyState('Aucun rapport disponible', 'fa-table'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-lg">
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h3 class="card-title">
                    <i class="fas fa-chart-line text-primary mr-2"></i>
                    Évolution des Comptes Rendus
                </h3>
                <div class="flex gap-2 text-sm">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-primary rounded-full mr-1"></div>
                        <span>Finalisés</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-primary rounded-full mr-1"></div>
                        <span>Rejetés</span>
                    </div>
                </div>
            </div>
            <div class="p-lg" style="height: 300px;">
                <canvas id="evolutionChart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie text-success mr-2"></i>
                    Répartition par Statut
                </h3>
                <button onclick="refreshCharts()" class="btn btn-ghost btn-sm">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="p-lg" style="height: 300px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-history text-primary mr-2"></i>
                Activité Récente
            </h3>
        </div>
        <div class="p-lg space-y-3">
            <?php if (!empty($activitesData)): ?>
                <?php foreach ($activitesData as $activite): ?>
                    <div class="flex items-center gap-3 p-2 hover:bg-accent-light rounded-lg">
                        <div class="p-2 <?php echo $activite['statut'] === 'valider' ? 'bg-success-light' : 'bg-danger-light'; ?> rounded-full">
                            <i class="fas fa-<?php echo $activite['statut'] === 'valider' ? 'check' : 'times'; ?> text-<?php echo $activite['statut'] === 'valider' ? 'success' : 'primary'; ?> text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium">Rapport <?php echo ucfirst($activite['statut']); ?></p>
                            <p class="text-xs text-muted">
                                <?php echo htmlspecialchars($activite['titre']); ?> -
                                <?php echo htmlspecialchars($activite['prenom_etudiant'] . ' ' . $activite['nom_etudiant']); ?>
                            </p>
                            <p class="text-xs text-muted">
                                Par <?php echo htmlspecialchars($activite['prenom_enseignant'] . ' ' . $activite['nom_enseignant']); ?>
                            </p>
                        </div>
                        <span class="text-xs text-muted"><?php echo getTimeAgo($activite['date_validation']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php echo renderEmptyState('Aucune activité récente', 'fa-history'); ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex justify-between items-center">
            <h3 class="card-title">
                <i class="fas fa-table text-muted mr-2"></i>
                Détails des Performances
            </h3>
            <div class="flex gap-2">
                <input type="text" placeholder="Rechercher..." class="input input-sm">
                <?php echo renderButton('<i class="fas fa-filter"></i>', 'primary', true, '', 'sm'); ?>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Statut</th>
                        <th>Rapport</th>
                        <th>Évaluateur</th>
                        <th>Commentaire</th>
                        <th>Date d'évaluation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dashboardData['evaluations_rapports'])): ?>
                        <?php foreach ($dashboardData['evaluations_rapports'] as $eval): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $badgeType = $eval['decision_evaluation'] === 'valider' ? 'success' : 
                                                ($eval['decision_evaluation'] === 'rejeter' ? 'danger' : 'info');
                                    echo renderBadge(ucfirst($eval['decision_evaluation']), $badgeType); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($eval['nom_rapport'] ?? $eval['id_rapport']); ?></td>
                                <td><?php echo htmlspecialchars(($eval['prenom_enseignant'] ?? '') . ' ' . ($eval['nom_enseignant'] ?? $eval['id_evaluateur'])); ?></td>
                                <td><?php echo htmlspecialchars($eval['commentaire']); ?></td>
                                <td><?php echo $eval['date_evaluation']; ?></td>
                                <td>
                                    <?php echo renderButton('<i class="fas fa-eye"></i>', 'ghost', true, '', 'sm'); ?>
                                    <?php echo renderButton('<i class="fas fa-download"></i>', 'ghost', true, '', 'sm'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <?php echo renderEmptyState('Aucune évaluation trouvée', 'fa-table'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
    let evolutionChart, statusChart;
    const evolutionData = { labels: <?php echo json_encode($evolutionLabels); ?>, datasets: [{ label: 'Finalisés', data: <?php echo json_encode($evolutionFinalises); ?>, borderColor: '#1a5276', backgroundColor: 'rgba(26,82,118,0.08)', tension: 0.4, fill: true }, { label: 'Rejetés', data: <?php echo json_encode($evolutionRejetes); ?>, borderColor: '#1a5276', backgroundColor: 'rgba(26,82,118,0.06)', tension: 0.4, fill: true }] };
    const statusData = { labels: <?php echo json_encode($statusLabels); ?>, datasets: [{ data: <?php echo json_encode($statusData); ?>, backgroundColor: <?php echo json_encode($statusColors); ?>, borderWidth: 0 }] };

    function initCharts() {
        const evolutionCtx = document.getElementById('evolutionChart');
        if (evolutionCtx) {
            evolutionChart = new Chart(evolutionCtx.getContext('2d'), { type: 'line', data: evolutionData, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }, elements: { point: { radius: 4, hoverRadius: 6 } } } });
        }

        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            statusChart = new Chart(statusCtx.getContext('2d'), { type: 'doughnut', data: statusData, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } } }, cutout: '60%' } });
        }
    }

    function refreshCharts() {
        showNotification('Actualisation des données...', 'info');
        setTimeout(() => { showNotification('Données actualisées avec succès', 'success'); }, 1500);
    }

    function showNotification(message, type) {
        const typeClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
        const icon = type === 'success' ? 'check' : type === 'error' ? 'times' : 'info';
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} fixed top-4 right-4 z-50 animate-slide-in`;
        notification.innerHTML = `<i class="fas fa-${icon} mr-2"></i>${message}`;
        document.body.appendChild(notification);
        setTimeout(() => { notification.remove(); }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initCharts();
    });
</script>