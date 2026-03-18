<?php

declare(strict_types=1);

$groups = [
    'administrateur' => 5,
    'secretaire' => 6,
    'charge_communication' => 7,
    'responsable_scolarite' => 8,
    'responsable_filiere' => 9,
    'responsable_niveau' => 10,
    'commission' => 11,
    'enseignant' => 12,
    'etudiant' => 13,
];

$cap = static function (bool $view, bool $create = false, bool $edit = false, bool $delete = false): array {
    return [
        'voir' => $view,
        'creer' => $create,
        'modifier' => $edit,
        'supprimer' => $delete,
    ];
};

$none = $cap(false, false, false, false);
$view = $cap(true, false, false, false);
$viewEdit = $cap(true, false, true, false);
$createEdit = $cap(true, true, true, false);
$full = $cap(true, true, true, true);

$profileMatrix = [];
foreach ($groups as $groupId) {
    $profileMatrix[$groupId] = $viewEdit;
}

$categoryDefaults = [
    'ADMIN_PLATEFORME' => [
        $groups['administrateur'] => $full,
    ],
    'SCOLARITE' => [
        $groups['administrateur'] => $full,
        $groups['responsable_scolarite'] => $full,
    ],
    'ETUDIANT_ENV' => [
        $groups['administrateur'] => $full,
        $groups['etudiant'] => $view,
    ],
    'COMMISSION' => [
        $groups['administrateur'] => $full,
        $groups['commission'] => $full,
    ],
    'SOUTENANCE' => [
        $groups['administrateur'] => $full,
        $groups['commission'] => $full,
    ],
    'ENV_ENSEIGNANT' => [
        $groups['administrateur'] => $full,
        $groups['responsable_filiere'] => $view,
        $groups['responsable_niveau'] => $view,
        $groups['commission'] => $view,
        $groups['enseignant'] => $view,
    ],
    'PROFIL' => $profileMatrix,
];

$features = [];

$addFeature = static function (array $definition) use (&$features): void {
    $slug = (string) ($definition['slug'] ?? '');
    if ($slug === '') {
        return;
    }

    $definition['slug'] = $slug;
    $definition['code'] = (string) ($definition['code'] ?? strtoupper($slug));
    $definition['label'] = (string) ($definition['label'] ?? $slug);
    $definition['category_code'] = (string) ($definition['category_code'] ?? 'ADMIN_PLATEFORME');
    $definition['menu_url'] = (string) ($definition['menu_url'] ?? '');
    $definition['routes'] = array_values($definition['routes'] ?? []);
    $definition['existing_codes'] = array_values(array_unique(array_filter(array_merge(
        [$definition['code']],
        $definition['existing_codes'] ?? []
    ))));
    $definition['permissions'] = $definition['permissions'] ?? [];
    $features[$slug] = $definition;
};

$paramCrud = static function (
    string $slug,
    string $action,
    string $label,
    string $code,
    array $options = []
) use ($addFeature, $groups, $full, $viewEdit): void {
    $pageAliases = $options['page_aliases'] ?? ['parametres_generaux'];
    $routes = [];
    foreach ($pageAliases as $pageAlias) {
        $routes[] = [
            'pattern' => 'page=' . $pageAlias . '&action=' . $action,
            'method' => 'GET',
            'crud' => 'voir',
        ];
        $routes[] = [
            'pattern' => 'page=' . $pageAlias . '&action=' . $action,
            'method' => 'POST',
            'crud' => 'modifier',
        ];
    }

    $permissions = $options['permissions'] ?? [
        $groups['administrateur'] => $full,
    ];

    $addFeature([
        'slug' => $slug,
        'code' => $code,
        'label' => $label,
        'category_code' => (string) ($options['category_code'] ?? 'ADMIN_PLATEFORME'),
        'menu_url' => (string) ($options['menu_url'] ?? ('?page=' . $pageAliases[0] . '&action=' . $action)),
        'existing_codes' => $options['existing_codes'] ?? [],
        'routes' => $routes,
        'permissions' => $permissions,
    ]);
};

$addFeature([
    'slug' => 'dashboard',
    'code' => 'ADM_DASHBOARD',
    'label' => 'Tableau de bord administration',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=dashboard',
    'existing_codes' => ['ADM_DASHBOARD'],
    'routes' => [
        ['pattern' => 'page=dashboard', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=dashboard_admin', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $view,
    ],
]);

$addFeature([
    'slug' => 'dashboard_secretaire',
    'code' => 'DASH_SECRETAIRE',
    'label' => 'Tableau de bord secrétaire',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=dashboard_secretaire',
    'routes' => [
        ['pattern' => 'page=dashboard_secretaire', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $view,
        $groups['secretaire'] => $view,
    ],
]);

$addFeature([
    'slug' => 'dashboard_scolarite',
    'code' => 'DASH_SCOLARITE',
    'label' => 'Tableau de bord scolarité',
    'category_code' => 'SCOLARITE',
    'menu_url' => '?page=dashboard_scolarite',
    'existing_codes' => ['DASH_SCOLARITE'],
    'routes' => [
        ['pattern' => 'page=dashboard_scolarite', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'dashboard_commission',
    'code' => 'COM_DASHBOARD',
    'label' => 'Tableau de bord commission',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=dashboard_commission',
    'existing_codes' => ['COM_DASHBOARD'],
    'routes' => [
        ['pattern' => 'page=dashboard_commission', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'dashboard_enseignant',
    'code' => 'DASH_ENSEIGNANT',
    'label' => 'COM Espaces enseignant',
    'category_code' => 'ENV_ENSEIGNANT',
    'menu_url' => '?page=dashboard_enseignant',
    'existing_codes' => ['DASH_ENSEIGNANT'],
    'routes' => [
        ['pattern' => 'page=dashboard_enseignant', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'tableau_bord_enseignant',
    'code' => 'ENS_DASHBOARD',
    'label' => 'Tableau de bord enseignant',
    'category_code' => 'ENV_ENSEIGNANT',
    'menu_url' => '?page=tableau_bord_enseignant',
    'existing_codes' => ['ENS_DASHBOARD'],
    'routes' => [
        ['pattern' => 'page=tableau_bord_enseignant', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'profil',
    'code' => 'PROFIL',
    'label' => 'Profil utilisateur',
    'category_code' => 'PROFIL',
    'menu_url' => '?page=profil',
    'existing_codes' => ['PROFIL'],
    'routes' => [
        ['pattern' => 'page=profil', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=profil&tab=profile', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=profil&tab=password', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=profil&tab=history', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=profil&tab=profile', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=profil&tab=password', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=profil&action=update_email&tab=profile', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=profil&action=update_password&tab=password', 'method' => 'POST', 'crud' => 'modifier'],
    ],
    'permissions' => $profileMatrix,
]);

$addFeature([
    'slug' => 'gestion_utilisateurs',
    'code' => 'SYS_UTILISATEURS',
    'label' => 'Gestion des utilisateurs',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=gestion_utilisateurs',
    'existing_codes' => ['SYS_UTILISATEURS'],
    'routes' => [
        ['pattern' => 'page=gestion_utilisateurs', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_utilisateurs&action=add', 'method' => 'GET', 'crud' => 'creer'],
        ['pattern' => 'page=gestion_utilisateurs&action=edit', 'method' => 'GET', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_utilisateurs&action=btn_add_utilisateur', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=gestion_utilisateurs&action=btn_add_multiple', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=gestion_utilisateurs&action=btn_modifier_utilisateur', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_utilisateurs&action=submit_enable_multiple', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_utilisateurs&action=submit_disable_multiple', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_utilisateurs&action=submit_send_access', 'method' => 'POST', 'crud' => 'modifier'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
    ],
]);

$addFeature([
    'slug' => 'piste_audit',
    'code' => 'SYS_AUDIT',
    'label' => 'Journal audit',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=piste_audit',
    'existing_codes' => ['SYS_AUDIT'],
    'routes' => [
        ['pattern' => 'page=piste_audit', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=piste_audit&action=export_csv', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=piste_audit&action=nettoyer', 'method' => 'POST', 'crud' => 'supprimer'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
    ],
]);

$addFeature([
    'slug' => 'sauvegarde_restauration',
    'code' => 'SYS_BACKUP',
    'label' => 'Sauvegardes et restauration',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=sauvegarde_restauration',
    'existing_codes' => ['SYS_BACKUP'],
    'routes' => [
        ['pattern' => 'page=sauvegarde_restauration', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=sauvegarde_restauration&action=create', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=sauvegarde_restauration&action=restore', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=sauvegarde_restauration&action=delete', 'method' => 'POST', 'crud' => 'supprimer'],
        ['pattern' => 'page=sauvegarde_restauration&action=download', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
    ],
]);

$addFeature([
    'slug' => 'admin_historique',
    'code' => 'SYS_HISTORIQUE',
    'label' => 'Historique et archivage',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=admin_historique',
    'existing_codes' => ['SYS_HISTORIQUE'],
    'routes' => [
        ['pattern' => 'page=admin_historique', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=hub_historique', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=admin_historique&action=view_student', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=admin_historique&action=update_student', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=admin_historique&action=import', 'method' => 'GET', 'crud' => 'creer'],
        ['pattern' => 'page=admin_historique&action=import', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=admin_historique&action=import_result', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=admin_historique&action=export', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=admin_historique&action=changeYear', 'method' => 'GET', 'crud' => 'modifier'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['commission'] => $createEdit,
    ],
]);

$addFeature([
    'slug' => 'archive_comptes_rendus',
    'code' => 'ARCHIVES_CR',
    'label' => 'Archives comptes rendus',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=archive_comptes_rendus',
    'routes' => [
        ['pattern' => 'page=archive_comptes_rendus', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=archives_compte_rendu', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=archives_compte_rendu&action=view', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'archives_etudiants',
    'code' => 'ARCHIVES_ETUDIANTS',
    'label' => 'Archives étudiants',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=archives_etudiants',
    'routes' => [
        ['pattern' => 'page=archives_etudiants', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=archives_etudiants&action=exportCsv', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=fiche_etudiant_archive', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=parcours_etudiant', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'archives_soutenances',
    'code' => 'ARCHIVES_SOUTENANCES',
    'label' => 'Archives soutenances',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=archives_soutenances',
    'routes' => [
        ['pattern' => 'page=archives_soutenances', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=fiche_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=archives_jurys', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'archives_documents',
    'code' => 'ARCHIVES_DOCUMENTS',
    'label' => 'Archives documents',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=archives_documents',
    'routes' => [
        ['pattern' => 'page=archives_documents', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=visionneuse_document', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=telecharger_document', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'archives_candidatures',
    'code' => 'ARCHIVES_CANDIDATURES',
    'label' => 'Archives candidatures',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=archives_candidatures',
    'routes' => [
        ['pattern' => 'page=archives_candidatures', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'archives_reclamations',
    'code' => 'ARCHIVES_RECLAMATIONS',
    'label' => 'Archives réclamations',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=archives_reclamations',
    'routes' => [
        ['pattern' => 'page=archives_reclamations', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'repertoire_enseignant',
    'code' => 'repertoire_enseignant',
    'label' => 'Répertoire documents',
    'category_code' => 'ENV_ENSEIGNANT',
    'menu_url' => '?page=repertoire_enseignant',
    'existing_codes' => ['repertoire_enseignant'],
    'routes' => [
        ['pattern' => 'page=repertoire_enseignant', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'programmation_ens',
    'code' => 'ENV_ENSEIGNANT',
    'label' => 'Programmation enseignant',
    'category_code' => 'ENV_ENSEIGNANT',
    'menu_url' => '?page=programmation_ens',
    'existing_codes' => ['ENV_ENSEIGNANT'],
    'routes' => [
        ['pattern' => 'page=programmation_ens', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'gestion_rh',
    'code' => 'GESTION_RH',
    'label' => 'Gestion RH',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=gestion_rh',
    'routes' => [
        ['pattern' => 'page=gestion_rh', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_rh&action=btn_add_enseignant', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=gestion_rh&action=btn_modifier_enseignant', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_rh&action=btn_add_pers_admin', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=gestion_rh&action=btn_modifier_pers_admin', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_rh&action=submit_delete_multiple', 'method' => 'POST', 'crud' => 'supprimer'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['secretaire'] => $full,
    ],
]);

$addFeature([
    'slug' => 'maj_enseignant',
    'code' => 'MAJ_ENSEIGNANT',
    'label' => 'Mise à jour enseignant',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=maj_enseignant',
    'existing_codes' => ['MAJ_ENSEIGNANT'],
    'routes' => [
        ['pattern' => 'page=maj_enseignant', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=maj_enseignant&action=btn_add_enseignant', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=maj_enseignant&action=btn_modifier_enseignant', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=maj_enseignant&action=submit_delete_multiple', 'method' => 'POST', 'crud' => 'supprimer'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['secretaire'] => $full,
    ],
]);

$addFeature([
    'slug' => 'maj_personnel_admin',
    'code' => 'MAJ_PERSONNEL_ADMIN',
    'label' => 'Mise à jour personnel administratif',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=maj_personnel_admin',
    'existing_codes' => ['MAJ_PERSONNEL_ADMIN'],
    'routes' => [
        ['pattern' => 'page=maj_personnel_admin', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=maj_personnel_admin&action=btn_add_pers_admin', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=maj_personnel_admin&action=btn_modifier_pers_admin', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=maj_personnel_admin&action=submit_delete_multiple', 'method' => 'POST', 'crud' => 'supprimer'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['secretaire'] => $full,
    ],
]);

$addFeature([
    'slug' => 'gestion_etudiants',
    'code' => 'SCOLA_GEST_ETUDIANT',
    'label' => 'Gestion des étudiants',
    'category_code' => 'SCOLARITE',
    'menu_url' => '?page=gestion_etudiants',
    'existing_codes' => ['SCOLA_GEST_ETUDIANT', 'MAJ_ETUDIANT'],
    'routes' => [
        ['pattern' => 'page=gestion_etudiants', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_etudiants&action=ajouter_des_etudiants', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_etudiants&action=inscrire_des_etudiants', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_etudiants&modalAction=edit', 'method' => 'GET', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_etudiants&modalAction=imprimer_recu', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_etudiants&action=submit_add_etudiant', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=gestion_etudiants&action=submit_modifier_etudiant', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_etudiants&action=selected_ids', 'method' => 'POST', 'crud' => 'supprimer'],
    ],
]);

$addFeature([
    'slug' => 'gestion_scolarite',
    'code' => 'INSCRIPTION_ETUDIANT',
    'label' => 'Inscriptions étudiants',
    'category_code' => 'SCOLARITE',
    'menu_url' => '?page=gestion_scolarite',
    'existing_codes' => ['INSCRIPTION_ETUDIANT'],
    'routes' => [
        ['pattern' => 'page=gestion_scolarite', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_scolarite&action=mettre_a_jour_versement', 'method' => 'GET', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_scolarite&action=enregistrer_versement', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_scolarite&modalAction=imprimer_recu', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_scolarite&action=imprimer_recu', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'gestion_notes_evaluations',
    'code' => 'MOYENNE_ETUDIANT',
    'label' => 'Gestion des notes et évaluations',
    'category_code' => 'SCOLARITE',
    'menu_url' => '?page=gestion_notes_evaluations',
    'existing_codes' => ['MOYENNE_ETUDIANT'],
    'routes' => [
        ['pattern' => 'page=gestion_notes_evaluations', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_notes_evaluations&action=enregistrer_notes', 'method' => 'GET', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_notes_evaluations&action=btn_enregistrer_notes', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_notes_evaluations&action=imprimer_releve', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_notes', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'gestion_dossiers_candidatures',
    'code' => 'DOSSIER_CANDIDATURE',
    'label' => 'Gestion des dossiers de candidatures',
    'category_code' => 'SCOLARITE',
    'menu_url' => '?page=gestion_dossiers_candidatures',
    'existing_codes' => ['DOSSIER_CANDIDATURE'],
    'routes' => [
        ['pattern' => 'page=gestion_dossiers_candidatures', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_dossiers_candidatures', 'method' => 'POST', 'crud' => 'modifier'],
    ],
]);

$addFeature([
    'slug' => 'gestion_reclamations_scolarite',
    'code' => 'RECLAMATION_SCOLARITE',
    'label' => 'Réclamations scolarité',
    'category_code' => 'SCOLARITE',
    'menu_url' => '?page=gestion_reclamations_scolarite',
    'existing_codes' => ['RECLAMATION_ETUDIANT'],
    'routes' => [
        ['pattern' => 'page=gestion_reclamations_scolarite', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_reclamations_scolarite&action=repondre_reclamation', 'method' => 'POST', 'crud' => 'modifier'],
    ],
]);

$addFeature([
    'slug' => 'mise_en_ligne_memoire',
    'code' => 'MISE_EN_LIGNE_MEMOIRE',
    'label' => 'Mise en ligne mémoire',
    'category_code' => 'SCOLARITE',
    'menu_url' => '?page=mise_en_ligne_memoire',
    'existing_codes' => ['SCOLARITE'],
    'routes' => [
        ['pattern' => 'page=mise_en_ligne_memoire', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=mise_en_ligne_memoire', 'method' => 'POST', 'crud' => 'creer'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['responsable_scolarite'] => $full,
        $groups['charge_communication'] => $full,
    ],
]);

$addFeature([
    'slug' => 'candidature_soutenance',
    'code' => 'ETU_CANDIDATURE',
    'label' => 'Candidature soutenance',
    'category_code' => 'ETUDIANT_ENV',
    'menu_url' => '?page=candidature_soutenance',
    'existing_codes' => ['ETU_CANDIDATURE'],
    'routes' => [
        ['pattern' => 'page=candidature_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=candidature_soutenance', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=candidature_soutenance&action=compte_rendu_etudiant', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['etudiant'] => $createEdit,
    ],
]);

$addFeature([
    'slug' => 'gestion_reclamations',
    'code' => 'ETU_RECLAMATION',
    'label' => 'Gestion des réclamations étudiant',
    'category_code' => 'ETUDIANT_ENV',
    'menu_url' => '?page=gestion_reclamations',
    'existing_codes' => ['ETU_RECLAMATION'],
    'routes' => [
        ['pattern' => 'page=gestion_reclamations', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_reclamations&action=soumettre_reclamation', 'method' => 'GET', 'crud' => 'creer'],
        ['pattern' => 'page=gestion_reclamations&action=suivi_historique_reclamation', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_reclamations&action=traiter', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_reclamations&action=exporter_reclamations', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_reclamations&action=get_reclamation_details', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_reclamations', 'method' => 'POST', 'crud' => 'creer'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['etudiant'] => $createEdit,
    ],
]);

$addFeature([
    'slug' => 'consultation_cr_etud',
    'code' => 'ETU_CONSULTATION_CR',
    'label' => 'Consultation compte rendu étudiant',
    'category_code' => 'ETUDIANT_ENV',
    'menu_url' => '?page=consultation_cr_etud',
    'existing_codes' => ['ETU_CONSULTATION_CR'],
    'routes' => [
        ['pattern' => 'page=consultation_cr_etud', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['etudiant'] => $view,
    ],
]);

$addFeature([
    'slug' => 'gestion_rapports',
    'code' => 'ETUD_RAPPORT',
    'label' => 'Gestion des rapports',
    'category_code' => 'ETUDIANT_ENV',
    'menu_url' => '?page=gestion_rapports',
    'existing_codes' => ['ETUD_RAPPORT'],
    'routes' => [
        ['pattern' => 'page=gestion_rapports', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_rapports&action=creer_rapport', 'method' => 'GET', 'crud' => 'creer'],
        ['pattern' => 'page=gestion_rapports&action=suivi_rapport', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_rapports&action=commentaire_rapport', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_rapports&action=supprimer_rapport', 'method' => 'GET', 'crud' => 'supprimer'],
        ['pattern' => 'page=gestion_rapports&action=get_rapport', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_rapports&action=get_commentaires', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_rapports&action=exporter_rapports', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_rapports&action=save_rapport', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_rapports&action=deposer_rapport', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=gestion_rapports&action=export_pdf', 'method' => 'POST', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_rapports&action=supprimer_rapport', 'method' => 'POST', 'crud' => 'supprimer'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['etudiant'] => $full,
    ],
]);

$addFeature([
    'slug' => 'notes_resultats',
    'code' => 'NOTES_RESULTATS',
    'label' => 'Notes et résultats',
    'category_code' => 'ETUDIANT_ENV',
    'menu_url' => '?page=notes_resultats',
    'routes' => [
        ['pattern' => 'page=notes_resultats', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=notes_resultats&action=export_pdf', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['responsable_filiere'] => $view,
        $groups['responsable_niveau'] => $view,
        $groups['enseignant'] => $view,
        $groups['etudiant'] => $view,
    ],
]);

$addFeature([
    'slug' => 'reception_rapport_com',
    'code' => 'COM_RECEPTION_RAPPORT',
    'label' => 'Réception des rapports',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=reception_rapport_com',
    'existing_codes' => ['COM_RECEPTION_RAPPORT'],
    'routes' => [
        ['pattern' => 'page=rapport_a_valider', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=reception_rapport_com', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'gestion_candidatures_soutenance',
    'code' => 'GESTION_CANDIDATURES_SOUTENANCE',
    'label' => 'Gestion candidatures soutenance',
    'category_code' => 'SOUTENANCE',
    'menu_url' => '?page=gestion_candidatures',
    'routes' => [
        ['pattern' => 'page=gestion_candidatures', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=gestion_candidatures&action=examiner', 'method' => 'GET', 'crud' => 'modifier'],
    ],
]);

$addFeature([
    'slug' => 'verification_candidatures_soutenance',
    'code' => 'VERIFICATION_CANDIDATURES_SOUTENANCE',
    'label' => 'Vérification candidatures soutenance',
    'category_code' => 'SOUTENANCE',
    'menu_url' => '?page=verification_candidatures_soutenance',
    'routes' => [
        ['pattern' => 'page=verification_candidatures', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=verification_candidatures_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=verification_candidatures_soutenance&action=detail', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=verification_candidatures_soutenance&action=telecharger_pdf', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=verification_candidatures_soutenance&action=valider', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=verification_candidatures_soutenance&action=rejeter', 'method' => 'POST', 'crud' => 'modifier'],
    ],
]);

$addFeature([
    'slug' => 'evaluations_dossiers_soutenance',
    'code' => 'ANA_APP_RAPPORT',
    'label' => 'Évaluation des dossiers',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=evaluation_dossiers',
    'existing_codes' => ['ANA_APP_RAPPORT'],
    'routes' => [
        ['pattern' => 'page=evaluation_dossiers', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=evaluations_dossiers_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=evaluations_dossiers_soutenance&detail=1', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=evaluations_dossiers_soutenance&fichier=1', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=evaluations_dossiers_soutenance&action=traiter_decision', 'method' => 'GET', 'crud' => 'modifier'],
        ['pattern' => 'page=evaluations_dossiers_soutenance&action=traiter_decision', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=evaluations_dossiers_soutenance&action=valider_dossier', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=evaluations_dossiers_soutenance&action=rejeter_dossier', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=evaluations_dossiers_soutenance&action=finaliser_decision', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=evaluations_dossiers_soutenance&action=get_statistiques', 'method' => 'POST', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'processus_validation',
    'code' => 'SUIVI_VALIDATION_COM',
    'label' => 'Processus validation',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=processus_validation',
    'existing_codes' => ['SUIVI_VALIDATION_COM'],
    'routes' => [
        ['pattern' => 'page=processus_validation', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'redaction_compte_rendu',
    'code' => 'COM_REDACTION_CR',
    'label' => 'Rédaction compte rendu',
    'category_code' => 'COMMISSION',
    'menu_url' => '?page=redaction_compte_rendu',
    'existing_codes' => ['COM_REDACTION_CR', 'CR_HUB', 'CR_REDACTION', 'CR_BROUILLONS', 'CR_ARCHIVES'],
    'routes' => [
        ['pattern' => 'page=redaction_compte_rendu', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=redaction_compte_rendu&action=brouillons', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=redaction_compte_rendu&action=archives', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=redaction_compte_rendu&action=export_pdf', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=redaction_compte_rendu', 'method' => 'POST', 'crud' => 'modifier'],
    ],
]);

$addFeature([
    'slug' => 'programmation_soutenance',
    'code' => 'SOUT_COMPOS_JURY',
    'label' => 'Programmation soutenance',
    'category_code' => 'SOUTENANCE',
    'menu_url' => '?page=programmation_soutenance',
    'existing_codes' => ['SOUT_COMPOS_JURY'],
    'routes' => [
        ['pattern' => 'page=programmation_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programation_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programmation_soutenance&action=getEtudiants', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programmation_soutenance&action=getEnseignants', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programmation_soutenance&action=getProfesseursTitulaires', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programmation_soutenance&action=getSalles', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programmation_soutenance&action=getAttributions', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programmation_soutenance&action=createAttribution', 'method' => 'POST', 'crud' => 'creer'],
        ['pattern' => 'page=programmation_soutenance&action=updateAttribution', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=programmation_soutenance&action=deleteAttribution', 'method' => 'POST', 'crud' => 'supprimer'],
        ['pattern' => 'page=programmation_soutenance&action=getPlanningPreview', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programmation_soutenance&action=generatePlanningPdf', 'method' => 'POST', 'crud' => 'voir'],
        ['pattern' => 'page=programmation_soutenance&action=getDayDetails', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programmation_soutenance&action=downloadPlanningPdf', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programation_soutenance&action=getPlanningPreview', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programation_soutenance&action=generatePlanningPdf', 'method' => 'POST', 'crud' => 'voir'],
        ['pattern' => 'page=programation_soutenance&action=getDayDetails', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=programation_soutenance&action=downloadPlanningPdf', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'planification_soutenance',
    'code' => 'PLANIFICATION_SOUTENANCE',
    'label' => 'Planification soutenance',
    'category_code' => 'SOUTENANCE',
    'menu_url' => '?page=planification_soutenance',
    'routes' => [
        ['pattern' => 'page=plannification_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=planification_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=plannification_soutenance&action=planifierSoutenance', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=planification_soutenance&action=planifierSoutenance', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=plannification_soutenance&action=supprimerPlanification', 'method' => 'POST', 'crud' => 'supprimer'],
        ['pattern' => 'page=planification_soutenance&action=supprimerPlanification', 'method' => 'POST', 'crud' => 'supprimer'],
        ['pattern' => 'page=plannification_soutenance&action=getPlanification', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=planification_soutenance&action=getPlanification', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'evaluation_soutenance',
    'code' => 'SOUT_EVALUATION',
    'label' => 'Évaluation soutenance',
    'category_code' => 'SOUTENANCE',
    'menu_url' => '?page=evaluation_soutenance',
    'existing_codes' => ['SOUT_EVALUATION'],
    'routes' => [
        ['pattern' => 'page=evaluation_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=evaluation_soutenance&action=evaluerSoutenance', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=evaluation_soutenance&action=supprimerEvaluation', 'method' => 'POST', 'crud' => 'supprimer'],
        ['pattern' => 'page=evaluation_soutenance&action=getEvaluationExistante', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=evaluation_soutenance&action=getCriteresParAnnee', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=evaluation_soutenance&action=imprimer_pv', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'edition_bulletin',
    'code' => 'SOUT_EDITION_BULLETIN',
    'label' => 'Édition des bulletins',
    'category_code' => 'SOUTENANCE',
    'menu_url' => '?page=edition_bulletin',
    'existing_codes' => ['SOUT_EDITION_BULLETIN'],
    'routes' => [
        ['pattern' => 'page=edition_bulletin', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'dossiers_academiques',
    'code' => 'DOSSIERS_ACADEMIQUES',
    'label' => 'Dossiers académiques',
    'category_code' => 'SCOLARITE',
    'menu_url' => '?page=dossiers_academiques',
    'routes' => [
        ['pattern' => 'page=dossiers_academiques', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=dossiers_academiques&action=get_dossier', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=dossiers_academiques&action=enregistrer_dossier', 'method' => 'POST', 'crud' => 'modifier'],
    ],
]);

$addFeature([
    'slug' => 'criteres_evaluation',
    'code' => 'CRITERES_EVALUATION',
    'label' => 'Critères évaluation',
    'category_code' => 'SOUTENANCE',
    'menu_url' => '?page=criteres_evaluation',
    'routes' => [
        ['pattern' => 'page=criteres_evaluation', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=criteres_evaluation', 'method' => 'POST', 'crud' => 'modifier'],
        ['pattern' => 'page=test_criteres', 'method' => 'GET', 'crud' => 'voir'],
    ],
]);

$addFeature([
    'slug' => 'liste_etudiants_resp_filiere',
    'code' => 'LISTE_ETUDIANTS_RESP_FILIERE',
    'label' => 'Liste étudiants responsable filière',
    'category_code' => 'ENV_ENSEIGNANT',
    'menu_url' => '?page=liste_etudiants_resp_filiere',
    'routes' => [
        ['pattern' => 'page=liste_etudiants_resp_filiere', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=liste_etudiants_resp', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['responsable_filiere'] => $view,
    ],
]);

$addFeature([
    'slug' => 'liste_etudiants_resp_niveau',
    'code' => 'LISTE_ETUDIANTS_RESP_NIVEAU',
    'label' => 'Liste étudiants responsable niveau',
    'category_code' => 'ENV_ENSEIGNANT',
    'menu_url' => '?page=liste_etudiants_resp_niveau',
    'routes' => [
        ['pattern' => 'page=liste_etudiants_resp_niveau', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['responsable_niveau'] => $view,
    ],
]);

$addFeature([
    'slug' => 'liste_etudiants_enseignant',
    'code' => 'LISTE_ETUDIANTS_ENS',
    'label' => 'Liste étudiants enseignant',
    'category_code' => 'ENV_ENSEIGNANT',
    'menu_url' => '?page=liste_etudiants_ens',
    'routes' => [
        ['pattern' => 'page=liste_etudiants_ens', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['enseignant'] => $view,
    ],
]);

$addFeature([
    'slug' => 'archives_dossiers_soutenance',
    'code' => 'ARCHIVES_DOSSIERS_SOUTENANCE',
    'label' => 'Archives dossiers soutenance',
    'category_code' => 'SOUTENANCE',
    'menu_url' => '?page=archives_dossiers_soutenance',
    'routes' => [
        ['pattern' => 'page=archives_dossiers_soutenance', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=archives_dossiers_soutenance&action=details_rapport', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=archives_dossiers_soutenance&action=download_rapport', 'method' => 'GET', 'crud' => 'voir'],
        ['pattern' => 'page=archives_dossiers_soutenance&export=1', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
        $groups['commission'] => $view,
    ],
]);

$addFeature([
    'slug' => 'parametres_generaux',
    'code' => 'PARAM_HUB',
    'label' => 'Paramètres généraux',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=parametres_generaux',
    'existing_codes' => ['PARAM_HUB'],
    'routes' => [
        ['pattern' => 'page=parametres_generaux', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
    ],
]);

$addFeature([
    'slug' => 'parametres_specifiques',
    'code' => 'PARAM_SPEC',
    'label' => 'Paramètres spécifiques',
    'category_code' => 'ADMIN_PLATEFORME',
    'menu_url' => '?page=parametres_specifiques',
    'existing_codes' => ['PARAM_SPEC'],
    'routes' => [
        ['pattern' => 'page=parametres_specifiques', 'method' => 'GET', 'crud' => 'voir'],
    ],
    'permissions' => [
        $groups['administrateur'] => $full,
    ],
]);

$paramCrud('annees_academiques', 'annees_academiques', 'Années académiques', 'PARAM_ANNEES', [
    'existing_codes' => ['PARAM_ANNEES', 'ADMIN_ANNEE_ACADEMIQUE'],
]);
$paramCrud('app_settings', 'app_settings', 'App settings', 'PARAM_APP_SETTINGS');
$paramCrud('genre', 'genre', 'Genre', 'PARAM_GENRE');
$paramCrud('decisions_jury', 'decisions_jury', 'Décisions jury', 'PARAM_DECISIONS_JURY');
$paramCrud('etablissement_origine', 'etablissement_origine', 'Établissement origine', 'PARAM_ETABLISSEMENT_ORIGINE');
$paramCrud('session', 'session', 'Sessions', 'PARAM_SESSION');
$paramCrud('mode_paiement', 'mode_paiement', 'Modes de paiement', 'PARAM_MODE_PAIEMENT');
$paramCrud('statut_reclamation', 'statut_reclamation', 'Statuts réclamation', 'PARAM_STATUT_RECLAMATION');
$paramCrud('domaine', 'domaine', 'Domaines', 'PARAM_DOMAINE');
$paramCrud('mentions', 'mentions', 'Mentions', 'PARAM_MENTIONS');
$paramCrud('filieres', 'filieres', 'Filières', 'PARAM_FILIERES');
$paramCrud('grades', 'grades', 'Grades', 'PARAM_GRADES', [
    'existing_codes' => ['PARAM_GRADES'],
]);
$paramCrud('fonction_utilisateur', 'fonction_utilisateur', 'Fonctions utilisateurs', 'PARAM_FONC_USER', [
    'existing_codes' => ['PARAM_FONC_USER'],
]);
$paramCrud('specialites', 'specialites', 'Spécialités', 'PARAM_SPECIALITES', [
    'existing_codes' => ['PARAM_SPECIALITES'],
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
]);
$paramCrud('niveaux_etude', 'niveaux_etude', 'Niveaux étude', 'PARAM_NIV_ETUDE', [
    'existing_codes' => ['PARAM_NIV_ETUDE'],
]);
$paramCrud('ue', 'ue', 'Unités enseignement', 'PARAM_UE', [
    'existing_codes' => ['PARAM_UE'],
]);
$paramCrud('ecue', 'ecue', 'ECUE', 'PARAM_ECUE', [
    'existing_codes' => ['PARAM_ECUE'],
]);
$paramCrud('statut_jury', 'statut_jury', 'Statut jury', 'PARAM_STATUT_JURY', [
    'existing_codes' => ['PARAM_STATUT_JURY'],
]);
$paramCrud('niveaux_approbation', 'niveaux_approbation', 'Niveaux approbation', 'PARAM_NIV_APPRO', [
    'existing_codes' => ['PARAM_NIV_APPRO'],
]);
$paramCrud('semestres', 'semestres', 'Semestres', 'PARAM_SEMESTRES', [
    'existing_codes' => ['PARAM_SEMESTRES'],
]);
$paramCrud('niveaux_acces', 'niveaux_acces', 'Niveaux accès', 'PARAM_NIV_ACCES', [
    'existing_codes' => ['PARAM_NIV_ACCES'],
]);
$paramCrud('entreprises', 'entreprises', 'Entreprises', 'PARAM_ENTREPRISES', [
    'existing_codes' => ['PARAM_ENTREPRISES'],
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
]);
$paramCrud('actions', 'actions', 'Actions', 'PARAM_ACTIONS', [
    'existing_codes' => ['PARAM_ACTIONS'],
]);
$paramCrud('fonctions', 'fonctions', 'Fonctions', 'PARAM_FONCTIONS', [
    'existing_codes' => ['PARAM_FONCTIONS'],
]);
$paramCrud('messages', 'messages', 'Messages système', 'PARAM_MESSAGES', [
    'existing_codes' => ['PARAM_MESSAGES'],
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
]);
$paramCrud('gestion_attribution', 'gestion_attribution', 'Attribution permissions', 'PARAM_ATTRIB', [
    'existing_codes' => ['PARAM_ATTRIB'],
    'permissions' => [
        $groups['administrateur'] => $full,
    ],
]);
$paramCrud('gestion_menus', 'gestion_menus', 'Gestion menus', 'PARAM_GESTION_MENUS', [
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
    'permissions' => [
        $groups['administrateur'] => $full,
    ],
]);
$paramCrud('salles', 'salles', 'Salles', 'PARAM_SALLES', [
    'existing_codes' => ['PARAM_SALLES'],
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
]);
$paramCrud('bareme_critere', 'bareme_critere', 'Barème critère', 'PARAM_BAREME_CRITERE', [
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
]);
$paramCrud('qualite_jury', 'qualite_jury', 'Qualité jury', 'PARAM_QUALITE_JURY', [
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
]);
$paramCrud('maitre_stage', 'maitre_stage', 'Maître de stage', 'PARAM_MAITRE_STAGE', [
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
]);
$paramCrud('type_enseignant', 'type_enseignant', 'Type enseignant', 'PARAM_TYPE_ENSEIGNANT', [
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
]);
$paramCrud('schema_tables', 'schema_tables', 'Couverture tables/colonnes', 'PARAM_SCHEMA_TABLES', [
    'page_aliases' => ['parametres_generaux', 'parametres_specifiques'],
    'permissions' => [
        $groups['administrateur'] => $view,
    ],
]);

return [
    'version' => '2026-03-12',
    'groups' => $groups,
    'public_routes' => [
        ['pattern' => 'page=access_denied', 'method' => 'GET'],
        ['pattern' => 'page=page_connexion', 'method' => 'GET'],
        ['pattern' => 'page=reset_password', 'method' => 'GET'],
    ],
    'slug_aliases' => [
        'dashboard_admin' => 'dashboard',
        'hub_historique' => 'admin_historique',
        'evaluation_dossiers' => 'evaluations_dossiers_soutenance',
        'gestion_candidatures' => 'gestion_candidatures_soutenance',
        'verification_candidatures' => 'verification_candidatures_soutenance',
        'maj_etudiant' => 'gestion_etudiants',
        'programation_soutenance' => 'programmation_soutenance',
        'plannification_soutenance' => 'planification_soutenance',
        'archive_comptes_rendus' => 'archive_comptes_rendus',
        'archives_compte_rendu' => 'archive_comptes_rendus',
        'gestion_notes' => 'gestion_notes_evaluations',
        'reclamation_etudiant' => 'gestion_reclamations_scolarite',
        'repertoire_documents' => 'repertoire_enseignant',
    ],
    'category_defaults' => $categoryDefaults,
    'features' => $features,
];
