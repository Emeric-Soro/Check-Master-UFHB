<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CritereEvaluation.php';
require_once __DIR__ . '/../Services/EvaluationSoutenanceService.php';

use CheckMaster\Services\EvaluationSoutenanceService;

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

    /**
     * Imprimer les procès-verbaux (PV) de soutenance en PDF - Les 3 annexes dans un seul document
     */
    public function imprimerPV()
    {
        try {
            require_once __DIR__ . '/../../vendor/autoload.php';

            $numEtu = $_GET['num_etu'] ?? null;
            // Moyenne M1 récupérée automatiquement depuis le système (dossier académique).
            $moyenneMaster1Float = null;

            // Fetch all data from the service
            $pvData = $this->service->getDonneesPV($numEtu ?? '', $moyenneMaster1Float);

            $dataAnnexe1 = $pvData['annexe1'];
            $dataAnnexe2 = $pvData['annexe2'];
            $dataAnnexe3 = $pvData['annexe3'];

            // ========== ANNEXE 1 - Soutenance de Mémoire ==========
            ob_start();
            $data = $dataAnnexe1; // Pour les templates
            include __DIR__ . '/../../ressources/views/pv_soutenance/annexe1.php';
            $htmlAnnexe1 = ob_get_clean();

            // ========== ANNEXE 2 - PV Jury ==========
            ob_start();
            $data = $dataAnnexe2; // Pour les templates
            include __DIR__ . '/../../ressources/views/pv_soutenance/annexe2.php';
            $htmlAnnexe2 = ob_get_clean();

            // ========== ANNEXE 3 - PV Jury FC ==========
            ob_start();
            $data = $dataAnnexe3; // Pour les templates
            include __DIR__ . '/../../ressources/views/pv_soutenance/annexe3.php';
            $htmlAnnexe3 = ob_get_clean();

            // ========== COMBINER LES 3 ANNEXES DANS UN SEUL PDF ==========
            // Structure HTML unique avec sauts de page CSS
            $htmlComplet = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV Soutenance</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 25mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            background-color: #fff;
            padding: 0 15px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
' . $htmlAnnexe1 . '
<div class="page-break"></div>
' . $htmlAnnexe2 . '
<div class="page-break"></div>
' . $htmlAnnexe3 . '
</body>
</html>';

            // Générer le PDF
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('enable_font_subsetting', true);
            // Disable image loading to avoid GD requirement
            $options->set('enablePhp', false);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($htmlComplet);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdfFilename = 'PV_Soutenance_' . $numEtu . '_' . date('Y-m-d') . '.pdf';

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $pdfFilename . '"');
            echo $dompdf->output();

        } catch (Exception $e) {
            error_log('Erreur imprimerPV: ' . $e->getMessage());
            echo '<h3>Erreur lors de la génération du PDF : ' . htmlspecialchars($e->getMessage()) . '</h3>';
        }
    }
}
?>
