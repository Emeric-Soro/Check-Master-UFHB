<?php
$utilisateurEdit = $GLOBALS['utilisateur_a_modifier'] ?? null;
$utilisateursSource = is_array($GLOBALS['utilisateurs'] ?? null) ? $GLOBALS['utilisateurs'] : [];
$typesUtilisateur = is_array($GLOBALS['types_utilisateur'] ?? null) ? $GLOBALS['types_utilisateur'] : [];
$groupesUtilisateur = is_array($GLOBALS['groupes_utilisateur'] ?? null) ? $GLOBALS['groupes_utilisateur'] : [];
$niveauxAcces = is_array($GLOBALS['niveau_acces'] ?? null) ? $GLOBALS['niveau_acces'] : [];
$enseignantsNonUtilisateurs = is_array($GLOBALS['enseignantsNonUtilisateurs'] ?? null) ? $GLOBALS['enseignantsNonUtilisateurs'] : [];
$personnelNonUtilisateurs = is_array($GLOBALS['personnelNonUtilisateurs'] ?? null) ? $GLOBALS['personnelNonUtilisateurs'] : [];
$etudiantsNonUtilisateurs = is_array($GLOBALS['etudiantsNonUtilisateurs'] ?? null) ? $GLOBALS['etudiantsNonUtilisateurs'] : [];
$messageSuccess = (string) ($GLOBALS['messageSuccess'] ?? '');
$messageErreur = (string) ($GLOBALS['messageErreur'] ?? '');
$messageSuccessType = (string) ($GLOBALS['messageSuccessType'] ?? 'success');
$isMassMode = (string) ($_GET['action'] ?? '') === 'addMasse';

if (!in_array($messageSuccessType, ['success', 'info', 'warning', 'danger'], true)) {
    $messageSuccessType = 'success';
}

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'type' => (string) ($_GET['type'] ?? ''),
    'groupe' => (string) ($_GET['groupe'] ?? ''),
    'statut' => (string) ($_GET['statut'] ?? ''),
];
$limit = max(5, min(100, (int) ($_GET['limit'] ?? 10)));
$page = max(1, (int) ($_GET['p'] ?? 1));

$utilisateursFiltres = array_values(array_filter($utilisateursSource, static function ($user) use ($filters): bool {
    if ($filters['search'] !== '') {
        $haystack = strtolower(implode(' ', [
            (string) ($user->nom_utilisateur ?? ''),
            (string) ($user->login_utilisateur ?? ''),
            (string) ($user->role_utilisateur ?? ''),
            (string) ($user->lib_GU ?? ''),
        ]));
        if (strpos($haystack, strtolower($filters['search'])) === false) {
            return false;
        }
    }
    if ($filters['type'] !== '' && (string) ($user->id_type_utilisateur ?? '') !== $filters['type']) {
        return false;
    }
    if ($filters['groupe'] !== '' && (string) ($user->id_GU ?? '') !== $filters['groupe']) {
        return false;
    }
    if ($filters['statut'] !== '' && strcasecmp((string) ($user->statut_utilisateur ?? ''), $filters['statut']) !== 0) {
        return false;
    }
    return true;
}));

usort($utilisateursFiltres, static function ($a, $b): int {
    return ((int) ($b->id_utilisateur ?? 0)) <=> ((int) ($a->id_utilisateur ?? 0));
});

$total = count($utilisateursFiltres);
$pagination = cm_paginate($total, $limit, $page);
$rowsPage = array_slice($utilisateursFiltres, (int) $pagination['offset'], $limit);

$typeOptions = ['' => '-- Sélectionner --'];
foreach ($typesUtilisateur as $type) {
    $id = (string) ($type->id_type_utilisateur ?? '');
    if ($id !== '') {
        $typeOptions[$id] = (string) ($type->lib_type_utilisateur ?? ('Type ' . $id));
    }
}
$groupOptions = ['' => '-- Sélectionner --'];
foreach ($groupesUtilisateur as $groupe) {
    $id = (string) ($groupe->id_GU ?? '');
    if ($id !== '') {
        $groupOptions[$id] = (string) ($groupe->lib_GU ?? ('Groupe ' . $id));
    }
}
$niveauOptions = [];
foreach ($niveauxAcces as $niveau) {
    $id = (string) ($niveau->id_niveau_acces_donnees ?? '');
    if ($id !== '') {
        $niveauOptions[$id] = (string) ($niveau->lib_niveau_acces_donnees ?? ('Niveau ' . $id));
    }
}

$userRows = [];
foreach ($rowsPage as $user) {
    $statut = (string) ($user->statut_utilisateur ?? '');
    $userRows[] = [
        'id_utilisateur' => (string) ($user->id_utilisateur ?? ''),
        'nom_utilisateur' => (string) ($user->nom_utilisateur ?? ''),
        'role_utilisateur' => (string) ($user->role_utilisateur ?? '-'),
        'lib_GU' => (string) ($user->lib_GU ?? '-'),
        'niveau_acces' => (string) ($user->niveau_acces ?? '-'),
        'statut_utilisateur' => ['label' => $statut ?: '-', 'type' => strcasecmp($statut, 'Actif') === 0 ? 'success' : 'warning'],
        'login_utilisateur' => (string) ($user->login_utilisateur ?? ''),
    ];
}

$pagerBase = '?' . http_build_query(array_filter([
    'page' => 'gestion_utilisateurs',
    'search' => $filters['search'],
    'type' => $filters['type'],
    'groupe' => $filters['groupe'],
    'statut' => $filters['statut'],
    'limit' => $limit,
], static fn($v) => $v !== '' && $v !== null));

$editTypeValue = (string) ($utilisateurEdit->id_type_utilisateur ?? '');
$editGroupeValue = (string) ($utilisateurEdit->id_GU ?? '');
$editNiveauValue = (string) ($utilisateurEdit->id_niv_acces_donnee ?? '');
$editStatutValue = (string) ($utilisateurEdit->statut_utilisateur ?? 'Actif');
$editNomValue = (string) ($utilisateurEdit->nom_utilisateur ?? '');
$editLoginValue = (string) ($utilisateurEdit->login_utilisateur ?? '');
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => $messageSuccessType, 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <?php if ($isMassMode): ?>
            <?php ob_start(); ?>
            <form method="POST" action="?page=gestion_utilisateurs" id="cmUsersMassForm" data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <div class="cm-grid-4">
                    <?php cm_component('form/select', ['name' => 'id_type_utilisateur', 'label' => 'Type utilisateur', 'required' => true, 'options' => $typeOptions]); ?>
                    <?php cm_component('form/select', ['name' => 'id_GU', 'label' => 'Groupe utilisateur', 'required' => true, 'options' => $groupOptions]); ?>
                    <?php cm_component('form/select', ['name' => 'id_niveau_acces', 'label' => 'Niveau acces', 'required' => true, 'options' => $niveauOptions]); ?>
                    <?php cm_component('form/select', ['name' => 'statut_utilisateur', 'label' => 'Statut', 'required' => true, 'options' => ['Actif' => 'Actif', 'Inactif' => 'Inactif'], 'selected' => 'Actif']); ?>
                </div>
                <div class="cm-form-group">
                    <label class="cm-form-label">Selection des personnes</label>
                    <div class="cm-table-wrapper is-mass-select">
                        <table class="cm-data-table">
                            <thead><tr><th class="cm-data-table__th">Sel</th><th class="cm-data-table__th">Type</th><th class="cm-data-table__th">Nom</th><th class="cm-data-table__th">Email</th></tr></thead>
                            <tbody>
                                <?php $hasMassData = false; foreach ($enseignantsNonUtilisateurs as $p): $hasMassData = true; ?>
                                <tr class="cm-data-table__row"><td class="cm-data-table__td"><input type="checkbox" name="selected_persons[]" value="ens_<?= htmlspecialchars((string) ($p->id_enseignant ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td><td class="cm-data-table__td">Enseignant</td><td class="cm-data-table__td"><?= htmlspecialchars(trim((string) (($p->nom_enseignant ?? '') . ' ' . ($p->prenom_enseignant ?? ''))), ENT_QUOTES, 'UTF-8') ?></td><td class="cm-data-table__td"><?= htmlspecialchars((string) ($p->mail_enseignant ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
                                <?php endforeach; foreach ($personnelNonUtilisateurs as $p): $hasMassData = true; ?>
                                <tr class="cm-data-table__row"><td class="cm-data-table__td"><input type="checkbox" name="selected_persons[]" value="pers_<?= htmlspecialchars((string) ($p->id_pers_admin ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td><td class="cm-data-table__td">Personnel</td><td class="cm-data-table__td"><?= htmlspecialchars(trim((string) (($p->nom_pers_admin ?? '') . ' ' . ($p->prenom_pers_admin ?? ''))), ENT_QUOTES, 'UTF-8') ?></td><td class="cm-data-table__td"><?= htmlspecialchars((string) ($p->email_pers_admin ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
                                <?php endforeach; foreach ($etudiantsNonUtilisateurs as $p): $hasMassData = true; ?>
                                <tr class="cm-data-table__row"><td class="cm-data-table__td"><input type="checkbox" name="selected_persons[]" value="etu_<?= htmlspecialchars((string) ($p->num_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td><td class="cm-data-table__td">Etudiant</td><td class="cm-data-table__td"><?= htmlspecialchars(trim((string) (($p->nom_etu ?? '') . ' ' . ($p->prenom_etu ?? ''))), ENT_QUOTES, 'UTF-8') ?></td><td class="cm-data-table__td"><?= htmlspecialchars((string) ($p->email_etu ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
                                <?php endforeach; if (!$hasMassData): ?>
                                <tr><td colspan="4" class="cm-data-table__td is-center">Aucune personne disponible.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php cm_component('crud/form-actions', ['actions' => array_filter([
                    ['tag' => 'a', 'href' => '?page=gestion_utilisateurs', 'label' => 'Retour', 'icon' => 'fa-arrow-left', 'class' => 'cm-btn is-light'],
                    canCreate() ? ['tag' => 'button', 'type' => 'submit', 'label' => 'Ajouter la selection', 'icon' => 'fa-users', 'class' => 'cm-btn is-success', 'attrs' => ['name' => 'btn_add_multiple']] : null,
                ])]); ?>
                <div id="cmUsersMassFormState" class="cm-text-muted" aria-live="polite"></div>
            </form>
            <?php cm_component('crud/form-pole', ['title' => '', 'icon' => 'fa-users', 'content' => (string) ob_get_clean()]); ?>
        <?php else: ?>
            <?php ob_start(); ?>
            <form method="POST" action="?page=gestion_utilisateurs" id="cmUserForm" data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <?php if ($utilisateurEdit): ?><input type="hidden" name="id_utilisateur" value="<?= htmlspecialchars((string) ($utilisateurEdit->id_utilisateur ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
                <input type="hidden" name="source_reference_id" id="cmSourceReferenceId" value="">
                <input type="hidden" name="source_reference_email" id="cmSourceReferenceEmail" value="">
                <div class="cm-grid-4">
                    <?php cm_component('form/select', ['name' => 'id_type_utilisateur', 'id' => 'cmTypeUtilisateur', 'label' => 'Type utilisateur', 'required' => true, 'options' => $typeOptions, 'selected' => $editTypeValue]); ?>
                    <div class="cm-form-group">
                        <label for="cmGroupeUtilisateur" class="cm-form-label">Groupe utilisateur</label>
                        <select name="id_GU" id="cmGroupeUtilisateur" class="cm-form-control" required>
                            <?php foreach ($groupesUtilisateur as $groupe): $gid = (string) ($groupe->id_GU ?? ''); if ($gid === '') { continue; } ?>
                            <option value="<?= htmlspecialchars($gid, ENT_QUOTES, 'UTF-8') ?>" data-type-id="<?= htmlspecialchars((string) ($groupe->id_type_utilisateur ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $editGroupeValue === $gid ? 'selected' : '' ?>><?= htmlspecialchars((string) ($groupe->lib_GU ?? ('Groupe ' . $gid)), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="cm-form-group" id="cmUserNameTextWrap"><?php cm_component('form/input-text', ['name' => 'nom_utilisateur', 'id' => 'cmNomUtilisateurText', 'label' => 'Nom utilisateur', 'required' => true, 'value' => $editNomValue, 'placeholder' => 'Nom complet']); ?></div>
                    <div class="cm-form-group cm-hidden" id="cmUserNameSelectWrap"><label for="cmNomUtilisateurSelect" class="cm-form-label">Nom utilisateur</label><select id="cmNomUtilisateurSelect" class="cm-form-control"></select></div>
                    <?php cm_component('form/select', ['name' => 'id_niveau_acces', 'id' => 'cmNiveauAcces', 'label' => 'Niveau acces', 'required' => true, 'options' => $niveauOptions, 'selected' => $editNiveauValue !== '' ? $editNiveauValue : (string) array_key_first($niveauOptions)]); ?>
                    <?php cm_component('form/select', ['name' => 'statut_utilisateur', 'id' => 'cmStatutUtilisateur', 'label' => 'Statut', 'required' => true, 'options' => ['Actif' => 'Actif', 'Inactif' => 'Inactif'], 'selected' => $editStatutValue]); ?>
                    <?php cm_component('form/input-text', ['name' => 'login_utilisateur', 'id' => 'cmLoginUtilisateur', 'label' => 'Login', 'required' => true, 'value' => $editLoginValue, 'placeholder' => 'login']); ?>
                </div>
                <div class="cm-form-group"><small id="cmLoginHint" class="cm-text-muted"></small></div>
                <?php cm_component('crud/form-actions', ['actions' => array_filter([
                    ['tag' => 'a', 'href' => '?page=gestion_utilisateurs', 'label' => 'Réinitialiser', 'icon' => 'fa-rotate-left', 'class' => 'cm-btn is-light'],
                    canCreate() ? ['tag' => 'a', 'href' => '?page=gestion_utilisateurs&action=addMasse', 'label' => 'Ajout en masse', 'icon' => 'fa-users', 'class' => 'cm-btn is-info'] : null,
                    ($utilisateurEdit ? canEdit() : canCreate()) ? ['tag' => 'button', 'type' => 'submit', 'label' => $utilisateurEdit ? 'Modifier' : 'Enregistrer', 'icon' => 'fa-save', 'class' => 'cm-btn is-success', 'attrs' => ['name' => $utilisateurEdit ? 'btn_modifier_utilisateur' : 'btn_add_utilisateur']] : null,
                ])]); ?>
                <div id="cmUserFormState" class="cm-text-muted" aria-live="polite"></div>
            </form>
            <?php cm_component('crud/form-pole', ['title' => '', 'icon' => 'fa-user-plus', 'content' => (string) ob_get_clean()]); ?>
        <?php endif; ?>

        <?php if (!$isMassMode): ?>
            <form id="cmUsersFilters" method="GET" data-cm-ajax-form="true">
                <input type="hidden" name="page" value="gestion_utilisateurs">
                <?php ob_start(); ?>
                <label class="cm-toolbar__control"><span>Afficher:</span><select class="cm-form-control cm-toolbar__select" name="limit"><?php foreach ([10,25,50,100] as $opt): ?><option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?></option><?php endforeach; ?></select></label>
                <label class="cm-toolbar__control"><span>Type:</span><select class="cm-form-control cm-toolbar__select" name="type"><?php foreach ($typeOptions as $id => $label): ?><option value="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['type'] === (string) $id ? 'selected' : '' ?>><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
                <label class="cm-toolbar__control"><span>Groupe:</span><select class="cm-form-control cm-toolbar__select" name="groupe"><?php foreach ($groupOptions as $id => $label): ?><option value="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['groupe'] === (string) $id ? 'selected' : '' ?>><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
                <label class="cm-toolbar__control"><span>Statut:</span><select class="cm-form-control cm-toolbar__select" name="statut"><option value="">Tous</option><option value="Actif" <?= $filters['statut'] === 'Actif' ? 'selected' : '' ?>>Actif</option><option value="Inactif" <?= $filters['statut'] === 'Inactif' ? 'selected' : '' ?>>Inactif</option></select></label>
                <?php $leftHtml = (string) ob_get_clean(); ob_start(); ?>
                <div class="cm-toolbar__search-wrap"><input type="search" class="cm-form-control" name="search" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom, login..."><button type="submit" class="cm-btn is-info is-sm"><i class="fas fa-search"></i><span>Filtrer</span></button></div>
                <?php $centerHtml = (string) ob_get_clean(); ob_start(); ?>
                <div class="cm-toolbar__actions">
                    <button type="button" class="cm-btn is-info is-sm" id="cmUsersSelectAll"><i class="fas fa-check-square"></i><span>Tout sélectionner</span></button>
                    <button type="button" class="cm-btn is-light is-sm" id="cmUsersDeselectAll"><i class="fas fa-square"></i><span>Deselectionner</span></button>
                    <?php if (canEdit()): ?>
                    <button type="button" class="cm-btn is-danger is-sm" id="cmUsersDisable"><i class="fas fa-user-slash"></i><span>Desactiver</span></button>
                    <button type="button" class="cm-btn is-success is-sm" id="cmUsersEnable"><i class="fas fa-user-check"></i><span>Activer</span></button>
                    <button type="button" class="cm-btn is-info is-sm" id="cmUsersSendAccess"><i class="fas fa-paper-plane"></i><span>Envoyer acces</span></button>
                    <?php endif; ?>
                    <?php if (canView()): ?>
                    <button type="button" class="cm-btn is-info is-sm" id="cmUsersPrint"><i class="fas fa-print"></i><span>Imprimer</span></button>
                    <button type="button" class="cm-btn is-info is-sm" id="cmUsersExport"><i class="fas fa-file-export"></i><span>Exporter</span></button>
                    <?php endif; ?>
                </div>
                <?php cm_component('crud/toolbar', ['left_html' => $leftHtml, 'center_html' => $centerHtml, 'right_html' => (string) ob_get_clean()]); ?>
            </form>

            <div class="cm-pole-inferieur">
                <form id="cmUsersBulkForm" method="POST" action="?page=gestion_utilisateurs" class="cm-table-form">
                    <?php cm_component('form/csrf-token'); ?>
                    <input type="hidden" name="submit_disable_multiple" id="cmSubmitDisable" value="0">
                    <input type="hidden" name="submit_enable_multiple" id="cmSubmitEnable" value="0">
                    <input type="hidden" name="submit_send_access" id="cmSubmitSendAccess" value="0">
                    <?php cm_component('crud/data-table', [
                        'id' => 'cmUsersTable',
                        'columns' => [
                            cm_column('id_utilisateur', 'ID'), cm_column('nom_utilisateur', 'Nom'), cm_column('role_utilisateur', 'Type'),
                            cm_column('lib_GU', 'Groupe'), cm_column('niveau_acces', 'Niveau acces'),
                            cm_column('statut_utilisateur', 'Statut', ['type' => 'badge']), cm_column('login_utilisateur', 'Login'),
                        ],
                        'rows' => $userRows,
                        'row_key' => 'id_utilisateur',
                        'selectable' => true,
                        'actions' => array_filter([ canEdit() ? [ 'tag' => 'button', 'type' => 'button', 'label' => 'Modifier', 'icon' => 'fa-pen', 'class' => 'cm-btn-action is-edit js-user-edit' ] : null ]),
                        'empty_title' => 'Aucun utilisateur',
                        'empty_message' => 'Aucun enregistrement trouve.',
                    ]); ?>
                </form>
                <?php cm_component('crud/pagination', ['pagination' => $pagination, 'base_url' => $pagerBase, 'param_name' => 'p']); ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<script>
(function () {
    const typeSelect = document.getElementById('cmTypeUtilisateur');
    const groupSelect = document.getElementById('cmGroupeUtilisateur');
    const nomTextWrap = document.getElementById('cmUserNameTextWrap');
    const nomSelectWrap = document.getElementById('cmUserNameSelectWrap');
    const nomText = document.getElementById('cmNomUtilisateurText');
    const nomSelect = document.getElementById('cmNomUtilisateurSelect');
    const loginInput = document.getElementById('cmLoginUtilisateur');
    const loginHint = document.getElementById('cmLoginHint');
    const sourceIdInput = document.getElementById('cmSourceReferenceId');
    const sourceEmailInput = document.getElementById('cmSourceReferenceEmail');
    const userForm = document.getElementById('cmUserForm');
    const userFormState = document.getElementById('cmUserFormState');
    const massForm = document.getElementById('cmUsersMassForm');
    const massFormState = document.getElementById('cmUsersMassFormState');
    const existingName = <?= json_encode($editNomValue) ?>;
    const existingLogin = <?= json_encode($editLoginValue) ?>;
    const flashSuccess = <?= json_encode($messageSuccess) ?>;
    const flashError = <?= json_encode($messageErreur) ?>;
    const flashSuccessType = <?= json_encode($messageSuccessType) ?>;
    let initialLoginSynced = false;
    const dataSets = {
        enseignant: <?= json_encode(array_map(static fn($r) => [
            'id' => (string) ($r->id_enseignant ?? ''),
            'label' => trim((string) (($r->nom_enseignant ?? '') . ' ' . ($r->prenom_enseignant ?? ''))),
            'email' => trim((string) ($r->mail_enseignant ?? '')),
        ], $enseignantsNonUtilisateurs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        personnel: <?= json_encode(array_map(static fn($r) => [
            'id' => (string) ($r->id_pers_admin ?? ''),
            'label' => trim((string) (($r->nom_pers_admin ?? '') . ' ' . ($r->prenom_pers_admin ?? ''))),
            'email' => trim((string) ($r->email_pers_admin ?? '')),
        ], $personnelNonUtilisateurs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        etudiant: <?= json_encode(array_map(static fn($r) => [
            'id' => (string) ($r->num_etu ?? ''),
            'label' => trim((string) (($r->nom_etu ?? '') . ' ' . ($r->prenom_etu ?? ''))),
            'email' => trim((string) ($r->email_etu ?? '')),
        ], $etudiantsNonUtilisateurs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    };

    function n(v) { return String(v || '').toLowerCase(); }
    function populateNameSelect(list) {
        nomSelect.innerHTML = '';
        const values = [];
        list.forEach(function (item) {
            if (!item || !item.label) {
                return;
            }
            values.push(item);
        });
        values.forEach(function (item, index) {
            const opt = document.createElement('option');
            opt.value = item.label;
            opt.textContent = item.email ? item.label : (item.label + ' (sans email)');
            opt.setAttribute('data-source-id', item.id || '');
            opt.setAttribute('data-source-email', item.email || '');
            if (item.label === existingName || item.label === nomText.value || index === 0) opt.selected = true;
            nomSelect.appendChild(opt);
        });
    }
    function getSelectedSourceEmail() {
        if (!nomSelect || nomSelectWrap.classList.contains('cm-hidden') || !nomSelect.selectedOptions.length) {
            return '';
        }
        return nomSelect.selectedOptions[0].getAttribute('data-source-email') || '';
    }
    function syncSourceMetadata() {
        if (nomSelect && !nomSelectWrap.classList.contains('cm-hidden') && nomSelect.selectedOptions.length) {
            const selectedOption = nomSelect.selectedOptions[0];
            if (sourceIdInput) sourceIdInput.value = selectedOption.getAttribute('data-source-id') || '';
            if (sourceEmailInput) sourceEmailInput.value = selectedOption.getAttribute('data-source-email') || '';
            return;
        }
        if (sourceIdInput) sourceIdInput.value = '';
        if (sourceEmailInput) sourceEmailInput.value = '';
    }
    function bindGroupByType() {
        if (!typeSelect || !groupSelect) return;
        const selectedType = typeSelect.value;
        Array.from(groupSelect.options).forEach(function (option) {
            const optionType = option.getAttribute('data-type-id');
            option.hidden = !!(optionType && selectedType && optionType !== selectedType && option.value !== '');
        });
        if (groupSelect.selectedOptions.length && groupSelect.selectedOptions[0].hidden) groupSelect.value = '';
    }
    function syncNameFieldByType() {
        if (!typeSelect || !nomTextWrap || !nomSelectWrap || !nomText || !nomSelect) return;
        const label = n(typeSelect.selectedOptions.length ? typeSelect.selectedOptions[0].textContent : '');
        let list = null;
        if (label.includes('etudiant')) list = dataSets.etudiant;
        else if (label.includes('enseignant')) list = dataSets.enseignant;
        else if (label.includes('personnel') || label.includes('administratif')) list = dataSets.personnel;

        if (list && list.length) {
            populateNameSelect(list);
            nomText.removeAttribute('name'); nomSelect.setAttribute('name', 'nom_utilisateur');
            nomTextWrap.classList.add('cm-hidden');
            nomSelectWrap.classList.remove('cm-hidden');
            nomText.value = nomSelect.value || nomText.value || '';
            syncSourceMetadata();
            applySuggestedLogin();
        } else {
            if (nomSelect.value) nomText.value = nomSelect.value;
            nomSelect.removeAttribute('name'); nomText.setAttribute('name', 'nom_utilisateur');
            nomTextWrap.classList.remove('cm-hidden');
            nomSelectWrap.classList.add('cm-hidden');
            syncSourceMetadata();
            applySuggestedLogin();
        }
        bindGroupByType();
    }
    function generateLoginFromName(value) {
        const p = String(value || '').trim().split(/\s+/).filter(Boolean);
        if (!p.length) return '';
        return (p[0].charAt(0) + p.slice(1).join('')).toLowerCase().replace(/[^a-z0-9._-]/g, '');
    }
    function applySuggestedLogin() {
        if (!loginInput) return;
        if (!initialLoginSynced && existingLogin) {
            initialLoginSynced = true;
            if (loginHint && existingLogin.trim() !== '') {
                checkLoginAvailability(existingLogin.trim());
            }
            return;
        }
        const sourceEmail = String(getSelectedSourceEmail() || '').trim();
        const currentName = !nomSelectWrap.classList.contains('cm-hidden') ? nomSelect.value : nomText.value;
        const suggestedLogin = sourceEmail || generateLoginFromName(currentName);
        initialLoginSynced = true;
        loginInput.value = suggestedLogin;
        if (loginHint) {
            loginHint.textContent = sourceEmail
                ? "Login prérempli avec l'email du profil sélectionné."
                : "Aucun email source trouvé pour ce profil. Le compte sera créé sans envoi d'accès tant qu'un email n'est pas renseigné.";
        }
        if (suggestedLogin) {
            checkLoginAvailability(suggestedLogin.trim());
        }
    }
    function checkLoginAvailability(login) {
        if (!login || !loginHint) return;
        fetch('?page=gestion_utilisateurs&ajax=checkLogin&login=' + encodeURIComponent(login))
            .then(r => r.json())
            .then(function (data) {
                const prefix = String(getSelectedSourceEmail() || '').trim()
                    ? "Login prérempli avec l'email du profil sélectionné. "
                    : "Aucun email source trouvé pour ce profil. ";
                if (!data || !data.success) { loginHint.textContent = prefix + 'Verification login impossible.'; return; }
                if (data.available) { loginHint.textContent = prefix + 'Login disponible.'; return; }
                loginHint.textContent = data.suggestedLogin ? ('Login deja pris. Suggestion: ' + data.suggestedLogin) : (data.message || 'Login indisponible.');
            })
            .catch(function () { loginHint.textContent = ''; });
    }

    if (typeSelect) { typeSelect.addEventListener('change', syncNameFieldByType); syncNameFieldByType(); }
    if (nomText) nomText.addEventListener('blur', function () { if (loginInput && loginInput.value.trim() === '') { applySuggestedLogin(); } });
    if (nomSelect) nomSelect.addEventListener('change', function () { syncSourceMetadata(); applySuggestedLogin(); });
    if (loginInput) loginInput.addEventListener('blur', function () { checkLoginAvailability(loginInput.value.trim()); });
    if (userForm) {
        userForm.addEventListener('submit', function (event) {
            const submitter = event.submitter || userForm.querySelector('button[type="submit"]');
            if (userFormState) {
                userFormState.textContent = <?= json_encode($utilisateurEdit ? 'Modification de l’utilisateur en cours...' : 'Création de l’utilisateur en cours...') ?>;
            }
            if (submitter) {
                submitter.disabled = true;
                submitter.classList.add('is-disabled');
                submitter.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> ' + <?= json_encode($utilisateurEdit ? 'Modification...' : 'Création...') ?>;
            }
        });
    }
    if (massForm) {
        massForm.addEventListener('submit', function (event) {
            const submitter = event.submitter || massForm.querySelector('button[type="submit"]');
            if (massFormState) {
                massFormState.textContent = 'Création des utilisateurs en masse en cours...';
            }
            if (submitter) {
                submitter.disabled = true;
                submitter.classList.add('is-disabled');
            }
        });
    }
    if (flashSuccess && window.CM && window.CM.toast && typeof window.CM.toast.show === 'function') {
        window.CM.toast.show(flashSuccess, flashSuccessType, 4500);
    }
    if (flashError && window.CM && window.CM.toast && typeof window.CM.toast.show === 'function') {
        window.CM.toast.show(flashError, 'danger', 5000);
    }

    const table = document.getElementById('cmUsersTable');
    const bulkForm = document.getElementById('cmUsersBulkForm');
    const submitDisable = document.getElementById('cmSubmitDisable');
    const submitEnable = document.getElementById('cmSubmitEnable');
    const submitSend = document.getElementById('cmSubmitSendAccess');
    function rowChecks() { return table ? Array.from(table.querySelectorAll('.cm-table-check-row')) : []; }
    function selectedIds() { return rowChecks().filter(cb => cb.checked).map(cb => cb.value); }
    function setSelectedIds(ids) {
        if (!bulkForm) return;
        bulkForm.querySelectorAll('input[data-generated-selected=\"1\"]').forEach(function (el) { el.remove(); });
        ids.forEach(function (id) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'selected_ids[]';
            hidden.value = String(id);
            hidden.setAttribute('data-generated-selected', '1');
            bulkForm.appendChild(hidden);
        });
    }
    function submitBulk(mode) {
        if (!bulkForm) return;
        const ids = selectedIds();
        if (!ids.length) { window.alert('Sélectionnez au moins un utilisateur.'); return; }
        setSelectedIds(ids);
        if (submitDisable) submitDisable.value = '0'; if (submitEnable) submitEnable.value = '0'; if (submitSend) submitSend.value = '0';
        if (mode === 'disable' && submitDisable) submitDisable.value = '2';
        if (mode === 'enable' && submitEnable) submitEnable.value = '3';
        if (mode === 'send' && submitSend) submitSend.value = '4';
        if (typeof bulkForm.requestSubmit === 'function') {
            bulkForm.requestSubmit();
        } else {
            bulkForm.submit();
        }
    }
    const selectAllBtn = document.getElementById('cmUsersSelectAll');
    const deselectAllBtn = document.getElementById('cmUsersDeselectAll');
    const disableBtn = document.getElementById('cmUsersDisable');
    const enableBtn = document.getElementById('cmUsersEnable');
    const sendBtn = document.getElementById('cmUsersSendAccess');
    const printBtn = document.getElementById('cmUsersPrint');
    const exportBtn = document.getElementById('cmUsersExport');
    if (selectAllBtn) selectAllBtn.addEventListener('click', () => rowChecks().forEach(cb => cb.checked = true));
    if (deselectAllBtn) deselectAllBtn.addEventListener('click', () => rowChecks().forEach(cb => cb.checked = false));
    if (disableBtn) disableBtn.addEventListener('click', () => { if (window.confirm('Desactiver les utilisateurs selectionnes ?')) submitBulk('disable'); });
    if (enableBtn) enableBtn.addEventListener('click', () => { if (window.confirm('Activer les utilisateurs selectionnes ?')) submitBulk('enable'); });
    if (sendBtn) sendBtn.addEventListener('click', () => { if (window.confirm('Envoyer les acces par email aux utilisateurs selectionnes ?')) submitBulk('send'); });
    if (printBtn) printBtn.addEventListener('click', function () {
        if (!table) return;
        const w = window.open('', '_blank'); if (!w) return;
        w.document.write('<html><head><title>Impression utilisateurs</title><style>body{font-family:Arial;padding:16px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d1d5db;padding:8px}th{background:#f3f4f6}</style></head><body>');
        w.document.write(table.outerHTML); w.document.write('</body></html>'); w.document.close(); w.print();
    });
    if (exportBtn) exportBtn.addEventListener('click', function () {
        if (!table) return;
        const csv = Array.from(table.querySelectorAll('tr')).map(function (tr) {
            return Array.from(tr.querySelectorAll('th,td')).map(function (cell) {
                return '"' + String((cell.textContent || '').trim()).replace(/"/g, '""') + '"';
            }).join(';');
        }).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a'); link.href = URL.createObjectURL(blob); link.download = 'utilisateurs.csv'; document.body.appendChild(link); link.click(); document.body.removeChild(link);
    });
    if (table) table.addEventListener('click', function (event) {
        const editBtn = event.target.closest('.js-user-edit');
        if (!editBtn) return;
        const userId = editBtn.getAttribute('data-row-id') || '';
        if (!userId) return;
        const editUrl = '?page=gestion_utilisateurs&action=edit&id_utilisateur=' + encodeURIComponent(userId);
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(editUrl);
            return;
        }
        window.location.href = editUrl;
    });
})();
</script>
