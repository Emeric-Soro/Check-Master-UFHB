<?php

namespace App\Controllers;

use PDO;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * ArchivesDossiersSoutenanceController - Liste des mémoires archivés
 * 
 * Ce contrôleur gère l'affichage des archives des rapports validés/rejetés :
 * - Liste des rapports archivés
 * - Détails des rapports
 * - Filtres et recherche
 * - Statistiques des archives
 * 
 * @package App\Controllers
 */
class ArchivesDossiersSoutenanceController
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
        
        if (!$this->security->can($idGroupe, 'archives_dossiers_soutenance', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur archives_dossiers_soutenance"
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
     * Action : Afficher la page des archives (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            // Récupérer les filtres depuis la requête
            $filtres = [
                'statut' => $this->security->sanitizeInput($_GET['statut'] ?? ''),
                'annee' => $this->security->sanitizeInput($_GET['annee'] ?? ''),
                'etudiant' => $this->security->sanitizeInput($_GET['etudiant'] ?? ''),
                'date_debut' => $this->security->sanitizeInput($_GET['date_debut'] ?? ''),
                'date_fin' => $this->security->sanitizeInput($_GET['date_fin'] ?? '')
            ];

            $archives = [
                'rapports_archives' => $this->getRapportsArchives($filtres),
                'statistiques' => $this->getStatistiquesArchives(),
                'filtres' => $filtres
            ];
            
            $GLOBALS['archives'] = $archives;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans ArchivesDossiersSoutenanceController::index: " . $e->getMessage());
            $GLOBALS['archives'] = [
                'rapports_archives' => [],
                'statistiques' => [],
                'filtres' => $filtres ?? []
            ];
            $GLOBALS['messageErreur'] = "Erreur lors du chargement des archives.";
        }
    }

    /**
     * Récupère les rapports archivés avec filtres
     */
    private function getRapportsArchives(array $filtres = []): array
    {
        try {
            $whereConditions = [];
            $params = [];

            if (!empty($filtres['statut'])) {
                $whereConditions[] = "v.decision_validation = :statut";
                $params['statut'] = $filtres['statut'];
            }

            if (!empty($filtres['annee'])) {
                $whereConditions[] = "YEAR(v.date_validation) = :annee";
                $params['annee'] = (int)$filtres['annee'];
            }

            if (!empty($filtres['etudiant'])) {
                $whereConditions[] = "(e.nom_etu LIKE :etudiant OR e.prenom_etu LIKE :etudiant OR e.num_etu LIKE :etudiant)";
                $params['etudiant'] = '%' . $filtres['etudiant'] . '%';
            }

            if (!empty($filtres['date_debut'])) {
                $whereConditions[] = "v.date_validation >= :date_debut";
                $params['date_debut'] = $filtres['date_debut'];
            }

            if (!empty($filtres['date_fin'])) {
                $whereConditions[] = "v.date_validation <= :date_fin";
                $params['date_fin'] = $filtres['date_fin'];
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            $query = "SELECT 
                        v.id_rapport,
                        v.decision_validation,
                        v.date_validation,
                        v.commentaire_validation,
                        r.nom_rapport,
                        r.theme_rapport,
                        r.date_rapport,
                        e.promotion_etu,
                        e.num_etu,
                        e.nom_etu,
                        e.prenom_etu,
                        e.email_etu,
                        DATEDIFF(v.date_validation, r.date_rapport) as temps_traitement
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON r.num_etu = e.num_etu
                      $whereClause
                      ORDER BY v.date_validation DESC";

            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getRapportsArchives: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques des archives
     */
    private function getStatistiquesArchives(): array
    {
        try {
            $stats = [];

            $query = "SELECT COUNT(*) as total FROM valider";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_archives'] = $result['total'] ?? 0;

            $queryStatuts = "SELECT decision_validation as statut, COUNT(*) as nombre FROM valider GROUP BY decision_validation";
            $stmtStatuts = $this->pdo->prepare($queryStatuts);
            $stmtStatuts->execute();
            $stats['repartition_statuts'] = $stmtStatuts->fetchAll(PDO::FETCH_ASSOC);

            $query = "SELECT YEAR(date_validation) as annee, COUNT(*) as nombre FROM valider GROUP BY YEAR(date_validation) ORDER BY annee DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['repartition_annees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $queryTemps = "SELECT AVG(DATEDIFF(v.date_validation, r.date_rapport)) as temps_moyen
                          FROM valider v
                          LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                          WHERE r.date_rapport IS NOT NULL";
            $stmtTemps = $this->pdo->prepare($queryTemps);
            $stmtTemps->execute();
            $resultTemps = $stmtTemps->fetch(PDO::FETCH_ASSOC);
            $stats['temps_moyen_traitement'] = round((float)($resultTemps['temps_moyen'] ?? 0), 1);

            return $stats;
        } catch (Exception $e) {
            $this->logger->error("Erreur getStatistiquesArchives: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails d'un rapport spécifique (READ)
     */
    public function getRapportDetails(int $idRapport): ?array
    {
        if (!$this->checkPermission('read')) {
            return null;
        }

        try {
            $query = "SELECT 
                        v.*,
                        r.nom_rapport,
                        r.theme_rapport,
                        r.date_rapport,
                        e.promotion_etu,
                        e.nom_etu,
                        e.prenom_etu,
                        e.email_etu
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON r.num_etu = e.num_etu
                      WHERE v.id_rapport = :id";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':id', $idRapport, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            $this->logger->error("Erreur getRapportDetails: " . $e->getMessage());
            return null;
        }
    }
}
 