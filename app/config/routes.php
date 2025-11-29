<?php
/**
 * Configuration centralisée des routes
 * 
 * Ce fichier définit toutes les routes de l'application en utilisant AltoRouter
 * Format: $router->map(METHOD, ROUTE, TARGET, NAME)
 */

// Routes d'authentification
$router->map('GET', '/', 'AuthController#login', 'login');
$router->map('POST', '/', 'AuthController#login', 'login_post');
$router->map('GET', '/logout', 'AuthController#logout', 'logout');

// Routes de dashboard
$router->map('GET', '/dashboard', 'DashboardController#index', 'dashboard');
$router->map('GET', '/dashboard/enseignant', 'DashboardEnseignantController#index', 'dashboard_enseignant');
$router->map('GET', '/dashboard/secretaire', 'DashboardSecretaireController#index', 'dashboard_secretaire');
$router->map('GET', '/dashboard/scolarite', 'DashboardScolariteController#index', 'dashboard_scolarite');
$router->map('GET', '/dashboard/commission', 'DashboardCommissionController#index', 'dashboard_commission');

// Routes de gestion des utilisateurs
$router->map('GET', '/utilisateurs', 'GestionUtilisateurController#index', 'gestion_utilisateurs');
$router->map('POST', '/utilisateurs', 'GestionUtilisateurController#index', 'gestion_utilisateurs_post');
$router->map('GET', '/profil', 'GestionUtilisateurController#index', 'profil');
$router->map('GET', '/admin/utilisateurs', 'GestionUtilisateurController#index', 'admin_utilisateurs');
$router->map('POST', '/admin/utilisateurs', 'GestionUtilisateurController#index', 'admin_utilisateurs_post');

// Routes de gestion RH
$router->map('GET', '/rh', 'GestionRhController#index', 'gestion_rh');

// Routes de gestion de la scolarité
$router->map('GET', '/scolarite', 'GestionScolariteController#index', 'gestion_scolarite');
$router->map('GET', '/scolarite/versement/ajouter', 'GestionScolariteController#index', 'scolarite_versement_ajouter');
$router->map('GET', '/scolarite/imprimer-recu/[a:id]', 'GestionScolariteController#imprimerRecu', 'scolarite_imprimer_recu');

// Routes de gestion des notes
$router->map('GET', '/notes', 'NotesController#index', 'gestion_notes');
$router->map('GET', '/notes/saisie', 'NotesController#index', 'notes_saisie');
$router->map('GET', '/notes-resultats', 'NotesResultatsController#index', 'notes_resultats');
$router->map('GET', '/bulletin/[a:id]', 'NotesController#imprimerReleve', 'notes_imprimer_releve');

// Routes de gestion des étudiants
$router->map('GET', '/etudiants', 'GestionEtudiantController#index', 'gestion_etudiants');
$router->map('GET', '/etudiants/ajouter', 'GestionEtudiantController#index', 'etudiants_ajouter');
$router->map('GET', '/etudiants/inscrire', 'InscriptionController#index', 'etudiants_inscrire');
$router->map('GET', '/etudiants/recu/[a:id]', 'GestionEtudiantController#imprimerRecu', 'etudiants_imprimer_recu');
$router->map('GET', '/etudiants/liste', 'GestionEtudiantController#liste', 'liste_etudiants');

// Routes de candidature à la soutenance
$router->map('GET', '/candidature-soutenance', 'CandidatureSoutenanceController#index', 'candidature_soutenance');
$router->map('GET', '/candidature-soutenance/compte-rendu', 'CandidatureSoutenanceController#compteRendu', 'candidature_compte_rendu');

// Routes de gestion des candidatures
$router->map('GET', '/gestion-candidatures', 'GestionCandidaturesController#index', 'gestion_candidatures');
$router->map('GET', '/gestion-candidatures/examiner/[a:id]', 'GestionCandidaturesController#examiner', 'gestion_candidatures_examiner');

// Routes de gestion des dossiers de candidatures
$router->map('GET', '/dossiers-candidatures', 'GestionDossiersCandidaturesController#index', 'gestion_dossiers_candidatures');

// Routes de gestion des rapports
$router->map('GET', '/rapports', 'GestionRapportController#index', 'gestion_rapports');
$router->map('GET', '/rapports/creer', 'GestionRapportController#creerRapport', 'rapports_creer');
$router->map('POST', '/rapports/creer', 'GestionRapportController#traiterCreationRapport', 'rapports_creer_post');
$router->map('GET', '/rapports/suivi', 'GestionRapportController#suiviRapport', 'rapports_suivi');
$router->map('GET', '/rapports/commentaire', 'GestionRapportController#commentaireRapport', 'rapports_commentaire');
$router->map('POST', '/rapports/supprimer', 'GestionRapportController#supprimer_rapport', 'rapports_supprimer');
$router->map('GET', '/rapports/ajax/get', 'GestionRapportController#getRapportAjax', 'rapports_ajax_get');
$router->map('GET', '/rapports/ajax/commentaires', 'GestionRapportController#getCommentairesAjax', 'rapports_ajax_commentaires');
$router->map('GET', '/rapports/exporter', 'GestionRapportController#exporterRapports', 'rapports_exporter');

// Routes de vérification des rapports
$router->map('GET', '/verification-rapports', 'VerificationRapportsController#index', 'verification_rapports');

// Routes de gestion des réclamations
$router->map('GET', '/reclamations', 'GestionReclamationsController#index', 'gestion_reclamations');
$router->map('GET', '/reclamations/soumettre', 'GestionReclamationsController#soumettreReclamations', 'reclamations_soumettre');
$router->map('GET', '/reclamations/suivi', 'GestionReclamationsController#suiviHistoriqueReclamations', 'reclamations_suivi');
$router->map('POST', '/reclamations/traiter', 'GestionReclamationsController#traiterReclamation', 'reclamations_traiter');
$router->map('GET', '/reclamations/exporter', 'GestionReclamationsController#exporterReclamations', 'reclamations_exporter');
$router->map('GET', '/reclamations/ajax/details', 'GestionReclamationsController#getReclamationDetailsAjax', 'reclamations_ajax_details');

// Routes de réclamations scolarité
$router->map('GET', '/reclamations-scolarite', 'GestionReclamationsScolariteController#index', 'gestion_reclamations_scolarite');

// Routes d'évaluation des dossiers
$router->map('GET', '/evaluation-dossiers', 'EvaluationDossiersController#index', 'evaluation_dossiers');

// Routes d'évaluation des soutenances
$router->map('GET', '/soutenances/planning', 'ProgrammationSoutenanceController#index', 'programmation_soutenance');
$router->map('GET', '/soutenances/evaluation', 'EvaluationSoutenanceController#index', 'evaluation_soutenance');
$router->map('GET', '/soutenances/pv/[a:id]', 'EvaluationSoutenanceController#imprimerPV', 'evaluation_soutenance_imprimer_pv');

// Routes de dossier académique
$router->map('GET', '/dossier-academique', 'DossierAcademiqueController#index', 'dossier_academique');

// Routes de programmation de soutenance
$router->map('GET', '/programmation-soutenance', 'ProgrammationSoutenanceController#index', 'programmation_soutenance');
$router->map('GET', '/programmation-soutenance/ajax/etudiants', 'ProgrammationSoutenanceController#getEtudiants', 'programmation_ajax_etudiants');
$router->map('GET', '/programmation-soutenance/ajax/enseignants', 'ProgrammationSoutenanceController#getEnseignants', 'programmation_ajax_enseignants');
$router->map('GET', '/programmation-soutenance/ajax/professeurs-titulaires', 'ProgrammationSoutenanceController#getProfesseursTitulaires', 'programmation_ajax_professeurs');
$router->map('GET', '/programmation-soutenance/ajax/attributions', 'ProgrammationSoutenanceController#getAttributions', 'programmation_ajax_attributions');
$router->map('POST', '/programmation-soutenance/attribution/creer', 'ProgrammationSoutenanceController#createAttribution', 'programmation_attribution_creer');
$router->map('POST', '/programmation-soutenance/attribution/modifier', 'ProgrammationSoutenanceController#updateAttribution', 'programmation_attribution_modifier');
$router->map('POST', '/programmation-soutenance/attribution/supprimer', 'ProgrammationSoutenanceController#deleteAttribution', 'programmation_attribution_supprimer');

// Routes de planification de soutenance
$router->map('GET', '/planification-soutenance', 'PlanificationSoutenanceController#index', 'planification_soutenance');

// Routes de rédaction de compte rendu
$router->map('GET', '/compte-rendu/redaction', 'RedactionCompteRenduController#index', 'redaction_compte_rendu');
$router->map('POST', '/compte-rendu/redaction', 'RedactionCompteRenduController#enregistrer', 'redaction_compte_rendu_post');
$router->map('POST', '/compte-rendu/exporter-pdf', 'RedactionCompteRenduController#exporterPDF', 'redaction_compte_rendu_exporter');

// Routes des archives de comptes rendus
$router->map('GET', '/compte-rendu/archives', 'ArchivesCompteRenduController#index', 'archives_compte_rendu');
$router->map('GET', '/compte-rendu/archives/telecharger/[a:id]', 'ArchivesCompteRenduController#telecharger', 'archives_compte_rendu_telecharger');
$router->map('GET', '/compte-rendu/archives/view/[a:id]', 'ArchivesCompteRenduController#viewArchive', 'archives_compte_rendu_view');
$router->map('POST', '/compte-rendu/archives/delete', 'ArchivesCompteRenduController#deleteArchive', 'archives_compte_rendu_delete');
$router->map('GET', '/compte-rendu/archives/export', 'ArchivesCompteRenduController#exportArchives', 'archives_compte_rendu_export');
$router->map('GET', '/compte-rendu/archives/search', 'ArchivesCompteRenduController#searchArchives', 'archives_compte_rendu_search');

// Routes des archives de dossiers de soutenance
$router->map('GET', '/archives-dossiers-soutenance', 'ArchivesDossiersSoutenanceController#index', 'archives_dossiers_soutenance');

// Routes de critères d'évaluation
$router->map('GET', '/criteres-evaluation', 'CriteresEvaluationController#index', 'criteres_evaluation');

// Routes d'audit
$router->map('GET', '/audit', 'AuditController#index', 'audit');

// Routes de sauvegarde et restauration
$router->map('GET', '/sauvegarde-restauration', 'SauvegardeRestaurationController#index', 'sauvegarde_restauration');
$router->map('POST', '/sauvegarde-restauration/sauvegarder', 'SauvegardeRestaurationController#effectuerSauvegarde', 'sauvegarde_effectuer');
$router->map('POST', '/sauvegarde-restauration/restaurer', 'SauvegardeRestaurationController#restaurer', 'sauvegarde_restaurer');

// Routes de processus de validation
$router->map('GET', '/processus-validation', 'ProcessusValidationController#index', 'processus_validation');

// Routes des paramètres généraux
$router->map('GET', '/parametres', 'ParametreController#index', 'parametres_generaux');
$router->map('GET', '/parametres/annees-academiques', 'ParametreController#gestionAnnees', 'parametres_annees');
$router->map('POST', '/parametres/annees-academiques', 'ParametreController#gestionAnnees', 'parametres_annees_post');
$router->map('GET', '/admin/annees', 'ParametreController#gestionAnnees', 'admin_annees');
$router->map('POST', '/admin/annees', 'ParametreController#gestionAnnees', 'admin_annees_post');
$router->map('GET', '/parametres/grades', 'ParametreController#gestionGrade', 'parametres_grades');
$router->map('GET', '/parametres/fonction-utilisateur', 'ParametreController#gestionFonctionUtilisateur', 'parametres_fonction_utilisateur');
$router->map('GET', '/parametres/specialites', 'ParametreController#gestionSpecialite', 'parametres_specialites');
$router->map('GET', '/parametres/niveaux-etude', 'ParametreController#gestionNiveauEtude', 'parametres_niveaux_etude');
$router->map('GET', '/parametres/ue', 'ParametreController#gestionUe', 'parametres_ue');
$router->map('POST', '/parametres/ue', 'ParametreController#gestionUe', 'parametres_ue_post');
$router->map('GET', '/admin/ue', 'ParametreController#gestionUe', 'admin_ue');
$router->map('POST', '/admin/ue', 'ParametreController#gestionUe', 'admin_ue_post');
$router->map('GET', '/parametres/ecue', 'ParametreController#gestionEcue', 'parametres_ecue');
$router->map('GET', '/parametres/statut-jury', 'ParametreController#gestionStatutJury', 'parametres_statut_jury');
$router->map('GET', '/parametres/niveaux-approbation', 'ParametreController#gestionNiveauApprobation', 'parametres_niveaux_approbation');
$router->map('GET', '/parametres/semestres', 'ParametreController#gestionSemestre', 'parametres_semestres');
$router->map('GET', '/parametres/niveaux-acces', 'ParametreController#gestionNiveauAccesDonnees', 'parametres_niveaux_acces');
$router->map('GET', '/parametres/traitements', 'ParametreController#gestionTraitement', 'parametres_traitements');
$router->map('GET', '/parametres/entreprises', 'ParametreController#gestionEntreprise', 'parametres_entreprises');
$router->map('GET', '/parametres/actions', 'ParametreController#gestionAction', 'parametres_actions');
$router->map('GET', '/parametres/fonctions', 'ParametreController#gestionFonction', 'parametres_fonctions');
$router->map('GET', '/parametres/messages', 'ParametreController#gestionMessagerie', 'parametres_messages');
$router->map('GET', '/parametres/attribution', 'ParametreController#gestionAttribution', 'parametres_attribution');
$router->map('GET', '/parametres/salles', 'ParametreController#gestionSalles', 'parametres_salles');
