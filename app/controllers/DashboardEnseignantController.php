<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Enseignant.php";
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . "/../models/NiveauEtude.php";

/**
 * Contrôleur du tableau de bord enseignant
 * 
 * Ce contrôleur gère l'affichage des statistiques et des données du tableau de bord enseignant :
 * - Statistiques des étudiants encadrés
 * - Activités récentes
 * - Calendrier et échéances
 */
class DashboardEnseignantController
{
    /** @var Enseignant */
    private $enseignant;

    /** @var Etudiant */
    private $etudiant;

    /** @var NiveauEtude */
    private $niveauEtude;

    /** @var string */
    private $baseViewPath;

    /**
     * Constructeur du contrôleur
     * Initialise les modèles et les chemins nécessaires
     */
    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->enseignant = new Enseignant(Database::getConnection());
        $this->etudiant = new Etudiant(Database::getConnection());
        $this->niveauEtude = new NiveauEtude(Database::getConnection());
    }

    /**
     * Point d'entrée principal du contrôleur
     * Affiche le tableau de bord enseignant avec toutes les statistiques
     */
    public function index()
    {
        $this->getGlobalStats();
    }

    /**
     * Récupère les statistiques globales
     */
    private function getGlobalStats()
    {
        // Valeurs par défaut
        $GLOBALS['total_etudiants'] = 0;
        $GLOBALS['total_ues']       = 0;
        $GLOBALS['total_ecues']     = 0;
        $GLOBALS['mes_cours']       = [];

        try {
            $enseignantData = $this->enseignant->getEnseignantByLogin($_SESSION['login_utilisateur'] ?? '');
            if (!$enseignantData) {
                return;
            }

            $etudiants = $this->etudiant->getAllListeEtudiants();
            $GLOBALS['total_etudiants'] = count($etudiants);

        } catch (Exception $e) {
            error_log('DashboardEnseignantController::getGlobalStats - ' . $e->getMessage());
        }
    }
}