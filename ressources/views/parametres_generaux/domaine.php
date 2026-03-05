<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'domaine',
    'title' => '',
    'icon' => 'fa-diagram-project',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_domaine',
    'id_param' => 'id_domaine',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_domaine', 'lib_domaine'],
    'add_button_name' => 'btn_add_domaine',
    'edit_button_name' => 'btn_modifier_domaine',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_domaine',
            'label' => 'Libellé domaine',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Data Science',
            'value_key' => 'lib_domaine',
        ],
    ],
    'columns' => [
        ['key' => 'id_domaine', 'label' => 'ID'],
        ['key' => 'lib_domaine', 'label' => 'Domaine'],
    ],
]);
