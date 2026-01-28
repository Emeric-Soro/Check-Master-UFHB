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
?>

<div class="container">
    <div class="card">
        <div class="page-header">
            <h1>Gestion des Candidatures de Soutenance</h1>
        </div>

        <!-- Message d'erreur pour les étudiants sans informations de stage -->
        <?php if (isset($_GET['error']) && $_GET['error'] === 'no_stage_info'): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                <strong>Erreur :</strong> Cet étudiant n'a pas rempli ses informations de stage. Il ne peut pas être
                examiné tant qu'il n'a pas complété cette étape.
            </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="filter-bar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" class="input" style="outline: none;" placeholder="Rechercher un étudiant...">
            </div>
            <form method="get" id="filterForm" style="margin:0;">
                <input type="hidden" name="page" value="gestion_candidatures_soutenance">
                <select name="statut" id="statusFilter" class="select" style="outline: none;"
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
        <div class="candidate-list" style="display: grid; gap: 1rem; margin-bottom: 2rem;">
            <?php if (empty($candidatures)): ?>
                <div class="empty-state">
                    Aucune candidature à afficher
                </div>
            <?php else: ?>
                <?php foreach ($candidatures as $candidature): ?>
                    <div class="candidate-item card" data-status="<?php echo $candidature['statut_candidature']; ?>" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding: 1rem;">
                        <div class="candidate-info">
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem; font-weight: 600;"><?php echo htmlspecialchars($candidature['nom_etu'] . ' ' . $candidature['prenom_etu']); ?>
                            </h3>
                            <p style="margin: 0.25rem 0; font-size: 0.875rem;">Numéro étudiant: <?php echo htmlspecialchars($candidature['num_etu']); ?></p>
                            <p style="margin: 0.25rem 0; font-size: 0.875rem;">Date de demande: <?php echo date('d/m/Y', strtotime($candidature['date_candidature'])); ?>
                            </p>
                            <p style="margin: 0.25rem 0; font-size: 0.875rem;">Statut: <span class="badge badge-<?php 
                                $status_map = [
                                    'En attente' => 'warning',
                                    'Validée' => 'success',
                                    'Rejetée' => 'danger'
                                ];
                                echo $status_map[$candidature['statut_candidature']] ?? 'info'; 
                            ?>">
                                    <?php echo ucfirst($candidature['statut_candidature']); ?>
                                </span></p>
                        </div>
                        <?php if ($candidature['statut_candidature'] === 'En attente'): ?>
                            <?php if (canEdit()): ?>
                            <button class="btn btn-primary"
                                onclick="window.location.href='?page=gestion_candidatures_soutenance&examiner=<?php echo $candidature['num_etu']; ?>&etape=1'">
                                Examiner
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Table d'historique des candidatures examinées -->
        <div class="card">
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <h2 class="card-title">Historique des candidatures examinées</h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="searchHistoriqueInput" class="input" placeholder="Rechercher dans l'historique..."
                        style="padding: 8px 12px;">
                    <button class="btn btn-secondary" onclick="printHistoriqueTable()"><i class="fas fa-print"></i>
                        Imprimer</button>
                    <button class="btn btn-primary" onclick="exportHistoriqueCSV()"><i class="fas fa-file-csv"></i>
                        Exporter</button>
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
                                    <td><?php echo htmlspecialchars($candidature['nom_etu'] . ' ' . $candidature['prenom_etu']); ?>
                                    </td>
                                    <td><span
                                            class="badge badge-<?php 
                                                $status_map = [
                                                    'validée' => 'success',
                                                    'validee' => 'success',
                                                    'rejetée' => 'danger',
                                                    'rejetee' => 'danger'
                                                ];
                                                echo $status_map[strtolower($candidature['statut_candidature'])] ?? 'warning'; 
                                            ?>"><?php echo ucfirst($candidature['statut_candidature']); ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($candidature['date_traitement'] ?? $candidature['date_candidature'])); ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-details"
                                            data-idcandidature="<?php echo $candidature['id_candidature']; ?>">Voir
                                            détails</button>
                                        <div id="resume-candidature-<?php echo $candidature['id_candidature']; ?>"
                                            style="display:none;">
                                            <?php if (!empty($resumes_candidatures[$candidature['id_candidature']])):
                                                $resume = $resumes_candidatures[$candidature['id_candidature']]; ?>
                                                <div class="resume-final">
                                                    <div
                                                        class="decision-finale card <?php echo $resume['decision'] === 'Validée' ? 'validee' : 'rejetee'; ?>" style="padding: 1.5rem; margin-bottom: 1.5rem; text-align: center; border-left: 4px solid <?php echo $resume['decision'] === 'Validée' ? 'var(--success)' : 'var(--danger)'; ?>;">
                                                        <h4 style="margin: 0 0 0.75rem 0; font-size: 1.25rem;">Décision finale : <?php echo $resume['decision']; ?></h4>
                                                        <p style="margin: 0.5rem 0;">Date de traitement : <?php echo $resume['date_enregistrement']; ?></p>
                                                        <?php if ($resume['decision'] === 'Validée'): ?>
                                                            <p style="margin: 0.5rem 0;">🎉 Félicitations ! Candidature validée.</p>
                                                        <?php else: ?>
                                                            <p style="margin: 0.5rem 0;">❌ Candidature rejetée. Voir détails ci-dessous.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="resume-etapes">
                                                        <h4 style="margin-bottom: 1rem; border-bottom: 2px solid var(--border); padding-bottom: 0.5rem;">Détail par étape :</h4>
                                                        <?php $r = $resume['resume_json']; ?>
                                                        <?php if (!empty($r['scolarite'])): ?>
                                                            <div class="etape-resume card <?php echo $r['scolarite']['validation']; ?>" style="margin-bottom: 1rem; padding: 1rem; border-left: 4px solid <?php echo $r['scolarite']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)'; ?>;">
                                                                <h5 style="margin: 0 0 0.5rem 0;"><i class='fas fa-money-check'></i> Scolarité</h5>
                                                                <p style="margin: 0.25rem 0;"><strong>Validation :</strong> <span
                                                                        class="badge badge-<?php echo $r['scolarite']['validation'] === 'validé' ? 'success' : 'danger'; ?>"><?php echo strtoupper($r['scolarite']['validation']); ?></span>
                                                                </p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($r['stage'])): ?>
                                                            <div class="etape-resume card <?php echo $r['stage']['validation']; ?>" style="margin-bottom: 1rem; padding: 1rem; border-left: 4px solid <?php echo $r['stage']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)'; ?>;">
                                                                <h5 style="margin: 0 0 0.5rem 0;"><i class='fas fa-briefcase'></i> Stage</h5>
                                                                <p style="margin: 0.25rem 0;"><strong>Validation :</strong> <span
                                                                        class="badge badge-<?php echo $r['stage']['validation'] === 'validé' ? 'success' : 'danger'; ?>"><?php echo strtoupper($r['stage']['validation']); ?></span>
                                                                </p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($r['semestre'])): ?>
                                                            <div class="etape-resume card <?php echo $r['semestre']['validation']; ?>" style="margin-bottom: 1rem; padding: 1rem; border-left: 4px solid <?php echo $r['semestre']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)'; ?>;">
                                                                <h5 style="margin: 0 0 0.5rem 0;"><i class='fas fa-graduation-cap'></i> Semestre</h5>
                                                                <p style="margin: 0.25rem 0;"><strong>Validation :</strong> <span
                                                                        class="badge badge-<?php echo $r['semestre']['validation'] === 'validé' ? 'success' : 'danger'; ?>"><?php echo strtoupper($r['semestre']['validation']); ?></span>
                                                                </p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <p>Aucun résumé trouvé pour cet étudiant.</p>
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
        <div id="historiqueDetailsModal" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:700px; max-height:80vh; overflow-y:auto;">
                <span class="close-button" onclick="closeHistoriqueModal()">&times;</span>
                <h2 class="modal-title">Résumé de l'examen de candidature</h2>
                <div id="historiqueDetailsContent">
                    <p>Chargement...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'examen -->
<?php if ($examiner && $etudiantData): ?>
    <div id="examinationModal" class="modal" style="display: flex;">
    <?php else: ?>
        <div id="examinationModal" class="modal" style="display: none;">
        <?php endif; ?>
        <div class="modal-content<?php echo ($etape == 4 ? ' resume-step' : ''); ?>">
            <a href="?page=gestion_candidatures_soutenance" class="close-button">&times;</a>
            <h2 class="modal-title">Examen de la Candidature -
                <?php echo htmlspecialchars($etudiantData['nom_etu'] . ' ' . $etudiantData['prenom_etu']); ?>
            </h2>
            <!--partie des step-icon-->
            <div class="step-indicator" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; position: relative;">
                <div class="step <?php echo isset($_SESSION['etapes_validation'][$examiner][1]) ? $_SESSION['etapes_validation'][$examiner][1] : ($etape == 1 ? 'active' : ''); ?>"
                    id="stepScolarite" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-size: 1.125rem;">
                        <i class="fas fa-money-check"></i>
                    </div>
                    <div class="step-label" style="font-size: 0.75rem;">Scolarité</div>
                </div>
                <div class="step <?php echo isset($_SESSION['etapes_validation'][$examiner][2]) ? $_SESSION['etapes_validation'][$examiner][2] : ($etape == 2 ? 'active' : ''); ?>"
                    id="stepStage" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-size: 1.125rem;">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="step-label" style="font-size: 0.75rem;">Stage</div>
                </div>
                <div class="step <?php echo isset($_SESSION['etapes_validation'][$examiner][3]) ? $_SESSION['etapes_validation'][$examiner][3] : ($etape == 3 ? 'active' : ''); ?>"
                    id="stepSemestre" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-size: 1.125rem;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="step-label" style="font-size: 0.75rem;">Semestre</div>
                </div>
                <div class="step <?php echo $etape == 4 ? 'active' : ''; ?>" id="stepResume" style="flex: 1; text-align: center; position: relative;">
                    <div class="step-icon" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-size: 1.125rem;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="step-label" style="font-size: 0.75rem;">Résumé</div>
                </div>
            </div>

            <?php if ($etapeData): ?>
                <div class="step-content active
                <?php
                if ($etape == 1)
                    echo 'active-scolarite';
                elseif ($etape == 2)
                    echo 'active-stage';
                elseif ($etape == 3)
                    echo 'active-semestre';
                elseif ($etape == 4)
                    echo 'active-resume';
                ?>" style="<?php
                    if ($etape == 1)
                        echo 'background: #f3e8ff;';
                    elseif ($etape == 2)
                        echo 'background: #e0f2fe;';
                    elseif ($etape == 3)
                        echo 'background: #fef9c3;';
                    elseif ($etape == 4)
                        echo 'background: #dcfce7;';
                    ?> padding: 1rem; border-radius: 0.5rem;">
                    <div class="info-section" style="margin-bottom: 1.5rem; padding: 1rem; background: #F9FAFB; border-radius: 0.5rem;">
                        <?php if ($etape == 4): ?>
                            <!-- Résumé final -->
                            <h3 style="font-size: 1.125rem; margin: 0 0 1rem 0; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Résumé de l'évaluation</h3>
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
                                <div class="decision-finale card <?php echo $decision === 'Validée' ? 'validee' : 'rejetee'; ?>" style="padding: 1.5rem; margin-bottom: 1.5rem; text-align: center; border-left: 4px solid <?php echo $decision === 'Validée' ? 'var(--success)' : 'var(--danger)'; ?>;">
                                    <h4 style="margin: 0 0 0.75rem 0; font-size: 1.25rem;">Décision finale : <?php echo $decision; ?></h4>
                                    <?php if ($decision === 'Validée'): ?>
                                        <p style="margin: 0.5rem 0;">🎉 Félicitations ! Votre candidature a été validée. Vous pouvez procéder à votre
                                            soutenance.</p>
                                    <?php else: ?>
                                        <p style="margin: 0.5rem 0;">❌ Votre candidature a été rejetée. Veuillez corriger les problèmes identifiés
                                            ci-dessous.</p>
                                    <?php endif; ?>
                                </div>

                                <div class="resume-etapes">
                                    <h4 style="margin-bottom: 1rem; border-bottom: 2px solid var(--border); padding-bottom: 0.5rem;">Détail par étape :</h4>

                                    <!-- Scolarité -->
                                    <div
                                        class="etape-resume card <?php echo $etapeData['scolarite']['validation']; ?>" style="margin-bottom: 1rem; padding: 1rem; border-left: 4px solid <?php echo $etapeData['scolarite']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)'; ?>; display: flex; gap: 0.5rem; align-items: center;">
                                        <div class="etape-details" style="display: flex; gap: 0.5rem; align-items: center;">
                                            <h5 style="margin: 0;"><i class="fas fa-money-check"></i> Scolarité</h5>
                                            <p style="margin: 0;"><strong>Validation :</strong>
                                                <span class="badge badge-<?php echo $etapeData['scolarite']['validation'] === 'validé' ? 'success' : 'danger'; ?>">
                                                    <?php echo strtoupper($etapeData['scolarite']['validation']); ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Stage -->
                                    <div
                                        class="etape-resume card <?php echo $etapeData['stage']['validation']; ?>" style="margin-bottom: 1rem; padding: 1rem; border-left: 4px solid <?php echo $etapeData['stage']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)'; ?>; display: flex; gap: 0.5rem; align-items: center;">
                                        <div class="etape-details" style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                                            <h5 style="margin: 0;"><i class="fas fa-briefcase"></i> Stage</h5>
                                            <p style="margin: 0;"><strong>Validation :</strong>
                                                <span class="badge badge-<?php echo $etapeData['stage']['validation'] === 'validé' ? 'success' : 'danger'; ?>">
                                                    <?php echo strtoupper($etapeData['stage']['validation']); ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Semestre -->
                                    <div
                                        class="etape-resume card <?php echo $etapeData['semestre']['validation']; ?>" style="margin-bottom: 1rem; padding: 1rem; border-left: 4px solid <?php echo $etapeData['semestre']['validation'] === 'validé' ? 'var(--success)' : 'var(--danger)'; ?>; display: flex; gap: 0.5rem; align-items: center;">

                                        <div class="etape-details" style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                                            <h5 style="margin: 0;"><i class="fas fa-graduation-cap"></i> Semestre</h5>
                                            <p style="margin: 0;"><strong>Validation :</strong>
                                                <span class="badge badge-<?php echo $etapeData['semestre']['validation'] === 'validé' ? 'success' : 'danger'; ?>">
                                                    <?php echo strtoupper($etapeData['semestre']['validation']); ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                </div>
                                <?php if (isset($_GET['email_envoye']) && $_GET['email_envoye'] == '1'): ?>
                                    <div class="alert alert-success" style="margin-top: 1.5rem; padding: 1rem; border-radius: 0.5rem;">
                                        <p style="margin: 0;"><i class="fas fa-check-circle"></i> <strong>Email envoyé avec succès !</strong> Les
                                            résultats ont été envoyés à l'étudiant.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info" style="margin-top: 1.5rem; padding: 1rem; border-radius: 0.5rem;">
                                        <p style="margin: 0;"><i class="fas fa-envelope"></i> Cliquez sur "Envoyer les résultats" pour notifier
                                            l'étudiant de la décision finale.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Contenu normal des étapes -->
                            <h3 style="font-size: 1.125rem; margin: 0 0 1rem 0; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                                <?php
                                switch ($etape) {
                                    case 1:
                                        echo 'Vérification de la scolarité';
                                        break;
                                    case 2:
                                        echo 'Vérification du stage';
                                        break;
                                    case 3:
                                        echo 'Vérification du semestre';
                                        break;
                                }
                                ?>
                            </h3>

                            <?php if ($etape == 1): ?>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Statut des paiements:</strong>
                                    <span><?php echo htmlspecialchars($etapeData['status']); ?></span>
                                </div>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Montant total:</strong>
                                    <span><?php echo htmlspecialchars($etapeData['montant']); ?></span>
                                </div>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Montant payé:</strong>
                                    <span><?php echo htmlspecialchars($etapeData['montant_paye']); ?></span>
                                </div>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Dernier paiement:</strong>
                                    <span><?php echo htmlspecialchars($etapeData['dernierPaiement']); ?></span>
                                </div>
                            <?php elseif ($etape == 2): ?>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Entreprise :</strong>
                                    <span><?php echo htmlspecialchars($etapeData['entreprise']); ?></span>
                                </div>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Sujet :</strong>
                                    <span><?php echo htmlspecialchars($etapeData['sujet']); ?></span>
                                </div>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Période :</strong>
                                    <span><?php echo htmlspecialchars($etapeData['periode']); ?></span>
                                </div>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Encadrant :</strong>
                                    <span><?php echo htmlspecialchars($etapeData['encadrant']); ?></span>
                                </div>
                            <?php elseif ($etape == 3): ?>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Semestre actuel:</strong>
                                    <span><?php echo htmlspecialchars($etapeData['semestre']); ?></span>
                                </div>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Moyenne générale:</strong>
                                    <span class="text-success">
                                        <?php echo htmlspecialchars($etapeData['moyenne']); ?>
                                    </span>
                                </div>
                                <div class="info-item" style="margin-bottom: 0.75rem;">
                                    <strong style="display: inline-block; min-width: 180px;">Unités validées:</strong>
                                    <span class="text-success">
                                        <?php echo htmlspecialchars($etapeData['unites']); ?>
                                    </span>
                                </div>
                                <?php if (empty($etapeData['moyenne']) || $etapeData['moyenne'] == '0'): ?>
                                    <div class="alert alert-warning" style="margin-top: 1rem; padding: 0.75rem; border-radius: 0.5rem;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Note :</strong> Aucune note n'a été trouvée pour cet étudiant.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="modal-footer" style="display: flex; justify-content: space-between; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                    <div class="left-buttons" style="display: flex; gap: 0.75rem;">
                        <?php if ($etape > 1 && $etape < 4): ?>
                            <a href="?page=gestion_candidatures_soutenance&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape - 1; ?>"
                                class="btn btn-secondary">
                                Précédent
                            </a>
                        <?php endif; ?>

                        <?php if ($etape < 4): ?>
                            <a href="?page=gestion_candidatures_soutenance&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape + 1; ?>"
                                class="btn btn-primary">
                                Suivant
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="right-buttons" style="display: flex; gap: 0.75rem;">
                        <?php if ($etape < 4): ?>
                            <form method="post"
                                action="?page=gestion_candidatures_soutenance&action=rejeter_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>"
                                style="display: inline;">
                                <input type="hidden" name="etape" value="<?php echo $etape; ?>">
                                <button type="submit" class="btn btn-danger">Rejeter</button>
                            </form>
                            <form method="post"
                                action="?page=gestion_candidatures_soutenance&action=valider_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>"
                                style="display: inline;">
                                <input type="hidden" name="etape" value="<?php echo $etape; ?>">
                                <button type="submit" class="btn btn-success">
                                    <?php echo $etape == 3 ? 'Terminer l\'évaluation' : 'Valider'; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if ($etape == 4): ?>
                            <form method="post"
                                action="?page=gestion_candidatures_soutenance&action=envoyer_resultats&examiner=<?php echo $examiner; ?>"
                                style="display: inline;">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-envelope"></i> Envoyer les résultats
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
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

