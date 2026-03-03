<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'session',
    'title' => '',
    'icon' => 'fa-clock',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_session',
    'id_param' => 'id_session',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_session', 'lib_session'],
    'add_button_name' => 'btn_add_session',
    'edit_button_name' => 'btn_modifier_session',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_session',
            'label' => 'Libellé session',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Octobre',
            'value_key' => 'lib_session',
        ],
    ],
    'columns' => [
        ['key' => 'id_session', 'label' => 'ID'],
        ['key' => 'lib_session', 'label' => 'Session'],
    ],
]);
