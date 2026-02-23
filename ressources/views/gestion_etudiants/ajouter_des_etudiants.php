<?php
require_once __DIR__ . '/../../../app/utils/permissions_helper.php';

$listeEtudiants = is_array($GLOBALS['listeEtudiants'] ?? null) ? $GLOBALS['listeEtudiants'] : [];
$allEtudiants = is_array($GLOBALS['allEtudiants'] ?? null) ? $GLOBALS['allEtudiants'] : [];
$etudiantAModifier = $GLOBALS['etudiant_a_modifier'] ?? null;
$listeNiveaux = is_array($GLOBALS['listeNiveaux'] ?? null) ? $GLOBALS['listeNiveaux'] : [];
$listeAnneesAcad = is_array($GLOBALS['listeAnneesAcad'] ?? null) ? $GLOBALS['listeAnneesAcad'] : [];

$currentPage = max(1, (int) ($GLOBALS['currentPage'] ?? 1));
$itemsPerPage = max(2, (int) ($GLOBALS['itemsPerPage'] ?? 10));
$totalItems = max(0, (int) ($GLOBALS['totalItems'] ?? count($listeEtudiants)));

$allowedLimits = [2, 5, 10, 25, 50, 100];
if (!in_array($itemsPerPage, $allowedLimits, true)) {
    $itemsPerPage = 10;
}

$anneeActiveId = null;
$anneeActiveLabel = date('Y') . '-' . (date('Y') + 1);
$today = date('Y-m-d');

foreach ($listeAnneesAcad as $annee) {
    $dateDebut = (string) ($annee->date_deb ?? '');
    $dateFin = (string) ($annee->date_fin ?? '');
    if ($dateDebut !== '' && $dateFin !== '' && $today >= $dateDebut && $today <= $dateFin) {
        $anneeActiveId = (int) ($annee->id_annee_acad ?? 0);
        $anneeActiveLabel = date('Y', strtotime($dateDebut)) . '-' . date('Y', strtotime($dateFin));
        break;
    }
}

// Find Master 2 ID for auto-selection (before formValues)
$master2Id = '';
foreach ($listeNiveaux as $niveau) {
    $libelle = strtolower(trim((string) ($niveau->lib_niv_etude ?? '')));
    if (strpos($libelle, 'master 2') !== false || strpos($libelle, 'master2') !== false) {
        $master2Id = (string) ($niveau->id_niv_etude ?? '');
        break;
    }
}

$formValues = [
    'id_annee_acad' => $anneeActiveId,
    'identifiant_mesrs' => '',
    'num_etu' => '',
    'nom_etu' => '',
    'prenom_etu' => '',
    'date_naiss_etu' => '',
    'genre_etu' => '',
    'id_niveau' => $master2Id, // Auto-select Master 2
    'promotion_etu' => $anneeActiveLabel, // Auto-select current year
    'email_etu' => '',
];

if (is_object($etudiantAModifier)) {
    $formValues['id_annee_acad'] = (int) ($etudiantAModifier->id_annee_acad ?? $anneeActiveId);
    $formValues['identifiant_mesrs'] = (string) ($etudiantAModifier->identifiant_mesrs ?? '');
    $formValues['num_etu'] = (string) ($etudiantAModifier->num_carte_etud ?? '');
    $formValues['nom_etu'] = (string) ($etudiantAModifier->nom_etu ?? '');
    $formValues['prenom_etu'] = (string) ($etudiantAModifier->prenom_etu ?? '');
    $formValues['date_naiss_etu'] = (string) ($etudiantAModifier->date_naiss_etu ?? '');
    $formValues['genre_etu'] = (string) ($etudiantAModifier->genre_etu ?? '');
    $formValues['id_niveau'] = (string) ($etudiantAModifier->id_niveau ?? '');
    $formValues['promotion_etu'] = (string) ($etudiantAModifier->promotion_etu ?? $anneeActiveLabel);
    $formValues['email_etu'] = (string) ($etudiantAModifier->email_etu ?? '');
}

$pagination = function_exists('cm_paginate')
    ? cm_paginate($totalItems, $itemsPerPage, $currentPage)
    : [
        'total' => $totalItems,
        'per_page' => $itemsPerPage,
        'current' => $currentPage,
        'last' => max(1, (int) ceil($totalItems / $itemsPerPage)),
        'offset' => max(0, ($currentPage - 1) * $itemsPerPage),
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < max(1, (int) ceil($totalItems / $itemsPerPage)),
        'pages' => [$currentPage],
    ];

$paginationBaseUrl = '?page=gestion_etudiants&action=ajouter_des_etudiants&limit=' . $itemsPerPage;
?>

<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php
    cm_component('layout/page-header', [
        'title' => 'Gestion des etudiants',
        'subtitle' => 'Pole superieur: saisie / Pole inferieur: historique des etudiants.',
        'annee' => '',
        'icon' => 'fa-user-graduate',
    ]);
    ?>

    <?php if (!empty($GLOBALS['messageSuccess'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => (string) $GLOBALS['messageSuccess']]); ?>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['messageErreur'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => (string) $GLOBALS['messageErreur']]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
    <div class="cm-pole-superieur">
        <div class="cm-pole-superieur-title">
            <h2>
                <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                <?php echo is_object($etudiantAModifier) ? 'Modification etudiant' : 'Ajout etudiant'; ?>
            </h2>
        </div>

        <form id="studentForm" method="POST" action="?page=gestion_etudiants&action=ajouter_des_etudiants">
            <?php cm_component('form/csrf-token'); ?>
            <?php if (is_object($etudiantAModifier)): ?>
                <input type="hidden" name="old_num_etu" value="<?php echo htmlspecialchars((string) ($etudiantAModifier->num_carte_etud ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <input type="hidden" id="num_ident_etud" name="num_ident_etud" value="<?php echo htmlspecialchars((string) $formValues['identifiant_mesrs'], ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Ligne 1: Niveau, Promotion, Année A. (grid-3) -->
            <div class="cm-grid-3">
                <?php
                $niveauOptions = [];
                foreach ($listeNiveaux as $niveau) {
                    $niveauOptions[(int) ($niveau->id_niv_etude ?? 0)] = (string) ($niveau->lib_niv_etude ?? 'Niveau');
                }
                cm_component('form/select', [
                    'name' => 'id_niveau',
                    'id' => 'id_niveau',
                    'label' => 'Niveau',
                    'required' => false,
                    'options' => $niveauOptions,
                    'selected' => (string) $formValues['id_niveau'],
                ]);

                $promotionOptions = [];
                foreach ($listeAnneesAcad as $annee) {
                    $debut = !empty($annee->date_deb) ? date('Y', strtotime((string) $annee->date_deb)) : '';
                    $fin = !empty($annee->date_fin) ? date('Y', strtotime((string) $annee->date_fin)) : '';
                    $label = trim($debut . '-' . $fin, '-');
                    if ($label !== '') {
                        $promotionOptions[$label] = $label;
                    }
                }
                cm_component('form/select', [
                    'name' => 'promotion_etu',
                    'id' => 'promotion_etu',
                    'label' => 'Promotion',
                    'required' => true,
                    'options' => $promotionOptions,
                    'selected' => (string) $formValues['promotion_etu'],
                ]);

                $anneeOptions = [];
                foreach ($listeAnneesAcad as $annee) {
                    $anneeId = (int) ($annee->id_annee_acad ?? 0);
                    $debut = !empty($annee->date_deb) ? date('Y', strtotime((string) $annee->date_deb)) : '';
                    $fin = !empty($annee->date_fin) ? date('Y', strtotime((string) $annee->date_fin)) : '';
                    $anneeOptions[$anneeId] = trim($debut . '-' . $fin, '-');
                }
                cm_component('form/select', [
                    'name' => 'id_annee_acad',
                    'id' => 'id_annee_acad',
                    'label' => 'Annee A.',
                    'required' => false,
                    'options' => $anneeOptions,
                    'selected' => (string) ($formValues['id_annee_acad'] ?? ''),
                ]);
                ?>
            </div>

            <!-- Ligne 2: Identifiant (MESRS), N° Carte Étudiant, Nom, Prénom (grid-4) -->
            <div class="cm-grid-4">
                <?php
                cm_component('form/input-text', [
                    'name' => 'identifiant_mesrs',
                    'id' => 'identifiant_mesrs',
                    'label' => 'Identifiant (MESRS)',
                    'maxlength' => 25,
                    'value' => (string) $formValues['identifiant_mesrs'],
                ]);

                cm_component('form/input-text', [
                    'name' => 'num_etu',
                    'id' => 'num_etu',
                    'label' => 'N° Carte Etudiant',
                    'maxlength' => 25,
                    'required' => true,
                    'value' => (string) $formValues['num_etu'],
                ]);

                cm_component('form/input-text', [
                    'name' => 'nom_etu',
                    'id' => 'nom_etu',
                    'label' => 'Nom',
                    'maxlength' => 50,
                    'required' => true,
                    'value' => (string) $formValues['nom_etu'],
                ]);

                cm_component('form/input-text', [
                    'name' => 'prenom_etu',
                    'id' => 'prenom_etu',
                    'label' => 'Prenom',
                    'maxlength' => 100,
                    'required' => true,
                    'value' => (string) $formValues['prenom_etu'],
                ]);
                ?>
            </div>

            <!-- Ligne 3: Date Naissance, Genre, E-mail (grid-3) -->
            <div class="cm-grid-3">
                <?php
                cm_component('form/input-date', [
                    'name' => 'date_naiss_etu',
                    'id' => 'date_naiss_etu',
                    'label' => 'Date Naissance',
                    'required' => true,
                    'value' => (string) $formValues['date_naiss_etu'],
                ]);

                cm_component('form/select', [
                    'name' => 'genre_etu',
                    'id' => 'genre_etu',
                    'label' => 'Genre',
                    'required' => true,
                    'options' => [
                        '1' => 'Masculin',
                        '2' => 'Feminin',
                        '3' => 'Neutre',
                    ],
                    'selected' => (string) $formValues['genre_etu'],
                ]);

                cm_component('form/input-email', [
                    'name' => 'email_etu',
                    'id' => 'email_etu',
                    'label' => 'E-mail',
                    'required' => true,
                    'maxlength' => 60,
                    'value' => (string) $formValues['email_etu'],
                ]);
                ?>
            </div>

            <div class="cm-form-buttons">
                <?php if (is_object($etudiantAModifier)): ?>
                    <a class="cm-btn is-light" href="?page=gestion_etudiants&action=ajouter_des_etudiants">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                        Annuler
                    </a>
                    <?php if (canEdit()): ?>
                        <button class="cm-btn is-success" type="submit" name="submit_modifier_etudiant">
                            <i class="fas fa-floppy-disk" aria-hidden="true"></i>
                            Modifier
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="cm-btn is-light" type="reset">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i>
                        Reinitialiser
                    </button>
                    <?php if (canCreate()): ?>
                        <button class="cm-btn is-success" type="submit" name="submit_add_etudiant">
                            <i class="fas fa-floppy-disk" aria-hidden="true"></i>
                            Enregistrer
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="cm-barre-intermediaire">
        <div class="cm-toolbar">
            <div class="cm-toolbar-left">
                <label for="cmStudentLimit"><strong>Afficher:</strong></label>
                <select id="cmStudentLimit" class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs">
                    <?php foreach ($allowedLimits as $limit): ?>
                        <option value="<?php echo $limit; ?>" <?php echo $limit === $itemsPerPage ? 'selected' : ''; ?>>
                            <?php echo $limit; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="cm-badge is-info cm-toolbar-year">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($anneeActiveLabel, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>

            <div class="cm-toolbar-center">
                <input type="text" id="cmStudentSearch" class="cm-form-control" placeholder="Rechercher (nom, prenom, numero, email)...">
            </div>

            <div class="cm-toolbar-right">
                <button type="button" class="cm-btn is-info is-sm" id="cmSelectAllBtn">
                    <i class="fas fa-check-square" aria-hidden="true"></i>
                    Tout selectionner
                </button>
                <button type="button" class="cm-btn is-light is-sm" id="cmDeselectAllBtn">
                    <i class="fas fa-square" aria-hidden="true"></i>
                    Deselectionner
                </button>
                <?php if (canDelete() || canEdit()): ?>
                    <button type="button" class="cm-btn is-info is-sm" id="cmDeleteSelectedBtn" disabled>
                        <i class="fas fa-trash" aria-hidden="true"></i>
                        Supprimer (<span id="cmSelectedCount">0</span>)
                    </button>
                <?php endif; ?>
                <button type="button" class="cm-btn is-info is-sm" id="cmPrintBtn">
                    <i class="fas fa-print" aria-hidden="true"></i>
                    Imprimer
                </button>
                <button type="button" class="cm-btn is-info is-sm" id="cmExportBtn">
                    <i class="fas fa-file-export" aria-hidden="true"></i>
                    Exporter
                </button>
            </div>
        </div>
    </div>

    <div class="cm-pole-inferieur">
        <form id="studentsBulkForm" method="POST" action="?page=gestion_etudiants&action=ajouter_des_etudiants" class="cm-table-form">
            <?php cm_component('form/csrf-token'); ?>
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmStudentsTable">
                    <thead>
                    <tr>
                        <?php if (canEdit() || canDelete()): ?>
                            <th class="cm-data-table__th is-checkbox">
                                <input type="checkbox" id="cmCheckAllRows" class="cm-checkbox" aria-label="Selectionner toutes les lignes">
                            </th>
                        <?php endif; ?>
                        <th class="cm-data-table__th">N° Carte Etud.</th>
                        <th class="cm-data-table__th">ID MESRS</th>
                        <th class="cm-data-table__th">Nom</th>
                        <th class="cm-data-table__th">Prenom</th>
                        <th class="cm-data-table__th">Date Nais.</th>
                        <th class="cm-data-table__th">Genre</th>
                        <th class="cm-data-table__th">Email</th>
                        <th class="cm-data-table__th">Promotion</th>
                        <?php if (canEdit()): ?>
                            <th class="cm-data-table__th is-center">Actions</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody id="cmStudentsTableBody">
                    <?php if (empty($listeEtudiants)): ?>
                        <?php cm_component('ui/empty-state', [
                            'in_table' => true,
                            'colspan' => (canEdit() || canDelete()) ? (canEdit() ? 10 : 9) : (canEdit() ? 9 : 8),
                            'title' => 'Aucun etudiant',
                            'message' => 'Aucun enregistrement disponible pour cette page.',
                        ]); ?>
                    <?php else: ?>
                        <?php foreach ($listeEtudiants as $etudiant): ?>
                            <?php
                            $numEtu = (string) ($etudiant->num_carte_etud ?? '');
                            $nom = (string) ($etudiant->nom_etu ?? '');
                            $prenom = (string) ($etudiant->prenom_etu ?? '');
                            $idMesrs = (string) ($etudiant->identifiant_mesrs ?? '');
                            $dateNaiss = (string) ($etudiant->date_naiss_etu ?? '');
                            $genre = (string) ($etudiant->libelle_genre ?? $etudiant->genre_etu ?? '');
                            $email = (string) ($etudiant->email_etu ?? '');
                            $promotion = (string) ($etudiant->promotion_etu ?? '');
                            ?>
                            <tr class="cm-data-table__row" data-search="<?php echo htmlspecialchars(strtolower($numEtu . ' ' . $nom . ' ' . $prenom . ' ' . $email . ' ' . $idMesrs), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if (canEdit() || canDelete()): ?>
                                    <td class="cm-data-table__td is-checkbox">
                                        <input type="checkbox"
                                               class="cm-checkbox cm-row-checkbox"
                                               name="selected_ids[]"
                                               value="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>">
                                    </td>
                                <?php endif; ?>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($idMesrs, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($dateNaiss, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($genre, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($promotion, ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php if (canEdit()): ?>
                                    <td class="cm-data-table__td is-center">
                                        <div class="cm-row-actions">
                                            <a class="cm-btn-action is-edit"
                                               href="?page=gestion_etudiants&action=ajouter_des_etudiants&num_etu=<?php echo urlencode($numEtu); ?>"
                                               title="Modifier">
                                                <i class="fas fa-pen" aria-hidden="true"></i>
                                            </a>
                                            <?php if (canDelete() || canEdit()): ?>
                                                <button type="button"
                                                        class="cm-btn-action is-delete"
                                                        onclick="submitSingleDelete('<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($nom . ' ' . $prenom, ENT_QUOTES, 'UTF-8'); ?>')"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <?php cm_component('crud/pagination', [
            'pagination' => $pagination,
            'base_url' => $paginationBaseUrl,
            'param_name' => 'p',
        ]); ?>
    </div>
</div>
</div>

<script>
(function () {
    const identifiantInput = document.getElementById('identifiant_mesrs');
    const hiddenNumIdent = document.getElementById('num_ident_etud');
    const bulkForm = document.getElementById('studentsBulkForm');
    const checkAll = document.getElementById('cmCheckAllRows');
    const searchInput = document.getElementById('cmStudentSearch');
    const limitSelect = document.getElementById('cmStudentLimit');
    const selectedCount = document.getElementById('cmSelectedCount');
    const deleteBtn = document.getElementById('cmDeleteSelectedBtn');
    const navigate = function (url) {
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(url);
            return;
        }
        window.location.href = url;
    };

    const rowCheckboxes = function () {
        return Array.from(document.querySelectorAll('#cmStudentsTableBody .cm-row-checkbox'));
    };

    const visibleRows = function () {
        return Array.from(document.querySelectorAll('#cmStudentsTableBody tr')).filter(function (row) {
            return row.style.display !== 'none';
        });
    };

    const updateSelectionState = function () {
        const checked = rowCheckboxes().filter(function (cb) { return cb.checked; }).length;
        if (selectedCount) {
            selectedCount.textContent = String(checked);
        }
        if (deleteBtn) {
            deleteBtn.disabled = checked === 0;
        }
        if (checkAll) {
            const all = rowCheckboxes();
            checkAll.checked = all.length > 0 && all.every(function (cb) { return cb.checked; });
        }
    };

    if (identifiantInput && hiddenNumIdent) {
        identifiantInput.addEventListener('input', function () {
            hiddenNumIdent.value = identifiantInput.value;
        });
    }

    const selectAllBtn = document.getElementById('cmSelectAllBtn');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            rowCheckboxes().forEach(function (cb) { cb.checked = true; });
            updateSelectionState();
        });
    }

    const deselectAllBtn = document.getElementById('cmDeselectAllBtn');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function () {
            rowCheckboxes().forEach(function (cb) { cb.checked = false; });
            updateSelectionState();
        });
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rowCheckboxes().forEach(function (cb) { cb.checked = checkAll.checked; });
            updateSelectionState();
        });
    }

    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('cm-row-checkbox')) {
            updateSelectionState();
        }
    });

    if (deleteBtn && bulkForm) {
        deleteBtn.addEventListener('click', function () {
            const selected = rowCheckboxes().filter(function (cb) { return cb.checked; });
            if (selected.length === 0) {
                return;
            }
            const confirmDelete = window.confirm('Confirmer la suppression de ' + selected.length + ' etudiant(s) ?');
            if (confirmDelete) {
                bulkForm.submit();
            }
        });
    }

    window.submitSingleDelete = function (numEtu, fullName) {
        const confirmed = window.confirm('Confirmer la suppression de l\'etudiant: ' + fullName + ' (' + numEtu + ') ?');
        if (!confirmed || !bulkForm) {
            return;
        }
        rowCheckboxes().forEach(function (cb) {
            cb.checked = cb.value === numEtu;
        });
        updateSelectionState();
        bulkForm.submit();
    };

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = searchInput.value.trim().toLowerCase();
            document.querySelectorAll('#cmStudentsTableBody tr').forEach(function (row) {
                const haystack = row.getAttribute('data-search') || '';
                row.style.display = haystack.indexOf(term) !== -1 ? '' : 'none';
            });
        });
    }

    if (limitSelect) {
        limitSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('limit', String(limitSelect.value));
            url.searchParams.set('p', '1');
            navigate(url.toString());
        });
    }

    const printBtn = document.getElementById('cmPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    const exportBtn = document.getElementById('cmExportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const headers = ['N° Carte Etud.', 'ID MESRS', 'Nom', 'Prenom', 'Date Nais.', 'Genre', 'Email', 'Promotion'];
            const lines = [headers.join(';')];

            visibleRows().forEach(function (row) {
                const cells = Array.from(row.querySelectorAll('td'));
                if (cells.length === 0) {
                    return;
                }

                const startIndex = <?php echo (canEdit() || canDelete()) ? '1' : '0'; ?>;
                const endIndex = <?php echo canEdit() ? '-1' : 'cells.length'; ?>;
                const dataCells = endIndex === -1 ? cells.slice(startIndex, cells.length - 1) : cells.slice(startIndex);

                const rowValues = dataCells.map(function (cell) {
                    return '"' + (cell.textContent || '').trim().replace(/"/g, '""') + '"';
                });
                lines.push(rowValues.join(';'));
            });

            const blob = new Blob(["\uFEFF" + lines.join('\n')], {type: 'text/csv;charset=utf-8;'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'etudiants_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    updateSelectionState();
})();
</script>
