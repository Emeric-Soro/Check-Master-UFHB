<?php
$etudiants = is_array($GLOBALS['etudiants'] ?? null) ? $GLOBALS['etudiants'] : [];
$selectedYearId = isset($GLOBALS['selectedYearId']) && is_numeric($GLOBALS['selectedYearId'])
    ? (int) $GLOBALS['selectedYearId']
    : \AcademicYear::getSelectedIdFromSession();
$selectedYearLabel = trim((string) \AcademicYear::getSelectedLabelFromSession());

$searchValue = trim((string) ($_GET['search'] ?? ''));
if ($searchValue !== '') {
    $needle = function_exists('mb_strtolower') ? mb_strtolower($searchValue, 'UTF-8') : strtolower($searchValue);
    $etudiants = array_values(array_filter($etudiants, static function (array $row) use ($needle): bool {
        $haystack = implode(' ', [
            (string) ($row['matricule'] ?? ''),
            (string) ($row['nom'] ?? ''),
            (string) ($row['prenom'] ?? ''),
            (string) ($row['promotion'] ?? ''),
            (string) ($row['theme'] ?? ''),
            (string) ($row['annee_label'] ?? ''),
        ]);
        $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
        return strpos($haystack, $needle) !== false;
    }));
}

$allowedLimits = [5, 10, 25, 50, 100];
$perPage = max(5, (int) ($_GET['limit_bulletin'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['page_bulletin'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($etudiants), $perPage, $currentPage)
    : [
        'total' => count($etudiants),
        'per_page' => $perPage,
        'current' => 1,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];
$rowsToShow = array_slice($etudiants, (int) ($pagination['offset'] ?? 0), $perPage);
$baseUrl = '?page=edition_bulletin&search=' . urlencode($searchValue) . '&limit_bulletin=' . $perPage;
$canGenerate = (function_exists('canCreate') && canCreate()) || (function_exists('canEdit') && canEdit());
?>

<?php if (!canView()): ?>
    <?php cm_component('ui/alert-box', [
        'type' => 'danger',
        'message' => "Vous n'avez pas l'autorisation d'accéder à cette page.",
    ]); ?>
<?php else: ?>
    <div class="cm-prd3-screen cm-prd3-crud-screen">
        <?php if ($selectedYearLabel !== ''): ?>
            <?php cm_component('ui/alert-box', [
                'type' => 'info',
                'message' => 'Édition des PV finaux - année académique : ' . $selectedYearLabel,
            ]); ?>
        <?php endif; ?>

        <div id="cmBulletinAlert"></div>
        <form id="cmBulletinApiForm" style="display:none;">
            <?php cm_component('form/csrf-token'); ?>
        </form>

        <div class="cm-crud-wrapper">
            <?php cm_toolbar([
                'screen' => 'edition_bulletin',
                'id_prefix' => 'cmBulletin',
                'search_value' => $searchValue,
                'search_placeholder' => 'Rechercher un étudiant, matricule, thème...',
                'limit' => $perPage,
                'limit_options' => $allowedLimits,
                'limit_name' => 'limit_bulletin',
                'can_delete' => false,
                'can_view' => canView(),
                'print_title' => 'Édition des PV finaux',
                'custom_actions' => $canGenerate ? [[
                    'tag' => 'button',
                    'id' => 'cmBulletinGenerateSelected',
                    'label' => 'Générer PV sélection',
                    'icon' => 'fa-file-circle-check',
                    'class' => 'cm-btn is-success is-sm',
                    'attrs' => ['title' => 'Générer les PV finaux des lignes sélectionnées (ou visibles)'],
                ]] : [],
            ]); ?>

            <div class="cm-pole-inferieur">
                <div class="cm-table-wrapper">
                    <table class="cm-data-table" id="cmBulletinTable">
                        <thead>
                        <tr>
                            <th class="cm-data-table__th cm-data-table__th--check">
                                <input type="checkbox" id="cmBulletinCheckAll" class="cm-table-check-all" aria-label="Tout sélectionner">
                            </th>
                            <th class="cm-data-table__th">Matricule</th>
                            <th class="cm-data-table__th">Nom</th>
                            <th class="cm-data-table__th">Prénom</th>
                            <th class="cm-data-table__th">Promotion</th>
                            <th class="cm-data-table__th">Thème</th>
                            <th class="cm-data-table__th">Moyenne</th>
                            <th class="cm-data-table__th">Mention</th>
                            <th class="cm-data-table__th">Statut</th>
                            <th class="cm-data-table__th is-center is-actions">Actions</th>
                        </tr>
                        </thead>
                        <tbody id="cmBulletinBody">
                        <?php if (empty($rowsToShow)): ?>
                            <?php cm_component('ui/empty-state', [
                                'in_table' => true,
                                'colspan' => 10,
                                'title' => '',
                                'message' => $selectedYearId !== null
                                    ? 'Aucune soutenance programmée trouvée pour cette année.'
                                    : 'Aucune soutenance programmée trouvée.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($rowsToShow as $row): ?>
                                <?php
                                $status = (string) ($row['status'] ?? 'non_eligible');
                                $isEligible = !empty($row['eligible']);
                                $hasBulletin = !empty($row['has_bulletin']);
                                $bulletinId = trim((string) ($row['bulletin_id'] ?? ''));
                                if ($bulletinId === '' && !empty($row['soutenance_id'])) {
                                    $bulletinId = trim((string) $row['soutenance_id']);
                                }
                                $statusLabel = 'Non éligible';
                                $statusType = 'danger';
                                if ($status === 'genere') {
                                    $statusLabel = 'Généré';
                                    $statusType = 'success';
                                } elseif ($status === 'eligible') {
                                    $statusLabel = 'Éligible';
                                    $statusType = 'warning';
                                }

                                $searchText = strtolower(implode(' ', [
                                    (string) ($row['matricule'] ?? ''),
                                    (string) ($row['nom'] ?? ''),
                                    (string) ($row['prenom'] ?? ''),
                                    (string) ($row['promotion'] ?? ''),
                                    (string) ($row['theme'] ?? ''),
                                ]));
                                $displayMoyenne = !empty($row['evaluation_complete'])
                                    ? number_format((float) ($row['moyenne'] ?? 0), 2, ',', ' ')
                                    : '-';
                                ?>
                                <tr class="cm-data-table__row"
                                    data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-num-etu="<?php echo htmlspecialchars((string) ($row['num_etu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-bulletin-id="<?php echo htmlspecialchars($bulletinId, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="cm-data-table__td cm-data-table__td--check">
                                        <input type="checkbox"
                                               class="cm-table-check-row cm-bulletin-check-row"
                                               value="<?php echo htmlspecialchars((string) ($row['num_etu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                               aria-label="Sélectionner étudiant">
                                    </td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['matricule'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['prenom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['promotion'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['theme'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars($displayMoyenne, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars((string) ($row['mention'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="cm-data-table__td">
                                        <?php cm_component('ui/badge', ['text' => $statusLabel, 'type' => $statusType]); ?>
                                    </td>
                                    <td class="cm-data-table__td is-center">
                                        <div class="cm-table-actions">
                                            <?php if ($canGenerate && $isEligible): ?>
                                                <button type="button"
                                                        class="cm-btn-action is-edit cm-bulletin-generate"
                                                        data-num-etu="<?php echo htmlspecialchars((string) ($row['num_etu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                        title="<?php echo $hasBulletin ? 'Regénérer le PV final' : 'Générer le PV final'; ?>">
                                                    <i class="fas fa-file-circle-check" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($hasBulletin && $bulletinId !== ''): ?>
                                                <button type="button"
                                                        class="cm-btn-action is-view cm-bulletin-download"
                                                        data-bulletin-id="<?php echo htmlspecialchars($bulletinId, ENT_QUOTES, 'UTF-8'); ?>"
                                                        title="Télécharger le PV final PDF">
                                                    <i class="fas fa-download" aria-hidden="true"></i>
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

                <?php cm_component('crud/pagination', [
                    'pagination' => $pagination,
                    'base_url' => $baseUrl,
                    'param_name' => 'page_bulletin',
                ]); ?>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const checkAll = document.getElementById('cmBulletinCheckAll');
            const selectAllBtn = document.getElementById('cmBulletin_selectAll');
            const deselectBtn = document.getElementById('cmBulletin_deselectAll');
            const generateBatchBtn = document.getElementById('cmBulletinGenerateSelected');

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
                    return !!cb && cb.checked;
                });
            }

            function getCsrfToken() {
                const scoped = document.querySelector('#cmBulletinApiForm input[name="csrf_token"]');
                if (scoped) {
                    return String(scoped.value || '');
                }
                const fallback = document.querySelector('input[name="csrf_token"]');
                return fallback ? String(fallback.value || '') : '';
            }

            function updateSelectionState() {
                if (!checkAll) {
                    return;
                }
                const visibleRows = getVisibleRows();
                if (visibleRows.length === 0) {
                    checkAll.checked = false;
                    return;
                }
                const checkedVisible = visibleRows.filter(function (row) {
                    const cb = row.querySelector('.cm-bulletin-check-row');
                    return !!cb && cb.checked;
                });
                checkAll.checked = checkedVisible.length === visibleRows.length;
            }

            async function postAction(action, payload) {
                const body = new URLSearchParams();
                body.append('csrf_token', getCsrfToken());
                Object.keys(payload).forEach(function (key) {
                    body.append(key, payload[key]);
                });

                const response = await fetch('?page=edition_bulletin&action=' + encodeURIComponent(action), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body.toString()
                });

                const text = await response.text();
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Réponse invalide du serveur.');
                }
                return data;
            }

            function triggerDownload(bulletinId) {
                if (!bulletinId) {
                    return;
                }
                window.location.href = '?page=edition_bulletin&action=download&id=' + encodeURIComponent(String(bulletinId));
            }

            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    getVisibleRows().forEach(function (row) {
                        const cb = row.querySelector('.cm-bulletin-check-row');
                        if (cb) {
                            cb.checked = checkAll.checked;
                        }
                    });
                    updateSelectionState();
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
                    updateSelectionState();
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
                    updateSelectionState();
                });
            }

            document.addEventListener('change', function (event) {
                if (event.target && event.target.classList.contains('cm-bulletin-check-row')) {
                    updateSelectionState();
                }
            });

            document.addEventListener('click', async function (event) {
                const generateBtn = event.target.closest('.cm-bulletin-generate');
                if (generateBtn) {
                    const numEtu = generateBtn.getAttribute('data-num-etu') || '';
                    if (!numEtu) {
                        return;
                    }
                    generateBtn.disabled = true;
                    try {
                        const result = await postAction('generate', {num_etu: numEtu});
                        if (!result || !result.success) {
                            window.alert((result && result.message) ? result.message : 'La génération a échoué.');
                            return;
                        }
                        window.alert(result.message || 'PV final généré avec succès.');
                        window.location.reload();
                    } catch (error) {
                        window.alert(error instanceof Error ? error.message : 'Erreur lors de la génération.');
                    } finally {
                        generateBtn.disabled = false;
                    }
                    return;
                }

                const downloadBtn = event.target.closest('.cm-bulletin-download');
                if (downloadBtn) {
                    const bulletinId = downloadBtn.getAttribute('data-bulletin-id') || '';
                    triggerDownload(bulletinId);
                }
            });

            if (generateBatchBtn) {
                generateBatchBtn.addEventListener('click', async function () {
                    const selectedRows = getCheckedRows();
                    const targetRows = selectedRows.length > 0 ? selectedRows : getVisibleRows();
                    const etudiants = targetRows
                        .map(function (row) {
                            return String(row.getAttribute('data-num-etu') || '').trim();
                        })
                        .filter(function (value, index, array) {
                            return value !== '' && array.indexOf(value) === index;
                        });

                    if (etudiants.length === 0) {
                        window.alert('Aucun étudiant sélectionné ou visible.');
                        return;
                    }

                    generateBatchBtn.disabled = true;
                    try {
                        const result = await postAction('generate_batch', {etudiants: etudiants.join(',')});
                        if (!result || !result.success) {
                            window.alert((result && result.message) ? result.message : 'La génération en lot a échoué.');
                            return;
                        }

                        const details = result.results && typeof result.results === 'object' ? result.results : {};
                        let successCount = 0;
                        let failCount = 0;
                        Object.keys(details).forEach(function (numEtu) {
                            const rowResult = details[numEtu];
                            if (rowResult && rowResult.success) {
                                successCount++;
                            } else {
                                failCount++;
                            }
                        });

                        if (successCount > 0) {
                            let message = successCount + ' PV final(aux) généré(s).';
                            if (failCount > 0) {
                                message += ' ' + failCount + ' échec(s).';
                            }
                            window.alert(message);
                            window.location.reload();
                            return;
                        }

                        window.alert('Aucun PV final n\'a pu être généré.');
                    } catch (error) {
                        window.alert(error instanceof Error ? error.message : 'Erreur lors de la génération en lot.');
                    } finally {
                        generateBatchBtn.disabled = false;
                    }
                });
            }

            updateSelectionState();
        })();
    </script>
<?php endif; ?>
