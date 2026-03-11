<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'statut_reclamation',
    'title' => '',
    'icon' => 'fa-triangle-exclamation',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_statut_reclamation',
    'id_param' => 'id_statut_reclamation',
    'list' => is_array($GLOBALS['listeReferentiel'] ?? null) ? $GLOBALS['listeReferentiel'] : [],
    'edit' => $GLOBALS['item_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_statut_reclamation', 'libelle_statut_reclamation'],
    'add_button_name' => 'btn_add_statut_reclamation',
    'edit_button_name' => 'btn_modifier_statut_reclamation',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'libelle_statut_reclamation',
            'label' => 'Libellé statut',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Ex: En attente',
            'value_key' => 'libelle_statut_reclamation',
        ],
    ],
    'columns' => [
        ['key' => 'id_statut_reclamation', 'label' => 'ID'],
        ['key' => 'libelle_statut_reclamation', 'label' => 'Statut'],
    ],
]);
