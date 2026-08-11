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
        return \FormattingUtils::formatPromotion($label);
    }
    if (!empty($candidature['id_annee_acad'])) {
        return \FormattingUtils::formatPromotion($academicYearLabels[(int)$candidature['id_annee_acad']] ?? '-');
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

<style>
.cm-cand-exam-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin: 1.5rem 0;
    padding: 1.5rem;
    border: 2px solid #e2e8f0;
}
.cm-cand-exam-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}
.cm-cand-exam-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a5276;
    margin: 0;
}
.cm-cand-exam-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}
.cm-cand-exam-nav {
    display: flex;
    gap: 0.5rem;
}
.cm-cand-exam-buttons {
    display: flex;
    gap: 0.5rem;
}
</style>

<div class="cm-card cm-mb-md cm-candidatures-submenu">
    <div class="cm-card__body cm-flex cm-flex-wrap cm-flex-gap-sm">
        <a class="cm-btn is-primary is-sm" href="?page=gestion_candidatures"><i class="fas fa-folder-open" aria-hidden="true"></i> Dossiers de candidatures</a>
        <a class="cm-btn is-light is-sm" href="?page=gestion_candidatures&action=evaluations_m2_s1"><i class="fas fa-table-list" aria-hidden="true"></i> Évaluations M2/S1</a>
    </div>
</div>

<!-- Filtres -->
<div class="cm-cand-filters">
    <div class="cm-cand-search">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" class="cm-form-control cm-toolbar-field-lg cm-size-personne" placeholder="Rechercher un étudiant...">
    </div>
    <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#filterForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form method="get" id="filterForm" class="cm-cand-inline-form">
        <input type="hidden" name="page" value="gestion_candidatures_soutenance">
        <select name="statut" id="statusFilter" class="cm-form-control cm-toolbar-field-sm cm-size-salle"
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

<!-- Section d'examen inline -->
<?php if ($examiner && $etudiantData): ?>
<div id="examinationSection" class="cm-cand-exam-section">
    <div class="cm-cand-exam-header">
        <h3 class="cm-cand-exam-title">Examen de candidature - <?php echo htmlspecialchars($etudiantData['nom_etu'] ?? ''); ?></h3>
        <a href="?page=gestion_candidatures_soutenance" class="cm-btn is-light is-sm">
            <i class="fas fa-times"></i> Fermer
        </a>
    </div>

    <!-- Indicateur d'étapes -->
    <div class="cm-cand-step-indicator">
        <div class="cm-cand-step <?php echo isset($_SESSION['etapes_validation'][$examiner][1]) ? $_SESSION['etapes_validation'][$examiner][1] : ($etape == 1 ? 'is-active' : ''); ?>" id="stepScolarite">
            <div class="cm-cand-step__icon"><i class="fas fa-money-check"></i></div>
            <div class="cm-cand-step__label">Scolarité</div>
        </div>
        <div class="cm-cand-step <?php echo isset($_SESSION['etapes_validation'][$examiner][2]) ? $_SESSION['etapes_validation'][$examiner][2] : ($etape == 2 ? 'is-active' : ''); ?>" id="stepStage">
            <div class="cm-cand-step__icon"><i class="fas fa-briefcase"></i></div>
            <div class="cm-cand-step__label">Stage</div>
        </div>
        <div class="cm-cand-step <?php echo isset($_SESSION['etapes_validation'][$examiner][3]) ? $_SESSION['etapes_validation'][$examiner][3] : ($etape == 3 ? 'is-active' : ''); ?>" id="stepSemestre">
            <div class="cm-cand-step__icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="cm-cand-step__label">Semestre</div>
        </div>
        <div class="cm-cand-step <?php echo $etape == 4 ? 'is-active' : ''; ?>" id="stepResume">
            <div class="cm-cand-step__icon"><i class="fas fa-clipboard-check"></i></div>
            <div class="cm-cand-step__label">Résumé</div>
        </div>
    </div>

    <!-- Contenu de l'étape -->
    <?php if ($etapeData): ?>
        <div class="cm-cand-step-content is-active">
            <div class="cm-cand-info-section">
                <?php if ($etape == 4): ?>
                    <!-- Résumé final -->
                    <div class="cm-cand-resume">
                        <?php
                        $decision = 'Validée';
                        foreach ($etapeData as $key => $data) {
                            if ($data['validation'] === 'rejeté') {
                                $decision = 'Rejetée';
                            }
                        }
                        ?>
                        <div class="cm-cand-decision <?php echo $decision === 'Validée' ? 'is-validated' : 'is-rejected'; ?>">
                            <?php if ($decision === 'Validée'): ?>
                                <p>Félicitations ! La candidature a été validée.</p>
                            <?php else: ?>
                                <p>La candidature a été rejetée.</p>
                            <?php endif; ?>
                        </div>

                        <div class="cm-cand-resume-etapes">
                            <div class="cm-cand-etape-resume <?php echo cmCandBadgeModifier($etapeData['scolarite']['validation']); ?>">
                                <div class="cm-cand-etape-details">
                                    <p><strong>Scolarité :</strong>
                                        <span class="cm-cand-badge <?php echo cmCandBadgeModifier($etapeData['scolarite']['validation']); ?>">
                                            <?php echo strtoupper($etapeData['scolarite']['validation']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="cm-cand-etape-resume <?php echo cmCandBadgeModifier($etapeData['stage']['validation']); ?>">
                                <div class="cm-cand-etape-details">
                                    <p><strong>Stage :</strong>
                                        <span class="cm-cand-badge <?php echo cmCandBadgeModifier($etapeData['stage']['validation']); ?>">
                                            <?php echo strtoupper($etapeData['stage']['validation']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="cm-cand-etape-resume <?php echo cmCandBadgeModifier($etapeData['semestre']['validation']); ?>">
                                <div class="cm-cand-etape-details">
                                    <p><strong>Semestre :</strong>
                                        <span class="cm-cand-badge <?php echo cmCandBadgeModifier($etapeData['semestre']['validation']); ?>">
                                            <?php echo strtoupper($etapeData['semestre']['validation']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($_GET['email_envoye']) && $_GET['email_envoye'] == '1'): ?>
                            <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => 'Email envoyé avec succès !']); ?>
                        <?php else: ?>
                            <?php cm_component('ui/alert-box', ['type' => 'info', 'message' => 'Cliquez sur "Envoyer les résultats" pour notifier l\'étudiant.']); ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Contenu normal des étapes -->
                    <?php if ($etape == 1): ?>
                        <div class="cm-cand-info-item"><strong>Statut des paiements:</strong> <span><?php echo htmlspecialchars($etapeData['status']); ?></span></div>
                        <div class="cm-cand-info-item"><strong>Montant total:</strong> <span><?php echo htmlspecialchars($etapeData['montant']); ?></span></div>
                        <div class="cm-cand-info-item"><strong>Montant payé:</strong> <span><?php echo htmlspecialchars($etapeData['montant_paye']); ?></span></div>
                        <div class="cm-cand-info-item"><strong>Dernier paiement:</strong> <span><?php echo htmlspecialchars($etapeData['dernierPaiement']); ?></span></div>
                    <?php elseif ($etape == 2): ?>
                        <div class="cm-cand-info-item"><strong>Entreprise :</strong> <span><?php echo htmlspecialchars($etapeData['entreprise']); ?></span></div>
                        <div class="cm-cand-info-item"><strong>Sujet :</strong> <span><?php echo htmlspecialchars($etapeData['sujet']); ?></span></div>
                        <div class="cm-cand-info-item"><strong>Période :</strong> <span><?php echo htmlspecialchars($etapeData['periode']); ?></span></div>
                        <div class="cm-cand-info-item"><strong>Encadrant :</strong> <span><?php echo htmlspecialchars($etapeData['encadrant']); ?></span></div>
                    <?php elseif ($etape == 3): ?>
                        <div class="cm-cand-info-item"><strong>Semestre actuel:</strong> <span><?php echo htmlspecialchars($etapeData['semestre']); ?></span></div>
                        <div class="cm-cand-info-item"><strong>Moyenne générale:</strong> <span><?php echo htmlspecialchars($etapeData['moyenne']); ?></span></div>
                        <div class="cm-cand-info-item"><strong>Unités validées:</strong> <span><?php echo htmlspecialchars($etapeData['unites']); ?></span></div>
                        <?php if (empty($etapeData['moyenne']) || $etapeData['moyenne'] == '0'): ?>
                            <?php cm_component('ui/alert-box', ['type' => 'warning', 'message' => 'Aucune note trouvée pour cet étudiant.']); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Boutons de navigation et actions -->
    <div class="cm-cand-exam-actions">
        <div class="cm-cand-exam-nav">
            <?php if ($etape > 1 && $etape < 4): ?>
                <a href="?page=gestion_candidatures_soutenance&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape - 1; ?>" class="cm-btn is-light">Précédent</a>
            <?php endif; ?>
            <?php if ($etape < 4): ?>
                <a href="?page=gestion_candidatures_soutenance&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape + 1; ?>" class="cm-btn is-primary">Suivant</a>
            <?php endif; ?>
        </div>
        <div class="cm-cand-exam-buttons">
            <?php if ($etape < 4): ?>
                <form method="post" action="?page=gestion_candidatures_soutenance&action=rejeter_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>" class="cm-cand-inline-form">
                    <input type="hidden" name="etape" value="<?php echo $etape; ?>">
                    <button type="submit" class="cm-cand-btn-reject">Rejeter</button>
                </form>
                <form method="post" action="?page=gestion_candidatures_soutenance&action=valider_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>" class="cm-cand-inline-form">
                    <input type="hidden" name="etape" value="<?php echo $etape; ?>">
                    <button type="submit" class="cm-cand-btn-validate"><?php echo $etape == 3 ? "Terminer l'évaluation" : 'Valider'; ?></button>
                </form>
            <?php endif; ?>
            <?php if ($etape == 4): ?>
                <form method="post" action="?page=gestion_candidatures_soutenance&action=envoyer_resultats&examiner=<?php echo $examiner; ?>" class="cm-cand-inline-form">
                    <button type="submit" class="cm-cand-btn-validate"><i class="fas fa-envelope"></i> Envoyer les résultats</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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

<script>
    // Fonction de recherche
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.cm-cand-item');

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Recherche dynamique sur la table d'historique
    const searchHistoriqueInput = document.getElementById('searchHistoriqueInput');
    if (searchHistoriqueInput) {
        searchHistoriqueInput.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#historiqueCandidaturesTable tbody tr');
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }

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

