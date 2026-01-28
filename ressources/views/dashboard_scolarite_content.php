<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/DashboardScolariteController.php';
$dashboardController = new DashboardScolariteController();
$dashboardData = $dashboardController->getDashboardData();
$stats = $dashboardData['stats'];
$inscriptionsParNiveau = $dashboardData['inscriptionsParNiveau'];
?>

<div class="container">
    <h1 class="text-2xl font-bold mb-lg">Tableau de bord Scolarité</h1>

    <div class="stats-grid mb-lg">
        <?php
        echo renderStatsCard('Étudiants inscrits', number_format($stats['etudiants']), 'users', 'primary', 'Total des inscriptions');
        echo renderStatsCard('Réclamations en attente', number_format($stats['reclamations_en_attente']), 'exclamation-triangle', 'warning', 'À traiter');
        echo renderStatsCard('Paiements complets', number_format($stats['paiements_complets']), 'check-circle', 'success', 'Validés');
        echo renderStatsCard('Paiements partiels', number_format($stats['paiements_partiels']), 'euro-sign', 'info', 'En attente: ' . number_format($stats['montant_attente'], 0, ',', ' ') . ' FCFA');
        ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-lg">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Inscriptions par niveau d'études</h2>
            </div>
            <div class="p-lg" style="height: 250px;">
                <canvas id="inscriptionsChart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Statistiques des réclamations</h2>
            </div>
            <div class="p-lg grid grid-cols-2 gap-4">
                <div class="p-4 rounded-lg bg-warning-light text-white">
                    <p class="text-sm font-medium">En attente</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_en_attente']); ?></p>
                </div>
                <div class="p-4 rounded-lg bg-success-light text-white">
                    <p class="text-sm font-medium">Résolues</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_resolues']); ?></p>
                </div>
                <div class="p-4 rounded-lg bg-danger-light text-white">
                    <p class="text-sm font-medium">Rejetées</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_rejetees']); ?></p>
                </div>
                <div class="p-4 rounded-lg bg-primary-light text-white">
                    <p class="text-sm font-medium">Total</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_total']); ?></p>
                </div>
            </div>
            
            <div class="card-header mt-4">
                <h2 class="card-title">Statistiques des paiements</h2>
            </div>
            <div class="p-lg grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-lg bg-primary-light text-white">
                    <p class="text-sm font-medium">Paiements complets</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['paiements_complets']); ?></p>
                </div>
                <div class="p-4 rounded-lg bg-danger-light text-white">
                    <p class="text-sm font-medium">Paiements partiels</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['paiements_partiels']); ?></p>
                </div>
                <div class="p-4 rounded-lg bg-success-light text-white">
                    <p class="text-sm font-medium">Montant total perçu</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['montant_percu'], 0, ',', ' '); ?> FCFA</p>
                </div>
                <div class="p-4 rounded-lg bg-warning-light text-white">
                    <p class="text-sm font-medium">Montant en attente</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['montant_attente'], 0, ',', ' '); ?> FCFA</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('inscriptionsChart');
    if (ctx) {
        const niveauLabels = [];
        const inscriptionsData = [];
        <?php foreach ($inscriptionsParNiveau as $niveau): ?>
            niveauLabels.push('<?php echo $niveau['niveau']; ?>');
            inscriptionsData.push(<?php echo $niveau['total']; ?>);
        <?php endforeach; ?>
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: niveauLabels,
                datasets: [{
                    label: 'Nombre d\'inscriptions',
                    data: inscriptionsData,
                    backgroundColor: ['rgba(26,82,118,0.5)', 'rgba(16,185,129,0.5)', 'rgba(26,82,118,0.5)', 'rgba(26,82,118,0.5)', 'rgba(16,185,129,0.5)'],
                    borderColor: ['#1a5276', '#10b981', '#1a5276', '#1a5276', '#10b981'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
</script>
