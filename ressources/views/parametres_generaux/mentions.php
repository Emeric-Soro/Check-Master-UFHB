<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

$actifOptions = [
    '1' => 'Actif',
    '0' => 'Inactif',
];

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'mentions',
    'title' => '',
    'icon' => 'fa-award',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_mention',
    'id_param' => 'id_mention',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_mention', 'lib_mention'],
    'add_button_name' => 'btn_add_mentions',
    'edit_button_name' => 'btn_modifier_mentions',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_mention',
            'label' => 'Libellé mention',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Très Bien',
            'value_key' => 'lib_mention',
        ],
        [
            'name' => 'actif',
            'label' => 'Statut',
            'type' => 'select',
            'required' => true,
            'options' => $actifOptions,
            'value_key' => 'actif',
        ],
    ],
    'columns' => [
        ['key' => 'id_mention', 'label' => 'ID'],
        ['key' => 'lib_mention', 'label' => 'Mention'],
        ['key' => 'actif', 'label' => 'Actif'],
    ],
]);
