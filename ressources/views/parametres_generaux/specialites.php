<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'specialites',
    'title' => '',
    'icon' => 'fa-user-graduate',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_specialite',
    'id_param' => 'id_specialite',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_specialite', 'id_specialite'],
    'add_button_name' => 'btn_add_specialites',
    'edit_button_name' => 'btn_modifier_specialites',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_specialite',
            'label' => 'Libelle specialite',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Informatique',
            'value_key' => 'lib_specialite',
        ],
    ],
    'columns' => [
        ['key' => 'id_specialite', 'label' => 'ID'],
        ['key' => 'lib_specialite', 'label' => 'Specialite'],
    ],
]);
