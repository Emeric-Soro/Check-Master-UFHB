<?php
require_once __DIR__ . '/../../app/controllers/EvaluationSoutenanceController.php';

$controller = new EvaluationSoutenanceController();
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$message = '';
$messageType = 'success';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'evaluer') {
        $result = $controller->enregistrerEvaluation();
        $message = (string) ($result['message'] ?? '');
        $messageType = !empty($result['success']) ? 'success' : 'error';
    } elseif ($_POST['action'] === 'supprimer') {
        $result = $controller->supprimerEvaluation();
        $message = (string) ($result['message'] ?? '');
        $messageType = !empty($result['success']) ? 'success' : 'error';
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => $messageType === 'success',
            'message' => $message,
        ]);
        exit;
    }
}

$soutenances = $controller->getSoutenancesProgrammeesForView();
$criteres = $controller->getCriteresEvaluation();

$anneesAcademiques = $controller->getAnneesAcademiques();
$anneeAcademiqueCourante = $controller->getAnneeAcademiqueCourante();

$evaluatedRows = array_values(array_filter($soutenances, static function (array $row): bool {
    return (int) ($row['est_evalue'] ?? 0) > 0;
}));

$allowedLimits = [5, 10, 25, 50];
$perPage = max(5, (int) ($_GET['limit_eval_sout'] ?? 10));
if (!in_array($perPage, $allowedLimits, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['page_eval_sout'] ?? 1));
$pagination = function_exists('cm_paginate')
    ? cm_paginate(count($evaluatedRows), $perPage, $currentPage)
    : [
        'total' => count($evaluatedRows),
        'per_page' => $perPage,
        'current' => 1,
        'last' => 1,
        'offset' => 0,
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];
$rowsToShow = array_slice($evaluatedRows, (int) ($pagination['offset'] ?? 0), $perPage);
$baseUrl = '?page=evaluation_soutenance&limit_eval_sout=' . $perPage;

$anneeOptions = [];
foreach ($anneesAcademiques as $annee) {
    $id = (string) ($annee['id_annee_acad'] ?? '');
    if ($id === '') {
        continue;
    }
    $anneeOptions[$id] = (string) ($annee['lib_annee'] ?? $id);
}

$soutenanceOptions = [];
foreach ($soutenances as $soutenance) {
    $num = (string) ($soutenance['num_etu'] ?? '');
    if ($num === '') {
        continue;
    }
    $label = trim((string) ($soutenance['nom_etudiant'] ?? 'Etudiant')) . ' - ' . trim((string) ($soutenance['matricule_etudiant'] ?? $num));
    $soutenanceOptions[$num] = $label;
}
?>

<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php if ($message !== ''): ?>
        <?php cm_component('ui/alert-box', [
            'type' => $messageType === 'success' ? 'success' : 'danger',
            'message' => $message,
        ]); ?>
    <?php endif; ?>

    <div id="cmEvalSoutAlert"></div>

    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur is-compact">
            <form id="cmEvalSoutForm" method="POST" action="?page=evaluation_soutenance" data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" name="action" value="evaluer">
                <input type="hidden" name="num_etu" id="cmEvalNumEtu" value="">

                <div class="cm-grid-2">
                    <?php
                    cm_component('form/select', [
                        'name' => 'cm_eval_soutenance',
                        'id' => 'cmEvalSoutenanceSelect',
                        'label' => 'Etudiant',
                        'required' => true,
                        'options' => $soutenanceOptions,
                        'control_class' => 'cm-field-lg cm-size-personne',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_eval_promotion',
                        'id' => 'cmEvalPromotion',
                        'label' => 'Promotion',
                        'readonly' => true,
                        'control_class' => 'cm-field-sm cm-size-salle',
                    ]);
                    ?>
                </div>
                <?php
                cm_component('form/textarea', [
                    'name' => 'cm_eval_theme',
                    'id' => 'cmEvalTheme',
                    'label' => 'Theme',
                    'readonly' => true,
                    'rows' => 2,
                    'control_class' => 'cm-field-full cm-size-theme',
                ]);
                ?>

                <p class="cm-text-sm cm-text-muted cm-m-0" id="cmEvalSelectedLabel">Soutenance selectionnee: -</p>
                <div class="cm-grid-3">
                    <?php
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_president',
                        'id' => 'cmProgPresident',
                        'label' => 'President du jury',
                        'required' => true,
                        'readonly' => true,
                        'control_class' => 'cm-field-lg cm-size-personne',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_examinateur',
                        'id' => 'cmProgExaminateur',
                        'label' => 'Examinateur',
                        'required' => true,
                        'readonly' => true,
                        'control_class' => 'cm-field-lg cm-size-personne',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_directeur',
                        'id' => 'cmProgDirecteur',
                        'label' => 'Directeur de mémoire',
                        'readonly' => true,
                        'control_class' => 'cm-field-lg cm-size-personne',
                    ]);
                    ?>
                </div>
                <div class="cm-grid-2">
                    <?php
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_encadreur',
                        'id' => 'cmProgEncadreur',
                        'label' => 'Encadreur Pédagogique.',
                        'readonly' => true,
                        'control_class' => 'cm-field-lg cm-size-personne',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_maitre',
                        'id' => 'cmProgMaitreStage',
                        'label' => 'Maître stage',
                        'readonly' => true,
                        'control_class' => 'cm-field-lg cm-size-personne',
                    ]);
                    ?>
                </div>
                <?php
                $criteriaForGrid = array_map(static function (array $c): array {
                    return [
                        'id' => (int) ($c['id_critere'] ?? 0),
                        'label' => (string) ($c['lib_critere'] ?? ''),
                        'abbrev' => (string) ($c['code_critere'] ?? ''),
                        'bareme' => (float) ($c['bareme_max'] ?? 20),
                    ];
                }, $criteres);
                cm_component('ui/evaluation-grid', [
                    'criteria' => $criteriaForGrid,
                    'name_prefix' => 'criteres',
                    'id_prefix' => 'cmEval',
                    'commentaire_name' => 'commentaire_general',
                    'commentaire_label' => 'Commentaire general',
                    'show_header' => false,
                    'show_buttons' => false,
                    'show_commentaire' => false,
                ]);
                ?>

                <div class="cm-grid-2">
                    <div class="cm-form-group">
                        <?php cm_component('form/select', [
                            'name' => 'cm_eval_decision',
                            'id' => 'cmEvalDecision',
                            'label' => 'Decision',
                            'options' => ['admis' => 'Admis', 'ajourne' => 'Ajourne'],
                            'control_class' => 'cm-field-sm cm-size-salle',
                        ]); ?>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label" for="cmEvalComment">Commentaire general</label>
                        <textarea id="cmEvalComment" name="commentaire_general" class="cm-form-control cm-field-full cm-size-commentaire"
                            rows="2"></textarea>
                    </div>
                </div>

                <div class="cm-form-buttons has-cancel is-dense">
                    <button class="cm-btn is-light" type="button" id="cmEvalCancelBtn" onclick="document.getElementById('cmEvalResetBtn').click();">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        Annuler
                    </button>
                    <div class="cm-form-buttons__right">
                        <button class="cm-btn is-light" type="button" id="cmEvalResetBtn">
                            <i class="fas fa-rotate-left" aria-hidden="true"></i>
                            Reinitialiser
                        </button>
                        <button class="cm-btn is-success" type="submit" id="cmEvalSubmitBtn">
                            <i class="fas fa-check" aria-hidden="true"></i>
                            Enregistrer evaluation
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="cm-barre-intermediaire">
            <div class="cm-toolbar">
                <div class="cm-toolbar-left">
                    <label for="cmEvalSoutLimit"><strong>Afficher:</strong></label>
                    <select id="cmEvalSoutLimit" class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs"
                        data-cm-ajax-param="limit_eval_sout" data-cm-ajax-reset-param="page_eval_sout"
                        data-cm-ajax-reset-value="1">
                        <?php foreach ($allowedLimits as $limit): ?>
                            <option value="<?php echo $limit; ?>" <?php echo $limit === $perPage ? 'selected' : ''; ?>>
                                <?php echo $limit; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" id="cmEvalSoutSearch" class="cm-form-control cm-toolbar-field-lg"
                        placeholder="Rechercher une soutenance...">
                </div>

                <div class="cm-toolbar-center">
                    <button type="button" class="cm-btn is-info is-sm" id="cmEvalSoutSelectAllBtn">
                        <i class="fas fa-square-check" aria-hidden="true"></i>
                        Select. tout
                    </button>
                    <button type="button" class="cm-btn is-light is-sm" id="cmEvalSoutDeselectBtn">
                        <i class="fas fa-square" aria-hidden="true"></i>
                        Deselect.
                    </button>
                    <button type="button" class="cm-btn is-light is-sm" id="cmEvalSoutDeleteBtn" disabled>
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        Supprimer (0)
                    </button>
                    <button type="button" class="cm-btn is-info is-sm" id="cmEvalSoutExport">
                        <i class="fas fa-file-export" aria-hidden="true"></i>
                        Export
                    </button>
                </div>

                <div class="cm-toolbar-right">
                    <button type="button" class="cm-btn is-info is-sm" id="cmEvalSoutPrint">
                        <i class="fas fa-print" aria-hidden="true"></i>
                        Imprimer
                    </button>
                </div>
            </div>
        </div>

        <div class="cm-pole-inferieur">
            <div class="cm-text-sm cm-text-semibold cm-px-sm">Soutenances evaluees</div>
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmEvalSoutTable">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th cm-data-table__th--check">
                                <input type="checkbox" id="cmEvalSoutCheckAll" aria-label="Tout selectionner">
                            </th>
                            <th class="cm-data-table__th">N</th>
                            <th class="cm-data-table__th">Etudiant</th>
                            <th class="cm-data-table__th">Date sout.</th>
                            <th class="cm-data-table__th">Moyenne</th>
                            <th class="cm-data-table__th">Mention</th>
                            <th class="cm-data-table__th">Commentaire</th>
                            <th class="cm-data-table__th is-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cmEvalSoutTableBody">
                        <?php if (empty($rowsToShow)): ?>
                            <?php cm_component('ui/empty-state', [
                                'in_table' => true,
                                'colspan' => 8,
                                'title' => 'Aucune soutenance',
                                'message' => 'Aucune soutenance programmee disponible.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($rowsToShow as $index => $soutenance): ?>
                                <?php
                                $numEtu = (string) ($soutenance['num_etu'] ?? '');
                                $isEvaluated = (int) ($soutenance['est_evalue'] ?? 0) > 0;
                                $moyenne = (float) ($soutenance['note_finale'] ?? 0);
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
                                $searchText = strtolower(
                                    (string) ($soutenance['nom_etudiant'] ?? '') . ' ' .
                                    (string) ($soutenance['matricule_etudiant'] ?? '') . ' ' .
                                    (string) ($soutenance['theme_soutenance'] ?? '')
                                );
                                $dateHeure = '-';
                                if (!empty($soutenance['date_soutenance']) && !empty($soutenance['heure_soutenance'])) {
                                    $dateHeure = date('d/m/Y', strtotime((string) $soutenance['date_soutenance'])) . ' ' . date('H:i', strtotime((string) $soutenance['heure_soutenance']));
                                }
                                $commentaireLigne = trim((string) ($soutenance['commentaire_general'] ?? ''));
                                ?>
                                <tr class="cm-data-table__row"
                                    data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-num-etu="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="cm-data-table__td cm-data-table__td--check">
                                        <input type="checkbox" class="cm-eval-sout-check-row"
                                            value="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                            aria-label="Selectionner ligne <?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>">
                                    </td>
                                    <td class="cm-data-table__td"><?php echo (int) ($pagination['offset'] ?? 0) + $index + 1; ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($soutenance['nom_etudiant'] ?? 'Etudiant'), ENT_QUOTES, 'UTF-8'); ?><br>
                                        <small><?php echo htmlspecialchars((string) ($soutenance['matricule_etudiant'] ?? $numEtu), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($dateHeure, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo $isEvaluated ? number_format($moyenne, 2, ',', ' ') : '-'; ?>
                                    </td>
                                    <td class="cm-data-table__td"><?php echo htmlspecialchars($mention, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($commentaireLigne !== '' ? $commentaireLigne : '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td is-center">
                                        <div class="cm-table-actions">
                                            <button type="button" class="cm-btn-action is-edit cm-eval-open"
                                                data-num-etu="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Saisir / modifier">
                                                <i class="fas fa-pen" aria-hidden="true"></i>
                                            </button>
                                            <?php if ($isEvaluated): ?>
                                                <button type="button" class="cm-btn-action is-view cm-eval-print-pv"
                                                    data-num-etu="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                                    title="Generer PV">
                                                    <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (function_exists('canDelete') ? canDelete() : true): ?>
                                                <button type="button" class="cm-btn-action is-delete cm-eval-sout-delete-one"
                                                    data-num-etu="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                                    title="Supprimer">
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
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
                'param_name' => 'page_eval_sout',
            ]);
            ?>
        </div>
    </div>
</div>

<script>
    (function () {
        const criteresInit = <?php echo json_encode($criteres, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const soutenances = <?php echo json_encode($soutenances, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const anneeSelect = document.getElementById('cmEvalAnnee');
        const soutenanceSelect = document.getElementById('cmEvalSoutenanceSelect');
        const numEtuInput = document.getElementById('cmEvalNumEtu');
        const moyenneInput = document.getElementById('cmEvalMoyenne');
        const decisionSelect = document.getElementById('cmEvalDecision');
        const themeInput = document.getElementById('cmEvalTheme');
        const salleInput = document.getElementById('cmEvalSalle');
        const dateTimeInput = document.getElementById('cmEvalDateTime');
        const juryInput = document.getElementById('cmEvalJury');
        const selectedLabel = document.getElementById('cmEvalSelectedLabel');
        const resetBtn = document.getElementById('cmEvalResetBtn');
        const alertBox = document.getElementById('cmEvalSoutAlert');
        const searchInput = document.getElementById('cmEvalSoutSearch');
        const tableBody = document.getElementById('cmEvalSoutTableBody');
        const exportBtn = document.getElementById('cmEvalSoutExport');
        const printBtn = document.getElementById('cmEvalSoutPrint');
        const checkAll = document.getElementById('cmEvalSoutCheckAll');
        const selectAllBtn = document.getElementById('cmEvalSoutSelectAllBtn');
        const deselectBtn = document.getElementById('cmEvalSoutDeselectBtn');
        const deleteBtn = document.getElementById('cmEvalSoutDeleteBtn');

        function setAlert(type, message) {
            if (!alertBox) {
                return;
            }
            const cssType = type === 'success' ? 'success' : 'danger';
            alertBox.innerHTML = '<div class="cm-alert is-' + cssType + '"><div class="cm-alert__content"><span class="cm-alert__message">' +
                String(message || '').replace(/[<>&]/g, '') +
                '</span></div></div>';
        }

        function getRows() {
            return Array.from(document.querySelectorAll('#cmEvalSoutTableBody .cm-data-table__row'));
        }

        function getVisibleRows() {
            return getRows().filter(function (row) {
                return row.style.display !== 'none';
            });
        }

        function getCheckedRows() {
            return getRows().filter(function (row) {
                const cb = row.querySelector('.cm-eval-sout-check-row');
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
                    const cb = row.querySelector('.cm-eval-sout-check-row');
                    return cb && cb.checked;
                });
                checkAll.checked = visible.length > 0 && checkedVisible.length === visible.length;
            }
        }

        function deleteEvaluation(numEtu) {
            const formData = new FormData();
            formData.append('action', 'supprimer');
            formData.append('num_etu', numEtu);

            const formToken = document.querySelector('#cmEvalSoutForm input[name=\"csrf_token\"]');
            if (formToken && formToken.value) {
                formData.append('csrf_token', formToken.value);
            }

            return fetch('?page=evaluation_soutenance', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).then(function (response) {
                return response.json();
            });
        }

        function mentionFromNote(note) {
            if (note >= 10) {
                return 'admis';
            }
            return 'ajourne';
        }

        function getMentionLabel(note) {
            if (note >= 18) return 'Honorable';
            if (note >= 16) return 'Très Bien';
            if (note >= 14) return 'Bien';
            if (note >= 12) return 'Assez Bien';
            if (note >= 10) return 'Passable';
            return 'Insuffisant';
        }

        function recalcMoyenne() {
            const evalGrid = document.getElementById('cmEval');
            if (!evalGrid) { return; }
            const mentionDisplay = document.getElementById('cmEvalMention');
            let total = 0, allFilled = true;
            evalGrid.querySelectorAll('.cm-eval-grid__row').forEach(function (row) {
                const bareme = parseFloat(row.dataset.bareme) || 0;
                const input = row.querySelector('.cm-eval-grid__note-field');
                const val = input ? parseFloat(input.value) : NaN;
                if (!isNaN(val) && val >= 0 && val <= bareme) {
                    total += val;
                } else {
                    allFilled = false;
                }
            });
            if (moyenneInput) {
                moyenneInput.value = allFilled ? total.toFixed(2) : '';
            }
            if (decisionSelect) {
                decisionSelect.value = allFilled ? mentionFromNote(total) : 'ajourne';
            }
            if (mentionDisplay) {
                if (allFilled) {
                    const mention = getMentionLabel(total);
                    mentionDisplay.textContent = mention;
                    mentionDisplay.className = 'cm-eval-grid__mention-display cm-eval-grid__mention-display--active';
                } else {
                    mentionDisplay.textContent = '—';
                    mentionDisplay.className = 'cm-eval-grid__mention-display';
                }
            }
        }

        function getSoutenanceByNumEtu(numEtu) {
            return (soutenances || []).find(function (item) {
                return String(item.num_etu || '') === String(numEtu || '');
            }) || null;
        }

        function fillSoutenanceInfo(numEtu) {
            const info = getSoutenanceByNumEtu(numEtu);
            if (!info) {
                if (themeInput) themeInput.value = '';
                if (salleInput) salleInput.value = '';
                if (dateTimeInput) dateTimeInput.value = '';
                if (juryInput) juryInput.value = '';
                if (selectedLabel) selectedLabel.textContent = 'Soutenance selectionnee: -';
                return;
            }

            if (themeInput) themeInput.value = info.theme_soutenance || '';
            if (salleInput) salleInput.value = info.nom_salle || '';

            const datePart = info.date_soutenance ? new Date(info.date_soutenance).toLocaleDateString('fr-FR') : '';
            const heurePart = info.heure_soutenance ? String(info.heure_soutenance).slice(0, 5) : '';
            if (dateTimeInput) dateTimeInput.value = (datePart + ' ' + heurePart).trim();
            if (juryInput) {
                juryInput.value = ((info.president_nom || '-') + ' / ' + (info.examinateur_nom || '-'));
            }
            if (selectedLabel) {
                selectedLabel.textContent = 'Soutenance selectionnee: ' + (info.nom_etudiant || 'Etudiant') + ' - ' + (datePart || '-');
            }

            const commentaireEl = document.getElementById('cmEvalComment');
            if (commentaireEl) {
                commentaireEl.value = info.commentaire_general || '';
            }

            if (info.est_evalue > 0) {
                fetch('?page=evaluation_soutenance&action=getEvaluationExistante&num_etu=' + encodeURIComponent(numEtu), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (response) { return response.json(); })
                    .then(function (rows) {
                        if (!Array.isArray(rows)) {
                            return;
                        }
                        rows.forEach(function (row) {
                            const input = document.getElementById('cmEvalCrit' + String(row.id_critere || ''));
                            if (input) {
                                input.value = row.note;
                            }
                        });
                        recalcMoyenne();
                    });
            } else {
                document.querySelectorAll('#cmEval .cm-eval-grid__note-field').forEach(function (input) {
                    input.value = '';
                    input.classList.remove('is-invalid');
                });
                recalcMoyenne();
            }
        }

        function rebuildGridRows(criteres) {
            const evalGrid = document.getElementById('cmEval');
            if (!evalGrid) { return; }
            const fieldsWrap = evalGrid.querySelector('.cm-eval-grid__fields-wrap');
            if (!fieldsWrap) { return; }
            // Garder la ligne total (qui contient maintenant total + mention)
            const totalRow = fieldsWrap.querySelector('.cm-eval-grid__total-row');
            fieldsWrap.innerHTML = '';
            (criteres || []).forEach(function (c) {
                const id = String(c.id_critere || '');
                const label = String(c.lib_critere || ('Critere ' + id));
                const abbrev = c.code_critere ? ' <span class="cm-eval-grid__abbrev">(' + c.code_critere + ')</span>' : '';
                const bareme = parseFloat(c.bareme_max || 0);
                const div = document.createElement('div');
                div.className = 'cm-eval-grid__row';
                div.dataset.bareme = String(bareme);
                div.innerHTML = '<label class="cm-eval-grid__label" for="cmEvalCrit' + id + '">' + label + abbrev + '</label>' +
                    '<div class="cm-eval-grid__input-group">' +
                    '<input type="number" id="cmEvalCrit' + id + '" name="criteres[' + id + ']" ' +
                    'class="cm-eval-grid__note-input cm-eval-grid__note-field" value="" ' +
                    'min="0" max="' + bareme + '" step="0.5" placeholder="\u2014" data-critere-id="' + id + '">' +
                    '<span class="cm-eval-grid__note-unit">/' + Math.round(bareme) + '</span></div>';
                fieldsWrap.appendChild(div);
            });
            // Rajouter la ligne total à la fin
            if (totalRow) {
                fieldsWrap.appendChild(totalRow);
            }
            fieldsWrap.querySelectorAll('.cm-eval-grid__note-field').forEach(function (input) {
                input.addEventListener('input', recalcMoyenne);
            });
            recalcMoyenne();
        }

        function reloadCriteriaByYear(idAnnee) {
            if (!idAnnee) {
                return;
            }
            fetch('?page=evaluation_soutenance&action=getCriteresParAnnee&id_annee_acad=' + encodeURIComponent(idAnnee), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload || !payload.success || !Array.isArray(payload.data)) {
                        return;
                    }
                    rebuildGridRows(payload.data);
                    if (soutenanceSelect && soutenanceSelect.value) {
                        fillSoutenanceInfo(soutenanceSelect.value);
                    }
                });
        }

        if (anneeSelect) {
            anneeSelect.addEventListener('change', function () {
                reloadCriteriaByYear(anneeSelect.value);
            });
        }

        if (soutenanceSelect) {
            soutenanceSelect.addEventListener('change', function () {
                const numEtu = soutenanceSelect.value || '';
                if (numEtuInput) {
                    numEtuInput.value = numEtu;
                }
                fillSoutenanceInfo(numEtu);
            });
        }

        document.querySelectorAll('.cm-eval-open').forEach(function (button) {
            button.addEventListener('click', function () {
                const numEtu = button.getAttribute('data-num-etu') || '';
                if (!numEtu || !soutenanceSelect) {
                    return;
                }
                soutenanceSelect.value = numEtu;
                if (numEtuInput) {
                    numEtuInput.value = numEtu;
                }
                fillSoutenanceInfo(numEtu);
                if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                    // noop
                }
            });
        });

        document.querySelectorAll('.cm-eval-print-pv').forEach(function (button) {
            button.addEventListener('click', function () {
                const numEtu = button.getAttribute('data-num-etu') || '';
                if (!numEtu) {
                    return;
                }
                const url = '?page=evaluation_soutenance&action=imprimer_pv&num_etu=' + encodeURIComponent(numEtu);
                window.open(url, '_blank');
            });
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (soutenanceSelect) {
                    soutenanceSelect.value = '';
                }
                if (numEtuInput) {
                    numEtuInput.value = '';
                }
                if (themeInput) themeInput.value = '';
                if (salleInput) salleInput.value = '';
                if (dateTimeInput) dateTimeInput.value = '';
                if (juryInput) juryInput.value = '';
                if (decisionSelect) decisionSelect.value = 'admis';
                const commentaire = document.getElementById('cmEvalComment');
                if (commentaire) commentaire.value = '';
                document.querySelectorAll('#cmEval .cm-eval-grid__note-field').forEach(function (input) {
                    input.value = '';
                    input.classList.remove('is-invalid');
                });
                recalcMoyenne();
            });
        }

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

        if (tableBody) {
            tableBody.addEventListener('change', function (event) {
                if (event.target && event.target.classList.contains('cm-eval-sout-check-row')) {
                    updateDeleteState();
                }
            });
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                getVisibleRows().forEach(function (row) {
                    const cb = row.querySelector('.cm-eval-sout-check-row');
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
                    const cb = row.querySelector('.cm-eval-sout-check-row');
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
                    const cb = row.querySelector('.cm-eval-sout-check-row');
                    if (cb) {
                        cb.checked = false;
                    }
                });
                updateDeleteState();
            });
        }

        document.querySelectorAll('.cm-eval-sout-delete-one').forEach(function (button) {
            button.addEventListener('click', async function () {
                const numEtu = button.getAttribute('data-num-etu') || '';
                if (!numEtu) {
                    return;
                }
                const confirmed = await window.CM.confirm({
                    title: 'Suppression',
                    message: 'Supprimer cette evaluation ?',
                    type: 'danger',
                    confirmText: 'Supprimer',
                });
                if (!confirmed) {
                    return;
                }

                deleteEvaluation(numEtu)
                    .then(function (payload) {
                        if (!payload || !payload.success) {
                            setAlert('error', payload && payload.message ? payload.message : 'Suppression impossible.');
                            return;
                        }
                        setAlert('success', payload.message || 'Evaluation supprimee.');
                        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                            window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(function () {
                        setAlert('error', 'Erreur reseau.');
                    });
            });
        });

        if (deleteBtn) {
            deleteBtn.addEventListener('click', async function () {
                const nums = getCheckedRows().map(function (row) {
                    const cb = row.querySelector('.cm-eval-sout-check-row');
                    return cb ? cb.value : '';
                }).filter(Boolean);

                if (nums.length === 0) {
                    return;
                }
                const confirmed = await window.CM.confirm({
                    title: 'Suppression multiple',
                    message: 'Supprimer ' + nums.length + ' evaluation(s) ?',
                    type: 'danger',
                    confirmText: 'Supprimer',
                });
                if (!confirmed) {
                    return;
                }

                Promise.all(nums.map(deleteEvaluation))
                    .then(function (results) {
                        const failed = results.filter(function (item) {
                            return !item || !item.success;
                        });
                        if (failed.length > 0) {
                            setAlert('error', 'Certaines suppressions ont echoue.');
                        } else {
                            setAlert('success', 'Suppressions effectuees.');
                        }
                        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                            window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(function () {
                        setAlert('error', 'Erreur reseau.');
                    });
            });
        }

        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                const headers = ['N', 'Etudiant', 'Date soutenance', 'Moyenne', 'Mention', 'Commentaire'];
                const csvRows = [headers.join(';')];

                getVisibleRows().forEach(function (row) {
                    const cells = row.querySelectorAll('.cm-data-table__td');
                    if (cells.length < 8) {
                        return;
                    }
                    const values = [
                        cells[1].innerText.trim(),
                        cells[2].innerText.trim().replace(/\s+/g, ' '),
                        cells[3].innerText.trim(),
                        cells[4].innerText.trim(),
                        cells[5].innerText.trim(),
                        cells[6].innerText.trim()
                    ].map(function (value) {
                        return '\"' + value.replace(/\"/g, '\"\"') + '\"';
                    });
                    csvRows.push(values.join(';'));
                });

                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'soutenances_evaluees.csv';
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            });
        }

        if (printBtn) {
            printBtn.addEventListener('click', function () {
                window.print();
            });
        }

        document.addEventListener('cm:ajax:form:error', function (event) {
            const payload = event && event.detail ? event.detail.payload : null;
            if (payload && payload.message) {
                setAlert('error', payload.message);
            }
        });

        document.querySelectorAll('#cmEval .cm-eval-grid__note-field').forEach(function (input) {
            input.addEventListener('input', recalcMoyenne);
        });
        recalcMoyenne();
        if (soutenanceSelect && soutenanceSelect.value) {
            if (numEtuInput) {
                numEtuInput.value = soutenanceSelect.value;
            }
            fillSoutenanceInfo(soutenanceSelect.value);
        }
        updateDeleteState();
    })();
</script>
