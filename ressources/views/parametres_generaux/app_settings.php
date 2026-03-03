<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

$isSensitiveOptions = [
    '0' => 'Non',
    '1' => 'Oui',
];

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'app_settings',
    'title' => '',
    'icon' => 'fa-sliders',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'setting_key',
    'id_field_name' => 'current_setting_key',
    'id_param' => 'setting_key',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['setting_key', 'setting_value'],
    'add_button_name' => 'btn_add_app_settings',
    'edit_button_name' => 'btn_modifier_app_settings',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'setting_key',
            'label' => 'Clé',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: smtp_host',
            'value_key' => 'setting_key',
        ],
        [
            'name' => 'setting_value',
            'label' => 'Valeur',
            'type' => 'textarea',
            'required' => true,
            'rows' => 3,
            'placeholder' => 'Valeur de configuration',
            'value_key' => 'setting_value',
        ],
        [
            'name' => 'is_sensitive',
            'label' => 'Valeur sensible',
            'type' => 'select',
            'required' => true,
            'options' => $isSensitiveOptions,
            'value_key' => 'is_sensitive',
        ],
    ],
    'columns' => [
        ['key' => 'setting_key', 'label' => 'Clé'],
        ['key' => 'setting_value', 'label' => 'Valeur'],
        ['key' => 'is_sensitive', 'label' => 'Sensible'],
        ['key' => 'updated_at', 'label' => 'Mise à jour'],
    ],
]);
