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
$rowsPage = $utilisateursFiltres;

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

$editTypeValue = (string) ($utilisateurEdit->id_type_utilisateur ?? '');
$editGroupeValue = (string) ($utilisateurEdit->id_GU ?? '');
$editNiveauValue = (string) ($utilisateurEdit->id_niv_acces_donnee ?? '');
$editStatutValue = (string) ($utilisateurEdit->statut_utilisateur ?? 'Actif');
$editNomValue = (string) ($utilisateurEdit->nom_utilisateur ?? '');
$editLoginValue = (string) ($utilisateurEdit->login_utilisateur ?? '');

$editTypeLabel = strtolower(trim((string) ($typeOptions[$editTypeValue] ?? '')));
$initialNameOptions = [];
if ($editTypeLabel !== '') {
    if (strpos($editTypeLabel, 'etudiant') !== false) {
        foreach ($etudiantsNonUtilisateurs as $row) {
            $label = trim((string) (($row->nom_etu ?? '') . ' ' . ($row->prenom_etu ?? '')));
            if ($label === '') {
                continue;
            }
            $initialNameOptions[] = [
                'id' => (string) ($row->num_etu ?? ''),
                'label' => $label,
                'email' => trim((string) ($row->email_etu ?? '')),
            ];
        }
    } elseif (strpos($editTypeLabel, 'enseignant') !== false) {
        foreach ($enseignantsNonUtilisateurs as $row) {
            $label = trim((string) (($row->nom_enseignant ?? '') . ' ' . ($row->prenom_enseignant ?? '')));
            if ($label === '') {
                continue;
            }
            $initialNameOptions[] = [
                'id' => (string) ($row->id_enseignant ?? ''),
                'label' => $label,
                'email' => trim((string) ($row->mail_enseignant ?? '')),
            ];
        }
    } elseif (strpos($editTypeLabel, 'personnel') !== false || strpos($editTypeLabel, 'administratif') !== false) {
        foreach ($personnelNonUtilisateurs as $row) {
            $label = trim((string) (($row->nom_pers_admin ?? '') . ' ' . ($row->prenom_pers_admin ?? '')));
            if ($label === '') {
                continue;
            }
            $initialNameOptions[] = [
                'id' => (string) ($row->id_pers_admin ?? ''),
                'label' => $label,
                'email' => trim((string) ($row->email_pers_admin ?? '')),
            ];
        }
    }
}
$showNameSelectInitially = !$isMassMode && count($initialNameOptions) > 0;
?>
<?php if (!canView()): ?>
    <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => "Vous n'avez pas l'autorisation d'accéder à cette page."]); ?>
<?php else: ?>
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
            <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmUsersMassForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form method="POST" action="?page=gestion_utilisateurs" id="cmUsersMassForm" data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <div class="cm-grid-4">
                    <?php cm_component('form/select', ['name' => 'id_type_utilisateur', 'id' => 'cmMassTypeUtilisateur', 'label' => 'Type utilisateur', 'required' => true, 'options' => $typeOptions, 'control_class' => 'cm-field-md']); ?>
                    <div class="cm-form-group">
                        <label for="cmMassGroupeUtilisateur" class="cm-form-label">Groupe utilisateur</label>
                        <select name="id_GU" id="cmMassGroupeUtilisateur" class="cm-form-control cm-field-md" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($groupesUtilisateur as $groupe): $gid = (string) ($groupe->id_GU ?? ''); if ($gid === '') { continue; } ?>
                            <option value="<?= htmlspecialchars($gid, ENT_QUOTES, 'UTF-8') ?>" data-type-id="<?= htmlspecialchars((string) ($groupe->id_type_utilisateur ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ($groupe->lib_GU ?? ('Groupe ' . $gid)), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php cm_component('form/select', ['name' => 'id_niveau_acces', 'label' => 'Niveau acces', 'required' => true, 'options' => $niveauOptions, 'control_class' => 'cm-field-lg']); ?>
                    <?php cm_component('form/select', ['name' => 'statut_utilisateur', 'label' => 'Statut', 'required' => true, 'options' => ['Actif' => 'Actif', 'Inactif' => 'Inactif'], 'selected' => 'Actif', 'control_class' => 'cm-field-sm']); ?>
                </div>
                <div class="cm-form-group">
                    <label class="cm-form-label">Selection des personnes</label>
                    <div class="cm-table-wrapper is-mass-select">
                        <table class="cm-data-table" id="cmMassPersonsTable">
                            <thead><tr><th class="cm-data-table__th">Sel</th><th class="cm-data-table__th">Type</th><th class="cm-data-table__th">Nom</th><th class="cm-data-table__th">Email</th></tr></thead>
                            <tbody>
                                <?php $hasMassData = false; foreach ($enseignantsNonUtilisateurs as $p): $hasMassData = true; ?>
                                <tr class="cm-data-table__row" data-person-type="ens"><td class="cm-data-table__td"><input type="checkbox" name="selected_persons[]" value="ens_<?= htmlspecialchars((string) ($p->id_enseignant ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td><td class="cm-data-table__td">Enseignant</td><td class="cm-data-table__td"><?= htmlspecialchars(trim((string) (($p->nom_enseignant ?? '') . ' ' . ($p->prenom_enseignant ?? ''))), ENT_QUOTES, 'UTF-8') ?></td><td class="cm-data-table__td"><?= htmlspecialchars((string) ($p->mail_enseignant ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
                                <?php endforeach; foreach ($personnelNonUtilisateurs as $p): $hasMassData = true; ?>
                                <tr class="cm-data-table__row" data-person-type="pers"><td class="cm-data-table__td"><input type="checkbox" name="selected_persons[]" value="pers_<?= htmlspecialchars((string) ($p->id_pers_admin ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td><td class="cm-data-table__td">Personnel</td><td class="cm-data-table__td"><?= htmlspecialchars(trim((string) (($p->nom_pers_admin ?? '') . ' ' . ($p->prenom_pers_admin ?? ''))), ENT_QUOTES, 'UTF-8') ?></td><td class="cm-data-table__td"><?= htmlspecialchars((string) ($p->email_pers_admin ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
                                <?php endforeach; foreach ($etudiantsNonUtilisateurs as $p): $hasMassData = true; ?>
                                <tr class="cm-data-table__row" data-person-type="etu"><td class="cm-data-table__td"><input type="checkbox" name="selected_persons[]" value="etu_<?= htmlspecialchars((string) ($p->num_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td><td class="cm-data-table__td">Etudiant</td><td class="cm-data-table__td"><?= htmlspecialchars(trim((string) (($p->nom_etu ?? '') . ' ' . ($p->prenom_etu ?? ''))), ENT_QUOTES, 'UTF-8') ?></td><td class="cm-data-table__td"><?= htmlspecialchars((string) ($p->email_etu ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
                                <?php endforeach; if (!$hasMassData): ?>
                                <tr><td colspan="4" class="cm-data-table__td is-center">Aucune personne disponible.</td></tr>
                                <?php endif; ?>
                                <tr id="cmMassNoMatchRow" hidden><td colspan="4" class="cm-data-table__td is-center">Aucune personne disponible pour ce type.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php cm_component('crud/form-actions', ['actions' => array_filter([
                    ['tag' => 'a', 'href' => '?page=gestion_utilisateurs', 'label' => 'Retour', 'icon' => 'fa-arrow-left', 'class' => 'cm-btn is-light'],
                    canCreate() ? ['tag' => 'button', 'type' => 'submit', 'label' => 'Ajouter la selection', 'icon' => 'fa-users', 'class' => 'cm-btn is-primary', 'attrs' => ['name' => 'btn_add_multiple']] : null,
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
                    <?php cm_component('form/select', ['name' => 'id_type_utilisateur', 'id' => 'cmTypeUtilisateur', 'label' => 'Type utilisateur', 'required' => true, 'options' => $typeOptions, 'selected' => $editTypeValue, 'control_class' => 'cm-field-md']); ?>
                    <div class="cm-form-group">
                        <label for="cmGroupeUtilisateur" class="cm-form-label">Groupe utilisateur</label>
                        <select name="id_GU" id="cmGroupeUtilisateur" class="cm-form-control cm-field-md" required>
                            <?php foreach ($groupesUtilisateur as $groupe): $gid = (string) ($groupe->id_GU ?? ''); if ($gid === '') { continue; } ?>
                            <option value="<?= htmlspecialchars($gid, ENT_QUOTES, 'UTF-8') ?>" data-type-id="<?= htmlspecialchars((string) ($groupe->id_type_utilisateur ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $editGroupeValue === $gid ? 'selected' : '' ?>><?= htmlspecialchars((string) ($groupe->lib_GU ?? ('Groupe ' . $gid)), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="cm-form-group<?= $showNameSelectInitially ? ' cm-user-name-hidden' : '' ?>" id="cmUserNameTextWrap"><?php cm_component('form/input-text', ['name' => $showNameSelectInitially ? '' : 'nom_utilisateur', 'id' => 'cmNomUtilisateurText', 'label' => 'Nom utilisateur', 'required' => true, 'value' => $editNomValue, 'placeholder' => 'Nom complet', 'control_class' => 'cm-field-lg']); ?></div>
                    <div class="cm-form-group<?= $showNameSelectInitially ? '' : ' cm-user-name-hidden' ?>" id="cmUserNameSelectWrap"><label for="cmNomUtilisateurSelect" class="cm-form-label">Nom utilisateur</label><select id="cmNomUtilisateurSelect" class="cm-form-control cm-field-lg"<?= $showNameSelectInitially ? ' name="nom_utilisateur"' : '' ?>><?php foreach ($initialNameOptions as $index => $option): ?><option value="<?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?>" data-source-id="<?= htmlspecialchars($option['id'], ENT_QUOTES, 'UTF-8') ?>" data-source-email="<?= htmlspecialchars($option['email'], ENT_QUOTES, 'UTF-8') ?>" <?= ($option['label'] === $editNomValue || ($editNomValue === '' && $index === 0)) ? 'selected' : '' ?>><?= htmlspecialchars($option['email'] !== '' ? $option['label'] : ($option['label'] . ' (sans email)'), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                    <?php cm_component('form/select', ['name' => 'id_niveau_acces', 'id' => 'cmNiveauAcces', 'label' => 'Niveau acces', 'required' => true, 'options' => $niveauOptions, 'selected' => $editNiveauValue !== '' ? $editNiveauValue : (string) array_key_first($niveauOptions), 'control_class' => 'cm-field-lg']); ?>
                    <?php cm_component('form/select', ['name' => 'statut_utilisateur', 'id' => 'cmStatutUtilisateur', 'label' => 'Statut', 'required' => true, 'options' => ['Actif' => 'Actif', 'Inactif' => 'Inactif', 'Suspendu' => 'Suspendu'], 'selected' => $editStatutValue, 'control_class' => 'cm-field-sm']); ?>
                    <?php cm_component('form/input-text', ['name' => 'login_utilisateur', 'id' => 'cmLoginUtilisateur', 'label' => 'Login', 'required' => true, 'value' => $editLoginValue, 'placeholder' => 'login', 'control_class' => 'cm-field-md']); ?>
                </div>
                <div class="cm-form-group"><small id="cmLoginHint" class="cm-text-muted"></small></div>
                <?php cm_component('crud/form-actions', ['cancel_action' => ['label' => 'Annuler', 'type' => 'button', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['data-reset-form' => '1']], 'actions' => array_filter([
                    ['tag' => 'button', 'type' => 'reset', 'label' => 'Réinitialiser', 'icon' => 'fa-rotate-left', 'class' => 'cm-btn is-secondary is-sm'],
                    canCreate() ? ['tag' => 'a', 'href' => '?page=gestion_utilisateurs&action=addMasse', 'label' => 'Ajout en masse', 'icon' => 'fa-users', 'class' => 'cm-btn is-info is-sm'] : null,
                    ($utilisateurEdit ? canEdit() : canCreate()) ? ['tag' => 'button', 'type' => 'submit', 'label' => $utilisateurEdit ? 'Modifier' : 'Enregistrer', 'icon' => 'fa-save', 'class' => 'cm-btn is-primary is-sm', 'attrs' => ['name' => $utilisateurEdit ? 'btn_modifier_utilisateur' : 'btn_add_utilisateur']] : null,
                ])]); ?>
                <div id="cmUserFormState" class="cm-text-muted" aria-live="polite"></div>
            </form>
            <?php cm_component('crud/form-pole', ['title' => '', 'icon' => 'fa-user-plus', 'content' => (string) ob_get_clean()]); ?>
        <?php endif; ?>

        <?php if (!$isMassMode): ?>
            <form id="cmUsersFilters" method="GET" data-cm-ajax-form="true">
                <input type="hidden" name="page" value="gestion_utilisateurs">
                <?php cm_toolbar([
                    'screen' => 'gestion_utilisateurs',
                    'id_prefix' => 'cmUsers',
                    'search_value' => $filters['search'],
                    'can_delete' => canDelete(),
                    'can_view' => canView(),
                    'custom_actions' => array_filter([
                        canEdit() ? [
                            'tag' => 'button',
                            'type' => 'button',
                            'id' => 'cmUsers_enableBtn',
                            'label' => 'Activer',
                            'class' => 'cm-btn is-success is-sm',
                            'icon' => 'fa-check-circle',
                        ] : null,
                        canEdit() ? [
                            'tag' => 'button',
                            'type' => 'button',
                            'id' => 'cmUsers_disableBtn',
                            'label' => 'Désactiver',
                            'class' => 'cm-btn is-warning is-sm',
                            'icon' => 'fa-ban',
                        ] : null,
                        canEdit() ? [
                            'tag' => 'button',
                            'type' => 'button',
                            'id' => 'cmUsers_sendAccessBtn',
                            'label' => 'Envoyer accès',
                            'class' => 'cm-btn is-info is-sm',
                            'icon' => 'fa-paper-plane',
                            'attrs' => ['title' => 'Envoyer les identifiants'],
                        ] : null,
                    ]),
                ]); ?>
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
                            cm_column('nom_utilisateur', 'Nom &amp; Prénom'), cm_column('role_utilisateur', 'Type utilisateur'),
                            cm_column('lib_GU', 'Groupe'), cm_column('niveau_acces', 'Niveau d\'accès'),
                            cm_column('statut_utilisateur', 'Statut', ['type' => 'badge']), cm_column('login_utilisateur', 'Login'),
                        ],
                        'rows' => $userRows,
                        'row_key' => 'id_utilisateur',
                        'selectable' => true,
                        'actions' => array_filter([
                            canEdit() ? [ 'tag' => 'button', 'type' => 'button', 'label' => 'Modifier', 'icon' => 'fa-pen', 'class' => 'cm-btn-action is-edit js-user-edit' ] : null,
                            canDelete() ? [ 'tag' => 'button', 'type' => 'button', 'label' => 'Supprimer', 'icon' => 'fa-trash', 'class' => 'cm-btn-action is-delete js-user-delete' ] : null,
                        ]),
                        'empty_title' => 'Aucun utilisateur',
                        'empty_message' => 'Aucun enregistrement trouvé.',
                    ]); ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<style>
.cm-prd3-crud-screen #cmUserNameTextWrap.cm-user-name-hidden,
.cm-prd3-crud-screen #cmUserNameSelectWrap.cm-user-name-hidden {
    display: none !important;
}
</style>

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
    const massTypeSelect = document.getElementById('cmMassTypeUtilisateur');
    const massGroupSelect = document.getElementById('cmMassGroupeUtilisateur');
    const massPersonsTable = document.getElementById('cmMassPersonsTable');
    const massNoMatchRow = document.getElementById('cmMassNoMatchRow');
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
    function mapTypeToMassPersonKinds(typeId) {
        const id = String(typeId || '').trim();
        if (id === '4') return ['pers'];
        if (id === '5' || id === '6') return ['ens'];
        if (id === '7') return ['etu'];
        return [];
    }
    function syncMassGroupsByType() {
        if (!massTypeSelect || !massGroupSelect) {
            return;
        }
        const selectedType = String(massTypeSelect.value || '').trim();
        Array.from(massGroupSelect.options).forEach(function (option) {
            const optionType = String(option.getAttribute('data-type-id') || '').trim();
            if (option.value === '' || selectedType === '' || optionType === '') {
                option.hidden = false;
                return;
            }
            option.hidden = optionType !== selectedType;
        });
        if (massGroupSelect.selectedOptions.length && massGroupSelect.selectedOptions[0].hidden) {
            massGroupSelect.value = '';
        }
    }
    function syncMassPersonsByType() {
        if (!massTypeSelect || !massPersonsTable) {
            return;
        }
        const selectedType = String(massTypeSelect.value || '').trim();
        const allowedKinds = mapTypeToMassPersonKinds(selectedType);
        let visibleCount = 0;
        Array.from(massPersonsTable.querySelectorAll('tbody tr[data-person-type]')).forEach(function (row) {
            const rowType = String(row.getAttribute('data-person-type') || '').trim();
            const shouldShow = selectedType === '' || allowedKinds.includes(rowType);
            row.hidden = !shouldShow;
            if (!shouldShow) {
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = false;
                }
            } else {
                visibleCount++;
            }
        });
        if (massNoMatchRow) {
            massNoMatchRow.hidden = visibleCount > 0;
        }
    }

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
        if (!nomSelect || nomSelectWrap.classList.contains('cm-user-name-hidden') || !nomSelect.selectedOptions.length) {
            return '';
        }
        return nomSelect.selectedOptions[0].getAttribute('data-source-email') || '';
    }
    function syncSourceMetadata() {
        if (nomSelect && !nomSelectWrap.classList.contains('cm-user-name-hidden') && nomSelect.selectedOptions.length) {
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
            nomTextWrap.classList.add('cm-user-name-hidden');
            nomSelectWrap.classList.remove('cm-user-name-hidden');
            nomText.value = nomSelect.value || nomText.value || '';
            syncSourceMetadata();
            applySuggestedLogin();
        } else {
            if (nomSelect.value) nomText.value = nomSelect.value;
            nomSelect.removeAttribute('name'); nomText.setAttribute('name', 'nom_utilisateur');
            nomTextWrap.classList.remove('cm-user-name-hidden');
            nomSelectWrap.classList.add('cm-user-name-hidden');
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
        const currentName = !nomSelectWrap.classList.contains('cm-user-name-hidden') ? nomSelect.value : nomText.value;
        const suggestedLogin = generateLoginFromName(currentName);
        initialLoginSynced = true;
        loginInput.value = suggestedLogin;
        if (loginHint) {
            loginHint.textContent = sourceEmail
                ? "Email de l'entité détecté pour les notifications. Login généré automatiquement."
                : "Aucun email source valide trouvé pour ce profil. Renseignez l'email dans l'entité avant création.";
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
                    ? "Email entité détecté. "
                    : "Aucun email source valide. ";
                if (!data || !data.success) { loginHint.textContent = prefix + 'Verification login impossible.'; return; }
                if (data.available) { loginHint.textContent = prefix + 'Login disponible.'; return; }
                loginHint.textContent = data.suggestedLogin ? ('Login deja pris. Suggestion: ' + data.suggestedLogin) : (data.message || 'Login indisponible.');
            })
            .catch(function () { loginHint.textContent = ''; });
    }

    if (typeSelect) { typeSelect.addEventListener('change', syncNameFieldByType); syncNameFieldByType(); }
    if (massTypeSelect) {
        massTypeSelect.addEventListener('change', function () {
            syncMassGroupsByType();
            syncMassPersonsByType();
        });
        syncMassGroupsByType();
        syncMassPersonsByType();
    }
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
    const selectAllBtn = document.getElementById('cmUsers_selectAll');
    const deselectAllBtn = document.getElementById('cmUsers_deselectAll');
    const deleteBtn = document.getElementById('cmUsers_deleteBtn');
    const disableBtn = document.getElementById('cmUsers_disableBtn');
    const enableBtn = document.getElementById('cmUsers_enableBtn');
    const sendBtn = document.getElementById('cmUsers_sendAccessBtn');
    const printBtn = document.getElementById('cmUsers_printBtn');
    const exportBtn = document.getElementById('cmUsers_exportBtn');
    const selectedCount = deleteBtn ? deleteBtn.querySelector('.cm-delete-count') : null;

    const updateSelectionState = function () {
        const checks = rowChecks();
        const selected = checks.filter(cb => cb.checked);
        if (selectedCount) {
            selectedCount.textContent = selected.length > 0 ? (' (' + selected.length + ')') : '';
        }
        [deleteBtn, disableBtn, enableBtn, sendBtn].forEach(btn => {
            if (btn) btn.disabled = selected.length === 0;
        });
    };

    if (table) {
        table.addEventListener('change', function (e) {
            if (e.target.classList.contains('cm-table-check-row') || e.target.classList.contains('cm-table-check-all')) {
                updateSelectionState();
            }
        });
    }

    const confirmBulkAction = function (message) {
        return window.CM.confirm({
            title: 'Confirmation',
            message: message,
            type: 'warning',
            confirmText: 'Confirmer',
        });
    };
    if (selectAllBtn) selectAllBtn.addEventListener('click', () => { rowChecks().forEach(cb => cb.checked = true); updateSelectionState(); });
    if (deselectAllBtn) deselectAllBtn.addEventListener('click', () => { rowChecks().forEach(cb => cb.checked = false); updateSelectionState(); });
    if (deleteBtn) deleteBtn.addEventListener('click', async () => { if (await confirmBulkAction('Supprimer les utilisateurs selectionnes ?')) submitBulk('delete'); });
    if (disableBtn) disableBtn.addEventListener('click', async () => { if (await confirmBulkAction('Desactiver les utilisateurs selectionnes ?')) submitBulk('disable'); });
    if (enableBtn) enableBtn.addEventListener('click', async () => { if (await confirmBulkAction('Activer les utilisateurs selectionnes ?')) submitBulk('enable'); });
    if (sendBtn) sendBtn.addEventListener('click', async () => { if (await confirmBulkAction('Envoyer les acces par email aux utilisateurs selectionnes ?')) submitBulk('send'); });
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
