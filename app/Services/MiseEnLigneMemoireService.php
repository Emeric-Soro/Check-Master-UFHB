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

        $numEtu = trim((string) ($post['num_etu'] ?? ''));
        if ($numEtu === '') {
            return ['success' => false, 'message' => 'Veuillez selectionner un etudiant.'];
        }

        if (!$this->isMemoireUploadAllowed($numEtu)) {
            return ['success' => false, 'message' => 'Le rapport de cet etudiant n\'a pas encore ete valide par la commission.'];
        }

        $rapport = $this->findLatestRapportAvecCompteRenduByStudent($numEtu);
        if (!is_array($rapport) || (int) ($rapport['id_rapport'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Aucun compte rendu n\'a ete trouve pour cet etudiant.'];
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
            return ['success' => false, 'message' => 'L\'enregistrement du memoire a echoue.'];
        }

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
        $sql .= ' ORDER BY r.id_rapport DESC';

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
                || !$this->isMemoireUploadAllowed($numEtu)
                || $this->findActiveMemoireByStudent($numEtu) !== null
            ) {
                continue;
            }

            $seen[$numEtu] = true;
            $soutenance = $this->findLatestSoutenanceByStudent($numEtu);

            $items[] = [
                'num_etu' => $numEtu,
                'id_rapport' => (int) ($row['id_rapport'] ?? 0),
                'num_soutenance' => (string) ($soutenance['num_soutenance'] ?? ''),
                'nom_complet' => trim((string) ($row['nom_etu'] ?? '') . ' ' . (string) ($row['prenom_etu'] ?? '')),
                'promotion' => $this->formatPromotion($row),
                'promotion_etu' => (string) ($row['promotion_etu'] ?? ''),
                'theme' => (string) ($row['theme_rapport'] ?? ''),
                'theme_rapport' => (string) ($row['theme_rapport'] ?? ''),
                'theme_soutenance' => (string) ($soutenance['theme'] ?? ''),
                'date_soutenance' => (string) ($soutenance['date_soutenance'] ?? ''),
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
    private function findLatestSoutenanceByStudent(string $numEtu): ?array
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
        $anneeId = $this->getSelectedAcademicYearId();
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
                WHERE r.num_etu = :num_etu
                   OR e.num_carte_etud = :num_etu
                   OR e.num_ident_etud = :num_etu
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
                WHERE r.num_etu = :num_etu
                   OR cr.num_etu = :num_etu
                   OR e.num_carte_etud = :num_etu
                   OR e.num_ident_etud = :num_etu
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

        $sql = 'SELECT
                    d.id_document,
                    d.nom_fichier,
                    d.taille_fichier,
                    d.date_creation,
                    r.id_rapport,
                    r.num_etu AS rapport_num_etu,
                    COALESCE(r.theme_rapport, "") AS theme_rapport,
                    e.num_carte_etud,
                    e.num_ident_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.promotion_etu
                FROM documents d
                INNER JOIN rapport_etudiants r
                    ON CAST(r.id_rapport AS CHAR) = d.entite_id
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

        $sql = 'SELECT
                    d.id_document,
                    d.nom_fichier,
                    d.taille_fichier,
                    d.date_creation,
                    ps.num_soutenance,
                    ps.num_etud,
                    ps.theme_soutenance,
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
        $soutenance = (!$legacySoutenance && $numEtu !== '') ? $this->findLatestSoutenanceByStudent($numEtu) : null;

        return [
            'id_document' => (int) ($row['id_document'] ?? 0),
            'id_rapport' => (int) ($row['id_rapport'] ?? 0),
            'num_etu' => $numEtu,
            'num_soutenance' => $legacySoutenance ? (string) ($row['num_soutenance'] ?? '') : (string) ($soutenance['num_soutenance'] ?? ''),
            'nom_etu' => (string) ($row['nom_etu'] ?? ''),
            'prenom_etu' => (string) ($row['prenom_etu'] ?? ''),
            'nom_etudiant' => trim((string) ($row['nom_etu'] ?? '') . ' ' . (string) ($row['prenom_etu'] ?? '')),
            'matricule' => $numEtu,
            'promotion' => $this->formatPromotion($row),
            'promotion_etu' => (string) ($row['promotion_etu'] ?? ''),
            'theme' => $legacySoutenance ? (string) ($row['theme_soutenance'] ?? '') : (string) ($row['theme_rapport'] ?? ''),
            'theme_soutenance' => $legacySoutenance ? (string) ($row['theme_soutenance'] ?? '') : (string) ($soutenance['theme'] ?? ''),
            'theme_rapport' => $legacySoutenance ? '' : (string) ($row['theme_rapport'] ?? ''),
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
        $rapport = $this->findLatestRapportAvecCompteRenduByStudent($numEtu);
        return is_array($rapport) && (int) ($rapport['id_rapport'] ?? 0) > 0;
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
            $soutenance = $this->findLatestSoutenanceByStudent($numEtu);
            $numSoutenance = trim((string) ($soutenance['num_soutenance'] ?? ''));
            $rapportId = (int) ($memoireContext['id_rapport'] ?? 0);
            $encadrement = $this->resolveEncadrementForMemoire($rapportId > 0 ? $rapportId : null, $numSoutenance);

            $notificationService = new NotificationService($this->db);
            $emailService = $notificationService->getEmailService();
            $baseData = [
                'nom_etudiant' => htmlspecialchars(trim((string) ($memoireContext['nom_etu'] ?? '') . ' ' . (string) ($memoireContext['prenom_etu'] ?? '')) ?: 'Etudiant', ENT_QUOTES, 'UTF-8'),
                'theme' => htmlspecialchars((string) ($memoireContext['theme_rapport'] ?? $memoireContext['theme'] ?? ''), ENT_QUOTES, 'UTF-8'),
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
