<?php
/**
 * TableHelper - Column registry and table utilities
 * Provides column configurations for all data tables across the system.
 */
class TableHelper
{
    private $configs = [
        'etudiant' => [
            ['key' => 'num_carte_etud', 'label' => 'Matricule', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_etu', 'label' => 'Nom', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'prenom_etu', 'label' => 'Prénoms', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'email_etu', 'label' => 'Email', 'sortable' => true, 'renderer' => 'text', 'hide_mobile' => true],
            ['key' => 'telephone_etu', 'label' => 'Téléphone', 'sortable' => false, 'renderer' => 'text', 'hide_mobile' => true],
            ['key' => 'lib_niv_etude', 'label' => 'Niveau', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'promotion_etu', 'label' => 'Promotion', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'libelle_genre', 'label' => 'Genre', 'sortable' => true, 'renderer' => 'text', 'hide_mobile' => true],
        ],
        'enseignant' => [
            ['key' => 'id_ens', 'label' => 'ID', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_ens', 'label' => 'Nom', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'prenom_ens', 'label' => 'Prénoms', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'email_ens', 'label' => 'Email', 'sortable' => true, 'renderer' => 'text', 'hide_mobile' => true],
            ['key' => 'telephone_ens', 'label' => 'Téléphone', 'sortable' => false, 'renderer' => 'text', 'hide_mobile' => true],
            ['key' => 'lib_specialite', 'label' => 'Spécialité', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'lib_type_ens', 'label' => 'Type', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'type_enseignant'],
        ],
        'utilisateur' => [
            ['key' => 'id_utilisateur', 'label' => 'ID', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_utilisateur', 'label' => 'Nom', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'lib_type_utilisateur', 'label' => 'Type', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'libelle_GU', 'label' => 'Groupe', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'statut_utilisateur', 'label' => 'Statut', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'utilisateur_status'],
            ['key' => 'login_utilisateur', 'label' => 'Login', 'sortable' => true, 'renderer' => 'text', 'hide_mobile' => true],
        ],
        'candidature' => [
            ['key' => 'id_candidature', 'label' => 'N° Candidature', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_etu', 'label' => 'Nom', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'prenom_etu', 'label' => 'Prénoms', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_candidature', 'label' => 'Date', 'sortable' => true, 'renderer' => 'date'],
            ['key' => 'statut_candidature', 'label' => 'Statut', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'candidature_status'],
            ['key' => 'commentaire_admin', 'label' => 'Commentaire', 'sortable' => false, 'renderer' => 'text', 'hide_mobile' => true],
            ['key' => 'date_traitement', 'label' => 'Date traitement', 'sortable' => true, 'renderer' => 'date', 'hide_mobile' => true],
        ],
        'inscription' => [
            ['key' => 'num_versement', 'label' => 'N° Versement', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_etu', 'label' => 'Étudiant', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_versement', 'label' => 'Date', 'sortable' => true, 'renderer' => 'date'],
            ['key' => 'montant_verser', 'label' => 'Montant', 'sortable' => true, 'renderer' => 'number'],
            ['key' => 'lib_mode_paiement', 'label' => 'Mode', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'solde', 'label' => 'Solde', 'sortable' => true, 'renderer' => 'number'],
        ],
        'note' => [
            ['key' => 'nom_etu', 'label' => 'Étudiant', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'lib_annee', 'label' => 'Année', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'moyenne_m1', 'label' => 'Moyenne M1', 'sortable' => true, 'renderer' => 'number', 'decimals' => 2],
            ['key' => 'moyenne_m2', 'label' => 'Moyenne M2', 'sortable' => true, 'renderer' => 'number', 'decimals' => 2],
            ['key' => 'date_creation', 'label' => 'Date création', 'sortable' => true, 'renderer' => 'datetime', 'hide_mobile' => true],
        ],
        'reclamation' => [
            ['key' => 'id_reclamation', 'label' => 'N° Réclamation', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_etu', 'label' => 'Étudiant', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'objet_reclamation', 'label' => 'Objet', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'lib_statut', 'label' => 'Statut', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'reclamation_status'],
            ['key' => 'date_creation', 'label' => 'Date création', 'sortable' => true, 'renderer' => 'date'],
            ['key' => 'date_mise_a_jour', 'label' => 'Dernière MAJ', 'sortable' => true, 'renderer' => 'datetime', 'hide_mobile' => true],
        ],
        'rapport' => [
            ['key' => 'id_rapport', 'label' => 'N°', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_etu', 'label' => 'Étudiant', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'theme_rapport', 'label' => 'Thème', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_redaction_rapport', 'label' => 'Date dépôt', 'sortable' => true, 'renderer' => 'date'],
            ['key' => 'statut_rapport', 'label' => 'Statut', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'rapport_status'],
            ['key' => 'version', 'label' => 'Version', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'taille_fichier', 'label' => 'Taille', 'sortable' => false, 'renderer' => 'text', 'hide_mobile' => true],
        ],
        'soutenance' => [
            ['key' => 'num_soutenance', 'label' => 'N° Soutenance', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_etu', 'label' => 'Étudiant', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'theme_soutenance', 'label' => 'Thème', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'lib_domaine', 'label' => 'Domaine', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'lib_session', 'label' => 'Session', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_soutenance', 'label' => 'Date', 'sortable' => true, 'renderer' => 'date'],
            ['key' => 'lib_salle', 'label' => 'Salle', 'sortable' => true, 'renderer' => 'text', 'hide_mobile' => true],
        ],
        'evaluation' => [
            ['key' => 'nom_etu', 'label' => 'Étudiant', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_evaluateur', 'label' => 'Évaluateur', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'decision', 'label' => 'Décision', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'evaluation_status'],
            ['key' => 'commentaire', 'label' => 'Commentaire', 'sortable' => false, 'renderer' => 'text', 'hide_mobile' => true],
            ['key' => 'date_evaluation', 'label' => 'Date', 'sortable' => true, 'renderer' => 'datetime'],
        ],
        'compte_rendu' => [
            ['key' => 'nom_CR', 'label' => 'Nom du CR', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_etu', 'label' => 'Étudiant', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_CR', 'label' => 'Date', 'sortable' => true, 'renderer' => 'date'],
            ['key' => 'statut', 'label' => 'Statut', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'cr_status'],
        ],
        'piste_audit' => [
            ['key' => 'id_piste', 'label' => 'ID', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_utilisateur', 'label' => 'Utilisateur', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'action', 'label' => 'Action', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'audit_action'],
            ['key' => 'statut_action', 'label' => 'Statut', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'audit_status'],
            ['key' => 'nom_table', 'label' => 'Table', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_creation', 'label' => 'Date/Heure', 'sortable' => true, 'renderer' => 'datetime'],
        ],
        'annee_academique' => [
            ['key' => 'id_annee_acad', 'label' => 'ID', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_deb', 'label' => 'Date début', 'sortable' => true, 'renderer' => 'date'],
            ['key' => 'date_fin', 'label' => 'Date fin', 'sortable' => true, 'renderer' => 'date'],
            ['key' => 'est_active', 'label' => 'Active', 'sortable' => true, 'renderer' => 'boolean'],
        ],
        'personnel_admin' => [
            ['key' => 'id_pers_admin', 'label' => 'ID', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_pers', 'label' => 'Nom', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'prenom_pers', 'label' => 'Prénoms', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'email_pers', 'label' => 'Email', 'sortable' => true, 'renderer' => 'text', 'hide_mobile' => true],
            ['key' => 'telephone_pers', 'label' => 'Téléphone', 'sortable' => false, 'renderer' => 'text', 'hide_mobile' => true],
            ['key' => 'poste_pers', 'label' => 'Poste', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_embauche', 'label' => 'Date embauche', 'sortable' => true, 'renderer' => 'date'],
        ],
        'backup' => [
            ['key' => 'nom_fichier', 'label' => 'Nom du backup', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_creation', 'label' => 'Date de création', 'sortable' => true, 'renderer' => 'datetime'],
            ['key' => 'taille', 'label' => 'Taille', 'sortable' => true, 'renderer' => 'text'],
        ],
    ];

    /**
     * Get columns for data-table
     */
    public function columnsConfig(string $entity): array
    {
        return $this->configs[$entity] ?? [];
    }

    /**
     * Check if entity exists
     */
    public function hasEntity(string $entity): bool
    {
        return isset($this->configs[$entity]);
    }

    /**
     * Returns pagination metadata
     */
    public function paginationData(int $total, int $page, int $perPage): array
    {
        $totalPages = (int)ceil($total / $perPage);
        $start = ($page - 1) * $perPage + 1;
        $end = min($page * $perPage, $total);

        return [
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'start' => $start,
            'end' => $end,
            'total' => $total,
            'perPage' => $perPage,
            'hasPrev' => $page > 1,
            'hasNext' => $page < $totalPages
        ];
    }

    /**
     * Returns edit, view, delete action configs
     */
    public function actionsConfig(string $entity, string $baseUrl): array
    {
        return [
            'view' => ['url' => $baseUrl . '&action=view&id=', 'icon' => 'fa-eye', 'label' => 'Voir', 'class' => 'is-info is-light'],
            'edit' => ['url' => $baseUrl . '&action=edit&id=', 'icon' => 'fa-edit', 'label' => 'Modifier', 'class' => 'is-primary is-light'],
            'delete' => ['url' => $baseUrl . '&action=delete&id=', 'icon' => 'fa-trash', 'label' => 'Supprimer', 'class' => 'is-light', 'confirm' => true]
        ];
    }

    /**
     * Badge color map for common statuses
     */
    public static function badgeColorMap(): array
    {
        return [
            // Generic positive
            'actif' => 'success', 'active' => 'success', 'validé' => 'success', 'validée' => 'success',
            'complet' => 'success', 'réussi' => 'success', 'valider' => 'success', 'publié' => 'success',
            'traité' => 'success', 'payée' => 'success', 'succès' => 'success',
            // Generic negative
            'inactif' => 'danger', 'inactive' => 'danger', 'rejeté' => 'danger', 'rejetée' => 'danger',
            'rejeter' => 'danger', 'incomplet' => 'danger', 'échoué' => 'danger', 'erreur' => 'danger',
            'acces_refuse' => 'danger',
            // Generic pending
            'en attente' => 'warning', 'en cours' => 'warning', 'pending' => 'warning',
            'en_attente' => 'warning', 'en_cours' => 'warning', 'soumise' => 'warning',
            'brouillon' => 'warning',
            // Generic info
            'nouveau' => 'info', 'info' => 'info',
            // Audit actions
            'connexion' => 'info', 'déconnexion' => 'light', 'création' => 'success',
            'modification' => 'warning', 'suppression' => 'danger', 'accès' => 'info',
        ];
    }
}
