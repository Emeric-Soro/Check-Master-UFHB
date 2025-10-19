<!DOCTYPE html>
<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/DashboardScolariteController.php';
$dashboardController = new DashboardScolariteController();
$dashboardData = $dashboardController->getDashboardData();
$stats = $dashboardData['stats'];
$inscriptionsParNiveau = $dashboardData['inscriptionsParNiveau'];
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsable Scolarité | Tableau de bord</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="font-poppins antialiased">
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Étudiants inscrits -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-primary">
                    <i class="fas fa-users text-3xl"></i>
                </div>
                <div class="stat-title">Étudiants inscrits</div>
                <div class="stat-value text-primary"><?php echo number_format($stats['etudiants']); ?></div>
                <div class="stat-desc">Total des inscriptions</div>
            </div>
        </div>

        <!-- Réclamations en attente -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-warning">
                    <i class="fas fa-exclamation-triangle text-3xl"></i>
                </div>
                <div class="stat-title">Réclamations en attente</div>
                <div class="stat-value text-warning"><?php echo number_format($stats['reclamations_en_attente']); ?></div>
                <div class="stat-desc">À traiter</div>
            </div>
        </div>

        <!-- Paiements complets -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-accent">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
                <div class="stat-title">Paiements complets</div>
                <div class="stat-value text-accent"><?php echo number_format($stats['paiements_complets']); ?></div>
                <div class="stat-desc">Validés</div>
            </div>
        </div>

        <!-- Paiements partiels -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <i class="fas fa-euro-sign text-3xl"></i>
                </div>
                <div class="stat-title">Paiements partiels</div>
                <div class="stat-value text-secondary"><?php echo number_format($stats['paiements_partiels']); ?></div>
                <div class="stat-desc text-xs">En attente: <?php echo number_format($stats['montant_attente'], 0, ',', ' '); ?> FCFA</div>
            </div>
        </div>
    </div>

    <!-- Charts and Stats Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Inscriptions Chart -->
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">Inscriptions par niveau d'études</h2>
                <div class="h-64">
                    <canvas id="inscriptionsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">Statistiques des réclamations</h2>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="card bg-gradient-to-br from-warning to-yellow-400 text-white shadow">
                        <div class="card-body p-4">
                            <p class="text-sm font-medium opacity-80">En attente</p>
                            <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_en_attente']); ?></p>
                        </div>
                    </div>
                    <div class="card bg-gradient-to-br from-accent to-green-400 text-white shadow">
                        <div class="card-body p-4">
                            <p class="text-sm font-medium opacity-80">Résolues</p>
                            <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_resolues']); ?></p>
                        </div>
                    </div>
                    <div class="card bg-gradient-to-br from-error to-red-400 text-white shadow">
                        <div class="card-body p-4">
                            <p class="text-sm font-medium opacity-80">Rejetées</p>
                            <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_rejetees']); ?></p>
                        </div>
                    </div>
                    <div class="card bg-gradient-to-br from-primary to-primary-light text-white shadow">
                        <div class="card-body p-4">
                            <p class="text-sm font-medium opacity-80">Total</p>
                            <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_total']); ?></p>
                        </div>
                    </div>
                </div>
                
                <h2 class="card-title text-primary mb-4 mt-4">Statistiques des paiements</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="card bg-gradient-to-br from-primary to-primary-light text-white shadow">
                        <div class="card-body p-4">
                            <p class="text-xs font-medium opacity-80">Paiements complets</p>
                            <p class="text-xl font-bold"><?php echo number_format($stats['paiements_complets']); ?></p>
                        </div>
                    </div>
                    <div class="card bg-gradient-to-br from-secondary to-yellow-400 text-white shadow">
                        <div class="card-body p-4">
                            <p class="text-xs font-medium opacity-80">Paiements partiels</p>
                            <p class="text-xl font-bold"><?php echo number_format($stats['paiements_partiels']); ?></p>
                        </div>
                    </div>
                    <div class="card bg-gradient-to-br from-accent to-green-400 text-white shadow">
                        <div class="card-body p-4">
                            <p class="text-xs font-medium opacity-80">Montant perçu</p>
                            <p class="text-lg font-bold"><?php echo number_format($stats['montant_percu'], 0, ',', ' '); ?> FCFA</p>
                        </div>
                    </div>
                    <div class="card bg-gradient-to-br from-warning to-yellow-400 text-white shadow">
                        <div class="card-body p-4">
                            <p class="text-xs font-medium opacity-80">Montant en attente</p>
                            <p class="text-lg font-bold"><?php echo number_format($stats['montant_attente'], 0, ',', ' '); ?> FCFA</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('inscriptionsChart').getContext('2d');
    const niveauLabels = [];
    const inscriptionsData = [];
    <?php foreach($inscriptionsParNiveau as $niveau): ?>
    niveauLabels.push('<?php echo $niveau['niveau']; ?>');
    inscriptionsData.push(<?php echo $niveau['total']; ?>);
    <?php endforeach; ?>
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: niveauLabels,
            datasets: [{
                label: 'Nombre d\'inscriptions',
                data: inscriptionsData,
                backgroundColor: ['rgba(26,82,118,0.7)','rgba(76,175,80,0.7)','rgba(243,156,18,0.7)','rgba(231,76,60,0.7)','rgba(52,152,219,0.7)'],
                borderColor: ['#1a5276','#4caf50','#f39c12','#e74c3c','#3498db'],
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a5276',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#fff',
                    borderWidth: 1
                }
            },
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { stepSize: 1, color: '#1a5276' },
                    grid: { color: 'rgba(26,82,118,0.1)' }
                },
                x: {
                    ticks: { color: '#1a5276' },
                    grid: { display: false }
                }
            }
        }
    });
</script>
</body>
</html>