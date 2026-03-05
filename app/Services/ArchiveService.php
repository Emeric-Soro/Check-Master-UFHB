<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Archive.php';
require_once __DIR__ . '/../utils/ExcelImportService.php';
require_once __DIR__ . '/../models/AuditLog.php';

use Archive;
use AuditLog;
use ExcelImportService;

/**
 * ArchiveService – business logic extracted from ArchiveController.
 */
class ArchiveService
{
    private $archive;
    private $importService;
    private $auditLog;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->archive = new Archive($db);
        $this->importService = new ExcelImportService($db);
        $this->auditLog = new AuditLog($db);
    }

    // ------------------------------------------------------------------
    //  Index page data
    // ------------------------------------------------------------------

    /**
     * Build the dataset for the archive index page.
     *
     * @return array{
     *   students?: array,
     *   totalPages?: int,
     *   currentPage?: int,
     *   juries?: array,
     *   globalStats?: mixed,
     *   yearlyEvolution?: mixed,
     *   mentionsDistribution?: mixed,
     *   topEntreprises?: mixed,
     *   academicYears: array,
     *   currentTab: string,
     *   filters: array
     * }
     */
    public function getIndexData(
        string $tab = 'vue_ensemble',
        ?string $anneeAcad = null,
        ?string $statut = null,
        ?string $search = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $offset = ($page - 1) * $perPage;
        $data = [];

        if ($tab === 'vue_ensemble') {
            $data['quick_stats'] = [
                'taux_reussite' => $this->archive->getTauxReussite($anneeAcad),
                'moyenne_generale' => $this->archive->getMoyenneGenerale($anneeAcad),
                'jours_soutenance' => $this->archive->getJoursSoutenance($anneeAcad),
                'total_etudiants' => $this->archive->countStudents($anneeAcad),
            ];
            $data['timeline'] = $this->archive->getTimeline($anneeAcad);
            $data['derniers_etudiants'] = $this->archive->getStudentHistory($anneeAcad, null, null, 5, 0);
        } elseif ($tab === 'students') {
            $data['students']    = $this->archive->getStudentHistory($anneeAcad, $statut, $search, $perPage, $offset);
            $totalStudents       = $this->archive->countStudents($anneeAcad, $statut, $search);
            $data['totalPages']  = (int) ceil($totalStudents / $perPage);
            $data['currentPage'] = $page;
        } elseif ($tab === 'jury') {
            $data['juries'] = $this->archive->getJuryHistory($anneeAcad, null, $perPage, $offset);
        } elseif ($tab === 'stats') {
            $data['globalStats']            = $this->archive->getGlobalStats();
            $data['yearlyEvolution']        = $this->archive->getYearlyEvolution();
            $data['mentionsDistribution']   = $this->archive->getMentionsDistribution();
            $data['topEntreprises']         = $this->archive->getTopEntreprises();
        }

        $data['academicYears'] = $this->archive->getAcademicYears();
        $data['currentTab']    = $tab;
        $data['filters']       = [
            'annee'  => $anneeAcad,
            'statut' => $statut,
            'search' => $search,
        ];

        return $data;
    }

    // ------------------------------------------------------------------
    //  Student file
    // ------------------------------------------------------------------

    /**
     * Retrieve the full student file.
     *
     * @return array|null  The student file or null if not found.
     */
    public function getStudentFile(string $numEtu): ?array
    {
        $file = $this->archive->getStudentCompleteFile($numEtu);
        return $file ?: null;
    }

    // ------------------------------------------------------------------
    //  Update student
    // ------------------------------------------------------------------

    /**
     * Build the update‑data array from raw POST values and persist.
     *
     * @return bool  Whether the update succeeded.
     */
    public function updateStudentInfo(string $numEtu, array $postData): bool
    {
        $updateData = [];

        if (isset($postData['nom_etu'])) {
            $updateData['nom_etu'] = $postData['nom_etu'];
        }
        if (isset($postData['prenom_etu'])) {
            $updateData['prenom_etu'] = $postData['prenom_etu'];
        }
        if (isset($postData['email_etu'])) {
            $updateData['email_etu'] = $postData['email_etu'];
        }

        if (isset($postData['theme_rapport']) || isset($postData['statut_rapport'])) {
            $updateData['rapport'] = [];
            if (isset($postData['theme_rapport'])) {
                $updateData['rapport']['theme_rapport'] = $postData['theme_rapport'];
            }
            if (isset($postData['statut_rapport'])) {
                $updateData['rapport']['statut_rapport'] = $postData['statut_rapport'];
            }
        }

        return $this->archive->updateStudentInfo($numEtu, $updateData);
    }

    // ------------------------------------------------------------------
    //  Import
    // ------------------------------------------------------------------

    /**
     * Validate and import an uploaded archive file.
     *
     * Returns an array with keys:
     *   - success (bool)
     *   - error   (string|null)   – set when validation fails *before* import
     *   - summary (array|null)     – set after a (possibly partial) import
     *
     * @param  array  $file  The $_FILES['archive_file'] entry.
     * @return array{success: bool, error: ?string, summary: ?array}
     */
    public function importArchiveFile(array $file): array
    {
        // --- Presence check ---
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => "Aucun fichier uploadé ou erreur lors de l'upload.", 'summary' => null];
        }

        $fileName      = $file['name'];
        $fileTmpPath   = $file['tmp_name'];
        $fileSize      = (int) ($file['size'] ?? 0);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // --- Upload authenticity & size ---
        if (!is_string($fileTmpPath) || $fileTmpPath === '' || !is_uploaded_file($fileTmpPath)) {
            return ['success' => false, 'error' => "Upload invalide (fichier non reconnu).", 'summary' => null];
        }
        if ($fileSize <= 0 || $fileSize > 10 * 1024 * 1024) {
            return ['success' => false, 'error' => "Fichier trop volumineux (max 10 MB).", 'summary' => null];
        }

        // --- Extension ---
        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        if (!in_array($fileExtension, $allowedExtensions, true)) {
            return ['success' => false, 'error' => "Type de fichier non supporté. Veuillez utiliser CSV, XLS ou XLSX.", 'summary' => null];
        }
        if ($fileExtension !== 'csv') {
            return ['success' => false, 'error' => "Pour le moment, seuls les fichiers CSV sont supportés. Veuillez convertir votre fichier Excel en CSV.", 'summary' => null];
        }

        // --- MIME ---
        $finfo       = new \finfo(FILEINFO_MIME_TYPE);
        $mime        = $finfo->file($fileTmpPath) ?: '';
        $allowedMimes = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'text/x-csv'];
        if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
            return ['success' => false, 'error' => "Type MIME non autorisé pour CSV ($mime).", 'summary' => null];
        }

        // --- Delegate to import service ---
        $this->importService->importFile($fileTmpPath, $fileExtension);
        $summary = $this->importService->getSummary();

        return ['success' => true, 'error' => null, 'summary' => $summary];
    }

    // ------------------------------------------------------------------
    //  Audit helpers
    // ------------------------------------------------------------------

    /**
     * Log a modification action.
     */
    public function logModification(int $userId, string $target, string $status): void
    {
        $this->auditLog->logModification($userId, $target, $status);
    }

    /**
     * Log a generic action.
     */
    public function logAction(int $userId, string $action, string $target, string $status): void
    {
        $this->auditLog->logAction($userId, $action, $target, $status);
    }
}