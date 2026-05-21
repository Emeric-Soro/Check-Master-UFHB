<?php
require_once __DIR__ . '/../Services/EditionBulletinService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\EditionBulletinService;

class EditionBulletinController
{
    private $service;

    public function __construct()
    {
        $this->service = new EditionBulletinService();
    }

    public function index()
    {
        // This method is called when accessing ?page=edition_bulletin
        // We pass data to the view via globals (as done in other controllers)
        $data = $this->service->getIndexData();
        $GLOBALS['etudiants'] = $data['etudiants'];
        $GLOBALS['anneesAcademiques'] = $data['anneesAcademiques'];
        $GLOBALS['selectedYearId'] = $data['selectedYearId'] ?? null;
        // Ne pas inclure la vue ici, le layout s'en charge
    }

    public function preview()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $numEtu = $_GET['num_etu'] ?? null;
            if (!$numEtu) {
                $this->outputJson(['success' => false, 'message' => 'Numéro étudiant requis']);
                return;
            }

            if (!$this->service->estEligiblePourBulletin($numEtu)) {
                $this->outputJson(['success' => false, 'message' => 'Soutenance introuvable pour cet étudiant']);
                return;
            }

            // Generate preview content (HTML)
            $contenu = $this->service->genererContenuBulletin($numEtu);
            if (empty($contenu)) {
                $this->outputJson(['success' => false, 'message' => 'Impossible de générer le aperçu']);
                return;
            }

            $this->outputJson([
                'success' => true,
                'content' => $contenu
            ]);
        } else {
            $this->outputJson(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    public function generate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numEtu = $_POST['num_etu'] ?? null;
            if (!$numEtu) {
                $this->outputJson(['success' => false, 'message' => 'Numéro étudiant requis']);
                return;
            }

            $result = $this->service->genererBulletin($numEtu);
            $this->outputJson($result);
        } else {
            $this->outputJson(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    public function generate_batch()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Expecting a list of num_etu in POST['etudiants'] (as JSON or comma-separated)
            $etudiants = $_POST['etudiants'] ?? [];
            if (is_string($etudiants)) {
                $etudiants = explode(',', $etudiants);
            }
            if (!is_array($etudiants) || empty($etudiants)) {
                $this->outputJson(['success' => false, 'message' => 'Liste d\'étudiants requise']);
                return;
            }

            $results = [];
            foreach ($etudiants as $numEtu) {
                $numEtu = trim($numEtu);
                if ($numEtu === '') {
                    continue;
                }
                $result = $this->service->genererBulletin($numEtu);
                $results[$numEtu] = $result;
            }

            $this->outputJson([
                'success' => true,
                'results' => $results
            ]);
        } else {
            $this->outputJson(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    public function publish()
    {
        // In our current design, generating a bulletin is already publishing it.
        // We could have a separate step for publication (e.g., changing status to published).
        // For now, we'll treat publish as generate (or we could just return success if already generated).
        // But the PRD mentions publish as a separate action.
        // Let's implement publish as: if the bulletin exists and is generated, mark it as published (we don't have a published flag, so we'll just return success).
        // Alternatively, we could have a separate table for published bulletins, but we are not allowed to change the database.
        // So we'll consider that generating a bulletin is the same as publishing it.
        // We'll redirect to generate for now.
        $this->generate();
    }

    public function download()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->outputJson(['success' => false, 'message' => 'ID de soutenance requis']);
                return;
            }

            $bulletin = $this->service->getPvFinalBySoutenanceId((string) $id);
            if (!$bulletin) {
                $this->outputJson(['success' => false, 'message' => 'PV final non trouvé']);
                return;
            }

            header('Location: ?page=docviewer&type=pv_final&id=' . urlencode((string) $id) . '&action=download');
            exit;
        } else {
            $this->outputJson(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    public function history()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $numEtu = $_GET['num_etu'] ?? null;
            if (!$numEtu) {
                $this->outputJson(['success' => false, 'message' => 'Numéro étudiant requis']);
                return;
            }

            $historique = $this->service->getHistoriqueBulletins($numEtu);
            $this->outputJson([
                'success' => true,
                'historique' => $historique
            ]);
        } else {
            $this->outputJson(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    private function outputJson(array $data)
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }
}
