<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'etablissement_origine',
    'title' => '',
    'icon' => 'fa-school',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_etablissement',
    'id_param' => 'id_etablissement',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_etablissement', 'libelle_long', 'libelle_court'],
    'add_button_name' => 'btn_add_etablissement_origine',
    'edit_button_name' => 'btn_modifier_etablissement_origine',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'libelle_long',
            'label' => 'Libellé long',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Nom complet',
            'value_key' => 'libelle_long',
        ],
        [
            'name' => 'libelle_court',
            'label' => 'Libellé court',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: UFHB',
            'value_key' => 'libelle_court',
            'size' => 'sm',
            'attrs' => ['maxlength' => '20'],
        ],
    ],
    'columns' => [
        ['key' => 'id_etablissement', 'label' => 'ID'],
        ['key' => 'libelle_long', 'label' => 'Libellé long'],
        ['key' => 'libelle_court', 'label' => 'Libellé court'],
    ],
]);
