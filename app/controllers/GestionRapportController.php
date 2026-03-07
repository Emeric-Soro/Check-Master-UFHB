<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Approuver.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/Entreprise.php';
require_once __DIR__ . '/../Services/GestionRapportService.php';
require_once __DIR__ . '/../Support/Database.php';
require_once __DIR__ . '/../Services/Document/RapportPdfGeneratorService.php';
require_once __DIR__ . '/../Utils/PlanningDataUtils.php';
require_once __DIR__ . '/../utils/permissions_helper.php';


class GestionRapportController
{

    private $baseViewPath;
    private $service;

    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/gestion_rapports/';
        $this->service = new GestionRapportService(Database::getConnection());

        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['id_utilisateur'])) {
            header('Location: page_connexion.php');
            exit;
        }

        // Les variables sont déjà définies par votre AuthController
        // Pas besoin de les redéfinir, juste s'assurer qu'elles existent
        $this->verifierVariablesSession();
    }

    private function verifierVariablesSession()
    {
        // Vérifier que les variables nécessaires sont présentes
        if (!isset($_SESSION['type_utilisateur'])) {
            throw new Exception("Variables de session manquantes. Veuillez vous reconnecter.");
        }

        // Pour les étudiants, s'assurer que num_etu est défini
        if ($_SESSION['type_utilisateur'] === 'Etudiant') {
            $numEtu = $_SESSION['num_etu'] ?? null;
            if ($numEtu === null || $numEtu === '') {
                throw new Exception("Numéro étudiant manquant. Veuillez vous reconnecter.");
            }
            $GLOBALS['candidatures_etudiant'] = $this->service->getCandidatures($numEtu);
            return;
        }

        // Profils non étudiants: aucune candidature personnelle à charger.
        $GLOBALS['candidatures_etudiant'] = [];
    }

    private function isEtudiant()
    {
        return isset($_SESSION['type_utilisateur']) && $_SESSION['type_utilisateur'] === 'Etudiant';
    }



    // Afficher le dashboard des rapports
    public function index()
    {
        try {
            global $statistiquesRapports, $rapportsRecents, $infosDepot;

            if ($this->isEtudiant()) {
                $statistiquesRapports = $this->service->getStatsEtudiant($_SESSION['num_etu']);
                $rapportsRecents = $this->service->getRapportsRecentsEtudiant($_SESSION['num_etu'], 5);

                // Récupérer les informations de dépôt pour chaque rapport
                $infosDepot = $this->service->getInfosDepotRapports($_SESSION['num_etu']);
            } else {
                $rapportsRecents = $this->service->getRecentRapports(5);
                $statistiquesRapports = $this->service->calculerStatistiquesGlobales();
            }

        } catch (Exception $e) {
            $this->afficherErreur("Erreur lors du chargement du dashboard : " . $e->getMessage());
        }
    }

    //=============================CREER UN RAPPORT=============================
    public function creerRapport()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->traiterCreationRapport();
        } else {
            global $rapport, $erreurs, $isEditMode, $contenuRapport, $stage_info;

            $edit_id = $_GET['edit'] ?? null;
            $rapport = null;
            $isEditMode = false;
            $contenuRapport = '';
            $stage_info = null;

            if ($this->isEtudiant()) {
                $numEtu = $_SESSION['num_etu'] ?? null;
                if ($numEtu !== null && $numEtu !== '') {
                    $stage_info = $this->service->getStageInfo($numEtu);
                }
            }

            if ($stage_info) {
                $GLOBALS['stage_info'] = $stage_info;
            }

            if ($edit_id) {
                // Vérifier que l'utilisateur est un étudiant
                if (!$this->isEtudiant()) {
                    $this->afficherErreur("Accès non autorisé.");
                    return;
                }

                // Récupérer le rapport
                $rapport = $this->service->getRapportById($edit_id);
                $isEditMode = true;

                if (!$rapport) {
                    $this->afficherErreur("Rapport non trouvé.");
                    return;
                }

                // Vérifier que le rapport appartient à l'étudiant connecté
                if ($rapport['num_etu'] != $_SESSION['num_etu']) {
                    $this->afficherErreur("Accès non autorisé à ce rapport.");
                    return;
                }

                // Vérifier si le rapport a déjà été déposé
                $GLOBALS['rapportDejaDepose'] = $this->service->isRapportDepose($_SESSION['num_etu'], $edit_id);

                // Charger le contenu du rapport
                $contenuRapport = $this->service->chargerContenuRapport($edit_id);

                // Convertir l'objet en tableau pour la vue
                $rapport = (array) $rapport;
            }

            $erreurs = $_SESSION['erreurs_form'] ?? [];
            unset($_SESSION['erreurs_form']);

            // Rendre toutes les données disponibles globalement pour la vue
            // (layout.php inclut la vue via $contentFile — ne PAS faire require_once ici)
            $GLOBALS['rapport'] = $rapport;
            $GLOBALS['isEditMode'] = $isEditMode;
            $GLOBALS['contenuRapport'] = $contenuRapport;
            $GLOBALS['erreurs'] = $erreurs;
        }
    }

    public function traiterCreationRapport()
    {
        $postAction = $_POST['action'] ?? '';

        $isEtudiant = $this->isEtudiant();
        // L'export PDF est une opération de lecture : canView() suffit (les étudiants y ont accès aussi)
        $canAccess = ($postAction === 'export_pdf')
            ? ($isEtudiant || canView('gestion_rapports') || canCreate('gestion_rapports') || canEdit('gestion_rapports') || canDelete('gestion_rapports'))
            : ($isEtudiant || canCreate('gestion_rapports') || canEdit('gestion_rapports') || canDelete('gestion_rapports'));

        if (!$canAccess) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }

        try {
            $action = $_POST['action'] ?? '';
            $auditLog = $this->service->getAuditLog();

            if ($action === 'save_rapport') {
                $this->sauvegarderRapport();
                $auditLog->logCreation($_SESSION['id_utilisateur'], "rapport", "Succès");
            } elseif ($action === 'deposer_rapport') {
                $id_rapport = $_POST['id_rapport'] ?? null;
                if (!$id_rapport && isset($_POST['edit_id'])) {
                    $id_rapport = $_POST['edit_id'];
                }
                if (!$id_rapport) {
                    $this->sendJsonResponse(['success' => false, 'message' => 'Aucun rapport à déposer.']);
                    return;
                }
                $depotResult = $this->service->traiterDepotRapport($id_rapport, $_SESSION['num_etu']);
                $auditLog->logDepot(
                    $_SESSION['id_utilisateur'],
                    'rapport',
                    $depotResult['success'] ? 'Succès' : 'Erreur'
                );
                header('Location: ' . $depotResult['redirect']);
                exit;
            } elseif ($action === 'export_pdf') {
                $this->exporterRapport();
                $auditLog->logExportation($_SESSION['id_utilisateur'], "rapport", "Succès");
            } else {
                $auditLog->logDepot($_SESSION['id_utilisateur'], "rapport", "Erreur");
                throw new Exception("Action non reconnue.");

            }

        } catch (Exception $e) {
            error_log("Exception dans traiterCreationRapport: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function sauvegarderRapport()
    {
        if (!$this->isEtudiant()) {
            throw new Exception('Seuls les étudiants peuvent créer des rapports.');
        }

        // Récupérer les données du formulaire
        $donneesRapport = [
            'nom_rapport' => trim($_POST['nom_rapport'] ?? ''),
            'theme_rapport' => trim($_POST['theme_rapport'] ?? ''),
            'contenu_rapport' => $_POST['contenu_rapport'] ?? '',
            'edit_id' => $_POST['edit_id'] ?? null
        ];

        // Déléguer au service
        $result = $this->service->sauvegarderRapport($donneesRapport, $_SESSION['num_etu']);

        if (!$result['success'] && isset($result['errors'])) {
            $_SESSION['erreurs_form'] = $result['errors'];
        }

        if ($result['success']) {
            $this->afficherMessage($result['message'], 'success');
            $this->service->getAuditLog()->logCreation($_SESSION['id_utilisateur'], 'rapport_etudiants', 'Succès');
        }

        $this->sendJsonResponse($result);
    }

    private function exporterRapport()
    {
        // Drain ALL output buffers to ensure no HTML leaks into the PDF response.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            $edit_id = $_POST['edit_id'] ?? null;
            if (!$edit_id) {
                throw new Exception('ID du rapport manquant.');
            }

            $rapport = $this->service->getRapportById($edit_id);
            if (!$rapport) {
                throw new Exception('Rapport introuvable.');
            }

            if ($this->isEtudiant()) {
                if (($rapport['num_etu'] ?? null) !== ($_SESSION['num_etu'] ?? null)) {
                    throw new Exception('Accès non autorisé à ce rapport.');
                }
            } elseif (!(canView('gestion_rapports') || canCreate('gestion_rapports') || canEdit('gestion_rapports') || canDelete('gestion_rapports'))) {
                throw new Exception('Accès non autorisé à l\'export de ce rapport.');
            }

            // Initialiser les services
            $db = new \App\Support\Database();
            $dataUtils = new \App\Utils\PlanningDataUtils($db);
            $pdfGenerator = new \App\Services\Document\PdfGeneratorService(
                __DIR__ . '/../../storage',
                __DIR__ . '/../../public/assets/img/logo.png'
            );
            $rapportService = new \App\Services\Document\RapportPdfGeneratorService($pdfGenerator, $dataUtils);

            // Générer le PDF via le service
            $userId = $_SESSION['id_utilisateur'] ?? 0;
            $result = $rapportService->generate((int) $edit_id, (int) $userId);

            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Erreur lors de la génération du PDF');
            }

            // Le fichier a été généré, on le télécharge
            $pdfPath = $result['path'];
            if (!file_exists($pdfPath)) {
                throw new Exception('Fichier PDF non trouvé: ' . $pdfPath);
            }

            // Stream the PDF — all ob levels were already drained at the top of this method.
            $isDownload = !empty($_POST['download']);
            $disposition = $isDownload ? 'attachment' : 'inline';
            header('Content-Type: application/pdf');
            header('Content-Disposition: ' . $disposition . '; filename="' . basename($pdfPath) . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . filesize($pdfPath));

            readfile($pdfPath);
            exit;

        } catch (Exception $e) {
            error_log("Erreur lors de l'export PDF: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Drain any buffers opened during PDF generation
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Return JSON error
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    //=============================SUIVI RAPPORTS=============================
    public function suiviRapport($num_etu)
    {
        global $rapports;
        $rapports = $this->service->getRapportsAvecDecisions($num_etu);
    }

    //=============================COMMENTAIRE RAPPORT=============================
    public function commentaireRapport()
    {
        try {
            // Si un ID est spécifié, afficher le détail
            if (isset($_GET['id'])) {
                $this->afficherDetailRapport($_GET['id']);
                return;
            }

            global $rapports, $statistiquesCompteRendu;

            // Récupérer les paramètres de filtrage
            $statut = $_GET['statut'] ?? '';
            $search = $_GET['search'] ?? '';

            $rapports = $this->service->getRapportsFiltres(
                $this->isEtudiant(),
                $_SESSION['num_etu'] ?? null,
                $statut,
                $search
            );

            $statistiquesCompteRendu = $this->service->calculerStatistiques(
                $this->isEtudiant(),
                $_SESSION['num_etu'] ?? null
            );

            // Les données sont maintenant disponibles globalement pour la vue

        } catch (Exception $e) {
            $this->afficherErreur("Erreur lors du chargement du compte rendu : " . $e->getMessage());
        }
    }

    private function afficherDetailRapport($id)
    {
        global $rapport, $commentaires;

        // Debug
        error_log("AfficherDetailRapport - ID: $id");
        error_log("Type utilisateur: " . ($_SESSION['type_utilisateur'] ?? 'non défini'));
        error_log("Num étudiant: " . ($_SESSION['num_etu'] ?? 'non défini'));

        // Récupérer le rapport
        $rapport = $this->service->getRapportById($id);
        error_log("Rapport trouvé: " . ($rapport ? 'oui' : 'non'));

        if (!$rapport) {
            $this->afficherErreur("Rapport non trouvé.");
            return;
        }

        // Récupérer les commentaires des évaluateurs
        $commentaires = $this->service->getCommentairesEvaluateurs($id);

        // Convertir l'objet en tableau pour la vue
        $rapport = (array) $rapport;

        // Forcer l'inclusion de la vue de détail
        $contentFile = $this->baseViewPath . 'detail_rapport.php';
        if (file_exists($contentFile)) {
            include $contentFile;
        } else {
            $this->afficherErreur("Vue de détail non trouvée.");
        }
        exit; // Empêcher l'affichage de la vue normale
    }

    //=============================ACTIONS AJAX=============================
    public function deleteRapportAjax()
    {
        if (!canDelete('gestion_rapports')) {
            http_response_code(403);
            $this->sendJsonResponse(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        if (!$this->isEtudiant()) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
            return;
        }

        try {
            $rapport_id = $_POST['rapport_id'] ?? 0;

            if (!$rapport_id) {
                $this->sendJsonResponse(['success' => false, 'message' => 'ID du rapport manquant']);
                return;
            }

            $result = $this->service->deleteRapport($rapport_id, $_SESSION['num_etu']);
            $auditLog = $this->service->getAuditLog();

            if ($result['success']) {
                $auditLog->logSuppression($_SESSION['id_utilisateur'], 'rapport_etudiants', 'Succès');
            } else {
                $auditLog->logSuppression($_SESSION['id_utilisateur'], 'rapport_etudiants', 'Erreur');
            }

            $this->sendJsonResponse($result);
        } catch (Exception $e) {
            error_log("Erreur suppression rapport: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
    }

    public function getRapportAjax()
    {
        if (!$this->isEtudiant()) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Accès non autorisé']);
            return;
        }

        try {
            $rapport_id = $_GET['id'] ?? 0;

            if (!$rapport_id) {
                $this->sendJsonResponse(['success' => false, 'message' => 'ID du rapport manquant']);
                return;
            }

            $rapport_data = $this->service->getRapportAvecContenu($rapport_id, $_SESSION['num_etu']);

            if (!$rapport_data) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Rapport non trouvé']);
                return;
            }

            $this->sendJsonResponse(['success' => true, 'data' => $rapport_data]);
        } catch (Exception $e) {
            error_log("Erreur récupération rapport: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'message' => 'Erreur lors de la récupération']);
        }
    }

    //=============================MÉTHODES UTILITAIRES=============================
    private function afficherMessage($message, $type = 'info')
    {
        $_SESSION['message'] = ['text' => $message, 'type' => $type];
    }

    private function afficherErreur($message)
    {
        $this->afficherMessage($message, 'error');

    }

    private function verifierDroitsAdmin()
    {
        // Utiliser les groupes utilisateur de votre système
        $groupesAdmin = [5, 6, 7, 8]; // Admins, secrétaires, etc.
        return in_array($_SESSION['id_GU'] ?? 0, $groupesAdmin);
    }

    private function sendJsonResponse($data)
    {
        // Drain all output buffers (layout.php opens ob_start() before including route files).
        // Without this, any buffered HTML would be prepended to the JSON response.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function exporterRapports()
    {
        if (!$this->verifierDroitsAdmin()) {
            $this->afficherErreur("Accès non autorisé.");
            return;
        }

        try {
            $csvData = $this->service->buildCsvData();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=rapports_' . date('Y-m-d') . '.csv');
            $output = fopen('php://output', 'w');
            fputcsv($output, $csvData['headers']);

            foreach ($csvData['rows'] as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        } catch (Exception $e) {
            $this->afficherErreur("Erreur lors de l'export : " . $e->getMessage());
        }
    }


    public function enregistrerDepotRapport($id_rapport)
    {
        return $this->service->enregistrerDepotRapport($id_rapport, $_SESSION['num_etu']);
    }

    /**
     * Supprime un rapport (appelé via formulaire POST)
     */
    public function supprimer_rapport()
    {
        if (!canDelete('gestion_rapports')) {
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }

        // Vérifier que c'est bien un POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return; // Ne rien faire si ce n'est pas un POST
        }

        try {
            // Vérifier que l'utilisateur est connecté
            if (!$this->isEtudiant()) {
                $this->afficherErreur('Accès non autorisé');
                return;
            }

            // Vérifier que l'ID du rapport est fourni
            if (!isset($_POST['rapport_id']) || empty($_POST['rapport_id'])) {
                $this->afficherErreur('ID du rapport manquant');
                return;
            }

            $rapportId = (int) $_POST['rapport_id'];
            $numEtu = $_SESSION['num_etu'];

            // Déléguer la suppression au service
            $result = $this->service->supprimerRapport($rapportId, $numEtu);
            $auditLog = $this->service->getAuditLog();

            if ($result['success']) {
                $auditLog->logSuppression($_SESSION['id_utilisateur'], 'rapport_etudiants', 'Succès');
                // Rediriger avec un message de succès
                header('Location: ?page=gestion_rapports&message=suppression_ok');
                exit;
            } else {
                $auditLog->logSuppression($_SESSION['id_utilisateur'], 'rapport_etudiants', 'Erreur');
                $this->afficherErreur($result['message']);
            }

        } catch (Exception $e) {
            $this->afficherErreur('Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    public function getCommentairesAjax()
    {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo "ID du rapport manquant";
            return;
        }

        echo $this->service->renderCommentairesHtml($_GET['id']);
    }

}
