<?php

require_once __DIR__ . '/../Services/RedactionCompteRenduService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\RedactionCompteRenduService;

class RedactionCompteRenduController {

    private $service;

    public function __construct() {
        $this->service = new RedactionCompteRenduService();
    }

    public function index() {
        $data = $this->service->getIndexData();
        $GLOBALS['rapports_valides'] = $data['rapports_valides'];
        $GLOBALS['enseignants']      = $data['enseignants'];
        $GLOBALS['editing_compte_rendu'] = null;

        $idCR = isset($_GET['id_CR']) ? (int) $_GET['id_CR'] : 0;
        if ($idCR > 0) {
            $GLOBALS['editing_compte_rendu'] = $this->service->getEditableCompteRenduData($idCR);
        }
        // Ne pas inclure la vue ici, le layout s'en charge
    }

    public function enregistrer() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!canCreate('redaction_compte_rendu') && !canEdit('redaction_compte_rendu')) {
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                    exit;
                }
                $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                header('Location: layout.php?page=access_denied');
                exit;
            }
            $reportsPayload = $this->decodeReportsPayload($_POST['cm_reports_payload'] ?? '');
            $rapportIds = isset($_POST['rapports']) ? (array) $_POST['rapports'] : [];
            $encadrants = is_array($_POST['encadrant_pedagogique'] ?? null) ? $_POST['encadrant_pedagogique'] : [];
            $directeurs = is_array($_POST['directeur_memoire'] ?? null) ? $_POST['directeur_memoire'] : [];
            $numEtu = $_POST['num_etu'] ?? null;

            foreach ($reportsPayload as $payloadRow) {
                $idRapport = (int) ($payloadRow['id_rapport'] ?? 0);
                if ($idRapport <= 0) {
                    continue;
                }
                $rapportIds[] = $idRapport;
                if (!isset($encadrants[$idRapport]) && isset($payloadRow['encadrant'])) {
                    $encadrants[$idRapport] = (string) $payloadRow['encadrant'];
                }
                if (!isset($directeurs[$idRapport]) && isset($payloadRow['directeur'])) {
                    $directeurs[$idRapport] = (string) $payloadRow['directeur'];
                }
                if ((empty($numEtu) || trim((string) $numEtu) === '') && !empty($payloadRow['num_etu'])) {
                    $numEtu = (string) $payloadRow['num_etu'];
                }
            }

            $result = $this->service->enregistrer([
                'num_etu'               => $numEtu,
                'nom_CR'                => $_POST['nom_CR'] ?? '',
                'contenu_CR'            => $_POST['contenu_CR'] ?? '',
                'rapports'              => $rapportIds,
                'encadrant_pedagogique' => $encadrants,
                'directeur_memoire'     => $directeurs,
                'submit_action'         => $_POST['submit_action'] ?? 'save',
            ]);

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            if (!empty($result['success'])) {
                $_SESSION['success'] = (string) ($result['message'] ?? '');
            } else {
                $_SESSION['error'] = (string) ($result['message'] ?? '');
            }

            $redirectId = (int) ($result['id_CR'] ?? ($_POST['id_CR_edit'] ?? 0));
            $redirect = '?page=redaction_compte_rendu' . ($redirectId > 0 ? '&id_CR=' . $redirectId : '');

            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => (bool) ($result['success'] ?? false),
                    'message' => (string) ($result['message'] ?? ''),
                    'redirect' => $redirect,
                ]);
                exit;
            }

            header('Location: layout.php?page=redaction_compte_rendu' . ($redirectId > 0 ? '&id_CR=' . $redirectId : ''));
            exit;
        }
    }

    public function exporterPDF() {
        try {
            $contenu = $_POST['contenu_CR'] ?? '';
            $nom_CR  = $_POST['nom_CR'] ?? 'compte_rendu';

            $result  = $this->service->exporterPdf($contenu, $nom_CR);

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $result['filename'] . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . strlen($result['pdf']));

            echo $result['pdf'];
            exit;
        } catch (\Exception $e) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h1>Erreur lors de la génération du PDF</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><a href="javascript:history.back()">Retour</a></p>';
            exit;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeReportsPayload($rawPayload): array
    {
        if (!is_string($rawPayload) || trim($rawPayload) === '') {
            return [];
        }

        $decoded = json_decode($rawPayload, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static function ($row): bool {
            return is_array($row) && (int) ($row['id_rapport'] ?? 0) > 0;
        }));
    }
}
