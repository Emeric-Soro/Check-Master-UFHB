<?php

namespace App\Controllers;

use PDO;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * ArchivesCompteRenduController - Liste des PV archivés
 * 
 * @package App\Controllers
 */
class ArchivesCompteRenduController
{
    private PDO $pdo;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'archive_comptes_rendus', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur archive_comptes_rendus"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Action : Afficher la liste des comptes rendus archivés (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $search = $this->security->sanitizeInput($_GET['search'] ?? null);
            $year = $this->security->sanitizeInput($_GET['year'] ?? null);
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Requête pour récupérer les comptes rendus archivés
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
            
            // Calcul du total pour pagination
            $countSql = "SELECT COUNT(DISTINCT cr.id_compte_rendu) as total FROM compte_rendu cr 
                        LEFT JOIN etudiants e ON cr.num_etu = e.num_etu 
                        WHERE 1=1";
            if ($search) $countSql .= " AND (e.nom_etu LIKE :search OR e.prenom_etu LIKE :search OR e.num_etu LIKE :search OR cr.lib_compte_rendu LIKE :search)";
            if ($year) $countSql .= " AND YEAR(cr.date_creation) = :year";

            $countStmt = $this->pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue(":$key", $value);
            }
            $countStmt->execute();
            $totalArchives = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            $totalPages = ceil($totalArchives / $limit);
            
            // Récupération paginée
            $sql .= " ORDER BY cr.date_creation DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $archives = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Passage à la vue
            $GLOBALS['archives'] = $archives;
            $GLOBALS['currentPage'] = $page;
            $GLOBALS['totalPages'] = $totalPages;
            $GLOBALS['search'] = $search;
            $GLOBALS['year'] = $year;
            $GLOBALS['totalArchives'] = $totalArchives;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans ArchivesCompteRenduController::index: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors du chargement des archives.";
            $GLOBALS['archives'] = [];
            $GLOBALS['totalPages'] = 1;
            $GLOBALS['currentPage'] = 1;
        }
    }
    
    /**
     * Action : Voir une archive spécifique (READ)
     */
    public function viewArchive(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $id_CR = $this->security->sanitizeInput($_GET['id'] ?? null);
            
            if (!$id_CR) {
                $GLOBALS['messageErreur'] = "ID du compte rendu manquant.";
                return;
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
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id_CR);
            $stmt->execute();
            $archive = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$archive) {
                $GLOBALS['messageErreur'] = "Compte rendu non trouvé.";
                return;
            }
            
            $GLOBALS['archive'] = $archive;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans ArchivesCompteRenduController::viewArchive: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors du chargement de l'archive.";
        }
    }
}
 