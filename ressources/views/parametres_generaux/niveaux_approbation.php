<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'niveaux_approbation',
    'title' => 'Gestion des niveaux d approbation',
    'icon' => 'fa-diagram-project',
    'form_title_add' => 'Ajout niveau approbation',
    'form_title_edit' => 'Modification niveau approbation',
    'id_key' => 'id_approb',
    'id_param' => 'id_approb',
    'list' => is_array($GLOBALS['listeNiveaux'] ?? null) ? $GLOBALS['listeNiveaux'] : [],
    'edit' => $GLOBALS['niveau_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['lib_approb', 'id_approb'],
    'add_button_name' => 'btn_add_niveau_approbation',
    'edit_button_name' => 'btn_modifier_niveau_approbation',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'niveaux_approbation',
            'label' => 'Libelle niveau',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: Validation directeur',
            'value_key' => 'lib_approb',
        ],
    ],
    'columns' => [
        ['key' => 'id_approb', 'label' => 'ID'],
        ['key' => 'lib_approb', 'label' => 'Niveau approbation'],
    ],
]);
