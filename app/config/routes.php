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
$router->map('GET', '/profil', 'GestionUtilisateurController#index', 'profil');

// Routes de gestion RH
$router->map('GET', '/rh', 'GestionRhController#index', 'gestion_rh');

// Routes de gestion de la scolarité
$router->map('GET', '/scolarite', 'GestionScolariteController#index', 'gestion_scolarite');
$router->map('GET', '/scolarite/imprimer-recu/[i:id]', 'GestionScolariteController#imprimerRecu', 'scolarite_imprimer_recu');

// Routes de gestion des notes
$router->map('GET', '/notes', 'NotesController#index', 'gestion_notes');
$router->map('GET', '/notes-resultats', 'NotesResultatsController#index', 'notes_resultats');
$router->map('GET', '/notes/imprimer-releve/[i:student]/[a:niveau]', 'NotesController#imprimerReleve', 'notes_imprimer_releve');

// Routes de gestion des étudiants
$router->map('GET', '/etudiants', 'GestionEtudiantController#index', 'gestion_etudiants');
$router->map('GET', '/etudiants/ajouter', 'GestionEtudiantController#index', 'etudiants_ajouter');
$router->map('GET', '/etudiants/inscrire', 'InscriptionController#index', 'etudiants_inscrire');
$router->map('GET', '/etudiants/imprimer-recu/[i:id]', 'GestionEtudiantController#imprimerRecu', 'etudiants_imprimer_recu');
$router->map('GET', '/etudiants/liste', 'GestionEtudiantController#liste', 'liste_etudiants');

// Routes de candidature à la soutenance
$router->map('GET', '/candidature-soutenance', 'CandidatureSoutenanceController#index', 'candidature_soutenance');
$router->map('GET', '/candidature-soutenance/compte-rendu', 'CandidatureSoutenanceController#compteRendu', 'candidature_compte_rendu');

// Routes de gestion des candidatures
$router->map('GET', '/gestion-candidatures', 'GestionCandidaturesController#index', 'gestion_candidatures');
$router->map('GET', '/gestion-candidatures/examiner/[i:id]', 'GestionCandidaturesController#examiner', 'gestion_candidatures_examiner');

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
$router->map('GET', '/evaluation-soutenance', 'EvaluationSoutenanceController#index', 'evaluation_soutenance');
$router->map('POST', '/evaluation-soutenance/evaluer', 'EvaluationSoutenanceController#enregistrerEvaluation', 'evaluation_soutenance_evaluer');
$router->map('POST', '/evaluation-soutenance/supprimer', 'EvaluationSoutenanceController#supprimerEvaluation', 'evaluation_soutenance_supprimer');
$router->map('GET', '/evaluation-soutenance/ajax/evaluation/[a:num_etu]', 'EvaluationSoutenanceController#getEvaluationExistante', 'evaluation_soutenance_ajax_get');
$router->map('GET', '/evaluation-soutenance/ajax/criteres', 'EvaluationSoutenanceController#getCriteresParAnnee', 'evaluation_soutenance_ajax_criteres');
$router->map('GET', '/evaluation-soutenance/imprimer-pv', 'EvaluationSoutenanceController#imprimerPV', 'evaluation_soutenance_imprimer_pv');

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
$router->map('POST', '/compte-rendu/redaction', 'RedactionCompteRenduController#traiter', 'redaction_compte_rendu_post');

// Routes des archives de comptes rendus
$router->map('GET', '/compte-rendu/archives', 'ArchivesCompteRenduController#index', 'archives_compte_rendu');
$router->map('GET', '/compte-rendu/archives/telecharger/[i:id]', 'ArchivesCompteRenduController#telecharger', 'archives_compte_rendu_telecharger');

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
$router->map('GET', '/parametres/grades', 'ParametreController#gestionGrade', 'parametres_grades');
$router->map('GET', '/parametres/fonction-utilisateur', 'ParametreController#gestionFonctionUtilisateur', 'parametres_fonction_utilisateur');
$router->map('GET', '/parametres/specialites', 'ParametreController#gestionSpecialite', 'parametres_specialites');
$router->map('GET', '/parametres/niveaux-etude', 'ParametreController#gestionNiveauEtude', 'parametres_niveaux_etude');
$router->map('GET', '/parametres/ue', 'ParametreController#gestionUe', 'parametres_ue');
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
