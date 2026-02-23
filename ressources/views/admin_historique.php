<?php
$currentTab = (string) ($GLOBALS['currentTab'] ?? ($_GET['tab'] ?? 'students'));
if (!in_array($currentTab, ['students', 'jury', 'stats'], true)) {
    $currentTab = 'students';
}

$students = is_array($GLOBALS['students'] ?? null) ? $GLOBALS['students'] : [];
$juries = is_array($GLOBALS['juries'] ?? null) ? $GLOBALS['juries'] : [];
$academicYears = is_array($GLOBALS['academicYears'] ?? null) ? $GLOBALS['academicYears'] : [];
$filters = is_array($GLOBALS['filters'] ?? null) ? $GLOBALS['filters'] : [];
$totalPages = max(1, (int) ($GLOBALS['totalPages'] ?? 1));
$currentPage = max(1, (int) ($GLOBALS['currentPage'] ?? 1));
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
    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <?php
        ob_start();
        ?>
        <div class="cm-grid-2">
            <div>
                <p class="cm-text-muted">Consultation et archivage des soutenances.</p>
            </div>
            <?php if (function_exists('canCreate') ? canCreate() : true): ?>
            <form method="POST" action="?page=admin_historique&action=import" enctype="multipart/form-data" data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <div class="cm-inline-stack">
                    <div class="cm-inline-stack__grow">
                        <?php cm_component('form/file-upload', [
                            'name' => 'archive_file',
                            'label' => 'Importer archives (CSV)',
                            'accept' => '.csv,.xls,.xlsx',
                        ]); ?>
                    </div>
                    <button type="submit" class="cm-btn is-success">
                        <i class="fas fa-upload" aria-hidden="true"></i>
                        <span>Importer</span>
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <?php
        cm_component('crud/form-pole', [
            'title' => 'Historique et archivage',
            'icon' => 'fa-archive',
            'content' => (string) ob_get_clean(),
        ]);
        ?>

        <?php
        $tabBase = '?page=admin_historique';
        ?>
        <div class="cm-tab-links" role="tablist" aria-label="Historique">
            <a class="cm-btn <?= $currentTab === 'students' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars($tabBase . '&tab=students', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-user-graduate" aria-hidden="true"></i>
                <span>Etudiants</span>
            </a>
            <a class="cm-btn <?= $currentTab === 'jury' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars($tabBase . '&tab=jury', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-users" aria-hidden="true"></i>
                <span>Jurys</span>
            </a>
            <a class="cm-btn <?= $currentTab === 'stats' ? 'is-info' : 'is-light' ?>" href="<?= htmlspecialchars($tabBase . '&tab=stats', ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-chart-pie" aria-hidden="true"></i>
                <span>Statistiques</span>
            </a>
        </div>

        <div class="cm-pole-inferieur">
            <?php if ($currentTab === 'students'): ?>
                <form method="GET" id="cmArchiveStudentFilters" data-cm-ajax-form="true">
                    <input type="hidden" name="page" value="admin_historique">
                    <input type="hidden" name="tab" value="students">
                    <?php
                    ob_start();
                    ?>
                    <label class="cm-toolbar__control">
                        <span>Annee:</span>
                        <select class="cm-form-control cm-toolbar__select" name="annee">
                            <option value="">Toutes</option>
                            <?php foreach ($academicYears as $year): ?>
                            <option value="<?= htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($filters['annee'] ?? '') === (string) $year) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="cm-toolbar__control">
                        <span>Statut:</span>
                        <select class="cm-form-control cm-toolbar__select" name="statut">
                            <option value="">Tous</option>
                            <option value="valider" <?= (($filters['statut'] ?? '') === 'valider') ? 'selected' : '' ?>>Admis</option>
                            <option value="rejeter" <?= (($filters['statut'] ?? '') === 'rejeter') ? 'selected' : '' ?>>Ajourne</option>
                            <option value="en_cours" <?= (($filters['statut'] ?? '') === 'en_cours') ? 'selected' : '' ?>>En cours</option>
                        </select>
                    </label>
                    <?php
                    $leftHtml = (string) ob_get_clean();

                    ob_start();
                    ?>
                    <div class="cm-toolbar__search-wrap">
                        <input type="search" class="cm-form-control" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom, matricule, theme...">
                        <button type="submit" class="cm-btn is-info is-sm">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>Filtrer</span>
                        </button>
                    </div>
                    <?php
                    cm_component('crud/toolbar', [
                        'left_html' => $leftHtml,
                        'center_html' => (string) ob_get_clean(),
                        'right_html' => '<a class="cm-btn is-light is-sm" href="?page=admin_historique&tab=students"><i class="fas fa-rotate-left"></i><span>Reset</span></a>',
                    ]);
                    ?>
                </form>

                <div class="cm-table-wrapper">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">Matricule</th>
                                <th class="cm-data-table__th">Nom</th>
                                <th class="cm-data-table__th">Theme</th>
                                <th class="cm-data-table__th">Entreprise</th>
                                <th class="cm-data-table__th">Statut</th>
                                <th class="cm-data-table__th is-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                            <tr><td colspan="6" class="cm-data-table__td is-center">Aucun etudiant trouve.</td></tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                <?php
                                $status = (string) ($student['statut'] ?? 'en_cours');
                                $statusLabel = 'En cours';
                                $statusType = 'info';
                                if ($status === 'valider') {
                                    $statusLabel = 'Admis';
                                    $statusType = 'success';
                                } elseif ($status === 'rejeter') {
                                    $statusLabel = 'Ajourne';
                                    $statusType = 'danger';
                                }
                                ?>
                                <tr class="cm-data-table__row">
                                    <td class="cm-data-table__td"><?= htmlspecialchars((string) ($student['matricule'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars(trim((string) (($student['nom'] ?? '') . ' ' . ($student['prenoms'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars((string) ($student['theme'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars((string) ($student['entreprise'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?php cm_component('ui/badge', ['text' => $statusLabel, 'type' => $statusType]); ?>
                                    </td>
                                    <td class="cm-data-table__td is-center">
                                        <a class="cm-btn-action is-edit" href="<?= htmlspecialchars('?page=admin_historique&action=view_student&num_etu=' . urlencode((string) ($student['matricule'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" aria-label="Voir">
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
            <?php elseif ($currentTab === 'jury'): ?>
                <div class="cm-table-wrapper">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">Date</th>
                                <th class="cm-data-table__th">Etudiant</th>
                                <th class="cm-data-table__th">President</th>
                                <th class="cm-data-table__th">Encadreur</th>
                                <th class="cm-data-table__th">Examinateur</th>
                                <th class="cm-data-table__th">Directeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($juries)): ?>
                            <tr><td colspan="6" class="cm-data-table__td is-center">Aucun jury trouve.</td></tr>
                            <?php else: ?>
                                <?php foreach ($juries as $jury): ?>
                                <?php
                                $dateRaw = (string) ($jury['date_soutenance'] ?? '');
                                $dateOut = ($dateRaw !== '' && strpos($dateRaw, '0000-00-00') !== 0) ? date('d/m/Y', strtotime($dateRaw)) : '-';
                                ?>
                                <tr class="cm-data-table__row">
                                    <td class="cm-data-table__td"><?= htmlspecialchars($dateOut, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars((string) ($jury['etudiant_nom'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                                        <small class="cm-text-muted"><?= htmlspecialchars((string) ($jury['etudiant_matricule'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
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
            <?php else: ?>
                <div class="cm-grid-4">
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Etudiants total',
                        'value' => (int) ($globalStats['total_students'] ?? 0),
                        'icon' => 'fa-user-graduate',
                        'color' => 'info',
                    ]); ?>
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Soutenances',
                        'value' => (int) ($globalStats['total_soutenances'] ?? 0),
                        'icon' => 'fa-chalkboard-teacher',
                        'color' => 'success',
                    ]); ?>
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Entreprises',
                        'value' => (int) ($globalStats['total_entreprises'] ?? 0),
                        'icon' => 'fa-building',
                        'color' => 'warning',
                    ]); ?>
                    <?php cm_component('dashboard/stat-widget', [
                        'label' => 'Encadreurs',
                        'value' => (int) ($globalStats['total_encadreurs'] ?? 0),
                        'icon' => 'fa-users',
                        'color' => 'info',
                    ]); ?>
                </div>

                <div class="cm-table-wrapper">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">Annee</th>
                                <th class="cm-data-table__th">Inscrits</th>
                                <th class="cm-data-table__th">Admis</th>
                                <th class="cm-data-table__th">Taux</th>
                                <th class="cm-data-table__th">Moyenne</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($yearlyEvolution)): ?>
                            <tr><td colspan="5" class="cm-data-table__td is-center">Aucune donnee.</td></tr>
                            <?php else: ?>
                                <?php foreach ($yearlyEvolution as $row): ?>
                                <tr class="cm-data-table__row">
                                    <td class="cm-data-table__td"><?= htmlspecialchars((string) ($row['annee'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= (int) ($row['inscrits'] ?? 0) ?></td>
                                    <td class="cm-data-table__td"><?= (int) ($row['admis'] ?? 0) ?></td>
                                    <td class="cm-data-table__td"><?= number_format((float) ($row['taux'] ?? 0), 1, ',', ' ') ?>%</td>
                                    <td class="cm-data-table__td"><?= isset($row['moyenne_note']) ? number_format((float) $row['moyenne_note'], 2, ',', ' ') : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="cm-grid-2">
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Mention</th>
                                    <th class="cm-data-table__th">Effectif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mentionsDistribution)): ?>
                                <tr><td colspan="2" class="cm-data-table__td is-center">Aucune donnee.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($mentionsDistribution as $mention => $count): ?>
                                    <tr class="cm-data-table__row">
                                        <td class="cm-data-table__td"><?= htmlspecialchars((string) $mention, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="cm-data-table__td"><?= (int) $count ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Entreprise</th>
                                    <th class="cm-data-table__th">Etudiants</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topEntreprises)): ?>
                                <tr><td colspan="2" class="cm-data-table__td is-center">Aucune donnee.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($topEntreprises as $company): ?>
                                    <tr class="cm-data-table__row">
                                        <td class="cm-data-table__td"><?= htmlspecialchars((string) ($company['entreprise'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="cm-data-table__td"><?= (int) ($company['total'] ?? 0) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
