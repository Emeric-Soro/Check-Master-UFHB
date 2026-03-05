<?php
// NOTE : Ce fichier utilise un endpoint AJAX 'resume_candidature_ajax.php' à créer pour charger dynamiquement le résumé de candidature depuis la base.
// Récupérer les données du contrôleur
$candidatures = $GLOBALS['candidatures_soutenance'] ?? [];
$examiner = $GLOBALS['examiner'] ?? null;
$etape = $GLOBALS['etape'] ?? 1;
$etudiantData = $GLOBALS['etudiantData'] ?? null;
$etapeData = $GLOBALS['etapeData'] ?? null;

// Session (centralisée)
\CheckMaster\Core\Session::start();
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$writableYearLabel = \AcademicYear::getWritableLabelFromSession();
$writableYearId = \AcademicYear::getWritableIdFromSession();
$academicYearLabels = [];
foreach (\AcademicYear::fetchAll(Database::getConnection()) as $academicYear) {
    $academicYearLabels[(int)($academicYear['id'] ?? 0)] = (string)($academicYear['label'] ?? '');
}

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

/**
 * Helper: map statut_candidature (FR) to BEM modifier for cm-cand-status-badge
 */
function cmCandStatusModifier(string $statut): string
{
    return match ($statut) {
            'En attente' => 'is-pending',
            'Validée', 'validée' => 'is-validated',
            'Rejetée', 'rejetée' => 'is-rejected',
            default => 'is-pending',
        };
}

/**
 * Helper: map validation result (FR) to BEM modifier for cm-cand-badge / cm-cand-etape-resume
 */
function cmCandBadgeModifier(string $validation): string
{
    return match ($validation) {
            'validé' => 'is-validated',
            'rejeté' => 'is-rejected',
            default => 'is-pending',
        };
}

function cmCandPromotionLabel(array $candidature, array $academicYearLabels): string
{
    $label = trim((string)($candidature['promotion_etu'] ?? ''));
    if ($label !== '') {
        return $label;
    }
    if (!empty($candidature['id_annee_acad'])) {
        return $academicYearLabels[(int)$candidature['id_annee_acad']] ?? '-';
    }
    return '-';
}
?>

<!-- Message d'erreur pour les étudiants sans informations de stage -->
<?php if (isset($_GET['error']) && $_GET['error'] === 'no_stage_info'): ?>
    <div class="cm-alert is-danger">
        <div class="cm-alert__content">
            <strong>Erreur :</strong> Cet étudiant n'a pas rempli ses informations de stage. Il ne peut pas être
            examiné tant qu'il n'a pas complété cette étape.
        </div>
    </div>
<?php
endif; ?>
<?php if ($allYearsSelected): ?>
    <div class="cm-alert is-info">
        <div class="cm-alert__content">
            Affichage global sur toutes les années. Les validations finales restent limitées à l'année active
            <strong><?php echo htmlspecialchars($writableYearLabel, ENT_QUOTES, 'UTF-8'); ?></strong>.
        </div>
    </div>
<?php
endif; ?>

<!-- Filtres -->
<div class="cm-cand-filters">
    <div class="cm-cand-search">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Rechercher un étudiant...">
    </div>
    <form method="get" id="filterForm" class="cm-cand-inline-form">
        <input type="hidden" name="page" value="gestion_candidatures_soutenance">
        <select name="statut" id="statusFilter"
            onchange="document.getElementById('filterForm').submit()">
            <option value="all" <?php if (($_GET['statut'] ?? 'all') === 'all')
    echo 'selected'; ?>>Tous les
                statuts</option>
            <option value="En attente" <?php if (($_GET['statut'] ?? '') === 'En attente')
    echo 'selected'; ?>>
                En attente</option>
            <option value="Validée" <?php if (($_GET['statut'] ?? '') === 'Validée')
    echo 'selected'; ?>>
                Validée</option>
            <option value="Rejetée" <?php if (($_GET['statut'] ?? '') === 'Rejetée')
    echo 'selected'; ?>>
                Rejetée</option>
        </select>
    </form>
</div>

<!-- Liste des candidatures -->
<div class="cm-cand-list">
    <?php if (empty($candidatures)): ?>
        <div class="cm-alert is-info">
            <div class="cm-alert__content">Aucune candidature à afficher</div>
        </div>
    <?php
else: ?>
        <?php foreach ($candidatures as $candidature): ?>
            <div class="cm-cand-item" data-status="<?php echo $candidature['statut_candidature']; ?>">
                <div class="cm-cand-info">
                    <p>Numéro étudiant: <?php echo htmlspecialchars($candidature['num_etu']); ?></p>
                    <p>Promotion: <?php echo htmlspecialchars(cmCandPromotionLabel($candidature, $academicYearLabels)); ?></p>
                    <p>Date de demande: <?php echo date('d/m/Y', strtotime($candidature['date_candidature'])); ?>
                    </p>
                    <p>Statut: <span class="cm-cand-status-badge <?php echo cmCandStatusModifier($candidature['statut_candidature']); ?>">
                            <?php echo ucfirst($candidature['statut_candidature']); ?>
                        </span></p>
                </div>
                <?php if ($candidature['statut_candidature'] === 'En attente'): ?>
                    <?php $canExamine = !$allYearsSelected || ((int)($candidature['id_annee_acad'] ?? 0) === (int)$writableYearId); ?>
                    <?php if (canEdit() && $canExamine): ?>
                    <button class="cm-cand-btn-examine"
                        onclick="window.location.href='?page=gestion_candidatures_soutenance&examiner=<?php echo $candidature['num_etu']; ?>&etape=1'">
                        Examiner
                    </button>
                    <?php
            elseif (canEdit()): ?>
                    <button class="cm-cand-btn-examine" type="button" disabled title="Seule l'année active accepte des écritures">
                        Examiner
                    </button>
                    <?php
            endif; ?>
                <?php
        endif; ?>
            </div>
        <?php
    endforeach; ?>
    <?php
endif; ?>
</div>

<!-- Table d'historique des candidatures examinées -->
<div class="cm-table-wrapper cm-cand-history">
    <?php cm_toolbar([
    'screen' => 'gestion_candidatures',
    'id_prefix' => 'cand_hist',
    'search_value' => $_GET['search'] ?? '',
    'limit' => 10,
    'can_delete' => canDelete(),
    'can_view' => canView(),
]); ?>
</div>

<!-- Modal détails historique -->
<div id="historiqueDetailsModal" class="cm-modal-overlay">
    <div class="cm-modal">
        <div class="cm-modal__header">
            <h3 class="cm-modal__title">Détails de la candidature</h3>
            <button class="cm-modal__close" onclick="closeHistoriqueModal()">&times;</button>
        </div>
        <div class="cm-modal__body">
            <div id="historiqueDetailsContent">
                <!-- Contenu dynamique à charger côté serveur/JS -->
                <p>Chargement...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'examen -->
<?php if ($examiner && $etudiantData): ?>
    <div id="examinationModal" class="cm-modal-overlay is-open">
<?php
else: ?>
    <div id="examinationModal" class="cm-modal-overlay">
<?php
endif; ?>
        <div class="cm-modal<?php echo($etape == 4 ? ' is-resume-step' : ''); ?>">
            <div class="cm-modal__header">
                <h3 class="cm-modal__title">Examen de candidature</h3>
                <a href="?page=gestion_candidatures_soutenance" class="cm-modal__close">&times;</a>
            </div>

            <div class="cm-modal__body cm-cand-modal-body">
                <!--partie des step-icon-->
                <div class="cm-cand-step-indicator">
                    <div class="cm-cand-step <?php echo isset($_SESSION['etapes_validation'][$examiner][1]) ? $_SESSION['etapes_validation'][$examiner][1] : ($etape == 1 ? 'is-active' : ''); ?>"
                        id="stepScolarite">
                        <div class="cm-cand-step__icon">
                            <i class="fas fa-money-check"></i>
                        </div>
                        <div class="cm-cand-step__label">Scolarité</div>
                    </div>
                    <div class="cm-cand-step <?php echo isset($_SESSION['etapes_validation'][$examiner][2]) ? $_SESSION['etapes_validation'][$examiner][2] : ($etape == 2 ? 'is-active' : ''); ?>"
                        id="stepStage">
                        <div class="cm-cand-step__icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="cm-cand-step__label">Stage</div>
                    </div>
                    <div class="cm-cand-step <?php echo isset($_SESSION['etapes_validation'][$examiner][3]) ? $_SESSION['etapes_validation'][$examiner][3] : ($etape == 3 ? 'is-active' : ''); ?>"
                        id="stepSemestre">
                        <div class="cm-cand-step__icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="cm-cand-step__label">Semestre</div>
                    </div>
                    <div class="cm-cand-step <?php echo $etape == 4 ? 'is-active' : ''; ?>" id="stepResume">
                        <div class="cm-cand-step__icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="cm-cand-step__label">Résumé</div>
                    </div>
                </div>

                <?php if ($etapeData): ?>
                    <div class="cm-cand-step-content is-active
                    <?php
    if ($etape == 1)
        echo 'is-scolarite';
    elseif ($etape == 2)
        echo 'is-stage';
    elseif ($etape == 3)
        echo 'is-semestre';
    elseif ($etape == 4)
        echo 'is-resume';
?>">
                        <div class="cm-cand-info-section">
                            <?php if ($etape == 4): ?>
                                <!-- Résumé final -->

                                <div class="cm-cand-resume">
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
                                    <div class="cm-cand-decision <?php echo $decision === 'Validée' ? 'is-validated' : 'is-rejected'; ?>">

                                        <?php if ($decision === 'Validée'): ?>
                                            <p>🎉 Félicitations ! Votre candidature a été validée. Vous pouvez procéder à votre
                                                soutenance.</p>
                                        <?php
        else: ?>
                                            <p>❌ Votre candidature a été rejetée. Veuillez corriger les problèmes identifiés
                                                ci-dessous.</p>
                                        <?php
        endif; ?>
                                    </div>

                                    <div class="cm-cand-resume-etapes">

                                        <!-- Scolarité -->
                                        <div
                                            class="cm-cand-etape-resume <?php echo cmCandBadgeModifier($etapeData['scolarite']['validation']); ?>">
                                            <div class="cm-cand-etape-details">
                                                <p><strong>Validation :</strong>
                                                    <span class="cm-cand-badge <?php echo cmCandBadgeModifier($etapeData['scolarite']['validation']); ?>">
                                                        <?php echo strtoupper($etapeData['scolarite']['validation']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Stage -->
                                        <div
                                            class="cm-cand-etape-resume <?php echo cmCandBadgeModifier($etapeData['stage']['validation']); ?>">
                                            <div class="cm-cand-etape-details">
                                                <p><strong>Validation :</strong>
                                                    <span class="cm-cand-badge <?php echo cmCandBadgeModifier($etapeData['stage']['validation']); ?>">
                                                        <?php echo strtoupper($etapeData['stage']['validation']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Semestre -->
                                        <div
                                            class="cm-cand-etape-resume <?php echo cmCandBadgeModifier($etapeData['semestre']['validation']); ?>">
                                            <div class="cm-cand-etape-details">
                                                <p><strong>Validation :</strong>
                                                    <span class="cm-cand-badge <?php echo cmCandBadgeModifier($etapeData['semestre']['validation']); ?>">
                                                        <?php echo strtoupper($etapeData['semestre']['validation']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>

                                    </div>
                                    <?php if (isset($_GET['email_envoye']) && $_GET['email_envoye'] == '1'): ?>
                                        <div class="cm-cand-email-notice is-success">
                                            <p><i class="fas fa-check-circle"></i> <strong>Email envoyé avec succès !</strong> Les
                                                résultats ont été envoyés à l'étudiant.</p>
                                        </div>
                                    <?php
        else: ?>
                                        <div class="cm-cand-email-notice is-info">
                                            <p><i class="fas fa-envelope"></i> Cliquez sur "Envoyer les résultats" pour notifier
                                                l'étudiant de la décision finale.</p>
                                        </div>
                                    <?php
        endif; ?>
                                </div>
                            <?php
    else: ?>
                                <!-- Contenu normal des étapes -->

                                <?php if ($etape == 1): ?>
                                    <div class="cm-cand-info-item">
                                        <strong>Statut des paiements:</strong>
                                        <span><?php echo htmlspecialchars($etapeData['status']); ?></span>
                                    </div>
                                    <div class="cm-cand-info-item">
                                        <strong>Montant total:</strong>
                                        <span><?php echo htmlspecialchars($etapeData['montant']); ?></span>
                                    </div>
                                    <div class="cm-cand-info-item">
                                        <strong>Montant payé:</strong>
                                        <span><?php echo htmlspecialchars($etapeData['montant_paye']); ?></span>
                                    </div>
                                    <div class="cm-cand-info-item">
                                        <strong>Dernier paiement:</strong>
                                        <span><?php echo htmlspecialchars($etapeData['dernierPaiement']); ?></span>
                                    </div>
                                <?php
        elseif ($etape == 2): ?>
                                    <div class="cm-cand-info-item">
                                        <strong>Entreprise :</strong>
                                        <span><?php echo htmlspecialchars($etapeData['entreprise']); ?></span>
                                    </div>
                                    <div class="cm-cand-info-item">
                                        <strong>Sujet :</strong>
                                        <span><?php echo htmlspecialchars($etapeData['sujet']); ?></span>
                                    </div>
                                    <div class="cm-cand-info-item">
                                        <strong>Période :</strong>
                                        <span><?php echo htmlspecialchars($etapeData['periode']); ?></span>
                                    </div>
                                    <div class="cm-cand-info-item">
                                        <strong>Encadrant :</strong>
                                        <span><?php echo htmlspecialchars($etapeData['encadrant']); ?></span>
                                    </div>
                                <?php
        elseif ($etape == 3): ?>
                                    <div class="cm-cand-info-item">
                                        <strong>Semestre actuel:</strong>
                                        <span><?php echo htmlspecialchars($etapeData['semestre']); ?></span>
                                    </div>
                                    <div class="cm-cand-info-item">
                                        <strong>Moyenne générale:</strong>
                                        <span><?php echo htmlspecialchars($etapeData['moyenne']); ?></span>
                                    </div>
                                    <div class="cm-cand-info-item">
                                        <strong>Unités validées:</strong>
                                        <span><?php echo htmlspecialchars($etapeData['unites']); ?></span>
                                    </div>
                                    <?php if (empty($etapeData['moyenne']) || $etapeData['moyenne'] == '0'): ?>
                                        <div class="cm-cand-alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <strong>Note :</strong> Aucune note n'a été trouvée pour cet étudiant.
                                        </div>
                                    <?php
            endif; ?>
                                <?php
        endif; ?>
                            </div>
                        </div>
                    <?php
    endif; ?>
            </div>

            <div class="cm-modal__footer">
                <div>
                    <?php if ($etape > 1 && $etape < 4): ?>
                        <a href="?page=gestion_candidatures_soutenance&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape - 1; ?>"
                            class="cm-btn is-light">
                            Précédent
                        </a>
                    <?php
    endif; ?>

                    <?php if ($etape < 4): ?>
                        <a href="?page=gestion_candidatures_soutenance&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape + 1; ?>"
                            class="cm-btn is-primary">
                            Suivant
                        </a>
                    <?php
    endif; ?>
                </div>
                <div>
                    <?php if ($etape < 4): ?>
                        <form method="post"
                            action="?page=gestion_candidatures_soutenance&action=rejeter_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>"
                            class="cm-cand-inline-form">
                            <input type="hidden" name="etape" value="<?php echo $etape; ?>">
                            <button type="submit" class="cm-cand-btn-reject">Rejeter</button>
                        </form>
                        <form method="post"
                            action="?page=gestion_candidatures_soutenance&action=valider_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>"
                            class="cm-cand-inline-form">
                            <input type="hidden" name="etape" value="<?php echo $etape; ?>">
                            <button type="submit" class="cm-cand-btn-validate">
                                <?php echo $etape == 3 ? 'Terminer l\'évaluation' : 'Valider'; ?>
                            </button>
                        </form>
                    <?php
    endif; ?>
                    <?php if ($etape == 4): ?>
                        <form method="post"
                            action="?page=gestion_candidatures_soutenance&action=envoyer_resultats&examiner=<?php echo $examiner; ?>"
                            class="cm-cand-inline-form">
                            <button type="submit" class="cm-cand-btn-validate">
                                <i class="fas fa-envelope"></i> Envoyer les résultats
                            </button>
                        </form>
                    <?php
    endif; ?>
                <?php
endif; ?>
                </div>
            </div>
        </div>
    </div>


<script>
    // Fonction de recherche
    document.getElementById('searchInput').addEventListener('input', function (e) {
        const searchTerm = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.cm-cand-item');

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
            document.getElementById('historiqueDetailsModal').classList.add('is-open');
        });
    });

    function closeHistoriqueModal() {
        document.getElementById('historiqueDetailsModal').classList.remove('is-open');
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
        document.querySelectorAll('.cm-cand-step').forEach(step => {
            step.classList.remove('is-scolarite', 'is-stage', 'is-semestre', 'is-resume');
        });
        if (etape == 1) {
            document.getElementById('stepScolarite').classList.add('is-scolarite');
        } else if (etape == 2) {
            document.getElementById('stepStage').classList.add('is-stage');
        } else if (etape == 3) {
            document.getElementById('stepSemestre').classList.add('is-semestre');
        } else if (etape == 4) {
            document.getElementById('stepResume').classList.add('is-resume');
        }
    }
    highlightActiveStep(<?php echo (int)$etape; ?>);
</script>
