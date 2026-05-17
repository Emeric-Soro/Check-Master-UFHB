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
            $result = $this->service->enregistrer([
                'num_etu'               => $_POST['num_etu'] ?? null,
                'nom_CR'                => $_POST['nom_CR'] ?? '',
                'contenu_CR'            => $_POST['contenu_CR'] ?? '',
                'rapports'              => isset($_POST['rapports']) ? $_POST['rapports'] : [],
                'encadrant_pedagogique' => $_POST['encadrant_pedagogique'] ?? [],
                'directeur_memoire'     => $_POST['directeur_memoire'] ?? [],
                'submit_action'         => $_POST['submit_action'] ?? 'save',
            ]);

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            if (!empty($result['success'])) {
                $_SESSION['success'] = (string) ($result['message'] ?? '');
            } else {
                $_SESSION['error'] = (string) ($result['message'] ?? '');
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => (bool) ($result['success'] ?? false),
                    'message' => (string) ($result['message'] ?? ''),
                    'redirect' => '?page=redaction_compte_rendu',
                ]);
                exit;
            }

            header('Location: layout.php?page=redaction_compte_rendu');
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
}
