<?php

/**
 * Helper class for router URL generation
 */
class RouterHelper
{
    private static $router = null;

    /**
     * Initialize the router instance
     */
    public static function init($router)
    {
        self::$router = $router;
    }

    /**
     * Generate URL from route name
     * 
     * @param string $name Route name
     * @param array $params Route parameters
     * @return string Generated URL
     */
    public static function route($name, $params = [])
    {
        if (self::$router === null) {
            // Fallback to old-style URLs if router not initialized
            return self::fallbackUrl($name, $params);
        }

        try {
            return self::$router->generate($name, $params);
        } catch (Exception $e) {
            // Fallback if route not found
            return self::fallbackUrl($name, $params);
        }
    }

    /**
     * Fallback URL generation for backward compatibility
     */
    private static function fallbackUrl($name, $params = [])
    {
        // Map new route names to old query parameter format
        $routeMap = [
            // Dashboard routes
            'dashboard' => '?page=dashboard',
            'dashboard_enseignant' => '?page=dashboard_enseignant',
            'dashboard_secretaire' => '?page=dashboard_secretaire',
            'dashboard_scolarite' => '?page=dashboard_scolarite',
            'dashboard_commission' => '?page=dashboard_commission',
            
            // User management
            'gestion_utilisateurs' => '?page=gestion_utilisateurs',
            'profil' => '?page=profil',
            
            // RH
            'gestion_rh' => '?page=gestion_rh',
            
            // Scolarité
            'gestion_scolarite' => '?page=gestion_scolarite',
            
            // Notes
            'gestion_notes' => '?page=gestion_notes_evaluations',
            'notes_resultats' => '?page=notes_resultats',
            
            // Students
            'gestion_etudiants' => '?page=gestion_etudiants',
            'etudiants_ajouter' => '?page=gestion_etudiants&action=ajouter_des_etudiants',
            'etudiants_inscrire' => '?page=gestion_etudiants&action=inscrire_des_etudiants',
            'liste_etudiants' => '?page=liste_etudiants',
            
            // Candidatures
            'candidature_soutenance' => '?page=candidature_soutenance',
            'gestion_candidatures' => '?page=gestion_candidatures_soutenance',
            'gestion_dossiers_candidatures' => '?page=gestion_dossiers_candidatures',
            
            // Rapports
            'gestion_rapports' => '?page=gestion_rapports',
            'rapports_creer' => '?page=gestion_rapports&action=creer_rapport',
            'rapports_suivi' => '?page=gestion_rapports&action=suivi_rapport',
            'rapports_commentaire' => '?page=gestion_rapports&action=commentaire_rapport',
            
            // Réclamations
            'gestion_reclamations' => '?page=gestion_reclamations',
            'reclamations_soumettre' => '?page=gestion_reclamations&action=soumettre_reclamation',
            'reclamations_suivi' => '?page=gestion_reclamations&action=suivi_historique_reclamation',
            
            // Evaluation
            'evaluation_dossiers' => '?page=evaluations_dossiers_soutenance',
            'evaluation_soutenance' => '?page=evaluation_soutenance',
            
            // Dossier académique
            'dossier_academique' => '?page=dossiers_academiques',
            
            // Programmation
            'programmation_soutenance' => '?page=programmation_soutenance',
            'planification_soutenance' => '?page=planification_reunion',
            
            // Compte rendu
            'redaction_compte_rendu' => '?page=redaction_compte_rendu',
            'archives_compte_rendu' => '?page=archive_comptes_rendus',
            
            // Archives
            'archives_dossiers_soutenance' => '?page=archives_dossiers_soutenance',
            
            // Critères
            'criteres_evaluation' => '?page=criteres_evaluation',
            
            // Audit
            'audit' => '?page=audit',
            
            // Sauvegarde
            'sauvegarde_restauration' => '?page=sauvegarde_restauration',
            
            // Processus validation
            'processus_validation' => '?page=processus_validation',
            
            // Paramètres
            'parametres_generaux' => '?page=parametres_generaux',
            'parametres_annees' => '?page=parametres_generaux&action=annees_academiques',
            'parametres_grades' => '?page=parametres_generaux&action=grades',
            'parametres_fonction_utilisateur' => '?page=parametres_generaux&action=fonction_utilisateur',
            'parametres_specialites' => '?page=parametres_generaux&action=specialites',
            'parametres_niveaux_etude' => '?page=parametres_generaux&action=niveaux_etude',
            'parametres_ue' => '?page=parametres_generaux&action=ue',
            'parametres_ecue' => '?page=parametres_generaux&action=ecue',
            'parametres_statut_jury' => '?page=parametres_generaux&action=statut_jury',
            'parametres_niveaux_approbation' => '?page=parametres_generaux&action=niveaux_approbation',
            'parametres_semestres' => '?page=parametres_generaux&action=semestres',
            'parametres_niveaux_acces' => '?page=parametres_generaux&action=niveaux_acces',
            'parametres_traitements' => '?page=parametres_generaux&action=traitements',
            'parametres_entreprises' => '?page=parametres_generaux&action=entreprises',
            'parametres_actions' => '?page=parametres_generaux&action=actions',
            'parametres_fonctions' => '?page=parametres_generaux&action=fonctions',
            'parametres_messages' => '?page=parametres_generaux&action=messages',
            'parametres_attribution' => '?page=parametres_generaux&action=gestion_attribution',
            'parametres_salles' => '?page=parametres_generaux&action=salles',
        ];

        if (isset($routeMap[$name])) {
            return 'layout.php' . $routeMap[$name];
        }

        // Default fallback
        return '?page=' . str_replace('_', '-', $name);
    }

    /**
     * Map old page parameter to new route name
     * Used for backward compatibility during migration
     */
    public static function mapOldPageToRoute($page, $action = null)
    {
        $mapping = [
            'dashboard' => 'dashboard',
            'gestion_utilisateurs' => 'gestion_utilisateurs',
            'profil' => 'profil',
            'gestion_rh' => 'gestion_rh',
            'gestion_scolarite' => 'gestion_scolarite',
            'gestion_notes_evaluations' => 'gestion_notes',
            'notes_resultats' => 'notes_resultats',
            'gestion_etudiants' => 'gestion_etudiants',
            'liste_etudiants' => 'liste_etudiants',
            'candidature_soutenance' => 'candidature_soutenance',
            'gestion_candidatures_soutenance' => 'gestion_candidatures',
            'gestion_dossiers_candidatures' => 'gestion_dossiers_candidatures',
            'gestion_rapports' => 'gestion_rapports',
            'verification_rapports' => 'verification_rapports',
            'gestion_reclamations' => 'gestion_reclamations',
            'gestion_reclamations_scolarite' => 'gestion_reclamations_scolarite',
            'evaluations_dossiers_soutenance' => 'evaluation_dossiers',
            'evaluation_soutenance' => 'evaluation_soutenance',
            'dossiers_academiques' => 'dossier_academique',
            'programmation_soutenance' => 'programmation_soutenance',
            'planification_reunion' => 'planification_soutenance',
            'redaction_compte_rendu' => 'redaction_compte_rendu',
            'archive_comptes_rendus' => 'archives_compte_rendu',
            'archives_dossiers_soutenance' => 'archives_dossiers_soutenance',
            'criteres_evaluation' => 'criteres_evaluation',
            'audit' => 'audit',
            'sauvegarde_restauration' => 'sauvegarde_restauration',
            'processus_validation' => 'processus_validation',
            'parametres_generaux' => 'parametres_generaux',
        ];

        $routeName = $mapping[$page] ?? $page;

        // Handle actions for specific pages
        if ($action && $page === 'gestion_etudiants') {
            if ($action === 'ajouter_des_etudiants') return 'etudiants_ajouter';
            if ($action === 'inscrire_des_etudiants') return 'etudiants_inscrire';
        }

        if ($action && $page === 'gestion_rapports') {
            if ($action === 'creer_rapport') return 'rapports_creer';
            if ($action === 'suivi_rapport') return 'rapports_suivi';
            if ($action === 'commentaire_rapport') return 'rapports_commentaire';
        }

        if ($action && $page === 'gestion_reclamations') {
            if ($action === 'soumettre_reclamation') return 'reclamations_soumettre';
            if ($action === 'suivi_historique_reclamation') return 'reclamations_suivi';
        }

        if ($action && $page === 'parametres_generaux') {
            $actionRoutes = [
                'annees_academiques' => 'parametres_annees',
                'grades' => 'parametres_grades',
                'fonction_utilisateur' => 'parametres_fonction_utilisateur',
                'specialites' => 'parametres_specialites',
                'niveaux_etude' => 'parametres_niveaux_etude',
                'ue' => 'parametres_ue',
                'ecue' => 'parametres_ecue',
                'statut_jury' => 'parametres_statut_jury',
                'niveaux_approbation' => 'parametres_niveaux_approbation',
                'semestres' => 'parametres_semestres',
                'niveaux_acces' => 'parametres_niveaux_acces',
                'traitements' => 'parametres_traitements',
                'entreprises' => 'parametres_entreprises',
                'actions' => 'parametres_actions',
                'fonctions' => 'parametres_fonctions',
                'messages' => 'parametres_messages',
                'gestion_attribution' => 'parametres_attribution',
                'salles' => 'parametres_salles',
            ];
            return $actionRoutes[$action] ?? $routeName;
        }

        return $routeName;
    }
}
