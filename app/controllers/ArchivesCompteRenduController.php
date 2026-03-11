<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/ArchivesCompteRenduService.php';

use CheckMaster\Services\ArchivesCompteRenduService;

class ArchivesCompteRenduController
{
    private $service;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->service = new ArchivesCompteRenduService($db);
    }

    public function index()
    {
        try {
            $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
            $year = isset($_GET['year']) ? trim((string) $_GET['year']) : null;
            $pageNum = max(1, (int) ($_GET['page_num'] ?? 1));
            $limit = max(5, (int) ($_GET['limit_archive_cr'] ?? 10));

            $data = $this->service->getPaginatedArchives($search, $year, $pageNum, $limit);
            $stats = $this->service->getStats();

            $GLOBALS['archives'] = $data['archives'];
            $GLOBALS['currentPage'] = $pageNum;
            $GLOBALS['totalPages'] = $data['totalPages'];
            $GLOBALS['totalArchives'] = $data['totalArchives'];
            $GLOBALS['search'] = $search;
            $GLOBALS['year'] = $year;
            $GLOBALS['limit_archive_cr'] = $limit;
            $GLOBALS['stats'] = $stats;
        } catch (Throwable $e) {
            error_log('Error ArchivesCompteRenduController::index: ' . $e->getMessage());
            $GLOBALS['archives'] = [];
            $GLOBALS['currentPage'] = 1;
            $GLOBALS['totalPages'] = 1;
            $GLOBALS['totalArchives'] = 0;
            $GLOBALS['search'] = null;
            $GLOBALS['year'] = null;
            $GLOBALS['limit_archive_cr'] = 10;
            $GLOBALS['stats'] = [];
        }
    }

    public function viewArchive()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo 'Archive introuvable';
            return;
        }

        $archive = $this->service->getArchiveById($id);
        if (!$archive) {
            http_response_code(404);
            echo 'Archive introuvable';
            return;
        }

        $content = (string) ($archive['contenu_CR'] ?? '');
        if ($content === '') {
            echo '<p>Aucun contenu.</p>';
            return;
        }
        echo $content;
    }

    public function deleteArchive()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = $_POST['id_CR'] ?? $_POST['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID manquant.']);
            return;
        }

        $ok = $this->service->deleteArchive($id);
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Suppression impossible.']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Archive supprimee.']);
    }

    public function searchArchives()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
        $year = isset($_GET['year']) ? trim((string) $_GET['year']) : null;
        $pageNum = max(1, (int) ($_GET['page_num'] ?? 1));
        $limit = max(5, (int) ($_GET['limit_archive_cr'] ?? 10));

        $data = $this->service->getPaginatedArchives($search, $year, $pageNum, $limit);
        echo json_encode([
            'success' => true,
            'archives' => $data['archives'],
            'total' => $data['totalArchives'],
            'pages' => $data['totalPages'],
            'current' => $pageNum,
        ]);
    }

    public function exportArchives()
    {
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
        $year = isset($_GET['year']) ? trim((string) $_GET['year']) : null;
        $data = $this->service->getPaginatedArchives($search, $year, 1, 10000);
        $rows = is_array($data['archives']) ? $data['archives'] : [];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=\"archives_comptes_rendus.csv\"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fputcsv($out, ['ID', 'Nom CR', 'Nom', 'Prenom', 'Email', 'Date'], ';');
        foreach ($rows as $archive) {
            fputcsv($out, [
                $archive['id_CR'] ?? '',
                $archive['nom_CR'] ?? '',
                $archive['nom_etu'] ?? '',
                $archive['prenom_etu'] ?? '',
                $archive['email_etu'] ?? '',
                $archive['date_CR'] ?? '',
            ], ';');
        }
        fclose($out);
    }

    public function telechargerPDF(string $chemin)
    {
        $relative = ltrim(str_replace(['..', '\\'], ['', '/'], $chemin), '/');
        $root = realpath(__DIR__ . '/../../');
        $fullPath = realpath($root . DIRECTORY_SEPARATOR . $relative);

        if (!$root || !$fullPath || strpos($fullPath, $root) !== 0 || !is_file($fullPath)) {
            http_response_code(404);
            echo 'Fichier introuvable.';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename=\"' . basename($fullPath) . '\"');
        readfile($fullPath);
    }
}
