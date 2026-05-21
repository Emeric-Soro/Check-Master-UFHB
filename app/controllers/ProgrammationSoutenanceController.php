<?php

require_once __DIR__ . '/../Services/ProgrammationSoutenanceService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../Services/Document/PlanningGeneratorService.php';
require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';
require_once __DIR__ . '/../utils/PlanningDataUtils.php';
require_once __DIR__ . '/../Support/Database.php';

use App\Services\Document\PdfGeneratorService;
use App\Services\Document\PlanningGeneratorService;
use App\Support\Database as AppDatabase;
use App\Utils\PlanningDataUtils;
use CheckMaster\Services\ProgrammationSoutenanceService;

class ProgrammationSoutenanceController
{
    private $service;
    private ?PlanningGeneratorService $planningGeneratorService = null;
    private ?PlanningDataUtils $planningDataUtils = null;

    public function __construct()
    {
        $this->service = new ProgrammationSoutenanceService();
    }

    /**
     * Recuperer tous les enseignants qui ont été une fois memebre de jury (pour affichage et modification)
     */
    public function getEnseignantJuryForView()
    {
        return $this->service->getEnseignantJury();
    }

    /**
     * Récupérer tous les étudiants avec rapport validé (pour affichage et modification)
     */
    public function getEtudiantsForView()
    {
        return $this->service->getEtudiantsForView();
    }

    /**
     * Récupérer les étudiants disponibles pour nouvelle programmation (non encore programmés)
     */
    public function getEtudiantsDisponiblesForView()
    {
        return $this->service->getEtudiantsDisponiblesForView();
    }

    /**
     * Récupérer tous les enseignants pour PHP (sans header JSON)
     */
    public function getEnseignantsForView()
    {
        return $this->service->getEnseignants();
    }

    /**
     * Récupérer toutes les salles pour PHP (sans header JSON)
     */
    public function getSallesForView()
    {
        return $this->service->getSalles();
    }

    /**
     * Récupérer les professeurs titulaires pour PHP (sans header JSON)
     */
    public function getProfesseursTitulairesForView()
    {
        return $this->service->getProfesseursTitulaires();
    }

    /**
     * Récupérer toutes les attributions pour PHP (sans header JSON)
     */
    public function getAttributionsForView()
    {
        return $this->service->getAttributions();
    }

    /**
     * Récupérer tous les étudiants disponibles (avec rapports validés par la commission)
     */
    public function getEtudiants()
    {
        try {
            $etudiants = $this->service->getEtudiantsValides();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $etudiants
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des étudiants : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer tous les enseignants disponibles
     */
    public function getEnseignants()
    {
        try {
            $enseignants = $this->service->getEnseignants();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $enseignants
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des enseignants : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer seulement les professeurs titulaires pour le poste de président
     */
    public function getProfesseursTitulaires()
    {
        try {
            $professeurs = $this->service->getProfesseursTitulaires();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $professeurs
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des professeurs titulaires : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer toutes les salles disponibles
     */
    public function getSalles()
    {
        try {
            $salles = $this->service->getSalles();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $salles
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des salles : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer toutes les attributions de jury (utilise composer_jury)
     */
    public function getAttributions()
    {
        try {
            $attributions = $this->service->getAttributions();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $attributions
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des attributions : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Créer une nouvelle attribution de jury
     */
    public function createAttribution()
    {
        try {
            if (!canCreate('programmation_soutenance')) {
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
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) || empty($input)) {
                $input = $_POST;
            }
            $result = $this->service->createAttribution($input);

            if (!empty($result['success']) && !empty($result['data']['id'])) {
                try {
                    $this->service->notifierAjoutJury($input);
                    $this->service->notifierProgrammationSoutenance((string)$result['data']['id'], $input);
                } catch (\Throwable $notifErr) {
                    error_log('Erreur notification programmation: ' . $notifErr->getMessage());
                }
            }

            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mettre à jour une attribution de jury
     */
    public function updateAttribution()
    {
        try {
            if (!canEdit('programmation_soutenance')) {
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
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) || empty($input)) {
                $input = $_POST;
            }
            $result = $this->service->updateAttribution($input);

            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Supprimer une attribution de jury
     */
    public function deleteAttribution()
    {
        try {
            if (!canDelete('programmation_soutenance')) {
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
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) || empty($input)) {
                $input = $_POST;
            }
            $result = $this->service->deleteAttribution($input['id'] ?? null);

            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Prévisualisation synthétique du planning (mode legacy).
     * Retourne le total et le nombre de soutenances par date.
     */
    public function getPlanningPreview()
    {
        if (!canView('programmation_soutenance')) {
            $this->jsonResponse([
                'success' => false,
                'error' => "Vous n'avez pas l'autorisation d'accéder à cette ressource.",
            ], 403);
            return;
        }

        try {
            $sessionId = isset($_GET['session_id']) && $_GET['session_id'] !== '' ? (int) $_GET['session_id'] : null;
            $dateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? (string) $_GET['date_from'] : null;
            $dateTo = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? (string) $_GET['date_to'] : null;

            $soutenances = $this->getPlanningDataUtils()->getSoutenancesForPlanning($sessionId, $dateFrom, $dateTo);
            $groupedByDate = [];
            foreach ($soutenances as $row) {
                $date = (string) ($row['date_soutenance'] ?? '');
                if ($date === '') {
                    continue;
                }
                if (!isset($groupedByDate[$date])) {
                    $groupedByDate[$date] = [
                        'count' => 0,
                        'conflits' => 0,
                    ];
                }
                $groupedByDate[$date]['count']++;
            }

            $conflits = $this->buildSalleConflicts($soutenances);
            foreach ($conflits as $conflict) {
                $date = (string) ($conflict['date_soutenance'] ?? '');
                if ($date !== '' && isset($groupedByDate[$date])) {
                    $groupedByDate[$date]['conflits']++;
                }
            }

            ksort($groupedByDate);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'total_soutenances' => count($soutenances),
                    'dates' => $groupedByDate,
                ],
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la prévisualisation du planning.',
            ], 500);
        }
    }

    /**
     * Détails d'une journée (mode legacy).
     */
    public function getDayDetails()
    {
        if (!canView('programmation_soutenance')) {
            $this->jsonResponse([
                'success' => false,
                'error' => "Vous n'avez pas l'autorisation d'accéder à cette ressource.",
            ], 403);
            return;
        }

        $date = trim((string) ($_GET['date'] ?? ''));
        if ($date === '') {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Date requise.',
            ], 400);
            return;
        }

        try {
            $soutenances = $this->getPlanningDataUtils()->getSoutenancesForPlanning(null, $date, $date);
            $salles = [];

            foreach ($soutenances as &$row) {
                $libSalle = trim((string) ($row['lib_salle'] ?? ''));
                if ($libSalle !== '') {
                    $salles[$libSalle] = true;
                }

                $juryDetails = $this->getPlanningDataUtils()->getJuryDetails((string) ($row['num_soutenance'] ?? ''));
                $row['jury'] = $this->flattenJuryDetails($juryDetails);
            }
            unset($row);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'soutenances' => $soutenances,
                    'salles_utilisees' => array_keys($salles),
                    'conflicts' => $this->buildSalleConflicts($soutenances),
                ],
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Erreur lors du chargement du détail de la journée.',
            ], 500);
        }
    }

    /**
     * Génération PDF de planning basé sur une sélection de soutenances.
     */
    public function generatePlanningPdf()
    {
        error_log('[generatePlanningPdf] Request received. Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
        
        if (!canView('programmation_soutenance')) {
            $this->jsonResponse([
                'success' => false,
                'error' => "Vous n'avez pas l'autorisation d'effectuer cette action.",
            ], 403);
            return;
        }

        try {
            $input = $this->readRequestInput();
            error_log('[generatePlanningPdf] Input: ' . json_encode($input));
            
            $selectedIds = $this->normalizeSelectedIds($input['selected_ids'] ?? $input['selectedIds'] ?? []);
            error_log('[generatePlanningPdf] Selected IDs: ' . json_encode($selectedIds));

            if (empty($selectedIds)) {
                $sessionId = isset($input['session_id']) && $input['session_id'] !== '' ? (int) $input['session_id'] : null;
                $dateFrom = isset($input['date_from']) && $input['date_from'] !== '' ? (string) $input['date_from'] : null;
                $dateTo = isset($input['date_to']) && $input['date_to'] !== '' ? (string) $input['date_to'] : null;

                if ($dateFrom === null && $dateTo === null) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => 'Aucune soutenance sélectionnée.',
                        'error_code' => 'no_selection',
                    ], 400);
                    return;
                }

                $rows = $this->getPlanningDataUtils()->getSoutenancesForPlanning($sessionId, $dateFrom, $dateTo);
                $selectedIds = array_values(array_filter(array_map(
                    static fn (array $row): string => trim((string) ($row['num_soutenance'] ?? '')),
                    $rows
                ), static fn (string $id): bool => $id !== ''));

                if (empty($selectedIds)) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => 'Aucune soutenance trouvée pour la période sélectionnée.',
                        'error_code' => 'no_soutenances_found',
                    ], 400);
                    return;
                }
            }

            $userId = (int) ($_SESSION['id_utilisateur'] ?? 0);
            $result = $this->getPlanningGeneratorService()->generateFromSelectedSoutenances($selectedIds, $userId);

            if (!($result['success'] ?? false)) {
                $status = match ((string) ($result['error_code'] ?? '')) {
                    'missing_soutenances' => 409,
                    'generation_failed' => 500,
                    default => 400,
                };
                $this->jsonResponse([
                    'success' => false,
                    'error' => (string) ($result['error'] ?? 'Erreur lors de la génération du planning PDF.'),
                    'error_code' => (string) ($result['error_code'] ?? 'generation_failed'),
                    'missing_ids' => $result['missing_ids'] ?? [],
                ], $status);
                return;
            }

            $reference = trim((string) ($result['reference'] ?? ''));
            $path = (string) ($result['path'] ?? '');
            $docId = $reference !== '' ? $reference : pathinfo($path, PATHINFO_FILENAME);
            $downloadUrl = '?page=docviewer&type=planning&id=' . urlencode($docId) . '&action=download';

            $this->jsonResponse([
                'success' => true,
                'reference' => $reference,
                'download_url' => $downloadUrl,
                'preview_url' => '?page=docviewer&type=planning&id=' . urlencode($docId) . '&action=preview',
            ]);
        } catch (Exception $e) {
            error_log(sprintf(
                '[ProgrammationSoutenanceController] generatePlanningPdf failed: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
            $this->jsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la génération du planning PDF.',
            ], 500);
        }
    }

    /**
     * Téléchargement d'un planning PDF via le DocViewer unifié.
     *
     * La méthode legacy acceptait un token base64 encodant le chemin.
     * Désormais, on redirige vers le DocViewer :
     *   - si le paramètre est un token base64, on extrait la référence depuis le nom de fichier
     *   - sinon on traite le paramètre comme une référence directe (PLN-YYYY-NNNNN)
     */
    public function downloadPlanningPdf()
    {
        if (!canView('programmation_soutenance')) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }

        $token = trim((string) ($_GET['file'] ?? ''));
        if ($token === '') {
            http_response_code(400);
            echo 'Paramètre fichier manquant.';
            return;
        }

        // Essayer de décoder comme base64 (legacy) ; en cas d'échec, utiliser le token brut
        $reference = $this->resolveReferenceFromToken($token);
        if ($reference === null || $reference === '') {
            http_response_code(400);
            echo 'Référence invalide.';
            return;
        }

        $redirectUrl = '?page=docviewer&type=planning&id=' . urlencode($reference) . '&action=download';
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Résout une référence planning (PLN-YYYY-NNNNN) depuis un token legacy ou direct.
     */
    private function resolveReferenceFromToken(string $token): ?string
    {
        // Si le token ressemble déjà à une référence PLN, l'utiliser directement
        if (preg_match('/^PLN-\d{4}-\d{5}$/', $token) === 1) {
            return $token;
        }

        // Tenter le décodage base64 (legacy)
        $decoded = $this->decodeFileToken($token);
        if ($decoded === null || $decoded === '') {
            return null;
        }

        // Extraire le nom de fichier (sans extension) du chemin décodé
        $filename = pathinfo($decoded, PATHINFO_FILENAME);
        if ($filename !== '' && preg_match('/^PLN-\d{4}-\d{5}$/', $filename) === 1) {
            return $filename;
        }

        // Fallback : tenter d'utiliser le décodage complet comme référence
        if (preg_match('/^[A-Za-z0-9_-]+$/', $decoded) === 1) {
            return $decoded;
        }

        return null;
    }

    private function getPlanningGeneratorService(): PlanningGeneratorService
    {
        if ($this->planningGeneratorService instanceof PlanningGeneratorService) {
            return $this->planningGeneratorService;
        }

        $db = new AppDatabase();
        $pdfGenerator = new PdfGeneratorService(
            __DIR__ . '/../../storage/documents',
            __DIR__ . '/../../public/image/logo_ufhb.png'
        );

        $this->planningDataUtils = new PlanningDataUtils($db);
        $this->planningGeneratorService = new PlanningGeneratorService(
            $pdfGenerator,
            $this->planningDataUtils,
            $db
        );

        return $this->planningGeneratorService;
    }

    private function getPlanningDataUtils(): PlanningDataUtils
    {
        if ($this->planningDataUtils instanceof PlanningDataUtils) {
            return $this->planningDataUtils;
        }
        $db = new AppDatabase();
        $this->planningDataUtils = new PlanningDataUtils($db);
        return $this->planningDataUtils;
    }

    private function readRequestInput(?string $rawInput = null): array
    {
        if (isset($GLOBALS['decoded_json_input']) && is_array($GLOBALS['decoded_json_input'])) {
            return $GLOBALS['decoded_json_input'];
        }

        $raw = $rawInput;
        if ($raw === null || $raw === '') {
            $raw = file_get_contents('php://input');
        }
        error_log('[readRequestInput] Raw input: ' . ($raw ?? 'NULL'));
        $json = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        if (is_array($json)) {
            return $json;
        }

        return is_array($_POST) ? $_POST : [];
    }

    /**
     * @param mixed $raw
     * @return array<int, string>
     */
    private function normalizeSelectedIds($raw): array
    {
        $ids = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = array_map('trim', explode(',', $raw));
            }
        }

        if (!is_array($raw)) {
            return [];
        }

        foreach ($raw as $id) {
            $value = trim((string) $id);
            if ($value === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
                continue;
            }
            $ids[$value] = $value;
        }

        return array_values($ids);
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        error_log('[jsonResponse] Sent response: ' . json_encode(['status' => $status, 'payload' => $payload]));
        exit;
    }

    private function flattenJuryDetails(array $juryDetails): string
    {
        $segments = [];
        if (!empty($juryDetails['president'])) {
            $segments[] = 'Président: ' . (string) $juryDetails['president'];
        }
        if (!empty($juryDetails['examinateur'])) {
            $segments[] = 'Examinateur: ' . implode(', ', (array) $juryDetails['examinateur']);
        }
        if (!empty($juryDetails['directeur'])) {
            $segments[] = 'Directeur: ' . (string) $juryDetails['directeur'];
        }
        if (!empty($juryDetails['encadreur'])) {
            $segments[] = 'Encadreur: ' . (string) $juryDetails['encadreur'];
        }
        if (!empty($juryDetails['maitre_stage'])) {
            $segments[] = 'Maître de stage: ' . (string) $juryDetails['maitre_stage'];
        }

        return implode(' | ', $segments);
    }

    /**
     * @param array<int, array<string, mixed>> $soutenances
     * @return array<int, array<string, mixed>>
     */
    private function buildSalleConflicts(array $soutenances): array
    {
        $bySlot = [];
        foreach ($soutenances as $row) {
            $date = (string) ($row['date_soutenance'] ?? '');
            $heure = substr((string) ($row['heure_soutenance'] ?? $row['heure_debut'] ?? ''), 0, 5);
            $salle = trim((string) ($row['lib_salle'] ?? ''));
            if ($date === '' || $heure === '' || $salle === '') {
                continue;
            }
            $normalizedSalle = function_exists('mb_strtolower')
                ? mb_strtolower($salle, 'UTF-8')
                : strtolower($salle);
            $slot = $date . '|' . $heure . '|' . $normalizedSalle;
            if (!isset($bySlot[$slot])) {
                $bySlot[$slot] = [
                    'date_soutenance' => $date,
                    'heure_soutenance' => $heure,
                    'lib_salle' => $salle,
                    'count' => 0,
                ];
            }
            $bySlot[$slot]['count']++;
        }

        $conflicts = [];
        foreach ($bySlot as $slot) {
            if ((int) ($slot['count'] ?? 0) <= 1) {
                continue;
            }
            $conflicts[] = [
                'type' => 'Salle en double',
                'date_soutenance' => (string) ($slot['date_soutenance'] ?? ''),
                'heure_soutenance' => (string) ($slot['heure_soutenance'] ?? ''),
                'lib_salle' => (string) ($slot['lib_salle'] ?? ''),
            ];
        }

        return $conflicts;
    }

    private function encodeFileToken(string $path): string
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }

    private function decodeFileToken(string $token): ?string
    {
        if ($token === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
            return null;
        }

        // Ne décoder que les tokens suffisamment longs pour être du vrai base64.
        // Les références courtes (PLN-YYYY-NNNNN) ne doivent pas être décodées.
        if (strlen($token) < 24) {
            return null;
        }

        $normalized = strtr($token, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);
        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        // Rejeter les chaînes contenant des null bytes
        if (str_contains($decoded, "\0")) {
            return null;
        }

        return $decoded;
    }
}
?>
