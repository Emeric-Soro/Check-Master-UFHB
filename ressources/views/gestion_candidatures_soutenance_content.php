<?php
// Récupérer les données du contrôleur
$candidatures = $GLOBALS['candidatures_soutenance'] ?? [];
$examiner = $GLOBALS['examiner'] ?? null;
$etape = $GLOBALS['etape'] ?? 1;
$etudiantData = $GLOBALS['etudiantData'] ?? null;
$etapeData = $GLOBALS['etapeData'] ?? null;

// Session (centralisée)
\CheckMaster\Core\Session::start();

$statutFiltre = $_GET['statut'] ?? 'all';
if ($statutFiltre !== 'all') {
    $candidatures = array_filter($candidatures, function ($c) use ($statutFiltre) {
        return $c['statut_candidature'] === $statutFiltre;
    });
}

// Charger les résumés de candidatures pour les étudiants traités
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/config/database.php';
$resumes_candidatures = [];
foreach ($candidatures as $c) {
    if ($c['statut_candidature'] !== 'En attente') {
        $resume = (new Etudiant(Database::getConnection()))->getResumeCandidature($c['id_candidature']);
        $resumes_candidatures[$c['id_candidature']] = $resume;
    }
}

// Helper function for rendering badges
function renderStatusBadge($status) {
    $statusMap = [
        'En attente' => ['class' => 'warning', 'label' => 'En attente'],
        'Validée' => ['class' => 'success', 'label' => 'Validée'],
        'validée' => ['class' => 'success', 'label' => 'Validée'],
        'validee' => ['class' => 'success', 'label' => 'Validée'],
        'Rejetée' => ['class' => 'danger', 'label' => 'Rejetée'],
        'rejetée' => ['class' => 'danger', 'label' => 'Rejetée'],
        'rejetee' => ['class' => 'danger', 'label' => 'Rejetée'],
        'validé' => ['class' => 'success', 'label' => 'Validé'],
        'rejeté' => ['class' => 'danger', 'label' => 'Rejeté']
    ];
    $data = $statusMap[$status] ?? ['class' => 'info', 'label' => $status];
    return "<span class='badge badge-{$data['class']}'>{$data['label']}</span>";
}
?>

<div class="container p-lg">
    <div class="page-header mb-lg">
        <h1>Gestion des Candidatures de Soutenance</h1>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'no_stage_info'): ?>
        <div class="alert alert-danger mb-lg">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Erreur :</strong> Cet étudiant n'a pas rempli ses informations de stage. Il ne peut pas être examiné tant qu'il n'a pas complété cette étape.
        </div>
    <?php endif; ?>

    <div class="card mb-lg">
        <div class="card-header">
            <h2 class="card-title">Candidatures en cours</h2>
        </div>
        <div class="card-content">
            <div class="filter-bar mb-md">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-input" placeholder="Rechercher un étudiant...">
                </div>
                <form method="get" id="filterForm">
                    <input type="hidden" name="page" value="gestion_candidatures_soutenance">
                    <select name="statut" id="statusFilter" class="form-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="all" <?= ($_GET['statut'] ?? 'all') === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                        <option value="En attente" <?= ($_GET['statut'] ?? '') === 'En attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="Validée" <?= ($_GET['statut'] ?? '') === 'Validée' ? 'selected' : '' ?>>Validée</option>
                        <option value="Rejetée" <?= ($_GET['statut'] ?? '') === 'Rejetée' ? 'selected' : '' ?>>Rejetée</option>
                    </select>
                </form>
            </div>

            <div class="candidate-list">
                <?php if (empty($candidatures)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>Aucune candidature à afficher</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($candidatures as $candidature): ?>
                        <div class="candidate-item card" data-status="<?= $candidature['statut_candidature'] ?>">
                            <div class="candidate-info">
                                <h3><?= htmlspecialchars($candidature['nom_etu'] . ' ' . $candidature['prenom_etu']) ?></h3>
                                <p class="text-muted">Numéro étudiant: <?= htmlspecialchars($candidature['num_etu']) ?></p>
                                <p class="text-muted">Date de demande: <?= date('d/m/Y', strtotime($candidature['date_candidature'])) ?></p>
                                <p class="text-muted">Statut: <?= renderStatusBadge($candidature['statut_candidature']) ?></p>
                            </div>
                            <?php if ($candidature['statut_candidature'] === 'En attente' && canEdit()): ?>
                                <button class="btn btn-primary" onclick="window.location.href='?page=gestion_candidatures_soutenance&examiner=<?= $candidature['num_etu'] ?>&etape=1'">
                                    Examiner
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Historique des candidatures examinées</h2>
            <div class="card-actions">
                <input type="text" id="searchHistoriqueInput" class="form-input" placeholder="Rechercher dans l'historique...">
                <button class="btn btn-secondary" onclick="printHistoriqueTable()">
                    <i class="fas fa-print"></i> Imprimer
                </button>
                <button class="btn btn-primary" onclick="exportHistoriqueCSV()">
                    <i class="fas fa-file-csv"></i> Exporter
                </button>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="table" id="historiqueCandidaturesTable">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidatures as $candidature): ?>
                        <?php if ($candidature['statut_candidature'] !== 'En attente'): ?>
                            <tr>
                                <td><?= htmlspecialchars($candidature['nom_etu'] . ' ' . $candidature['prenom_etu']) ?></td>
                                <td><?= renderStatusBadge($candidature['statut_candidature']) ?></td>
                                <td><?= date('d/m/Y', strtotime($candidature['date_traitement'] ?? $candidature['date_candidature'])) ?></td>
                                <td>
                                    <button class="btn btn-primary btn-details" data-idcandidature="<?= $candidature['id_candidature'] ?>">
                                        Voir détails
                                    </button>
                                    <div id="resume-candidature-<?= $candidature['id_candidature'] ?>" style="display:none;">
                                        <?php if (!empty($resumes_candidatures[$candidature['id_candidature']])):
                                            $resume = $resumes_candidatures[$candidature['id_candidature']]; ?>
                                            <div class="resume-final">
                                                <div class="decision-finale card mb-md" style="border-left: 4px solid <?= $resume['decision'] === 'Validée' ? 'var(--success)' : 'var(--danger)' ?>;">
                                                    <h4>Décision finale : <?= $resume['decision'] ?></h4>
                                                    <p class="text-muted">Date de traitement : <?= $resume['date_enregistrement'] ?></p>
                                                    <?php if ($resume['decision'] === 'Validée'): ?>
                                                        <p>🎉 Félicitations ! Candidature validée.</p>
                                                    <?php else: ?>
                                                        <p>❌ Candidature rejetée. Voir détails ci-dessous.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="resume-etapes">
                                                    <h4 class="separator">Détail par étape</h4>
                                                    <?php $r = $resume['resume_json']; ?>
                                                    <?php if (!empty($r['scolarite'])): ?>
                                                        <div class="etape-resume card mb-sm" style="border-left: 4px solid <?= $r['scolarite']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)' ?>;">
                                                            <h5><i class='fas fa-money-check'></i> Scolarité</h5>
                                                            <p><strong>Validation :</strong> <?= renderStatusBadge($r['scolarite']['validation']) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($r['stage'])): ?>
                                                        <div class="etape-resume card mb-sm" style="border-left: 4px solid <?= $r['stage']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)' ?>;">
                                                            <h5><i class='fas fa-briefcase'></i> Stage</h5>
                                                            <p><strong>Validation :</strong> <?= renderStatusBadge($r['stage']['validation']) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($r['semestre'])): ?>
                                                        <div class="etape-resume card mb-sm" style="border-left: 4px solid <?= $r['semestre']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)' ?>;">
                                                            <h5><i class='fas fa-graduation-cap'></i> Semestre</h5>
                                                            <p><strong>Validation :</strong> <?= renderStatusBadge($r['semestre']['validation']) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">Aucun résumé trouvé pour cet étudiant.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal détails historique -->
    <div id="historiqueDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeHistoriqueModal()">&times;</span>
            <h2 class="modal-title">Résumé de l'examen de candidature</h2>
            <div id="historiqueDetailsContent">
                <p>Chargement...</p>
            </div>
        </div>
    </div>
</div>


<!-- Modal d'examen -->
<?php if ($examiner && $etudiantData): ?>
    <div id="examinationModal" class="modal" style="display: flex;">
<?php else: ?>
    <div id="examinationModal" class="modal">
<?php endif; ?>
    <div class="modal-content<?= $etape == 4 ? ' resume-step' : '' ?>">
        <a href="?page=gestion_candidatures_soutenance" class="close-button">&times;</a>
        <h2 class="modal-title">Examen de la Candidature - <?= htmlspecialchars($etudiantData['nom_etu'] . ' ' . $etudiantData['prenom_etu']) ?></h2>
        
        <!-- Step Indicator -->
        <div class="step-indicator mb-lg">
            <div class="step <?= isset($_SESSION['etapes_validation'][$examiner][1]) ? $_SESSION['etapes_validation'][$examiner][1] : ($etape == 1 ? 'active' : '') ?>" id="stepScolarite">
                <div class="step-icon">
                    <i class="fas fa-money-check"></i>
                </div>
                <div class="step-label">Scolarité</div>
            </div>
            <div class="step <?= isset($_SESSION['etapes_validation'][$examiner][2]) ? $_SESSION['etapes_validation'][$examiner][2] : ($etape == 2 ? 'active' : '') ?>" id="stepStage">
                <div class="step-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="step-label">Stage</div>
            </div>
            <div class="step <?= isset($_SESSION['etapes_validation'][$examiner][3]) ? $_SESSION['etapes_validation'][$examiner][3] : ($etape == 3 ? 'active' : '') ?>" id="stepSemestre">
                <div class="step-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="step-label">Semestre</div>
            </div>
            <div class="step <?= $etape == 4 ? 'active' : '' ?>" id="stepResume">
                <div class="step-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="step-label">Résumé</div>
            </div>
        </div>

        <?php if ($etapeData): ?>
            <div class="step-content active <?php
                if ($etape == 1) echo 'active-scolarite';
                elseif ($etape == 2) echo 'active-stage';
                elseif ($etape == 3) echo 'active-semestre';
                elseif ($etape == 4) echo 'active-resume';
            ?>">
                <div class="info-section card mb-md">
                    <?php if ($etape == 4): ?>
                        <!-- Résumé final -->
                        <h3 class="separator">Résumé de l'évaluation</h3>
                        <div class="resume-final">
                            <?php
                            $decision = 'Validée';
                            $rejets = 0;
                            foreach ($etapeData as $key => $data) {
                                if ($data['validation'] === 'rejeté') {
                                    $rejets++;
                                    $decision = 'Rejetée';
                                }
                            }
                            ?>
                            <div class="decision-finale card mb-md" style="border-left: 4px solid <?= $decision === 'Validée' ? 'var(--success)' : 'var(--danger)' ?>;">
                                <h4>Décision finale : <?= $decision ?></h4>
                                <?php if ($decision === 'Validée'): ?>
                                    <p>🎉 Félicitations ! Votre candidature a été validée. Vous pouvez procéder à votre soutenance.</p>
                                <?php else: ?>
                                    <p>❌ Votre candidature a été rejetée. Veuillez corriger les problèmes identifiés ci-dessous.</p>
                                <?php endif; ?>
                            </div>

                            <div class="resume-etapes">
                                <h4 class="separator">Détail par étape</h4>

                                <!-- Scolarité -->
                                <div class="etape-resume card mb-sm" style="border-left: 4px solid <?= $etapeData['scolarite']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <div class="etape-details">
                                        <h5><i class="fas fa-money-check"></i> Scolarité</h5>
                                        <p><strong>Validation :</strong> <?= renderStatusBadge($etapeData['scolarite']['validation']) ?></p>
                                    </div>
                                </div>

                                <!-- Stage -->
                                <div class="etape-resume card mb-sm" style="border-left: 4px solid <?= $etapeData['stage']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <div class="etape-details">
                                        <h5><i class="fas fa-briefcase"></i> Stage</h5>
                                        <p><strong>Validation :</strong> <?= renderStatusBadge($etapeData['stage']['validation']) ?></p>
                                    </div>
                                </div>

                                <!-- Semestre -->
                                <div class="etape-resume card mb-sm" style="border-left: 4px solid <?= $etapeData['semestre']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <div class="etape-details">
                                        <h5><i class="fas fa-graduation-cap"></i> Semestre</h5>
                                        <p><strong>Validation :</strong> <?= renderStatusBadge($etapeData['semestre']['validation']) ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (isset($_GET['email_envoye']) && $_GET['email_envoye'] == '1'): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Email envoyé avec succès !</strong> Les résultats ont été envoyés à l'étudiant.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-envelope"></i> Cliquez sur "Envoyer les résultats" pour notifier l'étudiant de la décision finale.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Contenu normal des étapes -->
                        <h3 class="separator">
                            <?php
                            switch ($etape) {
                                case 1: echo 'Vérification de la scolarité'; break;
                                case 2: echo 'Vérification du stage'; break;
                                case 3: echo 'Vérification du semestre'; break;
                            }
                            ?>
                        </h3>

                        <?php if ($etape == 1): ?>
                            <div class="info-item mb-sm">
                                <strong>Statut des paiements:</strong>
                                <span><?= htmlspecialchars($etapeData['status']) ?></span>
                            </div>
                            <div class="info-item mb-sm">
                                <strong>Montant total:</strong>
                                <span><?= htmlspecialchars($etapeData['montant']) ?></span>
                            </div>
                            <div class="info-item mb-sm">
                                <strong>Montant payé:</strong>
                                <span><?= htmlspecialchars($etapeData['montant_paye']) ?></span>
                            </div>
                            <div class="info-item mb-sm">
                                <strong>Dernier paiement:</strong>
                                <span><?= htmlspecialchars($etapeData['dernierPaiement']) ?></span>
                            </div>
                        <?php elseif ($etape == 2): ?>
                            <div class="info-item mb-sm">
                                <strong>Entreprise :</strong>
                                <span><?= htmlspecialchars($etapeData['entreprise']) ?></span>
                            </div>
                            <div class="info-item mb-sm">
                                <strong>Sujet :</strong>
                                <span><?= htmlspecialchars($etapeData['sujet']) ?></span>
                            </div>
                            <div class="info-item mb-sm">
                                <strong>Période :</strong>
                                <span><?= htmlspecialchars($etapeData['periode']) ?></span>
                            </div>
                            <div class="info-item mb-sm">
                                <strong>Encadrant :</strong>
                                <span><?= htmlspecialchars($etapeData['encadrant']) ?></span>
                            </div>
                        <?php elseif ($etape == 3): ?>
                            <div class="info-item mb-sm">
                                <strong>Semestre actuel:</strong>
                                <span><?= htmlspecialchars($etapeData['semestre']) ?></span>
                            </div>
                            <div class="info-item mb-sm">
                                <strong>Moyenne générale:</strong>
                                <span class="text-success"><?= htmlspecialchars($etapeData['moyenne']) ?></span>
                            </div>
                            <div class="info-item mb-sm">
                                <strong>Unités validées:</strong>
                                <span class="text-success"><?= htmlspecialchars($etapeData['unites']) ?></span>
                            </div>
                            <?php if (empty($etapeData['moyenne']) || $etapeData['moyenne'] == '0'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Note :</strong> Aucune note n'a été trouvée pour cet étudiant.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="modal-footer">
            <div class="left-buttons">
                <?php if ($etape > 1 && $etape < 4): ?>
                    <a href="?page=gestion_candidatures_soutenance&examiner=<?= $examiner ?>&etape=<?= $etape - 1 ?>" class="btn btn-secondary">
                        Précédent
                    </a>
                <?php endif; ?>
                <?php if ($etape < 4): ?>
                    <a href="?page=gestion_candidatures_soutenance&examiner=<?= $examiner ?>&etape=<?= $etape + 1 ?>" class="btn btn-primary">
                        Suivant
                    </a>
                <?php endif; ?>
            </div>
            <div class="right-buttons">
                <?php if ($etape < 4): ?>
                    <form method="post" action="?page=gestion_candidatures_soutenance&action=rejeter_etape&examiner=<?= $examiner ?>&etape=<?= $etape ?>">
                        <input type="hidden" name="etape" value="<?= $etape ?>">
                        <button type="submit" class="btn btn-danger">Rejeter</button>
                    </form>
                    <form method="post" action="?page=gestion_candidatures_soutenance&action=valider_etape&examiner=<?= $examiner ?>&etape=<?= $etape ?>">
                        <input type="hidden" name="etape" value="<?= $etape ?>">
                        <button type="submit" class="btn btn-success">
                            <?= $etape == 3 ? 'Terminer l\'évaluation' : 'Valider' ?>
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($etape == 4): ?>
                    <form method="post" action="?page=gestion_candidatures_soutenance&action=envoyer_resultats&examiner=<?= $examiner ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-envelope"></i> Envoyer les résultats
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Fonction de recherche
    document.getElementById('searchInput').addEventListener('input', function (e) {
        const searchTerm = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.candidate-item');

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Recherche dynamique sur la table d'historique
    document.getElementById('searchHistoriqueInput').addEventListener('input', function (e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#historiqueCandidaturesTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
        });
    });

    // Export CSV
    function exportHistoriqueCSV() {
        let csv = 'Étudiant,Statut,Date\n';
        document.querySelectorAll('#historiqueCandidaturesTable tbody tr').forEach(row => {
            if (row.style.display !== 'none') {
                const cols = row.querySelectorAll('td');
                csv += Array.from(cols).slice(0, 3).map(td => '"' + td.textContent.replace(/"/g, '""') + '"')
                    .join(',') + '\n';
            }
        });
        const blob = new Blob([csv], {
            type: 'text/csv'
        });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'historique_candidatures.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Modal Voir détails (AJAX pour charger le résumé)
    document.querySelectorAll('.btn-details').forEach(btn => {
        btn.addEventListener('click', function () {
            const idCandidature = this.getAttribute('data-idcandidature');
            const resumeDiv = document.getElementById('resume-candidature-' + idCandidature);
            document.getElementById('historiqueDetailsContent').innerHTML = resumeDiv ? resumeDiv
                .innerHTML : '<p>Aucun résumé trouvé.</p>';
            document.getElementById('historiqueDetailsModal').style.display = 'flex';
        });
    });

    function closeHistoriqueModal() {
        document.getElementById('historiqueDetailsModal').style.display = 'none';
    }

    function printHistoriqueTable() {
        const printWindow = window.open('', '_blank');
        const table = document.querySelector('#historiqueCandidaturesTable');
        const title = 'Historique des candidatures examinées';
        // Construction des en-têtes sans la colonne Action
        const headers = Array.from(table.querySelectorAll('th')).slice(0, -1).map(th => `<th>${th.textContent}</th>`)
            .join('');
        // Construction des lignes sans la colonne Action
        const rows = Array.from(table.querySelectorAll('tbody tr')).map(row => {
            const cells = Array.from(row.querySelectorAll('td')).slice(0, -1).map(td =>
                `<td>${td.textContent}</td>`).join('');
            return `<tr>${cells}</tr>`;
        }).join('');
        const content = `
        <html>
        <head>
            <title>Impression - ${title}</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #888; padding: 10px 16px; text-align: left; font-size: 14px; }
                th { background-color: #f5f5f5; }
                h1 { text-align: center; color: #333; }
                @media print {
                    body { margin: 0; padding: 20px; }
                    table { page-break-inside: auto; }
                    tr { page-break-inside: avoid; page-break-after: auto; }
                }
            </style>
        </head>
        <body>
            <h1>${title}</h1>
            <table>
                <thead><tr>${headers}</tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </body>
        </html>
    `;
        printWindow.document.write(content);
        printWindow.document.close();
        printWindow.focus();
        printWindow.onload = function () {
            printWindow.print();
            printWindow.close();
        };
    }

    function highlightActiveStep(etape) {
        document.querySelectorAll('.step').forEach(step => {
            step.classList.remove('active-scolarite', 'active-stage', 'active-semestre', 'active-resume');
        });
        if (etape == 1) {
            document.getElementById('stepScolarite').classList.add('active-scolarite');
        } else if (etape == 2) {
            document.getElementById('stepStage').classList.add('active-stage');
        } else if (etape == 3) {
            document.getElementById('stepSemestre').classList.add('active-semestre');
        } else if (etape == 4) {
            document.getElementById('stepResume').classList.add('active-resume');
        }
    }
    highlightActiveStep(<?php echo (int) $etape; ?>);
</script>

