<!DOCTYPE html>
<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/DashboardScolariteController.php';

$dashboardController = new DashboardScolariteController();
$dashboardData = $dashboardController->getDashboardData();

$stats = $dashboardData['stats'];
$inscriptionsParNiveau = $dashboardData['inscriptionsParNiveau'];

// Calculer les statistiques par niveau pour l'affichage
$statsParNiveau = [];
foreach ($inscriptionsParNiveau as $niveau) {
    $statsParNiveau[$niveau['niveau']] = $niveau['total'];
}

// Calculer le pourcentage de réussite (simulation basée sur les paiements complets)
$totalInscriptions = $stats['etudiants'];
$paiementsComplets = $stats['paiements_complets'];
$pourcentageReussite = $totalInscriptions > 0 ? round(($paiementsComplets / $totalInscriptions) * 100) : 0;

// Récupérer les activités récentes (dernières inscriptions)
$db = Database::getConnection();
$queryActivites = "SELECT i.date_inscription, e.nom_etu, e.prenom_etu, n.lib_niv_etude 
                   FROM inscriptions i 
                   JOIN etudiants e ON i.id_etudiant = e.num_etu 
                   JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude 
                   ORDER BY i.date_inscription DESC 
                   LIMIT 5";
$stmtActivites = $db->prepare($queryActivites);
$stmtActivites->execute();
$activitesRecentes = $stmtActivites->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réclamations récentes
$queryReclamations = "SELECT r.date_creation, e.nom_etu, e.prenom_etu, r.type_reclamation, r.statut_reclamation 
                      FROM reclamations r 
                      JOIN etudiants e ON r.num_etu = e.num_etu 
                      ORDER BY r.date_creation DESC 
                      LIMIT 5";
$stmtReclamations = $db->prepare($queryReclamations);
$stmtReclamations->execute();
$reclamationsRecentes = $stmtReclamations->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques financières
$montantTotalPerçu = $stats['montant_percu'];
$montantEnAttente = $stats['montant_attente'];
$montantTotal = $montantTotalPerçu + $montantEnAttente;
$pourcentagePerçu = $montantTotal > 0 ? round(($montantTotalPerçu / $montantTotal) * 100) : 0;

// Calculer le total des inscriptions
$totalInscriptions = array_sum(array_column($inscriptionsParNiveau, 'total'));

// Calculer les nouvelles inscriptions du mois (simulation - à adapter selon vos besoins)
$nouvellesInscriptionsMois = $stats['nouvelles_inscriptions'] ?? 0;
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Secrétariat</title>
</head>
<body class="font-poppins antialiased">

<div class="space-y-6" x-data="{ activeTab: 'recentes' }">
        background-color: #6366f1;
        /* indigo-500 */
    }

    .donut-chart-segment-3 {
        background-color: #fcd34d;
        /* amber-300 */
    }

    .donut-chart-segment-4 {
        background-color: #f87171;
        /* red-400 */
    }

    /* Simulation du graphique en anneau (donut chart) avec des divs concentriques pour l'effet visuel */
    .donut-chart-container {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: conic-gradient(#8b5cf6 0% 45%,
                /* Violet-500 */
                #6366f1 45% 65%,
                /* Indigo-500 */
                #fcd34d 65% 85%,
                /* Amber-300 */
                #f87171 85% 100%
                /* Red-400 */
            );
        position: relative;
    }

    .donut-chart-inner {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90px;
        /* Ajuster la taille du trou central */
        height: 90px;
        background-color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    </style>
</head>

<body class="flex min-h-screen">



    <!-- Header -->
    <div class="card bg-base-100 shadow-lg mb-6">
        <div class="card-body">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <h1 class="text-3xl font-bold text-primary">Tableau de bord Secrétariat</h1>
                <div class="flex items-center gap-2 text-sm text-base-content/60">
                    <span><?php echo date('d/m/Y'); ?></span>
                    <span>|</span>
                    <span><?php echo date('H:i'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Étudiants -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-primary">
                    <i class="fas fa-user-graduate text-3xl"></i>
                </div>
                <div class="stat-title">Étudiants Inscrits</div>
                <div class="stat-value text-primary"><?php echo number_format($stats['etudiants']); ?></div>
                <div class="stat-desc">Total des inscriptions</div>
            </div>
        </div>

        <!-- Étudiants Actifs -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-accent">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
                <div class="stat-title">Étudiants Actifs</div>
                <div class="stat-value text-accent"><?php echo number_format($stats['paiements_complets']); ?></div>
                <div class="stat-desc">Paiements complets</div>
            </div>
        </div>

        <!-- Réclamations -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-error">
                    <i class="fas fa-exclamation-triangle text-3xl"></i>
                </div>
                <div class="stat-title">Réclamations</div>
                <div class="stat-value text-error"><?php echo number_format($stats['reclamations_en_attente']); ?></div>
                <div class="stat-desc">En attente</div>
            </div>
        </div>

        <!-- Répartition par Niveau -->
        <div class="stats shadow bg-base-100">
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <i class="fas fa-chart-bar text-3xl"></i>
                </div>
                <div class="stat-title">Répartition</div>
                <div class="stat-value text-xs text-secondary">
                    <?php 
                    $niveauxAffichage = [];
                    foreach ($inscriptionsParNiveau as $niveau) {
                        $niveauxAffichage[] = $niveau['niveau'] . ': ' . $niveau['total'];
                    }
                    echo implode(' | ', $niveauxAffichage);
                    ?>
                </div>
                <div class="stat-desc">Par niveau</div>
            </div>
        </div>
    </div>

    <!-- Analytics and Activities Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Analytics Chart -->
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body flex flex-col items-center">
                <h2 class="card-title text-primary mb-4 self-start">Analytique des Étudiants</h2>
                <div class="radial-progress text-accent text-4xl font-bold mb-4" style="--value:<?php echo $pourcentageReussite; ?>;">
                    <?php echo $pourcentageReussite; ?>%
                </div>
                <p class="text-sm text-base-content/60 mb-6">Paiements complets</p>
                
                <div class="grid grid-cols-2 gap-4 text-sm w-full">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-primary"></span>
                        <span>Paiements complets</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-secondary"></span>
                        <span>Paiements partiels</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-warning"></span>
                        <span>Réclamations</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-info"></span>
                        <span>Total inscriptions</span>
                    </div>
                </div>
                
                <div class="divider"></div>
                
                <div class="text-center w-full">
                    <div class="text-sm text-base-content/60">Montant total perçu</div>
                    <div class="text-2xl font-bold text-accent">
                        <?php echo number_format($montantTotalPerçu, 0, ',', ' '); ?> FCFA
                    </div>
                </div>
            </div>
        </div>

        <!-- Activities -->
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">Activités du Secrétariat</h2>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php if (!empty($activitesRecentes)): ?>
                        <?php foreach ($activitesRecentes as $activite): ?>
                            <div class="flex items-start gap-3 p-3 hover:bg-base-200 rounded-lg transition-colors">
                                <div class="avatar placeholder">
                                    <div class="w-10 h-10 rounded-full bg-primary text-white">
                                        <i class="fas fa-bell text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm">Inscription traitée</p>
                                    <p class="text-xs text-base-content/60">
                                        Dossier d'inscription de <?php echo htmlspecialchars($activite['prenom_etu'] . ' ' . $activite['nom_etu']); ?>
                                        (<?php echo htmlspecialchars($activite['lib_niv_etude']); ?>)
                                    </p>
                                    <span class="text-xs text-base-content/40">
                                        <?php echo date('d/m/Y H:i', strtotime($activite['date_inscription'])); ?>
                                    </span>
                                </div>
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
    </div>

    <!-- Suivi des Réclamations -->
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <h2 class="card-title text-primary mb-4">Suivi des Réclamations</h2>
            
            <!-- Tabs -->
            <div role="tablist" class="tabs tabs-boxed mb-4">
                <a role="tab" class="tab" :class="{ 'tab-active': activeTab === 'recentes' }" @click="activeTab = 'recentes'">
                    Récentes
                </a>
                <a role="tab" class="tab" :class="{ 'tab-active': activeTab === 'en-attente' }" @click="activeTab = 'en-attente'">
                    En attente
                </a>
                <a role="tab" class="tab" :class="{ 'tab-active': activeTab === 'resolues' }" @click="activeTab = 'resolues'">
                    Résolues
                </a>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Étudiant</th>
                            <th>Type</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody x-show="activeTab === 'recentes'">
                        <?php if (!empty($reclamationsRecentes)): ?>
                            <?php foreach ($reclamationsRecentes as $reclamation): ?>
                                <tr class="hover">
                                    <td><?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['nom_etu'] . ' ' . $reclamation['prenom_etu']); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['type_reclamation']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $reclamation['statut_reclamation'] === 'En attente' ? 'badge-warning' : 
                                                 ($reclamation['statut_reclamation'] === 'Résolue' ? 'badge-success' : 'badge-ghost'); 
                                        ?>">
                                            <?php echo $reclamation['statut_reclamation']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8">
                                    <i class="fas fa-folder-open text-4xl text-base-content/20 mb-2"></i>
                                    <p class="text-base-content/60">Aucune réclamation récente</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    
                    <tbody x-show="activeTab === 'en-attente'">
                        <?php 
                        $reclamationsEnAttente = array_filter($reclamationsRecentes, function($r) {
                            return $r['statut_reclamation'] === 'En attente';
                        });
                        ?>
                        <?php if (!empty($reclamationsEnAttente)): ?>
                            <?php foreach ($reclamationsEnAttente as $reclamation): ?>
                                <tr class="hover">
                                    <td><?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['nom_etu'] . ' ' . $reclamation['prenom_etu']); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['type_reclamation']); ?></td>
                                    <td>
                                        <span class="badge badge-warning">
                                            En attente
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8">
                                    <p class="text-base-content/60">Aucune réclamation en attente</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    
                    <tbody x-show="activeTab === 'resolues'">
                        <?php 
                        $reclamationsResolues = array_filter($reclamationsRecentes, function($r) {
                            return $reclamation['statut_reclamation'] === 'Résolue';
                        });
                        ?>
                        <?php if (!empty($reclamationsResolues)): ?>
                            <?php foreach ($reclamationsResolues as $reclamation): ?>
                                <tr class="hover">
                                    <td><?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['nom_etu'] . ' ' . $reclamation['prenom_etu']); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['type_reclamation']); ?></td>
                                    <td>
                                        <span class="badge badge-success">
                                            Résolue
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8">
                                    <p class="text-base-content/60">Aucune réclamation résolue</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>