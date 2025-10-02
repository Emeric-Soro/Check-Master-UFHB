<?php


// Récupérer
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/RapportEtudiant.php";
require_once __DIR__ . "/../models/EvaluationRapport.php";

/**
 * Contrôleur des archives des dossiers de soutenance
 * 
 * Ce contrôleur gère l'affichage des archives des rapports validés/rejetés :
 * - Liste des rapports archivés
 * - Détails des rapports
 * - Filtres et recherche
 * - Statistiques des archives
 */
class ArchivesDossiersSoutenanceController
{
    /** @var RapportEtudiant */
    private $rapportEtudiant;

    /** @var EvaluationRapport */
    private $evaluationRapport;

    /**
     * Constructeur du contrôleur
     * Initialise les modèles nécessaires
     */
    public function __construct()
    {
        $this->rapportEtudiant = new RapportEtudiant(Database::getConnection());
        $this->evaluationRapport = new EvaluationRapport(Database::getConnection());
    }

    /**
     * Récupère les données des archives
     * @param array $filtres Filtres optionnels
     * @return array Les données des archives
     */
    public function getArchivesData($filtres = [])
    {
        $archives = [
            'rapports_archives' => [],
            'statistiques' => [],
            'filtres' => $filtres
        ];

        try {
            $archives['rapports_archives'] = $this->getRapportsArchives($filtres);
            $archives['statistiques'] = $this->getStatistiquesArchives();
        } catch (Exception $e) {
            error_log("Erreur dans getArchivesData: " . $e->getMessage());
        }

        return $archives;
    }

    /**
     * Récupère les rapports archivés avec filtres
     * @param array $filtres
     * @return array
     */
    private function getRapportsArchives($filtres = [])
    {
        try {
            // Vérifier d'abord si la table valider existe et contient des données
            $checkQuery = "SHOW TABLES LIKE 'valider'";
            $checkStmt = Database::getConnection()->prepare($checkQuery);
            $checkStmt->execute();
            $validerExists = $checkStmt->rowCount() > 0;

            if ($validerExists) {
                // Utiliser la table valider comme source principale
                $whereConditions = [];
                $params = [];

                // Filtre par statut
                if (!empty($filtres['statut'])) {
                    $whereConditions[] = "v.decision_validation = ?";
                    $params[] = $filtres['statut'];
                }

                // Filtre par année
                if (!empty($filtres['annee'])) {
                    $whereConditions[] = "YEAR(v.date_validation) = ?";
                    $params[] = $filtres['annee'];
                }

                // Filtre par enseignant
                if (!empty($filtres['enseignant'])) {
                    $whereConditions[] = "(ens.nom_enseignant LIKE ? OR ens.prenom_enseignant LIKE ?)";
                    $params[] = '%' . $filtres['enseignant'] . '%';
                    $params[] = '%' . $filtres['enseignant'] . '%';
                }

                // Filtre par étudiant
                if (!empty($filtres['etudiant'])) {
                    $whereConditions[] = "(e.nom_etu LIKE ? OR e.prenom_etu LIKE ?)";
                    $params[] = '%' . $filtres['etudiant'] . '%';
                    $params[] = '%' . $filtres['etudiant'] . '%';
                }

                // Filtre par date
                if (!empty($filtres['date_debut'])) {
                    $whereConditions[] = "v.date_validation >= ?";
                    $params[] = $filtres['date_debut'];
                }

                if (!empty($filtres['date_fin'])) {
                    $whereConditions[] = "v.date_validation <= ?";
                    $params[] = $filtres['date_fin'];
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
                            ens.id_enseignant,
                            ens.nom_enseignant,
                            ens.prenom_enseignant,
                            ens.mail_enseignant,
                            DATEDIFF(v.date_validation, r.date_rapport) as temps_traitement,
                            (SELECT COUNT(*) FROM evaluations_rapports er WHERE er.id_rapport = v.id_rapport) as nombre_evaluations
                          FROM valider v
                          LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                          LEFT JOIN etudiants e ON r.num_etu = e.num_etu
                          LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                          $whereClause
                          ORDER BY v.date_validation DESC";

                $stmt = Database::getConnection()->prepare($query);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Fallback vers les autres tables si valider n'existe pas
                error_log("Table valider n'existe pas, utilisation des tables alternatives");
                return $this->getRapportsFromAvailableTables($filtres);
            }
        } catch (Exception $e) {
            error_log("Erreur getRapportsArchives: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les rapports depuis les tables disponibles si rapport_etudiants n'existe pas
     * @param array $filtres
     * @return array
     */
    private function getRapportsFromAvailableTables($filtres = [])
    {
        try {
            // Utiliser la table evaluations_rapports comme source principale
            $query = "SELECT 
                        er.id_rapport,
                        'valider' as decision_validation,
                        er.date_evaluation as date_validation,
                        er.commentaire as commentaire_validation,
                        CONCAT('Rapport #', er.id_rapport) as nom_rapport,
                        'Thème non spécifié' as theme_rapport,
                        er.date_evaluation as date_rapport,
                        'Promotion non spécifiée' as promotion_etu,
                        er.id_evaluateur as num_etu,
                        ens.nom_enseignant as nom_etu,
                        ens.prenom_enseignant as prenom_etu,
                        ens.email_enseignant as email_etu,
                        ens.id_enseignant,
                        ens.nom_enseignant,
                        ens.prenom_enseignant,
                        ens.email_enseignant,
                        0 as temps_traitement,
                        1 as nombre_evaluations
                      FROM evaluations_rapports er
                      LEFT JOIN enseignants ens ON er.id_evaluateur = ens.id_enseignant
                      ORDER BY er.date_evaluation DESC
                      LIMIT 20";

            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRapportsFromAvailableTables: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques des archives
     * @return array
     */
    private function getStatistiquesArchives()
    {
        try {
            $stats = [];

            // Vérifier d'abord si la table valider existe
            $checkQuery = "SHOW TABLES LIKE 'valider'";
            $checkStmt = Database::getConnection()->prepare($checkQuery);
            $checkStmt->execute();
            $validerExists = $checkStmt->rowCount() > 0;

            if ($validerExists) {
                // Utiliser la table valider comme source principale
                $query = "SELECT COUNT(*) as total FROM valider";
                $stmt = Database::getConnection()->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['total_archives'] = $result['total'] ?? 0;

                // Répartition par statut
                $queryStatuts = "SELECT 
                                  decision_validation as statut,
                                  COUNT(*) as nombre
                                FROM valider 
                                GROUP BY decision_validation";
                $stmtStatuts = Database::getConnection()->prepare($queryStatuts);
                $stmtStatuts->execute();
                $stats['repartition_statuts'] = $stmtStatuts->fetchAll(PDO::FETCH_ASSOC);

                // Répartition par année
                $query = "SELECT 
                            YEAR(date_validation) as annee,
                            COUNT(*) as nombre
                          FROM valider 
                          GROUP BY YEAR(date_validation)
                          ORDER BY annee DESC";
                $stmt = Database::getConnection()->prepare($query);
                $stmt->execute();
                $stats['repartition_annees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Temps moyen de traitement
                $queryTemps = "SELECT AVG(DATEDIFF(v.date_validation, r.date_rapport)) as temps_moyen
                              FROM valider v
                              LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                              WHERE r.date_rapport IS NOT NULL";
                $stmtTemps = Database::getConnection()->prepare($queryTemps);
                $stmtTemps->execute();
                $resultTemps = $stmtTemps->fetch(PDO::FETCH_ASSOC);
                $stats['temps_moyen_traitement'] = round($resultTemps['temps_moyen'] ?? 0, 1);
            } else {
                // Fallback vers les autres tables si valider n'existe pas
                error_log("Table valider n'existe pas, utilisation des tables alternatives");
                return $this->getStatistiquesFromAvailableTables();
            }

            return $stats;
        } catch (Exception $e) {
            error_log("Erreur getStatistiquesArchives: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques depuis les tables disponibles (fallback)
     * @return array
     */
    private function getStatistiquesFromAvailableTables()
    {
        try {
            $stats = [];

            // Utiliser evaluations_rapports comme source
            $query = "SELECT COUNT(*) as total FROM evaluations_rapports";
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_archives'] = $result['total'] ?? 0;

            // Répartition par statut (tous évalués)
            $stats['repartition_statuts'] = [
                ['statut' => 'valider', 'nombre' => $stats['total_archives']]
            ];

            // Répartition par année
            $query = "SELECT 
                        YEAR(date_evaluation) as annee,
                        COUNT(*) as nombre
                      FROM evaluations_rapports 
                      GROUP BY YEAR(date_evaluation)
                      ORDER BY annee DESC";
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute();
            $stats['repartition_annees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats['temps_moyen_traitement'] = 0;

            return $stats;
        } catch (Exception $e) {
            error_log("Erreur getStatistiquesFromAvailableTables: " . $e->getMessage());
            return [
                'total_archives' => 0,
                'repartition_statuts' => [],
                'repartition_annees' => [],
                'temps_moyen_traitement' => 0
            ];
        }
    }

    /**
     * Récupère les détails d'un rapport spécifique
     * @param int $idRapport
     * @return array
     */
    public function getRapportDetails($idRapport)
    {
        try {
            // Vérifier d'abord si la table valider existe
            $checkQuery = "SHOW TABLES LIKE 'valider'";
            $checkStmt = Database::getConnection()->prepare($checkQuery);
            $checkStmt->execute();
            $validerExists = $checkStmt->rowCount() > 0;

            if ($validerExists) {
                // Utiliser la table valider comme source principale
                $query = "SELECT 
                            v.*,
                            r.nom_rapport,
                            r.theme_rapport,
                            r.date_rapport,
                            e.promotion_etu,
                            e.nom_etu,
                            e.prenom_etu,
                            e.email_etu,
                            ens.nom_enseignant,
                            ens.prenom_enseignant,
                            ens.mail_enseignant
                          FROM valider v
                          LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                          LEFT JOIN etudiants e ON r.num_etu = e.num_etu
                          LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                          WHERE v.id_rapport = ?";
            } else {
                // Fallback vers evaluations_rapports
                $query = "SELECT 
                            er.*,
                            ens.nom_enseignant,
                            ens.prenom_enseignant,
                            ens.email_enseignant,
                            CONCAT('Rapport #', er.id_rapport) as nom_rapport,
                            'Thème non spécifié' as theme_rapport,
                            er.date_evaluation as date_rapport,
                            'Promotion non spécifiée' as promotion_etu,
                            'valider' as decision_validation,
                            er.date_evaluation as date_validation,
                            'Rapport évalué' as commentaire_validation
                          FROM evaluations_rapports er
                          LEFT JOIN enseignants ens ON er.id_evaluateur = ens.id_enseignant
                          WHERE er.id_rapport = ?";
            }
            
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute([$idRapport]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRapportDetails: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les évaluations d'un rapport
     * @param int $idRapport
     * @return array
     */
    public function getEvaluationsRapport($idRapport)
    {
        try {
            $query = "SELECT 
                        er.*,
                        ens.nom_enseignant,
                        ens.prenom_enseignant
                      FROM evaluations_rapports er
                      LEFT JOIN enseignants ens ON er.id_evaluateur = ens.id_enseignant
                      WHERE er.id_rapport = ?
                      ORDER BY er.date_evaluation DESC";
            
            $stmt = Database::getConnection()->prepare($query);
            $stmt->execute([$idRapport]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getEvaluationsRapport: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Affiche la page des archives
     */
    public function index()
    {
        try {
            // Récupérer les filtres depuis la requête
            $filtres = [
                'statut' => $_GET['statut'] ?? '',
                'annee' => $_GET['annee'] ?? '',
                'enseignant' => $_GET['enseignant'] ?? '',
                'etudiant' => $_GET['etudiant'] ?? '',
                'date_debut' => $_GET['date_debut'] ?? '',
                'date_fin' => $_GET['date_fin'] ?? ''
            ];

            $archivesData = $this->getArchivesData($filtres);
            
            // Passer les données à la vue
            global $archives;
            $archives = $archivesData;
            
        } catch (Exception $e) {
            error_log("Erreur dans index: " . $e->getMessage());
            // En cas d'erreur, utiliser des données par défaut
            global $archives;
            $archives = [
                'rapports_archives' => [],
                'statistiques' => [],
                'filtres' => []
            ];
        }
    }
} 