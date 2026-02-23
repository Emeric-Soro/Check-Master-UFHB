<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/DashboardService.php';

use CheckMaster\Services\DashboardService;

/**
 * Contrôleur du tableau de bord
 * 
 * Ce contrôleur gère l'affichage des statistiques et des données du tableau de bord :
 * - Statistiques des utilisateurs
 * - Statistiques des étudiants
 * - Statistiques des enseignants
 * - Statistiques du personnel administratif
 * - Activités récentes
 * - Données d'évolution
 */
class DashboardController
{
    /** @var DashboardService */
    private $service;
    /** @var string */
    private $baseViewPath;
    /**
     * Constructeur du contrôleur
     * Initialise le service métier et les chemins nécessaires
     */
    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->service = new DashboardService(Database::getConnection());
    }

    /**
     * Point d'entrée principal du contrôleur
     * Affiche le tableau de bord avec toutes les statistiques
     */
    public function index()
    {
        // Audit logging pour l'accès au tableau de bord
        $this->service->logAccess($_SESSION['id_utilisateur']);
        
        // Récupération et injection de toutes les statistiques dans $GLOBALS
        $this->service->populateDashboardGlobals();
       
    }

    /**
     * Récupère les données pour le tableau de bord de la scolarité
     * @return array Les données du tableau de bord
     */
    public function getDashboardData()
    {
        return $this->service->getDashboardData();
    }
}