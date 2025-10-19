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

function getTimeAgo($date) {
    if (!$date) return 'N/A';
    $time = time() - strtotime($date);
    if ($time < 3600) return floor($time/60) . 'min';
    if ($time < 86400) return floor($time/3600) . 'h';
    return floor($time/86400) . 'j';
}

function getStatusClass($status) {
    switch ($status) {
        case 'valider': return 'badge-success';
        case 'rejeter': return 'badge-error';
        case 'en_attente': return 'badge-warning';
        case 'en_cours': return 'badge-info';
        default: return 'badge-ghost';
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
$statusColors = ['#4caf50','#1a5276', '#f39c12', '#64748B', '#1a5276'];

foreach ($repartitionData as $data) {
    $statusLabels[] = ucfirst($data['statut']);
    $statusData[] = $data['nombre'];
}
?>

<div class="space-y-6" x-data="{ selectedFilter: 'all' }">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Comptes Rendus -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-primary">
                    <i class="fas fa-file-alt text-3xl"></i>
                </div>
                <div class="stat-title">Total Comptes Rendus</div>
                <div class="stat-value text-primary"><?php echo $totalRapports; ?></div>
                <div class="stat-desc">Documents traités</div>
            </div>
        </div>

        <!-- Taux de Validation -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-accent">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
                <div class="stat-title">Taux de Validation</div>
                <div class="stat-value text-accent"><?php echo $tauxValidation; ?>%</div>
                <div class="stat-desc">Comptes rendus validés</div>
            </div>
        </div>

        <!-- Temps Moyen -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <i class="fas fa-clock text-3xl"></i>
                </div>
                <div class="stat-title">Temps Moyen</div>
                <div class="stat-value text-secondary"><?php echo $tempsMoyen; ?>j</div>
                <div class="stat-desc">Délai de traitement</div>
            </div>
        </div>

        <!-- En Attente -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-warning">
                    <i class="fas fa-hourglass-half text-3xl"></i>
                </div>
                <div class="stat-title">En Attente</div>
                <div class="stat-value text-warning"><?php echo $enAttente; ?></div>
                <div class="stat-desc">À traiter</div>
            </div>
        </div>
    </div>

    <!-- Details Table -->
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-list-alt text-accent"></i>
                <h3 class="card-title text-primary">Détails des Performances (Évaluations des rapports)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-zebra">
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
                            <tr class="hover">
                                <td>
                                    <span class="badge <?php echo $rapport['statut'] === 'valider' ? 'badge-success' : 'badge-error'; ?>">
                                        <?php echo ucfirst($rapport['statut']); ?>
                                    </span>
                                </td>
                                <td><?php echo $rapport['titre']; ?></td>
                                <td><?php echo $rapport['prenom_enseignant'] . ' ' . $rapport['nom_enseignant']; ?></td>
                                <td><?php echo $rapport['prenom_etudiant'] . ' ' . $rapport['nom_etudiant']; ?></td>
                                <td><?php echo $rapport['prenom_enseignant'] . ' ' . $rapport['nom_enseignant']; ?></td>
                                <td><?php echo $rapport['temps_traitement'] ?? 0; ?> jours</td>
                                <td>
                                    <div class="join">
                                        <button class="btn btn-sm btn-ghost join-item">
                                            <i class="fas fa-eye text-primary"></i>
                                        </button>
                                        <button class="btn btn-sm btn-ghost join-item">
                                            <i class="fas fa-download text-accent"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-8">
                                <i class="fas fa-table text-4xl text-base-content/20 mb-2"></i>
                                <p class="text-base-content/60">Aucun rapport disponible</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Evolution Chart -->
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-chart-line text-primary"></i>
                        <h3 class="card-title text-primary">Évolution des Comptes Rendus</h3>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 bg-primary rounded-full"></div>
                            <span>Finalisés</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 bg-secondary rounded-full"></div>
                            <span>Rejetés</span>
                        </div>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="evolutionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Status Chart -->
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-chart-pie text-accent"></i>
                        <h3 class="card-title text-primary">Répartition par Statut</h3>
                    </div>
                    <button onclick="refreshCharts()" class="btn btn-ghost btn-sm btn-circle">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="h-80">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-history text-primary"></i>
                <h3 class="card-title text-primary">Activité Récente</h3>
            </div>
            <div class="space-y-3">
                <?php if (!empty($activitesData)): ?>
                    <?php foreach ($activitesData as $activite): ?>
                        <div class="flex items-center gap-3 p-3 hover:bg-base-200 rounded-lg transition-colors">
                            <div class="avatar placeholder">
                                <div class="w-10 h-10 rounded-full <?php echo $activite['statut'] === 'valider' ? 'bg-accent' : 'bg-error'; ?>">
                                    <i class="fas fa-<?php echo $activite['statut'] === 'valider' ? 'check' : 'times'; ?> text-white"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">Rapport <?php echo ucfirst($activite['statut']); ?></p>
                                <p class="text-xs text-base-content/60">
                                    <?php echo $activite['titre']; ?> - <?php echo $activite['prenom_etudiant'] . ' ' . $activite['nom_etudiant']; ?>
                                </p>
                                <p class="text-xs text-base-content/40">
                                    Par <?php echo $activite['prenom_enseignant'] . ' ' . $activite['nom_enseignant']; ?>
                                </p>
                            </div>
                            <span class="badge badge-ghost"><?php echo getTimeAgo($activite['date_validation']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-history text-4xl text-base-content/20 mb-2"></i>
                        <p class="text-base-content/60">Aucune activité récente</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Performance Details Table -->
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-table text-primary"></i>
                    <h3 class="card-title text-primary">Détails des Performances</h3>
                </div>
                <div class="join">
                    <input type="text" placeholder="Rechercher..." class="input input-bordered input-sm join-item" x-model="searchTerm">
                    <button class="btn btn-sm btn-primary join-item">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-zebra">
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
                            <tr class="hover">
                                <td>
                                    <span class="badge <?php 
                                        echo $eval['decision_evaluation'] === 'valider' ? 'badge-success' : 
                                             ($eval['decision_evaluation'] === 'rejeter' ? 'badge-error' : 'badge-warning'); 
                                    ?>">
                                        <?php echo ucfirst($eval['decision_evaluation']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($eval['nom_rapport'] ?? $eval['id_rapport']); ?></td>
                                <td><?php echo htmlspecialchars(($eval['prenom_enseignant'] ?? '') . ' ' . ($eval['nom_enseignant'] ?? $eval['id_evaluateur'])); ?></td>
                                <td class="max-w-xs truncate"><?php echo htmlspecialchars($eval['commentaire']); ?></td>
                                <td><?php echo $eval['date_evaluation']; ?></td>
                                <td>
                                    <div class="join">
                                        <button class="btn btn-sm btn-ghost join-item">
                                            <i class="fas fa-eye text-primary"></i>
                                        </button>
                                        <button class="btn btn-sm btn-ghost join-item">
                                            <i class="fas fa-download text-accent"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-8">
                                <i class="fas fa-table text-4xl text-base-content/20 mb-2"></i>
                                <p class="text-base-content/60">Aucune évaluation trouvée</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let evolutionChart, statusChart;
const evolutionData = { 
    labels: <?php echo json_encode($evolutionLabels); ?>, 
    datasets: [
        { 
            label: 'Finalisés', 
            data: <?php echo json_encode($evolutionFinalises); ?>, 
            borderColor: '#1a5276', 
            backgroundColor: 'rgba(26,82,118,0.1)', 
            tension: 0.4, 
            fill: true 
        }, 
        { 
            label: 'Rejetés', 
            data: <?php echo json_encode($evolutionRejetes); ?>, 
            borderColor: '#f39c12', 
            backgroundColor: 'rgba(243,156,18,0.1)', 
            tension: 0.4, 
            fill: true 
        }
    ] 
};

const statusData = { 
    labels: <?php echo json_encode($statusLabels); ?>, 
    datasets: [{ 
        data: <?php echo json_encode($statusData); ?>, 
        backgroundColor: <?php echo json_encode($statusColors); ?>, 
        borderWidth: 0 
    }] 
};

function initCharts() {
    const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
    evolutionChart = new Chart(evolutionCtx, { 
        type: 'line', 
        data: evolutionData, 
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        usePointStyle: true, 
                        padding: 20,
                        color: '#1a5276',
                        font: { family: 'Poppins' }
                    } 
                } 
            }, 
            scales: { 
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(26,82,118,0.1)' },
                    ticks: { color: '#1a5276' }
                }, 
                x: { 
                    grid: { display: false },
                    ticks: { color: '#1a5276' }
                } 
            }, 
            elements: { 
                point: { 
                    radius: 4, 
                    hoverRadius: 6,
                    backgroundColor: '#fff',
                    borderWidth: 2
                } 
            } 
        } 
    });

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(statusCtx, { 
        type: 'doughnut', 
        data: statusData, 
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        usePointStyle: true, 
                        padding: 15,
                        color: '#1a5276',
                        font: { family: 'Poppins' }
                    } 
                } 
            }, 
            cutout: '60%' 
        } 
    });
}

function refreshCharts() {
    console.log('Refreshing charts...');
    // Implementation for data refresh
}

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
});
</script>