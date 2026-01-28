<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/DashboardScolariteController.php';

$dashboardController = new DashboardScolariteController();
$dashboardData = $dashboardController->getDashboardData();

$stats = $dashboardData['stats'];
$inscriptionsParNiveau = $dashboardData['inscriptionsParNiveau'];

$statsParNiveau = [];
foreach ($inscriptionsParNiveau as $niveau) {
    $statsParNiveau[$niveau['niveau']] = $niveau['total'];
}

$totalInscriptions = $stats['etudiants'];
$paiementsComplets = $stats['paiements_complets'];
$pourcentageReussite = $totalInscriptions > 0 ? round(($paiementsComplets / $totalInscriptions) * 100) : 0;

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

$queryReclamations = "SELECT r.date_creation, e.nom_etu, e.prenom_etu, r.type_reclamation, r.statut_reclamation 
                      FROM reclamations r 
                      JOIN etudiants e ON r.num_etu = e.num_etu 
                      ORDER BY r.date_creation DESC 
                      LIMIT 5";
$stmtReclamations = $db->prepare($queryReclamations);
$stmtReclamations->execute();
$reclamationsRecentes = $stmtReclamations->fetchAll(PDO::FETCH_ASSOC);

$montantTotalPerçu = $stats['montant_percu'];
$montantEnAttente = $stats['montant_attente'];
$montantTotal = $montantTotalPerçu + $montantEnAttente;
$pourcentagePerçu = $montantTotal > 0 ? round(($montantTotalPerçu / $montantTotal) * 100) : 0;

$totalInscriptions = array_sum(array_column($inscriptionsParNiveau, 'total'));
$nouvellesInscriptionsMois = $stats['nouvelles_inscriptions'] ?? 0;
?>

<div class="container">
    <div class="page-header mb-lg">
        <h1 class="text-2xl font-bold">Tableau de bord Secrétariat</h1>
        <div class="flex items-center gap-4 text-sm text-muted">
            <span><?php echo date('d/m/Y'); ?></span>
            <span class="text-muted">|</span>
            <span><?php echo date('H:i'); ?></span>
        </div>
    </div>

    <div class="stats-grid mb-lg">
        <?php
        echo renderStatsCard('Étudiants Inscrits au total', number_format($stats['etudiants']), 'user-graduate', 'primary');
        echo renderStatsCard('Étudiants Actifs', number_format($stats['paiements_complets']), 'wallet', 'success');
        echo renderStatsCard('Réclamations en attente', number_format($stats['reclamations_en_attente']), 'exclamation-triangle', 'danger');
        ?>
        <div class="card p-lg bg-warning-light text-white">
            <div class="flex items-center gap-4">
                <div class="bg-white bg-opacity-30 p-3 rounded-full">
                    <i class="fas fa-chart-bar text-warning text-2xl"></i>
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
                    <div class="text-sm opacity-80">Répartition par Niveau</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-1 gap-6 mb-lg">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Analytique des Étudiants</h2>
            </div>
            <div class="p-lg flex flex-col items-center">
                <div class="mb-6" style="width: 150px; height: 150px; position: relative;">
                    <div style="width: 100%; height: 100%; border-radius: 50%; background: conic-gradient(#1a5276 0% <?php echo $pourcentageReussite; ?>%, #6b7280 <?php echo $pourcentageReussite; ?>% 100%);"></div>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90px; height: 90px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                        <span class="text-2xl font-bold text-primary"><?php echo $pourcentageReussite; ?>%</span>
                        <span class="text-xs text-muted">Paiements complets</span>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <div class="text-sm text-muted">Montant total perçu</div>
                    <div class="text-lg font-bold text-success"><?php echo number_format($montantTotalPerçu, 0, ',', ' '); ?> FCFA</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Activités du Secrétariat</h2>
            </div>
            <div class="p-lg space-y-4">
                <?php if (!empty($activitesRecentes)): ?>
                    <?php foreach ($activitesRecentes as $activite): ?>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 flex-shrink-0 bg-primary-light text-primary rounded-full flex items-center justify-center">
                                <i class="fas fa-bell text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium">Inscription traitée</p>
                                <p class="text-sm text-muted">
                                    Dossier d'inscription de <?php echo htmlspecialchars($activite['prenom_etu'] . ' ' . $activite['nom_etu']); ?>
                                    (<?php echo htmlspecialchars($activite['lib_niv_etude']); ?>)
                                </p>
                                <span class="text-xs text-muted"><?php echo date('d/m/Y H:i', strtotime($activite['date_inscription'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php echo renderEmptyState('Aucune activité récente'); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Suivi des Réclamations</h2>
        </div>
        <div class="flex mb-4 text-sm border-b">
            <button class="py-2 px-4 font-medium border-b-2 border-primary -mb-px filter-btn active" data-filter="recentes">Réclamations récentes</button>
            <button class="py-2 px-4 text-muted hover:text-primary transition-colors filter-btn" data-filter="en-attente">En attente</button>
            <button class="py-2 px-4 text-muted hover:text-primary transition-colors filter-btn" data-filter="resolues">Résolues</button>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Étudiant</th>
                        <th>Type</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody id="section-recentes" class="filter-section active">
                    <?php if (!empty($reclamationsRecentes)): ?>
                        <?php foreach ($reclamationsRecentes as $reclamation): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['nom_etu'] . ' ' . $reclamation['prenom_etu']); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['type_reclamation']); ?></td>
                                <td>
                                    <?php
                                    $badgeType = 'muted';
                                    switch ($reclamation['statut_reclamation']) {
                                        case 'En attente':
                                            $badgeType = 'warning';
                                            break;
                                        case 'Résolue':
                                            $badgeType = 'success';
                                            break;
                                    }
                                    echo renderBadge($reclamation['statut_reclamation'], $badgeType);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                <?php echo renderEmptyState('Aucune réclamation récente'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tbody id="section-en-attente" class="filter-section" style="display:none;">
                    <?php
                    $reclamationsEnAttente = array_filter($reclamationsRecentes, function ($r) {
                        return $r['statut_reclamation'] === 'En attente';
                    });
                    ?>
                    <?php if (!empty($reclamationsEnAttente)): ?>
                        <?php foreach ($reclamationsEnAttente as $reclamation): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['nom_etu'] . ' ' . $reclamation['prenom_etu']); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['type_reclamation']); ?></td>
                                <td><?php echo renderBadge('En attente', 'warning'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                <?php echo renderEmptyState('Aucune réclamation en attente'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tbody id="section-resolues" class="filter-section" style="display:none;">
                    <?php
                    $reclamationsResolues = array_filter($reclamationsRecentes, function ($r) {
                        return $r['statut_reclamation'] === 'Résolue';
                    });
                    ?>
                    <?php if (!empty($reclamationsResolues)): ?>
                        <?php foreach ($reclamationsResolues as $reclamation): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($reclamation['date_creation'])); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['nom_etu'] . ' ' . $reclamation['prenom_etu']); ?></td>
                                <td><?php echo htmlspecialchars($reclamation['type_reclamation']); ?></td>
                                <td><?php echo renderBadge('Résolue', 'success'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                <?php echo renderEmptyState('Aucune réclamation résolue'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const filterSections = document.querySelectorAll('.filter-section');

        filterButtons.forEach(button => {
            button.addEventListener('click', function () {
                const filterType = this.getAttribute('data-filter');

                filterButtons.forEach(btn => {
                    btn.classList.remove('active', 'font-medium', 'border-b-2', 'border-primary', '-mb-px');
                    btn.classList.add('text-muted');
                });

                this.classList.add('active', 'font-medium', 'border-b-2', 'border-primary', '-mb-px');
                this.classList.remove('text-muted');

                filterSections.forEach(section => {
                    section.style.display = 'none';
                    section.classList.remove('active');
                });

                const targetSection = document.getElementById('section-' + filterType);
                if (targetSection) {
                    targetSection.style.display = '';
                    targetSection.classList.add('active');
                }
            });
        });
    });
</script>
