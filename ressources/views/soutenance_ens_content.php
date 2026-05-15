<?php
require_once __DIR__ . '/../../app/controllers/ProgrammationSoutenanceController.php';
require_once __DIR__ . '/../../app/models/Enseignant.php';

$controller = new ProgrammationSoutenanceController();
$currentPageSlug = (string) ($_GET['page'] ?? 'programmation_ens');
$selectedYearLabel = \AcademicYear::getSelectedLabelFromSession();
$activeYearLabel = \AcademicYear::getActiveLabelFromSession();
$writeYearLabel = \AcademicYear::getWritableLabelFromSession();
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$isAdmin = function_exists('isAdmin') ? isAdmin() : false;
$selectedTeacherId = trim((string) ($_GET['id_enseignant_selected'] ?? ''));
$teacherId = '';
$teacherName = 'Enseignant';

$roleFieldLabels = [
    'president_id' => 'President',
    'directeur_id' => 'Directeur memoire',
    'examinateur_id' => 'Examinateur',
    'encadreur_id' => 'Encadreur pedagogique',
    'maitre_stage_id' => 'Maitre de stage',
];

$enseignants = $controller->getEnseignantsForView();
$teacherOptions = [];
foreach ($enseignants as $enseignant) {
    $id = trim((string) ($enseignant['id_enseignant'] ?? ''));
    if ($id === '') {
        continue;
    }

    $label = trim((string) ($enseignant['nom_complet'] ?? ''));
    if ($label === '') {
        $label = trim(
            (string) ($enseignant['prenom_enseignant'] ?? '') . ' ' . (string) ($enseignant['nom_enseignant'] ?? '')
        );
    }
    if ($label === '') {
        $label = 'Enseignant ' . $id;
    }

    $teacherOptions[$id] = $label;
}

try {
    $enseignantModel = new Enseignant(\Database::getConnection());

    if ($isAdmin) {
        if ($selectedTeacherId !== '') {
            $teacherId = $selectedTeacherId;
            if (isset($teacherOptions[$selectedTeacherId])) {
                $teacherName = $teacherOptions[$selectedTeacherId];
            } else {
                $selectedTeacher = $enseignantModel->getEnseignantById($selectedTeacherId);
                if ($selectedTeacher && is_object($selectedTeacher)) {
                    $teacherName = trim(
                        (string) ($selectedTeacher->prenom_enseignant ?? '') . ' ' . (string) ($selectedTeacher->nom_enseignant ?? '')
                    );
                }
            }
        }
    } else {
        $connectedTeacher = $enseignantModel->getEnseignantByLogin((string) ($_SESSION['login_utilisateur'] ?? ''));
        if ($connectedTeacher && is_object($connectedTeacher)) {
            $teacherId = trim((string) ($connectedTeacher->id_enseignant ?? ''));
            $resolvedName = trim(
                (string) ($connectedTeacher->prenom_enseignant ?? '') . ' ' . (string) ($connectedTeacher->nom_enseignant ?? '')
            );
            if ($resolvedName !== '') {
                $teacherName = $resolvedName;
            }
        }
    }
} catch (Throwable $e) {
    error_log('Programmation enseignant: impossible de resoudre le contexte enseignant: ' . $e->getMessage());
}

$allAttributions = $controller->getAttributionsForView();
$filteredAttributions = [];

$resolveTeacherRoles = static function (array $row, string $teacherId) use ($roleFieldLabels): array {
    if ($teacherId === '') {
        return [];
    }

    $roles = [];
    foreach ($roleFieldLabels as $field => $label) {
        if (trim((string) ($row[$field] ?? '')) === $teacherId) {
            $roles[] = $label;
        }
    }

    return array_values(array_unique($roles));
};

if ($teacherId !== '') {
    foreach ($allAttributions as $row) {
        $roles = $resolveTeacherRoles($row, $teacherId);
        if ($roles === []) {
            continue;
        }

        $row['_teacher_roles'] = implode(', ', $roles);
        $filteredAttributions[] = $row;
    }
}

usort($filteredAttributions, static function (array $left, array $right): int {
    $dateComparison = strcmp((string) ($left['date_soutenance'] ?? ''), (string) ($right['date_soutenance'] ?? ''));
    if ($dateComparison !== 0) {
        return $dateComparison;
    }

    return strcmp((string) ($left['heure_soutenance'] ?? ''), (string) ($right['heure_soutenance'] ?? ''));
});

$allowedLimits = [5, 10, 25, 50];
$perPage = max(5, (int) ($_GET['limit_prog'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}

$currentPage = max(1, (int) ($_GET['page_prog'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($filteredAttributions), $perPage, $currentPage)
    : [
        'total' => count($filteredAttributions),
        'per_page' => $perPage,
        'current' => $currentPage,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];

$rowsToShow = array_slice($filteredAttributions, (int) ($pagination['offset'] ?? 0), $perPage);

$baseQuery = ['page' => $currentPageSlug];
if ($isAdmin && $selectedTeacherId !== '') {
    $baseQuery['id_enseignant_selected'] = $selectedTeacherId;
}
$baseQuery['limit_prog'] = $perPage;
$baseUrl = '?' . http_build_query($baseQuery);

$pageSummary = $allYearsSelected
    ? 'Toutes les annees academiques'
    : ($selectedYearLabel !== '' ? $selectedYearLabel : $activeYearLabel);

$filterResetUrl = '?page=' . rawurlencode($currentPageSlug);
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <div class="cm-card cm-mb-md">
        <div class="cm-card__header">
            <h3 class="cm-card__title">
                <i class="fas fa-calendar-alt cm-mr-sm"></i>
                Programme des soutenances enseignant
            </h3>
            <p class="cm-text-muted">
                <small>Contexte d'affichage :
                    <?= htmlspecialchars($pageSummary !== '' ? $pageSummary : $writeYearLabel, ENT_QUOTES, 'UTF-8') ?></small>
            </p>
        </div>
        <div class="cm-card__body">
            <form method="GET" style="align-items:end;">
                <input type="hidden" name="page" value="<?= htmlspecialchars($currentPageSlug, ENT_QUOTES, 'UTF-8') ?>">

                <?php if ($isAdmin): ?>
                    <div class="cm-grid-2 cm-mb-md">
                        <?php
                        cm_component('form/select', [
                            'name' => 'id_enseignant_selected',
                            'label' => 'Enseignant',
                            'options' => $teacherOptions,
                            'selected' => $selectedTeacherId,
                            'placeholder' => 'Selectionner un enseignant',
                            'control_class' => 'cm-field-lg cm-size-personne',
                        ]);
                        ?>

                        <div class="cm-flex cm-flex-gap-sm" style="align-items:flex-end; justify-content:flex-start;">
                            <button type="submit" class="cm-btn is-primary is-sm">
                                <i class="fas fa-filter cm-mr-sm" aria-hidden="true"></i>
                                Filtrer
                            </button>
                            <a href="<?= htmlspecialchars($filterResetUrl, ENT_QUOTES, 'UTF-8') ?>"
                                class="cm-btn is-light is-sm">
                                Reinitialiser
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="cm-grid-2 cm-mb-md">
                        <div class="cm-form-group">
                            <label class="cm-form-label">Enseignant</label>
                            <select class="cm-form-control cm-field-lg cm-size-personne" disabled>
                                <option selected><?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?></option>
                            </select>
                        </div>
                        <div class="cm-form-group">
                            <label class="cm-form-label">Annee academique</label>
                            <input type="text" class="cm-form-control cm-field-lg" disabled
                                value="<?= htmlspecialchars($pageSummary !== '' ? $pageSummary : $writeYearLabel, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($teacherId !== ''): ?>
        <?php
        cm_toolbar([
            'left_html' => static function () use ($allowedLimits, $perPage, $filteredAttributions) {
                ob_start();
                ?>
            <div class="cm-toolbar__actions-group">
                <label class="cm-toolbar__control">
                    <span>Afficher:</span>
                    <select id="cmProgEnsLimit" name="limit_prog"
                        class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs">
                        <?php foreach ($allowedLimits as $limitOption): ?>
                            <option value="<?= $limitOption ?>" <?= $limitOption === $perPage ? 'selected' : '' ?>><?= $limitOption ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <span class="cm-text-muted"><small><?= count($filteredAttributions) ?> soutenance(s)</small></span>
            </div>
            <?php
                return (string) ob_get_clean();
            },
            'center_html' => static function () {
                ob_start();
                ?>
            <div class="cm-toolbar__search-wrap">
                <i class="fas fa-search cm-toolbar__search-icon" aria-hidden="true"></i>
                <input type="search" id="cmProgEnsSearch" class="cm-form-control is-sm cm-toolbar-field-lg"
                    value="<?= htmlspecialchars((string) ($_GET['search_prog'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Rechercher un etudiant, un theme, une salle...">
            </div>
            <?php
                return (string) ob_get_clean();
            },
            'right_html' => static function () {
                ob_start();
                ?>
            <div class="cm-toolbar__actions-group">
                <button type="button" id="cmProgEnsPrint" class="cm-btn is-light is-sm">
                    <i class="fas fa-print cm-mr-xs"></i>
                    <span>Imprimer</span>
                </button>
                <button type="button" id="cmProgEnsExport" class="cm-btn is-secondary is-sm">
                    <i class="fas fa-file-excel cm-mr-xs"></i>
                    <span>Excel</span>
                </button>
            </div>
            <?php
                return (string) ob_get_clean();
            }
        ]);
        ?>
    <?php endif; ?>

    <div class="cm-pole-inferieur">
        <?php if ($isAdmin && $teacherId === ''): ?>
            <div class="cm-card">
                <div class="cm-card__body">
                    <?php
                    cm_component('ui/empty-state', [
                        'title' => 'Aucun enseignant selectionne',
                        'message' => 'Selectionnez un enseignant puis appliquez le filtre pour afficher son programme de soutenances.',
                        'icon' => 'fa-user-check',
                    ]);
                    ?>
                </div>
            </div>
        <?php elseif ($teacherId === ''): ?>
            <div class="cm-card">
                <div class="cm-card__body">
                    <?php
                    cm_component('ui/empty-state', [
                        'title' => 'Profil enseignant introuvable',
                        'message' => 'Le compte connecte n est pas relie a une fiche enseignant exploitable pour cette page.',
                        'icon' => 'fa-user-slash',
                    ]);
                    ?>
                </div>
            </div>
        <?php else: ?>
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmProgEnsTable">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">N°</th>
                            <th class="cm-data-table__th">Etudiant</th>
                            <th class="cm-data-table__th">Promotion</th>
                            <th class="cm-data-table__th">Date</th>
                            <th class="cm-data-table__th">Heure</th>
                            <th class="cm-data-table__th">Salle</th>
                            <th class="cm-data-table__th">Votre role</th>
                            <th class="cm-data-table__th">President</th>
                            <th class="cm-data-table__th">Dir. memoire</th>
                            <th class="cm-data-table__th">Examinateur</th>
                            <th class="cm-data-table__th">Encadreur</th>
                            <th class="cm-data-table__th">Maitre de stage</th>
                            <th class="cm-data-table__th">Theme</th>
                        </tr>
                    </thead>
                    <tbody id="cmProgEnsTableBody">
                        <?php if ($rowsToShow === []): ?>
                            <?php
                            cm_component('ui/empty-state', [
                                'in_table' => true,
                                'colspan' => 13,
                                'title' => '',
                                'message' => 'Aucune soutenance programmee pour cet enseignant dans le contexte courant.',
                            ]);
                            ?>
                        <?php else: ?>
                            <?php foreach ($rowsToShow as $index => $row): ?>
                                <?php
                                $nomEtudiant = trim((string) ($row['nom_etudiant'] ?? ''));
                                $matricule = trim((string) ($row['matricule_etudiant'] ?? ''));
                                $promotion = \FormattingUtils::formatPromotion(trim((string) ($row['promotion_etu'] ?? '')));
                                $theme = trim((string) ($row['theme_soutenance'] ?? ''));
                                $dateRaw = (string) ($row['date_soutenance'] ?? '');
                                $heureRaw = (string) ($row['heure_soutenance'] ?? '');
                                $dateDisplay = $dateRaw !== '' ? date('d/m/Y', strtotime($dateRaw)) : '-';
                                $heureDisplay = $heureRaw !== '' ? date('H:i', strtotime($heureRaw)) : '-';
                                $salleNom = trim((string) ($row['nom_salle'] ?? ''));
                                $teacherRoles = trim((string) ($row['_teacher_roles'] ?? ''));
                                $searchText = strtolower(implode(' ', [
                                    $nomEtudiant,
                                    $matricule,
                                    $promotion,
                                    $dateDisplay,
                                    $heureDisplay,
                                    $salleNom,
                                    $teacherRoles,
                                    (string) ($row['president_nom'] ?? ''),
                                    (string) ($row['directeur_nom'] ?? ''),
                                    (string) ($row['examinateur_nom'] ?? ''),
                                    (string) ($row['encadreur_nom'] ?? ''),
                                    (string) ($row['maitre_stage_nom'] ?? ''),
                                    $theme,
                                ]));
                                ?>
                                <tr class="cm-data-table__row"
                                    data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                                    <td class="cm-data-table__td"><?= (int) ($pagination['offset'] ?? 0) + $index + 1 ?></td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars($nomEtudiant !== '' ? $nomEtudiant : '-', ENT_QUOTES, 'UTF-8') ?><br>
                                        <small><?= htmlspecialchars($matricule !== '' ? $matricule : '-', ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars($promotion !== '' ? $promotion : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars($heureDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars($salleNom !== '' ? $salleNom : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars($teacherRoles !== '' ? $teacherRoles : '-', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars((string) ($row['president_nom'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars((string) ($row['directeur_nom'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars((string) ($row['examinateur_nom'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars((string) ($row['encadreur_nom'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars((string) ($row['maitre_stage_nom'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?= htmlspecialchars($theme !== '' ? $theme : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php
            cm_component('crud/pagination', [
                'pagination' => $pagination,
                'base_url' => $baseUrl,
                'param_name' => 'page_prog',
            ]);
            ?>
        <?php endif; ?>
    </div>
</div>

<script>
    (function () {
        const searchInput = document.getElementById('cmProgEnsSearch');
        const limitSelect = document.getElementById('cmProgEnsLimit');
        const printButton = document.getElementById('cmProgEnsPrint');
        const exportButton = document.getElementById('cmProgEnsExport');
        const currentUrl = new URL(window.location.href);

        function getRows() {
            return Array.from(document.querySelectorAll('#cmProgEnsTableBody .cm-data-table__row'));
        }

        function applySearch() {
            const term = searchInput ? (searchInput.value || '').trim().toLowerCase() : '';
            getRows().forEach(function (row) {
                const haystack = (row.getAttribute('data-search') || '').toLowerCase();
                row.style.display = term === '' || haystack.indexOf(term) !== -1 ? '' : 'none';
            });
        }

        function exportVisibleRows() {
            const table = document.getElementById('cmProgEnsTable');
            if (!table) {
                return;
            }

            const headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
                return '"' + String(th.innerText || '').trim().replace(/"/g, '""') + '"';
            });

            const lines = [headers.join(';')];
            getRows().forEach(function (row) {
                if (row.style.display === 'none') {
                    return;
                }
                const values = Array.from(row.querySelectorAll('td')).map(function (cell) {
                    return '"' + String(cell.innerText || '').trim().replace(/"/g, '""') + '"';
                });
                lines.push(values.join(';'));
            });

            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'programmation_enseignant.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        }

        if (searchInput) {
            searchInput.addEventListener('input', applySearch);
            applySearch();
        }

        if (limitSelect) {
            limitSelect.addEventListener('change', function () {
                currentUrl.searchParams.set('limit_prog', limitSelect.value || '10');
                currentUrl.searchParams.set('page_prog', '1');
                window.location.href = currentUrl.toString();
            });
        }

        if (printButton) {
            printButton.addEventListener('click', function () {
                window.print();
            });
        }

        if (exportButton) {
            exportButton.addEventListener('click', exportVisibleRows);
        }
    })();
</script>