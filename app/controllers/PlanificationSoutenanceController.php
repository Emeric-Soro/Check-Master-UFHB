<?php

require_once __DIR__ . '/../Services/PlanificationSoutenanceService.php';

use CheckMaster\Services\PlanificationSoutenanceService;

class PlanificationSoutenanceController
{
    private $service;

    public function __construct()
    {
        $this->service = new PlanificationSoutenanceService();
    }

    /**
     * Récupérer tous les étudiants qui ont une attribution de jury
     */
    public function getEtudiantsAvecJuryForView()
    {
        return $this->service->getEtudiantsAvecJury();
    }

    /**
     * Récupérer les étudiants disponibles pour nouvelle planification (non encore planifiés)
     */
    public function getEtudiantsDisponiblesForView()
    {
        return $this->service->getEtudiantsDisponibles();
    }

    /**
     * Récupérer toutes les salles disponibles
     */
    public function getSallesForView()
    {
        return $this->service->getSalles();
    }

    /**
     * Récupérer toutes les planifications pour affichage
     */
    public function getPlanificationsForView()
    {
        return $this->service->getPlanifications();
    }

    /**
     * Planifier une soutenance (mettre à jour salle, date, heure) - Version POST
     */
    public function planifierSoutenance()
    {
        $idProgrammation = $_POST['id_programmation'] ?? null;
        $idSalle = $_POST['id_salle'] ?? null;
        $dateSoutenance = $_POST['date_soutenance'] ?? null;
        $heureSoutenance = $_POST['heure_soutenance'] ?? null;
        $editId = $_POST['edit_id'] ?? null;

        return $this->service->planifier($idProgrammation, $idSalle, $dateSoutenance, $heureSoutenance, $editId);
    }

    /**
     * Supprimer une planification (remettre salle, date, heure à NULL) - Version POST
     */
    public function supprimerPlanification()
    {
        $id = $_POST['id_programmation'] ?? null;

        return $this->service->supprimer($id);
    }

    /**
     * Récupérer une planification par ID pour modification
     */
    public function getPlanification()
    {
        $id = $_GET['id'] ?? null;
        $result = $this->service->getPlanificationById($id);

        header('Content-Type: application/json');

        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }
}
?>
