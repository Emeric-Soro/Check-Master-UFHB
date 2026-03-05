<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_specifiques');

$anneeOptions = [];
foreach ((array) ($GLOBALS['listeAnneesBareme'] ?? []) as $annee) {
    $id = (string) ($annee->id_annee_acad ?? '');
    if ($id === '') {
        continue;
    }
    $anneeOptions[$id] = (string) ($annee->lib_annee ?? $id);
}

$critereOptions = [];
foreach ((array) ($GLOBALS['listeCriteresBareme'] ?? []) as $critere) {
    $id = (string) ($critere->id_critere ?? '');
    if ($id === '') {
        continue;
    }
    $label = trim((string) ($critere->code_critere ?? '') . ' - ' . (string) ($critere->lib_critere ?? ''));
    $critereOptions[$id] = $label !== '' ? $label : $id;
}

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'bareme_critere',
    'title' => '',
    'icon' => 'fa-scale-balanced',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'bareme_pk',
    'id_field_name' => 'bareme_pk',
    'id_param' => 'bareme_pk',
    'list' => is_array($GLOBALS['listeBaremes'] ?? null) ? $GLOBALS['listeBaremes'] : [],
    'edit' => $GLOBALS['bareme_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_annee', 'code_critere', 'lib_critere', 'bareme'],
    'add_button_name' => 'btn_add_bareme_critere',
    'edit_button_name' => 'btn_modifier_bareme_critere',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'id_annee_acad',
            'label' => 'Année académique',
            'type' => 'select',
            'required' => true,
            'options' => $anneeOptions,
            'value_key' => 'id_annee_acad',
        ],
        [
            'name' => 'id_critere',
            'label' => 'Critère',
            'type' => 'select',
            'required' => true,
            'options' => $critereOptions,
            'value_key' => 'id_critere',
        ],
        [
            'name' => 'bareme',
            'label' => 'Barème',
            'type' => 'number',
            'required' => true,
            'min' => 1,
            'step' => 1,
            'value_key' => 'bareme',
        ],
    ],
    'columns' => [
        ['key' => 'lib_annee', 'label' => 'Année'],
        ['key' => 'code_critere', 'label' => 'Code critère'],
        ['key' => 'lib_critere', 'label' => 'Critère'],
        ['key' => 'bareme', 'label' => 'Barème'],
    ],
]);
