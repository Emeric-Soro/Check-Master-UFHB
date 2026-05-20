<?php
require_once __DIR__ . '/../../app/controllers/EvaluationSoutenanceController.php';

$controller = new EvaluationSoutenanceController();
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$message = '';
$messageType = 'success';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'evaluerSoutenance') {
        $result = $controller->enregistrerEvaluation();
        $message = (string) ($result['message'] ?? '');
        $messageType = !empty($result['success']) ? 'success' : 'error';
    } elseif ($_POST['action'] === 'supprimerEvaluation') {
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

$soutenances = array_map(static function (array $row): array {
    $row['promotion_etu'] = FormattingUtils::formatPromotion((string) ($row['promotion_etu'] ?? ''));
    $row['promotion_label'] = FormattingUtils::formatPromotion((string) ($row['promotion_label'] ?? ''));
    return $row;
}, $controller->getSoutenancesProgrammeesForView());
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
    $label = trim((string) ($soutenance['nom_etudiant'] ?? 'Étudiant')) . ' - ' . trim((string) ($soutenance['matricule_etudiant'] ?? $num));
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
                <input type="hidden" name="action" value="evaluerSoutenance">
                <input type="hidden" name="num_etu" id="cmEvalNumEtu" value="">

                <div class="cm-grid-2">
                    <?php
                    cm_component('form/select', [
                        'name' => 'cm_eval_soutenance',
                        'id' => 'cmEvalSoutenanceSelect',
                        'label' => 'Étudiant',
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
                    'label' => 'Thème',
                    'readonly' => true,
                    'rows' => 2,
                    'control_class' => 'cm-field-full cm-size-theme',
                ]);
                ?>

                <p class="cm-text-sm cm-text-muted cm-m-0" id="cmEvalSelectedLabel">Soutenance sélectionnée: -</p>
                <div class="cm-grid-3">
                    <?php
                    cm_component('form/input-text', [
                        'name' => 'cm_prog_president',
                        'id' => 'cmProgPresident',
                        'label' => 'Président du jury',
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
                    'commentaire_label' => 'Commentaire général',
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
                            'label' => 'Décision',
                            'options' => ['admis' => 'Admis', 'ajourne' => 'Ajourné'],
                            'control_class' => 'cm-field-sm cm-size-salle',
                        ]); ?>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label" for="cmEvalComment">Commentaire général</label>
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
            <?php cm_toolbar([
                'screen' => 'evaluation_soutenance',
                'id_prefix' => 'cmEvalSout',
                'limit' => $perPage,
                'limit_options' => $allowedLimits,
                'search_placeholder' => 'Rechercher une soutenance...',
                'custom_actions' => [
                    [
                        'tag' => 'button',
                        'id' => 'cmEvalSoutExport',
                        'label' => 'Export',
                        'icon' => 'fa-file-export',
                        'class' => 'cm-btn is-info is-sm',
                        'attrs' => ['data-cm-toolbar-action' => 'export']
                    ]
                ]
            ]); ?>
        </div>

        <div class="cm-pole-inferieur">
            <div class="cm-text-sm cm-text-semibold cm-px-sm">Soutenances evaluees</div>
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmEvalSoutTable">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th cm-data-table__th--check">
                                <input type="checkbox" id="cmEvalSoutCheckAll" class="cm-table-check-all" aria-label="Tout selectionner">
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
                                'message' => 'Aucune soutenance programmée disponible.',
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
                                        <input type="checkbox" class="cm-table-check-row cm-eval-sout-check-row"
                                            value="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                            aria-label="Selectionner ligne <?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>">
                                    </td>
                                    <td class="cm-data-table__td"><?php echo (int) ($pagination['offset'] ?? 0) + $index + 1; ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($soutenance['nom_etudiant'] ?? 'Étudiant'), ENT_QUOTES, 'UTF-8'); ?><br>
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
        const soutenances = <?php echo json_encode($soutenances, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const anneeSelect = document.getElementById('cmEvalAnnee');
        const soutenanceSelect = document.getElementById('cmEvalSoutenanceSelect');
        const numEtuInput = document.getElementById('cmEvalNumEtu');
        const moyenneInput = document.getElementById('cmEvalMoyenne');
        const decisionSelect = document.getElementById('cmEvalDecision');
        const promotionInput = document.getElementById('cmEvalPromotion');
        const themeInput = document.getElementById('cmEvalTheme');
        const presidentInput = document.getElementById('cmProgPresident');
        const examinateurInput = document.getElementById('cmProgExaminateur');
        const directeurInput = document.getElementById('cmProgDirecteur');
        const encadreurInput = document.getElementById('cmProgEncadreur');
        const maitreStageInput = document.getElementById('cmProgMaitreStage');
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
            formData.append('action', 'supprimerEvaluation');
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

        function formatDateFr(value) {
            const raw = String(value || '').trim();
            if (!raw || raw === '0000-00-00' || raw === '0000-00-00 00:00:00') {
                return '';
            }

            const isoMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (isoMatch) {
                return isoMatch[3] + '/' + isoMatch[2] + '/' + isoMatch[1];
            }

            const parsed = new Date(raw);
            if (!Number.isNaN(parsed.getTime())) {
                return parsed.toLocaleDateString('fr-FR');
            }

            return raw;
        }

        function formatTimeFr(value) {
            const raw = String(value || '').trim();
            if (!raw || raw === '00:00:00' || raw === '00:00' || raw === '00:00:10') {
                return '';
            }

            return raw.slice(0, 5);
        }

        function clearEvaluationGrid() {
            document.querySelectorAll('#cmEval .cm-eval-grid__note-field').forEach(function (input) {
                input.value = '';
                input.classList.remove('is-invalid');
            });
            recalcMoyenne();
        }

        function resetSoutenanceInfo() {
            if (promotionInput) promotionInput.value = '';
            if (themeInput) themeInput.value = '';
            if (presidentInput) presidentInput.value = '';
            if (examinateurInput) examinateurInput.value = '';
            if (directeurInput) directeurInput.value = '';
            if (encadreurInput) encadreurInput.value = '';
            if (maitreStageInput) maitreStageInput.value = '';
            if (selectedLabel) selectedLabel.textContent = 'Soutenance sélectionnée: -';
            const commentaireEl = document.getElementById('cmEvalComment');
            if (commentaireEl) {
                commentaireEl.value = '';
            }
            clearEvaluationGrid();
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
                resetSoutenanceInfo();
                return;
            }

            clearEvaluationGrid();

            if (promotionInput) promotionInput.value = info.promotion_label || info.promotion_etu || '';
            if (themeInput) themeInput.value = info.theme_soutenance || '';
            if (presidentInput) presidentInput.value = info.president_nom || '';
            if (examinateurInput) examinateurInput.value = info.examinateur_nom || '';
            if (directeurInput) directeurInput.value = info.directeur_nom || '';
            if (encadreurInput) encadreurInput.value = info.encadreur_nom || '';
            if (maitreStageInput) maitreStageInput.value = info.maitre_stage_nom || '';

            const datePart = formatDateFr(info.date_soutenance);
            const heurePart = formatTimeFr(info.heure_soutenance);
            const dateHeure = [datePart, heurePart].filter(Boolean).join(' ');
            if (selectedLabel) {
                selectedLabel.textContent = 'Soutenance sélectionnée: ' + (info.nom_etudiant || 'Étudiant') + ' - ' + (dateHeure || '-');
            }

            const commentaireEl = document.getElementById('cmEvalComment');
            if (commentaireEl) {
                commentaireEl.value = info.commentaire_general || '';
            }
            if (decisionSelect) {
                decisionSelect.value = info.decision || 'ajourne';
            }

            if (info.est_evalue > 0) {
                fetch('?page=evaluation_soutenance&action=getEvaluationExistante&num_etu=' + encodeURIComponent(numEtu), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        const rows = Array.isArray(payload) ? payload : (Array.isArray(payload && payload.rows) ? payload.rows : []);
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
                        if (commentaireEl && payload && typeof payload.commentaire_general === 'string') {
                            commentaireEl.value = payload.commentaire_general;
                        }
                        if (decisionSelect && payload && payload.decision) {
                            decisionSelect.value = payload.decision;
                        }
                    });
            } else {
                clearEvaluationGrid();
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
                if (decisionSelect) decisionSelect.value = 'ajourne';
                resetSoutenanceInfo();
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
                        setAlert('success', payload.message || 'Évaluation supprimée.');
                        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
                            window.CM.ajax.load(window.location.href, { replaceHistory: true, skipHistory: true });
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(function () {
                        setAlert('error', 'Erreur réseau.');
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
                        setAlert('error', 'Erreur réseau.');
                    });
            });
        }

        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                const headers = ['N', 'Étudiant', 'Date soutenance', 'Moyenne', 'Mention', 'Commentaire'];
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
