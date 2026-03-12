<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'qualite_jury',
    'title' => '',
    'icon' => 'fa-user-shield',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_role_jury',
    'id_param' => 'id_role_jury',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_role_jury', 'code_qltjury', 'lib_role'],
    'add_button_name' => 'btn_add_qualite_jury',
    'edit_button_name' => 'btn_modifier_qualite_jury',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'code_qltjury',
            'label' => 'Code',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: PJ',
            'value_key' => 'code_qltjury',
            'size' => 'sm',
            'attrs' => ['maxlength' => '10'],
        ],
        [
            'name' => 'lib_role',
            'label' => 'Libellé rôle',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Président',
            'value_key' => 'lib_role',
        ],
    ],
    'columns' => [
        ['key' => 'id_role_jury', 'label' => 'ID'],
        ['key' => 'code_qltjury', 'label' => 'Code'],
        ['key' => 'lib_role', 'label' => 'Rôle'],
    ],
]);
