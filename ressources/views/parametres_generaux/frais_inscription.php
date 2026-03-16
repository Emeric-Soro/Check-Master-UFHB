<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

$anneeOptions = [];
foreach ((array) ($GLOBALS['listeAnneesFrais'] ?? []) as $annee) {
    $id = (string) ($annee->id_annee_acad ?? '');
    if ($id === '') {
        continue;
    }
    $label = (string) ($annee->lib_annee ?? '');
    if ($label === '') {
        $label = $id;
    }
    $anneeOptions[$id] = $label;
}

$niveauOptions = [];
foreach ((array) ($GLOBALS['listeNiveauxFrais'] ?? []) as $niveau) {
    $id = (string) ($niveau->id_niv_etude ?? '');
    if ($id === '') {
        continue;
    }
    $label = (string) ($niveau->lib_niv_etude ?? '');
    $niveauOptions[$id] = $label !== '' ? $label : $id;
}

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'frais_inscription',
    'title' => '',
    'icon' => 'fa-money-bill-wave',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'frais_pk',
    'id_field_name' => 'frais_pk',
    'id_param' => 'frais_pk',
    'list' => is_array($GLOBALS['listeFraisInscription'] ?? null) ? $GLOBALS['listeFraisInscription'] : [],
    'edit' => $GLOBALS['frais_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_annee', 'id_annee_acad', 'id_niv_etude', 'lib_niv_etude', 'montant'],
    'add_button_name' => 'btn_add_frais_inscription',
    'edit_button_name' => 'btn_modifier_frais_inscription',
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
            'name' => 'id_niv_etude',
            'label' => "Niveau d'étude",
            'type' => 'select',
            'required' => true,
            'options' => $niveauOptions,
            'value_key' => 'id_niv_etude',
        ],
        [
            'name' => 'montant',
            'label' => 'Montant',
            'type' => 'number',
            'required' => true,
            'min' => 0,
            'step' => '0.01',
            'value_key' => 'montant',
        ],
    ],
    'columns' => [
        ['key' => 'lib_annee', 'label' => 'Année'],
        ['key' => 'id_niv_etude', 'label' => 'Code niveau'],
        ['key' => 'lib_niv_etude', 'label' => 'Niveau'],
        ['key' => 'montant', 'label' => 'Montant (FCFA)', 'value' => static function ($row): string {
            $montant = (float) ($row->montant ?? 0);
            return number_format($montant, 2, ',', ' ');
        }],
    ],
]);
