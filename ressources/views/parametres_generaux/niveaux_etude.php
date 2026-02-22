<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

$enseignantOptions = [];
foreach (($GLOBALS['listeEnseignants'] ?? []) as $enseignant) {
    $id = (string) ($enseignant->id_enseignant ?? '');
    if ($id === '') {
        continue;
    }
    $enseignantOptions[$id] = trim((string) ($enseignant->nom_enseignant ?? '') . ' ' . (string) ($enseignant->prenom_enseignant ?? ''));
}

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'niveaux_etude',
    'title' => '',
    'icon' => 'fa-graduation-cap',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_niv_etude',
    'id_param' => 'id_niv_etude',
    'list' => is_array($GLOBALS['listeNiveaux'] ?? null) ? $GLOBALS['listeNiveaux'] : [],
    'edit' => $GLOBALS['niveau_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_niv_etude', 'nom_enseignant', 'prenom_enseignant', 'id_niv_etude'],
    'add_button_name' => 'btn_add_niveau',
    'edit_button_name' => 'btn_modifier_niveau',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_niv_etude',
            'label' => 'Libelle niveau',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Master 1',
            'value_key' => 'lib_niv_etude',
        ],
        [
            'name' => 'montant_scolarite',
            'label' => 'Montant scolarite',
            'type' => 'number',
            'required' => true,
            'min' => 0,
            'step' => '0.01',
            'value_key' => 'montant_scolarite',
        ],
        [
            'name' => 'montant_inscription',
            'label' => 'Montant inscription',
            'type' => 'number',
            'required' => true,
            'min' => 0,
            'step' => '0.01',
            'value_key' => 'montant_inscription',
        ],
        [
            'name' => 'id_enseignant',
            'label' => 'Responsable niveau',
            'type' => 'select',
            'required' => true,
            'options' => $enseignantOptions,
            'value_key' => 'id_enseignant',
        ],
    ],
    'columns' => [
        ['key' => 'id_niv_etude', 'label' => 'ID'],
        ['key' => 'lib_niv_etude', 'label' => 'Niveau'],
        ['key' => 'montant_scolarite', 'label' => 'Scolarite'],
        ['key' => 'montant_inscription', 'label' => 'Inscription'],
        ['key' => 'responsable', 'label' => 'Responsable', 'value' => static function ($row): string {
            $nom = (string) ($row->nom_enseignant ?? '');
            $prenom = (string) ($row->prenom_enseignant ?? '');
            return trim($nom . ' ' . $prenom);
        }],
    ],
]);
