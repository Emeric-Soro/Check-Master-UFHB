<?php
require_once __DIR__ . '/../../app/models/Note.php';
$niveaux = is_array($GLOBALS['niveaux'] ?? null) ? $GLOBALS['niveaux'] : [];
$anneesAcademiques = is_array($GLOBALS['anneesAcademiques'] ?? null) ? $GLOBALS['anneesAcademiques'] : [];
$etudiants = is_array($GLOBALS['etudiants'] ?? null) ? $GLOBALS['etudiants'] : [];
$selectedNiveau = !empty($GLOBALS['selectedNiveau']) ? (int) $GLOBALS['selectedNiveau'] : null;
$selectedAnneeAcad = !empty($GLOBALS['selectedAnneeAcad']) ? (int) $GLOBALS['selectedAnneeAcad'] : null;
$selectedStudent = is_object($GLOBALS['selectedStudent'] ?? null) ? $GLOBALS['selectedStudent'] : null;
$studentNote = is_object($GLOBALS['studentNote'] ?? null) ? $GLOBALS['studentNote'] : null;
$activeAnneeId = AcademicYear::getActiveIdFromSession();
$activeAnneeLabel = AcademicYear::getActiveLabelFromSession();
$selectedAnneeLabel = AcademicYear::getSelectedLabelFromSession();
$allYearsSelected = AcademicYear::isAllSelectedFromSession();
$globalSelectedAnneeId = AcademicYear::getSelectedIdFromSession();
$writableAnneeId = AcademicYear::getWritableIdFromSession();
$writableAnneeLabel = AcademicYear::getWritableLabelFromSession();
$ecritureAutorisee = AcademicYear::isWriteAllowedFromSession();
$today = date('Y-m-d');
if ($activeAnneeId === null || $activeAnneeLabel === '') {
    $activeAnneeLabel = date('Y') . '-' . (date('Y') + 1);
    foreach ($anneesAcademiques as $annee) {
        $debut = (string) ($annee->date_deb ?? '');
        $fin = (string) ($annee->date_fin ?? '');
        if ($debut !== '' && $fin !== '' && $today >= $debut && $today <= $fin) {
            $activeAnneeId = (int) ($annee->id_annee_acad ?? 0);
            $activeAnneeLabel = date('Y', strtotime($debut)) . '-' . date('Y', strtotime($fin));
            break;
        }
    }
}
$effectiveAnneeId = $selectedAnneeAcad ?: $globalSelectedAnneeId;
$displayAnneeLabel = $selectedAnneeLabel;
if ($selectedAnneeAcad) {
    foreach ($anneesAcademiques as $annee) {
        if ((int) ($annee->id_annee_acad ?? 0) === $selectedAnneeAcad) {
            $displayAnneeLabel = date('Y', strtotime((string) $annee->date_deb)) . '-' . date('Y', strtotime((string) $annee->date_fin));
            break;
        }
    }
}
if ($displayAnneeLabel === '') {
    $displayAnneeLabel = $allYearsSelected
        ? AcademicYear::getAllLabel()
        : ($writableAnneeLabel !== '' ? $writableAnneeLabel : $activeAnneeLabel);
}
$notesList = [];
try {
    $noteModel = new Note(Database::getConnection());
    if ($selectedNiveau) {
        $notesList = $noteModel->getNotesByNiveauAndYear($selectedNiveau, $effectiveAnneeId);
    } else {
        $notesList = $noteModel->getNotesByYear($effectiveAnneeId);
    }
} catch (Throwable $e) {
    $notesList = [];
}
$formAnneeId = $effectiveAnneeId ?: $writableAnneeId;
$notesEmptyMessage = $effectiveAnneeId
    ? 'Aucune note enregistrée pour cette année académique.'
    : 'Aucune note enregistrée pour les années académiques affichées.';
$studentOptions = [];
$studentCatalog = [];
foreach ($etudiants as $etu) {
    $num = (string) ($etu->num_carte_etud ?? '');
    if ($num === '') {
        continue;
    }
    $label = trim((string) ($etu->nom_etu ?? '') . ' ' . (string) ($etu->prenom_etu ?? '')) . ' (' . $num . ')';
    if ($allYearsSelected && !empty($etu->promotion_etu)) {
        $label .= ' - ' . (string) $etu->promotion_etu;
    }
    $studentOptions[$num] = $label;
    $studentCatalog[$num] = [
        'num' => $num,
        'nom' => (string) ($etu->nom_etu ?? ''),
        'prenom' => (string) ($etu->prenom_etu ?? ''),
    ];
}
$selectedStudentId = $selectedStudent ? (string) ($selectedStudent->num_carte_etud ?? '') : '';
$m1Value = $studentNote ? (string) ($studentNote->moyenne_M1 ?? $studentNote->moyenne_m1 ?? '') : '';
$m2Value = $studentNote ? (string) ($studentNote->moyenne_M2 ?? $studentNote->moyenne_m2 ?? '') : '';
$formAction = '?page=gestion_notes_evaluations&action=enregistrer_notes';
if ($selectedNiveau) {
    $formAction .= '&niveau=' . urlencode((string) $selectedNiveau);
}
if ($effectiveAnneeId) {
    $formAction .= '&annee=' . urlencode((string) $effectiveAnneeId);
}
if ($selectedStudentId !== '') {
    $formAction .= '&student=' . urlencode($selectedStudentId);
}
$allowedLimits = [2, 5, 10, 25, 50, 100];
$notesPerPage = max(2, (int) ($_GET['limit_notes'] ?? 10));
if (!in_array($notesPerPage, $allowedLimits, true)) {
    $notesPerPage = 10;
}
$notesPage = max(1, (int) ($_GET['page_notes'] ?? 1));
$notePagination = function_exists('cm_paginate')
    ? cm_paginate(count($notesList), $notesPerPage, $notesPage)
    : [
        'total' => count($notesList),
        'per_page' => $notesPerPage,
        'current' => $notesPage,
        'last' => max(1, (int) ceil(max(1, count($notesList)) / $notesPerPage)),
        'offset' => max(0, ($notesPage - 1) * $notesPerPage),
        'has_prev' => false,
        'has_next' => false,
        'pages' => [1],
    ];
$notesToShow = array_slice($notesList, (int) ($notePagination['offset'] ?? 0), $notesPerPage);
$paginationBaseUrl = '?page=gestion_notes_evaluations&niveau=' . urlencode((string) ($selectedNiveau ?? ''));
if ($effectiveAnneeId) {
    $paginationBaseUrl .= '&annee=' . urlencode((string) $effectiveAnneeId);
}
$paginationBaseUrl .= '&limit_notes=' . $notesPerPage;
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php
    cm_component('layout/page-header', [
        'title' => '',
        'subtitle' => 'Saisie M1 / M2 par étudiant et année académique.',
        'annee' => $displayAnneeLabel,
        'icon' => 'fa-calculator',
    ]);
    ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => (string) $_SESSION['success']]); ?>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => (string) $_SESSION['error']]); ?>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['messageErreur'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => (string) $GLOBALS['messageErreur']]); ?>
    <?php endif; ?>
    <?php if ($allYearsSelected): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'info',
            'message' => "Affichage multi-années actif. Les nouvelles notes seront enregistrées sur l'année académique active {$writableAnneeLabel}.",
        ]); ?>
    <?php elseif (!$ecritureAutorisee): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'warning',
            'message' => "Consultation historique active. La saisie des notes est réservée à l'année académique active {$activeAnneeLabel}.",
        ]); ?>
    <?php endif; ?>
    <div class="cm-crud-wrapper">
    <div class="cm-pole-superieur">
        <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmNotesForm .cm-notes-form-grid {
    grid-template-columns: minmax(0, 2.8fr) minmax(0, 0.8fr) minmax(0, 0.8fr) !important;
    gap: 0.75rem 1rem !important;
    align-items: end !important;
}

#cmNotesForm .cm-form-group:has(#cmStudentPicker_hidden) {
    grid-column: 1 / -1;
    width: 100% !important;
    max-width: none !important;
}

#cmNotesForm .cm-form-group:has(#cmStudentPicker_hidden) .cm-select-search,
#cmNotesForm .cm-form-group:has(#cmStudentPicker_hidden) .cm-select-search__input {
    width: 100% !important;
    max-width: none !important;
}

#cmNotesForm #cmStudentPicker_wrapper .cm-select-search__input {
    --cm-field-width: 80%;
    --cm-field-max-width: none;
    width: 80% !important;
    max-width: none !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Dropdown en overlay: ne pousse plus les champs M1/M2 vers le bas */
#cmNotesForm #cmStudentPicker_wrapper {
    position: relative;
}

#cmNotesForm #cmStudentPicker_wrapper .cm-select-search__list {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% - var(--cm-spacing-xs));
    z-index: 1200;
    border: 1px solid var(--cm-border-color);
    box-shadow: var(--cm-shadow-lg);
}

#cmNotesForm .cm-form-group:has(#cmMoyenneM1),
#cmNotesForm .cm-form-group:has(#cmMoyenneM2) {
    max-width: 10rem;
}
</style>
<form id="cmNotesForm" method="POST" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>">
            <?php cm_component('form/csrf-token'); ?>
            <input type="hidden" name="id_annee_acad" id="cmAnneeHidden" value="<?php echo htmlspecialchars((string) ($formAnneeId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="cm_annee_filter" id="cmAnneeFilter" value="<?php echo htmlspecialchars((string) ($effectiveAnneeId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="cm-grid-3 cm-notes-form-grid">
                <?php
                echo '<input type="hidden" name="annee_display" value="' . htmlspecialchars((string) $displayAnneeLabel, ENT_QUOTES, 'UTF-8') . '">';
                cm_component('form/select-search', [
                    'name' => 'student_picker',
                    'id' => 'cmStudentPicker',
                    'label' => 'Etudiant',
                    'options' => $studentOptions,
                    'selected' => $selectedStudentId,
                    'required' => true,
                    'placeholder' => '-- Sélectionner un étudiant --',
                    'search_placeholder' => 'Rechercher un étudiant...',
                    'show_selected_label' => false,
                    'min_search' => 0,
                    'control_class' => 'cm-field-lg',
                ]);
                cm_component('form/input-number', [
                    'name' => 'moyenne_M1',
                    'id' => 'cmMoyenneM1',
                    'label' => 'Moyenne M1',
                    'required' => true,
                    'min' => 0,
                    'max' => 20,
                    'step' => '0.01',
                    'value' => $m1Value,
                    'control_class' => 'cm-field-xs',
                ]);
                cm_component('form/input-number', [
                    'name' => 'moyenne_M2',
                    'id' => 'cmMoyenneM2',
                    'label' => 'Moyenne M2',
                    'required' => true,
                    'min' => 0,
                    'max' => 20,
                    'step' => '0.01',
                    'value' => $m2Value,
                    'control_class' => 'cm-field-xs',
                ]);
                ?>
            </div>
            <div class="cm-form-buttons">
                <?php
                $notesFormActions = [
                    ['label' => 'Réinitialiser', 'type' => 'reset', 'class' => 'cm-btn is-secondary is-sm', 'attrs' => ['id' => 'cmResetNotes']],
                ];
                if (canCreate() || canEdit()) {
                    $notesFormActions[] = ['label' => 'Valider', 'type' => 'submit', 'class' => 'cm-btn is-primary is-sm', 'attrs' => ['name' => 'btn_enregistrer_notes']];
                }
                cm_component('crud/form-actions', [
                    'cancel_action' => ['label' => 'Annuler', 'type' => 'button', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['data-reset-form' => '1']],
                    'actions' => $notesFormActions,
                ]);
                ?>
            </div>
        </form>
    </div>
    <?php cm_toolbar([
        'screen' => 'gestion_notes_evaluations',
        'id_prefix' => 'cmNotes',
        'search_value' => $_GET['search'] ?? '',
        'limit' => $notesPerPage,
        'limit_options' => $allowedLimits,
        'can_delete' => canDelete(),
        'can_view' => canView(),
    ]); ?>
    <div class="cm-pole-inferieur">
        <div class="cm-table-wrapper">
            <table class="cm-data-table" id="cmNotesTable">
                <thead>
                <tr>
                    <th class="cm-data-table__th is-checkbox">
                        <input type="checkbox" id="cmCheckAllNotes" class="cm-checkbox" aria-label="Sélectionner toutes les lignes">
                    </th>
                    <th class="cm-data-table__th">N° Carte Étudiant</th>
                    <th class="cm-data-table__th">Nom &amp; Prénom</th>
                    <th class="cm-data-table__th">Année Académique</th>
                    <th class="cm-data-table__th">Moy. M1</th>
                    <th class="cm-data-table__th">Moy. M2</th>
                    <th class="cm-data-table__th">Date saisie</th>
                    <th class="cm-data-table__th">Actions</th>
                </tr>
                </thead>
                <tbody id="cmNotesTableBody">
                <?php if (empty($notesToShow)): ?>
                    <?php cm_component('ui/empty-state', [
                        'in_table' => true,
                        'colspan' => 8,
                        'title' => '',
                        'message' => $notesEmptyMessage,
                    ]); ?>
                <?php else: ?>
                    <?php foreach ($notesToShow as $note): ?>
                        <?php
                        $numEtu = (string) ($note->num_carte_etud ?? $note->num_etu ?? '');
                        $nom = (string) ($note->nom_etu ?? '');
                        $prenom = (string) ($note->prenom_etu ?? '');
                        $nomPrenom = trim($nom . ' ' . $prenom);
                        $m1 = (string) ($note->moyenne_M1 ?? $note->moyenne_m1 ?? '');
                        $m2 = (string) ($note->moyenne_M2 ?? $note->moyenne_m2 ?? '');
                        $anneeNoteId = !empty($note->id_annee_acad) ? (int) $note->id_annee_acad : null;
                        $anneeNote = (!empty($note->date_deb) && !empty($note->date_fin))
                            ? date('Y', strtotime((string) $note->date_deb)) . '-' . date('Y', strtotime((string) $note->date_fin))
                            : '';
                        $dateSaisie = '';
                        if (!empty($note->date_creation)) {
                            $dateSaisie = date('d/m/Y', strtotime((string) $note->date_creation));
                        } elseif (!empty($note->date_modification)) {
                            $dateSaisie = date('d/m/Y', strtotime((string) $note->date_modification));
                        }
                        ?>
                        <tr class="cm-data-table__row"
                            data-search="<?php echo htmlspecialchars(strtolower($numEtu . ' ' . $nom . ' ' . $prenom), ENT_QUOTES, 'UTF-8'); ?>">
                            <td class="cm-data-table__td is-checkbox">
                                <input type="checkbox" class="cm-checkbox cm-row-checkbox">
                            </td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($nomPrenom, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($anneeNote !== '' ? $anneeNote : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($m1, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($m2, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($dateSaisie, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td">
                                <div class="cm-row-actions">
                                    <?php if (canEdit()): ?>
                                        <button type="button"
                                                class="cm-btn-action is-edit"
                                                onclick="cmEditNote('<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($m1, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($m2, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars((string) ($anneeNoteId ?? ''), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($anneeNote, ENT_QUOTES, 'UTF-8'); ?>')"
                                                title="Modifier">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($selectedNiveau): ?>
                                        <a class="cm-btn-action is-edit"
                                           href="?page=gestion_notes_evaluations&action=imprimer_releve&student=<?php echo urlencode($numEtu); ?>&niveau=<?php echo urlencode((string) $selectedNiveau); ?><?php echo $anneeNoteId ? '&annee=' . urlencode((string) $anneeNoteId) : ''; ?>"
                                           target="_blank"
                                           title="Imprimer releve">
                                            <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                        </a>
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
            'pagination' => $notePagination,
            'base_url' => $paginationBaseUrl,
            'param_name' => 'page_notes',
        ]); ?>
    </div>
</div>
</div>
<script>
(function () {
    const studentCatalog = <?php echo json_encode($studentCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const anneeFilter = document.getElementById('cmAnneeFilter');
    const notesLimit = document.getElementById('cmNotesLimit');
    const studentHidden = document.getElementById('cmStudentPicker_hidden');
    const anneeHiddenInput = document.getElementById('cmAnneeHidden');
    const m1Field = document.getElementById('cmMoyenneM1');
    const m2Field = document.getElementById('cmMoyenneM2');
    const notesForm = document.getElementById('cmNotesForm');
    const allYearsSelected = <?php echo $allYearsSelected ? 'true' : 'false'; ?>;
    const writableAnneeId = <?php echo json_encode($writableAnneeId); ?>;
    const writableAnneeLabel = <?php echo json_encode($writableAnneeLabel); ?>;
    const initialFormAnneeId = <?php echo json_encode($formAnneeId); ?>;
    const navigate = function (url) {
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(url);
            return;
        }
        window.location.href = url;
    };
    const navigateWithParams = function (params) {
        navigate('?' + params.toString());
    };
    if (notesLimit) {
        notesLimit.addEventListener('change', function () {
            const params = new URLSearchParams(window.location.search);
            params.set('page', 'gestion_notes_evaluations');
            params.set('limit_notes', String(notesLimit.value));
            params.set('page_notes', '1');
            navigateWithParams(params);
        });
    }
    if (studentHidden) {
        studentHidden.addEventListener('change', function () {
            if (!studentHidden.value) {
                return;
            }
            const params = new URLSearchParams(window.location.search);
            params.set('page', 'gestion_notes_evaluations');
            if (anneeFilter && anneeFilter.value) {
                params.set('annee', anneeFilter.value);
            } else {
                params.delete('annee');
            }
            params.set('student', studentHidden.value);
            params.delete('action');
            navigateWithParams(params);
        });
        document.querySelectorAll('#cmStudentPicker_wrapper .cm-select-search__option').forEach(function (option) {
            option.addEventListener('click', function () {
                setTimeout(syncStudentFields, 0);
            });
        });
    }
    window.cmEditNote = function (num, nom, prenom, m1, m2, anneeId, anneeLabel) {
        if (allYearsSelected && anneeId && writableAnneeId && Number(anneeId) !== Number(writableAnneeId)) {
            window.alert("En mode toutes les années, seules les notes de l'année académique active " + writableAnneeLabel + " peuvent être modifiées.");
            return;
        }
        if (studentHidden) {
            studentHidden.value = num;
        }
        const studentWrapper = document.getElementById('cmStudentPicker_wrapper');
        if (studentWrapper) {
            const selectedLabel = studentWrapper.querySelector('#cmStudentPicker_selected_label');
            const options = studentWrapper.querySelectorAll('.cm-select-search__option');
            let labelText = '';
            options.forEach(function (opt) {
                if (opt.dataset.value === num) {
                    opt.classList.add('is-selected');
                    opt.setAttribute('aria-selected', 'true');
                    labelText = opt.dataset.label || '';
                } else {
                    opt.classList.remove('is-selected');
                    opt.setAttribute('aria-selected', 'false');
                }
            });
            if (selectedLabel && labelText) {
                selectedLabel.textContent = labelText;
            }
            const searchInput = studentWrapper.querySelector('.cm-select-search__input');
            if (searchInput) {
                searchInput.value = labelText;
            }
        }
        if (anneeHiddenInput) {
            anneeHiddenInput.value = anneeId || initialFormAnneeId || '';
        }
        if (m1Field) {
            m1Field.value = m1;
        }
        if (m2Field) {
            m2Field.value = m2;
        }
    };
    document.getElementById('cmResetNotes') && document.getElementById('cmResetNotes').addEventListener('click', function () {
        setTimeout(function () {
            if (anneeHiddenInput) {
                anneeHiddenInput.value = initialFormAnneeId || '';
            }
            if (m1Field) {
                m1Field.value = '';
            }
            if (m2Field) {
                m2Field.value = '';
            }
        }, 0);
    });
    if (notesForm) {
        notesForm.addEventListener('submit', function (event) {
            const studentId = studentHidden ? studentHidden.value : '';
            const m1 = Number(m1Field ? m1Field.value : 0);
            const m2 = Number(m2Field ? m2Field.value : 0);
            if (!studentId) {
                event.preventDefault();
                window.alert('Veuillez sélectionner un étudiant.');
                return;
            }
            if (isNaN(m1) || isNaN(m2) || m1 < 0 || m1 > 20 || m2 < 0 || m2 > 20) {
                event.preventDefault();
                window.alert('Les moyennes doivent etre comprises entre 0 et 20.');
                return;
            }
            const actionUrl = new URL(notesForm.action, window.location.origin + window.location.pathname);
            actionUrl.searchParams.set('student', studentId);
            notesForm.action = actionUrl.pathname + actionUrl.search;
        });
    }
    const searchInput = document.getElementById('cmSearchNotes');
    const noteRows = function () { return Array.from(document.querySelectorAll('#cmNotesTableBody tr')); };
    const noteCheckboxes = function () {
        return Array.from(document.querySelectorAll('#cmNotesTableBody .cm-row-checkbox'));
    };
    const selectedNotesCount = document.getElementById('cmSelectedNotesCount');
    const deleteNotesBtn = document.getElementById('cmDeleteNotes');
    const updateSelectionState = function () {
        const checked = noteCheckboxes().filter(function (cb) { return cb.checked; }).length;
        if (selectedNotesCount) {
            selectedNotesCount.textContent = String(checked);
        }
        if (deleteNotesBtn) {
            deleteNotesBtn.disabled = checked === 0;
        }
        if (checkAll) {
            const all = noteCheckboxes();
            checkAll.checked = all.length > 0 && all.every(function (cb) { return cb.checked; });
        }
    };
    const applySearch = function () {
        const term = (searchInput ? searchInput.value : '').trim().toLowerCase();
        noteRows().forEach(function (row) {
            const haystack = row.getAttribute('data-search') || '';
            row.style.display = haystack.indexOf(term) !== -1 ? '' : 'none';
        });
    };
    searchInput && searchInput.addEventListener('input', applySearch);
    const checkAll = document.getElementById('cmCheckAllNotes');
    checkAll && checkAll.addEventListener('change', function () {
        noteCheckboxes().forEach(function (cb) {
            cb.checked = checkAll.checked;
        });
        updateSelectionState();
    });
    document.getElementById('cmSelectAllNotes') && document.getElementById('cmSelectAllNotes').addEventListener('click', function () {
        noteCheckboxes().forEach(function (cb) { cb.checked = true; });
        updateSelectionState();
    });
    document.getElementById('cmDeselectAllNotes') && document.getElementById('cmDeselectAllNotes').addEventListener('click', function () {
        noteCheckboxes().forEach(function (cb) { cb.checked = false; });
        updateSelectionState();
    });
    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('cm-row-checkbox')) {
            updateSelectionState();
        }
    });
    deleteNotesBtn && deleteNotesBtn.addEventListener('click', function () {
        if (deleteNotesBtn.disabled) {
            return;
        }
        window.alert('Suppression multiple indisponible sur cet ecran.');
    });
    document.getElementById('cmPrintNotes') && document.getElementById('cmPrintNotes').addEventListener('click', function () {
        window.print();
    });
    document.getElementById('cmExportNotes') && document.getElementById('cmExportNotes').addEventListener('click', function () {
        const headers = ['N° Carte Étudiant', 'Nom & Prénom', 'Année Académique', 'Moy. M1', 'Moy. M2', 'Date saisie'];
        const lines = [headers.join(';')];
        noteRows().forEach(function (row) {
            if (row.style.display === 'none') {
                return;
            }
            const cells = Array.from(row.querySelectorAll('td')).slice(1, 8);
            const values = cells.map(function (cell) {
                return '"' + (cell.textContent || '').trim().replace(/"/g, '""') + '"';
            });
            lines.push(values.join(';'));
        });
        const blob = new Blob(["\uFEFF" + lines.join('\n')], {type: 'text/csv;charset=utf-8;'});
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'notes_' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    updateSelectionState();
})();
</script>
