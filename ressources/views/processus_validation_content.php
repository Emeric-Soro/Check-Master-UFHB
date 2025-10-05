<?php
require_once __DIR__ . '/../../app/controllers/ProcessusValidationController.php';

$controller = new ProcessusValidationController();
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'finaliser') {
    $id_rapport = intval($_POST['id_rapport']);
    
    // Essayer différentes clés possibles pour l'ID enseignant
    $id_enseignant = null;
    if (isset($_SESSION['id_enseignant'])) {
        $id_enseignant = intval($_SESSION['id_enseignant']);
    } elseif (isset($_SESSION['id_utilisateur'])) {
        $id_enseignant = intval($_SESSION['id_utilisateur']);
    } elseif (isset($_SESSION['enseignant_id'])) {
        $id_enseignant = intval($_SESSION['enseignant_id']);
    }
    
    // Vérifier que l'ID enseignant existe dans la table enseignants
    if ($id_enseignant) {
        if (!$controller->verifierIdEnseignant($id_enseignant)) {
            $id_enseignant = null; // ID invalide
        }
    }
    
    // Solution temporaire : utiliser le premier enseignant de la commission si aucun ID valide n'est trouvé
    if (!$id_enseignant) {
        $donnees = $controller->getDonneesPage();
        if (!empty($donnees['membres_commission'])) {
            $id_enseignant = intval($donnees['membres_commission'][0]['id_enseignant']);
        }
    }
    
    // Récupérer le commentaire de validation
    $commentaire = $_POST['commentaire_validation'] ?? null;
    
    if ($id_enseignant && $id_rapport) {
        $result = $controller->finaliserRapport($id_rapport, $id_enseignant, $commentaire);
        $message = $result;
        // Utiliser JavaScript pour la redirection au lieu de header()
        echo "<script>window.location.href = '" . $_SERVER['REQUEST_URI'] . "&message=" . urlencode(json_encode($result)) . "';</script>";
        exit();
    } else {
        $message = ['success' => false, 'message' => 'Erreur : ID enseignant manquant'];
        // Utiliser JavaScript pour la redirection au lieu de header()
        echo "<script>window.location.href = '" . $_SERVER['REQUEST_URI'] . "&message=" . urlencode(json_encode($message)) . "';</script>";
        exit();
    }
}

// Récupérer le message depuis l'URL si présent
if (isset($_GET['message'])) {
    $message = json_decode(urldecode($_GET['message']), true);
}

$donnees = $controller->getDonneesPage();

$statistiques = $donnees['statistiques'];
$rapports = $donnees['rapports'];
$membresCommission = $donnees['membres_commission'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processus de Validation | Commission de Validation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .filter-grid select, .filter-grid input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .reports { background: transparent; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .report-item { 
            padding: 20px; 
            border-bottom: 1px solid #eee; 
            margin-bottom: 24px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            background: #fff;
            transition: box-shadow 0.2s, background 0.2s;
        }
        .report-item:last-child { border-bottom: none; }
        .report-item:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.13);
            background: #f8fafc;
        }
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .report-title { font-size: 18px; font-weight: bold; margin: 0; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-en-cours { background: #fff3cd; color: #856404; }
        .status-valide { background: #d4edda; color: #155724; }
        .status-rejete { background: #f8d7da; color: #721c24; }
        .evaluations {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 2px solid #e0e7ef;
            background: none;
        }
        .evaluations h4 {
            margin-top: 0;
            margin-bottom: 12px;
            color: #3c9e5f;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .evaluation-item { background: #f8f9fa; padding: 15px; margin-bottom: 10px; border-radius: 6px; }
        .evaluator { display: flex; align-items: center; margin-bottom: 8px; }
        .evaluator img { width: 24px; height: 24px; border-radius: 50%; margin-right: 8px; }
        .decision-valid { color: #28a745; }
        .decision-reject { color: #dc3545; }
        .decision-pending { color: #6c757d; }
        .actions { display: flex; gap: 10px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .no-reports { text-align: center; padding: 40px; color: #6c757d; }
        

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-list-check"></i> Processus de Validation des Rapports</h1>
            <p>Aperçu de tous les rapports approuvés par la chargée de communication et leurs évaluations par les membres de la commission</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message['success'] ? 'success' : 'danger' ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 4px; <?= $message['success'] ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' ?>">
                <i class="fas fa-<?= $message['success'] ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($message['message']) ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <h3><i class="fas fa-file-alt"></i> Total rapports approuvés</h3>
                <p style="font-size: 24px; font-weight: bold; color: #007bff;"><?= $statistiques['total_rapports'] ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-clock"></i> En cours d'évaluation</h3>
                <p style="font-size: 24px; font-weight: bold; color: #ffc107;"><?= $statistiques['en_cours'] ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-check"></i> Validés par la commission</h3>
                <p style="font-size: 24px; font-weight: bold; color: #28a745;"><?= $statistiques['valides'] ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-times"></i> Rejetés par la commission</h3>
                <p style="font-size: 24px; font-weight: bold; color: #dc3545;"><?= $statistiques['rejetes'] ?></p>
            </div>
        </div>

        <div class="filters">
            <h3><i class="fas fa-filter"></i> Filtres</h3>
            <div class="filter-grid">
                <div>
                    <label>Statut:</label>
                    <select id="statusFilter" onchange="filterReports()">
                        <option value="">Tous les statuts</option>
                        <option value="en_cours">En cours d'évaluation</option>
                        <option value="valide">Validé</option>
                        <option value="rejete">Rejeté</option>
                    </select>
                </div>
                <div>
                    <label>Membre:</label>
                    <select id="memberFilter" onchange="filterReports()">
                        <option value="">Tous les membres</option>
                        <?php foreach ($membresCommission as $membre): ?>
                            <option value="<?= htmlspecialchars($membre['nom_enseignant'] . ' ' . $membre['prenom_enseignant']) ?>">
                                <?= htmlspecialchars($membre['nom_enseignant'] . ' ' . $membre['prenom_enseignant']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Recherche:</label>
                    <input type="text" id="searchFilter" placeholder="Rechercher un rapport..." onkeyup="filterReports()">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button onclick="resetFilters()" class="btn btn-primary">Réinitialiser</button>
                </div>
            </div>
        </div>

        <div class="reports">
            <div style="padding: 20px; border-bottom: 1px solid #eee;">
                <h3><i class="fas fa-list-check"></i> Rapports approuvés en cours de validation</h3>
                <p id="reportCount"><?= count($rapports) ?> rapports trouvés</p>
            </div>

            <?php if (empty($rapports)): ?>
                <div class="no-reports">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <h3>Aucun rapport approuvé</h3>
                    <p>Aucun rapport n'a encore été approuvé par la chargée de communication.</p>
                </div>
            <?php else: ?>
                <?php foreach ($rapports as $rapport): ?>
                    <div class="report-item" data-status="<?= $rapport['statut_vote']['statut'] ?>">
                        <div class="report-header">
                            <h3 class="report-title"><?= htmlspecialchars($rapport['nom_rapport']) ?></h3>
                            <div>
                                <span class="status-badge status-<?= $rapport['statut_vote']['statut'] ?>">
                                    <?= htmlspecialchars($rapport['statut_vote']['message']) ?>
                                </span>
                            </div>
                        </div>
                        <p>
                            <strong>Étudiant:</strong> <?= htmlspecialchars($rapport['nom_etu'] . ' ' . $rapport['prenom_etu']) ?> • 
                            <strong>Promotion:</strong> <?= htmlspecialchars($rapport['promotion_etu']) ?> • 
                            <strong>Date d'approbation:</strong> <?= date('d/m/Y H:i', strtotime($rapport['date_approv'])) ?>
                        </p>
                        <p><strong>Thème:</strong> <?= htmlspecialchars($rapport['theme_rapport']) ?></p>
                        <p><strong>Approuvé par:</strong> <?= htmlspecialchars($rapport['nom_pers_admin'] . ' ' . $rapport['prenom_pers_admin']) ?></p>
                        
                        <div class="evaluations">
                            <h4>Évaluations des membres :</h4>
                            
                            <?php if (empty($rapport['evaluations'])): ?>
                                <div class="evaluation-item">
                                    <p><em>Aucune évaluation effectuée pour le moment.</em></p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($rapport['evaluations'] as $evaluation): ?>
                                    <div class="evaluation-item">
                                        <div class="evaluator">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($evaluation['nom_enseignant'] . ' ' . $evaluation['prenom_enseignant']) ?>&background=random" 
                                                 alt="<?= htmlspecialchars($evaluation['nom_enseignant']) ?>">
                                            <strong><?= htmlspecialchars($evaluation['nom_enseignant'] . ' ' . $evaluation['prenom_enseignant']) ?></strong>
                                            <span class="decision-<?= $evaluation['decision_evaluation'] === 'valider' ? 'valid' : 'reject' ?>" style="margin-left: auto;">
                                                <?= $evaluation['decision_evaluation'] === 'valider' ? '✅ Validé' : '❌ Rejeté' ?>
                                            </span>
                                        </div>
                                        <p><em>"<?= htmlspecialchars($evaluation['commentaire']) ?>"</em></p>
                                        <small>Évalué le <?= date('d/m/Y à H:i', strtotime($evaluation['date_evaluation'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Afficher les membres qui n'ont pas encore évalué -->
                            <?php 
                            $evaluateursIds = array_column($rapport['evaluations'], 'id_evaluateur');
                            foreach ($membresCommission as $membre):
                                if (!in_array($membre['id_enseignant'], $evaluateursIds)):
                            ?>
                                <div class="evaluation-item">
                                    <div class="evaluator">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($membre['nom_enseignant'] . ' ' . $membre['prenom_enseignant']) ?>&background=random" 
                                             alt="<?= htmlspecialchars($membre['nom_enseignant']) ?>">
                                        <strong><?= htmlspecialchars($membre['nom_enseignant'] . ' ' . $membre['prenom_enseignant']) ?></strong>
                                        <span class="decision-pending" style="margin-left: auto;">⏳ En attente</span>
                                    </div>
                                    <p><em>Pas encore évalué</em></p>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        
                        <div class="actions" style="margin-top: 15px;">
                            <button onclick="viewReport(<?= $rapport['id_rapport'] ?>)" class="btn btn-primary"><i class="fas fa-file-lines"></i> Consulter</button>
                            <?php if ($rapport['statut_vote']['total_votes'] == 4 && !$rapport['statut_vote']['finalise']): ?>
                                <form id="form-finaliser-<?= $rapport['id_rapport'] ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="finaliser">
                                    <input type="hidden" name="id_rapport" value="<?= $rapport['id_rapport'] ?>">
                                    <input type="hidden" name="commentaire_validation" id="commentaire-<?= $rapport['id_rapport'] ?>" value="">
                                    <button type="button" class="btn btn-success" onclick="confirmerFinalisation(<?= $rapport['id_rapport'] ?>)">Finaliser</button>
                                </form>
                            <?php elseif ($rapport['statut_vote']['finalise']): ?>
                                <button class="btn btn-<?= $rapport['statut_vote']['statut'] === 'valide' ? 'success' : 'danger' ?>" disabled>
                                    ⚖️ Finalisé
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    ⏳ En attente (<?= $rapport['statut_vote']['total_votes'] ?>/4 votes)
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modale de confirmation -->
    <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                    Confirmation de finalisation
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors duration-200" id="closeModal">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-4">Voulez-vous vraiment finaliser la décision finale concernant ce rapport ?</p>
                
                <div class="mb-4">
                    <label for="commentaireFinalisation" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment mr-2"></i>Commentaire de validation (optionnel)
                    </label>
                    <textarea 
                        id="commentaireFinalisation" 
                        name="commentaire_validation" 
                        rows="3" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        placeholder="Ajoutez un commentaire pour expliquer la décision finale..."
                    ></textarea>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                        <p class="text-sm text-blue-700">
                            Cette action est irréversible. La décision sera automatiquement déterminée selon le nombre de votes favorables.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
                <button type="button" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200 flex items-center" id="cancelModal">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </button>
                <button type="button" class="px-4 py-2 text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200 flex items-center" id="confirmFinalisation">
                    <i class="fas fa-check mr-2"></i>
                    Confirmer la finalisation
                </button>
            </div>
        </div>
    </div>



    <script>
        function filterReports() {
            const statusFilter = document.getElementById('statusFilter').value;
            const memberFilter = document.getElementById('memberFilter').value;
            const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
            
            const reports = document.querySelectorAll('.report-item');
            let visibleCount = 0;
            
            reports.forEach(report => {
                let show = true;
                
                if (statusFilter && report.dataset.status !== statusFilter) {
                    show = false;
                }
                
                if (memberFilter && !report.textContent.includes(memberFilter)) {
                    show = false;
                }
                
                if (searchFilter && !report.textContent.toLowerCase().includes(searchFilter)) {
                    show = false;
                }
                
                report.style.display = show ? 'block' : 'none';
                if (show) visibleCount++;
            });
            
            document.getElementById('reportCount').textContent = `${visibleCount} rapports trouvés`;
        }
        
        function resetFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('memberFilter').value = '';
            document.getElementById('searchFilter').value = '';
            
            const reports = document.querySelectorAll('.report-item');
            reports.forEach(report => {
                report.style.display = 'block';
            });
            
            document.getElementById('reportCount').textContent = `${reports.length} rapports trouvés`;
        }
        
        function viewReport(reportId) {
            window.location.href = '?page=rapport_a_valider&action=consulter&id=' + reportId;
        }
        
        function confirmerFinalisation(reportId) {
            // Stocker l'ID du rapport pour la confirmation
            document.getElementById('confirmationModal').setAttribute('data-report-id', reportId);
            
            // Ouvrir la modale avec animation
            const modal = document.getElementById('confirmationModal');
            const modalContent = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Animation d'entrée
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }
        
        function closeModal() {
            const modal = document.getElementById('confirmationModal');
            const modalContent = document.getElementById('modalContent');
            
            // Vider le champ commentaire
            document.getElementById('commentaireFinalisation').value = '';
            
            // Animation de sortie
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 300);
        }
        
        // Gérer la confirmation dans la modale
        document.getElementById('confirmFinalisation').addEventListener('click', function() {
            const reportId = document.getElementById('confirmationModal').getAttribute('data-report-id');
            const commentaire = document.getElementById('commentaireFinalisation').value;
            
            if (reportId) {
                // Mettre à jour le champ caché avec le commentaire
                document.getElementById('commentaire-' + reportId).value = commentaire;
                document.getElementById('form-finaliser-' + reportId).submit();
            }
            closeModal();
        });
        
        // Fermer la modale avec les différents boutons
        document.getElementById('closeModal').addEventListener('click', closeModal);
        document.getElementById('cancelModal').addEventListener('click', closeModal);
        
        // Fermer en cliquant à l'extérieur
        document.getElementById('confirmationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html> 