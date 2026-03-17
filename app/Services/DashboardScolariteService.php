<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . "/../models/Scolarite.php";
require_once __DIR__ . "/../models/Inscription.php";
require_once __DIR__ . '/../utils/AcademicYear.php';

use Etudiant;
use Scolarite;
use Inscription;
use PDO;
use PDOException;

/**
 * Service métier du tableau de bord de la scolarité
 *
 * Contient toute la logique métier et les requêtes de données pour le tableau de bord de la scolarité :
 * - Statistiques des étudiants
 * - Nouvelles inscriptions
 * - Statistiques des réclamations
 * - Statistiques des paiements
 * - Inscriptions par niveau d'étude
 */
class DashboardScolariteService
{
    /** @var PDO */
    private $db;

    /** @var Etudiant */
    private $etudiant;

    /** @var Scolarite */
    private $scolarite;

    /** @var Inscription */
    private $inscription;

    /**
     * Constructeur du service
     *
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->etudiant = new Etudiant($db);
        $this->scolarite = new Scolarite($db);
        $this->inscription = new Inscription($db);
    }

    private function getSelectedYearId(): ?int
    {
        return \AcademicYear::getSelectedIdFromSession();
    }

    /**
     * Récupère les données pour le tableau de bord de la scolarité
     *
     * @return array Les données du tableau de bord contenant 'stats' et 'inscriptionsParNiveau'
     */
    public function getDashboardData()
    {
        $stats = [
            'etudiants' => 0,
            'nouvelles_inscriptions' => 0,
            'reclamations_en_attente' => 0,
            'reclamations_resolues' => 0,
            'paiements_complets' => 0,
            'paiements_partiels' => 0,
            'montant_total_paiements' => 0,
            'paiements_valides' => 0,
            'paiements_retard' => 0,
            'montant_percu' => 0,
            'montant_attente' => 0,
            'reclamations_total' => 0,
            'reclamations_rejetees' => 0
        ];

        try {
            $selectedYearId = $this->getSelectedYearId();

            // Nombre total d'inscriptions actives (étudiants inscrits)
            $stats['etudiants'] = $this->inscription->countInscriptions($selectedYearId);

            // Nouvelles inscriptions (dernière semaine)
            $stats['nouvelles_inscriptions'] = $this->inscription->countNouvellesInscriptions(7, $selectedYearId);

            // Statistiques des réclamations
            $stats = $this->loadReclamationStats($stats);

            // Statistiques des paiements
            $stats = $this->loadPaiementStats($stats, $selectedYearId);

            // Données pour le graphique des inscriptions par niveau d'étude
            $inscriptionsParNiveau = $this->inscription->getInscriptionsParNiveau($selectedYearId);

            return [
                'stats' => $stats,
                'inscriptionsParNiveau' => $inscriptionsParNiveau,
                'nouvelles_inscriptions_detail' => $this->getNouvellesInscriptionsDetail($selectedYearId),
                'paiements_en_attente_detail' => $this->getPaiementsEnAttenteDetail($selectedYearId),
                'reclamations_recentes_detail' => $this->getReclamationsRecentesDetail(),
            ];

        } catch (PDOException $e) {
            error_log("Erreur de base de données : " . $e->getMessage());
            return [
                'stats' => $stats,
                'inscriptionsParNiveau' => [],
                'error' => "Une erreur est survenue lors de la récupération des données"
            ];
        }
    }

    /**
     * Charge les statistiques des réclamations
     *
     * @param array $stats Tableau de statistiques à compléter
     * @return array Tableau de statistiques mis à jour
     */
    private function loadReclamationStats(array $stats)
    {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut_reclamation = 'en attente' THEN 1 ELSE 0 END) as en_attente,
                    SUM(CASE WHEN statut_reclamation = 'résolue' OR statut_reclamation = 'traitée' THEN 1 ELSE 0 END) as resolues,
                    SUM(CASE WHEN statut_reclamation = 'rejeté' OR statut_reclamation = 'rejetée' THEN 1 ELSE 0 END) as rejetees
                  FROM reclamations";
        $stmt = $this->db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['reclamations_total'] = $result['total'] ?? 0;
        $stats['reclamations_en_attente'] = $result['en_attente'] ?? 0;
        $stats['reclamations_resolues'] = $result['resolues'] ?? 0;
        $stats['reclamations_rejetees'] = $result['rejetees'] ?? 0;

        return $stats;
    }

    /**
     * Charge les statistiques des paiements à partir des étudiants inscrits
     *
     * @param array $stats Tableau de statistiques à compléter
     * @return array Tableau de statistiques mis à jour
     */
    private function loadPaiementStats(array $stats, ?int $selectedYearId = null)
    {
        $etudiantsInscrits = $this->scolarite->getEtudiantsInscrits($selectedYearId);
        $complete = 0;
        $partial = 0;
        $montantTotalPerçu = 0;
        $montantEnAttente = 0;

        foreach ($etudiantsInscrits as $etudiant) {
            $reste_a_payer = isset($etudiant['reste_a_payer']) ? floatval($etudiant['reste_a_payer']) : 0;
            $montant_paye = isset($etudiant['montant_paye']) ? floatval($etudiant['montant_paye']) : 0;

            // Ajouter le montant versé, peu importe si paiement complet ou partiel
            $montantTotalPerçu += $montant_paye;

            if ($reste_a_payer <= 0) {
                $complete++;
            } else {
                $partial++;
                $montantEnAttente += $reste_a_payer;
            }
        }

        $stats['paiements_complets'] = $complete;
        $stats['paiements_partiels'] = $partial;
        $stats['montant_percu'] = $montantTotalPerçu;
        $stats['montant_attente'] = $montantEnAttente;
        $stats['montant_total_paiements'] = $montantTotalPerçu + $montantEnAttente;

        return $stats;
    }

    private function getNouvellesInscriptionsDetail(?int $selectedYearId): array
    {
        try {
            $yearCond = $selectedYearId ? 'AND i.id_annee_acad = :annee' : '';
            $params = [];
            if ($selectedYearId) {
                $params[':annee'] = $selectedYearId;
            }
            $sql = "SELECT i.num_carte_etud AS num_etu,
                         e.nom_etu AS nom_etudiant,
                         e.prenom_etu AS prenom_etudiant,
                         i.id_niv_etude,
                         MIN(i.date_inscription) as date_inscription,
                         SUM(i.montant_verser) as montant_verser
                    FROM inscriptions i
                    JOIN etudiants e ON e.num_ident_etud = i.num_carte_etud
                    WHERE i.date_inscription >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    $yearCond
                      GROUP BY i.num_carte_etud, i.id_annee_acad, e.nom_etu, e.prenom_etu, i.id_niv_etude
                    ORDER BY date_inscription DESC
                    LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getPaiementsEnAttenteDetail(?int $selectedYearId): array
    {
        try {
            $yearCond = $selectedYearId ? 'AND i.id_annee_acad = :annee' : '';
            $params = [];
            if ($selectedYearId) {
                $params[':annee'] = $selectedYearId;
            }
            $sql = "SELECT i.num_carte_etud AS num_etu,
                      e.nom_etu AS nom_etudiant,
                      e.prenom_etu AS prenom_etudiant,
                      i.id_niv_etude,
                      i.montant_verser,
                      i.solde AS reste_a_payer,
                      i.date_inscription
                    FROM inscriptions i
                    JOIN etudiants e ON e.num_ident_etud = i.num_carte_etud
                    WHERE i.solde > 0
                    $yearCond
                  ORDER BY i.date_inscription ASC, i.num_carte_etud ASC, i.num_versement ASC
                  LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getReclamationsRecentesDetail(): array
    {
        try {
            $sql = "SELECT r.objet_reclamation,
                           r.date_creation AS date_reclamation,
                           e.nom_etu AS nom_etudiant,
                           e.prenom_etu AS prenom_etudiant,
                           COALESCE(
                               sr.libelle_statut_reclamation,
                               CASE r.statut_reclamation
                                   WHEN 1 THEN 'En attente'
                                   WHEN 2 THEN 'En cours'
                                   WHEN 3 THEN 'Résolue'
                                   WHEN 4 THEN 'Rejetée'
                                   ELSE CAST(r.statut_reclamation AS CHAR)
                               END
                              ) as libelle_statut,
                              r.statut_reclamation as statut_reclamation
                    FROM reclamations r
                    LEFT JOIN etudiants e ON e.num_ident_etud = r.num_carte_etud
                    LEFT JOIN statut_reclamation sr ON sr.id_statut_reclamation = r.statut_reclamation
                    ORDER BY r.date_creation DESC
                    LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
