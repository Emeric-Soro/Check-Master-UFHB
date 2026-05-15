<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CritereEvaluation.php';
require_once __DIR__ . '/../Services/EvaluationSoutenanceService.php';
require_once __DIR__ . '/../Support/Database.php';
require_once __DIR__ . '/../Services/Document/PvFinalGeneratorService.php';
require_once __DIR__ . '/../utils/PlanningDataUtils.php';
require_once __DIR__ . '/../models/CritereEvaluation.php';
require_once __DIR__ . '/../Services/EvaluationSoutenanceService.php';

use CheckMaster\Services\EvaluationSoutenanceService;
use App\Services\Document\PvFinalGeneratorService;
use App\Utils\PlanningDataUtils;

class EvaluationSoutenanceController
{
    private $service;

    public function __construct()
    {
        $this->service = new EvaluationSoutenanceService();
    }

    /**
     * Récupérer toutes les soutenances programmées pour évaluation
     */
    public function getSoutenancesProgrammeesForView()
    {
        return $this->service->getSoutenancesProgrammeesForView();
    }

    /**
     * Récupérer l'année académique courante
     */
    public function getAnneeAcademiqueCourante()
    {
        return $this->service->getAnneeAcademiqueCourante();
    }

    /**
     * Récupérer les critères d'évaluation avec barèmes pour l'année académique courante
     */
    public function getCriteresEvaluation()
    {
        return $this->service->getCriteresEvaluation();
    }

    /**
     * Récupérer les années académiques disponibles
     */
    public function getAnneesAcademiques()
    {
        return $this->service->getAnneesAcademiques();
    }

    /**
     * Récupérer une évaluation existante
     */
    public function getEvaluationExistante($numEtu)
    {
        return $this->service->getEvaluationExistante($numEtu);
    }

    /**
     * Enregistrer une évaluation de soutenance
     */
    public function enregistrerEvaluation()
    {
        // Récupérer les données POST
        $numEtu = $_POST['num_etu'] ?? null;
        $commentaireGeneral = $_POST['commentaire_general'] ?? '';
        $criteres = $_POST['criteres'] ?? [];
        $idAnneeAcad = $_POST['id_annee_acad'] ?? null;

        return $this->service->enregistrerEvaluation(
            $numEtu ?? '',
            $criteres,
            $commentaireGeneral,
            $idAnneeAcad
        );
    }

    /**
     * Supprimer une évaluation
     */
    public function supprimerEvaluation()
    {
        $numEtu = $_POST['num_etu'] ?? null;

        return $this->service->supprimerEvaluation($numEtu ?? '');
    }

    /**
     * Récupérer les critères d'évaluation pour une année académique spécifique (AJAX)
     */
    public function getCriteresParAnnee()
    {
        $idAnneeAcad = $_GET['id_annee_acad'] ?? null;

        $result = $this->service->getCriteresParAnnee($idAnneeAcad ?? '');

        header('Content-Type: application/json');
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }

    public function getBaremeCriteres()
    {
        $idAnneeAcad = $_GET['id_annee_acad'] ?? null;

        $result = $this->service->getBaremeCriteres();

        header('Content-Type: application/json');
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }

    /**
     * Imprimer les procès-verbaux (PV) de soutenance en PDF - Les 3 annexes dans un seul document
     */
    /**
     * Imprimer les procès-verbaux (PV) de soutenance en PDF - Les 3 annexes dans un seul document
     */
    public function imprimerPV()
    {
        try {
            $numEtu = $_GET['num_etu'] ?? null;
            
            if (!$numEtu) {
                throw new Exception('Numéro étudiant requis');
            }
            
            // Initialiser les services
            $db = new \App\Support\Database();
            $dataUtils = new PlanningDataUtils($db);
            $pdfGenerator = new \App\Services\Document\PdfGeneratorService(
                __DIR__ . '/../../storage/documents',
                __DIR__ . '/../../public/assets/img/logo.png'
            );
            $pvService = new PvFinalGeneratorService($pdfGenerator, $dataUtils);
            
            // Récupérer l'ID de soutenance à partir du numéro étudiant
            $soutenanceId = $this->service->getSoutenanceIdByNumEtu($numEtu);
            if (!$soutenanceId) {
                throw new Exception('Soutenance non trouvée pour cet étudiant');
            }
            
            // Générer le PDF via le service
            $userId = $_SESSION['id_utilisateur'] ?? 0;
            $result = $pvService->generate($soutenanceId, $userId);
            
            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Erreur lors de la génération du PDF');
            }
            
            // Le fichier a été généré, on le télécharge
            $pdfPath = $result['path'];
            if (!file_exists($pdfPath)) {
                throw new Exception('Fichier PDF non trouvé: ' . $pdfPath);
            }
            
            // Télécharger le fichier
            $pdfFilename = 'PV_Soutenance_' . $numEtu . '_' . date('Y-m-d') . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $pdfFilename . '"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            
        } catch (Exception $e) {
            error_log('Erreur imprimerPV: ' . $e->getMessage());
            echo '<h3>Erreur lors de la génération du PDF : ' . htmlspecialchars($e->getMessage()) . '</h3>';
        }
    }
}
?>
