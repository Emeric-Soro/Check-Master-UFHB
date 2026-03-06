<?php
/**
 * ArchiveAdminController - Candidatures et réclamations archivées
 */
require_once __DIR__ . '/../models/CandidatureSoutenance.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class ArchiveAdminController
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
    }

    /**
     * Archives des candidatures
     */
    public function candidatures()
    {
        if (!canView('admin_historique')) {
            $_SESSION['error_message'] = "Accès refusé.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        
        $candidatures = $this->getCandidaturesArchives($anneeId);
        $stats = $this->getStatsCandidatures($anneeId);

        return [
            'candidatures' => $candidatures,
            'stats' => $stats,
        ];
    }

    /**
     * Archives des réclamations
     */
    public function reclamations()
    {
        if (!canView('admin_historique')) {
            $_SESSION['error_message'] = "Accès refusé.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        
        $reclamations = $this->getReclamationsArchives($anneeId);
        $stats = $this->getStatsReclamations($anneeId);

        return [
            'reclamations' => $reclamations,
            'stats' => $stats,
        ];
    }

    // Méthodes privées

    private function getCandidaturesArchives($anneeId)
    {
        $sql = "SELECT 
                    cs.id_candidature,
                    cs.date_candidature,
                    e.num_carte_etud,
                    CONCAT(e.nom_etu, ' ', e.prenom_etu) as etudiant,
                    ne.lib_niv_etude as niveau,
                    cs.statut_candidature,
                    cs.date_traitement,
                    CONCAT(pa.nom_pers_admin, ' ', pa.prenom_pers_admin) as traite_par,
                    cs.commentaire_admin,
                    TIMESTAMPDIFF(DAY, cs.date_candidature, COALESCE(cs.date_traitement, NOW())) as delai_traitement
                FROM candidature_soutenance cs
                JOIN etudiants e ON cs.num_etu = e.num_carte_etud
                JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
                JOIN niveau_etude ne ON i.id_niveau = ne.id_niv_etude
                LEFT JOIN personnel_admin pa ON cs.id_pers_admin = pa.id_pers_admin
                WHERE i.id_annee_acad = ?
                ORDER BY cs.date_candidature DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$anneeId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getStatsCandidatures($anneeId)
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN cs.statut_candidature = 'Validée' THEN 1 END) as validees,
                    COUNT(CASE WHEN cs.statut_candidature = 'Rejetée' THEN 1 END) as rejetees,
                    COUNT(CASE WHEN cs.statut_candidature = 'En attente' THEN 1 END) as en_attente,
                    AVG(TIMESTAMPDIFF(DAY, cs.date_candidature, COALESCE(cs.date_traitement, NOW()))) as delai_moyen
                FROM candidature_soutenance cs
                JOIN etudiants e ON cs.num_etu = e.num_carte_etud
                JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
                WHERE i.id_annee_acad = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$anneeId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    private function getReclamationsArchives($anneeId)
    {
        $sql = "SELECT 
                    r.id_reclamation,
                    r.date_creation,
                    e.num_carte_etud,
                    CONCAT(e.nom_etu, ' ', e.prenom_etu) as etudiant,
                    r.objet_reclamation,
                    LEFT(r.description_reclamation, 100) as description,
                    sr.libelle_statut_reclamation as statut,
                    r.date_mise_a_jour
                FROM reclamations r
                JOIN etudiants e ON r.num_carte_etud = e.num_carte_etud
                LEFT JOIN statut_reclamation sr ON r.statut_reclamation = sr.id_statut_reclamation
                JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
                WHERE i.id_annee_acad = ?
                ORDER BY r.date_creation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$anneeId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getStatsReclamations($anneeId)
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN sr.libelle_statut_reclamation = 'Résolue' THEN 1 END) as resolues,
                    COUNT(CASE WHEN sr.libelle_statut_reclamation = 'En cours' THEN 1 END) as en_cours
                FROM reclamations r
                JOIN etudiants e ON r.num_carte_etud = e.num_carte_etud
                LEFT JOIN statut_reclamation sr ON r.statut_reclamation = sr.id_statut_reclamation
                JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
                WHERE i.id_annee_acad = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$anneeId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
