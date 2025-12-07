<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';

class ArchivesCompteRenduController {
    private $db;
    private $auditLog;
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->auditLog = new AuditLog($this->db);
    }
    
    public function index() {
        try {
            $search = $_GET['search'] ?? null;
            $year = $_GET['year'] ?? null;
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Récupérer les comptes rendus archivés via la table compte_rendu
            $sql = "
                SELECT 
                    cr.id_compte_rendu,
                    cr.lib_compte_rendu as nom_CR,
                    e.num_etu,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    cr.date_creation as date_CR,
                    COALESCE(CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)), e.promotion_etu) as annee
                FROM compte_rendu cr
                LEFT JOIN etudiants e ON cr.num_etu = e.num_etu
                LEFT JOIN inscriptions i ON e.num_etu = i.id_etudiant
                LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($search) {
                $sql .= " AND (e.nom_etu LIKE :search OR e.prenom_etu LIKE :search OR e.num_etu LIKE :search OR cr.lib_compte_rendu LIKE :search)";
                $params['search'] = '%' . $search . '%';
            }
            
            if ($year) {
                $sql .= " AND YEAR(cr.date_creation) = :year";
                $params['year'] = (int)$year;
            }
            
            // Nombre total pour pagination
            $countSql = str_replace("SELECT cr.id_compte_rendu, cr.lib_compte_rendu as nom_CR, e.num_etu, e.nom_etu, e.prenom_etu, e.email_etu, cr.date_creation as date_CR, COALESCE(CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)), e.promotion_etu) as annee", "SELECT COUNT(DISTINCT cr.id_compte_rendu) as total", $sql);
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue(":$key", $value);
            }
            $countStmt->execute();
            $totalArchives = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            $totalPages = ceil($totalArchives / $limit);
            
            // Récupérer les archives paginées
            $sql .= " ORDER BY cr.date_creation DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $archives = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Passer les données à la vue
            $GLOBALS['archives'] = $archives;
            $GLOBALS['currentPage'] = $page;
            $GLOBALS['totalPages'] = $totalPages;
            $GLOBALS['search'] = $search;
            $GLOBALS['year'] = $year;
            $GLOBALS['totalArchives'] = $totalArchives;
            
        } catch (Exception $e) {
            error_log("Error in ArchivesCompteRenduController::index: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors du chargement des archives.";
            $GLOBALS['archives'] = [];
            $GLOBALS['totalPages'] = 1;
            $GLOBALS['currentPage'] = 1;
        }
    }
    
    public function viewArchive() {
        try {
            $id_CR = $_GET['id'] ?? null;
            
            if (!$id_CR) {
                $_SESSION['error'] = "ID du compte rendu manquant.";
                header('Location: ?page=archives_compte_rendu');
                exit;
            }
            
            $sql = "
                SELECT 
                    cr.*,
                    e.num_etu,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu
                FROM compte_rendu cr
                LEFT JOIN etudiants e ON cr.num_etu = e.num_etu
                WHERE cr.id_compte_rendu = :id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id_CR);
            $stmt->execute();
            $archive = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$archive) {
                $_SESSION['error'] = "Compte rendu non trouvé.";
                header('Location: ?page=archives_compte_rendu');
                exit;
            }
            
            $GLOBALS['archive'] = $archive;
            
        } catch (Exception $e) {
            error_log("Error in ArchivesCompteRenduController::viewArchive: " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors du chargement de l'archive.";
            header('Location: ?page=archives_compte_rendu');
            exit;
        }
    }
} 