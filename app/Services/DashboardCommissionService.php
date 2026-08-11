<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/RapportEtudiant.php";
require_once __DIR__ . "/../models/EvaluationRapport.php";

use RapportEtudiant;
use EvaluationRapport;
use PDO;
use Exception;

/**
 * Service métier du tableau de bord de la commission
 *
 * Contient toute la logique métier et les requêtes de données pour le tableau de bord :
 * - Statistiques des rapports
 * - Taux de validation
 * - Temps moyen de traitement
 * - Rapports en attente
 * - Activités récentes
 * - Graphiques d'évolution
 */
class DashboardCommissionService
{
    /** @var PDO */
    private $db;

    /** @var RapportEtudiant */
    private $rapportEtudiant;

    /** @var EvaluationRapport */
    private $evaluationRapport;
    private $tableExistsCache = [];
    private $columnExistsCache = [];

    /**
     * Constructeur du service
     *
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->rapportEtudiant = new RapportEtudiant($db);
        $this->evaluationRapport = new EvaluationRapport($db);
    }

    private function tableExists($tableName)
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableExistsCache[$tableName] = $exists;
            return $exists;
        } catch (Exception $e) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function columnExists($tableName, $columnName)
    {
        $key = strtolower((string) $tableName . '.' . (string) $columnName);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }
        if (!$this->tableExists($tableName)) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$key] = $exists;
            return $exists;
        } catch (Exception $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function rapportDateExpr($alias = 'r')
    {
        if ($this->columnExists('rapport_etudiants', 'date_rapport')) {
            return $alias . '.date_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_redaction_rapport')) {
            return $alias . '.date_redaction_rapport';
        }
        return 'NULL';
    }

    private function rapportTitleExpr($alias = 'r')
    {
        if ($this->columnExists('rapport_etudiants', 'nom_rapport')) {
            return $alias . '.nom_rapport';
        }
        return $alias . '.theme_rapport';
    }

    /**
     * Récupère l'année académique active (celle dont la date actuelle est comprise entre date_deb et date_fin)
     *
     * @return array|null
     */
    public function getAnneeAcademiqueActive()
    {
        try {
            if (!$this->tableExists('annee_academique')) {
                return null;
            }
            $query = "SELECT id_annee_acad, date_deb, date_fin 
                      FROM annee_academique 
                      WHERE CURDATE() BETWEEN date_deb AND date_fin 
                      LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // Si aucune année trouvée avec la date actuelle, retourner la plus récente
                $query = "SELECT id_annee_acad, date_deb, date_fin 
                          FROM annee_academique 
                          ORDER BY date_deb DESC 
                          LIMIT 1";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $result ?: null;
        } catch (Exception $e) {
            error_log("Erreur getAnneeAcademiqueActive: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère le nombre de rapports validés
     * Combine la table valider (finalisation formelle) et le statut_rapport (approbation directe)
     *
     * @return int
     */
    public function getNombreRapportsValides()
    {
        try {
            $query = "SELECT COUNT(DISTINCT id_rapport) as valides
                      FROM rapport_etudiants
                      WHERE statut_rapport = 'valider'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['valides'] ?? 0);
        } catch (Exception $e) {
            error_log("Erreur getNombreRapportsValides: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le nombre de rapports rejetés
     *
     * @return int
     */
    public function getNombreRapportsRejetes()
    {
        try {
            // Compter depuis valider ET depuis statut_rapport pour cohérence
            $query = "SELECT COUNT(DISTINCT id_rapport) as rejetes
                      FROM rapport_etudiants
                      WHERE statut_rapport = 'rejeter'
                         OR id_rapport IN (
                             SELECT DISTINCT id_rapport FROM valider
                             WHERE decision_validation = 'rejeter'
                         )";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['rejetes'] ?? 0);
        } catch (Exception $e) {
            error_log("Erreur getNombreRapportsRejetes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les statistiques agrégées en UNE requête (au lieu de 5 requêtes individuelles).
     *
     * @return array
     */
    public function getAggregatedStats(): array
    {
        $defaults = ['total_valider' => 0, 'valides_valider' => 0, 'rejetes_valider' => 0,
                     'valides_statut' => 0, 'rejetes_statut' => 0, 'en_attente' => 0];
        try {
            $sql = "SELECT
                        (SELECT COUNT(DISTINCT id_rapport) FROM valider) AS total_valider,
                        (SELECT COUNT(DISTINCT id_rapport) FROM valider WHERE decision_validation = 'valider') AS valides_valider,
                        (SELECT COUNT(DISTINCT id_rapport) FROM valider WHERE decision_validation = 'rejeter') AS rejetes_valider,
                        (SELECT COUNT(*) FROM rapport_etudiants WHERE statut_rapport = 'valider') AS valides_statut,
                        (SELECT COUNT(*) FROM rapport_etudiants WHERE statut_rapport = 'rejeter') AS rejetes_statut,
                        (SELECT COUNT(*) FROM rapport_etudiants WHERE statut_rapport IS NULL OR statut_rapport NOT IN ('valider', 'rejeter')) AS en_attente";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: $defaults;
        } catch (Exception $e) {
            error_log("Erreur getAggregatedStats: " . $e->getMessage());
            return $defaults;
        }
    }

    /**
     * Récupère toutes les données pour le tableau de bord de la commission
     *
     * @return array Les données du tableau de bord
     */
    public function getDashboardData()
    {
        $stats = [
            'annee_academique' => null,
            'total_rapports' => 0,
            'taux_validation' => 0,
            'temps_moyen' => 0,
            'en_attente' => 0,
            'rapports_rejetes' => 0,
            'evolution_mensuelle' => [],
            'repartition_statuts' => [],
            'performance_categories' => [],
            'activites_recentes' => [],
            'rapports_details' => [],
            'evaluations_rapports' => [],
            'observations_membres' => [],
            'observation_detail' => []
        ];

        try {
            $stats['annee_academique'] = $this->getAnneeAcademiqueActive();

            // Agrégation unique remplaçant 5 requêtes COUNT individuelles
            $agg = $this->getAggregatedStats();
            $stats['total_rapports'] = (int) ($agg['total_valider'] ?? 0);
            $stats['rapports_valides'] = (int) ($agg['valides_statut'] ?? 0);
            $stats['rapports_rejetes'] = (int) ($agg['rejetes_statut'] ?? 0);
            $stats['en_attente'] = (int) ($agg['en_attente'] ?? 0);

            // Taux de validation
            $totalValider = (int) ($agg['total_valider'] ?? 0);
            $validesValider = (int) ($agg['valides_valider'] ?? 0);
            $stats['taux_validation'] = $totalValider > 0 ? round(($validesValider / $totalValider) * 100, 1) : 0;

            // Réutilisation de la répartition
            $stats['repartition_statuts'] = $this->getRepartitionStatuts();

            $stats['temps_moyen'] = $this->getTempsMoyenTraitement();
            $stats['evolution_mensuelle'] = $this->getEvolutionMensuelle();
            $stats['performance_categories'] = $this->getPerformanceCategories();
            $stats['activites_recentes'] = $this->getActivitesRecentes();
            $stats['rapports_details'] = $this->getRapportsDetails();
            $stats['evaluations_rapports'] = $this->getEvaluationsRapports();
            $stats['observations_membres'] = $this->getObservationMembers();
        } catch (Exception $e) {
            error_log("Erreur dans getDashboardData: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Récupère le nombre total de rapports
     *
     * @return int
     */
    public function getTotalRapports()
    {
        try {
            $query = "SELECT COUNT(DISTINCT id_rapport) as total FROM valider";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Erreur getTotalRapports: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le taux de validation des rapports
     *
     * @return float
     */
    public function getTauxValidation()
    {
        try {
            $query = "SELECT 
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) as valides,
                        COUNT(DISTINCT v.id_rapport) as total
                      FROM valider v";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                return round(($result['valides'] / $result['total']) * 100, 1);
            }
            return 0;
        } catch (Exception $e) {
            error_log("Erreur getTauxValidation: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le temps moyen de traitement
     *
     * @return float
     */
    public function getTempsMoyenTraitement()
    {
        try {
            $dateExpr = $this->rapportDateExpr('r');
            if ($dateExpr === 'NULL') {
                return 0;
            }
            $query = "SELECT AVG(DATEDIFF(v.date_validation, $dateExpr)) as temps_moyen
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      WHERE v.date_validation IS NOT NULL 
                      AND $dateExpr IS NOT NULL";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return round($result['temps_moyen'] ?? 0, 1);
        } catch (Exception $e) {
            error_log("Erreur getTempsMoyenTraitement: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le nombre de rapports en attente
     * Un rapport est "en attente" si son statut_rapport n'est ni 'valider' ni 'rejeter'
     *
     * @return int
     */
    public function getRapportsEnAttente()
    {
        try {
            $query = "SELECT COUNT(*) as en_attente
                      FROM rapport_etudiants
                      WHERE (statut_rapport IS NULL
                         OR statut_rapport NOT IN ('valider', 'rejeter'))";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['en_attente'] ?? 0);
        } catch (Exception $e) {
            error_log("Erreur getRapportsEnAttente: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère l'évolution mensuelle des rapports
     *
     * @return array
     */
    public function getEvolutionMensuelle()
    {
        try {
            $query = "SELECT 
                        DATE_FORMAT(v.date_validation, '%Y-%m') as mois,
                        COUNT(DISTINCT v.id_rapport) as total,
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) as finalises,
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'rejeter' THEN v.id_rapport END) as rejetes
                      FROM valider v
                      WHERE v.date_validation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(v.date_validation, '%Y-%m')
                      ORDER BY mois";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getEvolutionMensuelle: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la répartition par statut
     *
     * @return array
     */
    public function getRepartitionStatuts()
    {
        try {
            // Utiliser statut_rapport comme source de vérité principale
            $query = "SELECT 
                        CASE 
                            WHEN statut_rapport = 'valider' THEN 'valider'
                            WHEN statut_rapport = 'rejeter' THEN 'rejeter'
                            ELSE 'en_attente'
                        END as statut,
                        COUNT(*) as nombre
                      FROM rapport_etudiants
                      GROUP BY 
                        CASE 
                            WHEN statut_rapport = 'valider' THEN 'valider'
                            WHEN statut_rapport = 'rejeter' THEN 'rejeter'
                            ELSE 'en_attente'
                        END";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRepartitionStatuts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la performance par catégorie
     *
     * @return array
     */
    public function getPerformanceCategories()
    {
        try {
            $query = "SELECT 
                        r.theme_rapport as categorie,
                        COUNT(DISTINCT v.id_rapport) as total,
                        COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) as valides,
                        ROUND((COUNT(DISTINCT CASE WHEN v.decision_validation = 'valider' THEN v.id_rapport END) / COUNT(DISTINCT v.id_rapport)) * 100, 1) as taux
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      GROUP BY r.theme_rapport
                      ORDER BY taux DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getPerformanceCategories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les activités récentes
     *
     * @return array
     */
    public function getActivitesRecentes()
    {
        try {
            $titleExpr = $this->rapportTitleExpr('r');
            $dateExpr = $this->rapportDateExpr('r');
            $query = "SELECT 
                        v.id_rapport,
                        $titleExpr as titre,
                        v.decision_validation as statut,
                        COALESCE($dateExpr, v.date_validation) as date_soumission,
                        v.date_validation,
                        e.nom_etu as nom_etudiant,
                        e.prenom_etu as prenom_etudiant,
                        ens.nom_enseignant,
                        ens.prenom_enseignant
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                      LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                      ORDER BY v.date_validation DESC
                      LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getActivitesRecentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails des rapports pour le tableau
     *
     * @return array
     */
    public function getRapportsDetails()
    {
        try {
            $titleExpr = $this->rapportTitleExpr('r');
            $dateExpr = $this->rapportDateExpr('r');
            $query = "SELECT 
                        v.id_rapport,
                        $titleExpr as titre,
                        v.decision_validation as statut,
                        COALESCE($dateExpr, v.date_validation) as date_soumission,
                        v.date_validation,
                        e.nom_etu as nom_etudiant,
                        e.prenom_etu as prenom_etudiant,
                        ens.nom_enseignant,
                        ens.prenom_enseignant,
                        " . ($dateExpr === 'NULL' ? 'NULL' : "DATEDIFF(v.date_validation, $dateExpr)") . " as temps_traitement
                      FROM valider v
                      LEFT JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                      LEFT JOIN etudiants e ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud)
                      LEFT JOIN enseignants ens ON v.id_enseignant = ens.id_enseignant
                      ORDER BY v.date_validation DESC
                      LIMIT 20";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRapportsDetails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les évaluations de rapports avec détails enseignant
     *
     * @return array
     */
    public function getEvaluationsRapports()
    {
        try {
            $titleExpr = $this->rapportTitleExpr('r');
            $stmt = $this->db->query('
                SELECT e.*, 
                       ' . $titleExpr . ' as nom_rapport, 
                       COALESCE(ens.nom_enseignant, u.nom_utilisateur) as nom_enseignant, 
                       COALESCE(ens.prenom_enseignant, \'\') as prenom_enseignant
                FROM evaluations_rapports e
                LEFT JOIN rapport_etudiants r ON e.id_rapport = r.id_rapport
                LEFT JOIN utilisateur u ON e.id_evaluateur = u.id_utilisateur
                LEFT JOIN enseignants ens ON LOWER(ens.mail_enseignant) = LOWER(u.login_utilisateur)
                ORDER BY e.date_evaluation DESC
            ');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getEvaluationsRapports: " . $e->getMessage());
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getObservationMembers(): array
    {
        $rows = [];
        try {
            if ($this->tableExists('evaluations_rapports')) {
                $stmt = $this->db->query(
                    'SELECT CONCAT("u:", er.id_evaluateur) AS member_key,
                            COALESCE(NULLIF(TRIM(CONCAT(COALESCE(en.nom_enseignant, ""), " ", COALESCE(en.prenom_enseignant, ""))), ""), u.nom_utilisateur, CONCAT("Membre #", er.id_evaluateur)) AS membre,
                            0 AS nb_recus, COUNT(*) AS nb_observations,
                            SUM(CASE WHEN er.commentaire IS NOT NULL AND TRIM(er.commentaire) <> "" THEN 1 ELSE 0 END) AS nb_commentaires,
                            MAX(er.date_evaluation) AS derniere_activite
                     FROM evaluations_rapports er
                     LEFT JOIN utilisateur u ON u.id_utilisateur = er.id_evaluateur
                     LEFT JOIN enseignants en ON LOWER(en.mail_enseignant) = LOWER(u.login_utilisateur)
                     GROUP BY er.id_evaluateur, u.nom_utilisateur, en.nom_enseignant, en.prenom_enseignant'
                );
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
            if ($this->tableExists('rendre')) {
                $stmt = $this->db->query(
                    'SELECT CONCAT("e:", rd.id_enseignant) AS member_key,
                            COALESCE(NULLIF(TRIM(CONCAT(COALESCE(en.nom_enseignant, ""), " ", COALESCE(en.prenom_enseignant, ""))), ""), CONCAT("Enseignant #", rd.id_enseignant)) AS membre,
                            COUNT(DISTINCT rd.id_CR) AS nb_recus, 0 AS nb_observations, 0 AS nb_commentaires,
                            MAX(rd.date_env) AS derniere_activite
                     FROM rendre rd
                     LEFT JOIN enseignants en ON en.id_enseignant = rd.id_enseignant
                     GROUP BY rd.id_enseignant, en.nom_enseignant, en.prenom_enseignant'
                );
                $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            }
            usort($rows, static fn(array $a, array $b): int => strcmp((string) ($b['derniere_activite'] ?? ''), (string) ($a['derniere_activite'] ?? '')));
            return $rows;
        } catch (Throwable $e) {
            error_log('DashboardCommissionService::getObservationMembers: ' . $e->getMessage());
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getObservationDetails(string $memberKey): array
    {
        if ($memberKey === '') {
            return [];
        }
        try {
            if (str_starts_with($memberKey, 'u:') && $this->tableExists('evaluations_rapports')) {
                $stmt = $this->db->prepare(
                    'SELECT er.id_evaluation, er.id_rapport, er.decision_evaluation, er.commentaire, er.date_evaluation,
                            r.nom_rapport, r.theme_rapport, e.nom_etu, e.prenom_etu
                     FROM evaluations_rapports er
                     LEFT JOIN rapport_etudiants r ON r.id_rapport = er.id_rapport
                     LEFT JOIN etudiants e ON e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu
                     WHERE er.id_evaluateur = :member ORDER BY er.date_evaluation DESC'
                );
                $stmt->execute([':member' => substr($memberKey, 2)]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
            if (str_starts_with($memberKey, 'e:') && $this->tableExists('rendre')) {
                $stmt = $this->db->prepare(
                    'SELECT rd.id_CR AS id_rapport, cr.nom_CR AS nom_rapport, cr.date_CR AS date_evaluation,
                            NULL AS decision_evaluation, NULL AS commentaire, e.nom_etu, e.prenom_etu
                     FROM rendre rd INNER JOIN compte_rendu cr ON cr.id_CR = rd.id_CR
                     LEFT JOIN etudiants e ON e.num_carte_etud = cr.num_etu OR e.num_ident_etud = cr.num_etu
                     WHERE rd.id_enseignant = :member ORDER BY rd.date_env DESC'
                );
                $stmt->execute([':member' => substr($memberKey, 2)]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Throwable $e) {
            error_log('DashboardCommissionService::getObservationDetails: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * Retourne les données par défaut du tableau de bord (en cas d'erreur)
     *
     * @return array
     */
    public function getDefaultDashboardData()
    {
        return [
            'annee_academique' => null,
            'total_rapports' => 0,
            'taux_validation' => 0,
            'temps_moyen' => 0,
            'en_attente' => 0,
            'rapports_rejetes' => 0,
            'evolution_mensuelle' => [],
            'repartition_statuts' => [],
            'performance_categories' => [],
            'activites_recentes' => [],
            'rapports_details' => [],
            'evaluations_rapports' => [],
            'observations_membres' => [],
            'observation_detail' => []
        ];
    }
}
