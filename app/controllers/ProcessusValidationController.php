<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/ProcessusValidationService.php';

use CheckMaster\Services\ProcessusValidationService;

/**
 * Contrôleur pour le processus de validation des rapports
 *
 * Délègue toute la logique métier à ProcessusValidationService.
 * Conserve la même API publique pour la rétrocompatibilité.
 */
class ProcessusValidationController
{
    /** @var ProcessusValidationService */
    private $service;
    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->service = new ProcessusValidationService($pdo);
    }

    /**
     * Récupère les statistiques pour le tableau de bord
     */
    public function getStatistiques()
    {
        return $this->service->getStatistiques();
    }

    /**
     * Récupère tous les rapports approuvés avec leurs évaluations
     */
    public function getRapportsAvecEvaluations()
    {
        return $this->service->getRapportsAvecEvaluations();
    }

    /**
     * Récupère la liste des membres de la commission
     */
    public function getMembresCommission()
    {
        return $this->service->getMembresCommission();
    }

    /**
     * Récupère les données complètes pour la page
     */
    public function getDonneesPage()
    {
        return $this->service->getDonneesPage();
    }

    /**
     * Vérifie si un ID enseignant existe dans la table enseignants
     */
    public function verifierIdEnseignant($id_enseignant)
    {
        return $this->service->verifierIdEnseignant($id_enseignant);
    }

    public function resolveEnseignantIdFromSession(array $session)
    {
        return $this->service->resolveEnseignantIdFromSession($session);
    }

    /**
     * Finalise la décision pour un rapport
     */
    public function finaliserRapport($id_rapport, $id_enseignant, $commentaire = null)
    {
        return $this->service->finaliserRapport($id_rapport, $id_enseignant, $commentaire);
    }
}
