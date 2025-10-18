-- ============================================================================
-- Script d'optimisation des performances - Indexes recommandés
-- Base de données: soutenance_manager
-- Date: 2025-01-18
-- 
-- IMPORTANT: Vérifier que les indexes n'existent pas déjà avant d'exécuter
-- Utiliser: SHOW INDEX FROM nom_table; pour vérifier
-- ============================================================================

USE soutenance_manager;

-- ============================================================================
-- Table: etudiants
-- ============================================================================
CREATE INDEX idx_etudiants_nom_prenom ON etudiants(nom_etu, prenom_etu);
CREATE INDEX idx_etudiants_email ON etudiants(email_etu);
CREATE INDEX idx_etudiants_promotion ON etudiants(promotion_etu);

-- ============================================================================
-- Table: utilisateur
-- ============================================================================
CREATE INDEX idx_utilisateur_login ON utilisateur(login_utilisateur);
CREATE INDEX idx_utilisateur_nom ON utilisateur(nom_utilisateur);
CREATE INDEX idx_utilisateur_type ON utilisateur(id_type_utilisateur);
CREATE INDEX idx_utilisateur_statut ON utilisateur(statut_utilisateur);
CREATE INDEX idx_utilisateur_groupe ON utilisateur(id_GU);

-- ============================================================================
-- Table: reclamations
-- ============================================================================
CREATE INDEX idx_reclamations_statut ON reclamations(statut_reclamation);
CREATE INDEX idx_reclamations_num_etu ON reclamations(num_etu);
CREATE INDEX idx_reclamations_date ON reclamations(date_creation);
CREATE INDEX idx_reclamations_type ON reclamations(type_reclamation);
CREATE INDEX idx_reclamations_statut_date ON reclamations(statut_reclamation, date_creation);

-- ============================================================================
-- Table: pister (audit log)
-- ============================================================================
CREATE INDEX idx_pister_date ON pister(date_creation);
CREATE INDEX idx_pister_action ON pister(action);
CREATE INDEX idx_pister_utilisateur ON pister(id_utilisateur);
CREATE INDEX idx_pister_table ON pister(nom_table);
CREATE INDEX idx_pister_statut ON pister(statut_action);
CREATE INDEX idx_pister_date_action ON pister(date_creation, action);

-- ============================================================================
-- Table: notes
-- ============================================================================
CREATE INDEX idx_notes_etudiant ON notes(num_etu);
CREATE INDEX idx_notes_ue ON notes(id_ue);
CREATE INDEX idx_notes_etudiant_ue ON notes(num_etu, id_ue);

-- ============================================================================
-- Table: inscriptions
-- ============================================================================
CREATE INDEX idx_inscriptions_etudiant ON inscriptions(id_etudiant);
CREATE INDEX idx_inscriptions_niveau ON inscriptions(id_niveau);
CREATE INDEX idx_inscriptions_annee ON inscriptions(id_annee_acad);
CREATE INDEX idx_inscriptions_date ON inscriptions(date_inscription);

-- ============================================================================
-- Table: candidature_soutenance
-- ============================================================================
CREATE INDEX idx_candidature_num_etu ON candidature_soutenance(num_etu);
CREATE INDEX idx_candidature_statut ON candidature_soutenance(statut_candidature);
CREATE INDEX idx_candidature_date ON candidature_soutenance(date_candidature);
CREATE INDEX idx_candidature_statut_date ON candidature_soutenance(statut_candidature, date_candidature);

-- ============================================================================
-- Table: ue
-- ============================================================================
CREATE INDEX idx_ue_semestre ON ue(id_semestre);
CREATE INDEX idx_ue_niveau ON ue(id_niveau_etude);
CREATE INDEX idx_ue_annee ON ue(id_annee_academique);

-- ============================================================================
-- Table: informations_stage
-- ============================================================================
CREATE INDEX idx_info_stage_num_etu ON informations_stage(num_etu);
CREATE INDEX idx_info_stage_entreprise ON informations_stage(id_entreprise);

-- ============================================================================
-- Table: versements
-- ============================================================================
CREATE INDEX idx_versements_inscription ON versements(id_inscription);
CREATE INDEX idx_versements_date ON versements(date_versement);
