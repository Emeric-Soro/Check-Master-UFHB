<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Archive.php';
require_once __DIR__ . '/../utils/ExcelImportService.php';
require_once __DIR__ . '/../models/AuditLog.php';

/**
 * Archive Controller - Handles history and archiving operations
 */
class ArchiveController
{
    private $archive;
    private $importService;
    private $auditLog;
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->archive = new Archive($this->db);
        $this->importService = new ExcelImportService($this->db);
        $this->auditLog = new AuditLog($this->db);
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
            $perPage = 20;
            $offset = ($page - 1) * $perPage;
            
            // Get data based on active tab
            if ($tab === 'students') {
                $students = $this->archive->getStudentHistory($anneeAcad, $statut, $search, $perPage, $offset);
                $totalStudents = $this->archive->countStudents($anneeAcad, $statut, $search);
                $totalPages = ceil($totalStudents / $perPage);

                $GLOBALS['students'] = $students;
                $GLOBALS['totalPages'] = $totalPages;
                $GLOBALS['currentPage'] = $page;
            } elseif ($tab === 'jury') {
                $juries = $this->archive->getJuryHistory($anneeAcad, null, $perPage, $offset);
                $GLOBALS['juries'] = $juries;
            } elseif ($tab === 'stats') {
                $GLOBALS['globalStats'] = $this->archive->getGlobalStats();
                $GLOBALS['yearlyEvolution'] = $this->archive->getYearlyEvolution();
                $GLOBALS['mentionsDistribution'] = $this->archive->getMentionsDistribution();
                $GLOBALS['topEntreprises'] = $this->archive->getTopEntreprises();
            }
            
            // Get available years for filter
            $GLOBALS['academicYears'] = $this->archive->getAcademicYears();
            $GLOBALS['currentTab'] = $tab;
            $GLOBALS['filters'] = [
                'annee' => $anneeAcad,
                'statut' => $statut,
                'search' => $search
            ];
            
            // Messages
            $GLOBALS['messageSuccess'] = $_SESSION['archive_success'] ?? '';
            $GLOBALS['messageErreur'] = $_SESSION['archive_error'] ?? '';
            unset($_SESSION['archive_success'], $_SESSION['archive_error']);
            
        } catch (Exception $e) {
            error_log("Error in ArchiveController::index: " . $e->getMessage());
            $_SESSION['archive_error'] = "Une erreur est survenue lors du chargement de l'historique.";
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
                $_SESSION['archive_error'] = "Matricule étudiant manquant.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            $studentFile = $this->archive->getStudentCompleteFile($numEtu);
            
            if (!$studentFile) {
                $_SESSION['archive_error'] = "Étudiant non trouvé.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            $GLOBALS['studentFile'] = $studentFile;
            $GLOBALS['messageSuccess'] = $_SESSION['archive_success'] ?? '';
            $GLOBALS['messageErreur'] = $_SESSION['archive_error'] ?? '';
            unset($_SESSION['archive_success'], $_SESSION['archive_error']);
            
            // Load detail view
           
        } catch (Exception $e) {
            error_log("Error in ArchiveController::viewStudentFile: " . $e->getMessage());
            $_SESSION['archive_error'] = "Une erreur est survenue lors du chargement du dossier étudiant.";
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
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=admin_historique');
                exit;
            }
            
            $numEtu = $_POST['num_etu'] ?? null;
            
            if (!$numEtu) {
                $_SESSION['archive_error'] = "Matricule étudiant manquant.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            // Prepare update data
            $updateData = [];
            
            // Basic info
            if (isset($_POST['nom_etu'])) {
                $updateData['nom_etu'] = $_POST['nom_etu'];
            }
            if (isset($_POST['prenom_etu'])) {
                $updateData['prenom_etu'] = $_POST['prenom_etu'];
            }
            if (isset($_POST['email_etu'])) {
                $updateData['email_etu'] = $_POST['email_etu'];
            }
            
            // Rapport info
            if (isset($_POST['theme_rapport']) || isset($_POST['statut_rapport'])) {
                $updateData['rapport'] = [];
                if (isset($_POST['theme_rapport'])) {
                    $updateData['rapport']['theme_rapport'] = $_POST['theme_rapport'];
                }
                if (isset($_POST['statut_rapport'])) {
                    $updateData['rapport']['statut_rapport'] = $_POST['statut_rapport'];
                }
            }
            
            // Update the student
            $success = $this->archive->updateStudentInfo($numEtu, $updateData);
            
            if ($success) {
                // Log the action
                if (isset($_SESSION['id_utilisateur'])) {
                    $this->auditLog->logModification(
                        $_SESSION['id_utilisateur'],
                        'Archive Étudiant',
                        'Succès'
                    );
                }
                
                $_SESSION['archive_success'] = "Dossier étudiant mis à jour avec succès.";
            } else {
                $_SESSION['archive_error'] = "Erreur lors de la mise à jour du dossier.";
            }
            
            header("Location: ?page=admin_historique&action=view_student&num_etu=$numEtu");
            exit;
            
        } catch (Exception $e) {
            error_log("Error in ArchiveController::updateStudentFile: " . $e->getMessage());
            $_SESSION['archive_error'] = "Une erreur est survenue lors de la mise à jour.";
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
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=admin_historique');
                exit;
            }
            
            // Check if file was uploaded
            if (!isset($_FILES['archive_file']) || $_FILES['archive_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['archive_error'] = "Aucun fichier uploadé ou erreur lors de l'upload.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            $file = $_FILES['archive_file'];
            $fileName = $file['name'];
            $fileTmpPath = $file['tmp_name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validate file type
            $allowedExtensions = ['csv', 'xlsx', 'xls'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $_SESSION['archive_error'] = "Type de fichier non supporté. Veuillez utiliser CSV, XLS ou XLSX.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            // For now, only CSV is fully supported
            if ($fileExtension !== 'csv') {
                $_SESSION['archive_error'] = "Pour le moment, seuls les fichiers CSV sont supportés. Veuillez convertir votre fichier Excel en CSV.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            // Process the import
            $success = $this->importService->importFile($fileTmpPath, $fileExtension);
            
            $summary = $this->importService->getSummary();
            
            // Log the import
            if (isset($_SESSION['id_utilisateur'])) {
                $this->auditLog->logAction(
                    $_SESSION['id_utilisateur'],
                    'Import',
                    'Archive',
                    $summary['total_errors'] > 0 ? 'Succès' : 'Succès'
                );
            }
            
            // Store results in session
            $_SESSION['import_summary'] = $summary;
            
            if ($summary['total_errors'] > 0) {
                $_SESSION['archive_error'] = "Import terminé avec {$summary['total_errors']} erreur(s). Consultez les détails ci-dessous.";
            } else {
                $_SESSION['archive_success'] = "Import réussi! {$summary['total_success']} enregistrement(s) importé(s).";
            }
            
            header('Location: ?page=admin_historique&action=import_result');
            exit;
            
        } catch (Exception $e) {
            error_log("Error in ArchiveController::importArchive: " . $e->getMessage());
            $_SESSION['archive_error'] = "Une erreur est survenue lors de l'import: " . $e->getMessage();
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
        $GLOBALS['messageSuccess'] = $_SESSION['archive_success'] ?? '';
        $GLOBALS['messageErreur'] = $_SESSION['archive_error'] ?? '';
        
        // Clear the session data after displaying
        unset($_SESSION['import_summary'], $_SESSION['archive_success'], $_SESSION['archive_error']);
        
        // Load result view
    }
    
    /**
     * Export history data (future enhancement)
     */
    public function exportHistory()
    {
        // TODO: Implement export functionality
        $_SESSION['archive_error'] = "Fonctionnalité d'export non encore implémentée.";
        header('Location: ?page=admin_historique');
        exit;
    }
}
