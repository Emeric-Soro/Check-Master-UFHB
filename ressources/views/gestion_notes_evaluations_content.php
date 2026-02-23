<?php
require_once __DIR__ . '/../../app/utils/permissions_helper.php';
require_once __DIR__ . '/../../app/models/Note.php';

$niveaux = is_array($GLOBALS['niveaux'] ?? null) ? $GLOBALS['niveaux'] : [];
$anneesAcademiques = is_array($GLOBALS['anneesAcademiques'] ?? null) ? $GLOBALS['anneesAcademiques'] : [];
$etudiants = is_array($GLOBALS['etudiants'] ?? null) ? $GLOBALS['etudiants'] : [];

$selectedNiveau = !empty($GLOBALS['selectedNiveau']) ? (int) $GLOBALS['selectedNiveau'] : null;
$selectedAnneeAcad = !empty($GLOBALS['selectedAnneeAcad']) ? (int) $GLOBALS['selectedAnneeAcad'] : null;
$selectedStudent = is_object($GLOBALS['selectedStudent'] ?? null) ? $GLOBALS['selectedStudent'] : null;
$studentNote = is_object($GLOBALS['studentNote'] ?? null) ? $GLOBALS['studentNote'] : null;

$activeAnneeId = null;
$activeAnneeLabel = date('Y') . '-' . (date('Y') + 1);
$today = date('Y-m-d');
foreach ($anneesAcademiques as $annee) {
    $debut = (string) ($annee->date_deb ?? '');
    $fin = (string) ($annee->date_fin ?? '');
    if ($debut !== '' && $fin !== '' && $today >= $debut && $today <= $fin) {
        $activeAnneeId = (int) ($annee->id_annee_acad ?? 0);
        $activeAnneeLabel = date('Y', strtotime($debut)) . '-' . date('Y', strtotime($fin));
        break;
    }
}

$effectiveAnneeId = $selectedAnneeAcad ?: $activeAnneeId;
if ($selectedAnneeAcad) {
    foreach ($anneesAcademiques as $annee) {
        if ((int) ($annee->id_annee_acad ?? 0) === $selectedAnneeAcad) {
            $activeAnneeLabel = date('Y', strtotime((string) $annee->date_deb)) . '-' . date('Y', strtotime((string) $annee->date_fin));
            break;
        }
    }
}

$notesList = [];
if ($effectiveAnneeId) {
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
}
$notesEmptyMessage = $effectiveAnneeId
    ? 'Aucune note enregistree pour cette annee academique.'
    : 'Selectionnez une annee pour afficher les notes.';

$studentOptions = [];
$studentCatalog = [];
foreach ($etudiants as $etu) {
    $num = (string) ($etu->num_carte_etud ?? '');
    if ($num === '') {
        continue;
    }
    $label = trim((string) ($etu->nom_etu ?? '') . ' ' . (string) ($etu->prenom_etu ?? '')) . ' (' . $num . ')';
    $studentOptions[$num] = $label;
    $studentCatalog[$num] = [
        'num' => $num,
        'nom' => (string) ($etu->nom_etu ?? ''),
        'prenom' => (string) ($etu->prenom_etu ?? ''),
    ];
}

$selectedStudentId = $selectedStudent ? (string) ($selectedStudent->num_carte_etud ?? '') : '';
$m1Value = $studentNote ? (string) ($studentNote->moyenne_M1 ?? '') : '';
$m2Value = $studentNote ? (string) ($studentNote->moyenne_M2 ?? '') : '';

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
$paginationBaseUrl = '?page=gestion_notes_evaluations&niveau=' . urlencode((string) ($selectedNiveau ?? '')) . '&annee=' . urlencode((string) ($effectiveAnneeId ?? '')) . '&limit_notes=' . $notesPerPage;
?>

<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php
    cm_component('layout/page-header', [
        'title' => 'Saisie des moyennes',
        'subtitle' => 'Saisie M1 / M2 par etudiant et annee academique.',
        'annee' => $activeAnneeLabel,
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

    <div class="cm-crud-wrapper">
    <div class="cm-pole-superieur">
        <div class="cm-pole-superieur-title">
            <h2>
                <i class="fas fa-marker" aria-hidden="true"></i>
                Formulaire des notes
            </h2>
        </div>

        <div class="cm-grid-2">
            <?php
            $niveauOptions = [];
            foreach ($niveaux as $niveau) {
                $niveauOptions[(int) ($niveau->id_niv_etude ?? 0)] = (string) ($niveau->lib_niv_etude ?? 'Niveau');
            }
            cm_component('form/select', [
                'name' => 'cm_niveau_filter',
                'id' => 'cmNiveauFilter',
                'label' => 'Niveau',
                'options' => $niveauOptions,
                'selected' => (string) ($selectedNiveau ?? ''),
            ]);

            $anneeOptions = [];
            foreach ($anneesAcademiques as $annee) {
                $id = (int) ($annee->id_annee_acad ?? 0);
                $debut = !empty($annee->date_deb) ? date('Y', strtotime((string) $annee->date_deb)) : '';
                $fin = !empty($annee->date_fin) ? date('Y', strtotime((string) $annee->date_fin)) : '';
                $anneeOptions[$id] = trim($debut . '-' . $fin, '-');
            }
            cm_component('form/select', [
                'name' => 'cm_annee_filter',
                'id' => 'cmAnneeFilter',
                'label' => 'Annee Academique',
                'options' => $anneeOptions,
                'selected' => (string) ($effectiveAnneeId ?? ''),
            ]);
            ?>
        </div>

        <form id="cmNotesForm" method="POST" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>">
            <?php cm_component('form/csrf-token'); ?>
            <input type="hidden" name="id_annee_acad" id="cmAnneeHidden" value="<?php echo htmlspecialchars((string) ($effectiveAnneeId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <div class="cm-grid-4">
                <?php
                cm_component('form/input-text', [
                    'name' => 'annee_display',
                    'id' => 'cmAnneeDisplay',
                    'label' => 'Annee Acad.',
                    'readonly' => true,
                    'value' => $activeAnneeLabel,
                ]);

                cm_component('form/select-search', [
                    'name' => 'student_picker',
                    'id' => 'cmStudentPicker',
                    'label' => 'Etudiant',
                    'options' => $studentOptions,
                    'selected' => $selectedStudentId,
                    'required' => true,
                    'placeholder' => '-- Selectionner un etudiant --',
                ]);

                cm_component('form/input-text', [
                    'name' => 'num_etu_display',
                    'id' => 'cmNumEtuDisplay',
                    'label' => 'N° Carte',
                    'readonly' => true,
                    'value' => (string) ($selectedStudent->num_carte_etud ?? ''),
                ]);

                cm_component('form/input-text', [
                    'name' => 'nom_display',
                    'id' => 'cmNomDisplay',
                    'label' => 'Nom',
                    'readonly' => true,
                    'value' => (string) ($selectedStudent->nom_etu ?? ''),
                ]);

                cm_component('form/input-text', [
                    'name' => 'prenom_display',
                    'id' => 'cmPrenomDisplay',
                    'label' => 'Prenom',
                    'readonly' => true,
                    'value' => (string) ($selectedStudent->prenom_etu ?? ''),
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
                ]);
                ?>
            </div>

            <div class="cm-form-buttons">
                <?php if (canCreate() || canEdit()): ?>
                    <button class="cm-btn is-success" type="submit" name="btn_enregistrer_notes">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        Valider
                    </button>
                <?php endif; ?>
                <button class="cm-btn is-light" type="reset" id="cmResetNotes">
                    <i class="fas fa-rotate-left" aria-hidden="true"></i>
                    Reinitialiser
                </button>
            </div>
        </form>
    </div>

    <div class="cm-barre-intermediaire">
        <div class="cm-toolbar">
            <div class="cm-toolbar-left">
                <label for="cmNotesLimit"><strong>Afficher:</strong></label>
                <select id="cmNotesLimit" class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs">
                    <?php foreach ($allowedLimits as $limit): ?>
                        <option value="<?php echo $limit; ?>" <?php echo $limit === $notesPerPage ? 'selected' : ''; ?>>
                            <?php echo $limit; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="cm-badge is-info cm-toolbar-year">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($activeAnneeLabel, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
            <div class="cm-toolbar-center">
                <input type="text" id="cmSearchNotes" class="cm-form-control" placeholder="Rechercher un etudiant...">
            </div>
            <div class="cm-toolbar-right">
                <button type="button" class="cm-btn is-info is-sm" id="cmSelectAllNotes">
                    <i class="fas fa-check-square" aria-hidden="true"></i>
                    Tout selectionner
                </button>
                <button type="button" class="cm-btn is-light is-sm" id="cmDeselectAllNotes">
                    <i class="fas fa-square" aria-hidden="true"></i>
                    Deselectionner
                </button>
                <button type="button" class="cm-btn is-info is-sm" id="cmDeleteNotes" disabled>
                    <i class="fas fa-trash" aria-hidden="true"></i>
                    Supprimer (<span id="cmSelectedNotesCount">0</span>)
                </button>
                <button type="button" class="cm-btn is-info is-sm" id="cmExportNotes">
                    <i class="fas fa-file-export" aria-hidden="true"></i>
                    Exporter
                </button>
                <button type="button" class="cm-btn is-info is-sm" id="cmPrintNotes">
                    <i class="fas fa-print" aria-hidden="true"></i>
                    Imprimer
                </button>
            </div>
        </div>
    </div>

    <div class="cm-pole-inferieur">
        <div class="cm-table-wrapper">
            <table class="cm-data-table" id="cmNotesTable">
                <thead>
                <tr>
                    <th class="cm-data-table__th is-checkbox">
                        <input type="checkbox" id="cmCheckAllNotes" class="cm-checkbox" aria-label="Selectionner toutes les lignes">
                    </th>
                    <th class="cm-data-table__th">N° Etudiant</th>
                    <th class="cm-data-table__th">Nom</th>
                    <th class="cm-data-table__th">Prenom</th>
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
                        'title' => 'Aucune note',
                        'message' => $notesEmptyMessage,
                    ]); ?>
                <?php else: ?>
                    <?php foreach ($notesToShow as $note): ?>
                        <?php
                        $numEtu = (string) ($note->num_carte_etud ?? $note->num_etu ?? '');
                        $nom = (string) ($note->nom_etu ?? '');
                        $prenom = (string) ($note->prenom_etu ?? '');
                        $m1 = (string) ($note->moyenne_M1 ?? '');
                        $m2 = (string) ($note->moyenne_M2 ?? '');
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
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($m1, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($m2, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($dateSaisie, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td">
                                <div class="cm-row-actions">
                                    <?php if (canEdit()): ?>
                                        <button type="button"
                                                class="cm-btn-action is-edit"
                                                onclick="cmEditNote('<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($m1, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($m2, ENT_QUOTES, 'UTF-8'); ?>')"
                                                title="Modifier">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($selectedNiveau): ?>
                                        <a class="cm-btn-action is-edit"
                                           href="?page=gestion_notes_evaluations&action=imprimer_releve&student=<?php echo urlencode($numEtu); ?>&niveau=<?php echo urlencode((string) $selectedNiveau); ?>"
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
    const niveauFilter = document.getElementById('cmNiveauFilter');
    const anneeFilter = document.getElementById('cmAnneeFilter');
    const notesLimit = document.getElementById('cmNotesLimit');
    const studentHidden = document.getElementById('cmStudentPicker_hidden');
    const numDisplay = document.getElementById('cmNumEtuDisplay');
    const nomDisplay = document.getElementById('cmNomDisplay');
    const prenomDisplay = document.getElementById('cmPrenomDisplay');
    const m1Field = document.getElementById('cmMoyenneM1');
    const m2Field = document.getElementById('cmMoyenneM2');
    const notesForm = document.getElementById('cmNotesForm');
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

    const syncStudentFields = function () {
        const id = studentHidden ? studentHidden.value : '';
        const data = studentCatalog[id];
        if (!data) {
            numDisplay.value = '';
            nomDisplay.value = '';
            prenomDisplay.value = '';
            return;
        }
        numDisplay.value = data.num || '';
        nomDisplay.value = data.nom || '';
        prenomDisplay.value = data.prenom || '';
    };

    const reloadByFilters = function () {
        const niveau = niveauFilter ? niveauFilter.value : '';
        const annee = anneeFilter ? anneeFilter.value : '';
        const params = new URLSearchParams(window.location.search);
        params.set('page', 'gestion_notes_evaluations');

        if (niveau) {
            params.set('niveau', niveau);
        } else {
            params.delete('niveau');
        }
        if (annee) {
            params.set('annee', annee);
        } else {
            params.delete('annee');
        }
        params.delete('student');
        params.delete('action');
        navigateWithParams(params);
    };

    niveauFilter && niveauFilter.addEventListener('change', reloadByFilters);
    anneeFilter && anneeFilter.addEventListener('change', reloadByFilters);
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
            syncStudentFields();
            if (!niveauFilter || !anneeFilter || !studentHidden.value || !niveauFilter.value || !anneeFilter.value) {
                return;
            }
            const params = new URLSearchParams(window.location.search);
            params.set('page', 'gestion_notes_evaluations');
            params.set('niveau', niveauFilter.value);
            params.set('annee', anneeFilter.value);
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

    window.cmEditNote = function (num, nom, prenom, m1, m2) {
        if (studentHidden) {
            studentHidden.value = num;
        }
        if (numDisplay) {
            numDisplay.value = num;
        }
        if (nomDisplay) {
            nomDisplay.value = nom;
        }
        if (prenomDisplay) {
            prenomDisplay.value = prenom;
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
                window.alert('Veuillez selectionner un etudiant.');
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
        const headers = ['N° Etudiant', 'Nom', 'Prenom', 'Moy. M1', 'Moy. M2', 'Date saisie'];
        const lines = [headers.join(';')];

        noteRows().forEach(function (row) {
            if (row.style.display === 'none') {
                return;
            }
            const cells = Array.from(row.querySelectorAll('td')).slice(1, 7);
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

    syncStudentFields();
    updateSelectionState();
})();
</script>
