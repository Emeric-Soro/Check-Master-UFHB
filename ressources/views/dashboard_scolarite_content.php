<!DOCTYPE html>
<?php

require_once __DIR__ . '/../../app/utils/StyleManager.php';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hover-scale {
        transition: transform 0.3s ease;
    }

    .hover-scale:hover {
        transform: scale(1.03);
    }

    .gradient-blue {
        background: linear-gradient(135deg, #60A5FA 0%, #3B82F6 100%);
    }

    .gradient-green {
        background: linear-gradient(135deg, #34D399 0%, #10B981 100%);
    }

    .gradient-yellow {
        background: linear-gradient(135deg, #FBBF24 0%, #F59E0B 100%);
    }

    .gradient-red {
        background: linear-gradient(135deg, #F87171 0%, #EF4444 100%);
    }

    .gradient-purple {
        background: linear-gradient(135deg, #A78BFA 0%, #8B5CF6 100%);
    }

    .gradient-orange {
        background: linear-gradient(135deg, #FB923C 0%, #F97316 100%);
    }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Main content area -->
        <div class="flex-1 p-4 md:p-6 overflow-y-auto">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Tableau de bord Scolarité</h1>

                <!-- Statistiques principales -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm hover-scale">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Étudiants inscrits</p>
                                <p class="text-2xl font-bold text-gray-800">
                                    <?php echo number_format($stats['etudiants']); ?></p>
                                <p class="text-xs text-gray-500">Total des inscriptions</p>
                            </div>
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm hover-scale">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Réclamations en attente</p>
                                <p class="text-2xl font-bold text-gray-800">
                                    <?php echo number_format($stats['reclamations_en_attente']); ?></p>
                                <p class="text-xs text-gray-500">À traiter</p>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                                <i class="fas fa-exclamation-triangle text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm hover-scale">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Paiements complets</p>
                                <p class="text-2xl font-bold text-gray-800">
                                    <?php echo number_format($stats['paiements_complets']); ?></p>
                                <p class="text-xs text-gray-500">Validés</p>
                            </div>
                            <div class="p-3 rounded-full bg-primary-100 text-primary-500">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm hover-scale">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Paiements partiels</p>
                                <p class="text-2xl font-bold text-gray-800">
                                    <?php echo number_format($stats['paiements_partiels']); ?></p>
                                <p class="text-xs text-gray-500">En attente:
                                    <?php echo number_format($stats['montant_attente'], 0, ',', ' '); ?> FCFA</p>
                            </div>
                            <div class="p-3 rounded-full bg-red-100 text-red-500">
                                <i class="fas fa-euro-sign text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques et statistiques détaillées -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Graphique des inscriptions par niveau d'étude -->
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Inscriptions par niveau d'études</h2>
                        <canvas id="inscriptionsChart" height="200"></canvas>
                    </div>

                    <!-- Statistiques des réclamations -->
                    <div class="bg-white p-4 rounded-lg shadow-sm ">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Statistiques des réclamations</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-lg gradient-yellow text-white">
                                <p class="text-sm font-medium">En attente</p>
                                <p class="text-2xl font-bold">
                                    <?php echo number_format($stats['reclamations_en_attente']); ?></p>
                            </div>
                            <div class="p-4 rounded-lg gradient-green text-white">
                                <p class="text-sm font-medium">Résolues</p>
                                <p class="text-2xl font-bold">
                                    <?php echo number_format($stats['reclamations_resolues']); ?></p>
                            </div>
                            <div class="p-4 rounded-lg gradient-red text-white">
                                <p class="text-sm font-medium">Rejetées</p>
                                <p class="text-2xl font-bold">
                                    <?php echo number_format($stats['reclamations_rejetees']); ?></p>
                            </div>
                            <div class="p-4 rounded-lg gradient-blue text-white">
                                <p class="text-sm font-medium">Total</p>
                                <p class="text-2xl font-bold">
                                    <?php echo number_format($stats['reclamations_total']); ?></p>
                            </div>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 mt-4">Statistiques des paiements</h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 rounded-lg gradient-blue text-white">
                                <p class="text-sm font-medium">Paiements complets</p>
                                <p class="text-2xl font-bold">
                                    <?php echo number_format($stats['paiements_complets']); ?></p>
                            </div>
                            <div class="p-4 rounded-lg gradient-red text-white">
                                <p class="text-sm font-medium">Paiements partiels</p>
                                <p class="text-2xl font-bold">
                                    <?php echo number_format($stats['paiements_partiels']); ?></p>
                            </div>
                            <div class="p-4 rounded-lg gradient-green text-white">
                                <p class="text-sm font-medium">Montant total perçu</p>
                                <p class="text-2xl font-bold">
                                    <?php echo number_format($stats['montant_percu'], 0, ',', ' '); ?> FCFA</p>
                            </div>
                            <div class="p-4 rounded-lg gradient-yellow text-white">
                                <p class="text-sm font-medium">Montant en attente</p>
                                <p class="text-2xl font-bold">
                                    <?php echo number_format($stats['montant_attente'], 0, ',', ' '); ?> FCFA</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Configuration du graphique des inscriptions par niveau d'étude
    const ctx = document.getElementById('inscriptionsChart').getContext('2d');

    // Préparer les données pour le graphique
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
                backgroundColor: [
                    'rgba(59, 130, 246, 0.5)',
                    'rgba(16, 185, 129, 0.5)',
                    'rgba(245, 158, 11, 0.5)',
                    'rgba(139, 92, 246, 0.5)',
                    'rgba(239, 68, 68, 0.5)'
                ],
                borderColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(139, 92, 246)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>
</body>

</html>