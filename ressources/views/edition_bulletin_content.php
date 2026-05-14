<?php
require_once __DIR__ . '/../../app/controllers/EvaluationSoutenanceController.php';
$controller = new EvaluationSoutenanceController();
$soutenances = $controller->getSoutenancesProgrammeesForView();
$anneesAcademiques = $controller->getAnneesAcademiques();
$niveauOptions = ['all' => 'Tous'];
$semestreOptions = ['all' => 'Tous', 'S1' => 'S1', 'S2' => 'S2'];
$promotionOptions = ['all' => 'Toutes'];
$anneeOptions = ['all' => 'Toutes'];
$rows = [];
foreach ($anneesAcademiques as $annee) {
    $label = (string) ($annee['lib_annee'] ?? '');
    if ($label !== '') {
        $anneeOptions[$label] = $label;
    }
}
foreach ($soutenances as $row) {
    $numEtu = (string) ($row['num_etu'] ?? '');
    if ($numEtu === '') {
        continue;
    }
    $fullName = trim((string) ($row['nom_etudiant'] ?? 'Étudiant'));
    $nameParts = preg_split('/\s+/', $fullName, 2);
    $prenom = trim((string) ($nameParts[0] ?? ''));
    $nom = trim((string) ($nameParts[1] ?? ''));
    $promotion = trim((string) ($row['promotion_etu'] ?? ''));
    $niveau = '-';
    if (stripos($promotion, 'M2') !== false) {
        $niveau = 'M2';
    } elseif (stripos($promotion, 'M1') !== false) {
        $niveau = 'M1';
    }
    $semestre = 'S1';
    $isEvaluated = (int) ($row['est_evalue'] ?? 0) > 0;
    $moyenne = (float) ($row['note_finale'] ?? 0);
    $mention = '-';
    if ($isEvaluated) {
        if ($moyenne >= 18) {
            $mention = 'Honorable';
        } elseif ($moyenne >= 16) {
            $mention = 'Tres Bien';
        } elseif ($moyenne >= 14) {
            $mention = 'Bien';
        } elseif ($moyenne >= 12) {
            $mention = 'Assez Bien';
        } elseif ($moyenne >= 10) {
            $mention = 'Passable';
        } else {
            $mention = 'Insuffisant';
        }
    }
    if ($niveau !== '-' && !isset($niveauOptions[$niveau])) {
        $niveauOptions[$niveau] = $niveau;
    }
    if ($promotion !== '' && !isset($promotionOptions[$promotion])) {
        $promotionOptions[$promotion] = $promotion;
    }
    $rows[] = [
        'num_etu' => $numEtu,
        'matricule' => (string) ($row['matricule_etudiant'] ?? $numEtu),
        'nom' => $nom !== '' ? $nom : $fullName,
        'prenom' => $prenom,
        'niveau' => $niveau,
        'semestre' => $semestre,
        'promotion' => $promotion,
        'moyenne' => $moyenne,
        'mention' => $mention,
        'is_evaluated' => $isEvaluated,
    ];
}
$niveauFilter = (string) ($_GET['bulletin_niveau'] ?? 'all');
$semestreFilter = (string) ($_GET['bulletin_semestre'] ?? 'all');
$bulletinYearFilter = (string) ($_GET['bulletin_annee'] ?? ($_SESSION['global_annee_selected'] ?? 'all'));
$promotionFilter = (string) ($_GET['bulletin_promotion'] ?? $bulletinYearFilter);
if ($bulletinYearFilter !== 'all') {
    $promotionFilter = $bulletinYearFilter;
}
$filteredRows = array_values(array_filter($rows, static function (array $row) use ($niveauFilter, $semestreFilter, $promotionFilter, $bulletinYearFilter): bool {
    if ($niveauFilter !== 'all' && (string) ($row['niveau'] ?? '') !== $niveauFilter) {
        return false;
    }
    if ($semestreFilter !== 'all' && (string) ($row['semestre'] ?? '') !== $semestreFilter) {
        return false;
    }
    if ($bulletinYearFilter !== 'all' && (string) ($row['promotion'] ?? '') !== $bulletinYearFilter) {
        return false;
    }
    if ($promotionFilter !== 'all' && (string) ($row['promotion'] ?? '') !== $promotionFilter) {
        return false;
    }
    return true;
}));
$allowedLimits = [5, 10, 25, 50, 100];
$perPage = max(5, (int) ($_GET['limit_bulletin'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['page_bulletin'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($filteredRows), $perPage, $currentPage)
    : [
        'total' => count($filteredRows),
        'per_page' => $perPage,
        'current' => 1,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];
$rowsToShow = array_slice($filteredRows, (int) ($pagination['offset'] ?? 0), $perPage);
$baseUrl = '?page=edition_bulletin'
    . '&bulletin_niveau=' . urlencode($niveauFilter)
    . '&bulletin_semestre=' . urlencode($semestreFilter)
    . '&bulletin_annee=' . urlencode($bulletinYearFilter)
    . '&bulletin_promotion=' . urlencode($promotionFilter)
    . '&limit_bulletin=' . $perPage;
?>
<?php if (!canView()): ?>
    <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => "Vous n'avez pas l'autorisation d'accéder à cette page."]); ?>
<?php else: ?>
    <div class="cm-prd3-screen cm-prd3-crud-screen">

        <div class="cm-crud-wrapper">
            <div class="cm-pole-superieur">
                <div class="">
                </div>
                <div class="cm-grid-3">
                    <?php
                    cm_component('form/select', [
                        'name' => 'cm_bulletin_annee',
                        'id' => 'cmBulletinAnnee',
                        'label' => 'Année académique',
                        'options' => $anneeOptions,
                        'selected' => $bulletinYearFilter,
                        'attrs' => [
                            'data-cm-ajax-param' => 'bulletin_annee',
                            'data-cm-ajax-reset-param' => 'page_bulletin',
                            'data-cm-ajax-reset-value' => '1',
                        ],
                    ]);
                    cm_component('form/select', [
                        'name' => 'cm_bulletin_session',
                        'id' => 'cmBulletinSession',
                        'label' => 'Session',
                        'options' => ['all' => 'Toutes'],
                        'selected' => 'all',
                        'attrs' => [
                            'data-cm-ajax-param' => 'bulletin_session',
                            'data-cm-ajax-reset-param' => 'page_bulletin',
                            'data-cm-ajax-reset-value' => '1',
                        ],
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_bulletin_etudiant',
                        'id' => 'cmBulletinEtudiant',
                        'label' => 'Étudiant',
                        'placeholder' => 'Rechercher...',
                    ]);
                    ?>
                    <div class="cm-form-group" style="display: flex; align-items: flex-end;">
                        <?php if (canCreate() || canEdit()): ?>
                            <button class="cm-btn is-success" type="button" id="cmBulletinGenerateAll">
                                <i class="fas fa-file-circle-check" aria-hidden="true"></i>
                                Generer tous les bulletins
                            </button>
                        <?php endif; ?>
                    </div>
                    <button class="cm-btn is-success" type="button" id="cmBulletinGenerateAll">
                        <i class="fas fa-file-circle-check" aria-hidden="true"></i>
                        Generer tous les bulletins
                    </button>
                </div>
            </div>
        </div>
        <?php cm_toolbar([
            'screen' => 'edition_bulletin',
            'id_prefix' => 'cmBulletin',
            'search_value' => $_GET['search'] ?? '',
            'limit' => $perPage,
            'allowed_limits' => $allowedLimits,
            'can_delete' => canDelete(),
            'can_view' => canView(),
            'print_title' => 'Édition bulletin',
        ]); ?>
        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmBulletinTable">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th cm-data-table__th--check">
                                <input type="checkbox" id="cmBulletinCheckAll" class="cm-table-check-all" aria-label="Tout sélectionner">
                            </th>
                            <th class="cm-data-table__th">N Carte</th>
                            <th class="cm-data-table__th">Nom</th>
                            <th class="cm-data-table__th">Prénom</th>
                            <th class="cm-data-table__th">Niv</th>
                            <th class="cm-data-table__th">Moy. Gen</th>
                            <th class="cm-data-table__th">Mention</th>
                            <th class="cm-data-table__th">Statut Bull.</th>
                            <th class="cm-data-table__th is-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cmBulletinBody">
                        <?php if (empty($rowsToShow)): ?>
                            <?php cm_component('ui/empty-state', [
                                'in_table' => true,
                                'colspan' => 9,
                                'title' => '',
                                'message' => 'Aucune ligne disponible pour ces filtres.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($rowsToShow as $row): ?>
                                <?php
                                $searchText = strtolower(
                                    (string) ($row['matricule'] ?? '') . ' ' .
                                    (string) ($row['nom'] ?? '') . ' ' .
                                    (string) ($row['prenom'] ?? '') . ' ' .
                                    (string) ($row['promotion'] ?? '')
                                );
                                $statusText = !empty($row['is_evaluated']) ? 'Genere' : 'En attente';
                                ?>
                                <tr class="cm-data-table__row"
                                    data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-num-etu="<?php echo htmlspecialchars((string) ($row['num_etu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="cm-data-table__td cm-data-table__td--check">
                                        <input type="checkbox" class="cm-table-check-row cm-bulletin-check-row"
                                            value="<?php echo htmlspecialchars((string) ($row['num_etu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                            aria-label="Sélectionner étudiant">
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($row['matricule'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($row['nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($row['prenom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($row['niveau'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td">
                                        <?php echo !empty($row['is_evaluated']) ? number_format((float) ($row['moyenne'] ?? 0), 2, ',', ' ') : '-'; ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($row['mention'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td">
                                        <?php cm_component('ui/badge', [
                                            'text' => $statusText,
                                            'type' => !empty($row['is_evaluated']) ? 'success' : 'warning',
                                        ]); ?>
                                    </td>
                                    <td class="cm-data-table__td is-center">
                                        <div class="cm-table-actions">
                                            <?php if (!empty($row['is_evaluated']) && canView()): ?>
                                                <button type="button" class="cm-btn-action is-view cm-bulletin-pdf"
                                                    data-num-etu="<?php echo htmlspecialchars((string) ($row['num_etu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    title="Generer PDF">
                                                    <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($row['is_evaluated'])): ?>
                                            <button type="button" class="cm-btn-action is-view cm-bulletin-pdf"
                                                data-num-etu="<?php echo htmlspecialchars((string) ($row['num_etu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Generer PDF">
                                                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                    </div>
                    </td>
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
            'param_name' => 'page_bulletin',
        ]);
        ?>
    </div>
    <?php if (canCreate() || canEdit() || canView()): ?>
        <div class="cm-barre-intermediaire">
            <div class="cm-toolbar">
                <div class="cm-toolbar-right">
                    <?php if (canCreate() || canEdit()): ?>
                        <button class="cm-btn is-success" type="button" id="cmBulletinGenerateSelected">
                            <i class="fas fa-file-circle-check" aria-hidden="true"></i>
                            Generer bulletins selectionnes
                        </button>
                    <?php endif; ?>
                    <?php if (canView()): ?>
                        <button class="cm-btn is-info" type="button" id="cmBulletinExportAll">
                            <i class="fas fa-file-export" aria-hidden="true"></i>
                            Exporter tous
                        </button>
                        <button class="cm-btn is-info" type="button" id="cmBulletinPrint">
                            <i class="fas fa-print" aria-hidden="true"></i>
                            Imprimer
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    </div>
    </div>
<?php endif; ?>

<script>
    (function () {
        const searchInput = document.getElementById('cmBulletinSearch');
        const checkAll = document.getElementById('cmBulletinCheckAll');
        const selectAllBtn = document.getElementById('cmBulletinSelectAllBtn');
        const deselectBtn = document.getElementById('cmBulletinDeselectBtn');
        const deleteBtn = document.getElementById('cmBulletinDeleteBtn');
        const printBtn = document.getElementById('cmBulletinPrint');
        const exportAllBtn = document.getElementById('cmBulletinExportAll');
        const generateSelectedBtn = document.getElementById('cmBulletinGenerateSelected');
        function getRows() {
            return Array.from(document.querySelectorAll('#cmBulletinBody .cm-data-table__row'));
        }
        function getVisibleRows() {
            return getRows().filter(function (row) {
                return row.style.display !== 'none';
            });
        }
        function getCheckedRows() {
            return getRows().filter(function (row) {
                const cb = row.querySelector('.cm-bulletin-check-row');
                return cb && cb.checked;
            });
        }
        function updateDeleteState() {
            const checked = getCheckedRows();
            if (deleteBtn) {
                deleteBtn.disabled = checked.length === 0;
                deleteBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i> Supprimer (' + checked.length + ')';
            }
            if (checkAll) {
                const visible = getVisibleRows();
                const checkedVisible = visible.filter(function (row) {
                    const cb = row.querySelector('.cm-bulletin-check-row');
                    return cb && cb.checked;
                });
                checkAll.checked = visible.length > 0 && checkedVisible.length === visible.length;
            }
        }
        function openPv(numEtu) {
            if (!numEtu) {
                return;
            }
            const url = '?page=evaluation_soutenance&action=imprimer_pv&num_etu=' + encodeURIComponent(numEtu);
            window.open(url, '_blank');
        }
        function exportVisibleCsv() {
            const headers = ['N Carte', 'Nom', 'Prénom', 'Niveau', 'Moyenne', 'Mention', 'Statut'];
            const rows = [headers.join(';')];
            getVisibleRows().forEach(function (row) {
                const cells = row.querySelectorAll('.cm-data-table__td');
                if (cells.length < 8) {
                    return;
                }
                const values = [
                    cells[1].innerText.trim(),
                    cells[2].innerText.trim(),
                    cells[3].innerText.trim(),
                    cells[4].innerText.trim(),
                    cells[5].innerText.trim(),
                    cells[6].innerText.trim(),
                    cells[7].innerText.trim()
                ].map(function (value) {
                    return '\"' + value.replace(/\"/g, '\"\"') + '\"';
                });
                rows.push(values.join(';'));
            });
            const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'edition_bulletins.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        }
        document.querySelectorAll('.cm-bulletin-pdf').forEach(function (button) {
            button.addEventListener('click', function () {
                openPv(button.getAttribute('data-num-etu') || '');
            });
        });
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const term = (searchInput.value || '').trim().toLowerCase();
                getRows().forEach(function (row) {
                    const text = row.getAttribute('data-search') || '';
                    row.style.display = term === '' || text.indexOf(term) !== -1 ? '' : 'none';
                });
                updateDeleteState();
            });
        }
        document.addEventListener('change', function (event) {
            if (event.target && event.target.classList.contains('cm-bulletin-check-row')) {
                updateDeleteState();
            }
        });
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                getVisibleRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-bulletin-check-row');
                    if (cb) {
                        cb.checked = checkAll.checked;
                    }
                });
                updateDeleteState();
            });
        }
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function () {
                getVisibleRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-bulletin-check-row');
                    if (cb) {
                        cb.checked = true;
                    }
                });
                updateDeleteState();
            });
        }
        if (deselectBtn) {
            deselectBtn.addEventListener('click', function () {
                getRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-bulletin-check-row');
                    if (cb) {
                        cb.checked = false;
                    }
                });
                updateDeleteState();
            });
        }
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                getCheckedRows().forEach(function (row) {
                    row.remove();
                });
                updateDeleteState();
            });
        }
        if (generateSelectedBtn) {
            generateSelectedBtn.addEventListener('click', function () {
                const selected = getCheckedRows();
                const targets = selected.length > 0 ? selected : getVisibleRows();
                if (targets.length === 0) {
                    window.alert('Aucun bulletin visible.');
                    return;
                }
                targets.forEach(function (row, index) {
                    const num = row.getAttribute('data-num-etu') || '';
                    if (!num) {
                        return;
                    }
                    setTimeout(function () {
                        openPv(num);
                    }, index * 220);
                });
            });
        }
        if (exportAllBtn) {
            exportAllBtn.addEventListener('click', exportVisibleCsv);
        }
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                window.print();
            });
        }
        updateDeleteState();
    })();
</script>