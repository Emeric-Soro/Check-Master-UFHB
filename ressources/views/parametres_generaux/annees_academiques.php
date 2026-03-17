<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');
$anneesList = is_array($GLOBALS['listeAnnees'] ?? null) ? $GLOBALS['listeAnnees'] : [];
$today = date('Y-m-d');
$activeYearId = null;

foreach ($anneesList as $anneeRow) {
    $dateDebut = (string) ($anneeRow->date_deb ?? '');
    $dateFin = (string) ($anneeRow->date_fin ?? '');
    if ($dateDebut !== '' && $dateFin !== '' && $today >= $dateDebut && $today <= $dateFin) {
        $activeYearId = (string) ($anneeRow->id_annee_acad ?? '');
        break;
    }
}

if ($activeYearId === '' || $activeYearId === null) {
    $fallback = $anneesList[0] ?? null;
    $activeYearId = (string) ($fallback->id_annee_acad ?? '');
}

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'annees_academiques',
    'title' => '',
    'icon' => 'fa-calendar-alt',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_annee_acad',
    'id_param' => 'id_annee_acad',
    'list' => $anneesList,
    'edit' => $GLOBALS['annee_a_modifier'] ?? null,
    'message_success' => (string) ($GLOBALS['messageSuccess'] ?? ''),
    'message_error' => (string) ($GLOBALS['messageErreur'] ?? ''),
    'search_fields' => ['id_annee_acad', 'date_deb', 'date_fin'],
    'no_delete' => true,
    'add_button_name' => 'btn_add_annees_academiques',
    'edit_button_name' => 'btn_modifier_annees_academiques',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'date_debut',
            'label' => 'Date début',
            'type' => 'date',
            'required' => true,
            'value_key' => 'date_deb',
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
        ['key' => 'annee_lib', 'label' => 'Année académique', 'value' => static function ($row): string {
            $dateDebut = (string) ($row->date_deb ?? '');
            $dateFin = (string) ($row->date_fin ?? '');
            if ($dateDebut === '' || $dateFin === '') {
                return (string) ($row->id_annee_acad ?? '-');
            }

            $y1 = date('Y', strtotime($dateDebut));
            $y2 = date('Y', strtotime($dateFin));
            return $y1 . '-' . $y2;
        }],
        ['key' => 'date_debut', 'label' => 'Date début', 'source' => 'date_deb'],
        ['key' => 'date_fin', 'label' => 'Date fin'],
        ['key' => 'statut', 'label' => 'Statut', 'type' => 'badge', 'value' => static function ($row) use ($activeYearId): array {
            $isActive = (string) ($row->id_annee_acad ?? '') === $activeYearId;
            return [
                'label' => $isActive ? 'Actif' : 'Inactif',
                'type' => $isActive ? 'success' : 'warning',
            ];
        }],
    ],
]);
