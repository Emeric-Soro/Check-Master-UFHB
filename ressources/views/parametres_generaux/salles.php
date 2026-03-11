<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');

// Gestion legacy conservee cote controller, rendu migre en components.
require_once __DIR__ . '/../../../app/controllers/GestionSallesController.php';
$gestionSallesController = new GestionSallesController();

$salle_a_modifier = null;
$messageErreur = '';
$messageSuccess = '';

if (isset($_POST['btn_add_salle']) || isset($_POST['btn_modifier_salle'])) {
    $result = $gestionSallesController->ajouterOuModifierSalle($_POST);
    if (!empty($result['success'])) {
        $messageSuccess = (string) ($result['message'] ?? 'Operation reussie.');
    } else {
        $messageErreur = (string) ($result['message'] ?? 'Erreur operation.');
    }
}

if (isset($_POST['submit_delete_multiple']) && (string) ($_POST['submit_delete_multiple'] ?? '') === '1') {
    $selectedIds = is_array($_POST['selected_ids'] ?? null) ? $_POST['selected_ids'] : [];
    $result = $gestionSallesController->supprimerSallesMultiples($selectedIds);
    if (!empty($result['success'])) {
        $messageSuccess = (string) ($result['message'] ?? 'Suppression reussie.');
    } else {
        $messageErreur = (string) ($result['message'] ?? 'Erreur suppression.');
    }
}

if (isset($_GET['id_salle']) && $_GET['id_salle'] !== '') {
    $salle_a_modifier = $gestionSallesController->getSallePourModification((string) $_GET['id_salle']);
}

$page = max(1, (int) ($_GET['p'] ?? 1));
$limit = max(1, (int) ($_GET['limit'] ?? 10));
$search = trim((string) ($_GET['search'] ?? ''));
$resultats = $gestionSallesController->getSallesAvecPagination($search, $page, $limit);
$listeSalles = is_array($resultats['data'] ?? null) ? $resultats['data'] : [];

cm_render_param_crud_view([
    'page_slug' => $pageSlug,
    'action' => 'salles',
    'title' => '',
    'icon' => 'fa-door-open',
    'form_title_add' => '',
    'form_title_edit' => '',
    'id_key' => 'id_salle',
    'id_param' => 'id_salle',
    'list' => $listeSalles,
    'edit' => $salle_a_modifier,
    'message_success' => $messageSuccess,
    'message_error' => $messageErreur,
    'search_fields' => ['lib_salle', 'id_salle'],
    'default_per_page' => $limit,
    'add_button_name' => 'btn_add_salle',
    'edit_button_name' => 'btn_modifier_salle',
    'add_button_label' => 'Ajouter',
    'edit_button_label' => 'Modifier',
    'form_fields' => [
        [
            'name' => 'lib_salle',
            'label' => 'Libelle salle',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Entrez le nom de la salle',
            'value_key' => 'lib_salle',
        ],
    ],
    'columns' => [
        ['key' => 'id_salle', 'label' => 'ID'],
        ['key' => 'lib_salle', 'label' => 'Salle'],
    ],
]);
