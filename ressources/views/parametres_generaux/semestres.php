<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

$niveauOptions = [];
foreach (($GLOBALS['listeNiveauxEtude'] ?? []) as $niveau) {
    $id = (string) ($niveau->id_niv_etude ?? '');
    if ($id === '') {
        continue;
    }
    $niveauOptions[$id] = (string) ($niveau->lib_niv_etude ?? ('Niveau ' . $id));
}

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'semestres',
    'title' => '',
    'icon' => 'fa-calendar-check',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_semestre',
    'id_param' => 'id_semestre',
    'list' => is_array($GLOBALS['listeSemestres'] ?? null) ? $GLOBALS['listeSemestres'] : [],
    'edit' => $GLOBALS['semestre_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_semestre', 'lib_niv_etude', 'id_semestre'],
    'add_button_name' => 'btn_add_semestre',
    'edit_button_name' => 'btn_modifier_semestre',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_semestre',
            'label' => 'Libelle semestre',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Semestre 1',
            'value_key' => 'lib_semestre',
        ],
        [
            'name' => 'niveau_etude',
            'label' => 'Niveau etude',
            'type' => 'select',
            'required' => true,
            'options' => $niveauOptions,
            'value_key' => 'id_niv_etude',
        ],
    ],
    'columns' => [
        ['key' => 'id_semestre', 'label' => 'ID'],
        ['key' => 'lib_semestre', 'label' => 'Semestre'],
        ['key' => 'lib_niv_etude', 'label' => 'Niveau etude'],
    ],
]);
