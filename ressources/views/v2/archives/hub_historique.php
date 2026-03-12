<?php
/**
 * Hub Historique - Page centrale d'historisation et archivage
 * Onglets: Vue d'ensemble | Étudiants | Jurys | Statistiques | Import
 */
$anneeId = $data['annee_id'] ?? null;
$anneeLibelle = $data['annee_libelle'] ?? '';
$annees = $data['annees'] ?? [];
$activeTab = $data['active_tab'] ?? 'vue_ensemble';
$stats = $data['stats'] ?? [];

// Données onglets spécifiques
$quickStats = $data['quick_stats'] ?? [];
$timeline = $data['timeline'] ?? [];
$derniersEtudiants = $data['derniers_etudiants'] ?? [];

$students = $data['students'] ?? [];
$studentsTotal = $data['students_total'] ?? 0;
$studentsPage = $data['students_page'] ?? 1;
$studentsPerPage = $data['students_per_page'] ?? 10;
$studentsTotalPages = $data['students_total_pages'] ?? 1;
$studentsSearch = $data['students_search'] ?? '';
$studentsStatut = $data['students_statut'] ?? '';

$juries = $data['juries'] ?? [];
$juriesTotal = $data['juries_total'] ?? 0;
$juriesPage = $data['juries_page'] ?? 1;
$juriesPerPage = $data['juries_per_page'] ?? 10;
$juriesTotalPages = $data['juries_total_pages'] ?? 1;

$globalStats = $data['global_stats'] ?? [];
$yearlyEvolution = $data['yearly_evolution'] ?? [];
$mentionsDistribution = $data['mentions_distribution'] ?? [];
$topEntreprises = $data['top_entreprises'] ?? [];

$tabBase = '?page=admin_historique';

$messageSuccess = $_SESSION['success_message'] ?? '';
$messageErreur = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <!-- En-tête avec titre et sélecteur d'année -->
        <?php ob_start(); ?>
        </div>
        <!-- Onglets de navigation -->
        <div class="cm-tab-links" role="tablist" aria-label="Historique et Archivage">
            <a class="cm-btn <?= $activeTab === 'vue_ensemble' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars($tabBase . '&tab=vue_ensemble', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-th-large" aria-hidden="true"></i>
                <span>Vue d'ensemble</span>
            </a>
            <a class="cm-btn <?= $activeTab === 'etudiants' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars($tabBase . '&tab=etudiants', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-user-graduate" aria-hidden="true"></i>
                <span>Étudiants</span>
                <?php if (($stats['etudiants'] ?? 0) > 0): ?>
                    <span class="cm-badge cm-badge-sm cm-badge-info cm-ml-1"><?= number_format($stats['etudiants']) ?></span>
                <?php endif; ?>
            </a>
            <a class="cm-btn <?= $activeTab === 'jurys' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars($tabBase . '&tab=jurys', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-users" aria-hidden="true"></i>
                <span>Jurys</span>
                <?php if (($stats['jurys'] ?? 0) > 0): ?>
                    <span class="cm-badge cm-badge-sm cm-badge-warning cm-ml-1"><?= number_format($stats['jurys']) ?></span>
                <?php endif; ?>
            </a>
            <a class="cm-btn <?= $activeTab === 'statistiques' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars($tabBase . '&tab=statistiques', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-chart-pie" aria-hidden="true"></i>
                <span>Statistiques</span>
            </a>
            <a class="cm-btn <?= $activeTab === 'import' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars($tabBase . '&tab=import', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-file-import" aria-hidden="true"></i>
                <span>Import</span>
            </a>
        </div>

        <!-- Contenu des onglets -->
        <div class="cm-pole-inferieur">

            <?php if ($activeTab === 'vue_ensemble'): ?>
            <!-- =============== ONGLET: VUE D'ENSEMBLE =============== -->

            <!-- Compteurs par catégorie -->
            <div class="cm-grid-3 cm-gap-4 cm-mb-4">
                <div class="cm-card cm-card-hover cm-p-4">
                    <div class="cm-flex cm-items-center cm-gap-3">
                        <div class="cm-icon-box cm-icon-box-primary cm-icon-box-lg">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div>
                            <p class="cm-text-muted cm-mb-0 cm-text-sm">Étudiants inscrits</p>
                            <p class="cm-text-2xl cm-font-bold cm-text-primary cm-mb-0"><?= number_format($stats['etudiants'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="cm-card cm-card-hover cm-p-4">
                    <div class="cm-flex cm-items-center cm-gap-3">
                        <div class="cm-icon-box cm-icon-box-success cm-icon-box-lg">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div>
                            <p class="cm-text-muted cm-mb-0 cm-text-sm">Soutenances</p>
                            <p class="cm-text-2xl cm-font-bold cm-text-success cm-mb-0"><?= number_format($stats['soutenances'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="cm-card cm-card-hover cm-p-4">
                    <div class="cm-flex cm-items-center cm-gap-3">
                        <div class="cm-icon-box cm-icon-box-warning cm-icon-box-lg">
                            <i class="fas fa-gavel"></i>
                        </div>
                        <div>
                            <p class="cm-text-muted cm-mb-0 cm-text-sm">Membres de jury</p>
                            <p class="cm-text-2xl cm-font-bold cm-text-warning cm-mb-0"><?= number_format($stats['jurys'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="cm-card cm-card-hover cm-p-4">
                    <div class="cm-flex cm-items-center cm-gap-3">
                        <div class="cm-icon-box cm-icon-box-info cm-icon-box-lg">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <p class="cm-text-muted cm-mb-0 cm-text-sm">Documents / Rapports</p>
                            <p class="cm-text-2xl cm-font-bold cm-text-info cm-mb-0"><?= number_format($stats['documents'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="cm-card cm-card-hover cm-p-4">
                    <div class="cm-flex cm-items-center cm-gap-3">
                        <div class="cm-icon-box cm-icon-box-secondary cm-icon-box-lg">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div>
                            <p class="cm-text-muted cm-mb-0 cm-text-sm">Candidatures</p>
                            <p class="cm-text-2xl cm-font-bold cm-text-secondary cm-mb-0"><?= number_format($stats['candidatures'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="cm-card cm-card-hover cm-p-4">
                    <div class="cm-flex cm-items-center cm-gap-3">
                        <div class="cm-icon-box cm-icon-box-danger cm-icon-box-lg">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div>
                            <p class="cm-text-muted cm-mb-0 cm-text-sm">Réclamations</p>
                            <p class="cm-text-2xl cm-font-bold cm-text-danger cm-mb-0"><?= number_format($stats['reclamations'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Indicateurs clés -->
            <div class="cm-card cm-mb-4">
                <div class="cm-card-header">
                    <h3 class="cm-card-title"><i class="fas fa-tachometer-alt cm-mr-2"></i>Indicateurs clés</h3>
                </div>
                <div class="cm-card-body">
                    <div class="cm-grid-4 cm-gap-4">
                        <?php cm_component('dashboard/stat-widget', [
                            'label' => 'Taux de réussite',
                            'value' => ($quickStats['taux_reussite'] ?? 0) . '%',
                            'icon' => 'fa-trophy',
                            'color' => 'success',
                        ]); ?>
                        <?php cm_component('dashboard/stat-widget', [
                            'label' => 'Moyenne générale',
                            'value' => ($quickStats['moyenne_generale'] ?? 0) . '/20',
                            'icon' => 'fa-chart-line',
                            'color' => 'primary',
                        ]); ?>
                        <?php cm_component('dashboard/stat-widget', [
                            'label' => 'Jours de soutenance',
                            'value' => number_format($quickStats['jours_soutenance'] ?? 0),
                            'icon' => 'fa-calendar-check',
                            'color' => 'info',
                        ]); ?>
                        <?php cm_component('dashboard/stat-widget', [
                            'label' => 'Rapports déposés',
                            'value' => number_format($quickStats['rapports_deposes'] ?? 0),
                            'icon' => 'fa-file-circle-check',
                            'color' => 'warning',
                        ]); ?>
                    </div>
                </div>
            </div>

            <!-- Derniers étudiants + Chronologie côte à côte -->
            <div class="cm-grid-2 cm-gap-4">
                <!-- Derniers étudiants -->
                <div class="cm-card">
                    <div class="cm-card-header cm-flex cm-justify-between cm-items-center">
                        <h3 class="cm-card-title"><i class="fas fa-user-clock cm-mr-2"></i>Derniers étudiants archivés</h3>
                        <a href="<?= htmlspecialchars($tabBase . '&tab=etudiants', ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-light is-sm">
                            Voir tout <i class="fas fa-arrow-right cm-ml-1"></i>
                        </a>
                    </div>
                    <div class="cm-card-body">
                        <?php if (empty($derniersEtudiants)): ?>
                            <p class="cm-text-muted cm-text-center cm-p-4">Aucun étudiant pour cette année.</p>
                        <?php else: ?>
                            <div class="cm-table-wrapper">
                                <table class="cm-data-table">
                                    <thead>
                                        <tr>
                                            <th class="cm-data-table__th">Matricule</th>
                                            <th class="cm-data-table__th">Nom</th>
                                            <th class="cm-data-table__th">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($derniersEtudiants as $student):
                                            $status = (string) ($student['statut'] ?? 'en_cours');
                                            $statusLabel = 'En cours';
                                            $statusType = 'info';
                                            if ($status === 'valider') { $statusLabel = 'Admis'; $statusType = 'success'; }
                                            elseif ($status === 'rejeter') { $statusLabel = 'Ajourné'; $statusType = 'danger'; }
                                        ?>
                                        <tr class="cm-data-table__row">
                                            <td class="cm-data-table__td"><code><?= htmlspecialchars((string) ($student['matricule'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                                            <td class="cm-data-table__td"><?= htmlspecialchars(trim(($student['nom'] ?? '') . ' ' . ($student['prenoms'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="cm-data-table__td"><?php cm_component('ui/badge', ['text' => $statusLabel, 'type' => $statusType]); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chronologie -->
                <div class="cm-card">
                    <div class="cm-card-header">
                        <h3 class="cm-card-title"><i class="fas fa-timeline cm-mr-2"></i>Chronologie de l'année</h3>
                    </div>
                    <div class="cm-card-body">
                        <?php if (empty($timeline)): ?>
                            <p class="cm-text-muted cm-text-center cm-p-4">Aucun événement enregistré.</p>
                        <?php else: ?>
                            <div class="cm-timeline">
                                <?php foreach ($timeline as $event): ?>
                                    <div class="cm-timeline-item">
                                        <div class="cm-timeline-marker"></div>
                                        <div class="cm-timeline-content">
                                            <p class="cm-text-sm cm-text-muted cm-mb-0">
                                                <?php
                                                $d = $event['date'] ?? '';
                                                echo ($d && strpos($d, '0000') !== 0) ? date('d/m/Y', strtotime($d)) : '-';
                                                ?>
                                            </p>
                                            <p class="cm-font-semibold cm-mb-0"><?= htmlspecialchars($event['event'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php elseif ($activeTab === 'etudiants'): ?>
            <!-- =============== ONGLET: ÉTUDIANTS =============== -->
            <?php
            $studentsPager = cm_paginate(max($studentsTotal, 1), $studentsPerPage, $studentsPage);
            $studentsPager['last'] = $studentsTotalPages;
            $studentsPagerBase = $tabBase . '&tab=etudiants&' . http_build_query(array_filter([
                'search' => $studentsSearch,
                'statut' => $studentsStatut,
            ], static function ($value) { return $value !== ''; }));
            ?>

            <?php
            cm_toolbar([
                'screen' => 'admin_historique',
                'id_prefix' => 'cmArchEtu',
                'search_name' => 'search',
                'search_value' => $studentsSearch,
                'search_placeholder' => 'Nom, matricule, thème...',
                'limit' => $studentsPerPage,
                'limit_options' => [5, 10, 25, 50, 100],
                'can_delete' => false,
                'can_view' => true,
                'show_actions' => false,
                'custom_actions' => [
                    ['tag' => 'a', 'href' => $tabBase . '&tab=etudiants', 'label' => 'Réinitialiser', 'icon' => 'fa-rotate-left', 'class' => 'cm-btn is-light is-sm'],
                ],
                'filters' => [
                    ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'valider' => 'Admis', 'rejeter' => 'Ajourné', 'en_cours' => 'En cours']],
                ],
            ]);
            ?>

            <div class="cm-flex cm-justify-between cm-items-center cm-mb-3">
                <p class="cm-text-muted cm-mb-0">
                    <strong><?= number_format($studentsTotal) ?></strong> étudiant<?= $studentsTotal > 1 ? 's' : '' ?> trouvé<?= $studentsTotal > 1 ? 's' : '' ?>
                    <?php if ($studentsSearch): ?>
                        pour « <em><?= htmlspecialchars($studentsSearch, ENT_QUOTES, 'UTF-8') ?></em> »
                    <?php endif; ?>
                </p>
            </div>

            <div class="cm-table-wrapper">
                <table class="cm-data-table">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">#</th>
                            <th class="cm-data-table__th">Matricule</th>
                            <th class="cm-data-table__th">Nom & Prénoms</th>
                            <th class="cm-data-table__th">Thème</th>
                            <th class="cm-data-table__th">Entreprise</th>
                            <th class="cm-data-table__th">Année</th>
                            <th class="cm-data-table__th">Statut</th>
                            <th class="cm-data-table__th is-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                        <tr><td colspan="8" class="cm-data-table__td is-center cm-p-5">
                            <i class="fas fa-inbox cm-text-muted cm-text-3xl cm-mb-2" style="display: block;"></i>
                            <span class="cm-text-muted">Aucun étudiant trouvé pour les critères sélectionnés.</span>
                        </td></tr>
                        <?php else: ?>
                            <?php
                            $rowNum = ($studentsPage - 1) * $studentsPerPage;
                            foreach ($students as $student):
                                $rowNum++;
                                $status = (string) ($student['statut'] ?? 'en_cours');
                                $statusLabel = 'En cours';
                                $statusType = 'info';
                                if ($status === 'valider') { $statusLabel = 'Admis'; $statusType = 'success'; }
                                elseif ($status === 'rejeter') { $statusLabel = 'Ajourné'; $statusType = 'danger'; }
                            ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td cm-text-muted"><?= $rowNum ?></td>
                                <td class="cm-data-table__td"><code><?= htmlspecialchars((string) ($student['matricule'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td class="cm-data-table__td cm-font-semibold"><?= htmlspecialchars(trim(($student['nom'] ?? '') . ' ' . ($student['prenoms'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td" style="max-width: 250px;">
                                    <span class="cm-text-ellipsis" title="<?= htmlspecialchars((string) ($student['theme'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string) ($student['theme'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="cm-data-table__td"><?= htmlspecialchars((string) ($student['entreprise'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td">
                                    <small class="cm-text-muted"><?= htmlspecialchars((string) ($student['annee_academique'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="cm-data-table__td"><?php cm_component('ui/badge', ['text' => $statusLabel, 'type' => $statusType]); ?></td>
                                <td class="cm-data-table__td is-center">
                                    <a class="cm-btn-action is-edit" href="<?= htmlspecialchars('?page=admin_historique&action=view_student&num_etu=' . urlencode((string) ($student['matricule'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" aria-label="Voir le dossier" title="Consulter le dossier">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php cm_component('crud/pagination', [
                'pagination' => $studentsPager,
                'base_url' => $studentsPagerBase,
                'param_name' => 'p',
            ]); ?>

            <?php elseif ($activeTab === 'jurys'): ?>
            <!-- =============== ONGLET: JURYS =============== -->
            <?php
            $juriesPager = cm_paginate(max($juriesTotal, 1), $juriesPerPage, $juriesPage);
            $juriesPager['last'] = $juriesTotalPages;
            $juriesPagerBase = $tabBase . '&tab=jurys';
            ?>

            <div class="cm-flex cm-justify-between cm-items-center cm-mb-3">
                <p class="cm-text-muted cm-mb-0">
                    <strong><?= number_format($juriesTotal) ?></strong> composition<?= $juriesTotal > 1 ? 's' : '' ?> de jury
                </p>
            </div>

            <div class="cm-table-wrapper">
                <table class="cm-data-table">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">#</th>
                            <th class="cm-data-table__th">Date soutenance</th>
                            <th class="cm-data-table__th">Étudiant</th>
                            <th class="cm-data-table__th">Thème</th>
                            <th class="cm-data-table__th">Président</th>
                            <th class="cm-data-table__th">Encadreur</th>
                            <th class="cm-data-table__th">Examinateur</th>
                            <th class="cm-data-table__th">Directeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($juries)): ?>
                        <tr><td colspan="8" class="cm-data-table__td is-center cm-p-5">
                            <i class="fas fa-users-slash cm-text-muted cm-text-3xl cm-mb-2" style="display: block;"></i>
                            <span class="cm-text-muted">Aucun jury trouvé pour cette année académique.</span>
                        </td></tr>
                        <?php else: ?>
                            <?php
                            $jRowNum = ($juriesPage - 1) * $juriesPerPage;
                            foreach ($juries as $jury):
                                $jRowNum++;
                                $dateRaw = (string) ($jury['date_soutenance'] ?? '');
                                $dateOut = ($dateRaw !== '' && strpos($dateRaw, '0000-00-00') !== 0) ? date('d/m/Y', strtotime($dateRaw)) : '-';
                            ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td cm-text-muted"><?= $jRowNum ?></td>
                                <td class="cm-data-table__td">
                                    <span class="cm-font-semibold"><?= htmlspecialchars($dateOut, ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td class="cm-data-table__td">
                                    <?= htmlspecialchars((string) ($jury['etudiant_nom'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    <br><small class="cm-text-muted"><?= htmlspecialchars((string) ($jury['etudiant_matricule'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="cm-data-table__td" style="max-width: 200px;">
                                    <span class="cm-text-ellipsis" title="<?= htmlspecialchars((string) ($jury['theme_soutenance'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string) ($jury['theme_soutenance'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="cm-data-table__td"><?= htmlspecialchars((string) ($jury['president'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars((string) ($jury['encadreur'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars((string) ($jury['examinateur'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars((string) ($jury['directeur'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php cm_component('crud/pagination', [
                'pagination' => $juriesPager,
                'base_url' => $juriesPagerBase,
                'param_name' => 'p',
            ]); ?>

            <?php elseif ($activeTab === 'statistiques'): ?>
            <!-- =============== ONGLET: STATISTIQUES =============== -->

            <!-- Stats globales toutes années confondues -->
            <div class="cm-grid-4 cm-gap-4 cm-mb-4">
                <?php cm_component('dashboard/stat-widget', [
                    'label' => 'Étudiants (total)',
                    'value' => (int) ($globalStats['total_students'] ?? 0),
                    'icon' => 'fa-user-graduate',
                    'color' => 'info',
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'label' => 'Soutenances (total)',
                    'value' => (int) ($globalStats['total_soutenances'] ?? 0),
                    'icon' => 'fa-chalkboard-teacher',
                    'color' => 'success',
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'label' => 'Entreprises partenaires',
                    'value' => (int) ($globalStats['total_entreprises'] ?? 0),
                    'icon' => 'fa-building',
                    'color' => 'warning',
                ]); ?>
                <?php cm_component('dashboard/stat-widget', [
                    'label' => 'Encadreurs',
                    'value' => (int) ($globalStats['total_encadreurs'] ?? 0),
                    'icon' => 'fa-users',
                    'color' => 'primary',
                ]); ?>
            </div>

            <!-- Évolution par année -->
            <div class="cm-card cm-mb-4">
                <div class="cm-card-header">
                    <h3 class="cm-card-title"><i class="fas fa-chart-line cm-mr-2"></i>Évolution par année académique</h3>
                </div>
                <div class="cm-card-body">
                    <?php if (!empty($yearlyEvolution)): ?>
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Année</th>
                                    <th class="cm-data-table__th is-center">Inscrits</th>
                                    <th class="cm-data-table__th is-center">Admis</th>
                                    <th class="cm-data-table__th is-center">Taux réussite</th>
                                    <th class="cm-data-table__th is-center">Moyenne</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($yearlyEvolution as $row): ?>
                                <tr class="cm-data-table__row">
                                    <td class="cm-data-table__td cm-font-semibold"><?= htmlspecialchars((string) ($row['annee'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td is-center"><?= (int) ($row['inscrits'] ?? 0) ?></td>
                                    <td class="cm-data-table__td is-center"><?= (int) ($row['admis'] ?? 0) ?></td>
                                    <td class="cm-data-table__td is-center">
                                        <?php
                                        $taux = (float) ($row['taux'] ?? 0);
                                        $tauxColor = $taux >= 80 ? 'success' : ($taux >= 50 ? 'warning' : 'danger');
                                        cm_component('ui/badge', [
                                            'text' => number_format($taux, 1, ',', ' ') . '%',
                                            'type' => $tauxColor
                                        ]);
                                        ?>
                                    </td>
                                    <td class="cm-data-table__td is-center">
                                        <?= isset($row['moyenne_note']) ? number_format((float) $row['moyenne_note'], 2, ',', ' ') . '/20' : '-' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <p class="cm-text-muted cm-text-center cm-p-4">Aucune donnée d'évolution disponible.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Distribution des mentions + Top entreprises -->
            <div class="cm-grid-2 cm-gap-4">
                <!-- Distribution des mentions -->
                <div class="cm-card">
                    <div class="cm-card-header">
                        <h3 class="cm-card-title"><i class="fas fa-medal cm-mr-2"></i>Distribution des mentions</h3>
                    </div>
                    <div class="cm-card-body">
                        <?php if (!empty($mentionsDistribution)): ?>
                        <div class="cm-table-wrapper">
                            <table class="cm-data-table">
                                <thead>
                                    <tr>
                                        <th class="cm-data-table__th">Mention</th>
                                        <th class="cm-data-table__th is-center">Effectif</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $mentionColors = [
                                        'Très bien' => 'success',
                                        'Bien' => 'primary',
                                        'Assez bien' => 'warning',
                                        'Passable' => 'info',
                                    ];
                                    foreach ($mentionsDistribution as $mention => $count):
                                        $color = $mentionColors[$mention] ?? 'secondary';
                                    ?>
                                    <tr class="cm-data-table__row">
                                        <td class="cm-data-table__td">
                                            <?php cm_component('ui/badge', ['text' => (string) $mention, 'type' => $color]); ?>
                                        </td>
                                        <td class="cm-data-table__td is-center cm-font-bold"><?= (int) $count ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p class="cm-text-muted cm-text-center cm-p-4">Aucune donnée de mention disponible.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top entreprises -->
                <div class="cm-card">
                    <div class="cm-card-header">
                        <h3 class="cm-card-title"><i class="fas fa-building cm-mr-2"></i>Top 10 entreprises d'accueil</h3>
                    </div>
                    <div class="cm-card-body">
                        <?php if (!empty($topEntreprises)): ?>
                        <div class="cm-table-wrapper">
                            <table class="cm-data-table">
                                <thead>
                                    <tr>
                                        <th class="cm-data-table__th">#</th>
                                        <th class="cm-data-table__th">Entreprise</th>
                                        <th class="cm-data-table__th is-center">Stagiaires</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rank = 0;
                                    foreach ($topEntreprises as $company):
                                        $rank++;
                                    ?>
                                    <tr class="cm-data-table__row">
                                        <td class="cm-data-table__td">
                                            <?php if ($rank <= 3): ?>
                                                <span class="cm-badge cm-badge-<?= $rank === 1 ? 'warning' : ($rank === 2 ? 'secondary' : 'info') ?>">
                                                    <?= $rank ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="cm-text-muted"><?= $rank ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cm-data-table__td cm-font-semibold"><?= htmlspecialchars((string) ($company['entreprise'] ?? ($company['lib_long_entreprise'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="cm-data-table__td is-center cm-font-bold"><?= (int) ($company['total'] ?? 0) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p class="cm-text-muted cm-text-center cm-p-4">Aucune donnée entreprise disponible.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php elseif ($activeTab === 'import'): ?>
            <!-- =============== ONGLET: IMPORT =============== -->

            <div class="cm-card cm-mb-4">
                <div class="cm-card-header">
                    <h3 class="cm-card-title"><i class="fas fa-file-import cm-mr-2"></i>Importer des archives</h3>
                </div>
                <div class="cm-card-body">
                    <div class="cm-grid-2 cm-gap-4">
                        <div>
                            <form method="POST" action="?page=admin_historique&action=import" enctype="multipart/form-data" data-cm-ajax-form="true">
                                <?php cm_component('form/csrf-token'); ?>
                                <div class="cm-mb-3">
                                    <?php cm_component('form/file-upload', [
                                        'name' => 'archive_file',
                                        'label' => 'Fichier d\'import (CSV, XLS, XLSX)',
                                        'accept' => '.csv,.xls,.xlsx',
                                    ]); ?>
                                </div>
                                <p class="cm-text-muted cm-text-sm cm-mb-3">
                                    <i class="fas fa-info-circle cm-mr-1"></i>
                                    Le fichier doit respecter le format attendu. Les colonnes requises dépendent du type de données importées.
                                </p>
                                <button type="submit" class="cm-btn is-success">
                                    <i class="fas fa-upload" aria-hidden="true"></i>
                                    <span>Lancer l'import</span>
                                </button>
                            </form>
                        </div>
                        <div>
                            <div class="cm-card cm-bg-light cm-p-4">
                                <h4 class="cm-font-semibold cm-mb-3"><i class="fas fa-question-circle cm-mr-2"></i>Format attendu</h4>
                                <ul class="cm-list cm-text-sm cm-text-muted">
                                    <li><strong>Formats acceptés :</strong> CSV (séparateur ;), Excel (.xls, .xlsx)</li>
                                    <li><strong>Encodage :</strong> UTF-8 recommandé</li>
                                    <li><strong>Colonnes étudiants :</strong> Matricule, Nom, Prénoms, Thème, Entreprise, Encadreur, Année, Statut</li>
                                    <li><strong>Colonnes jurys :</strong> Matricule étudiant, Date soutenance, Président, Encadreur, Examinateur, Directeur</li>
                                </ul>
                                <hr class="cm-my-3">
                                <p class="cm-text-sm cm-text-muted">
                                    <i class="fas fa-download cm-mr-1"></i>
                                    <a href="?page=admin_historique&action=export&format=csv" class="cm-text-primary">Télécharger un modèle CSV</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historique des imports récents -->
            <div class="cm-card">
                <div class="cm-card-header">
                    <h3 class="cm-card-title"><i class="fas fa-history cm-mr-2"></i>Activité récente</h3>
                </div>
                <div class="cm-card-body">
                    <p class="cm-text-muted cm-text-center cm-p-4">
                        <i class="fas fa-info-circle cm-mr-1"></i>
                        L'historique des imports est disponible dans le <a href="?page=piste_audit" class="cm-text-primary">journal d'audit</a>.
                    </p>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </div>
</section>

<script>
(function() {
    // Changement d'année académique
    document.getElementById('archiveYearSelect')?.addEventListener('change', function() {
        const anneeId = this.value;
        fetch('?page=admin_historique&action=changeYear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ annee_id: anneeId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    });

    // Navigation année précédente
    document.getElementById('prevYearBtn')?.addEventListener('click', function() {
        const select = document.getElementById('archiveYearSelect');
        if (select.selectedIndex < select.options.length - 1) {
            select.selectedIndex++;
            select.dispatchEvent(new Event('change'));
        }
    });

    // Navigation année suivante
    document.getElementById('nextYearBtn')?.addEventListener('click', function() {
        const select = document.getElementById('archiveYearSelect');
        if (select.selectedIndex > 0) {
            select.selectedIndex--;
            select.dispatchEvent(new Event('change'));
        }
    });
})();
</script>
