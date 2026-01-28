<?php
/**
 * CheckMaster Premium - Évaluation des Rapports
 * Vue refactorisée avec Premium Design System
 */

// ========== DONNÉES ET LOGIQUE PHP ==========

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/RapportEtudiant.php';
require_once __DIR__ . '/../../app/models/EvaluationRapport.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/AuditLog.php';

$database = new Database();
$pdo = $database->getConnection();

$rapportModel = new RapportEtudiant($pdo);
$evaluationModel = new EvaluationRapport($pdo);
$etudiantModel = new Etudiant($pdo);
$auditLog = new AuditLog($pdo);

$id_utilisateur = $_SESSION['user_id'];

// Statistiques
$stats = [
    'en_attente' => 0,
    'en_cours' => 0,
    'valider' => 0,
    'rejeter' => 0
];

$rapportsAvecEtudiants = [];

try {
    $tousLesRapports = $rapportModel->getAllRapports();
    
    foreach ($tousLesRapports as $rapport) {
        if (isset($stats[$rapport->statut_rapport])) {
            $stats[$rapport->statut_rapport]++;
        }
    }
    
    foreach ($tousLesRapports as $rapport) {
        $etudiant = $etudiantModel->getEtudiantById($rapport->num_etu);
        if ($etudiant) {
            $rapport->etudiant = $etudiant;
            $rapport->evaluations = $evaluationModel->getEvaluationsRapport($rapport->id_rapport);
            $rapport->nb_evaluations = count($rapport->evaluations);
            $rapport->deja_evalue = $evaluationModel->evaluationExiste($rapport->id_rapport, $id_utilisateur);
            $rapportsAvecEtudiants[] = $rapport;
        }
    }
    
    usort($rapportsAvecEtudiants, fn($a, $b) => strtotime($b->date_rapport) - strtotime($a->date_rapport));
    
} catch (Exception $e) {
    $error = "Erreur lors du chargement des rapports : " . $e->getMessage();
    $rapportsAvecEtudiants = [];
}

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'evaluer') {
            $id_rapport = $_POST['id_rapport'] ?? null;
            $decision = $_POST['decision'] ?? null;
            $commentaire = $_POST['commentaire'] ?? '';
            
            if ($id_rapport && $decision) {
                $evaluationExistante = $evaluationModel->evaluationExiste($id_rapport, $id_utilisateur);
                
                if ($evaluationExistante) {
                    $result = $evaluationModel->mettreAJourEvaluation($evaluationExistante['id_evaluation'], $decision, $commentaire);
                } else {
                    $result = $evaluationModel->ajouterEvaluation($id_rapport, $id_utilisateur, $decision, $commentaire);
                }
                
                if ($result) {
                    $_SESSION['success'] = "Votre évaluation a été enregistrée avec succès.";
                    $auditLog->logAction($id_utilisateur, 'EVALUATION', 'evaluations_rapports', 'Évaluation du rapport #' . $id_rapport . ' : ' . $decision);
                } else {
                    $_SESSION['error'] = "Erreur lors de l'enregistrement de votre évaluation.";
                }
            }
        }
        
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
    }
}

// Messages
if (isset($_SESSION['success'])) {
    echo renderAlert($_SESSION['success'], 'success');
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo renderAlert($_SESSION['error'], 'error');
    unset($_SESSION['error']);
}

// ========== STATISTIQUES ==========

echo renderStatsGrid([
    [
        'label' => 'En attente',
        'value' => number_format($stats['en_attente']),
        'icon' => 'clock',
        'type' => 'warning'
    ],
    [
        'label' => 'En cours d\'évaluation',
        'value' => number_format($stats['en_cours']),
        'icon' => 'hourglass-half',
        'type' => 'info'
    ],
    [
        'label' => 'Validés',
        'value' => number_format($stats['valider']),
        'icon' => 'check-circle',
        'type' => 'success'
    ],
    [
        'label' => 'Rejetés',
        'value' => number_format($stats['rejeter']),
        'icon' => 'times-circle',
        'type' => 'danger'
    ]
]);

// ========== LISTE DES RAPPORTS ==========

ob_start();
?>

<div class="space-y-sm">
    <?php if (empty($rapportsAvecEtudiants)): ?>
        <?= renderEmptyState('Aucun rapport à évaluer', 'Tous les rapports ont été traités', 'fa-file-alt') ?>
    <?php else: ?>
        <?php foreach ($rapportsAvecEtudiants as $rapport): ?>
            <div class="card hover:shadow-md transition-shadow fade-in">
                <div class="card-content">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center flex-1 gap-md">
                            <div class="stat-card-icon <?= getStatutColor($rapport->statut_rapport) ?>">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-xs">
                                    <p class="font-semibold text-foreground"><?= htmlspecialchars($rapport->theme_rapport) ?></p>
                                    <div class="flex items-center gap-xs">
                                        <?php if ($rapport->deja_evalue): ?>
                                            <?= renderBadge('Déjà évalué', 'success') ?>
                                        <?php endif; ?>
                                        <?= renderBadge(ucfirst($rapport->statut_rapport), getStatutType($rapport->statut_rapport)) ?>
                                    </div>
                                </div>
                                
                                <p class="text-sm text-muted">
                                    Étudiant: <?= htmlspecialchars($rapport->etudiant->nom . ' ' . $rapport->etudiant->prenom) ?>
                                </p>
                                
                                <div class="flex items-center gap-sm text-xs text-muted mt-xs">
                                    <span>
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y', strtotime($rapport->date_rapport)) ?>
                                    </span>
                                    <span class="separator-dot"></span>
                                    <span class="text-success">
                                        <?= countValidations($rapport->evaluations) ?> validations
                                    </span>
                                    <span class="separator-dot"></span>
                                    <span class="text-danger">
                                        <?= countRejections($rapport->evaluations) ?> rejets
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-xs">
                            <?= renderButton('Consulter', 'outline', true, 'onclick="viewReport(' . $rapport->id_rapport . ')" type="button"', 'sm', 'fa-eye') ?>
                            <?= renderButton('Votes', 'secondary', true, 'onclick="viewEvaluations(' . $rapport->id_rapport . ')" type="button"', 'sm', 'fa-users') ?>
                            
                            <?php if (!$rapport->deja_evalue): ?>
                                <?= renderButton('Évaluer', 'primary', true, 'onclick="openEvaluationModal(' . $rapport->id_rapport . ')" type="button"', 'sm', 'fa-star') ?>
                            <?php endif; ?>
                            
                            <?php if (canEdit()): ?>
                                <?= renderButton('Finaliser', 'success', true, 'onclick="makeFinalDecision(' . $rapport->id_rapport . ')" type="button"', 'sm', 'fa-gavel') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$rapportsListHtml = ob_get_clean();

echo renderFullCard(
    'Rapports à évaluer',
    $rapportsListHtml,
    '',
    '',
    'Commission de validation des rapports de soutenance'
);

// ========== MODALES ==========

// Modal Évaluation
ob_start();
?>
<form method="POST" action="" id="formEvaluation">
    <input type="hidden" name="action" value="evaluer">
    <input type="hidden" name="id_rapport" id="modal_id_rapport">
    
    <div class="grid grid-2 gap-md">
        <!-- Informations du rapport -->
        <div class="space-y-md">
            <div class="card bg-muted-light">
                <div class="card-content">
                    <h4 class="font-semibold mb-sm">Informations du rapport</h4>
                    <div class="space-y-xs text-sm" id="modalRapportInfo">
                        <p><strong>Titre:</strong> <span id="modal_titre"></span></p>
                        <p><strong>Étudiant:</strong> <span id="modal_etudiant"></span></p>
                        <p><strong>Thème:</strong> <span id="modal_theme"></span></p>
                        <p><strong>Date de dépôt:</strong> <span id="modal_date"></span></p>
                    </div>
                </div>
            </div>
            
            <div class="card bg-info-light">
                <div class="card-content">
                    <h4 class="font-semibold mb-sm">Évaluations des autres membres</h4>
                    <div class="space-y-xs" id="modalEvaluationsListe">
                        <!-- Chargé dynamiquement -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulaire d'évaluation -->
        <div class="space-y-md">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-comment text-primary"></i> Votre commentaire / Avis
                </label>
                <textarea name="commentaire" id="evaluationComment" rows="6" required class="form-textarea"
                    placeholder="Veuillez donner votre avis détaillé sur ce rapport..."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-check-circle text-primary"></i> Votre décision
                </label>
                <div class="space-y-xs">
                    <label class="flex items-center p-sm border rounded cursor-pointer hover:bg-success-light transition-colors">
                        <input type="radio" name="decision" value="valider" required class="form-radio">
                        <span class="ml-sm flex items-center text-success">
                            <i class="fas fa-check-circle mr-xs"></i>
                            Valider le rapport
                        </span>
                    </label>
                    <label class="flex items-center p-sm border rounded cursor-pointer hover:bg-danger-light transition-colors">
                        <input type="radio" name="decision" value="rejeter" required class="form-radio">
                        <span class="ml-sm flex items-center text-danger">
                            <i class="fas fa-times-circle mr-xs"></i>
                            Rejeter le rapport
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</form>
<?php
$evaluationContent = ob_get_clean();

$evaluationFooter = renderButton('Annuler', 'secondary', true, 'onclick="closeEvaluationModal()" type="button"');
$evaluationFooter .= ' ';
$evaluationFooter .= renderButton('Soumettre l\'évaluation', 'primary', true, 'type="submit" form="formEvaluation"', '', 'fa-paper-plane');

echo renderModal(
    'evaluationModal',
    'Évaluation du rapport',
    $evaluationContent,
    $evaluationFooter,
    'xl'
);

// Modal Votes
ob_start();
?>
<div id="votesContent" class="space-y-sm">
    <!-- Chargé dynamiquement -->
</div>
<?php
$votesContent = ob_get_clean();

$votesFooter = renderButton('Fermer', 'secondary', true, 'onclick="closeEvaluationsModal()" type="button"');

echo renderModal(
    'evaluationsModal',
    'Résumé des votes',
    $votesContent,
    $votesFooter,
    'lg'
);

// Modal Décision Finale
ob_start();
?>
<form id="formFinalDecision">
    <div class="form-group">
        <label class="form-label">
            <i class="fas fa-gavel text-primary"></i> Commentaire de la commission
        </label>
        <textarea id="finalComment" rows="6" required class="form-textarea"
            placeholder="Motivation de la décision finale de la commission..."></textarea>
    </div>
    
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <p>Cette décision est <strong>irréversible</strong>. Assurez-vous que tous les membres ont voté avant de finaliser.</p>
    </div>
</form>
<?php
$finalContent = ob_get_clean();

$finalFooter = renderButton('Annuler', 'secondary', true, 'onclick="closeFinalDecisionModal()" type="button"');
$finalFooter .= ' ';
$finalFooter .= renderButton('Confirmer la décision', 'danger', true, 'onclick="submitFinalDecision()" type="button"', '', 'fa-gavel');

echo renderModal(
    'finalDecisionModal',
    'Décision finale de la commission',
    $finalContent,
    $finalFooter,
    'md'
);

// ========== FONCTIONS HELPERS ==========

function getStatutColor($statut) {
    $colors = [
        'en_attente' => 'warning',
        'en_cours' => 'info',
        'valider' => 'success',
        'rejeter' => 'danger'
    ];
    return $colors[$statut] ?? 'muted';
}

function getStatutType($statut) {
    $types = [
        'en_attente' => 'warning',
        'en_cours' => 'info',
        'valider' => 'success',
        'rejeter' => 'danger'
    ];
    return $types[$statut] ?? 'muted';
}

function countValidations($evaluations) {
    return count(array_filter($evaluations, fn($e) => $e->decision_evaluation === 'valider'));
}

function countRejections($evaluations) {
    return count(array_filter($evaluations, fn($e) => $e->decision_evaluation === 'rejeter'));
}
?>

<!-- ========== JAVASCRIPT ========== -->

<script>
// ========== VARIABLES GLOBALES ==========
const rapportsData = <?= json_encode($rapportsAvecEtudiants) ?>;
let currentReportId = null;

// ========== GESTION DES MODALES ==========

function openEvaluationModal(reportId) {
    currentReportId = reportId;
    const rapport = rapportsData.find(r => r.id_rapport == reportId);
    
    if (rapport) {
        document.getElementById('modal_id_rapport').value = reportId;
        document.getElementById('modal_titre').textContent = rapport.nom_rapport;
        document.getElementById('modal_etudiant').textContent = rapport.etudiant.nom + ' ' + rapport.etudiant.prenom;
        document.getElementById('modal_theme').textContent = rapport.theme_rapport;
        document.getElementById('modal_date').textContent = new Date(rapport.date_rapport).toLocaleDateString('fr-FR');
        
        const evaluationsListe = document.getElementById('modalEvaluationsListe');
        evaluationsListe.innerHTML = '';
        
        if (rapport.evaluations && rapport.evaluations.length > 0) {
            rapport.evaluations.forEach(eval => {
                const badgeType = eval.decision_evaluation === 'valider' ? 'success' : 'danger';
                const badgeIcon = eval.decision_evaluation === 'valider' ? 'check' : 'times';
                const badgeText = eval.decision_evaluation === 'valider' ? 'Validé' : 'Rejeté';
                
                evaluationsListe.innerHTML += `
                    <div class="flex items-center justify-between p-xs bg-background rounded border">
                        <span class="text-sm font-medium">Évaluateur #${eval.id_evaluateur}</span>
                        <span class="badge badge-${badgeType}">
                            <i class="fas fa-${badgeIcon}"></i> ${badgeText}
                        </span>
                    </div>
                `;
            });
        } else {
            evaluationsListe.innerHTML = '<p class="text-sm text-muted">Aucune évaluation pour le moment</p>';
        }
    }
    
    CM.Modal.show('evaluationModal');
}

function closeEvaluationModal() {
    CM.Modal.hide('evaluationModal');
    currentReportId = null;
}

function viewEvaluations(reportId) {
    currentReportId = reportId;
    const rapport = rapportsData.find(r => r.id_rapport == reportId);
    const votesContent = document.getElementById('votesContent');
    
    if (rapport && rapport.evaluations) {
        votesContent.innerHTML = '';
        rapport.evaluations.forEach(eval => {
            const badgeType = eval.decision_evaluation === 'valider' ? 'success' : 'danger';
            const badgeText = eval.decision_evaluation === 'valider' ? 'Validé' : 'Rejeté';
            votesContent.innerHTML += `
                <div class="card">
                    <div class="card-content">
                        <div class="flex items-center justify-between mb-sm">
                            <span class="font-semibold">Évaluateur #${eval.id_evaluateur}</span>
                            <span class="badge badge-${badgeType}">${badgeText}</span>
                        </div>
                        <p class="text-sm text-muted">${eval.commentaire_evaluation || 'Aucun commentaire'}</p>
                    </div>
                </div>
            `;
        });
    }
    
    CM.Modal.show('evaluationsModal');
}

function closeEvaluationsModal() {
    CM.Modal.hide('evaluationsModal');
    currentReportId = null;
}

function makeFinalDecision(reportId) {
    currentReportId = reportId;
    CM.Modal.show('finalDecisionModal');
}

function closeFinalDecisionModal() {
    CM.Modal.hide('finalDecisionModal');
    currentReportId = null;
}

function viewReport(reportId) {
    CM.Toast.show('Ouverture du rapport #' + reportId, 'info');
}

// ========== SOUMISSION DÉCISION FINALE ==========

function submitFinalDecision() {
    const comment = document.getElementById('finalComment').value;
    
    if (!comment.trim()) {
        CM.Toast.show('Veuillez saisir un commentaire', 'warning');
        return;
    }
    
    if (!confirm('Êtes-vous sûr de vouloir finaliser la décision?\nCette action est irréversible.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'finaliser_decision');
    formData.append('id_rapport', currentReportId);
    formData.append('commentaire', comment);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            CM.Toast.show('Décision finale enregistrée', 'success');
            closeFinalDecisionModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            CM.Toast.show('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        CM.Toast.show('Erreur lors de la finalisation', 'error');
    });
}

// ========== INITIALISATION ==========

document.addEventListener('DOMContentLoaded', function() {
    // Fermeture des modales avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEvaluationModal();
            closeFinalDecisionModal();
            closeEvaluationsModal();
        }
    });
});
</script>

<style>
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.separator-dot::before {
    content: '•';
}
</style>
