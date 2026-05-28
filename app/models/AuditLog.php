<?php

class AuditLog {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAuditLog($id_utilisateur) {
        $sql = "SELECT * FROM pister WHERE id_utilisateur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_utilisateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuditLogByAction($action) {
        $sql = "SELECT * FROM pister WHERE action = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$action]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuditLogByTable($contexte) {
        $sql = "SELECT * FROM pister WHERE contexte = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contexte]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuditLogByDate($date) {
        $sql = "SELECT * FROM pister WHERE date_creation = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllAuditLog() {
        $sql = "SELECT p.*, u.login_utilisateur, u.nom_utilisateur
                FROM pister p
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                ORDER BY p.date_creation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Enregistre une action générique dans la table pister
    // $statut_action doit être dans {'Erreur','Succès'} pour respecter l'ENUM de la DB.
    public function logAction($id_utilisateur, $action, $contexte, $statut_action, $details = null) {
        // id_utilisateur null pour les événements anonymes (pas de 0 fictif)
        $id_utilisateur = ($id_utilisateur !== null && (int) $id_utilisateur > 0) ? (int) $id_utilisateur : null;

        // Normaliser statut
        $statut = (string) $statut_action;
        if ($statut === 'Partiel' || $statut === 'partial') {
            $statut = 'Erreur';
        }
        if ($statut !== 'Erreur' && $statut !== 'Succès') {
            $statut = 'Erreur';
        }

        $sql = "INSERT INTO pister (id_utilisateur, action, contexte, statut_action, date_creation) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_utilisateur, $action, $contexte, $statut]);
    }

    // Méthodes spécifiques pour chaque type d'action
    public function logCreation($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Création', $contexte, $statut_action);
    }

    public function logModification($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Modification', $contexte, $statut_action);
    }

    public function logSuppression($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Suppression', $contexte, $statut_action);
    }

    public function logConnexion($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Connexion', $contexte, $statut_action);
    }

    public function logDeconnexion($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Déconnexion', $contexte, $statut_action);
    }

    public function logExportation($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Exportation', $contexte, $statut_action);
    }

    public function logImpression($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Impression', $contexte, $statut_action);
    }

    public function logDepot($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Dépôt', $contexte, $statut_action);
    }

    public function logEvaluation($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Evaluation', $contexte, $statut_action);
    }

    public function logValidation($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Validation', $contexte, $statut_action);
    }

    public function logRejet($id_utilisateur, $contexte, $statut_action) {
        return $this->logAction($id_utilisateur, 'Rejet', $contexte, $statut_action);
    }
} 