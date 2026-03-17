<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

$typeOptions = [
    'info' => 'Information',
    'success' => 'Succes',
    'warning' => 'Avertissement',
    'error' => 'Erreur',
];

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'messages',
    'title' => '',
    'icon' => 'fa-envelope',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_message',
    'id_param' => 'id_message',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_message', 'type_message', 'contenu_message', 'id_message'],
    'add_button_name' => 'btn_add_messages',
    'edit_button_name' => 'btn_modifier_messages',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_message',
            'label' => 'Libelle message',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: message_bienvenue',
            'value_key' => 'lib_message',
        ],
        [
            'name' => 'type_message',
            'label' => 'Type message',
            'type' => 'select',
            'required' => true,
            'options' => $typeOptions,
            'value_key' => 'type_message',
        ],
        [
            'name' => 'contenu_message',
            'label' => 'Contenu message',
            'type' => 'textarea',
            'required' => true,
            'rows' => 4,
            'placeholder' => 'Contenu du message',
            'value_key' => 'contenu_message',
        ],
    ],
    'columns' => [
        ['key' => 'id_message', 'label' => 'ID'],
        ['key' => 'lib_message', 'label' => 'Libelle'],
        ['key' => 'type_message', 'label' => 'Type'],
        ['key' => 'contenu_message', 'label' => 'Contenu'],
    ],
]);
