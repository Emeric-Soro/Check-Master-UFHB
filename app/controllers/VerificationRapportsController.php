<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/VerificationRapportsService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class VerificationRapportsController
{

    /** @var VerificationRapportsService */
    private $service;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->service = new VerificationRapportsService($pdo);
    }

    public function index()
    {
        try {
            $data = $this->service->getIndexData();

            // Passer les données à la vue via les variables globales
            $GLOBALS['rapports'] = $data['rapports'];
            $GLOBALS['nbRapports'] = $data['nbRapports'];
            $GLOBALS['statsRapports'] = $data['statsRapports'];

        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des rapports: " . $e->getMessage());
            $GLOBALS['rapports'] = [];
            $GLOBALS['nbRapports'] = 0;
            $GLOBALS['statsRapports'] = [];
        }
    }

    /**
     * Valider un rapport (approuver)
     */
    public function validerRapport()
    {
        if (!canEdit('verification_candidatures_soutenance')) {
            return ['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."];
        }
        $id_rapport = $_POST['id_rapport'] ?? 0;
        $commentaire = $_POST['commentaire'] ?? '';

        return $this->service->validerRapport($id_rapport, $commentaire);
    }

    /**
     * Rejeter un rapport (désapprouver)
     */
    public function rejeterRapport()
    {
        if (!canEdit('verification_candidatures_soutenance')) {
            return ['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."];
        }
        $id_rapport = $_POST['id_rapport'] ?? 0;
        $commentaire = $_POST['commentaire'] ?? '';

        return $this->service->rejeterRapport($id_rapport, $commentaire);
    }

    /**
     * Récupérer les détails d'un rapport
     */
    public function getRapportDetail($id_rapport)
    {
        return $this->service->getRapportDetail($id_rapport);
    }

    /**
     * Récupérer les décisions d'évaluation d'un rapport
     */
    public function getDecisionsEvaluation($id_rapport)
    {
        return $this->service->getDecisionsEvaluation($id_rapport);
    }
}
