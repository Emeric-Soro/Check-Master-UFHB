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

            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <h3 class="text-gray-900 text-lg font-semibold mb-4">
                    <i class="fas fa-list-alt text-accent mr-2"></i>
                    Détails des Performances (Évaluations des rapports)
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rapport</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Évaluateur</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Étudiant</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Enseignant</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Temps de traitement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($rapportsDetails)): ?>
                            <?php foreach ($rapportsDetails as $rapport): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $rapport['statut'] === 'valider' ? 'bg-green-100 text-primary' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($rapport['statut']); ?>
                                            </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">
                                        <?php echo $rapport['titre']; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">
                                        <?php echo $rapport['prenom_enseignant'] . ' ' . $rapport['nom_enseignant']; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">
                                        <?php echo $rapport['prenom_etudiant'] . ' ' . $rapport['nom_etudiant']; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">
                                        <?php echo $rapport['temps_traitement'] ?? 0; ?> jours
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-2">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-table text-2xl mb-2"></i>
                                    <p>Aucun rapport disponible</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6 fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                            Évolution des Comptes Rendus
                        </h3>
                        <div class="flex items-center space-x-2 text-sm">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-600 rounded-full mr-1"></div>
                                <span>Finalisés</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-600 rounded-full mr-1"></div>
                                <span>Rejetés</span>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="evolutionChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-chart-pie text-green-600 mr-2"></i>
                            Répartition par Statut
                        </h3>
                        <button onclick="refreshCharts()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 fade-in mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-history text-blue-600 mr-2"></i>
                    Activité Récente
                </h3>
                <div class="space-y-3">
                    <?php if (!empty($activitesData)): ?>
                        <?php foreach ($activitesData as $activite): ?>
                            <div class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-lg">
                                <div class="p-2 <?php echo $activite['statut'] === 'valider' ? 'bg-green-100' : 'bg-red-100'; ?> rounded-full">
                                    <i class="fas fa-<?php echo $activite['statut'] === 'valider' ? 'check' : 'times'; ?> text-<?php echo $activite['statut'] === 'valider' ? 'green' : 'blue'; ?>-600 text-xs"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium">Rapport <?php echo ucfirst($activite['statut']); ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo $activite['titre']; ?> - <?php echo $activite['prenom_etudiant'] . ' ' . $activite['nom_etudiant']; ?>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Par <?php echo $activite['prenom_enseignant'] . ' ' . $activite['nom_enseignant']; ?>
                                    </p>
                                </div>
                                <span class="text-xs text-gray-400"><?php echo getTimeAgo($activite['date_validation']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-gray-500 py-4">
                            <i class="fas fa-history text-2xl mb-2"></i>
                            <p>Aucune activité récente</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-table text-gray-600 mr-2"></i>
                        Détails des Performances
                    </h3>
                    <div class="flex items-center space-x-2">
                        <input type="text" placeholder="Rechercher..." class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                        <button class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rapport</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Évaluateur</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commentaire</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'évaluation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($dashboardData['evaluations_rapports'])): ?>
                            <?php foreach ($dashboardData['evaluations_rapports'] as $eval): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $eval['decision_evaluation'] === 'valider' ? 'bg-green-100 text-primary' : ($eval['decision_evaluation'] === 'rejeter' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'); ?>">
                                                <?php echo ucfirst($eval['decision_evaluation']); ?>
                                            </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($eval['nom_rapport'] ?? $eval['id_rapport']); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">
                                        <?php echo htmlspecialchars(($eval['prenom_enseignant'] ?? '') . ' ' . ($eval['nom_enseignant'] ?? $eval['id_evaluateur'])); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($eval['commentaire']); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">
                                        <?php echo $eval['date_evaluation']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-2">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-table text-2xl mb-2"></i>
                                    <p>Aucune évaluation trouvée</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let evolutionChart, statusChart;
    const evolutionData = { labels: <?php echo json_encode($evolutionLabels); ?>, datasets: [{ label: 'Finalisés', data: <?php echo json_encode($evolutionFinalises); ?>, borderColor: '#1a5276', backgroundColor: 'rgba(15,76,117,0.08)', tension: 0.4, fill: true }, { label: 'Rejetés', data: <?php echo json_encode($evolutionRejetes); ?>, borderColor: '#1a5276', backgroundColor: 'rgba(15,76,117,0.06)', tension: 0.4, fill: true }] };
    const statusData = { labels: <?php echo json_encode($statusLabels); ?>, datasets: [{ data: <?php echo json_encode($statusData); ?>, backgroundColor: <?php echo json_encode($statusColors); ?>, borderWidth: 0 }] };

    function initCharts() {
        const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
        evolutionChart = new Chart(evolutionCtx, { type: 'line', data: evolutionData, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }, elements: { point: { radius: 4, hoverRadius: 6 } } } });

        const statusCtx = document.getElementById('statusChart').getContext('2d');
        statusChart = new Chart(statusCtx, { type: 'doughnut', data: statusData, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } } }, cutout: '60%' } });
    }

    function refreshCharts() {
        showNotification('Actualisation des données...', 'info');
        setTimeout(()=>{ showNotification('Données actualisées avec succès','success'); },1500);
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-4 py-2 rounded-md text-white text-sm font-medium z-50 ${ type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-blue-600' : type === 'info' ? 'bg-blue-600' : 'bg-blue-600' }`;
        notification.innerHTML = `<div class="flex items-center"><i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'} mr-2"></i>${message}</div>`;
        document.body.appendChild(notification);
        setTimeout(()=>{ notification.remove(); },3000);
    }

    function animateMetrics() {
        const metrics = document.querySelectorAll('.metric-value');
        metrics.forEach((metric,index)=>{
            const finalValue = metric.textContent;
            metric.textContent = '0';
            setTimeout(()=>{
                const increment = finalValue.includes('%') ? 1 : finalValue.includes('j') ? 0.1 : 1;
                const target = parseFloat(finalValue);
                let current = 0;
                const timer = setInterval(()=>{
                    current += increment;
                    if (current >= target) { current = target; clearInterval(timer); }
                    if (finalValue.includes('%')) metric.textContent = Math.round(current) + '%';
                    else if (finalValue.includes('j')) metric.textContent = current.toFixed(1) + 'j';
                    else metric.textContent = Math.round(current);
                },50);
            }, index * 200);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        animateMetrics();
        const cards = document.querySelectorAll('.fade-in');
        cards.forEach((card,index)=>{ setTimeout(()=>{ card.style.opacity='1'; card.style.transform='translateY(0)'; }, index * 100); });
        document.addEventListener('keydown', function(e){ if (e.ctrlKey && e.key === 'r') { e.preventDefault(); refreshCharts(); } });
    });
    setInterval(()=>{ refreshCharts(); }, 300000);
</script>
</body>
</html>