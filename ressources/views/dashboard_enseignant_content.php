<?php
require_once __DIR__ . '/../../app/utils/permissions.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Enseignant.php';
require_once __DIR__ . '/../../app/models/NiveauEtude.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/Ue.php';
require_once __DIR__ . '/../../app/models/Ecue.php';

$pdo = Database::getConnection();
$enseignantModel = new Enseignant($pdo);
$niveauEtudeModel = new NiveauEtude($pdo);
$etudiantModel = new Etudiant($pdo);
$ueModel = new Ue($pdo);
$ecueModel = new Ecue($pdo);
$enseignant = $enseignantModel->getEnseignantByLogin($_SESSION['login_utilisateur']);
$enseignantId = $enseignant->id_enseignant;
if (!$enseignantId) {
    die("Enseignant non trouvé ou non connecté.");
}

$total_etudiants = $total_etudiants ?? 0;
$total_ues = $total_ues ?? 0;
$total_ecues = $total_ecues ?? 0;
$mes_cours = $mes_cours ?? [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Enseignant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root{--primary:#0F4C75;--accent:#3282B8;--muted:#64748B;--success:#10b981}
        .stat-card{transition:all .3s ease}
        .stat-card:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(0,0,0,.1)}
        .bg-gradient-to-br.from-blue-400{background:linear-gradient(135deg,var(--primary),var(--accent)) !important}
        .bg-gradient-to-br.from-orange-300{background:linear-gradient(135deg,#fb923c,#fdba74) !important}
        .bg-gradient-to-br.from-green-400{background:linear-gradient(135deg,#34d399,#10b981) !important}
        .text-blue-500{color:var(--primary)}
    </style>
</head>

<body class="font-sans bg-[#F8F7FA] text-gray-600 leading-relaxed p-5">

<div class="container mx-auto px-4 max-w-screen-xl">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-gray-600 text-3xl font-bold">Bonjour, Professeur!</h1>
            <p class="text-gray-400 text-sm mt-1">Bienvenue à nouveau sur votre tableau de bord.</p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="text-right">
                <p class="text-sm text-gray-500"><?php echo date('d/m/Y'); ?></p>
                <p class="text-sm text-gray-400"><?php echo date('H:i'); ?></p>
            </div>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Étudiants suivant les UE/ECUE pris en charge -->
        <div class="stat-card bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white text-sm font-medium">Étudiants (vos UE/ECUE)</h3>
                <i class="fas fa-users text-white text-xl opacity-80"></i>
            </div>
            <div class="text-white text-3xl font-bold mb-2">
                <?php echo $total_etudiants ?? 0; ?>
            </div>
            <div class="text-white text-xs opacity-80">
                Étudiants suivant vos UE/ECUE (tous niveaux confondus)
            </div>
        </div>

        <!-- UE prises en charge -->
        <div class="stat-card bg-gradient-to-br from-orange-300 to-amber-400 rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white text-sm font-medium">UE prises en charge</h3>
                <i class="fas fa-book text-white text-xl opacity-80"></i>
            </div>
            <div class="text-white text-3xl font-bold mb-2">
                <?php echo $total_ues ?? 0; ?>
            </div>
            <div class="text-white text-xs opacity-80">
                Total UE
            </div>
        </div>

        <!-- ECUE pris en charge -->
        <div class="stat-card bg-gradient-to-br from-green-400 to-teal-500 rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white text-sm font-medium">ECUE pris en charge</h3>
                <i class="fas fa-layer-group text-white text-xl opacity-80"></i>
            </div>
            <div class="text-white text-3xl font-bold mb-2">
                <?php echo $total_ecues ?? 0; ?>
            </div>
            <div class="text-white text-xs opacity-80">
                Total ECUE
            </div>
        </div>


    </div>

    <!-- Mes cours -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-gray-900 text-xl font-bold mb-4 pb-3 border-b border-gray-200">
            <i class="fas fa-book text-blue-500 mr-2"></i>
            Mes Cours
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (!empty($mes_cours)): ?>
                <?php foreach ($mes_cours as $cours): ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-gray-900 font-semibold"><?php echo htmlspecialchars($cours['nom']); ?></h3>
                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                            <?php echo htmlspecialchars($cours['niveau']); ?>
                        </span>
                        </div>
                        <p class="text-gray-500 text-sm mb-3"><?php echo $cours['nombre_etudiants']; ?> étudiants inscrits
                        </p>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center text-gray-500 py-8">
                    <i class="fas fa-chalkboard-teacher text-4xl mb-4"></i>
                    <p>Aucun cours assigné</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts pour les graphiques -->
<script>
    // Données pour les graphiques (à remplacer par les vraies données PHP)
    const evaluationsData = <?php echo json_encode($evaluations_par_mois ?? []); ?>;
    const typesEvaluationsData = <?php echo json_encode($types_evaluations ?? []); ?>;
    const etudiantsNiveauData = <?php echo json_encode($etudiants_par_niveau ?? []); ?>;
    const distributionNotesData = <?php echo json_encode($distribution_notes ?? []); ?>;

    // Graphique des évaluations par mois
    const evaluationsCtx = document.getElementById('evaluationsChart').getContext('2d');
    new Chart(evaluationsCtx, {
        type: 'line',
        data: {
            labels: evaluationsData.map(item => {
                const mois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct',
                    'Nov', 'Déc'
                ];
                return mois[item.mois - 1];
            }),
            datasets: [{
                label: 'Évaluations créées',
                data: evaluationsData.map(item => item.nombre_evaluations),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }, {
                label: 'Évaluations terminées',
                data: evaluationsData.map(item => item.evaluations_terminees),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique des types d'évaluations
    const typesCtx = document.getElementById('typesEvaluationsChart').getContext('2d');
    new Chart(typesCtx, {
        type: 'doughnut',
        data: {
            labels: typesEvaluationsData.map(item => item.type_evaluation),
            datasets: [{
                data: typesEvaluationsData.map(item => item.nombre),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(168, 85, 247, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Graphique des étudiants par niveau
    const etudiantsCtx = document.getElementById('etudiantsNiveauChart').getContext('2d');
    new Chart(etudiantsCtx, {
        type: 'bar',
        data: {
            labels: etudiantsNiveauData.map(item => item.niveau),
            datasets: [{
                label: 'Nombre d\'étudiants',
                data: etudiantsNiveauData.map(item => item.nombre_etudiants),
                backgroundColor: 'rgba(168, 85, 247, 0.8)',
                borderColor: 'rgb(168, 85, 247)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique des rapports
    const rapportsCtx = document.getElementById('rapportsChart').getContext('2d');
    new Chart(rapportsCtx, {
        type: 'doughnut',
        data: {
            labels: ['En attente', 'En cours', 'Validés', 'Rejetés'],
            datasets: [{
                data: [
                    <?php echo $stats_rapports['rapports_en_attente'] ?? 0; ?>,
                    <?php echo $stats_rapports['rapports_en_cours'] ?? 0; ?>,
                    <?php echo $stats_rapports['rapports_valides'] ?? 0; ?>,
                    <?php echo $stats_rapports['rapports_rejetes'] ?? 0; ?>
                ],
                backgroundColor: [
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderColor: [
                    'rgb(251, 191, 36)',
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Graphique de distribution des notes
    const distributionCtx = document.getElementById('distributionNotesChart').getContext('2d');
    new Chart(distributionCtx, {
        type: 'bar',
        data: {
            labels: ['0-5', '6-10', '11-15', '16-20'],
            datasets: [{
                label: 'Nombre d\'étudiants',
                data: distributionNotesData.map(item => item.nombre_etudiants),
                backgroundColor: [
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)'
                ],
                borderColor: [
                    'rgb(239, 68, 68)',
                    'rgb(251, 191, 36)',
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Nombre d\'étudiants'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Notes (/20)'
                    }
                }
            }
        }
    });

    // Fonction pour rafraîchir les données en temps réel
    function refreshStats() {
        fetch('?page=dashboard_enseignant&action=get_stats')
            .then(response => response.json())
            .then(data => {
                if (data.stats) {
                    // Mise à jour des statistiques
                    document.querySelector('.bg-gradient-to-br.from-orange-300 .text-3xl').textContent = data.stats
                        .cours.total || 0;
                    document.querySelector('.bg-gradient-to-br.from-green-400 .text-3xl').textContent = data.stats
                        .evaluations.a_faire || 0;
                    document.querySelector('.bg-gradient-to-br.from-blue-400 .text-3xl').textContent = data.stats
                        .etudiants.encadres || 0;
                    document.querySelector('.bg-gradient-to-br.from-purple-400 .text-3xl').textContent = data.stats
                        .evaluations.terminees || 0;
                }
            })
            .catch(error => console.error('Erreur lors du rafraîchissement:', error));
    }

    // Rafraîchir les données toutes les 30 secondes
    setInterval(refreshStats, 30000);
</script>
</body>

</html>