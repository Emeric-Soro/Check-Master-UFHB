<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../Services/MiseEnLigneMemoireService.php';
require_once __DIR__ . '/../Services/Document/DocumentStorageService.php';

use App\Services\Document\DocumentStorageService;

final class MiseEnLigneMemoireController
{
    private PDO $db;
    private MiseEnLigneMemoireService $service;
    private DocumentStorageService $storage;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->service = new MiseEnLigneMemoireService($this->db);
        $this->storage = new DocumentStorageService($this->db, dirname(__DIR__, 2));
    }

    public function handleRequest(): array
    {
        if (!canView('mise_en_ligne_memoire')) {
            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'accéder à cette page.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

        if ($requestMethod === 'POST') {
            if ($action === 'upload_memoire') {
                $this->handleUpload();
            }

            if ($action === 'supprimer_memoire') {
                $this->handleDelete();
            }
        }

        if ($action === 'telecharger') {
            $this->handleDownload();
        }

        return $this->service->getPageData();
    }

    private function handleUpload(): void
    {
        if (!canCreate('mise_en_ligne_memoire')) {
            $this->respond(['success' => false, 'message' => 'Accès non autorisé pour la mise en ligne du mémoire.']);
        }

        $userId = isset($_SESSION['id_utilisateur']) ? (int) $_SESSION['id_utilisateur'] : null;
        $result = $this->service->uploadMemoire($_POST, $_FILES, $userId);
        $this->respond($result);
    }

    private function handleDelete(): void
    {
        if (!canCreate('mise_en_ligne_memoire')) {
            $this->respond(['success' => false, 'message' => 'Accès non autorisé pour supprimer un mémoire.']);
        }

        $numEtu = trim((string) ($_POST['num_etu'] ?? ''));
        $result = $this->service->supprimerMemoire($numEtu);
        $this->respond($result);
    }

    private function handleDownload(): void
    {
        $numEtu = trim((string) ($_GET['num_etu'] ?? ''));
        if ($numEtu === '') {
            http_response_code(400);
            echo 'Etudiant manquant.';
            exit;
        }

        $document = $this->service->getMemoireDocumentByStudent($numEtu);
        if (!is_array($document)) {
            http_response_code(404);
            echo 'Mémoire introuvable.';
            exit;
        }

        $cachedPath = $this->storage->materializeToCache($document);
        if (!is_string($cachedPath) || $cachedPath === '' || !is_file($cachedPath)) {
            http_response_code(404);
            echo 'Fichier introuvable.';
            exit;
        }

        $fileName = trim((string) ($document['nom_fichier'] ?? 'memoire.pdf'));
        if ($fileName === '') {
            $fileName = 'memoire.pdf';
        }

        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string) filesize($cachedPath));
        header('Content-Disposition: inline; filename="' . rawurlencode($fileName) . '"');
        readfile($cachedPath);
        exit;
    }

    /**
     * @param array{success: bool, message: string} $result
     */
    private function respond(array $result): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($result);
            exit;
        }

        if (($result['success'] ?? false) === true) {
            $_SESSION['success'] = (string) ($result['message'] ?? '');
        } else {
            $_SESSION['error'] = (string) ($result['message'] ?? '');
        }

        header('Location: ?page=mise_en_ligne_memoire');
        exit;
    }
}
