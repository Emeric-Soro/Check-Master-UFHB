<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'niveaux_acces',
    'title' => '',
    'icon' => 'fa-lock',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_niveau_acces_donnees',
    'id_param' => 'id_niveau',
    'id_field_name' => 'id_niveau_acces_donnees',
    'list' => is_array($GLOBALS['listeNiveaux'] ?? null) ? $GLOBALS['listeNiveaux'] : [],
    'edit' => $GLOBALS['niveau_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_niv_acces', 'lib_niveau_acces_donnees', 'id_niveau_acces_donnees'],
    'add_button_name' => 'btn_add_niveau',
    'edit_button_name' => 'btn_modifier_niveau_acces',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_niveau_acces_donnees',
            'label' => 'Libelle niveau',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Lecture seule',
            'value_key' => 'lib_niveau_acces_donnees',
        ],
    ],
    'columns' => [
        ['key' => 'id_niveau_acces_donnees', 'label' => 'ID'],
        ['key' => 'lib_niv_acces', 'label' => 'Niveau'],
    ],
]);
