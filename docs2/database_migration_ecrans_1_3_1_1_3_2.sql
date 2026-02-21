-- Migration SQL pour les écrans 1.3.1 et 1.3.2
-- Candidature (Validation) et Réclamation (Traitement)
-- Date: 2026-02-12

-- ============================================================
-- TABLE: candidature_soutenance
-- Ajout des colonnes manquantes pour la validation
-- ============================================================

-- Ajout de la colonne nombre_soumissions (pour RG-CAND-006)
ALTER TABLE candidature_soutenance 
ADD COLUMN IF NOT EXISTS nombre_soumissions INT DEFAULT 1 
COMMENT 'Nombre de fois que l\'étudiant a soumis sa candidature';

-- Ajout de la colonne id_validateur (pour RG-CAND-003)
ALTER TABLE candidature_soutenance 
ADD COLUMN IF NOT EXISTS id_validateur INT DEFAULT NULL 
COMMENT 'ID du personnel admin qui a validé/rejeté la candidature';

-- Ajout de la colonne motif_rejet (pour RG-CAND-004)
ALTER TABLE candidature_soutenance 
ADD COLUMN IF NOT EXISTS motif_rejet VARCHAR(100) DEFAULT NULL 
COMMENT 'Motif du rejet de la candidature';

-- Ajout de la colonne commentaire_rejet (pour RG-CAND-004)
ALTER TABLE candidature_soutenance 
ADD COLUMN IF NOT EXISTS commentaire_rejet TEXT DEFAULT NULL 
COMMENT 'Commentaire détaillé lors du rejet';

-- Ajout de la colonne snapshot_json (pour RG-CAND-005)
ALTER TABLE candidature_soutenance 
ADD COLUMN IF NOT EXISTS snapshot_json JSON DEFAULT NULL 
COMMENT 'Snapshot JSON des données à chaque étape du workflow';

-- Modification de la colonne statut_candidature pour supporter tous les statuts
ALTER TABLE candidature_soutenance 
MODIFY COLUMN statut_candidature ENUM('brouillon', 'soumise', 'validee', 'rejetee', 'En attente', 'Validée', 'Rejetée') 
NOT NULL DEFAULT 'brouillon' 
COMMENT 'Statut de la candidature: brouillon, soumise, validee, rejetee';

-- Ajout de la clé étrangère pour id_validateur
ALTER TABLE candidature_soutenance 
ADD CONSTRAINT IF NOT EXISTS fk_candidature_validateur 
FOREIGN KEY (id_validateur) REFERENCES personnel_admin(id_pers_admin) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================
-- TABLE: reclamations
-- Ajout des colonnes manquantes pour le traitement
-- ============================================================

-- Renommage de num_carte_etud vers num_etu pour cohérence
ALTER TABLE reclamations 
CHANGE COLUMN IF EXISTS num_carte_etud num_etu VARCHAR(25) DEFAULT NULL;

-- Ajout de la colonne titre_reclamation (alias objet_reclamation)
ALTER TABLE reclamations 
ADD COLUMN IF NOT EXISTS titre_reclamation VARCHAR(150) DEFAULT NULL 
COMMENT 'Titre/objet de la réclamation';

-- Ajout de la colonne type_reclamation (pour distinguer RCL/DMT)
ALTER TABLE reclamations 
ADD COLUMN IF NOT EXISTS type_reclamation VARCHAR(50) DEFAULT 'RCL' 
COMMENT 'Type: RCL (Réclamation) ou DMT (Demande de changement de thème)';

-- Ajout de la colonne priorite_reclamation
ALTER TABLE reclamations 
ADD COLUMN IF NOT EXISTS priorite_reclamation ENUM('Basse', 'Moyenne', 'Haute') DEFAULT 'Moyenne' 
COMMENT 'Priorité de la réclamation';

-- Ajout de la colonne id_admin_assigne (pour RG-REC-002)
ALTER TABLE reclamations 
ADD COLUMN IF NOT EXISTS id_admin_assigne INT DEFAULT NULL 
COMMENT 'ID du personnel admin assigné au traitement';

-- Ajout de la colonne commentaire_traitement (pour RG-REC-003)
ALTER TABLE reclamations 
ADD COLUMN IF NOT EXISTS commentaire_traitement TEXT DEFAULT NULL 
COMMENT 'Commentaire de traitement/solution apportée';

-- Ajout de la colonne date_traitement
ALTER TABLE reclamations 
ADD COLUMN IF NOT EXISTS date_traitement DATETIME DEFAULT NULL 
COMMENT 'Date de clôture du traitement';

-- Ajout de la colonne motif_rejet (pour RG-REC-004)
ALTER TABLE reclamations 
ADD COLUMN IF NOT EXISTS motif_rejet VARCHAR(100) DEFAULT NULL 
COMMENT 'Motif en cas de rejet';

-- Modification de statut_reclamation pour supporter les valeurs textuelles
-- Note: Si la colonne est de type INT, il faut la convertir en VARCHAR
ALTER TABLE reclamations 
ADD COLUMN IF NOT EXISTS statut_reclamation_v2 VARCHAR(50) DEFAULT 'En attente' 
COMMENT 'Statut: En attente, En cours, Traitée, Rejetée';

-- Migration des données si nécessaire (à adapter selon la structure existante)
-- UPDATE reclamations SET statut_reclamation_v2 = CASE 
--     WHEN statut_reclamation = 1 THEN 'En attente'
--     WHEN statut_reclamation = 2 THEN 'En cours'
--     WHEN statut_reclamation = 3 THEN 'Traitée'
--     WHEN statut_reclamation = 4 THEN 'Rejetée'
--     ELSE 'En attente'
-- END;

-- Ajout de la clé étrangère pour id_admin_assigne
ALTER TABLE reclamations 
ADD CONSTRAINT IF NOT EXISTS fk_reclamation_admin 
FOREIGN KEY (id_admin_assigne) REFERENCES personnel_admin(id_pers_admin) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Ajout des index pour optimiser les requêtes
CREATE INDEX IF NOT EXISTS idx_candidature_statut ON candidature_soutenance(statut_candidature);
CREATE INDEX IF NOT EXISTS idx_candidature_date ON candidature_soutenance(date_candidature);
CREATE INDEX IF NOT EXISTS idx_reclamation_statut ON reclamations(statut_reclamation_v2);
CREATE INDEX IF NOT EXISTS idx_reclamation_type ON reclamations(type_reclamation);
CREATE INDEX IF NOT EXISTS idx_reclamation_date ON reclamations(date_creation);

-- ============================================================
-- TABLE: historique_candidature (nouvelle table pour RG-CAND-005)
-- ============================================================

CREATE TABLE IF NOT EXISTS historique_candidature (
    id_historique INT AUTO_INCREMENT PRIMARY KEY,
    id_candidature INT NOT NULL,
    statut_precedent VARCHAR(50) DEFAULT NULL,
    statut_nouveau VARCHAR(50) NOT NULL,
    id_utilisateur INT DEFAULT NULL,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT DEFAULT NULL,
    snapshot_json JSON DEFAULT NULL,
    INDEX idx_historique_candidature (id_candidature),
    INDEX idx_historique_date (date_action),
    CONSTRAINT fk_hist_cand_candidature FOREIGN KEY (id_candidature) 
        REFERENCES candidature_soutenance(id_candidature) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Historique des actions sur les candidatures';

-- ============================================================
-- TABLE: historique_reclamation (nouvelle table pour RG-REC-005)
-- ============================================================

CREATE TABLE IF NOT EXISTS historique_reclamation (
    id_historique INT AUTO_INCREMENT PRIMARY KEY,
    id_reclamation INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    statut_precedent VARCHAR(50) DEFAULT NULL,
    statut_nouveau VARCHAR(50) DEFAULT NULL,
    id_utilisateur INT DEFAULT NULL,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT DEFAULT NULL,
    INDEX idx_historique_reclamation (id_reclamation),
    INDEX idx_historique_date (date_action),
    CONSTRAINT fk_hist_rec_reclamation FOREIGN KEY (id_reclamation) 
        REFERENCES reclamations(id_reclamation) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Historique des actions sur les réclamations';

-- ============================================================
-- TABLE: motif_rejet_candidature (pour RG-CAND-004)
-- ============================================================

CREATE TABLE IF NOT EXISTS motif_rejet_candidature (
    id_motif INT AUTO_INCREMENT PRIMARY KEY,
    code_motif VARCHAR(50) NOT NULL UNIQUE,
    libelle_motif VARCHAR(200) NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_motif_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Motifs paramétrables de rejet de candidature';

-- Insertion des motifs par défaut
INSERT INTO motif_rejet_candidature (code_motif, libelle_motif) VALUES
('scolarite_non_soldee', 'Scolarité non soldée'),
('duree_stage_insuffisante', 'Durée de stage insuffisante'),
('sujet_inapproprie', 'Sujet de stage inapproprié'),
('entreprise_non_valide', 'Entreprise non validée'),
('documents_manquants', 'Documents manquants'),
('autre', 'Autre (précisez)')
ON DUPLICATE KEY UPDATE libelle_motif = VALUES(libelle_motif);

-- ============================================================
-- PARAMÈTRES DU SYSTÈME (pour les règles métier)
-- ============================================================

-- Création d'une table de paramètres si elle n'existe pas
CREATE TABLE IF NOT EXISTS parametres_systeme (
    id_parametre INT AUTO_INCREMENT PRIMARY KEY,
    code_parametre VARCHAR(100) NOT NULL UNIQUE,
    valeur_parametre VARCHAR(500) NOT NULL,
    description_parametre TEXT,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parametre_code (code_parametre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Paramètres configurables du système';

-- Insertion des paramètres par défaut
INSERT INTO parametres_systeme (code_parametre, valeur_parametre, description_parametre) VALUES
('VERIF_SCOLARITE_BLOQUANTE', 'true', 'Si true, la validation est bloquée si la scolarité n\'est pas soldée'),
('DETTE_TOLERANCE_MAX', '50000', 'Montant maximum de dette toléré pour la validation (en FCFA)'),
('STAGE_DUREE_MIN_JOURS', '60', 'Durée minimum du stage en jours'),
('SLA_RECLAMATION_JOURS', '7', 'SLA interne de traitement des réclamations en jours')
ON DUPLICATE KEY UPDATE 
    valeur_parametre = VALUES(valeur_parametre),
    description_parametre = VALUES(description_parametre);
