<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Utilisateur.php";
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . "/../models/Scolarite.php";
require_once __DIR__ . "/../models/Inscription.php";

/**
 * Contrôleur du tableau de bord de la scolarité
 * 
 * Ce contrôleur gère l'affichage des statistiques et des données du tableau de bord de la scolarité :
 * - Statistiques des étudiants
 * - Nouvelles inscriptions
 * - Notes à valider
 * - Paiements en attente
 * - Activités récentes
 */
class DashboardScolariteController
{
    /** @var Etudiant */
    private $etudiant;

    /** @var Scolarite */
    private $scolarite;

    /** @var Inscription */
    private $inscription;

    /** @var string */
    private $baseViewPath;

    /**
     * Constructeur du contrôleur
     * Initialise les modèles et les chemins nécessaires
     */
    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->etudiant = new Etudiant(Database::getConnection());
        $this->scolarite = new Scolarite(Database::getConnection());
        $this->inscription = new Inscription(Database::getConnection());
    }

    /**
     * Récupère les données pour le tableau de bord de la scolarité
     * @return array Les données du tableau de bord
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
            // Nombre total d'inscriptions actives (étudiants inscrits)
            $stats['etudiants'] = $this->inscription->countInscriptions();

            // Nouvelles inscriptions (dernière semaine)
            $stats['nouvelles_inscriptions'] = $this->inscription->countNouvellesInscriptions(7);

            // Statistiques des réclamations
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN statut_reclamation = 'en attente' THEN 1 ELSE 0 END) as en_attente,
                        SUM(CASE WHEN statut_reclamation = 'résolue' OR statut_reclamation = 'traitée' THEN 1 ELSE 0 END) as resolues,
                        SUM(CASE WHEN statut_reclamation = 'rejeté' OR statut_reclamation = 'rejetée' THEN 1 ELSE 0 END) as rejetees
                      FROM reclamations";
            $stmt = Database::getConnection()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['reclamations_total'] = $result['total'] ?? 0;
            $stats['reclamations_en_attente'] = $result['en_attente'] ?? 0;
            $stats['reclamations_resolues'] = $result['resolues'] ?? 0;
            $stats['reclamations_rejetees'] = $result['rejetees'] ?? 0;

            // Statistiques des paiements (même logique que gestion_scolarite)
            $etudiantsInscrits = $this->scolarite->getEtudiantsInscrits();
            $totalEtudiants = count($etudiantsInscrits);
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

            // Données pour le graphique des inscriptions par niveau d'étude
            $inscriptionsParNiveau = $this->inscription->getInscriptionsParNiveau();

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
}