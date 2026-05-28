<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/Entreprise.php';
require_once __DIR__ . '/../Services/GestionRapportService.php';
require_once __DIR__ . '/../Support/Database.php';
require_once __DIR__ . '/../Services/Document/RapportPdfGeneratorService.php';
require_once __DIR__ . '/../utils/PlanningDataUtils.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\GestionRapportService;

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

        // Vérifier les variables de session
        $this->verifierVariablesSession();
    }

    private function verifierVariablesSession()
    {
        // Vérifier que les variables nécessaires sont présentes
        if (empty($_SESSION['type_utilisateur'])) {
            throw new Exception("Variables de session manquantes. Veuillez vous reconnecter.");
        }

        // Pour les étudiants, s'assurer que num_etu est défini
        if ($_SESSION['type_utilisateur'] === 'Etudiant') {
            $numEtu = $_SESSION['num_etu'] ?? null;
            if (empty($numEtu)) {
                throw new Exception("Numéro étudiant manquant. Veuillez vous reconnecter.");
            }
            return;
        }
    }

    private function isEtudiant()
    {
        return isset($_SESSION['type_utilisateur']) && $_SESSION['type_utilisateur'] === 'Etudiant';
    }


    //=============================CREER UN RAPPORT=============================
    public function creerRapport()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->traiterCreationRapport();
        } else {
            global $rapport, $erreurs, $isEditMode, $contenuRapport, $stage_info, $rapportEstUpload, $rapportUploadChemin;

            $edit_id = $_GET['edit'] ?? null;
            $rapport = null;
            $isEditMode = false;
            $contenuRapport = '';
            $stage_info = null;
            $rapportEstUpload = false;
            $rapportUploadChemin = '';

            if ($edit_id !== null) {
                try {
                    $rapport = $this->service->getRapportById($edit_id);

                    if ($rapport === null) {
                        $this->afficherErreur("Rapport non trouvé.");
                        return;
                    }

                    $isEditMode = true;

                    // Détecter si c'est un fichier uploadé (PDF/DOC/DOCX) vs éditeur HTML
                    $cheminFichier = (string) ($rapport['chemin_fichier'] ?? '');
                    if ($cheminFichier !== '') {
                        $ext = strtolower(pathinfo($cheminFichier, PATHINFO_EXTENSION));
                        if ($ext !== 'html') {
                            $rapportEstUpload = true;
                            $rapportUploadChemin = $cheminFichier;
                        }
                    }

                    if (!$rapportEstUpload) {
                        $contenuRapport = $this->service->chargerContenuRapport($edit_id);
                    }

                } catch (Exception $e) {
                    $this->afficherErreur("Erreur lors du chargement du rapport : " . $e->getMessage());
                    return;
                }
            }

            $stage_info = $this->service->getStageInfo($_SESSION['num_etu']);
        }
    }

    //=============================TRAITER LA CREATION DE RAPPORT=============================
    public function traiterCreationRapport()
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        try {

            if (!canCreate('gestion_rapports')) {
                if ($isAjax) {
                    while (ob_get_level())
                        ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => "Accès non autorisé. Vous n'avez pas la permission de créer un rapport."], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $this->afficherErreur("Accès non autorisé. Vous n'avez pas la permission de créer un rapport.");
                return;
            }

            $num_etu = $_SESSION['num_etu'];
            $id_utilisateur = $_SESSION['id_utilisateur'];

            // Récupérer les données du formulaire
            $nom_rapport = isset($_POST['nom_rapport']) ? trim((string) $_POST['nom_rapport']) : '';
            $theme_rapport = isset($_POST['theme_rapport']) ? trim((string) $_POST['theme_rapport']) : '';
            $contenu_rapport = isset($_POST['contenu_rapport']) ? (string) $_POST['contenu_rapport'] : '';
            $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';
            $edit_id = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : (isset($_POST['id_rapport']) ? (int) $_POST['id_rapport'] : null);

            // Si c'est juste un dépôt depuis la liste (les données du rapport ne sont pas soumises)
            if ($action === 'deposer_rapport' && empty($nom_rapport) && $edit_id !== null) {
                $resultat = $this->service->traiterDepotRapport($edit_id, $num_etu);
                if ($resultat['success']) {
                    $_SESSION['success'] = "Rapport déposé avec succès. Vous recevrez la notification des résultats à la date prévue.";
                    header('Location: ' . $resultat['redirect']);
                } else {
                    $_SESSION['error'] = "Une erreur est survenue lors du dépôt du rapport.";
                    header('Location: ' . $resultat['redirect']);
                }
                exit;
            }

            // Dépôt d'un fichier uploadé (PDF/DOC/DOCX) — pas besoin de valider le contenu éditeur
            if ($action === 'deposer_rapport' && $edit_id !== null) {
                $rapportCheck = $this->service->getRapportById($edit_id);
                if ($rapportCheck && !empty($rapportCheck['chemin_fichier'])) {
                    $extCheck = strtolower(pathinfo((string) $rapportCheck['chemin_fichier'], PATHINFO_EXTENSION));
                    if ($extCheck !== 'html') {
                        $resultat = $this->service->traiterDepotRapport($edit_id, $num_etu);
                        if ($resultat['success']) {
                            $_SESSION['success'] = "Rapport déposé avec succès. Vous recevrez la notification des résultats à la date prévue.";
                        } else {
                            $_SESSION['error'] = "Une erreur est survenue lors du dépôt du rapport.";
                        }
                        header('Location: ' . ($resultat['redirect'] ?? '?page=gestion_rapports'));
                        exit;
                    }
                }
            }

            // Valider les données
            $donnees = [
                'nom_rapport' => $nom_rapport,
                'theme_rapport' => $theme_rapport,
                'contenu_rapport' => $contenu_rapport,
                'num_etu' => $num_etu
            ];

            $erreurs = $this->service->validerDonneesRapport($donnees);

            if (!empty($erreurs)) {
                if ($isAjax) {
                    while (ob_get_level())
                        ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erreurs de validation',
                        'errors' => $erreurs
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $_SESSION['erreurs_rapport'] = $erreurs;
                if ($edit_id !== null) {
                    header("Location: ?page=gestion_rapports&action=creer_rapport&edit=$edit_id");
                } else {
                    header('Location: ?page=gestion_rapports&action=creer_rapport');
                }
                exit;
            }

            // Déterminer si c'est une création ou une modification
            $isEdit = $edit_id !== null && $edit_id > 0;

            // Vérifier que le nom du rapport est unique (sauf si on l'édite)
            if ($this->service->isRapportNomExist($nom_rapport, $num_etu, $isEdit ? $edit_id : null)) {
                if ($isAjax) {
                    while (ob_get_level())
                        ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => "Un rapport avec ce nom existe déjà. Veuillez choisir un autre nom."
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $_SESSION['error'] = "Un rapport avec ce nom existe déjà. Veuillez choisir un autre nom.";
                if ($isEdit) {
                    header("Location: ?page=gestion_rapports&action=creer_rapport&edit=$edit_id");
                } else {
                    header('Location: ?page=gestion_rapports&action=creer_rapport');
                }
                exit;
            }

            // Sauvegarder le rapport
            if ($isEdit) {
                // Modification
                $resultat = $this->service->sauvegarderRapport([
                    'edit_id' => $edit_id,
                    'nom_rapport' => $nom_rapport,
                    'theme_rapport' => $theme_rapport,
                    'contenu_rapport' => $contenu_rapport,
                    'num_etu' => $num_etu
                ], $num_etu);
                $rapport_id = $edit_id;
            } else {
                // Création
                $resultat = $this->service->sauvegarderRapport($donnees, $num_etu);
                $rapport_id = $resultat['rapport_id'] ?? null;
            }

            if (!$rapport_id) {
                $errorMessage = $resultat['message'] ?? "Une erreur est survenue lors de la sauvegarde du rapport.";
                if ($isAjax) {
                    while (ob_get_level())
                        ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage,
                        'debug' => $resultat['debug'] ?? null
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $_SESSION['error'] = $errorMessage;
                if ($isEdit) {
                    header("Location: ?page=gestion_rapports&action=creer_rapport&edit=$edit_id");
                } else {
                    header('Location: ?page=gestion_rapports&action=creer_rapport');
                }
                exit;
            }

            // Sauvegarder le contenu du rapport (éditeur HTML uniquement)
            $this->service->sauvegarderContenuRapport($rapport_id, $contenu_rapport);

            // Récupérer le rapport créé
            $rapport_cree = $this->service->getRapportById($rapport_id);

            if ($action === 'save_rapport') {
                if ($isAjax) {
                    while (ob_get_level())
                        ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rapport sauvegardé avec succès.',
                        'rapport_id' => $rapport_id
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $_SESSION['success'] = "Rapport sauvegardé avec succès.";
                header('Location: ?page=gestion_rapports&action=creer_rapport&edit=' . $rapport_id);
                exit;

            } elseif ($action === 'export_pdf') {
                $this->exporterRapport($rapport_id);

            } elseif ($action === 'deposer_rapport') {
                // Enregistrer le dépôt du rapport
                $resultat = $this->service->traiterDepotRapport($rapport_id, $num_etu);
                if ($resultat['success']) {
                    $_SESSION['success'] = "Rapport déposé avec succès. Vous recevrez la notification des résultats à la date prévue.";
                    header('Location: ' . $resultat['redirect']);
                } else {
                    $_SESSION['error'] = "Une erreur est survenue lors du dépôt du rapport.";
                    header('Location: ' . $resultat['redirect']);
                }
                exit;
            }

        } catch (Exception $e) {
            if ($isAjax) {
                while (ob_get_level())
                    ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $this->afficherErreur('Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Exporte un rapport au format PDF
     */
    public function exporterRapport($rapport_id = null)
    {
        try {
            $id_rapport = $rapport_id ?? ($_GET['id'] ?? null);

            if (!$id_rapport) {
                $this->afficherErreur("ID du rapport manquant.");
                return;
            }

            $num_etu = $_SESSION['num_etu'];

            // Vérifier que l'étudiant a accès à ce rapport
            $rapport = $this->service->getRapportById($id_rapport);

            if (!$rapport || $rapport['num_etu'] != $num_etu) {
                $this->afficherErreur("Accès non autorisé à ce rapport.");
                return;
            }

            // Charger le contenu du rapport
            $contenu = $this->service->chargerContenuRapport($id_rapport);
            $contenu_rapport = $contenu['contenu_rapport'] ?? '';

            // Nettoyer les tampons de sortie avant de générer le PDF
            while (ob_get_level())
                ob_end_clean();

            // Générer le PDF
            require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';
            require_once __DIR__ . '/../utils/PlanningDataUtils.php';

            $dbWrapper = new \App\Support\Database();
            $planningDataUtils = new \App\Utils\PlanningDataUtils($dbWrapper);
            $pdfGen = new \App\Services\Document\PdfGeneratorService(
                __DIR__ . '/../../storage/documents',
                __DIR__ . '/../../public/image/logo_ufhb.png'
            );
            $pdfGenerator = new \App\Services\Document\RapportPdfGeneratorService($pdfGen, $planningDataUtils);

            $result = $pdfGenerator->generate((int) $id_rapport, (int) $_SESSION['id_utilisateur']);

            if (!$result['success']) {
                throw new Exception($result['error'] ?? "Erreur lors de la génération du PDF.");
            }

            $filepath = $result['path'] ?? null;
            if (!$filepath || !file_exists($filepath)) {
                throw new Exception("Le fichier PDF n'a pas pu être trouvé après génération.");
            }

            // Rediriger vers le DocViewer unifié
            $action = (isset($_POST['download']) && $_POST['download'] === '1') ? 'download' : 'preview';
            $redirectUrl = '?page=docviewer&type=rapport&id=' . (int) $id_rapport . '&action=' . $action;
            header('Location: ' . $redirectUrl);
            exit;

        } catch (Exception $e) {
            $this->afficherErreur('Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }

    /**
     * Enregistre le dépôt d'un rapport
     */
    public function enregistrerDepotRapport($id_rapport)
    {
        return $this->service->enregistrerDepotRapport($id_rapport, $_SESSION['num_etu']);
    }

    /**
     * Affiche un message de succès
     */
    protected function afficherMessage($message)
    {
        $_SESSION['success'] = $message;
    }

    /**
     * Affiche un message d'erreur
     */
    protected function afficherErreur($erreur)
    {
        $_SESSION['error'] = $erreur;
    }

    /**
     * Envoie une réponse JSON
     */
    protected function sendJsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function verifierDroitsAdmin()
    {
        return $this->hasRapportAdminPermission('voir');
    }

    private function isAdminGroup()
    {
        return $this->hasRapportAdminPermission('voir');
    }

    private function hasRapportAdminPermission(string $action = 'voir'): bool
    {
        switch ($action) {
            case 'creer':
                return canCreate('telecharger_rapport');
            case 'modifier':
                return canEdit('telecharger_rapport');
            case 'supprimer':
                return canDelete('telecharger_rapport');
            case 'voir':
            default:
                return canView('telecharger_rapport');
        }
    }

    private function getRapportAdminPage(): string
    {
        return 'telecharger_rapport';
    }

    // ======================== PRD 1 : Téléchargement/Dépôt côté étudiant ========================

    /**
     * Affiche la page de téléchargement du rapport étudiant (PRD 1)
     */
    public function telechargerRapportEtudiant()
    {
        if ($this->isEtudiant()) {
            // Préparer les données pour la vue étudiant
            $num_etu = $_SESSION['num_etu'];

            // URL du modèle de rapport
            $GLOBALS['modeleRapportUrl'] = $this->service->getModeleRapportUrl();

            // Récupérer les rapports de l'étudiant
            $rapports = $this->service->getRapportsRecentsEtudiant($num_etu, 20);
            $GLOBALS['rapportsRecents'] = $rapports;

            // Dernier rapport uploadé
            $rapportModel = new RapportEtudiant(Database::getConnection());
            $dernierRapport = $rapportModel->getDernierRapportUploaded($num_etu);
            $GLOBALS['dernierRapport'] = $dernierRapport;

            // Infos de dépôt
            $infosDepot = [];
            foreach ($rapports as $rapport) {
                $rapportId = (int) ($rapport->id_rapport ?? 0);
                $dejaDepose = $this->service->isRapportDepose($num_etu, $rapportId);
                $infosDepot[$rapportId] = [
                    'dejaDepose' => $dejaDepose,
                    'peutDeposer' => !$dejaDepose && !$this->service->aUnRapportEnCours($num_etu)
                ];
            }
            $GLOBALS['infosDepot'] = $infosDepot;
            $GLOBALS['statistiquesRapports'] = $this->service->getStatsEtudiant($num_etu);

            // Types et taille max autorisés
            $GLOBALS['typesAutorises'] = 'pdf,doc,docx';
            $GLOBALS['tailleMax'] = 20 * 1024 * 1024; // 20 MB
        } else {
            // Rediriger vers la page admin
            header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
            exit;
        }
    }

    /**
     * Traite l'upload du rapport par l'étudiant (PRD 1 F1.3)
     */
    public function traiterUploadRapport()
    {
        try {
            if (!$this->isEtudiant()) {
                $_SESSION['error'] = "Accès non autorisé.";
                header('Location: ?page=gestion_rapports&action=telecharger_rapport');
                exit;
            }

            $num_etu = $_SESSION['num_etu'];
            $theme_rapport = isset($_POST['theme_rapport']) ? trim($_POST['theme_rapport']) : '';

            // Vérifier qu'un fichier a été soumis
            if (!isset($_FILES['rapport_fichier']) || $_FILES['rapport_fichier']['error'] === UPLOAD_ERR_NO_FILE) {
                $_SESSION['error'] = "Veuillez sélectionner un fichier à uploader.";
                header('Location: ?page=gestion_rapports&action=telecharger_rapport');
                exit;
            }

            // Traiter l'upload
            $resultat = $this->service->traiterUploadRapportEtudiant(
                $_FILES['rapport_fichier'],
                $num_etu,
                $theme_rapport
            );

            if ($resultat['success']) {
                $_SESSION['success'] = $resultat['message'];

                // Si le rapport a été uploadé avec succès, optionnellement le déposer aussi
                if (isset($_POST['deposer_apres_upload']) && $_POST['deposer_apres_upload'] === '1' && isset($resultat['id_rapport'])) {
                    $this->service->enregistrerDepotRapport($resultat['id_rapport'], $num_etu);
                }
            } else {
                $_SESSION['error'] = $resultat['message'];
            }

            header('Location: ?page=gestion_rapports&action=telecharger_rapport');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: ?page=gestion_rapports&action=telecharger_rapport');
            exit;
        }
    }

    /**
     * Télécharge le modèle de rapport
     */
    public function supprimerRapport()
    {
        try {
            if (!$this->isEtudiant() || !canDelete('gestion_rapports')) {
                $_SESSION['error'] = "AccÃ¨s non autorisÃ©.";
                header('Location: ?page=gestion_rapports');
                exit;
            }

            $idRapport = isset($_POST['id_rapport']) ? (int) $_POST['id_rapport'] : 0;
            if ($idRapport <= 0) {
                $_SESSION['error'] = "Rapport introuvable.";
                header('Location: ?page=gestion_rapports');
                exit;
            }

            $result = $this->service->supprimerBrouillonRapport($idRapport, (string) $_SESSION['num_etu']);
            $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
            header('Location: ?page=gestion_rapports');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: ?page=gestion_rapports');
            exit;
        }
    }

    public function downloadModele()
    {
        $cheminModele = $this->service->getModeleRapportUrl();
        $cheminAbsolu = __DIR__ . '/../../' . $cheminModele;

        if (file_exists($cheminAbsolu)) {
            while (ob_get_level())
                ob_end_clean();
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($cheminAbsolu) . '"');
            header('Content-Length: ' . filesize($cheminAbsolu));
            readfile($cheminAbsolu);
            exit;
        }

        $_SESSION['error'] = "Le modèle de rapport n'est pas disponible actuellement.";
        header('Location: ?page=gestion_rapports&action=telecharger_rapport');
        exit;
    }

    /**
     * Télécharge le PDF d'un rapport.
     * L'ancien fichier source uploadé n'est plus servi directement.
     */
    public function downloadFichierRapport()
    {
        $id_rapport = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id_rapport) {
            $_SESSION['error'] = "ID du rapport manquant.";
            header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
            exit;
        }

        $rapport = $this->service->getRapportById($id_rapport);
        if (!$rapport) {
            $_SESSION['error'] = "Rapport introuvable.";
            header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
            exit;
        }

        $isAuthorized = $this->isAdminGroup();

        if (!$isAuthorized && $this->isEtudiant()) {
            $rapportModel = new RapportEtudiant(Database::getConnection());
            $isAuthorized = (bool) $rapportModel->getRapportByIdAndEtudiant($id_rapport, (string) $_SESSION['num_etu']);
        }

        if (!$isAuthorized) {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: ?page=gestion_rapports&action=telecharger_rapport');
            exit;
        }

        header('Location: ?page=docviewer&type=rapport&id=' . $id_rapport . '&action=download');
        exit;
    }

    // ======================== PRD 2 : Import rapport côté administration ========================

    /**
     * Affiche la page d'import de rapport pour l'administration (PRD 2)
     */
    public function adminTelechargerRapport()
    {
        if (!$this->hasRapportAdminPermission('voir')) {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: ?page=gestion_rapports&action=telecharger_rapport');
            exit;
        }

        // URL du modèle
        $GLOBALS['modeleRapportUrl'] = $this->service->getModeleRapportUrl();

        // Liste des étudiants sans rapport
        $id_annee_acad = !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;
        $etudiantsSansRapport = $this->service->getEtudiantsSansRapport($id_annee_acad);
        $GLOBALS['etudiantsSansRapport'] = $etudiantsSansRapport;

        // Liste de tous les rapports
        $search = isset($_GET['search']) ? trim($_GET['search']) : null;
        $rapports = $this->service->getAllRapportsAdmin($id_annee_acad, $search);
        $GLOBALS['rapportsAdmin'] = $rapports;

        $GLOBALS['typesAutorises'] = 'pdf,doc,docx';
        $GLOBALS['tailleMax'] = 20 * 1024 * 1024; // 20 MB
    }

    /**
     * Traite l'upload de rapport par l'administration (PRD 2 F2.4)
     */
    public function traiterAdminUploadRapport()
    {
        try {
            if (!$this->hasRapportAdminPermission('creer')) {
                $_SESSION['error'] = "Accès non autorisé.";
                header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
                exit;
            }

            $num_etu = isset($_POST['num_etu']) ? trim($_POST['num_etu']) : '';
            $theme_rapport = isset($_POST['theme_rapport']) ? trim($_POST['theme_rapport']) : '';
            $date_operation = isset($_POST['date_operation']) ? trim($_POST['date_operation']) : date('Y-m-d H:i:s');

            if (empty($num_etu)) {
                $_SESSION['error'] = "Veuillez sélectionner un étudiant.";
                header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
                exit;
            }

            if (!isset($_FILES['rapport_fichier']) || $_FILES['rapport_fichier']['error'] === UPLOAD_ERR_NO_FILE) {
                $_SESSION['error'] = "Veuillez sélectionner un fichier à uploader.";
                header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
                exit;
            }

            // Traiter l'upload admin
            $resultat = $this->service->traiterUploadRapportAdmin(
                $_FILES['rapport_fichier'],
                $num_etu,
                $theme_rapport,
                $date_operation
            );

            if ($resultat['success']) {
                $_SESSION['success'] = $resultat['message'];
            } else {
                $_SESSION['error'] = $resultat['message'];
            }

            header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
            exit;
        }
    }

    /**
     * AJAX: retourne la liste des étudiants sans rapport (pour combo list)
     */
    public function getEtudiantsSansRapportAjax()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->hasRapportAdminPermission('voir')) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
            exit;
        }

        $search = isset($_GET['q']) ? trim($_GET['q']) : '';
        $id_annee_acad = !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;

        $etudiants = $this->service->getEtudiantsSansRapport($id_annee_acad);

        // Filtrer par terme de recherche
        if ($search !== '') {
            $etudiants = array_filter($etudiants, function ($e) use ($search) {
                $search = strtolower($search);
                return str_contains(strtolower($e->nom_etu ?? ''), $search)
                    || str_contains(strtolower($e->prenom_etu ?? ''), $search)
                    || str_contains(strtolower($e->num_carte_etud ?? ''), $search)
                    || str_contains(strtolower($e->num_ident_etud ?? ''), $search);
            });
        }

        // Reformater pour le select2
        $resultats = [];
        foreach ($etudiants as $e) {
            $resultats[] = [
                'id' => $e->num_carte_etud ?? $e->num_ident_etud,
                'text' => ($e->nom_etu ?? '') . ' ' . ($e->prenom_etu ?? '') . ' (' . ($e->num_ident_etud ?? $e->num_carte_etud ?? '') . ')'
            ];
        }

        echo json_encode(['success' => true, 'results' => $resultats]);
        exit;
    }

    /**
     * Export CSV de la liste des rapports (PRD 4 F4.3)
     */
    public function exporterRapportsCsv()
    {
        if (!$this->hasRapportAdminPermission('voir')) {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
            exit;
        }

        $id_annee_acad = !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;
        $search = isset($_GET['search']) ? trim($_GET['search']) : null;
        $rapports = $this->service->getAllRapportsAdmin($id_annee_acad, $search);

        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rapports_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        // En-têtes
        fputcsv($output, [
            'ID',
            'Matricule',
            'Nom',
            'Prénom',
            'Email',
            'Nom rapport',
            'Thème',
            'Statut',
            'Taille (Ko)',
            'Date dépôt',
            'Date opération',
            'Date modification'
        ]);

        foreach ($rapports as $r) {
            fputcsv($output, [
                $r->id_rapport ?? '',
                $r->num_etu ?? '',
                $r->nom_etu ?? '',
                $r->prenom_etu ?? '',
                $r->email_etu ?? '',
                $r->nom_rapport ?? '',
                $r->theme_rapport ?? '',
                $r->statut_rapport ?? '',
                $r->taille_fichier ? round($r->taille_fichier / 1024, 2) : '',
                $r->date_depot ?? '',
                $r->date_operation ?? '',
                $r->date_modification ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Met à jour la date d'opération d'un rapport (PRD 3)
     */
    public function updateRapportInline()
    {
        if (!$this->hasRapportAdminPermission('modifier')) {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
            exit;
        }

        $id_rapport = isset($_POST['id_rapport']) ? (int) $_POST['id_rapport'] : 0;
        $nouvelle_date = isset($_POST['date_operation']) ? trim($_POST['date_operation']) : '';
        $nom_rapport = isset($_POST['nom_rapport']) ? trim($_POST['nom_rapport']) : '';
        $theme_rapport = isset($_POST['theme_rapport']) ? trim($_POST['theme_rapport']) : '';

        if (!$id_rapport) {
            $_SESSION['error'] = "Paramètres manquants.";
            header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
            exit;
        }

        // Récupérer les anciennes valeurs pour l'audit
        $rapport = $this->service->getRapportById($id_rapport);
        $ancienne_date = $rapport['date_operation'] ?? '';
        $ancien_nom = $rapport['nom_rapport'] ?? '';
        $ancien_theme = $rapport['theme_rapport'] ?? '';

        $result = $this->service->updateRapportInlineWithAudit($id_rapport, $nouvelle_date, $ancienne_date, $nom_rapport, $ancien_nom, $theme_rapport, $ancien_theme);

        if ($result) {
            $_SESSION['success'] = "Rapport mis à jour avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour.";
        }

        header('Location: ?page=' . $this->getRapportAdminPage() . '&action=admin_telecharger_rapport');
        exit;
    }
}
