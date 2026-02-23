<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');
$activeTab = (string) ($_GET['tab'] ?? 'groupes');
if (!in_array($activeTab, ['groupes', 'types'], true)) {
    $activeTab = 'groupes';
}

$tabGroupUrl = '?page=' . rawurlencode($pageSlug) . '&action=fonction_utilisateur&tab=groupes';
$tabTypeUrl = '?page=' . rawurlencode($pageSlug) . '&action=fonction_utilisateur&tab=types';

$typeOptions = [];
foreach (($GLOBALS['listeTypesAll'] ?? []) as $type) {
    $id = (string) ($type->id_type_utilisateur ?? '');
    if ($id === '') {
        continue;
    }
    $typeOptions[$id] = (string) ($type->lib_type_utilisateur ?? ('Type ' . $id));
}
?>
<section class="cm-prd6-admin-screen cm-prd6-admin-screen--tabs">
    <div class="cm-tab-links" role="tablist" aria-label="Fonctions utilisateurs">
        <a href="<?= htmlspecialchars($tabGroupUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn <?= $activeTab === 'groupes' ? 'is-info' : 'is-light' ?>">
            <i class="fas fa-users"></i>
            <span>Groupes</span>
        </a>
        <a href="<?= htmlspecialchars($tabTypeUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn <?= $activeTab === 'types' ? 'is-info' : 'is-light' ?>">
            <i class="fas fa-tags"></i>
            <span>Types</span>
        </a>
    </div>

    <?php
    if ($activeTab === 'types') {
        cm_render_param_crud_view([
            'page_slug' => $pageSlug,
            'action' => 'fonction_utilisateur',
            'extra_query' => ['tab' => 'types'],
            'title' => 'Gestion des types utilisateurs',
            'icon' => 'fa-tags',
            'form_title_add' => 'Ajout type utilisateur',
            'form_title_edit' => 'Modification type utilisateur',
            'id_key' => 'id_type_utilisateur',
            'id_param' => 'id_type',
            'list' => is_array($GLOBALS['listeTypes'] ?? null) ? $GLOBALS['listeTypes'] : [],
            'edit' => $GLOBALS['type_a_modifier'] ?? null,
            'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
            'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
            'search_fields' => ['lib_type_utilisateur', 'id_type_utilisateur'],
            'add_button_name' => 'submit_add_type',
            'edit_button_name' => 'btn_modifier_type',
            'add_button_label' => 'Ajouter',
            'edit_button_label' => 'Modifier',
            'form_fields' => [
                [
                    'name' => 'lib_type_utilisateur',
                    'label' => 'Libelle type utilisateur',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Ex: Enseignant simple',
                    'value_key' => 'lib_type_utilisateur',
                ],
            ],
            'columns' => [
                ['key' => 'id_type_utilisateur', 'label' => 'ID'],
                ['key' => 'lib_type_utilisateur', 'label' => 'Type utilisateur'],
            ],
        ]);
    } else {
        cm_render_param_crud_view([
            'page_slug' => $pageSlug,
            'action' => 'fonction_utilisateur',
            'extra_query' => ['tab' => 'groupes'],
            'title' => 'Gestion des groupes utilisateurs',
            'icon' => 'fa-users-cog',
            'form_title_add' => 'Ajout groupe utilisateur',
            'form_title_edit' => 'Modification groupe utilisateur',
            'id_key' => 'id_GU',
            'id_field_name' => 'id_groupe',
            'id_param' => 'id_groupe',
            'list' => is_array($GLOBALS['listeGroupes'] ?? null) ? $GLOBALS['listeGroupes'] : [],
            'edit' => $GLOBALS['groupe_a_modifier'] ?? null,
            'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
            'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
            'search_fields' => ['lib_GU', 'lib_type_utilisateur', 'id_GU'],
            'add_button_name' => 'submit_add_groupe',
            'edit_button_name' => 'btn_modifier_groupe',
            'add_button_label' => 'Ajouter',
            'edit_button_label' => 'Modifier',
            'form_fields' => [
                [
                    'name' => 'lib_groupe',
                    'label' => 'Libelle groupe',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Ex: Commission',
                    'value_key' => 'lib_GU',
                ],
                [
                    'name' => 'id_type_utilisateur',
                    'label' => 'Type utilisateur',
                    'type' => 'select',
                    'required' => false,
                    'options' => $typeOptions,
                    'value_key' => 'id_type_utilisateur',
                ],
            ],
            'columns' => [
                ['key' => 'id_GU', 'label' => 'ID'],
                ['key' => 'lib_GU', 'label' => 'Groupe'],
                ['key' => 'type_utilisateur', 'label' => 'Type', 'value' => static function ($row): string {
                    return (string) ($row->lib_type_utilisateur ?? $row->id_type_utilisateur ?? '-');
                }],
            ],
        ]);
    }
    ?>
</section>
