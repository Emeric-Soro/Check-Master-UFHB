<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/DashboardEnseignantService.php';

use CheckMaster\Services\DashboardEnseignantService;

/**
 * Contrôleur du tableau de bord enseignant
 * 
 * Ce contrôleur gère l'affichage des statistiques et des données du tableau de bord enseignant :
 * - Statistiques des cours enseignés
 * - Statistiques des évaluations
 * - Statistiques des étudiants encadrés
 * - Activités récentes
 * - Calendrier et échéances
 */
class DashboardEnseignantController
{
    /** @var DashboardEnseignantService */
    private $service;
    /** @var string */
    private $baseViewPath;

    /**
     * Constructeur du contrôleur
     * Initialise le service et les chemins nécessaires
     */
    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->service = new DashboardEnseignantService(Database::getConnection());
    }

    /**
     * Point d'entrée principal du contrôleur
     * Affiche le tableau de bord enseignant avec toutes les statistiques
     */
    public function index()
    {
        // Délégation au service pour peupler les $GLOBALS
        $this->service->populateDashboardGlobals($_SESSION['login_utilisateur']);
    }
}