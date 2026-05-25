<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/ArchiveService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\ArchiveService;

/**
 * Archive Controller - Handles history and archiving operations.
 *
 * HTTP / session concerns only – all business logic lives in ArchiveService.
 */
class ArchiveController
{
    private $service;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->service = new ArchiveService($db);
    }

    /**
     * Display the main archive page
     */
    public function index()
    {
        try {
            // Get filters from request
            $tab = $_GET['tab'] ?? 'students';
            $anneeAcad = $_GET['annee'] ?? null;
            $statut = $_GET['statut'] ?? null;
            $search = $_GET['search'] ?? null;
            $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
            $perPage = 10;

            // Delegate to service
            $data = $this->service->getIndexData($tab, $anneeAcad, $statut, $search, $page, $perPage);

            // Expose data to views via $GLOBALS
            foreach ($data as $key => $value) {
                $GLOBALS[$key] = $value;
            }

            // Messages
            $GLOBALS['messageSuccess'] = $_SESSION['success'] ?? '';
            $GLOBALS['messageErreur'] = $_SESSION['error'] ?? '';
            unset($_SESSION['success'], $_SESSION['error']);

        } catch (Exception $e) {
            error_log("Error in ArchiveController::index: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors du chargement de l'historique.";
            header('Location: ?page=dashboard');
            exit;
        }
    }

    /**
     * Display student detail file
     */
    public function viewStudentFile()
    {
        try {
            $numEtu = $_GET['num_etu'] ?? null;

            if (!$numEtu) {
                $_SESSION['error'] = "Matricule étudiant manquant.";
                header('Location: ?page=admin_historique');
                exit;
            }

            $studentFile = $this->service->getStudentFile($numEtu);

            if (!$studentFile) {
                $_SESSION['error'] = "Étudiant non trouvé.";
                header('Location: ?page=admin_historique');
                exit;
            }

            $GLOBALS['studentFile'] = $studentFile;
            $GLOBALS['messageSuccess'] = $_SESSION['success'] ?? '';
            $GLOBALS['messageErreur'] = $_SESSION['error'] ?? '';
            unset($_SESSION['success'], $_SESSION['error']);

            // Load detail view

        } catch (Exception $e) {
            error_log("Error in ArchiveController::viewStudentFile: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors du chargement du dossier étudiant.";
            header('Location: ?page=admin_historique');
            exit;
        }
    }

    /**
     * Update student file
     */
    public function updateStudentFile()
    {
        try {
            if (!canEdit('admin_historique')) {
                $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                header('Location: ?page=admin_historique');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=admin_historique');
                exit;
            }

            $numEtu = $_POST['num_etu'] ?? null;

            if (!$numEtu) {
                $_SESSION['error'] = "Matricule étudiant manquant.";
                header('Location: ?page=admin_historique');
                exit;
            }

            // Delegate update to service
            $success = $this->service->updateStudentInfo($numEtu, $_POST);

            if ($success) {
                // Log the action
                if (isset($_SESSION['id_utilisateur'])) {
                    $this->service->logModification(
                        $_SESSION['id_utilisateur'],
                        'Archive Étudiant',
                        'Succès'
                    );
                }

                $_SESSION['success'] = "Dossier étudiant mis à jour avec succès.";
            } else {
                $_SESSION['error'] = "Erreur lors de la mise à jour du dossier.";
            }

            header("Location: ?page=admin_historique&action=view_student&num_etu=$numEtu");
            exit;

        } catch (Exception $e) {
            error_log("Error in ArchiveController::updateStudentFile: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour.";
            header('Location: ?page=admin_historique');
            exit;
        }
    }

    /**
     * Handle file import
     */
    public function importArchive()
    {
        try {
            if (!canCreate('admin_historique')) {
                $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                header('Location: ?page=admin_historique');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=admin_historique');
                exit;
            }

            // Delegate validation + import to service
            $result = $this->service->importArchiveFile($_FILES['archive_file'] ?? []);

            if (!$result['success']) {
                $_SESSION['error'] = $result['error'];
                header('Location: ?page=admin_historique');
                exit;
            }

            $summary = $result['summary'];

            // Log the import
            if (isset($_SESSION['id_utilisateur'])) {
                $this->service->logAction(
                    $_SESSION['id_utilisateur'],
                    'Import',
                    'Archive',
                    $summary['total_errors'] > 0 ? 'Succès' : 'Succès'
                );
            }

            // Store results in session
            $_SESSION['import_summary'] = $summary;

            if ($summary['total_errors'] > 0) {
                $_SESSION['error'] = "Import terminé avec {$summary['total_errors']} erreur(s). Consultez les détails ci-dessous.";
            } else {
                $_SESSION['success'] = "Import réussi! {$summary['total_success']} enregistrement(s) importé(s).";
            }

            header('Location: ?page=admin_historique&action=import_result');
            exit;

        } catch (Exception $e) {
            error_log("Error in ArchiveController::importArchive: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de l'import: " . $e->getMessage();
            header('Location: ?page=admin_historique');
            exit;
        }
    }

    /**
     * Display import results
     */
    public function showImportResult()
    {
        $summary = $_SESSION['import_summary'] ?? null;

        if (!$summary) {
            header('Location: ?page=admin_historique');
            exit;
        }

        $GLOBALS['importSummary'] = $summary;
        $GLOBALS['messageSuccess'] = $_SESSION['success'] ?? '';
        $GLOBALS['messageErreur'] = $_SESSION['error'] ?? '';

        // Clear the session data after displaying
        unset($_SESSION['import_summary'], $_SESSION['success'], $_SESSION['error']);

        // Load result view
    }

    /**
     * Export history data en CSV
     */
    public function exportHistory()
    {
        try {
            if (!canCreate('admin_historique')) {
                $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
                header('Location: ?page=admin_historique');
                exit;
            }

            // Recuperer les filtres
            $tab = $_GET['tab'] ?? 'students';
            $anneeAcad = $_GET['annee'] ?? null;
            $statut = $_GET['statut'] ?? null;
            $search = $_GET['search'] ?? null;

            // Definir les en-tetes CSV
            $filename = 'historique_' . $tab . '_' . date('Y-m-d_H-i-s') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            $output = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Ligne d'en-tete
            $headers = [];
            switch ($tab) {
                case 'students':
                    $headers = ['Matricule', 'Nom', 'Prenom', 'Email', 'Promotion', 'Statut', 'Thème'];
                    break;
                case 'jury':
                    $headers = ['Enseignant', 'Rôle', 'Soutenance', 'Date'];
                    break;
                case 'vue_ensemble':
                case 'stats':
                default:
                    $headers = ['Matricule', 'Nom', 'Prenom', 'Promotion', 'Statut', 'Moyenne'];
                    break;
            }
            fputcsv($output, $headers);

            // Recuperer les donnees via le service
            $this->service->exportHistoryToStream($output, $tab, $anneeAcad, $statut, $search);

            fclose($output);
            exit;

        } catch (Exception $e) {
            error_log("Error in ArchiveController::exportHistory: " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de l'export: " . $e->getMessage();
            header('Location: ?page=admin_historique');
            exit;
        }
    }
}
