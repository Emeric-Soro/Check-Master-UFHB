<?php
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
$anneeActiveId = \AcademicYear::getActiveIdFromSession();
$anneeActiveLabel = \AcademicYear::getActiveLabelFromSession();
$anneeSelectionneeId = \AcademicYear::getSelectedIdFromSession();
$anneeSelectionneeLabel = \AcademicYear::getSelectedLabelFromSession();
$anneeSelectionToutes = \AcademicYear::isAllSelectedFromSession();
$anneeEcritureId = \AcademicYear::getWritableIdFromSession();
$anneeEcritureLabel = \AcademicYear::getWritableLabelFromSession();
$ecritureAutorisee = \AcademicYear::isWriteAllowedFromSession();
$today = date('Y-m-d');
if ($anneeActiveId === null || $anneeActiveLabel === '') {
    $anneeActiveLabel = date('Y') . '-' . (date('Y') + 1);
    foreach ($listeAnneesAcad as $annee) {
        $dateDebut = (string) ($annee->date_deb ?? '');
        $dateFin = (string) ($annee->date_fin ?? '');
        if ($dateDebut !== '' && $dateFin !== '' && $today >= $dateDebut && $today <= $dateFin) {
            $anneeActiveId = (int) ($annee->id_annee_acad ?? 0);
            $anneeActiveLabel = date('Y', strtotime($dateDebut)) . '-' . date('Y', strtotime($dateFin));
            break;
        }
    }
}
if ($anneeSelectionneeLabel === '') {
    $anneeSelectionneeLabel = $anneeSelectionToutes
        ? \AcademicYear::getAllLabel()
        : ($anneeEcritureLabel !== '' ? $anneeEcritureLabel : $anneeActiveLabel);
}
// Build promotion options keyed by id_annee_acad
$promotionOptions = [];
$promotionLabelToId = [];
foreach ($listeAnneesAcad as $annee) {
    $id = (string) ($annee->id_annee_acad ?? '');
    $debut = !empty($annee->date_deb) ? date('Y', strtotime((string) $annee->date_deb)) : '';
    $fin = !empty($annee->date_fin) ? date('Y', strtotime((string) $annee->date_fin)) : '';
    $label = trim($debut . '-' . $fin, '-');
    if ($id !== '' && $label !== '') {
        $promotionOptions[$id] = \FormattingUtils::formatPromotion($label);
        $promotionLabelToId[$label] = $id;
    }
}
$defaultPromotionId = '';
if (!empty($anneeEcritureId)) {
    $defaultPromotionId = (string) $anneeEcritureId;
} elseif (!empty($anneeSelectionneeId)) {
    $defaultPromotionId = (string) $anneeSelectionneeId;
} elseif (!empty($anneeActiveId)) {
    $defaultPromotionId = (string) $anneeActiveId;
} elseif (!empty($promotionOptions)) {
    $defaultPromotionId = (string) array_key_first($promotionOptions);
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
    'id_annee_acad' => $defaultPromotionId,
    'identifiant_mesrs' => '',
    'num_etu' => '',
    'nom_etu' => '',
    'prenom_etu' => '',
    'date_naiss_etu' => '',
    'genre_etu' => 'M',
    'id_niveau' => $master2Id, // Auto-select Master 2
    'promotion_etu' => $defaultPromotionId,
    'email_etu' => '',
];
if (is_object($etudiantAModifier)) {
    $formValues['id_annee_acad'] = (string) ($etudiantAModifier->id_annee_acad ?? $formValues['id_annee_acad']);
    $formValues['identifiant_mesrs'] = (string) ($etudiantAModifier->identifiant_mesrs ?? '');
    $formValues['num_etu'] = (string) ($etudiantAModifier->num_carte_etud ?? '');
    $formValues['nom_etu'] = (string) ($etudiantAModifier->nom_etu ?? '');
    $formValues['prenom_etu'] = (string) ($etudiantAModifier->prenom_etu ?? '');
    $formValues['date_naiss_etu'] = (string) ($etudiantAModifier->date_naiss_etu ?? '');
    $formValues['genre_etu'] = (string) ($etudiantAModifier->id_genre ?? $etudiantAModifier->genre_etu ?? '');
    $formValues['id_niveau'] = (string) ($etudiantAModifier->id_niveau ?? '');
    $promotionRaw = (string) ($etudiantAModifier->promotion_etu ?? '');
    if ($promotionRaw !== '') {
        if (ctype_digit($promotionRaw)) {
            $formValues['promotion_etu'] = $promotionRaw;
        } elseif (isset($promotionLabelToId[$promotionRaw])) {
            $formValues['promotion_etu'] = (string) $promotionLabelToId[$promotionRaw];
        }
    }
    $formValues['email_etu'] = (string) ($etudiantAModifier->email_etu ?? '');
}
if ($formValues['promotion_etu'] !== '') {
    $formValues['id_annee_acad'] = $formValues['promotion_etu'];
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
$preservedListParams = '&limit=' . urlencode((string) $itemsPerPage) . '&p=' . urlencode((string) $currentPage);
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php
    cm_component('layout/page-header', [
        'title' => 'Ajouter / modifier des étudiants',
        'subtitle' => 'Pôle supérieur: saisie / Pôle inférieur: historique des étudiants.',
        'annee' => $anneeSelectionneeLabel,
        'icon' => 'fa-user-graduate',
    ]);
    ?>
    <?php if (!empty($GLOBALS['messageSuccess'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => (string) $GLOBALS['messageSuccess']]); ?>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['messageErreur'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => (string) $GLOBALS['messageErreur']]); ?>
    <?php endif; ?>
    <?php if ($anneeSelectionToutes): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'info',
            'message' => "Affichage multi-années actif. Les nouveaux étudiants seront rattachés à l'année académique active {$anneeEcritureLabel}.",
        ]); ?>
    <?php elseif (!$ecritureAutorisee): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'warning',
            'message' => "Consultation historique active. Les enregistrements et modifications sont réservés à l'année académique active {$anneeActiveLabel}.",
        ]); ?>
    <?php endif; ?>
    <div class="cm-crud-wrapper">
        <div class="">
            <div class="">
            </div>
            <style>
                /* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
                #studentForm .cm-form-group:has(#FIELD_ID) {
                    width: 10ch !important;
                    min-width: 10ch !important;
                    max-width: 10ch !important;
                }

                /* Compact adjustments for Data Table */
                .cm-prd3-crud-screen .cm-table-wrapper {
                    margin-top: 0 !important;
                }

                .cm-prd3-crud-screen .cm-data-table th,
                .cm-prd3-crud-screen .cm-data-table td {
                    padding: 0.3rem 0.45rem !important;
                    font-size: 0.8rem !important;
                    line-height: 1.2 !important;
                }

                .cm-prd3-crud-screen .cm-data-table__th.is-checkbox,
                .cm-prd3-crud-screen .cm-data-table__td.is-checkbox {
                    width: 2rem !important;
                    padding: 0.3rem 0.2rem !important;
                }

                .cm-prd3-crud-screen .cm-checkbox {
                    width: 13px !important;
                    height: 13px !important;
                }

                /* Row Action Buttons inside Table */
                .cm-prd3-crud-screen .cm-row-actions {
                    gap: 0.25rem !important;
                    justify-content: center !important;
                }

                .cm-prd3-crud-screen .cm-row-actions .cm-btn-action {
                    width: 1.55rem !important;
                    height: 1.55rem !important;
                    font-size: 0.72rem !important;
                    border-radius: 4px !important;
                    padding: 0 !important;
                    min-height: 0 !important;
                    display: inline-flex !important;
                    align-items: center;
                    justify-content: center;
                }
            </style>
            <form id="studentForm" class="cm-ajout-etudiant-form" method="POST"
                action="?page=gestion_etudiants&action=ajouter_des_etudiants<?php echo $preservedListParams; ?>">
                <?php cm_component('form/csrf-token'); ?>
                <?php if (is_object($etudiantAModifier)): ?>
                    <input type="hidden" name="old_num_etu"
                        value="<?php echo htmlspecialchars((string) ($etudiantAModifier->num_carte_etud ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                <input type="hidden" id="num_ident_etud" name="num_ident_etud"
                    value="<?php echo htmlspecialchars((string) $formValues['identifiant_mesrs'], ENT_QUOTES, 'UTF-8'); ?>">
                <!-- Ligne 1: Promotion seule (grid-1) -->
                <div class="cm-grid-1">
                    <?php
                    cm_component('form/select', [
                        'name' => 'promotion_etu',
                        'id' => 'promotion_etu',
                        'label' => 'Promotion',
                        'required' => true,
                        'options' => $promotionOptions,
                        'selected' => (string) $formValues['promotion_etu'],
                        'control_class' => 'cm-field-sm',
                    ]);
                    echo '<input type="hidden" id="id_annee_acad" name="id_annee_acad" value="' . htmlspecialchars((string) $formValues['id_annee_acad'], ENT_QUOTES, 'UTF-8') . '">';
                    ?>
                </div>
                <!-- Ligne 2: Identifiant MESRS, N° Carte, Nom, Prénom (grid-4) -->
                <div class="cm-grid-4">
                    <?php
                    cm_component('form/input-text', [
                        'name' => 'identifiant_mesrs',
                        'id' => 'identifiant_mesrs',
                        'label' => 'Identifiant (MESRS)',
                        'maxlength' => 15,
                        'value' => (string) $formValues['identifiant_mesrs'],
                        'control_class' => 'cm-field-sm',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'num_etu',
                        'id' => 'num_etu',
                        'label' => 'N° Carte Etudiant',
                        'maxlength' => 15,
                        'required' => true,
                        'value' => (string) $formValues['num_etu'],
                        'control_class' => 'cm-field-sm',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'nom_etu',
                        'id' => 'nom_etu',
                        'label' => 'Nom',
                        'maxlength' => 50,
                        'required' => true,
                        'value' => (string) $formValues['nom_etu'],
                        'control_class' => 'cm-field-md',
                    ]);
                    cm_component('form/input-text', [
                        'name' => 'prenom_etu',
                        'id' => 'prenom_etu',
                        'label' => 'Prénom',
                        'maxlength' => 100,
                        'required' => true,
                        'value' => (string) $formValues['prenom_etu'],
                        'control_class' => 'cm-field-md',
                    ]);
                    ?>
                </div>
                <!-- Ligne 3: Date Naissance, Genre, E-mail, (vide) (grid-4) -->
                <div class="cm-grid-4">
                    <?php
                    cm_component('form/input-date', [
                        'name' => 'date_naiss_etu',
                        'id' => 'date_naiss_etu',
                        'label' => 'Date Naissance',
                        'required' => true,
                        'value' => (string) $formValues['date_naiss_etu'],
                        'control_class' => 'cm-field-sm',
                    ]);
                    cm_component('form/select', [
                        'name' => 'genre_etu',
                        'id' => 'genre_etu',
                        'label' => 'Genre',
                        'required' => true,
                        'placeholder' => '',
                        'options' => [
                            'M' => 'M',
                            'F' => 'F',
                            'N' => 'N',
                        ],
                        'selected' => (string) ($formValues['genre_etu'] !== '' ? $formValues['genre_etu'] : 'M'),
                        'control_class' => 'cm-field-xs',
                    ]);
                    cm_component('form/input-email', [
                        'name' => 'email_etu',
                        'id' => 'email_etu',
                        'label' => 'E-mail',
                        'required' => true,
                        'maxlength' => 60,
                        'value' => (string) $formValues['email_etu'],
                        'control_class' => 'cm-field-md',
                    ]);
                    ?>
                    <!-- Espace blanc pour alignement avec ligne 2 (4 colonnes) -->
                </div>
                <?php
                if (is_object($etudiantAModifier)) {
                    $cancelHref = '?page=gestion_etudiants&action=ajouter_des_etudiants' . $preservedListParams;
                    $cancelAction = ['label' => 'Annuler', 'tag' => 'a', 'class' => 'cm-btn is-light is-sm', 'href' => $cancelHref];
                    $submitActions = [
                        ['label' => 'Réinitialiser', 'type' => 'reset', 'class' => 'cm-btn is-secondary is-sm'],
                    ];
                    if (canEdit()) {
                        $submitActions[] = ['label' => 'Modifier', 'type' => 'submit', 'name' => 'submit_modifier_etudiant', 'class' => 'cm-btn is-primary is-sm'];
                    }
                } else {
                    $cancelAction = ['label' => 'Annuler', 'type' => 'button', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['data-reset-form' => '1']];
                    $submitActions = [
                        ['label' => 'Réinitialiser', 'type' => 'reset', 'class' => 'cm-btn is-secondary is-sm'],
                    ];
                    if (canCreate()) {
                        $submitActions[] = ['label' => 'Enregistrer', 'type' => 'submit', 'name' => 'submit_add_etudiant', 'class' => 'cm-btn is-primary is-sm'];
                    }
                }
                cm_component('crud/form-actions', [
                    'cancel_action' => $cancelAction,
                    'actions' => $submitActions,
                ]);
                ?>
            </form>
        </div>
        <?php cm_toolbar([
            'screen' => 'gestion_etudiants',
            'id_prefix' => 'students',
            'limit' => $itemsPerPage,
            'limit_options' => $allowedLimits,
            'search_placeholder' => 'Rechercher (nom, prénom, numéro, email)...',
            'can_delete' => canDelete() || canEdit(),
            'can_view' => canView(),
            'custom_actions' => [
                [
                    'tag' => 'a',
                    'href' => '?page=gestion_etudiants&action=importer_etudiants',
                    'label' => 'Importer',
                    'icon' => 'fa-file-import',
                    'class' => 'cm-btn is-secondary is-sm',
                ],
            ],
        ]); ?>
        <div class="cm-pole-inferieur">
            <form id="studentsBulkForm" method="POST" action="?page=gestion_etudiants&action=ajouter_des_etudiants"
                class="cm-table-form">
                <?php cm_component('form/csrf-token'); ?>
                <div class="cm-table-wrapper">
                    <table class="cm-data-table" id="cmStudentsTable">
                        <thead>
                            <tr>
                                <?php if (canEdit() || canDelete()): ?>
                                    <th class="cm-data-table__th is-checkbox">
                                        <input type="checkbox" id="cmCheckAllRows" class="cm-checkbox"
                                            aria-label="Sélectionner toutes les lignes">
                                    </th>
                                <?php endif; ?>
                                <th class="cm-data-table__th cm-col-id" data-sort-field="id_mesrs">ID MESRS</th>
                                <th class="cm-data-table__th cm-col-id" data-sort-field="num_etu">N° Carte Etud.</th>
                                <th class="cm-data-table__th" data-sort-field="nom">Nom &amp; Prénom</th>
                                <th class="cm-data-table__th cm-col-date" data-sort-field="date_naiss">Date Nais.</th>
                                <th class="cm-data-table__th cm-col-genre" data-sort-field="genre">Genre</th>
                                <th class="cm-data-table__th" data-sort-field="promotion">Promotion</th>
                                <?php if (canEdit()): ?>
                                    <th class="cm-data-table__th is-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="cmStudentsTableBody">
                            <?php if (empty($listeEtudiants)): ?>
                                <?php cm_component('ui/empty-state', [
                                    'in_table' => true,
                                    'colspan' => (canEdit() || canDelete()) ? (canEdit() ? 9 : 8) : (canEdit() ? 8 : 7),
                                    'title' => '',
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
                                    $genreRaw = (string) ($etudiant->libelle_genre ?? $etudiant->genre_etu ?? '');
                                    $genreShort = '';
                                    if ($genreRaw !== '') {
                                        $genreUpper = strtoupper(trim($genreRaw));
                                        if (in_array($genreUpper, ['M', 'F', 'N'], true)) {
                                            $genreShort = $genreUpper;
                                        } else {
                                            $genreNormalized = strtolower(trim($genreRaw));
                                            $genreMap = [
                                                'masculin' => 'M',
                                                'feminin' => 'F',
                                                'féminin' => 'F',
                                                'neutre' => 'N',
                                            ];
                                            if (isset($genreMap[$genreNormalized])) {
                                                $genreShort = $genreMap[$genreNormalized];
                                            } elseif ($genreNormalized !== '') {
                                                $genreShort = strtoupper(substr($genreNormalized, 0, 1));
                                            }
                                        }
                                    }
                                    $email = (string) ($etudiant->email_etu ?? '');
                                    $promotion = \FormattingUtils::formatPromotion((string) ($etudiant->promotion_etu ?? ''));
                                    ?>
                                    <tr class="cm-data-table__row"
                                        data-search="<?php echo htmlspecialchars(strtolower($numEtu . ' ' . $nom . ' ' . $prenom . ' ' . $email . ' ' . $idMesrs), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-num-etu="<?php echo htmlspecialchars(strtolower($numEtu), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-id-mesrs="<?php echo htmlspecialchars(strtolower($idMesrs), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-nom="<?php echo htmlspecialchars(strtolower($nom), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-prenom="<?php echo htmlspecialchars(strtolower($prenom), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-date-naiss="<?php echo htmlspecialchars($dateNaiss, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-genre="<?php echo htmlspecialchars(strtolower($genreShort), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-email="<?php echo htmlspecialchars(strtolower($email), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-promotion="<?php echo htmlspecialchars($promotion, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php if (canEdit() || canDelete()): ?>
                                            <td class="cm-data-table__td is-checkbox">
                                                <input type="checkbox" class="cm-checkbox cm-row-checkbox" name="selected_ids[]"
                                                    value="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>">
                                            </td>
                                        <?php endif; ?>
                                        <td class="cm-data-table__td cm-col-id">
                                            <?php echo htmlspecialchars($idMesrs, ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cm-data-table__td cm-col-id">
                                            <?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php echo htmlspecialchars(strtoupper($nom) . ' ' . $prenom, ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cm-data-table__td cm-col-date">
                                            <?php echo htmlspecialchars($dateNaiss, ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cm-data-table__td cm-col-genre">
                                            <?php echo htmlspecialchars($genreShort, ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php echo htmlspecialchars($promotion, ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <?php if (canEdit()): ?>
                                            <td class="cm-data-table__td is-center">
                                                <div class="cm-row-actions">
                                                    <a class="cm-btn-action is-edit"
                                                        href="?page=gestion_etudiants&action=ajouter_des_etudiants&num_etu=<?php echo urlencode($numEtu); ?><?php echo $preservedListParams; ?>"
                                                        title="Modifier">
                                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                                    </a>
                                                    <?php if (canDelete() || canEdit()): ?>
                                                        <button type="button" class="cm-btn-action is-delete"
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
        const sortableHeaders = Array.from(document.querySelectorAll('#cmStudentsTable thead th[data-sort-field]'));
        let currentSort = { field: null, direction: 'asc' };
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
        const parseSortableValue = function (raw) {
            const value = String(raw || '').trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                return new Date(value + 'T00:00:00').getTime();
            }
            if (/^\d+(\.\d+)?$/.test(value)) {
                return Number(value);
            }
            return value.toLowerCase();
        };
        const sortRows = function (field, direction) {
            const tbody = document.getElementById('cmStudentsTableBody');
            if (!tbody) {
                return;
            }
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(function (a, b) {
                const attr = 'data-' + field.replace(/_/g, '-');
                const av = parseSortableValue(a.getAttribute(attr));
                const bv = parseSortableValue(b.getAttribute(attr));
                if (av < bv) {
                    return direction === 'asc' ? -1 : 1;
                }
                if (av > bv) {
                    return direction === 'asc' ? 1 : -1;
                }
                return 0;
            });
            rows.forEach(function (row) {
                tbody.appendChild(row);
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
        const promotionSelect = document.getElementById('promotion_etu');
        const anneeInput = document.getElementById('id_annee_acad');
        if (promotionSelect && anneeInput) {
            promotionSelect.addEventListener('change', function () {
                anneeInput.value = promotionSelect.value;
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
            deleteBtn.addEventListener('click', async function () {
                const selected = rowCheckboxes().filter(function (cb) { return cb.checked; });
                if (selected.length === 0) {
                    return;
                }
                const confirmDelete = await window.CM.confirm('Confirmer la suppression de ' + selected.length + ' étudiant(s) ?');
                if (confirmDelete) {
                    bulkForm.submit();
                }
            });
        }
        window.submitSingleDelete = async function (numEtu, fullName) {
            const confirmed = await window.CM.confirm('Confirmer la suppression de l\'étudiant : ' + fullName + ' (' + numEtu + ') ?');
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
        sortableHeaders.forEach(function (th) {
            th.style.cursor = 'pointer';
            th.title = 'Trier';
            th.addEventListener('click', function () {
                const field = th.getAttribute('data-sort-field');
                if (!field) {
                    return;
                }
                const nextDirection = (currentSort.field === field && currentSort.direction === 'asc') ? 'desc' : 'asc';
                currentSort = { field: field, direction: nextDirection };
                sortableHeaders.forEach(function (header) {
                    header.removeAttribute('data-sort-dir');
                });
                th.setAttribute('data-sort-dir', nextDirection);
                sortRows(field, nextDirection);
                updateSelectionState();
            });
        });
        // Tri par défaut: Promotion desc (derniere année académique en premier)
        const defaultSortField = 'promotion';
        const defaultSortDirection = 'desc';
        currentSort = { field: defaultSortField, direction: defaultSortDirection };
        sortRows(defaultSortField, defaultSortDirection);
        sortableHeaders.forEach(function (header) {
            header.removeAttribute('data-sort-dir');
            if (header.getAttribute('data-sort-field') === defaultSortField) {
                header.setAttribute('data-sort-dir', defaultSortDirection);
            }
        });
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
                const headers = ['ID MESRS', 'N° Carte Etud.', 'Nom', 'Prénom', 'Date Nais.', 'Genre', 'Promotion'];
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
                const blob = new Blob(["\uFEFF" + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
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
