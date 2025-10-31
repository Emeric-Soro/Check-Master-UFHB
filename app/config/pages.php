<?php

/**
 * Page Configuration
 * Contains configuration data for special pages like parametres_generaux and gestion_reclamations
 */

return [
    'parametres_generaux' => [
        'cards' => [
            [
                'title' => 'Années Académiques',
                'description' => 'Gérer les années académiques, les dates de début et de fin.',
                'link' => '?page=parametres_generaux&action=annees_academiques',
                'icon' => './images/date-du-calendrier.png'
            ],
            [
                'title' => 'Gestion des Grades',
                'description' => 'Définir et administrer les différents grades académiques.',
                'link' => '?page=parametres_generaux&action=grades',
                'icon' => './images/diplome.png'
            ],
            [
                'title' => 'Fonctions Utilisateurs',
                'description' => 'Configurer les rôles et fonctions des utilisateurs du système.',
                'link' => '?page=parametres_generaux&action=fonction_utilisateur&tab=groupes',
                'icon' => './images/equipe.png'
            ],
            [
                'title' => 'Spécialités des enseignants',
                'description' => 'Administrer les spécialités et filières proposées.',
                'link' => '?page=parametres_generaux&action=specialites',
                'icon' => './images/marche-de-niche.png'
            ],
            [
                'title' => 'Niveaux d\'Étude',
                'description' => 'Gérer les différents niveaux d\'étude (Licence, Master, etc.).',
                'link' => '?page=parametres_generaux&action=niveaux_etude',
                'icon' => './images/livre.png'
            ],
            [
                'title' => 'Unités d\'Enseignement (UE)',
                'description' => 'Définir les unités d\'enseignement et leurs crédits.',
                'link' => '?page=parametres_generaux&action=ue',
                'icon' => './images/livre-ouvert.png'
            ],
            [
                'title' => 'Éléments Constitutifs (ECUE)',
                'description' => 'Gérer les éléments constitutifs des unités d\'enseignement.',
                'link' => '?page=parametres_generaux&action=ecue',
                'icon' => './images/piece-de-puzzle.png'
            ],
            [
                'title' => 'Statuts du Jury',
                'description' => 'Configurer les différents statuts possibles pour les membres du jury.',
                'link' => '?page=parametres_generaux&action=statut_jury',
                'icon' => './images/droit.png'
            ],
            [
                'title' => 'Niveaux d\'Approbation',
                'description' => 'Définir les circuits et niveaux d\'approbation pour les documents.',
                'link' => '?page=parametres_generaux&action=niveaux_approbation',
                'icon' => './images/check.png'
            ],
            [
                'title' => 'Semestres',
                'description' => 'Définir les différents semestres et UE associées.',
                'link' => '?page=parametres_generaux&action=semestres',
                'icon' => './images/diplome.png'
            ],
            [
                'title' => 'Niveaux d\'Accès',
                'description' => 'Définir les différents niveaux d\'accès pour les utilisateurs',
                'link' => '?page=parametres_generaux&action=niveaux_acces',
                'icon' => './images/check.png'
            ],
            [
                'title' => 'Traitements',
                'description' => 'Définir les traitements à affecter aux différents utilisateurs.',
                'link' => '?page=parametres_generaux&action=traitements',
                'icon' => './images/bd.png'
            ],
            [
                'title' => 'Entreprises',
                'description' => 'Gérer les entreprises partenaires et leurs informations.',
                'link' => '?page=parametres_generaux&action=entreprises',
                'icon' => './images/valise.png'
            ],
            [
                'title' => 'Actions',
                'description' => 'Définir les actions possibles pour les utilisateurs dans le système.',
                'link' => '?page=parametres_generaux&action=actions',
                'icon' => './images/cible.png'
            ],
            [
                'title' => 'Fonctions',
                'description' => 'Définir les fonctions exercées par les enseignants dans le système.',
                'link' => '?page=parametres_generaux&action=fonctions',
                'icon' => './images/valise.png'
            ],
            [
                'title' => 'Messagerie',
                'description' => 'Définition des messages d\'erreur à afficher dans le système.',
                'link' => '?page=parametres_generaux&action=messages',
                'icon' => './images/enveloppe.png'
            ],
            [
                'title' => 'Gestion des Attributions',
                'description' => 'Gérer les attributions de traitement pour chacun des groupes utilisateurs dans le système.',
                'link' => '?page=parametres_generaux&action=gestion_attribution',
                'icon' => './images/attribution.png'
            ],
        ],
        'allowed_actions' => [
            'annees_academiques', 'grades', 'fonctions', 'fonction_utilisateur',
            'specialites', 'niveaux_etude', 'ue', 'ecue', 'statut_jury',
            'niveaux_approbation', 'semestres', 'niveaux_acces', 'traitements',
            'entreprises', 'actions', 'fonctions_enseignants', 'messages',
            'gestion_attribution',
        ]
    ],

    'gestion_reclamations' => [
        'cards' => [
            [
                'title' => 'Soumettre une Réclamation',
                'description' => 'Déposez une nouvelle réclamation en remplissant le formulaire dédié.',
                'link' => '?page=gestion_reclamations&action=soumettre_reclamation',
                'icon' => 'fa-solid fa-circle-exclamation',
                'title_link' => 'Soumettre',
                'bg_color' => 'bg-accent-lighter',
                'text_color' => 'text-accent'
            ],
            [
                'title' => 'Suivi et historique des réclamations',
                'description' => 'Consultez l\'état actuel de vos réclamations en cours et accédez à l\'historique complet de vos réclamations passées.',
                'link' => '?page=gestion_reclamations&action=suivi_historique_reclamation',
                'icon' => 'fa-solid fa-eye',
                'title_link' => 'Suivi et historique',
                'bg_color' => 'bg-warning/20',
                'text_color' => 'text-warning'
            ]
        ],
        'allowed_actions' => ['soumettre_reclamation', 'suivi_historique_reclamation'],
        'ajax_actions' => ['get_reclamation_details']
    ],

    'gestion_rapports' => [
        'allowed_actions' => ['creer_rapport', 'suivi_rapport', 'commentaire_rapport'],
        'ajax_actions' => ['get_commentaires', 'get_rapport']
    ],

    'candidature_soutenance' => [
        'allowed_actions' => ['compte_rendu_etudiant']
    ],

    'gestion_etudiants' => [
        'allowed_actions' => ['ajouter_des_etudiants', 'inscrire_des_etudiants']
    ],
];
