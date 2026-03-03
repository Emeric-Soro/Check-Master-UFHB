<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

$actifOptions = [
    '1' => 'Actif',
    '0' => 'Inactif',
];

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'decisions_jury',
    'title' => '',
    'icon' => 'fa-gavel',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_decision',
    'id_param' => 'id_decision',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_decision', 'lib_decision', 'description'],
    'add_button_name' => 'btn_add_decisions_jury',
    'edit_button_name' => 'btn_modifier_decisions_jury',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_decision',
            'label' => 'Libellé décision',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Admis',
            'value_key' => 'lib_decision',
        ],
        [
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
            'required' => false,
            'rows' => 3,
            'placeholder' => 'Description de la décision',
            'value_key' => 'description',
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
        ['key' => 'id_decision', 'label' => 'ID'],
        ['key' => 'lib_decision', 'label' => 'Décision'],
        ['key' => 'description', 'label' => 'Description'],
        ['key' => 'actif', 'label' => 'Actif'],
    ],
]);
