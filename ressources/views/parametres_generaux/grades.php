<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'grades',
    'title' => '',
    'icon' => 'fa-medal',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_grade',
    'id_param' => 'id_grade',
    'list' => is_array($GLOBALS['listeGrade'] ?? null) ? $GLOBALS['listeGrade'] : [],
    'edit' => $GLOBALS['grade_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_grade', 'id_grade'],
    'add_button_name' => 'btn_add_grades',
    'edit_button_name' => 'btn_modifier_grades',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'grades',
            'label' => 'Libelle grade',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Professeur titulaire',
            'value_key' => 'lib_grade',
        ],
    ],
    'columns' => [
        ['key' => 'id_grade', 'label' => 'ID'],
        ['key' => 'lib_grade', 'label' => 'Grade'],
    ],
]);
