<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/DashboardCommissionService.php';

use CheckMaster\Services\DashboardCommissionService;

/**
 * Contrôleur du tableau de bord de la commission
 * 
 * Ce contrôleur gère l'affichage des statistiques et des données du tableau de bord de la commission :
 * - Statistiques des rapports
 * - Taux de validation
 * - Temps moyen de traitement
 * - Rapports en attente
 * - Activités récentes
 * - Graphiques d'évolution
 */
class DashboardCommissionController
{
    /** @var DashboardCommissionService */
    private $service;

    /**
     * Constructeur du contrôleur
     * Initialise le service métier
     */
    public function __construct()
    {
        $this->service = new DashboardCommissionService(Database::getConnection());
    }

    /**
     * Récupère les données pour le tableau de bord de la commission
     * @return array Les données du tableau de bord
     */
    public function getDashboardData()
    {
        return $this->service->getDashboardData();
    }

    /**
     * Affiche le tableau de bord
     */
    public function index()
    {
        try {
            $dashboardData = $this->service->getDashboardData();
            // Passer les données à la vue
            global $stats;
            $stats = $dashboardData;
        } catch (Exception $e) {
            error_log("Erreur dans index: " . $e->getMessage());
            // En cas d'erreur, utiliser des données par défaut
            global $stats;
            $stats = $this->service->getDefaultDashboardData();
        }
    }
}