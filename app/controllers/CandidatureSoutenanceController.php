<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/CandidatureSoutenanceService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\CandidatureSoutenanceService;

class CandidatureSoutenanceController
{
    private $baseViewPath;

    private $service;

    private function debugLog(string $message, array $context = []): void
    {
        $logPath = __DIR__ . '/../../logs/candidature_soutenance_debug.log';
        $fallbackLogPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'candidature_soutenance_debug.log';
        $line = date('c') . ' [CandidatureSoutenanceController] ' . $message;
        if ($context !== []) {
            $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $line .= ' | context=' . ($json !== false ? $json : '[json_error]');
        }

        error_log($line);
        @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
        @file_put_contents($fallbackLogPath, $line . PHP_EOL, FILE_APPEND);
    }

    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/candidature_soutenance/';
        $db = Database::getConnection();
        $this->service = new CandidatureSoutenanceService($db);
        $this->debugLog('construct');
    }

    public function index()
    {
        $this->debugLog('index:start', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'get' => $_GET,
            'post_keys' => array_keys($_POST),
            'session' => [
                'id_utilisateur' => $_SESSION['id_utilisateur'] ?? null,
                'id_GU' => $_SESSION['id_GU'] ?? null,
                'lib_GU' => $_SESSION['lib_GU'] ?? null,
                'num_etu' => $_SESSION['num_etu'] ?? null,
                'login_utilisateur' => $_SESSION['login_utilisateur'] ?? null,
            ],
        ]);

        if (isset($_GET['action'])) {
            $this->debugLog('index:dispatch_action', ['action' => $_GET['action']]);
            switch ($_GET['action']) {
                case 'demande_candidature':
                    $this->demande_candidature();
                    break;
                case 'compte_rendu_etudiant':
                    $this->compteRenduRapport();
                    break;
                case 'info_stage':
                    $this->infoStage();
                    break;
            }
        }

        $lib = $_SESSION['lib_GU'] ?? null;
        $isAdmin = is_string($lib) && in_array(strtolower(trim($lib)), ['administrateur', 'admin'], true);
        $this->debugLog('index:role_check', [
            'lib_GU' => $lib,
            'isAdmin' => $isAdmin,
            'has_num_etu' => isset($_SESSION['num_etu']) && !empty($_SESSION['num_etu']),
        ]);

        if (!isset($_SESSION['num_etu']) && !$isAdmin) {
            $this->debugLog('index:blocked_non_student');
            $_SESSION['error'] = "Cette page est reservee aux etudiants uniquement.";
            header('Location: layout.php?page=dashboard');
            exit();
        }

        if (isset($_SESSION['num_etu'])) {
            $GLOBALS['stage_info'] = $this->service->getStageInfo($_SESSION['num_etu']);
            $GLOBALS['compte_rendu'] = $this->service->getCompteRendu($_SESSION['num_etu']);
            $candidature = $this->service->getCandidature($_SESSION['num_etu']);
            $GLOBALS['has_candidature'] = !empty($candidature);
            $GLOBALS['candidatures_etudiant'] = $this->service->getCandidatures($_SESSION['num_etu']);
            $GLOBALS['candidature_active'] = $this->service->getLastCandidature($_SESSION['num_etu']);
            $GLOBALS['progression'] = $this->service->calculerProgression($_SESSION['num_etu']);
            $GLOBALS['dossier_soutenance'] = $this->service->getSuiviDossier($_SESSION['num_etu']);
            $this->debugLog('index:student_globals_loaded', [
                'num_etu' => $_SESSION['num_etu'],
                'has_stage_info' => !empty($GLOBALS['stage_info']),
                'has_compte_rendu' => !empty($GLOBALS['compte_rendu']),
                'has_candidature' => !empty($GLOBALS['has_candidature']),
                'tracking_mode' => !empty($GLOBALS['dossier_soutenance']['tracking_mode']),
            ]);
        } else {
            $GLOBALS['stage_info'] = null;
            $GLOBALS['compte_rendu'] = null;
            $GLOBALS['has_candidature'] = false;
            $GLOBALS['candidatures_etudiant'] = [];
            $GLOBALS['dossier_soutenance'] = [];
            $this->debugLog('index:admin_defaults_loaded');
        }

        $GLOBALS['entreprises'] = $this->service->getAllEntreprises();
        $GLOBALS['maitres_de_stage'] = $this->service->getAllMaitresDeStage();
        $this->debugLog('index:autocomplete_loaded', [
            'entreprises_count' => is_array($GLOBALS['entreprises']) ? count($GLOBALS['entreprises']) : 0,
            'maitres_count' => is_array($GLOBALS['maitres_de_stage']) ? count($GLOBALS['maitres_de_stage']) : 0,
        ]);
    }

    public function demande_candidature()
    {
        $this->debugLog('demande_candidature:redirect');
        $_SESSION['success'] = "Votre candidature sera soumise automatiquement lors du depot de votre rapport. Veuillez d'abord remplir les informations de stage, puis deposer votre rapport.";
        header('Location: ?page=candidature_soutenance');
        exit();
    }

    public function compteRenduRapport()
    {
        $this->debugLog('compteRenduRapport:start', [
            'num_etu' => $_SESSION['num_etu'] ?? null,
        ]);

        if (!isset($_SESSION['num_etu']) || empty($_SESSION['num_etu'])) {
            $this->debugLog('compteRenduRapport:missing_student_session');
            $_SESSION['error'] = "Identifiant etudiant non trouve. Veuillez vous reconnecter.";
            return;
        }

        $etudiant_id = $_SESSION['num_etu'];
        $compte_rendu = $this->service->getCompteRendu($etudiant_id);
        $GLOBALS['compte_rendu'] = $compte_rendu;
        $this->debugLog('compteRenduRapport:loaded', [
            'num_etu' => $etudiant_id,
            'has_compte_rendu' => !empty($compte_rendu),
        ]);

        if (!$compte_rendu) {
            $_SESSION['error'] = "Aucun compte rendu disponible pour le moment. Veuillez patienter jusqu'a ce que la commission d'evaluation ait examine votre dossier.";
        }
    }

    public function infoStage()
    {
        $this->debugLog('infoStage:entry', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'get' => $_GET,
            'post' => $_POST,
            'session' => [
                'id_utilisateur' => $_SESSION['id_utilisateur'] ?? null,
                'id_GU' => $_SESSION['id_GU'] ?? null,
                'lib_GU' => $_SESSION['lib_GU'] ?? null,
                'num_etu' => $_SESSION['num_etu'] ?? null,
                'login_utilisateur' => $_SESSION['login_utilisateur'] ?? null,
            ],
        ]);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->debugLog('infoStage:non_post_exit');
            return;
        }

        if (!isset($_SESSION['num_etu']) || empty($_SESSION['num_etu'])) {
            $this->debugLog('infoStage:missing_student_session_redirect');
            header('Location: ?page=gestion_rapports&action=creer_rapport');
            exit();
        }

        $canCreate = canCreate('candidature_soutenance');
        $this->debugLog('infoStage:permission_check', ['canCreate' => $canCreate]);
        if (!$canCreate) {
            $_SESSION['error'] = "Acces non autorise.";
            $this->debugLog('infoStage:permission_denied_redirect');
            header('Location: ?page=candidature_soutenance');
            exit();
        }

        $etudiant_id = $_SESSION['num_etu'];
        $id_utilisateur = $_SESSION['id_utilisateur'] ?? null;
        $data = [
            'entreprise' => $_POST['entreprise'] ?? '',
            'date_debut' => $_POST['date_debut'] ?? '',
            'date_fin' => $_POST['date_fin'] ?? '',
            'sujet' => $_POST['sujet'] ?? '',
            'encadrant' => $_POST['encadrant'] ?? '',
            'email_encadrant' => $_POST['email_encadrant'] ?? '',
            'telephone_encadrant' => $_POST['telephone_encadrant'] ?? '',
        ];
        $this->debugLog('infoStage:payload_built', [
            'etudiant_id' => $etudiant_id,
            'id_utilisateur' => $id_utilisateur,
            'data' => $data,
        ]);

        try {
            $result = $this->service->enregistrerInfoStage($etudiant_id, $id_utilisateur, $data);
            $this->debugLog('infoStage:service_result', [
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            $this->debugLog('infoStage:service_exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }

        if (!empty($result['success'])) {
            $_SESSION['success'] = $result['message'] ?? "Enregistrement effectue.";
            $this->debugLog('infoStage:success_redirect', [
                'redirect' => '?page=gestion_rapports&action=creer_rapport',
            ]);
            header('Location: ?page=gestion_rapports&action=creer_rapport');
            exit();
        }

        $_SESSION['error'] = $result['message'] ?? "Une erreur est survenue lors de l'enregistrement des informations.";
        $this->debugLog('infoStage:error_redirect', [
            'redirect' => '?page=candidature_soutenance',
            'message' => $result['message'] ?? null,
        ]);
        header('Location: ?page=candidature_soutenance');
        exit();
    }
}
