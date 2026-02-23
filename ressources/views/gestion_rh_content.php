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
    <div class="cm-tab-links" role="tablist" aria-label="Referentiel RH">
        <a href="<?= htmlspecialchars($tabPersUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn <?= $activeTab === 'pers_admin' ? 'is-info' : 'is-light' ?>">
            <i class="fas fa-users-cog" aria-hidden="true"></i>
            <span>Personnel administratif</span>
        </a>
        <a href="<?= htmlspecialchars($tabEnsUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn <?= $activeTab === 'enseignant' ? 'is-info' : 'is-light' ?>">
            <i class="fas fa-user-tag" aria-hidden="true"></i>
            <span>Enseignants</span>
        </a>
    </div>

    <?php
    if ($activeTab === 'enseignant') {
        cm_render_param_crud_view([
            'screen_class' => 'cm-prd3-crud-screen cm-prd6-admin-screen',
            'page_slug' => $pageSlug,
            'action' => 'edit',
            'extra_query' => ['tab' => 'enseignant'],
            'title' => 'Mise a jour enseignant',
            'icon' => 'fa-user-tag',
            'form_title_add' => 'Ajout enseignant',
            'form_title_edit' => 'Modification enseignant',
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
                ['name' => 'nom', 'label' => 'Nom', 'type' => 'text', 'required' => true, 'value_key' => 'nom_enseignant'],
                ['name' => 'prenom', 'label' => 'Prenom', 'type' => 'text', 'required' => true, 'value_key' => 'prenom_enseignant'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'value_key' => 'mail_enseignant'],
                ['name' => 'id_specialite', 'label' => 'Specialite', 'type' => 'select', 'required' => true, 'options' => $specialitesOptions, 'value_key' => 'id_specialite'],
                ['name' => 'id_fonction', 'label' => 'Fonction', 'type' => 'select', 'required' => true, 'options' => $fonctionsOptions, 'value_key' => 'id_fonction'],
                ['name' => 'date_fonction', 'label' => 'Date occupation', 'type' => 'date', 'required' => true, 'value_key' => 'date_occupation'],
                ['name' => 'id_grade', 'label' => 'Grade', 'type' => 'select', 'required' => true, 'options' => $gradesOptions, 'value_key' => 'id_grade'],
                ['name' => 'date_grade', 'label' => 'Date obtention grade', 'type' => 'date', 'required' => true, 'value_key' => 'date_grade'],
                [
                    'name' => 'type_enseignant',
                    'label' => 'Type enseignant',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'Simple' => 'Simple',
                        'Administratif' => 'Administratif',
                        '1' => 'Simple',
                        '0' => 'Administratif',
                    ],
                    'value_key' => 'type_enseignant',
                ],
            ],
            'columns' => [
                ['key' => 'nom_enseignant', 'label' => 'Nom'],
                ['key' => 'prenom_enseignant', 'label' => 'Prenom'],
                ['key' => 'mail_enseignant', 'label' => 'Email'],
                ['key' => 'lib_specialite', 'label' => 'Specialite'],
                ['key' => 'lib_fonction', 'label' => 'Fonction'],
                ['key' => 'lib_grade', 'label' => 'Grade'],
                ['key' => 'date_grade', 'label' => 'Date grade'],
                ['key' => 'type_enseignant_fmt', 'label' => 'Type', 'value' => static function ($row): string {
                    $value = (string) ($row->type_enseignant ?? '');
                    if ($value === '1' || strcasecmp($value, 'simple') === 0) {
                        return 'Simple';
                    }
                    if ($value === '0' || strcasecmp($value, 'administratif') === 0) {
                        return 'Administratif';
                    }
                    return $value !== '' ? $value : '-';
                }],
            ],
        ]);
    } else {
        cm_render_param_crud_view([
            'screen_class' => 'cm-prd3-crud-screen cm-prd6-admin-screen',
            'page_slug' => $pageSlug,
            'action' => 'edit',
            'extra_query' => ['tab' => 'pers_admin'],
            'title' => 'Mise a jour personnel administratif',
            'icon' => 'fa-users-cog',
            'form_title_add' => 'Ajout personnel administratif',
            'form_title_edit' => 'Modification personnel administratif',
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
                ['name' => 'nom', 'label' => 'Nom', 'type' => 'text', 'required' => true, 'value_key' => 'nom_pers_admin'],
                ['name' => 'prenom', 'label' => 'Prenom', 'type' => 'text', 'required' => true, 'value_key' => 'prenom_pers_admin'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'value_key' => 'email_pers_admin'],
                ['name' => 'telephone', 'label' => 'Telephone', 'type' => 'text', 'required' => true, 'value_key' => 'tel_pers_admin'],
                ['name' => 'poste', 'label' => 'Poste', 'type' => 'text', 'required' => true, 'value_key' => 'poste'],
                ['name' => 'date_embauche', 'label' => 'Date embauche', 'type' => 'date', 'required' => true, 'value_key' => 'date_embauche'],
            ],
            'columns' => [
                ['key' => 'nom_pers_admin', 'label' => 'Nom'],
                ['key' => 'prenom_pers_admin', 'label' => 'Prenom'],
                ['key' => 'email_pers_admin', 'label' => 'Email'],
                ['key' => 'tel_pers_admin', 'label' => 'Telephone'],
                ['key' => 'poste', 'label' => 'Poste'],
                ['key' => 'date_embauche', 'label' => 'Date embauche'],
            ],
        ]);
    }
    ?>
</section>
