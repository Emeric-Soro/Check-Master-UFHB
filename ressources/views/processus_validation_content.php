<?php
require_once __DIR__ . '/../../app/controllers/ProcessusValidationController.php';
require_once __DIR__ . '/components/ui/stats-card.php';
require_once __DIR__ . '/components/ui/card.php';
require_once __DIR__ . '/components/ui/button.php';
require_once __DIR__ . '/components/ui/badge.php';
require_once __DIR__ . '/components/ui/alert.php';

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

<div class="container p-lg">
    <div class="page-header mb-lg">
        <div>
            <h1><i class="fas fa-list-check"></i> Processus de Validation des Rapports</h1>
            <p class="text-muted mt-2">Aperçu de tous les rapports approuvés par la chargée de communication et leurs évaluations par les membres de la commission</p>
        </div>
    </div>

    <?php if ($message): ?>
        <?= renderAlert($message['success'] ? 'success' : 'danger', $message['message']) ?>
    <?php endif; ?>

    <div class="stats-grid mb-lg">
        <?= renderStatsCard('Total rapports approuvés', $statistiques['total_rapports'], 'file-alt', 'primary') ?>
        <?= renderStatsCard('En cours d\'évaluation', $statistiques['en_cours'], 'clock', 'warning') ?>
        <?= renderStatsCard('Validés par la commission', $statistiques['valides'], 'check', 'success') ?>
        <?= renderStatsCard('Rejetés par la commission', $statistiques['rejetes'], 'times', 'danger') ?>
    </div>

    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Filtres</h3>
        </div>
        <div class="card-content">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block mb-2 text-sm font-medium">Statut:</label>
                    <select id="statusFilter" class="form-select w-full" onchange="filterReports()">
                        <option value="">Tous les statuts</option>
                        <option value="en_cours">En cours d'évaluation</option>
                        <option value="valide">Validé</option>
                        <option value="rejete">Rejeté</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium">Membre:</label>
                    <select id="memberFilter" class="form-select w-full" onchange="filterReports()">
                        <option value="">Tous les membres</option>
                        <?php foreach ($membresCommission as $membre): ?>
                            <option value="<?= htmlspecialchars($membre['nom_enseignant'] . ' ' . $membre['prenom_enseignant']) ?>">
                                <?= htmlspecialchars($membre['nom_enseignant'] . ' ' . $membre['prenom_enseignant']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium">Recherche:</label>
                    <input type="text" id="searchFilter" class="form-input w-full" placeholder="Rechercher un rapport..." onkeyup="filterReports()">
                </div>
                <div class="flex items-end">
                    <?= renderButton('Réinitialiser', 'outline', true, 'onclick="resetFilters()"') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-b">
            <div>
                <h3 class="card-title"><i class="fas fa-list-check"></i> Rapports approuvés en cours de validation</h3>
                <p id="reportCount" class="text-muted text-sm"><?= count($rapports) ?> rapports trouvés</p>
            </div>
        </div>

        <?php if (empty($rapports)): ?>
            <div class="card-content py-12 text-center">
                <i class="fas fa-inbox text-6xl text-muted mb-4"></i>
                <h3 class="text-lg font-semibold mb-2">Aucun rapport approuvé</h3>
                <p class="text-muted">Aucun rapport n'a encore été approuvé par la chargée de communication.</p>
            </div>
        <?php else: ?>
            <div class="divide-y">
                <?php foreach ($rapports as $rapport): ?>
                    <div class="report-item p-lg hover:bg-accent-light transition-all" data-status="<?= $rapport['statut_vote']['statut'] ?>">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-bold"><?= htmlspecialchars($rapport['nom_rapport']) ?></h3>
                            <?php
                            $statusMap = [
                                'en_cours' => ['type' => 'warning', 'label' => 'En cours'],
                                'valide' => ['type' => 'success', 'label' => 'Validé'],
                                'rejete' => ['type' => 'danger', 'label' => 'Rejeté']
                            ];
                            $status = $statusMap[$rapport['statut_vote']['statut']] ?? ['type' => 'muted', 'label' => 'Inconnu'];
                            echo renderBadge($status['label'], $status['type']);
                            ?>
                        </div>
                        
                        <div class="text-sm text-muted mb-3">
                            <strong>Étudiant:</strong> <?= htmlspecialchars($rapport['nom_etu'] . ' ' . $rapport['prenom_etu']) ?> •
                            <strong>Promotion:</strong> <?= htmlspecialchars($rapport['promotion_etu']) ?> •
                            <strong>Date d'approbation:</strong> <?= date('d/m/Y H:i', strtotime($rapport['date_approv'])) ?>
                        </div>
                        <p class="mb-2"><strong>Thème:</strong> <?= htmlspecialchars($rapport['theme_rapport']) ?></p>
                        <p class="mb-4"><strong>Approuvé par:</strong> <?= htmlspecialchars($rapport['nom_pers_admin'] . ' ' . $rapport['prenom_pers_admin']) ?></p>

                        <div class="border-t pt-4">
                            <h4 class="text-md font-bold text-success mb-3"><i class="fas fa-user-check"></i> Évaluations des membres</h4>

                            <?php if (empty($rapport['evaluations'])): ?>
                                <div class="bg-muted-light p-4 rounded-lg">
                                    <p class="text-muted italic">Aucune évaluation effectuée pour le moment.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($rapport['evaluations'] as $evaluation): ?>
                                    <div class="bg-muted-light p-4 rounded-lg mb-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <?= renderAvatar($evaluation['nom_enseignant'] . ' ' . $evaluation['prenom_enseignant']) ?>
                                                <strong><?= htmlspecialchars($evaluation['nom_enseignant'] . ' ' . $evaluation['prenom_enseignant']) ?></strong>
                                            </div>
                                            <?php if ($evaluation['decision_evaluation'] === 'valider'): ?>
                                                <span class="text-success font-bold">✅ Validé</span>
                                            <?php else: ?>
                                                <span class="text-danger font-bold">❌ Rejeté</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="italic mb-1">"<?= htmlspecialchars($evaluation['commentaire']) ?>"</p>
                                        <small class="text-muted">Évalué le <?= date('d/m/Y à H:i', strtotime($evaluation['date_evaluation'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php
                            $evaluateursIds = array_column($rapport['evaluations'], 'id_evaluateur');
                            foreach ($membresCommission as $membre):
                                if (!in_array($membre['id_enseignant'], $evaluateursIds)):
                                    ?>
                                    <div class="bg-muted-light p-4 rounded-lg mb-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <?= renderAvatar($membre['nom_enseignant'] . ' ' . $membre['prenom_enseignant']) ?>
                                                <strong><?= htmlspecialchars($membre['nom_enseignant'] . ' ' . $membre['prenom_enseignant']) ?></strong>
                                            </div>
                                            <span class="text-muted">⏳ En attente</span>
                                        </div>
                                        <p class="italic mt-2 text-muted">Pas encore évalué</p>
                                    </div>
                                <?php
                                endif;
                            endforeach;
                            ?>
                        </div>

                        <div class="flex gap-3 mt-4">
                            <?= renderButton('<i class="fas fa-file-lines"></i> Consulter', 'primary', true, "onclick='viewReport({$rapport['id_rapport']})'") ?>
                            <?php if ($rapport['statut_vote']['total_votes'] == 4 && !$rapport['statut_vote']['finalise']): ?>
                                <?php if (canEdit()): ?>
                                    <form id="form-finaliser-<?= $rapport['id_rapport'] ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="finaliser">
                                        <input type="hidden" name="id_rapport" value="<?= $rapport['id_rapport'] ?>">
                                        <input type="hidden" name="commentaire_validation" id="commentaire-<?= $rapport['id_rapport'] ?>" value="">
                                        <?= renderButton('Finaliser', 'success', true, "type='button' onclick='confirmerFinalisation({$rapport['id_rapport']})'") ?>
                                    </form>
                                <?php endif; ?>
                            <?php elseif ($rapport['statut_vote']['finalise']): ?>
                                <?= renderButton('⚖️ Finalisé', $rapport['statut_vote']['statut'] === 'valide' ? 'success' : 'danger', false) ?>
                            <?php else: ?>
                                <?= renderButton("⏳ En attente ({$rapport['statut_vote']['total_votes']}/4 votes)", 'outline', false) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modale de confirmation -->
<div id="confirmationModal" class="modal">
    <div class="modal-content max-w-md" id="modalContent">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                Confirmation de finalisation
            </h3>
            <button type="button" class="modal-close" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p class="mb-4">Voulez-vous vraiment finaliser la décision finale concernant ce rapport ?</p>

            <div class="mb-4">
                <label for="commentaireFinalisation" class="block text-sm font-medium mb-2">
                    <i class="fas fa-comment mr-2"></i>Commentaire de validation (optionnel)
                </label>
                <textarea id="commentaireFinalisation" name="commentaire_validation" rows="3" class="form-input w-full" placeholder="Ajoutez un commentaire pour expliquer la décision finale..."></textarea>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                Cette action est irréversible. La décision sera automatiquement déterminée selon le nombre de votes favorables.
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" id="cancelModal">
                <i class="fas fa-times mr-2"></i> Annuler
            </button>
            <button type="button" class="btn btn-success" id="confirmFinalisation">
                <i class="fas fa-check mr-2"></i> Confirmer la finalisation
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
    document.getElementById('confirmationModal').setAttribute('data-report-id', reportId);
    Modal.show('confirmationModal');
}

function closeModal() {
    document.getElementById('commentaireFinalisation').value = '';
    Modal.hide('confirmationModal');
}

document.getElementById('confirmFinalisation').addEventListener('click', function () {
    const reportId = document.getElementById('confirmationModal').getAttribute('data-report-id');
    const commentaire = document.getElementById('commentaireFinalisation').value;

    if (reportId) {
        document.getElementById('commentaire-' + reportId).value = commentaire;
        document.getElementById('form-finaliser-' + reportId).submit();
    }
    closeModal();
});

document.getElementById('closeModal').addEventListener('click', closeModal);
document.getElementById('cancelModal').addEventListener('click', closeModal);

document.getElementById('confirmationModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>