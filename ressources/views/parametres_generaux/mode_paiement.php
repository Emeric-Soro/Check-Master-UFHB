<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'mode_paiement',
    'title' => '',
    'icon' => 'fa-credit-card',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_mode_paiement',
    'id_param' => 'id_mode_paiement',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_mode_paiement', 'code_mode_paiement', 'libelle_mode_paement'],
    'add_button_name' => 'btn_add_mode_paiement',
    'edit_button_name' => 'btn_modifier_mode_paiement',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'code_mode_paiement',
            'label' => 'Code',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: ES',
            'value_key' => 'code_mode_paiement',
            'size' => 'sm',
            'attrs' => ['maxlength' => '10'],
        ],
        [
            'name' => 'libelle_mode_paement',
            'label' => 'Libellé mode paiement',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Espèce',
            'value_key' => 'libelle_mode_paement',
        ],
    ],
    'columns' => [
        ['key' => 'id_mode_paiement', 'label' => 'ID'],
        ['key' => 'code_mode_paiement', 'label' => 'Code'],
        ['key' => 'libelle_mode_paement', 'label' => 'Libellé'],
    ],
]);
