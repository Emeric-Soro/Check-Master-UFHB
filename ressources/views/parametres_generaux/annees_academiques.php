<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'annees_academiques',
    'title' => 'Gestion des annees academiques',
    'icon' => 'fa-calendar-alt',
    'form_title_add' => 'Ajout annee academique',
    'form_title_edit' => 'Modification annee academique',
    'id_key' => 'id_annee_acad',
    'id_param' => 'id_annee_acad',
    'list' => is_array($GLOBALS['listeAnnees'] ?? null) ? $GLOBALS['listeAnnees'] : [],
    'edit' => $GLOBALS['annee_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_annee_acad', 'date_debut', 'date_fin'],
    'add_button_name' => 'btn_add_annees_academiques',
    'edit_button_name' => 'btn_modifier_annees_academiques',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'date_debut',
            'label' => 'Date debut',
            'type' => 'date',
            'required' => true,
            'value_key' => 'date_debut',
        ],
        [
            'name' => 'date_fin',
            'label' => 'Date fin',
            'type' => 'date',
            'required' => true,
            'value_key' => 'date_fin',
        ],
    ],
    'columns' => [
        ['key' => 'id_annee_acad', 'label' => 'Code'],
        ['key' => 'annee_lib', 'label' => 'Annee', 'value' => static function ($row): string {
            $dateDebut = (string) ($row->date_debut ?? '');
            $dateFin = (string) ($row->date_fin ?? '');
            if ($dateDebut === '' || $dateFin === '') {
                return (string) ($row->id_annee_acad ?? '-');
            }

            $y1 = date('Y', strtotime($dateDebut));
            $y2 = date('Y', strtotime($dateFin));
            return $y1 . '-' . $y2;
        }],
        ['key' => 'date_debut', 'label' => 'Date debut'],
        ['key' => 'date_fin', 'label' => 'Date fin'],
    ],
]);
