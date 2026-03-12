<?php
$pageSlug = (string) ($_GET['page'] ?? 'gestion_rh');
$activeTab = (string) ($_GET['tab'] ?? 'pers_admin');
if (!in_array($activeTab, ['pers_admin', 'enseignant'], true)) {
    $activeTab = 'pers_admin';
}

$messageSuccess = (string) ($GLOBALS['messageSuccess'] ?? '');
$messageErreur = (string) ($GLOBALS['messageErreur'] ?? '');

$listePersAdmin = is_array($GLOBALS['listePersAdmin'] ?? null) ? $GLOBALS['listePersAdmin'] : [];
$listeEnseignants = is_array($GLOBALS['listeEnseignants'] ?? null) ? $GLOBALS['listeEnseignants'] : [];
$listeGrades = is_array($GLOBALS['listeGrades'] ?? null) ? $GLOBALS['listeGrades'] : [];
$listeFonctions = is_array($GLOBALS['listeFonctions'] ?? null) ? $GLOBALS['listeFonctions'] : [];
$listeSpecialites = is_array($GLOBALS['listeSpecialites'] ?? null) ? $GLOBALS['listeSpecialites'] : [];

$persAdminEdit = $GLOBALS['pers_admin_a_modifier'] ?? null;
$enseignantEdit = $GLOBALS['enseignant_a_modifier'] ?? null;

$gradesOptions = [];
foreach ($listeGrades as $grade) {
    $id = (string) ($grade->id_grade ?? '');
    if ($id === '') {
        continue;
    }
    $gradesOptions[$id] = (string) ($grade->lib_grade ?? ('Grade ' . $id));
}

$fonctionsOptions = [];
foreach ($listeFonctions as $fonction) {
    $id = (string) ($fonction->id_fonction ?? '');
    if ($id === '') {
        continue;
    }
    $fonctionsOptions[$id] = (string) ($fonction->lib_fonction ?? ('Fonction ' . $id));
}

$specialitesOptions = [];
foreach ($listeSpecialites as $specialite) {
    $id = (string) ($specialite->id_specialite ?? '');
    if ($id === '') {
        continue;
    }
    $specialitesOptions[$id] = (string) ($specialite->lib_specialite ?? ('Specialite ' . $id));
}

$tabPersUrl = '?page=' . rawurlencode($pageSlug) . '&tab=pers_admin';
$tabEnsUrl = '?page=' . rawurlencode($pageSlug) . '&tab=enseignant';
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <?php
    if ($activeTab === 'enseignant') {
        cm_render_param_crud_view([
            'screen_class' => 'cm-prd3-crud-screen cm-prd6-admin-screen',
            'page_slug' => $pageSlug,
            'action' => 'edit',
            'extra_query' => ['tab' => 'enseignant'],
            'title' => '',
            'icon' => 'fa-user-tag',
            'form_title_add' => '',
            'form_title_edit' => '',
            'id_key' => 'id_enseignant',
            'id_field_name' => 'id_enseignant',
            'id_param' => 'id_enseignant',
            'list' => $listeEnseignants,
            'edit' => $enseignantEdit,
            'message_success' => $messageSuccess,
            'message_error' => $messageErreur,
            'search_fields' => ['nom_enseignant', 'prenom_enseignant', 'mail_enseignant', 'lib_grade', 'lib_specialite'],
            'add_button_name' => 'btn_add_enseignant',
            'edit_button_name' => 'btn_modifier_enseignant',
            'add_button_label' => 'Enregistrer',
            'edit_button_label' => 'Modifier',
            'form_fields' => [
                ['name' => 'matricule', 'label' => 'N° Matricule', 'type' => 'text', 'required' => true, 'value_key' => 'matricule_enseignant'],
                ['name' => 'nom', 'label' => 'Nom', 'type' => 'text', 'required' => true, 'value_key' => 'nom_enseignant'],
                ['name' => 'prenom', 'label' => 'Prénom', 'type' => 'text', 'required' => true, 'value_key' => 'prenom_enseignant'],
                ['name' => 'genre', 'label' => 'Genre', 'type' => 'text', 'required' => true, 'value_key' => 'genre', 'attrs' => ['size' => 1, 'maxlength' => 1]],
                ['name' => 'id_specialite', 'label' => 'Spécialité', 'type' => 'select-search', 'required' => true, 'options' => $specialitesOptions, 'value_key' => 'id_specialite'],
                ['name' => 'id_grade', 'label' => 'Grade', 'type' => 'select', 'required' => true, 'options' => $gradesOptions, 'value_key' => 'id_grade'],
                ['name' => 'date_occupation', 'label' => 'Date occupation poste', 'type' => 'date', 'required' => true, 'value_key' => 'date_occupation', 'attrs' => ['size' => 10, 'maxlength' => 10]],
                ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true, 'value_key' => 'mail_enseignant'],
                ['name' => 'telephone', 'label' => 'Téléphone', 'type' => 'text', 'required' => true, 'value_key' => 'tel_enseignant', 'attrs' => ['size' => 10, 'maxlength' => 10]],
                ['name' => 'id_fonction', 'label' => 'Fonction', 'type' => 'select', 'required' => true, 'options' => $fonctionsOptions, 'value_key' => 'id_fonction'],
                ['name' => 'date_fonction', 'label' => 'Date fonction', 'type' => 'date', 'required' => true, 'value_key' => 'date_fonction', 'attrs' => ['size' => 10, 'maxlength' => 10]],
                [
                    'name' => 'type_enseignant',
                    'label' => 'Type enseignant',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'Simple' => 'Simple',
                        'Administratif' => 'Administratif',
                    ],
                    'value_key' => 'type_enseignant',
                ],
            ],
            'columns' => [
                ['key' => 'matricule_enseignant', 'label' => 'N° Matricule'],
                [
                    'key' => 'nom_prenom',
                    'label' => 'Nom & Prénom',
                    'value' => static function ($row) {
                        $nom = is_object($row) ? (string) ($row->nom_enseignant ?? '') : (string) ($row['nom_enseignant'] ?? '');
                        $prenom = is_object($row) ? (string) ($row->prenom_enseignant ?? '') : (string) ($row['prenom_enseignant'] ?? '');
                        return htmlspecialchars(strtoupper($nom) . ' ' . $prenom, ENT_QUOTES, 'UTF-8');
                    },
                ],
                ['key' => 'lib_specialite', 'label' => 'Spécialité'],
                ['key' => 'lib_grade', 'label' => 'Grade'],
                ['key' => 'lib_fonction', 'label' => 'Fonction'],
                ['key' => 'mail_enseignant', 'label' => 'E-mail'],
            ],
        ]);
    } else {
        cm_render_param_crud_view([
            'screen_class' => 'cm-prd3-crud-screen cm-prd6-admin-screen',
            'page_slug' => $pageSlug,
            'action' => 'edit',
            'extra_query' => ['tab' => 'pers_admin'],
            'title' => '',
            'icon' => 'fa-users-cog',
            'form_title_add' => '',
            'form_title_edit' => '',
            'id_key' => 'id_pers_admin',
            'id_field_name' => 'id_pers_admin',
            'id_param' => 'id_pers_admin',
            'list' => $listePersAdmin,
            'edit' => $persAdminEdit,
            'message_success' => $messageSuccess,
            'message_error' => $messageErreur,
            'search_fields' => ['nom_pers_admin', 'prenom_pers_admin', 'email_pers_admin', 'poste'],
            'add_button_name' => 'btn_add_pers_admin',
            'edit_button_name' => 'btn_modifier_pers_admin',
            'add_button_label' => 'Enregistrer',
            'edit_button_label' => 'Modifier',
            'form_fields' => [
                ['name' => 'matricule', 'label' => 'N° Matricule', 'type' => 'text', 'required' => true, 'value_key' => 'matricule_pers_admin'],
                ['name' => 'nom', 'label' => 'Nom', 'type' => 'text', 'required' => true, 'value_key' => 'nom_pers_admin'],
                ['name' => 'prenom', 'label' => 'Prénom', 'type' => 'text', 'required' => true, 'value_key' => 'prenom_pers_admin'],
                ['name' => 'genre', 'label' => 'Genre', 'type' => 'text', 'required' => true, 'value_key' => 'genre', 'attrs' => ['size' => 1, 'maxlength' => 1]],
                ['name' => 'date_embauche', 'label' => "Date d'embauche", 'type' => 'date', 'required' => true, 'value_key' => 'date_embauche', 'attrs' => ['size' => 10, 'maxlength' => 10]],
                ['name' => 'poste', 'label' => 'Poste', 'type' => 'select', 'required' => true, 'options' => $fonctionsOptions, 'value_key' => 'poste'],
                ['name' => 'date_occupation', 'label' => 'Date occupation poste', 'type' => 'date', 'required' => true, 'value_key' => 'date_occupation', 'attrs' => ['size' => 10, 'maxlength' => 10]],
                ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true, 'value_key' => 'email_pers_admin'],
                ['name' => 'telephone', 'label' => 'Téléphone', 'type' => 'text', 'required' => true, 'value_key' => 'tel_pers_admin', 'attrs' => ['size' => 10, 'maxlength' => 10]],
            ],
            'columns' => [
                ['key' => 'matricule_pers_admin', 'label' => 'N° Matricule'],
                [
                    'key' => 'nom_prenom',
                    'label' => 'Nom & Prénom',
                    'value' => static function ($row) {
                        $nom = is_object($row) ? (string) ($row->nom_pers_admin ?? '') : (string) ($row['nom_pers_admin'] ?? '');
                        $prenom = is_object($row) ? (string) ($row->prenom_pers_admin ?? '') : (string) ($row['prenom_pers_admin'] ?? '');
                        return htmlspecialchars(strtoupper($nom) . ' ' . $prenom, ENT_QUOTES, 'UTF-8');
                    },
                ],
                ['key' => 'genre', 'label' => 'Genre'],
                ['key' => 'email_pers_admin', 'label' => 'E-mail'],
                ['key' => 'tel_pers_admin', 'label' => 'Téléphone'],
                ['key' => 'poste', 'label' => 'Poste'],
                ['key' => 'date_embauche', 'label' => "Date d'embauche"],
            ],
        ]);
    }
    ?>
</section>
