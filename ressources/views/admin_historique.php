<?php
$currentTab = (string) ($GLOBALS['currentTab'] ?? ($_GET['tab'] ?? 'vue_ensemble'));
$validTabs = ['vue_ensemble', 'students', 'jury', 'stats'];
if (!in_array($currentTab, $validTabs, true)) {
    $currentTab = 'vue_ensemble';
}

$students = is_array($GLOBALS['students'] ?? null) ? $GLOBALS['students'] : [];
$juries = is_array($GLOBALS['juries'] ?? null) ? $GLOBALS['juries'] : [];
$academicYears = is_array($GLOBALS['academicYears'] ?? null) ? $GLOBALS['academicYears'] : [];
$filters = is_array($GLOBALS['filters'] ?? null) ? $GLOBALS['filters'] : [];
$totalPages = max(1, (int) ($GLOBALS['totalPages'] ?? 1));
$currentPage = max(1, (int) ($GLOBALS['currentPage'] ?? 1));

// New data for vue_ensemble
$quick_stats = is_array($GLOBALS['quick_stats'] ?? null) ? $GLOBALS['quick_stats'] : [];
$timeline = is_array($GLOBALS['timeline'] ?? null) ? $GLOBALS['timeline'] : [];
$derniers_etudiants = is_array($GLOBALS['derniers_etudiants'] ?? null) ? $GLOBALS['derniers_etudiants'] : [];

$globalStats = is_array($GLOBALS['globalStats'] ?? null) ? $GLOBALS['globalStats'] : [];
$yearlyEvolution = is_array($GLOBALS['yearlyEvolution'] ?? null) ? $GLOBALS['yearlyEvolution'] : [];
$mentionsDistribution = is_array($GLOBALS['mentionsDistribution'] ?? null) ? $GLOBALS['mentionsDistribution'] : [];
$topEntreprises = is_array($GLOBALS['topEntreprises'] ?? null) ? $GLOBALS['topEntreprises'] : [];

$messageSuccess = (string) ($GLOBALS['messageSuccess'] ?? '');
$messageErreur = (string) ($GLOBALS['messageErreur'] ?? '');

$studentsPager = cm_paginate(max(count($students), $totalPages * 20), 20, $currentPage);
$studentsPager['last'] = $totalPages;
$studentsPagerBase = '?page=admin_historique&tab=students&' . http_build_query(array_filter([
    'annee' => $filters['annee'] ?? '',
    'statut' => $filters['statut'] ?? '',
    'search' => $filters['search'] ?? '',
], static function ($value) { return $value !== ''; }));
?>

<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <div class="cm-screen-header">
        <div class="cm-screen-header__title">
            <i class="fas fa-history" aria-hidden="true"></i>
            <h1>Historique et Archivage</h1>
        </div>
    </div>

    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <div class="cm-tab-container">
        <div class="cm-tab-links" role="tablist">
            <a class="cm-tab-link <?= $currentTab === 'vue_ensemble' ? 'is-active' : '' ?>" href="?page=admin_historique&tab=vue_ensemble&annee=<?= urlencode($filters['annee'] ?? '') ?>">
                <i class="fas fa-th-large"></i> Vue d'ensemble
            </a>
            <a class="cm-tab-link <?= $currentTab === 'students' ? 'is-active' : '' ?>" href="?page=admin_historique&tab=students&annee=<?= urlencode($filters['annee'] ?? '') ?>">
                <i class="fas fa-user-graduate"></i> Étudiants
            </a>
            <a class="cm-tab-link <?= $currentTab === 'jury' ? 'is-active' : '' ?>" href="?page=admin_historique&tab=jury&annee=<?= urlencode($filters['annee'] ?? '') ?>">
                <i class="fas fa-users"></i> Jurys
            </a>
            <a class="cm-tab-link <?= $currentTab === 'stats' ? 'is-active' : '' ?>" href="?page=admin_historique&tab=stats&annee=<?= urlencode($filters['annee'] ?? '') ?>">
                <i class="fas fa-chart-pie"></i> Statistiques
            </a>
        </div>

        <div class="cm-tab-content">
            <?php if ($currentTab === 'vue_ensemble'): ?>
                <!-- QUICK STATS -->
                <div class="cm-grid-4 cm-mb-6">
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Étudiants',
                        'value' => $quick_stats['total_etudiants'] ?? 0,
                        'icon' => 'fa-user-graduate',
                        'color' => 'primary',
                    ]); ?>
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Taux de réussite',
                        'value' => ($quick_stats['taux_reussite'] ?? 0) . '%',
                        'icon' => 'fa-check-circle',
                        'color' => 'success',
                    ]); ?>
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Moyenne générale',
                        'value' => $quick_stats['moyenne_generale'] ?? '0.00',
                        'icon' => 'fa-star',
                        'color' => 'warning',
                    ]); ?>
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Jours de soutenance',
                        'value' => $quick_stats['jours_soutenance'] ?? 0,
                        'icon' => 'fa-calendar-alt',
                        'color' => 'info',
                    ]); ?>
                </div>

                <div class="cm-grid-2">
                    <!-- TIMELINE -->
                    <div class="cm-card">
                        <div class="cm-card-header">
                            <h3 class="cm-card-title"><i class="fas fa-stream"></i> Déroulement de l'année</h3>
                        </div>
                        <div class="cm-card-body">
                            <?php if (empty($timeline)): ?>
                                <p class="cm-text-muted">Aucun événement enregistré pour cette période.</p>
                            <?php else: ?>
                                <div class="cm-timeline">
                                    <?php foreach ($timeline as $event): ?>
                                        <div class="cm-timeline-item">
                                            <div class="cm-timeline-date"><?= date('d/m/Y', strtotime($event['date'])) ?></div>
                                            <div class="cm-timeline-content">
                                                <strong><?= htmlspecialchars($event['event']) ?></strong>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- RECENT STUDENTS -->
                    <div class="cm-card">
                        <div class="cm-card-header">
                            <h3 class="cm-card-title"><i class="fas fa-user-clock"></i> Derniers dossiers</h3>
                        </div>
                        <div class="cm-card-body">
                            <div class="cm-table-responsive">
                                <table class="cm-data-table is-compact">
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Statut</th>
                                            <th class="is-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($derniers_etudiants as $student): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($student['nom'] . ' ' . $student['prenoms']) ?></strong><br>
                                                    <small class="cm-text-muted"><?= htmlspecialchars($student['matricule']) ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $st = $student['statut'] ?? 'en_cours';
                                                    $type = ($st === 'valider') ? 'success' : (($st === 'rejeter') ? 'danger' : 'info');
                                                    $lbl = ($st === 'valider') ? 'Admis' : (($st === 'rejeter') ? 'Ajourné' : 'En cours');
                                                    cm_component('ui/badge', ['text' => $lbl, 'type' => $type]);
                                                    ?>
                                                </td>
                                                <td class="is-center">
                                                    <a href="?page=admin_historique&action=view_student&num_etu=<?= urlencode($student['matricule']) ?>" class="cm-btn-icon">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="cm-mt-4 is-right">
                                <a href="?page=admin_historique&tab=students" class="cm-link">Voir tous les étudiants <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($currentTab === 'students'): ?>
                <!-- STUDENTS TAB -->
                <div class="cm-toolbar cm-mb-4">
                    <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
.cm-content-area form .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form method="GET" class="cm-toolbar__search">
                        <input type="hidden" name="page" value="admin_historique">
                        <input type="hidden" name="tab" value="students">
                        <input type="hidden" name="annee" value="<?= htmlspecialchars($filters['annee'] ?? '') ?>">
                        
                        <div class="cm-inline-stack">
                            <select name="statut" class="cm-form-control">
                                <option value="">Tous les statuts</option>
                                <option value="valider" <?= ($filters['statut'] ?? '') === 'valider' ? 'selected' : '' ?>>Admis</option>
                                <option value="rejeter" <?= ($filters['statut'] ?? '') === 'rejeter' ? 'selected' : '' ?>>Ajourné</option>
                                <option value="en_cours" <?= ($filters['statut'] ?? '') === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                            </select>
                            <input type="search" name="search" class="cm-form-control" placeholder="Nom, matricule..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                            <button type="submit" class="cm-btn is-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>

                <div class="cm-card">
                    <div class="cm-table-responsive">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom & Prénoms</th>
                                    <th>Thème / Sujet</th>
                                    <th>Entreprise</th>
                                    <th>Statut</th>
                                    <th class="is-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="6" class="is-center cm-py-8 cm-text-muted">Aucun étudiant trouvé.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($student['matricule'] ?? '-') ?></code></td>
                                            <td><strong><?= htmlspecialchars(($student['nom'] ?? '') . ' ' . ($student['prenoms'] ?? '')) ?></strong></td>
                                            <td><div class="cm-text-truncate" title="<?= htmlspecialchars($student['theme'] ?? '') ?>"><?= htmlspecialchars($student['theme'] ?? '-') ?></div></td>
                                            <td><?= htmlspecialchars($student['entreprise'] ?? '-') ?></td>
                                            <td>
                                                <?php
                                                $st = $student['statut'] ?? 'en_cours';
                                                $type = ($st === 'valider') ? 'success' : (($st === 'rejeter') ? 'danger' : 'info');
                                                $lbl = ($st === 'valider') ? 'Admis' : (($st === 'rejeter') ? 'Ajourné' : 'En cours');
                                                cm_component('ui/badge', ['text' => $lbl, 'type' => $type]);
                                                ?>
                                            </td>
                                            <td class="is-center">
                                                <a href="?page=admin_historique&action=view_student&num_etu=<?= urlencode($student['matricule'] ?? '') ?>" class="cm-btn-action is-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="cm-mt-4">
                        <?php cm_component('crud/pagination', [
                            'pagination' => $studentsPager,
                            'base_url' => $studentsPagerBase,
                            'param_name' => 'p',
                        ]); ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($currentTab === 'jury'): ?>
                <!-- JURY TAB -->
                <div class="cm-card">
                    <div class="cm-table-responsive">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Étudiant</th>
                                    <th>Président</th>
                                    <th>Encadreur</th>
                                    <th>Examinateur</th>
                                    <th>Directeur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($juries)): ?>
                                    <tr><td colspan="6" class="is-center cm-py-8 cm-text-muted">Aucun jury trouvé pour cette période.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($juries as $jury): ?>
                                        <tr>
                                            <td><?= !empty($jury['date_soutenance']) ? date('d/m/Y', strtotime($jury['date_soutenance'])) : '-' ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($jury['etudiant_nom'] ?? '-') ?></strong><br>
                                                <small><?= htmlspecialchars($jury['etudiant_matricule'] ?? '-') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($jury['president'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($jury['encadreur'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($jury['examinateur'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($jury['directeur'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($currentTab === 'stats'): ?>
                <!-- STATS TAB -->
                <div class="cm-grid-4 cm-mb-6">
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Total Étudiants',
                        'value' => $globalStats['total_students'] ?? 0,
                        'icon' => 'fa-user-graduate',
                        'color' => 'info',
                    ]); ?>
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Soutenances',
                        'value' => $globalStats['total_soutenances'] ?? 0,
                        'icon' => 'fa-chalkboard-teacher',
                        'color' => 'success',
                    ]); ?>
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Entreprises partenaires',
                        'value' => $globalStats['total_entreprises'] ?? 0,
                        'icon' => 'fa-building',
                        'color' => 'warning',
                    ]); ?>
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Encadreurs',
                        'value' => $globalStats['total_encadreurs'] ?? 0,
                        'icon' => 'fa-users',
                        'color' => 'danger',
                    ]); ?>
                </div>

                <div class="cm-grid-2">
                    <div class="cm-card">
                        <div class="cm-card-header"><h3 class="cm-card-title">Évolution annuelle</h3></div>
                        <div class="cm-table-responsive">
                            <table class="cm-data-table is-compact">
                                <thead>
                                    <tr>
                                        <th>Année</th>
                                        <th class="is-right">Inscrits</th>
                                        <th class="is-right">Admis</th>
                                        <th class="is-right">Taux %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($yearlyEvolution as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['annee']) ?></td>
                                            <td class="is-right"><?= $row['inscrits'] ?></td>
                                            <td class="is-right"><?= $row['admis'] ?></td>
                                            <td class="is-right"><strong><?= $row['taux'] ?>%</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="cm-card">
                        <div class="cm-card-header"><h3 class="cm-card-title">Top Entreprises</h3></div>
                        <div class="cm-table-responsive">
                            <table class="cm-data-table is-compact">
                                <thead>
                                    <tr>
                                        <th>Entreprise</th>
                                        <th class="is-right">Stages</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topEntreprises as $ent): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ent['lib_court_en'] ?: $ent['lib_long_entreprise']) ?></td>
                                            <td class="is-right"><strong><?= $ent['total'] ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.cm-tab-container { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; margin-top: 1rem; }
.cm-tab-links { display: flex; background: #f8f9fa; border-bottom: 1px solid #eee; padding: 0 1rem; }
.cm-tab-link { padding: 1rem 1.5rem; color: #666; text-decoration: none; border-bottom: 3px solid transparent; transition: all 0.2s; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
.cm-tab-link:hover { color: var(--cm-primary); background: rgba(0,0,0,0.02); }
.cm-tab-link.is-active { color: var(--cm-primary); border-bottom-color: var(--cm-primary); background: #fff; }
.cm-tab-content { padding: 2rem; }
.cm-timeline { position: relative; padding-left: 2rem; border-left: 2px solid #eee; margin-left: 1rem; }
.cm-timeline-item { position: relative; margin-bottom: 1.5rem; }
.cm-timeline-item::before { content: ''; position: absolute; left: -2.45rem; top: 0.25rem; width: 12px; height: 12px; border-radius: 50%; background: var(--cm-primary); border: 2px solid #fff; }
.cm-timeline-date { font-size: 0.85rem; color: #999; margin-bottom: 0.25rem; }
.cm-screen-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.cm-screen-header__title { display: flex; align-items: center; gap: 1rem; }
.cm-screen-header__title h1 { margin: 0; font-size: 1.5rem; }
.cm-screen-header__title i { font-size: 1.5rem; color: var(--cm-primary); }
.cm-mb-6 { margin-bottom: 1.5rem; }
.cm-mb-4 { margin-bottom: 1rem; }
.cm-py-8 { padding-top: 2rem; padding-bottom: 2rem; }
.is-right { text-align: right; }
</style>

