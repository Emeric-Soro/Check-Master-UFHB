<?php
$etudiantsNonInscrits = is_array($GLOBALS['etudiantsNonInscrits'] ?? null) ? $GLOBALS['etudiantsNonInscrits'] : [];
$etudiantsInscrits = is_array($GLOBALS['etudiantsInscrits'] ?? null) ? $GLOBALS['etudiantsInscrits'] : [];
$listeAllEtudiant = is_array($GLOBALS['listeAllEtudiant'] ?? null) ? $GLOBALS['listeAllEtudiant'] : [];
$niveaux = is_array($GLOBALS['niveaux'] ?? null) ? $GLOBALS['niveaux'] : [];
$listeAnnees = is_array($GLOBALS['listeAnnees'] ?? null) ? $GLOBALS['listeAnnees'] : [];
$listeVersement = is_array($GLOBALS['listeVersement'] ?? null) ? $GLOBALS['listeVersement'] : [];
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
    foreach ($listeAnnees as $annee) {
        $debut = (string) ($annee->date_deb ?? '');
        $fin = (string) ($annee->date_fin ?? '');
        if ($debut !== '' && $fin !== '' && $today >= $debut && $today <= $fin) {
            $anneeActiveId = (int) ($annee->id_annee_acad ?? 0);
            $anneeActiveLabel = date('Y', strtotime($debut)) . '-' . date('Y', strtotime($fin));
            break;
        }
    }
}
if ($anneeSelectionneeLabel === '') {
    $anneeSelectionneeLabel = $anneeSelectionToutes
        ? \AcademicYear::getAllLabel()
        : ($anneeEcritureLabel !== '' ? $anneeEcritureLabel : $anneeActiveLabel);
}
$niveauxOptions = [];
$niveauxMontants = [];
foreach ($niveaux as $niveau) {
    $idNiveau = (int) ($niveau['id_niv_etude'] ?? 0);
    $niveauxOptions[$idNiveau] = (string) ($niveau['lib_niv_etude'] ?? 'Niveau');
    $niveauxMontants[$idNiveau] = (float) ($niveau['montant_scolarite'] ?? 0);
}
$anneesOptions = [];
foreach ($listeAnnees as $annee) {
    $id = (int) ($annee->id_annee_acad ?? 0);
    $debut = !empty($annee->date_deb) ? date('Y', strtotime((string) $annee->date_deb)) : '';
    $fin = !empty($annee->date_fin) ? date('Y', strtotime((string) $annee->date_fin)) : '';
    $anneesOptions[$id] = trim($debut . '-' . $fin, '-');
}
$catalog = [];
foreach ($listeAllEtudiant as $etu) {
    $num = (string) ($etu['num_carte_etud'] ?? '');
    if ($num === '') {
        continue;
    }
    $catalog[$num] = [
        'num' => $num,
        'nom' => (string) ($etu['nom_etu'] ?? ''),
        'prenom' => (string) ($etu['prenom_etu'] ?? ''),
        'identifiant' => (string) ($etu['num_ident_etud'] ?? ''),
        'inscrit' => false,
        'id_niveau' => !empty($etu['id_niveau']) ? (int) $etu['id_niveau'] : null,
        'nom_niveau' => '',
        'id_annee' => $anneeEcritureId,
        'montant_total' => 0.0,
        'montant_paye' => 0.0,
        'reste_a_payer' => 0.0,
        'solde' => 0.0,
        'num_versement' => 1,
    ];
}
foreach ($etudiantsNonInscrits as $etu) {
    $num = (string) ($etu['num_etu'] ?? '');
    if ($num === '') {
        continue;
    }
    if (!isset($catalog[$num])) {
        $catalog[$num] = [
            'num' => $num,
            'nom' => (string) ($etu['nom_etu'] ?? ''),
            'prenom' => (string) ($etu['prenom_etu'] ?? ''),
            'identifiant' => '',
            'inscrit' => false,
            'id_niveau' => null,
            'nom_niveau' => '',
            'id_annee' => $anneeEcritureId,
            'montant_total' => 0.0,
            'montant_paye' => 0.0,
            'reste_a_payer' => 0.0,
            'solde' => 0.0,
            'num_versement' => 1,
        ];
    }
}
foreach ($etudiantsInscrits as $etu) {
    $num = (string) ($etu['id_etudiant'] ?? '');
    if ($num === '') {
        continue;
    }
    if (!isset($catalog[$num])) {
        $catalog[$num] = [
            'num' => $num,
            'nom' => (string) ($etu['nom'] ?? ''),
            'prenom' => (string) ($etu['prenom'] ?? ''),
            'identifiant' => '',
            'inscrit' => true,
            'id_niveau' => !empty($etu['id_niveau']) ? (int) $etu['id_niveau'] : null,
            'nom_niveau' => (string) ($etu['nom_niveau'] ?? ''),
            'id_annee' => !empty($etu['id_annee_acad']) ? (int) $etu['id_annee_acad'] : $anneeActiveId,
            'montant_total' => (float) ($etu['montant_scolarite'] ?? 0),
            'montant_paye' => (float) ($etu['montant_paye'] ?? 0),
            'reste_a_payer' => (float) ($etu['reste_a_payer'] ?? 0),
            'solde' => (float) ($etu['solde'] ?? $etu['reste_a_payer'] ?? 0),
            'num_versement' => ((int) ($etu['nombre_versements'] ?? 0)) + 1,
        ];
    } else {
        $catalog[$num]['inscrit'] = true;
        $catalog[$num]['id_niveau'] = !empty($etu['id_niveau']) ? (int) $etu['id_niveau'] : $catalog[$num]['id_niveau'];
        $catalog[$num]['nom_niveau'] = (string) ($etu['nom_niveau'] ?? $catalog[$num]['nom_niveau']);
        $catalog[$num]['id_annee'] = !empty($etu['id_annee_acad']) ? (int) $etu['id_annee_acad'] : $catalog[$num]['id_annee'];
        $catalog[$num]['montant_total'] = (float) ($etu['montant_scolarite'] ?? 0);
        $catalog[$num]['montant_paye'] = (float) ($etu['montant_paye'] ?? 0);
        $catalog[$num]['reste_a_payer'] = (float) ($etu['reste_a_payer'] ?? 0);
        $catalog[$num]['solde'] = (float) ($etu['solde'] ?? $etu['reste_a_payer'] ?? 0);
        $catalog[$num]['num_versement'] = ((int) ($etu['nombre_versements'] ?? 0)) + 1;
    }
}
$studentOptions = [];
foreach ($catalog as $num => $item) {
    $status = !empty($item['inscrit']) ? 'Inscrit' : 'Non inscrit';
    $studentOptions[$num] = trim($item['nom'] . ' ' . $item['prenom']) . ' (' . $num . ') - ' . $status;
}
ksort($studentOptions);
$allowedLimits = [2, 5, 10, 25, 50, 100];
$versementsParPage = max(2, (int) ($_GET['limit_versements'] ?? 10));
if (!in_array($versementsParPage, $allowedLimits, true)) {
    $versementsParPage = 10;
}
$versementsPage = max(1, (int) ($_GET['page_versements'] ?? 1));
$totalVersements = count($listeVersement);
$versementPagination = function_exists('cm_paginate')
    ? cm_paginate($totalVersements, $versementsParPage, $versementsPage)
    : [
        'total' => $totalVersements,
        'per_page' => $versementsParPage,
        'current' => $versementsPage,
        'last' => max(1, (int) ceil($totalVersements / $versementsParPage)),
        'offset' => max(0, ($versementsPage - 1) * $versementsParPage),
        'has_prev' => $versementsPage > 1,
        'has_next' => $versementsPage < max(1, (int) ceil($totalVersements / $versementsParPage)),
        'pages' => [$versementsPage],
    ];
$versementsToShow = array_slice($listeVersement, (int) ($versementPagination['offset'] ?? 0), $versementsParPage);
$paginationBaseUrl = '?page=gestion_scolarite&limit_versements=' . $versementsParPage;
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php
    cm_component('layout/page-header', [
        'title' => '',
        'subtitle' => 'Gestion unifiee des inscriptions et versements.',
        'annee' => $anneeSelectionneeLabel,
        'icon' => 'fa-credit-card',
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
            'message' => "Affichage multi-années actif. Les nouvelles inscriptions et les nouveaux versements seront enregistrés sur l'année académique active {$anneeEcritureLabel}.",
        ]); ?>
    <?php elseif (!$ecritureAutorisee): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'warning',
            'message' => "Consultation historique active. Les inscriptions et versements sont réservés à l'année académique active {$anneeActiveLabel}.",
        ]); ?>
    <?php endif; ?>
    <div class="cm-crud-wrapper">
    <div class="">
        <div class="">
        </div>
        <form id="cmPaiementForm" method="POST" action="?page=gestion_scolarite&action=enregistrer_paiement">
            <?php cm_component('form/csrf-token'); ?>
            <input type="hidden" id="cmIsNewInscription" name="is_new_inscription" value="">
            <!-- Ligne 1: Niveau, Année A., Frais Scolarité -->
            <div class="cm-grid-3">
                <?php
                cm_component('form/select', [
                    'name' => 'niveau',
                    'id' => 'cmNiveau',
                    'label' => 'Niveau',
                    'options' => $niveauxOptions,
                    'selected' => '',
                    'required' => true,
                ]);
                ?>
                <input type="hidden" name="annee_academique" id="cmAnneeAcademique" value="<?= htmlspecialchars((string) $anneeEcritureId, ENT_QUOTES, 'UTF-8') ?>">
                <?php
                cm_component('form/input-text', [
                    'name' => 'frais_scolarite_display',
                    'id' => 'cmFraisScolarite',
                    'label' => 'Frais',
                    'readonly' => true,
                    'value' => '',
                ]);
                ?>
            </div>
            <!-- Ligne 2: Nom Prénom, Identifiant, N° Carte -->
            <div class="cm-grid-3">
                <?php
                cm_component('form/select-search', [
                    'name' => 'etudiant',
                    'id' => 'cmEtudiantSelect',
                    'label' => 'Nom Prénom',
                    'options' => $studentOptions,
                    'required' => true,
                    'placeholder' => '-- Sélectionner --',
                ]);
                cm_component('form/input-text', [
                    'name' => 'identifiant_display',
                    'id' => 'cmIdentifiantDisplay',
                    'label' => 'Identifiant',
                    'readonly' => true,
                ]);
                cm_component('form/input-text', [
                    'name' => 'num_carte_display',
                    'id' => 'cmNumCarteDisplay',
                    'label' => 'N° Carte',
                    'readonly' => true,
                ]);
                ?>
            </div>
            <!-- Ligne 3: Versement + Paiement (compact) -->
            <div class="cm-grid-5">
                <?php
                cm_component('form/input-number', [
                    'name' => 'num_versement_display',
                    'id' => 'cmNumVersement',
                    'label' => 'N° Vers.',
                    'readonly' => true,
                ]);
                cm_component('form/input-date', [
                    'name' => 'date_versement_display',
                    'id' => 'cmDateVersement',
                    'label' => 'Date',
                    'value' => date('Y-m-d'),
                    'required' => true,
                ]);
                cm_component('form/input-number', [
                    'name' => 'montant_versement',
                    'id' => 'cmMontantVersement',
                    'label' => 'Montant',
                    'required' => true,
                    'min' => 1,
                ]);
                cm_component('form/input-text', [
                    'name' => 'reste_a_payer_display',
                    'id' => 'cmResteAPayer',
                    'label' => 'Reste',
                    'readonly' => true,
                    'value' => '',
                ]);
                cm_component('form/input-text', [
                    'name' => 'solde_display',
                    'id' => 'cmSolde',
                    'label' => 'Solde',
                    'readonly' => true,
                    'value' => '',
                ]);
                cm_component('form/select', [
                    'name' => 'methode_paiement',
                    'id' => 'cmModePaiement',
                    'label' => 'Mode',
                    'required' => true,
                    'options' => [
                        'Espece' => 'Espece',
                        'Cheque' => 'Cheque',
                        'Virement' => 'Virement',
                        'Mobile Money' => 'Mobile Money',
                        'Wave' => 'Wave',
                    ],
                ]);
                cm_component('form/input-text', [
                    'name' => 'num_piece',
                    'id' => 'cmNumPiece',
                    'label' => 'N° M.P',
                    'maxlength' => 30,
                ]);
                ?>
            </div>
            <!-- Hidden field -->
            <input type="hidden" id="cmInfoEtudiant" name="cmInfoEtudiant" value="">
            <div class="cm-form-buttons">
                <?php if (canCreate() || canEdit()): ?>
                    <button class="cm-btn is-success" type="submit">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        Valider
                    </button>
                <?php endif; ?>
                <button class="cm-btn is-light" type="reset" id="cmResetPaiement">
                    <i class="fas fa-rotate-left" aria-hidden="true"></i>
                    Réinitialiser
                </button>
            </div>
        </form>
    </div>
    <div class="cm-barre-intermediaire">
        <div class="cm-toolbar">
            <div class="cm-toolbar-left">
                <label for="cmVersementsLimit"><strong>Afficher:</strong></label>
                <select id="cmVersementsLimit" class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs">
                    <?php foreach ($allowedLimits as $limit): ?>
                        <option value="<?php echo $limit; ?>" <?php echo $limit === $versementsParPage ? 'selected' : ''; ?>>
                            <?php echo $limit; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="cmFiltreNiveau"><strong>Niveau:</strong></label>
                <select id="cmFiltreNiveau" class="cm-form-control cm-form-select is-sm cm-toolbar-field-md">
                    <option value="">Tous</option>
                    <?php foreach ($niveauxOptions as $libelle): ?>
                        <option value="<?php echo htmlspecialchars(strtolower((string) $libelle), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $libelle, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="cmFiltreStatut"><strong>Statut:</strong></label>
                <select id="cmFiltreStatut" class="cm-form-control cm-form-select is-sm cm-toolbar-field-sm">
                    <option value="">Tous</option>
                    <option value="solde">Solde</option>
                    <option value="partiel">Partiel</option>
                </select>
            </div>
            <div class="cm-toolbar-center">
                <input type="text" id="cmSearchVersement" class="cm-form-control" placeholder="Rechercher (étudiant, numero, mode)...">
            </div>
            <div class="cm-toolbar-right">
                <button type="button" class="cm-btn is-info is-sm" id="cmSelectAllVersements">
                    <i class="fas fa-check-square" aria-hidden="true"></i>
                    Tout sélectionner
                </button>
                <button type="button" class="cm-btn is-light is-sm" id="cmDeselectAllVersements">
                    <i class="fas fa-square" aria-hidden="true"></i>
                    Deselectionner
                </button>
                <button type="button" class="cm-btn is-info is-sm" id="cmDeleteVersements" disabled>
                    <i class="fas fa-trash" aria-hidden="true"></i>
                    Supprimer (<span id="cmSelectedVersementsCount">0</span>)
                </button>
                <?php if (canView()): ?>
                <button type="button" class="cm-btn is-info is-sm" id="cmPrintVersements">
                    <i class="fas fa-print" aria-hidden="true"></i>
                    Imprimer
                </button>
                <button type="button" class="cm-btn is-info is-sm" id="cmExportVersements">
                    <i class="fas fa-file-export" aria-hidden="true"></i>
                    Exporter
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="cm-pole-inferieur">
        <div class="cm-table-wrapper">
            <table class="cm-data-table" id="cmVersementsTable">
                <thead>
                <tr>
                    <th class="cm-data-table__th is-checkbox">
                        <input type="checkbox" id="cmCheckAllVersements" class="cm-checkbox" aria-label="Sélectionner toutes les lignes">
                    </th>
                    <th class="cm-data-table__th">N° Etud.</th>
                    <th class="cm-data-table__th">Nom & Prenom</th>
                    <th class="cm-data-table__th">N° Vers.</th>
                    <th class="cm-data-table__th">Date Vers.</th>
                    <th class="cm-data-table__th">Année Acad.</th>
                    <th class="cm-data-table__th">Montant verse</th>
                    <th class="cm-data-table__th">Reste</th>
                    <th class="cm-data-table__th">Solde</th>
                    <th class="cm-data-table__th">Mode paie.</th>
                    <th class="cm-data-table__th">N° M.P</th>
                    <th class="cm-data-table__th">Actions</th>
                </tr>
                </thead>
                <tbody id="cmVersementsTableBody">
                <?php if (empty($versementsToShow)): ?>
                    <?php cm_component('ui/empty-state', [
                        'in_table' => true,
                        'colspan' => 12,
                        'title' => '',
                        'message' => 'Aucune inscription / aucun versement trouve.',
                    ]); ?>
                <?php else: ?>
                    <?php foreach ($versementsToShow as $versement): ?>
                        <?php
                        $numEtu = (string) ($versement['id_etudiant'] ?? '');
                        $nomPrenom = trim((string) ($versement['nom_etu'] ?? '') . ' ' . (string) ($versement['prenom_etu'] ?? ''));
                        $numVersement = (int) ($versement['num_versement'] ?? 0);
                        $dateVersement = !empty($versement['date_versement']) ? date('d/m/Y', strtotime((string) $versement['date_versement'])) : '';
                        $anneeVersement = $anneesOptions[(int) ($versement['id_annee_acad'] ?? 0)] ?? '';
                        $montant = (float) ($versement['montant_verser'] ?? 0);
                        $resteVersement = (float) ($versement['reste_a_payer'] ?? 0);
                        $soldeVersement = (float) ($versement['solde'] ?? $resteVersement);
                        $mode = (string) ($versement['methode_paiement'] ?? '');
                        $numPiece = (string) ($versement['num_piece_mp'] ?? '');
                        $niveauLib = strtolower((string) ($versement['lib_niv_etude'] ?? ''));
                        $statutPaiement = $soldeVersement <= 0 ? 'solde' : 'partiel';
                        ?>
                        <tr class="cm-data-table__row"
                            data-search="<?php echo htmlspecialchars(strtolower($numEtu . ' ' . $nomPrenom . ' ' . $mode . ' ' . $numPiece), ENT_QUOTES, 'UTF-8'); ?>"
                            data-niveau="<?php echo htmlspecialchars($niveauLib, ENT_QUOTES, 'UTF-8'); ?>"
                            data-statut="<?php echo htmlspecialchars($statutPaiement, ENT_QUOTES, 'UTF-8'); ?>">
                            <td class="cm-data-table__td is-checkbox">
                                <input type="checkbox" class="cm-checkbox cm-row-checkbox">
                            </td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($nomPrenom, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars((string) $numVersement, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($dateVersement, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($anneeVersement !== '' ? $anneeVersement : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars(number_format($montant, 0, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars(number_format($resteVersement, 0, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars(number_format($soldeVersement, 0, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td"><?php echo htmlspecialchars($numPiece, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="cm-data-table__td">
                                <div class="cm-row-actions">
                                    <button type="button"
                                            class="cm-btn-action is-edit cmPrefillPaiement"
                                            data-student="<?php echo htmlspecialchars($numEtu, ENT_QUOTES, 'UTF-8'); ?>"
                                            title="Pre-remplir le formulaire">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                    </button>
                                    <a class="cm-btn-action is-edit" href="?page=gestion_scolarite&action=imprimer_recu&id=<?php echo urlencode((string) ($versement['id_inscription'] ?? '')); ?>" target="_blank" title="Imprimer recu">
                                        <i class="fas fa-receipt" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php cm_component('crud/pagination', [
            'pagination' => $versementPagination,
            'base_url' => $paginationBaseUrl,
            'param_name' => 'page_versements',
        ]); ?>
    </div>
</div>
</div>
<script>
(function () {
    const catalog = <?php echo json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const niveauxMontants = <?php echo json_encode($niveauxMontants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const navigate = function (url) {
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(url);
            return;
        }
        window.location.href = url;
    };
    const etudiantHidden = document.getElementById('cmEtudiantSelect_hidden');
    const etudiantSearchInput = document.getElementById('cmEtudiantSelect_search');
    const etudiantSelectedLabel = document.getElementById('cmEtudiantSelect_selected_label');
    const niveauField = document.getElementById('cmNiveau');
    const fraisField = document.getElementById('cmFraisScolarite');
    const identifiantField = document.getElementById('cmIdentifiantDisplay');
    const numCarteField = document.getElementById('cmNumCarteDisplay');
    const numVersementField = document.getElementById('cmNumVersement');
    const montantVerseField = document.getElementById('cmMontantVersement');
    const resteField = document.getElementById('cmResteAPayer');
    const soldeField = document.getElementById('cmSolde');
    const modePaiementField = document.getElementById('cmModePaiement');
    const anneeField = document.getElementById('cmAnneeAcademique');
    const infoField = document.getElementById('cmInfoEtudiant');
    const isNewField = document.getElementById('cmIsNewInscription');
    const form = document.getElementById('cmPaiementForm');
    const formatNumber = function (value) {
        const parsed = Number(value || 0);
        return parsed.toLocaleString('fr-FR');
    };
    const parseNumber = function (value) {
        return Number(String(value || '0').replace(/\s/g, '').replace(',', '.')) || 0;
    };
    const syncByStudent = function () {
        if (!etudiantHidden) {
            return;
        }
        const studentId = etudiantHidden.value;
        const data = catalog[studentId];
        if (!data) {
            identifiantField.value = '';
            numCarteField.value = '';
            numVersementField.value = '';
            fraisField.value = '';
            resteField.value = '';
            if (soldeField) {
                soldeField.value = '';
            }
            infoField.value = '';
            isNewField.value = '';
            return;
        }
        const isInscrit = !!data.inscrit;
        isNewField.value = isInscrit ? 'false' : 'true';
        identifiantField.value = data.identifiant || '';
        numCarteField.value = data.num || '';
        numVersementField.value = String(data.num_versement || 1);
        if (isInscrit) {
            if (data.id_niveau && niveauField) {
                niveauField.value = String(data.id_niveau);
            }
            if (data.id_annee && anneeField) {
                anneeField.value = String(data.id_annee);
            }
            const montantTotal = Number(data.montant_total || 0);
            const montantPaye = Number(data.montant_paye || 0);
            const reste = Number(data.reste_a_payer || 0);
            const solde = Number(data.solde !== undefined ? data.solde : reste);
            fraisField.value = formatNumber(montantTotal);
            resteField.value = formatNumber(reste);
            if (soldeField) {
                soldeField.value = formatNumber(solde);
            }
            infoField.value = 'Deja inscrit - montant paye: ' + formatNumber(montantPaye) + ' FCFA';
        } else {
            infoField.value = 'Nouvelle inscription';
            if (niveauField && niveauField.value && niveauxMontants[niveauField.value] !== undefined) {
                fraisField.value = formatNumber(niveauxMontants[niveauField.value]);
            } else {
                fraisField.value = '';
            }
            updateReste();
        }
    };
    const setSelectSearchValue = function (studentId) {
        const options = document.querySelectorAll('#cmEtudiantSelect_wrapper .cm-select-search__option');
        let selectedLabel = '';
        options.forEach(function (option) {
            const isSelected = option.getAttribute('data-value') === studentId;
            option.classList.toggle('is-selected', isSelected);
            option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            if (isSelected) {
                selectedLabel = option.getAttribute('data-label') || '';
            }
        });
        if (etudiantSearchInput) {
            etudiantSearchInput.value = selectedLabel;
        }
        if (etudiantSelectedLabel) {
            etudiantSelectedLabel.textContent = selectedLabel || '-- Sélectionner un étudiant --';
        }
    };
    const updateFraisFromNiveau = function () {
        if (!niveauField) {
            return;
        }
        const niveauId = niveauField.value;
        const montant = niveauxMontants[niveauId] !== undefined ? Number(niveauxMontants[niveauId]) : 0;
        fraisField.value = montant > 0 ? formatNumber(montant) : '';
        updateReste();
    };
    const updateReste = function () {
        const studentId = etudiantHidden ? etudiantHidden.value : '';
        const data = catalog[studentId];
        const montantScolarite = parseNumber(fraisField.value);
        const montantVerse = parseNumber(montantVerseField.value);
        if (data && data.inscrit) {
            const reste = Number(data.reste_a_payer || 0);
            const solde = Math.max(0, reste - montantVerse);
            resteField.value = formatNumber(solde);
            if (soldeField) {
                soldeField.value = formatNumber(solde);
            }
        } else {
            const reste = Math.max(0, montantScolarite - montantVerse);
            resteField.value = montantScolarite > 0 ? formatNumber(reste) : '';
            if (soldeField) {
                soldeField.value = montantScolarite > 0 ? formatNumber(reste) : '';
            }
        }
    };
    if (etudiantHidden) {
        etudiantHidden.addEventListener('change', syncByStudent);
        document.querySelectorAll('#cmEtudiantSelect_wrapper .cm-select-search__option').forEach(function (option) {
            option.addEventListener('click', function () {
                setTimeout(syncByStudent, 0);
            });
        });
    }
    document.querySelectorAll('.cmPrefillPaiement').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!etudiantHidden) {
                return;
            }
            const studentId = button.getAttribute('data-student') || '';
            if (!studentId) {
                return;
            }
            etudiantHidden.value = studentId;
            setSelectSearchValue(studentId);
            syncByStudent();
        });
    });
    if (niveauField) {
        niveauField.addEventListener('change', updateFraisFromNiveau);
    }
    if (montantVerseField) {
        montantVerseField.addEventListener('input', updateReste);
    }
    const resetPaiementBtn = document.getElementById('cmResetPaiement');
    if (resetPaiementBtn) {
        resetPaiementBtn.addEventListener('click', function () {
            setTimeout(function () {
                if (isNewField) {
                    isNewField.value = '';
                }
                if (infoField) {
                    infoField.value = '';
                }
                if (fraisField) {
                    fraisField.value = '';
                }
                if (resteField) {
                    resteField.value = '';
                }
                if (soldeField) {
                    soldeField.value = '';
                }
                if (numVersementField) {
                    numVersementField.value = '';
                }
            }, 0);
        });
    }
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!etudiantHidden || !etudiantHidden.value) {
                event.preventDefault();
                window.alert('Veuillez sélectionner un étudiant.');
                return;
            }
            const currentData = catalog[etudiantHidden.value];
            const montantVerse = parseNumber(montantVerseField.value);
            if (montantVerse <= 0) {
                event.preventDefault();
                window.alert('Le montant verse doit etre strictement positif.');
                return;
            }
            if (currentData && currentData.inscrit) {
                const reste = Number(currentData.reste_a_payer || 0);
                if (montantVerse > reste) {
                    event.preventDefault();
                    window.alert('Le montant verse ne peut pas depasser le reste a payer (' + formatNumber(reste) + ' FCFA).');
                    return;
                }
            } else {
                const montantScolarite = parseNumber(fraisField.value);
                if (montantScolarite > 0 && montantVerse > montantScolarite) {
                    event.preventDefault();
                    window.alert('Le montant verse ne peut pas depasser les frais de scolarite.');
                    return;
                }
                if (!niveauField.value || !anneeField.value) {
                    event.preventDefault();
                    window.alert('Niveau et année académique sont obligatoires pour une nouvelle inscription.');
                    return;
                }
            }
            if (!modePaiementField.value) {
                event.preventDefault();
                window.alert('Veuillez sélectionner un mode de paiement.');
            }
        });
    }
    const searchInput = document.getElementById('cmSearchVersement');
    const limitSelect = document.getElementById('cmVersementsLimit');
    const filterNiveau = document.getElementById('cmFiltreNiveau');
    const filterStatut = document.getElementById('cmFiltreStatut');
    const selectAllRowsBtn = document.getElementById('cmSelectAllVersements');
    const deselectAllRowsBtn = document.getElementById('cmDeselectAllVersements');
    const deleteRowsBtn = document.getElementById('cmDeleteVersements');
    const selectedRowsCount = document.getElementById('cmSelectedVersementsCount');
    const rows = function () { return Array.from(document.querySelectorAll('#cmVersementsTableBody tr')); };
    const rowCheckboxes = function () {
        return Array.from(document.querySelectorAll('#cmVersementsTableBody .cm-row-checkbox'));
    };
    const updateSelectionState = function () {
        const checkboxes = rowCheckboxes();
        const checked = checkboxes.filter(function (cb) { return cb.checked; }).length;
        if (selectedRowsCount) {
            selectedRowsCount.textContent = String(checked);
        }
        if (deleteRowsBtn) {
            deleteRowsBtn.disabled = checked === 0;
        }
        if (checkAllRows) {
            checkAllRows.checked = checkboxes.length > 0 && checkboxes.every(function (cb) { return cb.checked; });
        }
    };
    const applyFilters = function () {
        const term = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const niveau = (filterNiveau ? filterNiveau.value : '').trim().toLowerCase();
        const statut = (filterStatut ? filterStatut.value : '').trim().toLowerCase();
        rows().forEach(function (row) {
            const matchSearch = term === '' || (row.getAttribute('data-search') || '').indexOf(term) !== -1;
            const matchNiveau = niveau === '' || (row.getAttribute('data-niveau') || '') === niveau;
            const matchStatut = statut === '' || (row.getAttribute('data-statut') || '') === statut;
            row.style.display = matchSearch && matchNiveau && matchStatut ? '' : 'none';
        });
    };
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    if (filterNiveau) {
        filterNiveau.addEventListener('change', applyFilters);
    }
    if (filterStatut) {
        filterStatut.addEventListener('change', applyFilters);
    }
    if (limitSelect) {
        limitSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('limit_versements', String(limitSelect.value));
            url.searchParams.set('page_versements', '1');
            navigate(url.toString());
        });
    }
    const checkAllRows = document.getElementById('cmCheckAllVersements');
    if (checkAllRows) {
        checkAllRows.addEventListener('change', function () {
            rowCheckboxes().forEach(function (cb) {
                cb.checked = checkAllRows.checked;
            });
            updateSelectionState();
        });
    }
    if (selectAllRowsBtn) {
        selectAllRowsBtn.addEventListener('click', function () {
            rowCheckboxes().forEach(function (cb) { cb.checked = true; });
            updateSelectionState();
        });
    }
    if (deselectAllRowsBtn) {
        deselectAllRowsBtn.addEventListener('click', function () {
            rowCheckboxes().forEach(function (cb) { cb.checked = false; });
            updateSelectionState();
        });
    }
    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('cm-row-checkbox')) {
            updateSelectionState();
        }
    });
    if (deleteRowsBtn) {
        deleteRowsBtn.addEventListener('click', function () {
            if (deleteRowsBtn.disabled) {
                return;
            }
            window.alert('Suppression multiple indisponible sur cet ecran.');
        });
    }
    const printVersementsBtn = document.getElementById('cmPrintVersements');
    if (printVersementsBtn) {
        printVersementsBtn.addEventListener('click', function () {
            window.print();
        });
    }
    const exportVersementsBtn = document.getElementById('cmExportVersements');
    if (exportVersementsBtn) {
        exportVersementsBtn.addEventListener('click', function () {
            const headers = ['N° Etud.', 'Nom & Prenom', 'N° Vers.', 'Date Vers.', 'Année Acad.', 'Montant', 'Reste', 'Solde', 'Mode', 'N° M.P'];
            const lines = [headers.join(';')];
            rows().forEach(function (row) {
                if (row.style.display === 'none') {
                    return;
                }
                const cells = Array.from(row.querySelectorAll('td')).slice(1, 11);
                const values = cells.map(function (cell) {
                    return '"' + (cell.textContent || '').trim().replace(/"/g, '""') + '"';
                });
                lines.push(values.join(';'));
            });
            const blob = new Blob(["\uFEFF" + lines.join('\n')], {type: 'text/csv;charset=utf-8;'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'versements_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }
    syncByStudent();
    updateSelectionState();
})();
</script>
