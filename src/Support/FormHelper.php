<?php
/**
 * FormHelper - Registry of form fields by entity
 * Provides field configurations for all CRUD forms across the system.
 */
class FormHelper
{
    private $configs = [
        'etudiant' => [
            'num_carte_etud' => ['type' => 'text', 'label' => 'N° Carte étudiant', 'size' => 'standard', 'required' => true, 'icon' => 'fa-id-card'],
            'num_identification' => ['type' => 'text', 'label' => 'N° Identification', 'size' => 'standard', 'required' => true],
            'nom_etu' => ['type' => 'text', 'label' => 'Nom', 'size' => 'standard', 'required' => true, 'icon' => 'fa-user'],
            'prenom_etu' => ['type' => 'text', 'label' => 'Prénoms', 'size' => 'standard', 'required' => true],
            'date_naiss_etu' => ['type' => 'date', 'label' => 'Date de naissance', 'size' => 'standard', 'required' => true],
            'genre_etu' => [
                'type' => 'select',
                'label' => 'Genre',
                'size' => 'compresse',
                'required' => true,
                'options' => ['M' => 'Masculin', 'F' => 'Féminin']
            ],
            'email_etu' => ['type' => 'email', 'label' => 'Email', 'size' => 'standard', 'required' => true, 'icon' => 'fa-envelope'],
            'promotion_etu' => ['type' => 'text', 'label' => 'Promotion', 'size' => 'compresse', 'required' => true],
            'telephone_etu' => ['type' => 'text', 'label' => 'Téléphone', 'size' => 'standard', 'required' => false, 'icon' => 'fa-phone'],
            'id_niveau' => ['type' => 'select', 'label' => 'Niveau d\'étude', 'size' => 'standard', 'required' => true],
            'id_annee_acad' => ['type' => 'select', 'label' => 'Année Académique', 'size' => 'standard', 'required' => true],
        ],
        'enseignant' => [
            'id_ens' => ['type' => 'text', 'label' => 'ID Enseignant', 'size' => 'standard', 'required' => true, 'icon' => 'fa-id-badge'],
            'nom_ens' => ['type' => 'text', 'label' => 'Nom', 'size' => 'standard', 'required' => true, 'icon' => 'fa-user'],
            'prenom_ens' => ['type' => 'text', 'label' => 'Prénoms', 'size' => 'standard', 'required' => true],
            'email_ens' => ['type' => 'email', 'label' => 'Email', 'size' => 'standard', 'required' => false, 'icon' => 'fa-envelope'],
            'telephone_ens' => ['type' => 'text', 'label' => 'Téléphone', 'size' => 'standard', 'required' => false, 'icon' => 'fa-phone'],
            'id_specialite' => ['type' => 'select', 'label' => 'Spécialité', 'size' => 'standard', 'required' => false],
            'id_type_ens' => ['type' => 'select', 'label' => 'Type enseignant', 'size' => 'standard', 'required' => false],
            'id_etablissement' => ['type' => 'select', 'label' => 'Établissement d\'origine', 'size' => 'standard', 'required' => false],
        ],
        'utilisateur' => [
            'nom_utilisateur' => ['type' => 'text', 'label' => 'Nom utilisateur', 'size' => 'standard', 'required' => true, 'icon' => 'fa-user'],
            'id_type_utilisateur' => [
                'type' => 'select',
                'label' => 'Type utilisateur',
                'size' => 'standard',
                'required' => true
            ],
            'id_GU' => ['type' => 'select', 'label' => 'Groupe Utilisateur', 'size' => 'standard', 'required' => true],
            'id_niv_acces' => ['type' => 'select', 'label' => 'Niveau accès données', 'size' => 'standard', 'required' => true],
            'statut_utilisateur' => [
                'type' => 'select',
                'label' => 'Statut',
                'size' => 'compresse',
                'required' => true,
                'options' => ['Actif' => 'Actif', 'Inactif' => 'Inactif']
            ],
            'login_utilisateur' => ['type' => 'text', 'label' => 'Login', 'size' => 'standard', 'required' => true, 'icon' => 'fa-at'],
            'mot_de_passe' => ['type' => 'password', 'label' => 'Mot de passe', 'size' => 'standard', 'required' => true, 'icon' => 'fa-lock'],
        ],
        'inscription' => [
            'id_etudiant' => ['type' => 'select-search', 'label' => 'Étudiant', 'size' => 'etendue', 'required' => true],
            'id_annee_acad' => ['type' => 'select', 'label' => 'Année Académique', 'size' => 'standard', 'required' => true],
            'num_versement' => ['type' => 'text', 'label' => 'N° Versement', 'size' => 'compresse', 'required' => true],
            'date_versement' => ['type' => 'date', 'label' => 'Date versement', 'size' => 'standard', 'required' => true],
            'montant_verser' => ['type' => 'text', 'label' => 'Montant versé', 'size' => 'standard', 'required' => true, 'icon' => 'fa-money-bill-wave'],
            'id_mode_paiement' => [
                'type' => 'select',
                'label' => 'Mode de paiement',
                'size' => 'standard',
                'required' => true
            ],
            'num_piece' => ['type' => 'text', 'label' => 'N° Pièce', 'size' => 'standard', 'required' => true],
        ],
        'candidature' => [
            'num_etu' => ['type' => 'select-search', 'label' => 'Étudiant', 'size' => 'etendue', 'required' => true],
            'date_candidature' => ['type' => 'date', 'label' => 'Date de candidature', 'size' => 'standard', 'required' => true],
            'statut_candidature' => [
                'type' => 'select',
                'label' => 'Statut',
                'size' => 'standard',
                'required' => true,
                'options' => [
                    'En attente' => 'En attente',
                    'Validée' => 'Validée',
                    'Rejetée' => 'Rejetée'
                ]
            ],
            'commentaire_admin' => ['type' => 'textarea', 'label' => 'Commentaire administratif', 'size' => 'etendue', 'required' => false],
        ],
        'annee_academique' => [
            'date_deb' => ['type' => 'date', 'label' => 'Date de début', 'size' => 'standard', 'required' => true],
            'date_fin' => ['type' => 'date', 'label' => 'Date de fin', 'size' => 'standard', 'required' => true],
        ],
        'reclamation' => [
            'objet_reclamation' => ['type' => 'text', 'label' => 'Objet', 'size' => 'etendue', 'required' => true],
            'description_reclamation' => ['type' => 'textarea', 'label' => 'Description', 'size' => 'full', 'required' => true],
        ],
        'rapport' => [
            'theme_rapport' => ['type' => 'text', 'label' => 'Thème du rapport', 'size' => 'etendue', 'required' => true],
            'fichier_rapport' => ['type' => 'file', 'label' => 'Fichier', 'size' => 'standard', 'required' => true],
        ],
        'note' => [
            'id_etudiant' => ['type' => 'select-search', 'label' => 'Étudiant', 'size' => 'etendue', 'required' => true],
            'id_annee_acad' => ['type' => 'select', 'label' => 'Année Académique', 'size' => 'standard', 'required' => true],
            'moyenne_m1' => ['type' => 'text', 'label' => 'Moyenne M1', 'size' => 'compresse', 'required' => true, 'attrs' => ['min' => '0', 'max' => '20', 'step' => '0.01']],
            'moyenne_m2' => ['type' => 'text', 'label' => 'Moyenne M2', 'size' => 'compresse', 'required' => true, 'attrs' => ['min' => '0', 'max' => '20', 'step' => '0.01']],
        ],
        'compte_rendu' => [
            'nom_CR' => ['type' => 'text', 'label' => 'Nom du CR', 'size' => 'etendue', 'required' => true],
            'contenu_CR' => ['type' => 'rich-editor', 'label' => 'Contenu', 'size' => 'full', 'required' => true],
            'id_etudiant' => ['type' => 'select-search', 'label' => 'Étudiant concerné', 'size' => 'etendue', 'required' => true],
        ],
        'soutenance' => [
            'num_etu' => ['type' => 'select-search', 'label' => 'Étudiant', 'size' => 'etendue', 'required' => true],
            'theme_soutenance' => ['type' => 'text', 'label' => 'Thème', 'size' => 'etendue', 'required' => true],
            'id_domaine' => ['type' => 'select', 'label' => 'Domaine', 'size' => 'standard', 'required' => true],
            'id_session' => ['type' => 'select', 'label' => 'Session', 'size' => 'standard', 'required' => true],
            'id_salle' => ['type' => 'select', 'label' => 'Salle', 'size' => 'standard', 'required' => false],
            'date_soutenance' => ['type' => 'date', 'label' => 'Date soutenance', 'size' => 'standard', 'required' => false],
            'heure_soutenance' => ['type' => 'time', 'label' => 'Heure', 'size' => 'compresse', 'required' => false],
        ],
        'personnel_admin' => [
            'nom_pers' => ['type' => 'text', 'label' => 'Nom', 'size' => 'standard', 'required' => true, 'icon' => 'fa-user'],
            'prenom_pers' => ['type' => 'text', 'label' => 'Prénoms', 'size' => 'standard', 'required' => true],
            'email_pers' => ['type' => 'email', 'label' => 'Email', 'size' => 'standard', 'required' => true, 'icon' => 'fa-envelope'],
            'telephone_pers' => ['type' => 'text', 'label' => 'Téléphone', 'size' => 'standard', 'required' => true, 'icon' => 'fa-phone'],
            'poste_pers' => ['type' => 'text', 'label' => 'Poste', 'size' => 'standard', 'required' => true],
            'date_embauche' => ['type' => 'date', 'label' => 'Date d\'embauche', 'size' => 'standard', 'required' => true],
        ],
        'profil' => [
            'ancien_mdp' => ['type' => 'password', 'label' => 'Ancien mot de passe', 'size' => 'standard', 'required' => true, 'icon' => 'fa-lock'],
            'nouveau_mdp' => ['type' => 'password', 'label' => 'Nouveau mot de passe', 'size' => 'standard', 'required' => true, 'icon' => 'fa-key'],
            'confirmer_mdp' => ['type' => 'password', 'label' => 'Confirmer le mot de passe', 'size' => 'standard', 'required' => true, 'icon' => 'fa-key'],
        ],
    ];

    /**
     * Get single field config
     */
    public function fieldConfig(string $entity, string $field): array
    {
        return $this->configs[$entity][$field] ?? [];
    }

    /**
     * Get all fields for entity
     */
    public function getEntityFields(string $entity): array
    {
        return $this->configs[$entity] ?? [];
    }

    /**
     * Get select options
     */
    public function getOptions(string $entity, string $field): array
    {
        return $this->configs[$entity][$field]['options'] ?? [];
    }

    /**
     * Check if an entity is registered
     */
    public function hasEntity(string $entity): bool
    {
        return isset($this->configs[$entity]);
    }

    /**
     * Get all entity names
     */
    public function getEntities(): array
    {
        return array_keys($this->configs);
    }
}
