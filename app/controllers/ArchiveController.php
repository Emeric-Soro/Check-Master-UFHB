<?php

namespace App\Controllers;

use PDO;
use App\Models\Archive;
use App\Utils\SecurityUtils;
use App\Utils\ExcelImportService;
use App\Models\AuditLog;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * ArchiveController - Moteur de recherche global archives
 * 
 * Ce contrôleur gère l'historique et les opérations d'archivage :
 * - Consultation de l'historique des étudiants et des jurys
 * - Visualisation et mise à jour des dossiers archivés
 * - Importation de données historiques
 * 
 * @package App\Controllers
 */
class ArchiveController
{
    private Archive $archive;
    private ExcelImportService $importService;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;
    private PDO $pdo;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        Archive $archive,
        ExcelImportService $importService,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger,
        PDO $pdo
    ) {
        $this->archive = $archive;
        $this->importService = $importService;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
        $this->pdo = $pdo;
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'admin_historique', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur admin_historique"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Action : Afficher la page principale des archives (READ)
     */
    public function index(): void
    {
        // 4. Sécurité Granulaire
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            // Récupération des filtres
            $tab = $this->security->sanitizeInput($_GET['tab'] ?? 'students');
            $anneeAcad = $this->security->sanitizeInput($_GET['annee'] ?? null);
            $statut = $this->security->sanitizeInput($_GET['statut'] ?? null);
            $search = $this->security->sanitizeInput($_GET['search'] ?? null);
            $page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
            $perPage = 20;
            $offset = ($page - 1) * $perPage;
            
            // Récupération des données selon l'onglet actif
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
            
            // Années académiques pour le filtre
            $GLOBALS['academicYears'] = $this->archive->getAcademicYears();
            $GLOBALS['currentTab'] = $tab;
            $GLOBALS['filters'] = [
                'annee' => $anneeAcad,
                'statut' => $statut,
                'search' => $search
            ];
            
            $GLOBALS['messageSuccess'] = $_SESSION['archive_success'] ?? '';
            $GLOBALS['messageErreur'] = $_SESSION['archive_error'] ?? '';
            unset($_SESSION['archive_success'], $_SESSION['archive_error']);
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans ArchiveController::index: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors du chargement de l'historique.";
        }
    }
    
    /**
     * Action : Afficher le dossier complet d'un étudiant (READ)
     */
    public function viewStudentFile(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $numEtu = $this->security->sanitizeInput($_GET['num_etu'] ?? null);
            
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
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans ArchiveController::viewStudentFile: " . $e->getMessage());
            $_SESSION['archive_error'] = "Une erreur est survenue lors du chargement du dossier étudiant.";
            header('Location: ?page=admin_historique');
            exit;
        }
    }
    
    /**
     * Action : Mettre à jour le dossier étudiant (UPDATE)
     */
    public function updateStudentFile(): void
    {
        if (!$this->checkPermission('update')) {
            return;
        }

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=admin_historique');
                exit;
            }
            
            $numEtu = $this->security->sanitizeInput($_POST['num_etu'] ?? null);
            
            if (!$numEtu) {
                $_SESSION['archive_error'] = "Matricule étudiant manquant.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            // Préparation des données de mise à jour
            $updateData = [];
            
            if (isset($_POST['nom_etu'])) {
                $updateData['nom_etu'] = $this->security->sanitizeInput($_POST['nom_etu']);
            }
            if (isset($_POST['prenom_etu'])) {
                $updateData['prenom_etu'] = $this->security->sanitizeInput($_POST['prenom_etu']);
            }
            if (isset($_POST['email_etu'])) {
                $updateData['email_etu'] = $this->security->sanitizeInput($_POST['email_etu']);
            }
            
            if (isset($_POST['theme_rapport']) || isset($_POST['statut_rapport'])) {
                $updateData['rapport'] = [];
                if (isset($_POST['theme_rapport'])) {
                    $updateData['rapport']['theme_rapport'] = $this->security->sanitizeInput($_POST['theme_rapport']);
                }
                if (isset($_POST['statut_rapport'])) {
                    $updateData['rapport']['statut_rapport'] = $this->security->sanitizeInput($_POST['statut_rapport']);
                }
            }
            
            $success = $this->archive->updateStudentInfo($numEtu, $updateData);
            
            if ($success) {
                $this->auditLog->logModification(
                    $_SESSION['id_utilisateur'] ?? 0,
                    'Archive Étudiant',
                    'Succès'
                );
                
                $_SESSION['archive_success'] = "Dossier étudiant mis à jour avec succès.";
                $this->logger->info("Dossier étudiant archivé mis à jour : $numEtu");
            } else {
                $_SESSION['archive_error'] = "Erreur lors de la mise à jour du dossier.";
                $this->auditLog->logModification($_SESSION['id_utilisateur'] ?? 0, 'Archive Étudiant', 'Erreur');
            }
            
            header("Location: ?page=admin_historique&action=view_student&num_etu=$numEtu");
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans ArchiveController::updateStudentFile: " . $e->getMessage());
            $_SESSION['archive_error'] = "Une erreur est survenue lors de la mise à jour.";
            header('Location: ?page=admin_historique');
            exit;
        }
    }
    
    /**
     * Action : Importer des données d'archive (CREATE)
     */
    public function importArchive(): void
    {
        if (!$this->checkPermission('create')) {
            return;
        }

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=admin_historique');
                exit;
            }
            
            if (!isset($_FILES['archive_file']) || $_FILES['archive_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['archive_error'] = "Aucun fichier uploadé ou erreur lors de l'upload.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            $file = $_FILES['archive_file'];
            $fileName = $file['name'];
            $fileTmpPath = $file['tmp_name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
                $_SESSION['archive_error'] = "Type de fichier non supporté. Veuillez utiliser CSV, XLS ou XLSX.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            if ($fileExtension !== 'csv') {
                $_SESSION['archive_error'] = "Pour le moment, seuls les fichiers CSV sont supportés.";
                header('Location: ?page=admin_historique');
                exit;
            }
            
            $success = $this->importService->importFile($fileTmpPath, $fileExtension);
            $summary = $this->importService->getSummary();
            
            $this->auditLog->logAction(
                $_SESSION['id_utilisateur'] ?? 0,
                'Import',
                'Archive',
                'Succès'
            );
            
            $_SESSION['import_summary'] = $summary;
            
            if ($summary['total_errors'] > 0) {
                $_SESSION['archive_error'] = "Import terminé avec " . $summary['total_errors'] . " erreur(s).";
            } else {
                $_SESSION['archive_success'] = "Import réussi! " . $summary['total_success'] . " enregistrement(s) importé(s).";
            }
            
            header('Location: ?page=admin_historique&action=import_result');
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans ArchiveController::importArchive: " . $e->getMessage());
            $_SESSION['archive_error'] = "Une erreur est survenue lors de l'import.";
            header('Location: ?page=admin_historique');
            exit;
        }
    }
    
    /**
     * Action : Afficher les résultats de l'import (READ)
     */
    public function showImportResult(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $summary = $_SESSION['import_summary'] ?? null;
        
        if (!$summary) {
            header('Location: ?page=admin_historique');
            exit;
        }
        
        $GLOBALS['importSummary'] = $summary;
        $GLOBALS['messageSuccess'] = $_SESSION['archive_success'] ?? '';
        $GLOBALS['messageErreur'] = $_SESSION['archive_error'] ?? '';
        
        unset($_SESSION['import_summary'], $_SESSION['archive_success'], $_SESSION['archive_error']);
    }
    
    /**
     * Action : Exporter les données de l'historique (READ)
     */
    public function exportHistory(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        $_SESSION['archive_error'] = "Fonctionnalité d'export non encore implémentée.";
        header('Location: ?page=admin_historique');
        exit;
    }
}

