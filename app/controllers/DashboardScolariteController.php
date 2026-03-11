<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Utilisateur.php";
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . "/../models/Scolarite.php";
require_once __DIR__ . "/../models/Inscription.php";
require_once __DIR__ . '/../Services/DashboardScolariteService.php';

use CheckMaster\Services\DashboardScolariteService;

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
    /** @var DashboardScolariteService */
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
        $this->service = new DashboardScolariteService(Database::getConnection());
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
