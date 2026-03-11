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
                'inscriptionsParNiveau' => $inscriptionsParNiveau
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

            if ($reste_a_payer <= 0) {
                $complete++;
                $montantTotalPerçu += $montant_paye;
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
}
