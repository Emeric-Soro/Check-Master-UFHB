<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/ArchivesDossiersSoutenanceService.php';

use CheckMaster\Services\ArchivesDossiersSoutenanceService;

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
    private $service;
    public function __construct()
    {
        $db = Database::getConnection();
        $this->service = new ArchivesDossiersSoutenanceService($db);
    }

    /**
     * Récupère les détails d'un rapport spécifique
     * @param int $idRapport
     * @return array|null
     */
    public function getRapportDetails($idRapport)
    {
        return $this->service->getRapportDetails($idRapport);
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
                'etudiant' => $_GET['etudiant'] ?? '',
                'date_debut' => $_GET['date_debut'] ?? '',
                'date_fin' => $_GET['date_fin'] ?? ''
            ];
            $archives = [
                'rapports_archives' => $this->service->getRapportsArchives($filtres),
                'statistiques' => $this->service->getStatistiquesArchives(),
                'filtres' => $filtres
            ];
            // Passer les données à la vue
            $GLOBALS['archives'] = $archives;
        } catch (Exception $e) {
            error_log("Erreur dans index: " . $e->getMessage());
            // En cas d'erreur, utiliser des données par défaut
            $GLOBALS['archives'] = [
                'rapports_archives' => [],
                'statistiques' => [],
                'filtres' => []
            ];
        }
    }
}