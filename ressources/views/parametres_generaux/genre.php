<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'genre',
    'title' => '',
    'icon' => 'fa-venus-mars',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_genre',
    'id_param' => 'id_genre',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_genre', 'libelle_genre'],
    'add_button_name' => 'btn_add_genre',
    'edit_button_name' => 'btn_modifier_genre',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'id_genre',
            'label' => 'Code genre',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: M',
            'value_key' => 'id_genre',
            'size' => 'xs',
            'attrs' => ['maxlength' => '1'],
        ],
        [
            'name' => 'libelle_genre',
            'label' => 'Libellé genre',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Masculin',
            'value_key' => 'libelle_genre',
        ],
    ],
    'columns' => [
        ['key' => 'id_genre', 'label' => 'ID'],
        ['key' => 'libelle_genre', 'label' => 'Genre'],
    ],
]);
