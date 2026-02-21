<?php
// Variables pour les vues paramètres et autres (présentes historiquement à la fin)
    $cardPGeneraux = [
        [
            'title' => 'Années Académiques',
            'description' => 'Gestion des périodes.',
            'link' => '?page=parametres_generaux&action=annees_academiques',
            'icon' => './images/date-du-calendrier.png'
        ],
        [
            'title' => 'Niveaux d\'Étude',
            'description' => 'L1, L2, M1, M2...',
            'link' => '?page=parametres_generaux&action=niveaux_etude',
            'icon' => './images/livre.png'
        ],
        [
            'title' => 'Semestres',
            'description' => 'S1, S2...',
            'link' => '?page=parametres_generaux&action=semestres',
            'icon' => './images/diplome.png'
        ],
        [
            'title' => 'Spécialités',
            'description' => 'Filières.',
            'link' => '?page=parametres_generaux&action=specialites',
            'icon' => './images/marche-de-niche.png'
        ],
        [
            'title' => 'Grades',
            'description' => 'Grades enseignants.',
            'link' => '?page=parametres_generaux&action=grades',
            'icon' => './images/diplome.png'
        ],
        [
            'title' => 'Fonctions Personnel',
            'description' => 'Rôles administratifs.',
            'link' => '?page=parametres_generaux&action=fonctions',
            'icon' => './images/valise.png'
        ],
        [
            'title' => 'Fonctions Utilisateurs',
            'description' => 'Groupes et types.',
            'link' => '?page=parametres_generaux&action=fonction_utilisateur&tab=groupes',
            'icon' => './images/equipe.png'
        ],
        [
            'title' => 'Niveaux d\'Accès',
            'description' => 'Lecture/Écriture.',
            'link' => '?page=parametres_generaux&action=niveaux_acces',
            'icon' => './images/check.png'
        ],
        [
            'title' => 'Niveaux d\'Approbation',
            'description' => 'Workflow.',
            'link' => '?page=parametres_generaux&action=niveaux_approbation',
            'icon' => './images/check.png'
        ],
        [
            'title' => 'Statuts du Jury',
            'description' => 'Rôles jury.',
            'link' => '?page=parametres_generaux&action=statut_jury',
            'icon' => './images/droit.png'
        ]
    ];

    $cardPSpecifiques = [
        [
            'title' => 'Unités d\'Enseignement (UE)',
            'description' => 'Gestion des matières.',
            'link' => '?page=parametres_specifiques&action=ue',
            'icon' => './images/livre-ouvert.png'
        ],
        [
            'title' => 'Éléments Constitutifs (ECUE)',
            'description' => 'Détail des cours.',
            'link' => '?page=parametres_specifiques&action=ecue',
            'icon' => './images/piece-de-puzzle.png'
        ],
        [
            'title' => 'Critères Évaluation',
            'description' => 'Barèmes de soutenance.',
            'link' => '?page=parametres_specifiques&action=criteres_evaluation',
            'icon' => 'fas fa-list-ol'
        ],
        [
            'title' => 'Salles',
            'description' => 'Lieux de soutenance.',
            'link' => '?page=parametres_specifiques&action=salles',
            'icon' => './images/door-open.png'
        ],
        [
            'title' => 'Entreprises',
            'description' => 'Partenaires de stage.',
            'link' => '?page=parametres_specifiques&action=entreprises',
            'icon' => './images/valise.png'
        ],
        [
            'title' => 'Gestion des Menus',
            'description' => 'Structure de navigation.',
            'link' => '?page=parametres_specifiques&action=gestion_menus',
            'icon' => './images/bd.png'
        ],
        [
            'title' => 'Habilitations (Attributions)',
            'description' => 'Droits par groupe.',
            'link' => '?page=parametres_specifiques&action=gestion_attribution',
            'icon' => './images/attribution.png'
        ],
        [
            'title' => 'Traitements',
            'description' => 'Actions techniques.',
            'link' => '?page=parametres_specifiques&action=traitements',
            'icon' => './images/bd.png'
        ],
        [
            'title' => 'Messages Système',
            'description' => 'Libellés d\'erreurs.',
            'link' => '?page=parametres_specifiques&action=messages',
            'icon' => './images/enveloppe.png'
        ]
    ];
    $cardReclamation = [
        [
            'title' => 'Soumettre une Réclamation',
            'description' => 'Déposez une nouvelle réclamation en remplissant le formulaire dédié.',
            'link' => '?page=gestion_reclamations&action=soumettre_reclamation',
            'icon' => 'fa-solid fa-circle-exclamation ',
            'title_link' => 'Soumettre',
            'bg_color' => 'bg-primary',
            'text_color' => 'text-white'
        ],
        [
            'title' => 'Suivi et historique des réclamations',
            'description' => 'Consultez l\'état actuel de vos réclamations en cours et accédez à l\'historique complet de vos réclamations passées.',
            'link' => '?page=gestion_reclamations&action=suivi_historique_reclamation',
            'icon' => 'fa-solid fa-eye ',
            'title_link' => 'Suivi et historique',
            'bg_color' => 'bg-primary-light',
            'text_color' => 'text-white'
        ]
    ];
?>
