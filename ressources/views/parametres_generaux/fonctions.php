<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'fonctions',
    'title' => 'Gestion des fonctions',
    'icon' => 'fa-briefcase',
    'form_title_add' => 'Ajout fonction',
    'form_title_edit' => 'Modification fonction',
    'id_key' => 'id_fonction',
    'id_param' => 'id_fonction',
    'list' => is_array($GLOBALS['listeFonctions'] ?? null) ? $GLOBALS['listeFonctions'] : [],
    'edit' => $GLOBALS['fonction_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_fonction', 'id_fonction'],
    'add_button_name' => 'btn_add_fonction',
    'edit_button_name' => 'btn_modifier_fonction',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_fonction',
            'label' => 'Libelle fonction',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Entrez le libelle',
            'value_key' => 'lib_fonction',
        ],
    ],
    'columns' => [
        ['key' => 'id_fonction', 'label' => 'ID'],
        ['key' => 'lib_fonction', 'label' => 'Fonction'],
    ],
]);
