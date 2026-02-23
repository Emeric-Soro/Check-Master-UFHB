<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'statut_jury',
    'title' => 'Gestion des statuts jury',
    'icon' => 'fa-gavel',
    'form_title_add' => 'Ajout statut jury',
    'form_title_edit' => 'Modification statut jury',
    'id_key' => 'id_jury',
    'id_field_name' => 'id_statut_jury',
    'id_param' => 'id_statut_jury',
    'list' => is_array($GLOBALS['listeStatuts'] ?? null) ? $GLOBALS['listeStatuts'] : [],
    'edit' => $GLOBALS['statut_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_jury', 'id_jury'],
    'add_button_name' => 'btn_add_statut_jury',
    'edit_button_name' => 'btn_modifier_statut_jury',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'statut_jury',
            'label' => 'Libelle statut jury',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: President',
            'value_key' => 'lib_jury',
        ],
    ],
    'columns' => [
        ['key' => 'id_jury', 'label' => 'ID'],
        ['key' => 'lib_jury', 'label' => 'Statut jury'],
    ],
]);
