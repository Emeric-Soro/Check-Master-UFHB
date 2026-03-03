<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_specifiques');

$entrepriseOptions = ['' => '-- Sélectionner --'];
foreach ((array) ($GLOBALS['listeEntreprisesRef'] ?? []) as $row) {
    $id = (string) ($row->id ?? '');
    if ($id === '') {
        continue;
    }
    $entrepriseOptions[$id] = (string) ($row->label ?? $id);
}

$fonctionOptions = ['' => '-- Aucune --'];
foreach ((array) ($GLOBALS['listeFonctionsRef'] ?? []) as $row) {
    $id = (string) ($row->id ?? '');
    if ($id === '') {
        continue;
    }
    $fonctionOptions[$id] = (string) ($row->label ?? $id);
}

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'maitre_stage',
    'title' => '',
    'icon' => 'fa-user-tie',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_maitre_stage',
    'id_param' => 'id_maitre_stage',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_maitre_stage', 'Nom', 'prenom', 'email', 'telephone'],
    'add_button_name' => 'btn_add_maitre_stage',
    'edit_button_name' => 'btn_modifier_maitre_stage',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'id_maitre_stage',
            'label' => 'ID maître de stage',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: MS-1-001',
            'value_key' => 'id_maitre_stage',
        ],
        [
            'name' => 'Nom',
            'label' => 'Nom',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Nom',
            'value_key' => 'Nom',
        ],
        [
            'name' => 'prenom',
            'label' => 'Prénom',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Prénom',
            'value_key' => 'prenom',
        ],
        [
            'name' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'required' => false,
            'placeholder' => 'email@domaine.com',
            'value_key' => 'email',
        ],
        [
            'name' => 'telephone',
            'label' => 'Téléphone',
            'type' => 'text',
            'required' => false,
            'placeholder' => '+225...',
            'value_key' => 'telephone',
        ],
        [
            'name' => 'id_entreprise',
            'label' => 'Entreprise',
            'type' => 'select',
            'required' => true,
            'options' => $entrepriseOptions,
            'value_key' => 'id_entreprise',
        ],
        [
            'name' => 'id_fonction',
            'label' => 'Fonction',
            'type' => 'select',
            'required' => false,
            'options' => $fonctionOptions,
            'value_key' => 'id_fonction',
        ],
    ],
    'columns' => [
        ['key' => 'id_maitre_stage', 'label' => 'ID'],
        ['key' => 'Nom', 'label' => 'Nom'],
        ['key' => 'prenom', 'label' => 'Prénom'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'telephone', 'label' => 'Téléphone'],
        ['key' => 'id_entreprise', 'label' => 'Entreprise'],
        ['key' => 'id_fonction', 'label' => 'Fonction'],
    ],
]);
