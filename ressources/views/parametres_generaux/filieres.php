<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'filieres',
    'title' => '',
    'icon' => 'fa-graduation-cap',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_filiere',
    'id_param' => 'id_filiere',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_filiere', 'lib_filiere'],
    'add_button_name' => 'btn_add_filieres',
    'edit_button_name' => 'btn_modifier_filieres',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_filiere',
            'label' => 'Libellé filière',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: MIAGE-GI',
            'value_key' => 'lib_filiere',
        ],
    ],
    'columns' => [
        ['key' => 'id_filiere', 'label' => 'ID'],
        ['key' => 'lib_filiere', 'label' => 'Filière'],
    ],
]);
