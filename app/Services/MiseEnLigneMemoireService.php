<?php
declare(strict_types=1);

require_once __DIR__ . '/Document/DocumentStorageService.php';
require_once __DIR__ . '/../utils/FormattingUtils.php';
require_once __DIR__ . '/../utils/NotificationService.php';
require_once __DIR__ . '/../Security/PermissionRegistry.php';

use App\Services\Document\DocumentStorageService;
use CheckMaster\Security\PermissionRegistry;

final class MiseEnLigneMemoireService
{
    private const MAX_FILE_SIZE = 20971520;
    private const TABLE_MEMOIRE_METADATA = 'memoire_metadonnees';

    private PDO $db;
    private DocumentStorageService $storage;

    /** @var array<string,bool> */
    private array $tableExistsCache = [];

    /** @var array<string,bool> */
    private array $columnExistsCache = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->storage = new DocumentStorageService($db, dirname(__DIR__, 2));
    }

    public function getPageData(): array
    {
        return [
            'etudiants' => $this->getEtudiantsAvecSoutenance(),
            'memoires' => $this->getMemoiresEnLigne(),
        ];
    }

    public function uploadMemoire(array $post, array $files, ?int $userId = null): array
    {
        if (!$this->storage->isAvailable()) {
            return ['success' => false, 'message' => 'Le registre documentaire n\'est pas disponible.'];
        }

        $numEtu = trim((string) ($post['num_etu'] ?? $post['cm_memoire_etudiant'] ?? ''));
        if ($numEtu === '') {
            return ['success' => false, 'message' => 'Veuillez selectionner un etudiant.'];
        }

        $selectedSessionNumber = (int) ($post['cm_memoire_session_previsionnelle'] ?? $post['num_session_previsionnelle'] ?? 0);
        if ($selectedSessionNumber <= 0) {
            return ['success' => false, 'message' => 'Veuillez selectionner une session previsionnelle.'];
        }

        $eligibility = $this->getMemoireUploadEligibility($numEtu, null, $selectedSessionNumber);
        if (!($eligibility['allowed'] ?? false)) {
            return [
                'success' => false,
                'message' => (string) ($eligibility['message'] ?? 'Ce memoire ne peut pas encore etre mis en ligne.'),
            ];
        }

        $rapport = $eligibility['rapport'] ?? null;
        if (!is_array($rapport) || (int) ($rapport['id_rapport'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Aucun rapport valide n\'a ete trouve pour cet etudiant.'];
        }
        $selectedSession = is_array($eligibility['session'] ?? null) ? $eligibility['session'] : null;
        if ($selectedSession === null) {
            return ['success' => false, 'message' => 'Session previsionnelle introuvable.'];
        }

        $themeMemoire = $this->normalizeMemoireTheme((string) ($post['cm_memoire_theme'] ?? $post['theme_memoire'] ?? ''));
        if ($themeMemoire === '') {
            return ['success' => false, 'message' => 'Veuillez renseigner le theme du memoire.'];
        }

        $file = $files['memoire_pdf'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Veuillez selectionner un fichier PDF valide.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = trim((string) ($file['name'] ?? 'memoire.pdf'));
        $size = (int) ($file['size'] ?? 0);

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'message' => 'Le fichier uploade est introuvable.'];
        }

        if ($size <= 0) {
            return ['success' => false, 'message' => 'Le fichier selectionne est vide.'];
        }

        if ($size > self::MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'Le fichier depasse la taille maximale autorisee de 20 MB.'];
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return ['success' => false, 'message' => 'Seuls les fichiers PDF sont autorises.'];
        }

        $mimeType = 'application/pdf';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $mimeType = $detected;
                }
            }
        }

        if (stripos($mimeType, 'pdf') === false) {
            return ['success' => false, 'message' => 'Le fichier transmis n\'est pas reconnu comme un PDF.'];
        }

        $content = file_get_contents($tmpName);
        if (!is_string($content) || $content === '') {
            return ['success' => false, 'message' => 'Impossible de lire le fichier transmis.'];
        }

        $safeStudent = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($rapport['num_etu'] ?? $numEtu));
        $storedName = 'memoire_' . $safeStudent . '_' . date('Y-m-d') . '.pdf';

        try {
            $this->ensureMemoireMetadataTable();
            $this->db->beginTransaction();

            $document = $this->storage->storeDocument(
                'memoire',
                $storedName,
                $content,
                'application/pdf',
                'rapport_etudiants',
                (string) ((int) ($rapport['id_rapport'] ?? 0)),
                $userId,
                null,
                null,
                $originalName,
                true
            );

            if (!is_array($document)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'L\'enregistrement du memoire a echoue.'];
            }

            $this->saveMemoireMetadata(
                (int) ($document['id_document'] ?? 0),
                (int) ($rapport['id_rapport'] ?? 0),
                $numEtu,
                $themeMemoire,
                $selectedSession
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[MiseEnLigneMemoireService] upload failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'L\'enregistrement du memoire a echoue.'];
        }

        $rapport['theme_memoire'] = $themeMemoire;
        $rapport['theme'] = $themeMemoire;
        $this->notifyMemoireValidators($rapport, $document);

        return ['success' => true, 'message' => 'Memoire mis en ligne avec succes.'];
    }

    public function supprimerMemoire(string $numEtu): array
    {
        if (!$this->storage->isAvailable()) {
            return ['success' => false, 'message' => 'Le registre documentaire n\'est pas disponible.'];
        }

        $document = $this->findActiveMemoireByStudent($numEtu);
        if ($document === null) {
            return ['success' => false, 'message' => 'Aucun memoire actif a supprimer.'];
        }

        $stmt = $this->db->prepare(
            'UPDATE documents
             SET statut = "supprime"
             WHERE id_document = :id_document
               AND statut = "actif"'
        );
        $stmt->execute([':id_document' => (int) ($document['id_document'] ?? 0)]);

        if ($stmt->rowCount() <= 0) {
            return ['success' => false, 'message' => 'Suppression impossible.'];
        }

        return ['success' => true, 'message' => 'Memoire supprime avec succes.'];
    }

    public function getMemoireDocumentByStudent(string $numEtu): ?array
    {
        return $this->findActiveMemoireByStudent($numEtu);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEtudiantsAvecSoutenance(): array
    {
        if (
            !$this->tableExists('compte_rendu')
            || !$this->tableExists('compte_rendu_rapport')
            || !$this->tableExists('rapport_etudiants')
            || !$this->tableExists('etudiants')
        ) {
            return [];
        }

        $sql = 'SELECT
                    cr.id_CR,
                    cr.date_CR,
                    r.id_rapport,
                    r.num_etu AS rapport_num_etu,
                    COALESCE(r.theme_rapport, "") AS theme_rapport,
                    e.num_carte_etud,
                    e.num_ident_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.promotion_etu
                FROM compte_rendu cr
                INNER JOIN compte_rendu_rapport crr ON crr.id_CR = cr.id_CR
                INNER JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
                INNER JOIN etudiants e
                    ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
                WHERE 1 = 1';

        $yearFilter = $this->buildStudentYearFilter('e');
        $sql .= $yearFilter['sql'];
        $sql .= ' ORDER BY cr.date_CR DESC, r.id_rapport DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($yearFilter['params'] as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $seen = [];
        $items = [];
        foreach ($rows as $row) {
            $numEtu = trim((string) ($row['num_ident_etud'] ?? $row['num_carte_etud'] ?? $row['rapport_num_etu'] ?? ''));
            if ($numEtu === '' || isset($seen[$numEtu])) {
                continue;
            }

            $latestRapport = $this->findLatestRapportAvecCompteRenduByStudent($numEtu);
            if (
                !is_array($latestRapport)
                || (int) ($latestRapport['id_rapport'] ?? 0) !== (int) ($row['id_rapport'] ?? 0)
                || $this->findActiveMemoireByStudent($numEtu) !== null
            ) {
                continue;
            }

            $anneeId = $this->resolveAcademicYearForMemoire($latestRapport);
            $sessions = $anneeId !== null ? $this->getPrevisionSessionsByYear($anneeId) : [];
            $session = $sessions[0] ?? null;
            $dateLimite = '';
            if (is_array($session) && !empty($session['date_debut'])) {
                try {
                    $dateLimite = (new \DateTimeImmutable((string) $session['date_debut']))
                        ->modify('-1 month')
                        ->format('Y-m-d');
                } catch (\Throwable) {
                    $dateLimite = '';
                }
            }

            $seen[$numEtu] = true;

            $items[] = [
                'num_etu' => $numEtu,
                'id_rapport' => (int) ($row['id_rapport'] ?? 0),
                'num_carte_etud' => (string) ($row['num_carte_etud'] ?? ''),
                'num_ident_etud' => (string) ($row['num_ident_etud'] ?? ''),
                'num_soutenance' => '',
                'nom_complet' => trim((string) ($row['nom_etu'] ?? '') . ' ' . (string) ($row['prenom_etu'] ?? '')),
                'promotion' => $this->formatPromotion($row),
                'promotion_etu' => (string) ($row['promotion_etu'] ?? ''),
                'theme' => (string) ($row['theme_rapport'] ?? ''),
                'theme_rapport' => (string) ($row['theme_rapport'] ?? ''),
                'theme_soutenance' => '',
                'date_soutenance' => '',
                'id_annee_acad' => (int) ($anneeId ?? 0),
                'num_session_previsionnelle' => is_array($session) ? (int) ($session['num_session'] ?? 0) : 0,
                'date_session_previsionnelle' => is_array($session) ? (string) ($session['date_debut'] ?? '') : '',
                'date_fin_session_previsionnelle' => is_array($session) ? (string) ($session['date_fin'] ?? '') : '',
                'date_limite_memoire' => $dateLimite,
                'sessions_previsionnelles' => $sessions,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMemoiresEnLigne(): array
    {
        if (!$this->storage->isAvailable()) {
            return [];
        }

        $itemsByStudent = [];

        foreach ($this->getRapportBasedMemoires() as $row) {
            $this->mergeMemoireItem($itemsByStudent, $this->mapMemoireRow($row, false));
        }

        foreach ($this->getLegacySoutenanceBasedMemoires() as $row) {
            $this->mergeMemoireItem($itemsByStudent, $this->mapMemoireRow($row, true));
        }

        $items = array_values($itemsByStudent);
        usort($items, static function (array $left, array $right): int {
            $leftTime = strtotime((string) ($left['date_creation'] ?? '')) ?: 0;
            $rightTime = strtotime((string) ($right['date_creation'] ?? '')) ?: 0;
            if ($leftTime === $rightTime) {
                return (int) ($right['id_document'] ?? 0) <=> (int) ($left['id_document'] ?? 0);
            }
            return $rightTime <=> $leftTime;
        });

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findLatestSoutenanceByStudent(string $numEtu, bool $ignoreSelectedAcademicYear = false): ?array
    {
        if (!$this->tableExists('programmer_soutenance')) {
            return null;
        }

        $sql = 'SELECT
                    ps.num_soutenance,
                    ps.num_etud,
                    ps.theme_soutenance,
                    ps.date_soutenance,
                    e.num_carte_etud,
                    e.num_ident_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.promotion_etu,
                    aa.date_deb,
                    aa.date_fin
                FROM programmer_soutenance ps
                INNER JOIN etudiants e
                    ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                LEFT JOIN annee_academique aa ON aa.id_annee_acad = ps.id_annee_acad
                WHERE (ps.num_etud = :num_etu
                    OR e.num_carte_etud = :num_etu
                    OR e.num_ident_etud = :num_etu)';

        $params = [':num_etu' => $numEtu];
        $anneeId = $ignoreSelectedAcademicYear ? null : $this->getSelectedAcademicYearId();
        if ($anneeId !== null) {
            $sql .= ' AND ps.id_annee_acad = :annee_id';
            $params[':annee_id'] = $anneeId;
        }

        $sql .= ' ORDER BY ps.date_soutenance DESC, ps.num_soutenance DESC LIMIT 1';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return [
            'num_soutenance' => (string) ($row['num_soutenance'] ?? ''),
            'num_etu' => trim((string) ($row['num_ident_etud'] ?? $row['num_carte_etud'] ?? $row['num_etud'] ?? $numEtu)),
            'theme' => (string) ($row['theme_soutenance'] ?? ''),
            'date_soutenance' => (string) ($row['date_soutenance'] ?? ''),
            'nom_etudiant' => trim((string) ($row['nom_etu'] ?? '') . ' ' . (string) ($row['prenom_etu'] ?? '')),
            'promotion' => $this->formatPromotion($row),
        ];
    }

    private function findActiveMemoireByStudent(string $numEtu): ?array
    {
        $rapport = $this->findLatestRapportAvecCompteRenduByStudent($numEtu);
        if (!is_array($rapport)) {
            $rapport = $this->findLatestValidatedRapportForMemoireUpload($numEtu);
        }
        if (!is_array($rapport)) {
            $rapport = $this->findLatestRapportByStudent($numEtu);
        }
        if (is_array($rapport) && (int) ($rapport['id_rapport'] ?? 0) > 0) {
            $document = $this->storage->findLatestByEntity(
                'rapport_etudiants',
                (string) ((int) ($rapport['id_rapport'] ?? 0)),
                ['memoire'],
                null,
                'application/pdf'
            );
            if (is_array($document)) {
                return $document;
            }
        }

        $soutenance = $this->findLatestSoutenanceByStudent($numEtu);
        if ($soutenance === null || $soutenance['num_soutenance'] === '') {
            return null;
        }

        return $this->storage->findLatestByEntity(
            'programmer_soutenance',
            (string) $soutenance['num_soutenance'],
            ['memoire'],
            null,
            'application/pdf'
        );
    }

    private function getSelectedAcademicYearId(): ?int
    {
        $raw = $_SESSION['selected_academic_year_id']
            ?? $_SESSION['archive_annee_acad']
            ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;
        return $id > 0 ? $id : null;
    }

    /**
     * @return array{sql:string,params:array<string,int>}
     */
    private function buildStudentYearFilter(string $studentAlias = 'e'): array
    {
        $selectedYearId = $this->getSelectedAcademicYearId();
        if ($selectedYearId === null || !$this->tableExists('inscriptions')) {
            return ['sql' => '', 'params' => []];
        }

        return [
            'sql' => " AND EXISTS (
                SELECT 1
                FROM inscriptions i
                WHERE (
                    i.num_carte_etud = {$studentAlias}.num_carte_etud
                    OR i.num_carte_etud = {$studentAlias}.num_ident_etud
                )
                  AND i.id_annee_acad = :annee_id
            )",
            'params' => [':annee_id' => $selectedYearId],
        ];
    }

    /**
     * @param array<string,mixed>|null $rapport
     * @return array{allowed:bool,message:string,rapport:array<string,mixed>|null,session:array<string,mixed>|null,id_annee_acad:int|null,date_limite:string|null}
     */
    private function getMemoireUploadEligibility(string $numEtu, ?array $rapport = null, ?int $numSession = null): array
    {
        if ($rapport === null) {
            $rapport = $this->findLatestRapportAvecCompteRenduByStudent($numEtu);
        }

        if (!is_array($rapport) || (int) ($rapport['id_rapport'] ?? 0) <= 0) {
            return [
                'allowed' => false,
                'message' => 'Aucun compte rendu n\'a ete trouve pour cet etudiant.',
                'rapport' => null,
                'session' => null,
                'id_annee_acad' => null,
                'date_limite' => null,
            ];
        }

        if ($this->findActiveMemoireByStudent($numEtu) !== null) {
            return [
                'allowed' => false,
                'message' => 'Un memoire actif existe deja pour cet etudiant.',
                'rapport' => $rapport,
                'session' => null,
                'id_annee_acad' => null,
                'date_limite' => null,
            ];
        }

        $anneeId = $this->resolveAcademicYearForMemoire($rapport);
        if ($anneeId === null || $anneeId <= 0) {
            return [
                'allowed' => false,
                'message' => 'Impossible de determiner l\'annee academique de cet etudiant.',
                'rapport' => $rapport,
                'session' => null,
                'id_annee_acad' => null,
                'date_limite' => null,
            ];
        }

        $session = $numSession !== null && $numSession > 0
            ? $this->findPrevisionSession($anneeId, $numSession)
            : $this->findNextPrevisionSession($anneeId);
        if ($session === null) {
            return [
                'allowed' => false,
                'message' => $numSession !== null && $numSession > 0
                    ? 'La session previsionnelle selectionnee n\'est pas parametree pour cette annee academique.'
                    : 'Aucune date previsionnelle de soutenance future n\'est parametree pour cette annee academique.',
                'rapport' => $rapport,
                'session' => null,
                'id_annee_acad' => $anneeId,
                'date_limite' => null,
            ];
        }

        $dateSession = trim((string) ($session['date_debut'] ?? ''));
        try {
            $sessionDate = new \DateTimeImmutable($dateSession);
            $deadline = $sessionDate->modify('-1 month');
            $today = new \DateTimeImmutable('today');
        } catch (\Throwable) {
            return [
                'allowed' => false,
                'message' => 'La date previsionnelle de soutenance est invalide.',
                'rapport' => $rapport,
                'session' => $session,
                'id_annee_acad' => $anneeId,
                'date_limite' => null,
            ];
        }

        if ($today > $deadline) {
            return [
                'allowed' => false,
                'message' => 'La date limite de mise en ligne du memoire est depassee pour la prochaine session previsionnelle.',
                'rapport' => $rapport,
                'session' => $session,
                'id_annee_acad' => $anneeId,
                'date_limite' => $deadline->format('Y-m-d'),
            ];
        }

        return [
            'allowed' => true,
            'message' => '',
            'rapport' => $rapport,
            'session' => $session,
            'id_annee_acad' => $anneeId,
            'date_limite' => $deadline->format('Y-m-d'),
        ];
    }

    /**
     * @param array<string,mixed> $rapport
     */
    private function resolveAcademicYearForMemoire(array $rapport): ?int
    {
        $selectedYearId = $this->getSelectedAcademicYearId();
        if ($selectedYearId !== null) {
            return $selectedYearId;
        }

        $rawYear = $rapport['id_annee_acad'] ?? null;
        if (is_numeric($rawYear) && (int) $rawYear > 0) {
            return (int) $rawYear;
        }

        $rapportDate = trim((string) ($rapport['rapport_date'] ?? $rapport['date_depot'] ?? $rapport['date_redaction_rapport'] ?? ''));
        if ($rapportDate !== '' && $this->tableExists('annee_academique')) {
            try {
                $stmt = $this->db->prepare(
                    'SELECT id_annee_acad
                     FROM annee_academique
                     WHERE DATE(:rapport_date) BETWEEN date_deb AND date_fin
                     ORDER BY date_deb DESC
                     LIMIT 1'
                );
                $stmt->execute([':rapport_date' => $rapportDate]);
                $value = $stmt->fetchColumn();
                if (is_numeric($value) && (int) $value > 0) {
                    return (int) $value;
                }
            } catch (\Throwable) {
            }
        }

        if (!$this->tableExists('inscriptions')) {
            return null;
        }

        $identifiers = array_values(array_unique(array_filter([
            trim((string) ($rapport['num_carte_etud'] ?? '')),
            trim((string) ($rapport['num_ident_etud'] ?? '')),
            trim((string) ($rapport['num_etu'] ?? $rapport['rapport_num_etu'] ?? '')),
        ], static fn(string $value): bool => $value !== '')));

        if ($identifiers === []) {
            return null;
        }

        try {
            $placeholders = implode(', ', array_fill(0, count($identifiers), '?'));
            $stmt = $this->db->prepare(
                'SELECT id_annee_acad
                 FROM inscriptions
                 WHERE num_carte_etud IN (' . $placeholders . ')
                 ORDER BY date_inscription DESC, id_annee_acad DESC, num_versement DESC
                 LIMIT 1'
            );
            $stmt->execute($identifiers);
            $value = $stmt->fetchColumn();
            return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findPrevisionSession(int $anneeId, int $numSession): ?array
    {
        if ($anneeId <= 0 || $numSession <= 0 || !$this->tableExists('programmation_sessions_soutenance')) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT id_programmation, id_annee_acad, num_session, date_debut, date_fin
                 FROM programmation_sessions_soutenance
                 WHERE id_annee_acad = :annee_id
                   AND num_session = :num_session
                   AND date_debut IS NOT NULL
                 LIMIT 1'
            );
            $stmt->bindValue(':annee_id', $anneeId, PDO::PARAM_INT);
            $stmt->bindValue(':num_session', $numSession, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $this->mapPrevisionSession($row) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getPrevisionSessionsByYear(int $anneeId): array
    {
        if ($anneeId <= 0 || !$this->tableExists('programmation_sessions_soutenance')) {
            return [];
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT id_programmation, id_annee_acad, num_session, date_debut, date_fin
                 FROM programmation_sessions_soutenance
                 WHERE id_annee_acad = :annee_id
                   AND date_debut IS NOT NULL
                   AND date_debut >= CURDATE()
                   AND DATE_SUB(date_debut, INTERVAL 1 MONTH) >= CURDATE()
                 ORDER BY date_debut ASC, num_session ASC'
            );
            $stmt->execute([':annee_id' => $anneeId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map(fn(array $row): array => $this->mapPrevisionSession($row), $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapPrevisionSession(array $row): array
    {
        $dateDebut = (string) ($row['date_debut'] ?? '');
        $dateFin = (string) ($row['date_fin'] ?? '');
        $dateLimite = '';

        if ($dateDebut !== '') {
            try {
                $dateLimite = (new \DateTimeImmutable($dateDebut))->modify('-1 month')->format('Y-m-d');
            } catch (\Throwable) {
                $dateLimite = '';
            }
        }

        return [
            'id_programmation' => (int) ($row['id_programmation'] ?? 0),
            'id_annee_acad' => (int) ($row['id_annee_acad'] ?? 0),
            'num_session' => (int) ($row['num_session'] ?? 0),
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'date_limite_memoire' => $dateLimite,
            'label' => $this->formatPrevisionSessionLabel((int) ($row['num_session'] ?? 0), $dateDebut, $dateFin),
        ];
    }

    private function formatPrevisionSessionLabel(int $numSession, string $dateDebut, string $dateFin): string
    {
        $label = $numSession > 0 ? 'Session ' . $numSession : 'Session';
        if ($dateDebut === '') {
            return $label;
        }

        $start = date('d/m/Y', strtotime($dateDebut));
        if ($dateFin === '' || $dateFin === $dateDebut) {
            return $label . ' - ' . $start;
        }

        return $label . ' - ' . $start . ' au ' . date('d/m/Y', strtotime($dateFin));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findNextPrevisionSession(int $anneeId): ?array
    {
        if ($anneeId <= 0 || !$this->tableExists('programmation_sessions_soutenance')) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT id_programmation, id_annee_acad, num_session, date_debut, date_fin
                 FROM programmation_sessions_soutenance
                 WHERE id_annee_acad = :annee_id
                   AND date_debut IS NOT NULL
                   AND date_debut >= CURDATE()
                   AND DATE_SUB(date_debut, INTERVAL 1 MONTH) >= CURDATE()
                 ORDER BY date_debut ASC, num_session ASC
                 LIMIT 1'
            );
            $stmt->execute([':annee_id' => $anneeId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $this->mapPrevisionSession($row) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function formatPromotion(array $row): string
    {
        $promotion = trim((string) ($row['promotion_etu'] ?? ''));
        if ($promotion !== '') {
            return \FormattingUtils::formatPromotion($promotion);
        }

        $debut = $row['date_deb'] ?? null;
        $fin = $row['date_fin'] ?? null;
        if (!empty($debut) && !empty($fin)) {
            return \FormattingUtils::formatPromotion(
                date('Y', strtotime((string) $debut)) . '-' . date('Y', strtotime((string) $fin))
            );
        }

        return '-';
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0';
        }

        return number_format($bytes / 1048576, 2, ',', ' ');
    }

    private function tableExists(string $tableName): bool
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }

        try {
            $stmt = $this->db->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$tableName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->tableExistsCache[$tableName] = $exists;
            return $exists;
        } catch (\Throwable) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $cacheKey = strtolower($tableName . '.' . $columnName);
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        if (!$this->tableExists($tableName)) {
            $this->columnExistsCache[$cacheKey] = false;
            return false;
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$cacheKey] = $exists;
            return $exists;
        } catch (\Throwable) {
            $this->columnExistsCache[$cacheKey] = false;
            return false;
        }
    }

    private function resolveRapportOrderColumn(): ?string
    {
        if ($this->columnExists('rapport_etudiants', 'date_rapport')) {
            return 'date_rapport';
        }
        if ($this->columnExists('rapport_etudiants', 'date_redaction_rapport')) {
            return 'date_redaction_rapport';
        }
        return null;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findLatestRapportByStudent(string $numEtu): ?array
    {
        if ($numEtu === '' || !$this->tableExists('rapport_etudiants')) {
            return null;
        }

        $orderColumn = $this->resolveRapportOrderColumn();
        $orderBy = $orderColumn !== null
            ? "ORDER BY r.{$orderColumn} DESC, r.id_rapport DESC"
            : 'ORDER BY r.id_rapport DESC';

        $etapeSelect = $this->columnExists('rapport_etudiants', 'etape_validation')
            ? 'COALESCE(r.etape_validation, "") AS etape_validation,'
            : '"" AS etape_validation,';

        try {
            $stmt = $this->db->prepare("
                SELECT
                    r.id_rapport,
                    r.num_etu,
                    {$etapeSelect}
                    COALESCE(r.statut_rapport, '') AS statut_rapport,
                    COALESCE(r.theme_rapport, '') AS theme_rapport,
                    e.num_carte_etud,
                    e.num_ident_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.promotion_etu
                FROM rapport_etudiants r
                LEFT JOIN etudiants e
                    ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
                WHERE (
                    r.num_etu = :num_etu
                    OR e.num_carte_etud = :num_etu
                    OR e.num_ident_etud = :num_etu
                )
                {$orderBy}
                LIMIT 1
            ");
            $stmt->execute([':num_etu' => $numEtu]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            error_log('[MiseEnLigneMemoireService] latest rapport lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findLatestValidatedRapportForMemoireUpload(string $numEtu): ?array
    {
        if (
            $numEtu === ''
            || !$this->tableExists('rapport_etudiants')
            || !$this->tableExists('etudiants')
            || !$this->tableExists('valider')
        ) {
            return null;
        }

        $depotJoin = $this->tableExists('deposer')
            ? 'LEFT JOIN deposer d ON d.id_rapport = r.id_rapport'
            : '';
        $rapportDateSelect = $this->tableExists('deposer')
            ? 'COALESCE(d.date_depot, r.date_redaction_rapport) AS rapport_date'
            : 'r.date_redaction_rapport AS rapport_date';
        $rapportDateOrder = $this->tableExists('deposer')
            ? 'COALESCE(d.date_depot, r.date_redaction_rapport)'
            : 'r.date_redaction_rapport';

        try {
            $sql = "
                SELECT
                    r.id_rapport,
                    r.num_etu,
                    COALESCE(r.statut_rapport, '') AS statut_rapport,
                    COALESCE(r.theme_rapport, '') AS theme_rapport,
                    {$rapportDateSelect},
                    e.num_carte_etud,
                    e.num_ident_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.promotion_etu
                FROM rapport_etudiants r
                INNER JOIN etudiants e
                    ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
                INNER JOIN valider v
                    ON v.id_rapport = r.id_rapport
                   AND v.decision_validation = 'valider'
                {$depotJoin}
                WHERE (
                    r.num_etu = :num_etu
                    OR e.num_carte_etud = :num_etu
                    OR e.num_ident_etud = :num_etu
                )
            ";

            $yearFilter = $this->buildStudentYearFilter('e');
            $sql .= $yearFilter['sql'] . " ORDER BY {$rapportDateOrder} DESC, r.id_rapport DESC LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':num_etu', $numEtu, PDO::PARAM_STR);
            foreach ($yearFilter['params'] as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            error_log('[MiseEnLigneMemoireService] latest validated rapport lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findLatestRapportAvecCompteRenduByStudent(string $numEtu): ?array
    {
        if (
            $numEtu === ''
            || !$this->tableExists('compte_rendu')
            || !$this->tableExists('compte_rendu_rapport')
            || !$this->tableExists('rapport_etudiants')
        ) {
            return null;
        }

        $orderColumn = $this->resolveRapportOrderColumn();
        $orderBy = $orderColumn !== null
            ? "ORDER BY cr.date_CR DESC, r.{$orderColumn} DESC, r.id_rapport DESC"
            : 'ORDER BY cr.date_CR DESC, r.id_rapport DESC';

        try {
            $sql = "
                SELECT
                    r.id_rapport,
                    r.num_etu,
                    COALESCE(r.statut_rapport, '') AS statut_rapport,
                    COALESCE(r.theme_rapport, '') AS theme_rapport,
                    e.num_carte_etud,
                    e.num_ident_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.promotion_etu,
                    cr.id_CR,
                    cr.date_CR
                FROM compte_rendu cr
                INNER JOIN compte_rendu_rapport crr ON crr.id_CR = cr.id_CR
                INNER JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
                LEFT JOIN etudiants e
                    ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
                WHERE (
                    r.num_etu = :num_etu
                    OR cr.num_etu = :num_etu
                    OR e.num_carte_etud = :num_etu
                    OR e.num_ident_etud = :num_etu
                )
            ";

            $yearFilter = $this->buildStudentYearFilter('e');
            $sql .= $yearFilter['sql'] . " {$orderBy} LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':num_etu', $numEtu, PDO::PARAM_STR);
            foreach ($yearFilter['params'] as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            error_log('[MiseEnLigneMemoireService] latest compte rendu rapport lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getRapportBasedMemoires(): array
    {
        if (!$this->tableExists('documents') || !$this->tableExists('rapport_etudiants')) {
            return [];
        }

        $hasMetadata = $this->tableExists(self::TABLE_MEMOIRE_METADATA);
        $metadataJoin = $hasMetadata
            ? 'LEFT JOIN ' . self::TABLE_MEMOIRE_METADATA . ' mm ON mm.id_document = d.id_document'
            : '';
        $metadataSelect = $hasMetadata
            ? 'COALESCE(mm.theme_memoire, r.theme_rapport, "") AS theme_memoire,
               mm.num_session_previsionnelle,
               mm.date_debut_session,
               mm.date_fin_session,'
            : 'COALESCE(r.theme_rapport, "") AS theme_memoire,
               NULL AS num_session_previsionnelle,
               NULL AS date_debut_session,
               NULL AS date_fin_session,';

        $sql = 'SELECT
                    d.id_document,
                    d.nom_fichier,
                    d.taille_fichier,
                    d.date_creation,
                    r.id_rapport,
                    r.num_etu AS rapport_num_etu,
                    COALESCE(r.theme_rapport, "") AS theme_rapport,
                    ' . $metadataSelect . '
                    e.num_carte_etud,
                    e.num_ident_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.promotion_etu
                FROM documents d
                INNER JOIN rapport_etudiants r
                    ON CAST(r.id_rapport AS CHAR) = d.entite_id
                ' . $metadataJoin . '
                INNER JOIN etudiants e
                    ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
                WHERE d.entite_type = "rapport_etudiants"
                  AND d.type_document = "memoire"
                  AND d.statut = "actif"';

        $yearFilter = $this->buildStudentYearFilter('e');
        $sql .= $yearFilter['sql'];
        $sql .= ' ORDER BY d.date_creation DESC, d.id_document DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($yearFilter['params'] as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getLegacySoutenanceBasedMemoires(): array
    {
        if (!$this->tableExists('documents') || !$this->tableExists('programmer_soutenance')) {
            return [];
        }

        $hasMetadata = $this->tableExists(self::TABLE_MEMOIRE_METADATA);
        $metadataJoin = $hasMetadata
            ? 'LEFT JOIN ' . self::TABLE_MEMOIRE_METADATA . ' mm ON mm.id_document = d.id_document'
            : '';
        $metadataSelect = $hasMetadata
            ? 'COALESCE(mm.theme_memoire, ps.theme_soutenance, "") AS theme_memoire,
               mm.num_session_previsionnelle,
               mm.date_debut_session,
               mm.date_fin_session,'
            : 'COALESCE(ps.theme_soutenance, "") AS theme_memoire,
               NULL AS num_session_previsionnelle,
               NULL AS date_debut_session,
               NULL AS date_fin_session,';

        $sql = 'SELECT
                    d.id_document,
                    d.nom_fichier,
                    d.taille_fichier,
                    d.date_creation,
                    ps.num_soutenance,
                    ps.num_etud,
                    ps.theme_soutenance,
                    ' . $metadataSelect . '
                    e.num_carte_etud,
                    e.num_ident_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.promotion_etu,
                    aa.date_deb,
                    aa.date_fin
                FROM documents d
                INNER JOIN programmer_soutenance ps
                    ON CAST(ps.num_soutenance AS CHAR) = d.entite_id
                ' . $metadataJoin . '
                INNER JOIN etudiants e
                    ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                LEFT JOIN annee_academique aa ON aa.id_annee_acad = ps.id_annee_acad
                WHERE d.entite_type = "programmer_soutenance"
                  AND d.type_document = "memoire"
                  AND d.statut = "actif"';

        $params = [];
        $anneeId = $this->getSelectedAcademicYearId();
        if ($anneeId !== null) {
            $sql .= ' AND ps.id_annee_acad = :annee_id';
            $params[':annee_id'] = $anneeId;
        }

        $sql .= ' ORDER BY d.date_creation DESC, d.id_document DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapMemoireRow(array $row, bool $legacySoutenance): array
    {
        $numEtu = trim((string) ($row['num_ident_etud'] ?? $row['num_carte_etud'] ?? $row['num_etud'] ?? $row['rapport_num_etu'] ?? ''));
        $soutenance = (!$legacySoutenance && $numEtu !== '') ? $this->findLatestSoutenanceByStudent($numEtu, true) : null;
        $themeMemoire = (string) ($row['theme_memoire'] ?? ($legacySoutenance ? ($row['theme_soutenance'] ?? '') : ($row['theme_rapport'] ?? '')));

        return [
            'id_document' => (int) ($row['id_document'] ?? 0),
            'id_rapport' => (int) ($row['id_rapport'] ?? 0),
            'num_etu' => $numEtu,
            'num_carte_etud' => (string) ($row['num_carte_etud'] ?? ''),
            'num_ident_etud' => (string) ($row['num_ident_etud'] ?? ''),
            'num_soutenance' => $legacySoutenance ? (string) ($row['num_soutenance'] ?? '') : (string) ($soutenance['num_soutenance'] ?? ''),
            'nom_etu' => (string) ($row['nom_etu'] ?? ''),
            'prenom_etu' => (string) ($row['prenom_etu'] ?? ''),
            'nom_etudiant' => trim((string) ($row['nom_etu'] ?? '') . ' ' . (string) ($row['prenom_etu'] ?? '')),
            'matricule' => $numEtu,
            'promotion' => $this->formatPromotion($row),
            'promotion_etu' => (string) ($row['promotion_etu'] ?? ''),
            'theme' => $themeMemoire,
            'theme_memoire' => $themeMemoire,
            'theme_soutenance' => $legacySoutenance ? (string) ($row['theme_soutenance'] ?? '') : (string) ($soutenance['theme'] ?? ''),
            'theme_rapport' => $legacySoutenance ? '' : (string) ($row['theme_rapport'] ?? ''),
            'num_session_previsionnelle' => (int) ($row['num_session_previsionnelle'] ?? 0),
            'date_debut_session' => (string) ($row['date_debut_session'] ?? ''),
            'date_fin_session' => (string) ($row['date_fin_session'] ?? ''),
            'fichier' => (string) ($row['nom_fichier'] ?? ''),
            'nom_fichier' => (string) ($row['nom_fichier'] ?? ''),
            'date_depot' => (string) ($row['date_creation'] ?? ''),
            'date_creation' => (string) ($row['date_creation'] ?? ''),
            'taille' => $this->formatFileSize((int) ($row['taille_fichier'] ?? 0)),
            'taille_fichier' => (int) ($row['taille_fichier'] ?? 0),
        ];
    }

    /**
     * @param array<string,array<string,mixed>> $itemsByStudent
     * @param array<string,mixed> $item
     */
    private function mergeMemoireItem(array &$itemsByStudent, array $item): void
    {
        $key = trim((string) ($item['num_etu'] ?? ''));
        if ($key === '') {
            $key = 'document_' . (string) ((int) ($item['id_document'] ?? 0));
        }

        if (!isset($itemsByStudent[$key])) {
            $itemsByStudent[$key] = $item;
            return;
        }

        $currentTime = strtotime((string) ($itemsByStudent[$key]['date_creation'] ?? '')) ?: 0;
        $incomingTime = strtotime((string) ($item['date_creation'] ?? '')) ?: 0;
        if (
            $incomingTime > $currentTime
            || (
                $incomingTime === $currentTime
                && (int) ($item['id_document'] ?? 0) > (int) ($itemsByStudent[$key]['id_document'] ?? 0)
            )
        ) {
            $itemsByStudent[$key] = $item;
        }
    }

    private function isMemoireUploadAllowed(string $numEtu): bool
    {
        $eligibility = $this->getMemoireUploadEligibility($numEtu);
        return (bool) ($eligibility['allowed'] ?? false);
    }

    private function normalizeMemoireTheme(string $theme): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($theme));
        if (!is_string($normalized)) {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($normalized, 0, 500);
        }

        return substr($normalized, 0, 500);
    }

    private function ensureMemoireMetadataTable(): void
    {
        if ($this->tableExists(self::TABLE_MEMOIRE_METADATA)) {
            $this->ensureMemoireMetadataColumns();
            return;
        }

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS ' . self::TABLE_MEMOIRE_METADATA . ' (
                id_memoire_metadata INT NOT NULL AUTO_INCREMENT,
                id_document BIGINT UNSIGNED NOT NULL,
                id_rapport INT NULL,
                num_etu VARCHAR(25) NOT NULL,
                theme_memoire VARCHAR(500) NOT NULL,
                num_session_previsionnelle TINYINT NULL,
                date_debut_session DATE NULL,
                date_fin_session DATE NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id_memoire_metadata),
                UNIQUE KEY uq_memoire_metadata_document (id_document),
                KEY idx_memoire_metadata_rapport (id_rapport),
                KEY idx_memoire_metadata_num_etu (num_etu)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $this->tableExistsCache[self::TABLE_MEMOIRE_METADATA] = true;
        $this->ensureMemoireMetadataColumns();
    }

    private function ensureMemoireMetadataColumns(): void
    {
        $columns = [
            'num_session_previsionnelle' => 'TINYINT NULL AFTER theme_memoire',
            'date_debut_session' => 'DATE NULL AFTER num_session_previsionnelle',
            'date_fin_session' => 'DATE NULL AFTER date_debut_session',
        ];

        foreach ($columns as $column => $definition) {
            if ($this->columnExists(self::TABLE_MEMOIRE_METADATA, $column)) {
                continue;
            }

            $this->db->exec('ALTER TABLE ' . self::TABLE_MEMOIRE_METADATA . ' ADD COLUMN ' . $column . ' ' . $definition);
            $this->columnExistsCache[strtolower(self::TABLE_MEMOIRE_METADATA . '.' . $column)] = true;
        }
    }

    /**
     * @param array<string,mixed> $session
     */
    private function saveMemoireMetadata(int $documentId, int $rapportId, string $numEtu, string $themeMemoire, array $session): void
    {
        if ($documentId <= 0 || $numEtu === '' || $themeMemoire === '') {
            throw new \RuntimeException('Metadonnees memoire invalides.');
        }

        $stmt = $this->db->prepare('
            INSERT INTO ' . self::TABLE_MEMOIRE_METADATA . ' (
                id_document,
                id_rapport,
                num_etu,
                theme_memoire,
                num_session_previsionnelle,
                date_debut_session,
                date_fin_session
            ) VALUES (
                :id_document,
                :id_rapport,
                :num_etu,
                :theme_memoire,
                :num_session_previsionnelle,
                :date_debut_session,
                :date_fin_session
            )
            ON DUPLICATE KEY UPDATE
                id_rapport = VALUES(id_rapport),
                num_etu = VALUES(num_etu),
                theme_memoire = VALUES(theme_memoire),
                num_session_previsionnelle = VALUES(num_session_previsionnelle),
                date_debut_session = VALUES(date_debut_session),
                date_fin_session = VALUES(date_fin_session),
                updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->bindValue(':id_document', $documentId, PDO::PARAM_INT);
        if ($rapportId > 0) {
            $stmt->bindValue(':id_rapport', $rapportId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':id_rapport', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':num_etu', $numEtu);
        $stmt->bindValue(':theme_memoire', $themeMemoire);
        $numSession = (int) ($session['num_session'] ?? 0);
        $stmt->bindValue(':num_session_previsionnelle', $numSession > 0 ? $numSession : null, $numSession > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $dateDebut = trim((string) ($session['date_debut'] ?? ''));
        $dateFin = trim((string) ($session['date_fin'] ?? ''));
        $stmt->bindValue(':date_debut_session', $dateDebut !== '' ? $dateDebut : null, $dateDebut !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':date_fin_session', $dateFin !== '' ? $dateFin : null, $dateFin !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
    }

    /**
     * @return array<string,array<string,string>>
     */
    private function resolveEncadrementForMemoire(?int $rapportId, string $numSoutenance): array
    {
        $roles = [];

        if ($rapportId !== null && $rapportId > 0 && $this->tableExists('affecter')) {
            $stmt = $this->db->prepare("
                SELECT a.role, a.id_enseignant,
                       CONCAT(COALESCE(e.prenom_enseignant, ''), ' ', COALESCE(e.nom_enseignant, '')) AS nom,
                       e.mail_enseignant AS email
                FROM affecter a
                JOIN enseignants e ON e.id_enseignant = a.id_enseignant
                WHERE a.id_rapport = ?
            ");
            $stmt->execute([$rapportId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $role = strtolower(trim((string) ($row['role'] ?? '')));
                if (!in_array($role, ['directeur', 'encadrant'], true) || isset($roles[$role])) {
                    continue;
                }

                $roles[$role] = [
                    'id_enseignant' => (string) ($row['id_enseignant'] ?? ''),
                    'nom' => trim((string) ($row['nom'] ?? '')),
                    'email' => trim((string) ($row['email'] ?? '')),
                ];
            }
        }

        if (
            (!isset($roles['directeur']) || !isset($roles['encadrant']))
            && $numSoutenance !== ''
            && $this->tableExists('enseignant_jury')
            && $this->tableExists('qualite_jury')
        ) {
            $stmt = $this->db->prepare("
                SELECT ej.id_enseignant, qj.lib_role,
                       CONCAT(COALESCE(e.prenom_enseignant, ''), ' ', COALESCE(e.nom_enseignant, '')) AS nom,
                       e.mail_enseignant AS email
                FROM enseignant_jury ej
                JOIN qualite_jury qj ON ej.id_qualite_jury = qj.id_role_jury
                JOIN enseignants e ON e.id_enseignant = ej.id_enseignant
                WHERE ej.num_soutenance = ?
            ");
            $stmt->execute([$numSoutenance]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $roleRaw = strtoupper(trim((string) ($row['lib_role'] ?? '')));
                $resolvedRole = null;
                if (str_contains($roleRaw, 'DIRECT')) {
                    $resolvedRole = 'directeur';
                } elseif (str_contains($roleRaw, 'ENCADR')) {
                    $resolvedRole = 'encadrant';
                }

                if ($resolvedRole === null || isset($roles[$resolvedRole])) {
                    continue;
                }

                $roles[$resolvedRole] = [
                    'id_enseignant' => (string) ($row['id_enseignant'] ?? ''),
                    'nom' => trim((string) ($row['nom'] ?? '')),
                    'email' => trim((string) ($row['email'] ?? '')),
                ];
            }
        }

        return $roles;
    }

    /**
     * @return array<int,int>
     */
    private function getResponsableFiliereGroupIds(): array
    {
        $groups = PermissionRegistry::groups();
        $ids = [];

        if (isset($groups['responsable_filiere'])) {
            $ids[] = (int) $groups['responsable_filiere'];
        }
        if (isset($groups['admin_responsable_filiere'])) {
            $ids[] = (int) $groups['admin_responsable_filiere'];
        }

        return array_values(array_unique(array_filter($ids, static fn($id) => $id > 0)));
    }

    /**
     * @param array<string,mixed> $memoireContext
     * @param array<string,mixed> $document
     */
    private function notifyMemoireValidators(array $memoireContext, array $document): void
    {
        try {
            $numEtu = trim((string) ($memoireContext['num_etu'] ?? $memoireContext['num_ident_etud'] ?? $memoireContext['num_carte_etud'] ?? ''));
            $soutenance = $this->findLatestSoutenanceByStudent($numEtu, true);
            $numSoutenance = trim((string) ($soutenance['num_soutenance'] ?? ''));
            $rapportId = (int) ($memoireContext['id_rapport'] ?? 0);
            $encadrement = $this->resolveEncadrementForMemoire($rapportId > 0 ? $rapportId : null, $numSoutenance);

            $notificationService = new NotificationService($this->db);
            $emailService = $notificationService->getEmailService();
            $baseData = [
                'nom_etudiant' => htmlspecialchars(trim((string) ($memoireContext['nom_etu'] ?? '') . ' ' . (string) ($memoireContext['prenom_etu'] ?? '')) ?: 'Etudiant', ENT_QUOTES, 'UTF-8'),
                'theme' => htmlspecialchars((string) ($memoireContext['theme_memoire'] ?? $memoireContext['theme'] ?? $memoireContext['theme_rapport'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'promotion' => htmlspecialchars($this->formatPromotion($memoireContext), ENT_QUOTES, 'UTF-8'),
                'nom_fichier' => htmlspecialchars((string) ($document['nom_fichier'] ?? 'memoire.pdf'), ENT_QUOTES, 'UTF-8'),
                'num_soutenance' => htmlspecialchars($numSoutenance, ENT_QUOTES, 'UTF-8'),
            ];

            $roleLabels = [
                'directeur' => 'Directeur de memoire',
                'encadrant' => 'Encadrant',
            ];

            $alreadySent = [];
            foreach ($encadrement as $role => $recipient) {
                $email = strtolower(trim((string) ($recipient['email'] ?? '')));
                if ($email === '' || isset($alreadySent[$email])) {
                    continue;
                }

                $sent = $emailService->sendTemplate('MEMOIRE_A_VALIDER', $email, $baseData + [
                    'nom' => htmlspecialchars((string) ($recipient['nom'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'role_validateur' => $roleLabels[$role] ?? ucfirst($role),
                ]);

                if ($sent) {
                    $alreadySent[$email] = true;
                }
            }

            $responsableGroupIds = $this->getResponsableFiliereGroupIds();
            if ($responsableGroupIds !== []) {
                $notificationService->sendToUserGroups($responsableGroupIds, 'MEMOIRE_A_VALIDER', $baseData + [
                    'role_validateur' => 'Responsable de filiere',
                ]);
            }
        } catch (\Throwable $e) {
            error_log('[MiseEnLigneMemoireService] notification memoire failed: ' . $e->getMessage());
        }
    }
}
