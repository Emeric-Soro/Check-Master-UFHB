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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f3f4f6;
        /* bg-gray-100 */
    }

    /* Styles personnalisés pour les dégradés et les couleurs spécifiques */
    .sidebar-bg {
        background: linear-gradient(135deg, #7c3aed, #9a67ea);
        /* from-purple-600 to-purple-400 */
    }

    .card-red-gradient {
        background: linear-gradient(135deg, #ef4444, #f87171);
        /* from-red-500 to-red-400 */
    }

    .card-orange-gradient {
        background: linear-gradient(135deg, #fb923c, #fdba74);
        /* from-orange-400 to-orange-300 */
    }

    .chart-placeholder {
        background-color: #f9fafb;
        /* bg-gray-50 */
        border-radius: 0.75rem;
        /* rounded-xl */
    }

    .donut-chart-segment-1 {
        background-color: #8b5cf6;
        /* violet-500 */
    }

    .donut-chart-segment-2 {
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



    <!-- Contenu principal -->
    <main class="flex-1 p-6 md:p-8">
        <!-- En-tête -->
        <header class="flex items-center justify-between mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Tableau de bord Secrétariat</h1>
            <div class="flex items-center space-x-4 text-gray-600 text-sm">
                <span><?php echo date('d/m/Y'); ?></span>
                <span class="text-gray-400">|</span>
                <span><?php echo date('H:i'); ?></span>
                <!-- Heure actuelle -->
            </div>
        </header>

        <!-- Cartes de statistiques -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Carte 1: Total Étudiants Inscrits -->
            <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
                <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-xl font-bold text-gray-900"><?php echo number_format($stats['etudiants']); ?></div>
                    <div class="text-gray-500 text-sm">Étudiants Inscrits au total</div>
                </div>
            </div>
            <!-- Carte 2: Étudiants Actifs -->
            <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
                <div class="bg-green-100 p-3 rounded-full text-green-600">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-xl font-bold text-gray-900">
                        <?php echo number_format($stats['paiements_complets']); ?></div>
                    <div class="text-gray-500 text-sm">Étudiants Actifs</div>
                </div>
            </div>
            <!-- Carte 3: Demandes d'Admission (Dégradé rouge) -->
            <div class="card-red-gradient p-6 rounded-xl shadow-lg flex items-center space-x-4 text-white">
                <div class="bg-white bg-opacity-30 p-3 rounded-full">
                    <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-xl font-bold"><?php echo number_format($stats['reclamations_en_attente']); ?></div>
                    <div class="text-sm text-white text-opacity-80">Réclamations en attente</div>
                </div>
            </div>
            <!-- Carte 4: Étudiants par Niveau (Dégradé orange) -->
            <div class="card-orange-gradient p-6 rounded-xl shadow-lg flex items-center space-x-4 text-white">
                <div class="bg-white bg-opacity-30 p-3 rounded-full">
                    <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z">
                        </path>
                    </svg>
                </div>
                <div>
                    <div class="text-lg font-bold">
                        <?php 
                        $niveauxAffichage = [];
                        foreach ($inscriptionsParNiveau as $niveau) {
                            $niveauxAffichage[] = $niveau['niveau'] . ': ' . $niveau['total'];
                        }
                        echo implode(' | ', $niveauxAffichage);
                        ?>
                    </div>
                    <div class="text-sm text-white text-opacity-80">Répartition par Niveau</div>
                </div>
            </div>
        </div>

        <!-- Section Statistiques des Inscriptions & Analytique -->
        <div class="grid grid-cols-2 lg:grid-cols-1 gap-6 mb-8">

            <!-- Analytique (Droite) -->
            <div class="bg-white p-6 rounded-xl shadow-lg flex flex-col items-center">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 text-center">Analytique des Étudiants</h2>
                <div class="donut-chart-container mb-6">
                    <div class="donut-chart-inner">
                        <span class="text-2xl font-bold text-gray-900"><?php echo $pourcentageReussite; ?>%</span>
                        <span class="text-gray-500 text-xs">Paiements complets</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm w-full max-w-[200px]">
                    <div class="flex items-center">
                        <span class="w-3 h-3 rounded-full donut-chart-segment-1 mr-2"></span>
                        Paiements complets
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 rounded-full donut-chart-segment-2 mr-2"></span>
                        Paiements partiels
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 rounded-full donut-chart-segment-3 mr-2"></span>
                        Réclamations
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 rounded-full donut-chart-segment-4 mr-2"></span>
                        Total inscriptions
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <div class="text-sm text-gray-600">Montant total perçu</div>
                    <div class="text-lg font-bold text-green-600">
                        <?php echo number_format($montantTotalPerçu, 0, ',', ' '); ?> FCFA</div>
                </div>
            </div>
            <!-- Activités du Secrétariat (Gauche) -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Activités du Secrétariat</h2>
                <div class="space-y-4">
                    <?php if (!empty($activitesRecentes)): ?>
                    <?php foreach ($activitesRecentes as $activite): ?>
                    <div class="flex items-start space-x-3">
                        <div
                            class="w-10 h-10 flex-shrink-0 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L14 11.586V8a6 6 0 00-6-6zM12 15.5V14H8v1.5a2 2 0 104 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-900 font-medium">Inscription traitée</p>
                            <p class="text-gray-600 text-sm">Dossier d'inscription de
                                <?php echo htmlspecialchars($activite['prenom_etu'] . ' ' . $activite['nom_etu']); ?>
                                (<?php echo htmlspecialchars($activite['lib_niv_etude']); ?>)</p>
                            <span
                                class="text-gray-400 text-xs"><?php echo date('d/m/Y H:i', strtotime($activite['date_inscription'])); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center text-gray-500 py-4">
                        <p>Aucune activité récente</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Suivi des Dossiers -->
        <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Suivi des Réclamations</h2>
                <div class="flex mb-4 text-sm border-b border-gray-200">
                    <button id="btn-recentes"
                        class="py-2 px-4 text-gray-700 font-medium border-b-2 border-indigo-500 -mb-px filter-btn active"
                        data-filter="recentes">Réclamations récentes</button>
                    <button id="btn-en-attente"
                        class="py-2 px-4 text-gray-500 hover:text-gray-700 transition-colors duration-200 filter-btn"
                        data-filter="en-attente">En attente</button>
                    <button id="btn-resolues"
                        class="py-2 px-4 text-gray-500 hover:text-gray-700 transition-colors duration-200 filter-btn"
                        data-filter="resolues">Résolues</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Étudiant</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut</th>
                            </tr>
                        </thead>

                        <!-- Section Réclamations Récentes -->
                        <tbody id="section-recentes" class="filter-section active bg-white divide-y divide-gray-200">
                            <?php if (!empty($reclamationsRecentes)): ?>
                            <?php foreach ($reclamationsRecentes as $reclamation): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($reclamation['nom_etu'] . ' ' . $reclamation['prenom_etu']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($reclamation['type_reclamation']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            $statutClass = '';
                                            $statutText = '';
                                            switch($reclamation['statut_reclamation']) {
                                                case 'En attente':
                                                    $statutClass = 'bg-yellow-100 text-yellow-800';
                                                    $statutText = 'En attente';
                                                    break;
                                                case 'Résolue':
                                                    $statutClass = 'bg-green-100 text-green-800';
                                                    $statutText = 'Résolue';
                                                    break;
                                                default:
                                                    $statutClass = 'bg-gray-100 text-gray-800';
                                                    $statutText = $reclamation['statut_reclamation'];
                                            }
                                            echo $statutClass;
                                            ?>">
                                        <?php echo $statutText; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucune réclamation récente
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>

                        <!-- Section Réclamations En Attente -->
                        <tbody id="section-en-attente" class="filter-section hidden bg-white divide-y divide-gray-200">
                            <?php 
                            $reclamationsEnAttente = array_filter($reclamationsRecentes, function($r) {
                                return $r['statut_reclamation'] === 'En attente';
                            });
                            ?>
                            <?php if (!empty($reclamationsEnAttente)): ?>
                            <?php foreach ($reclamationsEnAttente as $reclamation): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($reclamation['nom_etu'] . ' ' . $reclamation['prenom_etu']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($reclamation['type_reclamation']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        En attente
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucune réclamation en attente
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>

                        <!-- Section Réclamations Résolues -->
                        <tbody id="section-resolues" class="filter-section hidden bg-white divide-y divide-gray-200">
                            <?php 
                            $reclamationsResolues = array_filter($reclamationsRecentes, function($r) {
                                return $r['statut_reclamation'] === 'Résolue';
                            });
                            ?>
                            <?php if (!empty($reclamationsResolues)): ?>
                            <?php foreach ($reclamationsResolues as $reclamation): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($reclamation['nom_etu'] . ' ' . $reclamation['prenom_etu']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($reclamation['type_reclamation']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Résolue
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucune réclamation résolue
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Fonctionnalité de filtrage des réclamations
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const filterSections = document.querySelectorAll('.filter-section');

        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const filterType = this.getAttribute('data-filter');

                // Retirer la classe active de tous les boutons
                filterButtons.forEach(btn => {
                    btn.classList.remove('active', 'text-gray-700', 'font-medium',
                        'border-b-2', 'border-indigo-500', '-mb-px');
                    btn.classList.add('text-gray-500');
                });

                // Ajouter la classe active au bouton cliqué
                this.classList.add('active', 'text-gray-700', 'font-medium', 'border-b-2',
                    'border-indigo-500', '-mb-px');
                this.classList.remove('text-gray-500');

                // Masquer toutes les sections
                filterSections.forEach(section => {
                    section.classList.add('hidden');
                    section.classList.remove('active');
                });

                // Afficher la section correspondante
                const targetSection = document.getElementById('section-' + filterType);
                if (targetSection) {
                    targetSection.classList.remove('hidden');
                    targetSection.classList.add('active');
                }
            });
        });
    });
    </script>
</body>

</html>