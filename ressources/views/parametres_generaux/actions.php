<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'actions',
    'title' => '',
    'icon' => 'fa-tasks',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_action',
    'id_param' => 'id_action',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_action', 'id_action'],
    'add_button_name' => 'btn_add_actions',
    'edit_button_name' => 'btn_modifier_actions',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_action',
            'label' => 'Libelle action',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Modifier',
            'value_key' => 'lib_action',
        ],
    ],
    'columns' => [
        ['key' => 'id_action', 'label' => 'ID'],
        ['key' => 'lib_action', 'label' => 'Libelle'],
    ],
]);
