<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/FicheFinanciereService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\FicheFinanciereService;

class FicheFinanciereController
{
    private FicheFinanciereService $service;

    public function __construct()
    {
        $this->service = new FicheFinanciereService(Database::getConnection());
    }

    /**
     * Vue d'ensemble avec sélecteur d'année.
     */
    public function index(): void
    {
        // Permission
        if (!canView('gestion_scolarite')) {
            $_SESSION['error_message'] = "Accès non autorisé.";
            header('Location: layout.php?page=dashboard');
            exit;
        }

        $idAnnee = isset($_GET['id_annee_acad']) && $_GET['id_annee_acad'] !== ''
            ? (int) $_GET['id_annee_acad']
            : \AcademicYear::getSelectedIdFromSession();

        // Liste des années
        $listeAnnees = $this->service->getAllAnnees();

        // Si aucune année sélectionnée, prendre la première
        if ($idAnnee === null && !empty($listeAnnees)) {
            $idAnnee = (int) $listeAnnees[0]['id_annee_acad'];
        }

        $recap = $idAnnee ? $this->service->getRecapAnnee($idAnnee) : [];
        $etudiants = $idAnnee ? $this->service->getEtudiantsInscritsRecap($idAnnee) : [];

        $GLOBALS['idAnnee'] = $idAnnee;
        $GLOBALS['listeAnnees'] = $listeAnnees;
        $GLOBALS['recap'] = $recap;
        $GLOBALS['etudiants'] = $etudiants;
    }

    /**
     * Détail d'un étudiant pour une année.
     */
    public function detailEtudiant(): void
    {
        if (!canView('gestion_scolarite')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }

        $numEtu = (string) ($_GET['num_etu'] ?? '');
        $idAnnee = (int) ($_GET['id_annee_acad'] ?? \AcademicYear::getSelectedIdFromSession());

        if ($numEtu === '' || $idAnnee <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']);
            exit;
        }

        $detail = $this->service->getDetailEtudiant($numEtu, $idAnnee);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => $detail !== null, 'data' => $detail]);
        exit;
    }
}
