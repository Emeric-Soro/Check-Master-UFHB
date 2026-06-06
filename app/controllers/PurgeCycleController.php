<?php
/**
 * PurgeCycleController
 *
 * Contrôleur dédié à la gestion ciblée du cycle étudiant.
 * Appelé par ressources/routes/parametreGenerauxRouteur.php
 * avec des méthodes individuelles par action.
 */
require_once __DIR__ . '/../Services/PurgeCycleService.php';
require_once __DIR__ . '/../Services/CycleReconstructionService.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\PurgeCycleService;
use CheckMaster\Services\CycleReconstructionService;
use CheckMaster\Security\PermissionRegistry;

class PurgeCycleController
{
    private $purgeService;
    private $rebuildService;
    private $pdo;

    public function __construct()
    {
        $this->pdo = \Database::getConnection();
        $this->purgeService = new PurgeCycleService($this->pdo);
        $this->rebuildService = new CycleReconstructionService($this->pdo);
    }

    // ================================================================
    //  PAGE PRINCIPALE (affichage vue)
    // ================================================================

    public function index(): void
    {
        if (!$this->isAdminOrRespFiliere() || !canView('purge_cycle_etudiant')) {
            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'accéder à cet écran.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        // La vue est chargée automatiquement par layout.php
        // On injecte juste les données nécessaires dans $GLOBALS
        $GLOBALS['purge_cycle_page'] = true;
    }

    // ================================================================
    //  AJAX — Recherche étudiants
    // ================================================================

    public function searchStudents(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isAdminOrRespFiliere() || !canView('purge_cycle_etudiant')) {
            $this->jsonForbidden();
        }

        $query = trim($_GET['q'] ?? '');
        if (mb_strlen($query) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        $results = $this->purgeService->searchStudents($query);
        echo json_encode(['success' => true, 'data' => $results]);
        exit;
    }

    // ================================================================
    //  AJAX — Inventaire du cycle
    // ================================================================

    public function getCycleInventory(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isAdminOrRespFiliere() || !canView('purge_cycle_etudiant')) {
            $this->jsonForbidden();
        }

        $numEtu = trim($_GET['num_etu'] ?? '');
        if ($numEtu === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Matricule requis.']);
            exit;
        }

        $inventory = $this->purgeService->getCycleInventory($numEtu);
        echo json_encode($inventory);
        exit;
    }

    // ================================================================
    //  AJAX — Brouillon de reconstruction
    // ================================================================

    public function getReconstructionDraft(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isAdminOrRespFiliere() || (!canCreate('purge_cycle_etudiant') && !canEdit('purge_cycle_etudiant'))) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Action non autorisée."]);
            exit;
        }

        $numEtu = trim($_GET['num_etu'] ?? '');
        if ($numEtu === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Matricule requis.']);
            exit;
        }

        $draft = $this->rebuildService->getReconstructionDraft($numEtu);
        echo json_encode($draft);
        exit;
    }

    // ================================================================
    //  POST — Dry-run (simulation)
    // ================================================================

    public function dryRunPurge(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isAdminOrRespFiliere() || !canDelete('purge_cycle_etudiant')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Action non autorisée."]);
            exit;
        }

        $numEtu = trim($_POST['num_etu'] ?? '');
        if ($numEtu === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Matricule requis.']);
            exit;
        }

        $result = $this->purgeService->dryRunPurge($numEtu);

        // Audit
        $this->purgeService->logAudit(
            $_SESSION['id_utilisateur'] ?? 0,
            'Simulation purge cycle étudiant: ' . $numEtu,
            $result['success'] ? 'Succès' : 'Erreur'
        );

        echo json_encode($result);
        exit;
    }

    // ================================================================
    //  POST — Purge (exécution)
    // ================================================================

    public function purge(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isAdminOrRespFiliere() || !canDelete('purge_cycle_etudiant')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Action non autorisée."]);
            exit;
        }

        // Validation confirmation
        if (empty($_POST['confirm_irreversible'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Confirmation irréversible requise.']);
            exit;
        }

        $numEtu = trim($_POST['num_etu'] ?? '');
        $confirmMatricule = trim($_POST['confirm_matricule'] ?? '');

        if ($numEtu === '' || $confirmMatricule === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Matricule et confirmation requis.']);
            exit;
        }

        if ($numEtu !== $confirmMatricule) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le matricule de confirmation ne correspond pas.']);
            exit;
        }

        // Vérifier CSRF
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalide.']);
            exit;
        }

        $confirmation = [
            'irreversible' => true,
            'matricule' => $confirmMatricule,
        ];

        $result = $this->purgeService->purge($numEtu, $confirmation);

        // Audit
        $summary = '';
        if ($result['success']) {
            $d = $result['details'] ?? [];
            $totalDeleted = array_sum($d['tables'] ?? []);
            $docsDeleted = ($d['documents']['deleted'] ?? 0);
            $docsKept = ($d['documents']['kept_shared'] ?? 0);
            $summary = "Tables: {$totalDeleted} lignes, Docs supprimés: {$docsDeleted}, Docs conservés: {$docsKept}";
        }
        $this->purgeService->logAudit(
            $_SESSION['id_utilisateur'] ?? 0,
            'Purge cycle étudiant: ' . $numEtu . ($summary ? ' - ' . $summary : ''),
            $result['success'] ? 'Succès' : 'Erreur'
        );

        echo json_encode($result);
        exit;
    }

    // ================================================================
    //  POST — Reconstruction
    // ================================================================

    public function rebuildCycle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isAdminOrRespFiliere() || (!canCreate('purge_cycle_etudiant') && !canEdit('purge_cycle_etudiant'))) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Action non autorisée."]);
            exit;
        }

        $numEtu = trim($_POST['num_etu'] ?? '');
        if ($numEtu === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Matricule requis.']);
            exit;
        }

        $payload = $_POST;
        unset($payload['csrf_token'], $payload['num_etu']);
        $payload = array_replace_recursive($payload, $this->normalizeUploadedFiles($_FILES));

        $result = $this->rebuildService->rebuildCycle($numEtu, $payload);

        // Audit
        $summary = '';
        if ($result['success']) {
            $created = $result['details']['created'] ?? [];
            $totalCreated = array_sum($created);
            $summary = "Tables: {$totalCreated} lignes créées";
        }
        $this->rebuildService->logAudit(
            $_SESSION['id_utilisateur'] ?? 0,
            'Reconstruction cycle étudiant: ' . $numEtu . ($summary ? ' - ' . $summary : ''),
            $result['success'] ? 'Succès' : 'Erreur'
        );

        echo json_encode($result);
        exit;
    }

    private function isAdminOrRespFiliere(): bool
    {
        $groups = PermissionRegistry::groups();
        $allowed = [
            $groups['administrateur'] ?? null,
            $groups['admin_responsable_filiere'] ?? null,
        ];
        $allowed = array_filter($allowed);
        return !empty($allowed)
            && isset($_SESSION['id_GU'])
            && in_array((int) $_SESSION['id_GU'], array_map('intval', $allowed), true);
    }

    private function jsonForbidden(): void
    {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => "Action non autorisée."]);
        exit;
    }

    private function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $section => $meta) {
            if (!is_array($meta) || !isset($meta['name'], $meta['tmp_name'], $meta['error'])) {
                continue;
            }

            foreach ((array) $meta['name'] as $field => $name) {
                $error = $meta['error'][$field] ?? UPLOAD_ERR_NO_FILE;
                $tmpName = $meta['tmp_name'][$field] ?? '';
                if ($error !== UPLOAD_ERR_OK || !is_string($tmpName) || $tmpName === '' || !is_uploaded_file($tmpName)) {
                    continue;
                }

                $fileName = is_string($name) && $name !== '' ? $name : 'document.pdf';
                $content = file_get_contents($tmpName);
                if ($content === false || $content === '') {
                    continue;
                }

                $normalized[$section][$field] = [
                    'name' => $fileName,
                    'content' => $content,
                    'mime' => (string) ($meta['type'][$field] ?? 'application/octet-stream'),
                    'extension' => pathinfo($fileName, PATHINFO_EXTENSION) ?: 'bin',
                    'size' => (int) ($meta['size'][$field] ?? strlen($content)),
                ];
            }
        }

        return $normalized;
    }
}
