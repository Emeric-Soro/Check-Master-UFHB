<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_specifiques');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'type_enseignant',
    'title' => '',
    'icon' => 'fa-chalkboard-user',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_type_enseignant',
    'id_param' => 'id_type_enseignant',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_type_enseignant', 'libelle'],
    'add_button_name' => 'btn_add_type_enseignant',
    'edit_button_name' => 'btn_modifier_type_enseignant',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'libelle',
            'label' => 'Libellé type enseignant',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Administratif',
            'value_key' => 'libelle',
        ],
    ],
    'columns' => [
        ['key' => 'id_type_enseignant', 'label' => 'ID'],
        ['key' => 'libelle', 'label' => 'Type enseignant'],
    ],
]);
