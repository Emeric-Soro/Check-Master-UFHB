<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'entreprises',
    'title' => '',
    'icon' => 'fa-building',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_entreprise',
    'id_param' => 'id_entreprise',
    'list' => is_array($GLOBALS['listeEntreprises'] ?? null) ? $GLOBALS['listeEntreprises'] : [],
    'edit' => $GLOBALS['entreprise_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_entreprise', 'id_entreprise'],
    'add_button_name' => 'btn_add_entreprise',
    'edit_button_name' => 'btn_modifier_entreprise',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_entreprise',
            'label' => 'Libelle entreprise',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Entrez le libelle',
            'value_key' => 'lib_entreprise',
        ],
    ],
    'columns' => [
        ['key' => 'id_entreprise', 'label' => 'ID'],
        ['key' => 'lib_entreprise', 'label' => 'Entreprise'],
    ],
]);
