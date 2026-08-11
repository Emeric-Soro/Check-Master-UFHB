<?php
declare(strict_types=1);

require_once __DIR__ . '/DocumentStorageService.php';

use App\Services\Document\DocumentStorageService;

/**
 * Registry centralise des types de documents.
 * Il resout les chemins PDF a partir du schema reel, des references generees
 * et des chemins historiques encore presents dans la base.
 */
final class DocumentRegistry
{
    /**
     * @var array<string, array{codes: array<int, string>, subdir?: string}>
     */
    private const TYPE_CONFIG = [
        'rapport' => [
            'codes' => ['RAP'],
            'subdir' => 'rapports',
        ],
        'fiche_inscription' => [
            'codes' => ['FIC'],
            'subdir' => 'fiches',
        ],
        'recu' => [
            'codes' => ['REC'],
            'subdir' => 'recus',
        ],
        'memoire' => [
            'codes' => ['MEM', 'MEMOIRE'],
            'subdir' => 'memoires',
        ],
        'pv_commission' => [
            'codes' => ['PVC'],
            'subdir' => 'pv_commission',
        ],
        'pv_final' => [
            'codes' => ['PVF', 'PV_FINAL'],
            'subdir' => 'pv_finaux',
        ],
        'pv_ecrits' => [
            'codes' => ['PV_EPREUVES_ECRITES', 'PVE'],
            'subdir' => 'pv_ecrits',
        ],
        'autorisation_soutenance' => [
            'codes' => ['AUT_SOUT'],
            'subdir' => 'autorisation_soutenance',
        ],
        'suivi_encadrement' => [
            'codes' => ['SUIVI_ENC'],
            'subdir' => 'suivi_encadrement',
        ],
        'planning' => [
            'codes' => ['PLN'],
            'subdir' => 'planning',
        ],
        'bulletin' => [
            'codes' => ['BUL'],
        ],
        'compte_rendu' => [
            'codes' => ['CR'],
        ],
    ];

    private PDO $pdo;
    private string $projectRoot;
    private string $storagePath;
    private string $documentsPath;
    private string $uploadsPath;
    private DocumentStorageService $documentStorage;

    /** @var array<string, bool> */
    private array $tableExistsCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->projectRoot = dirname(__DIR__, 3);
        $this->storagePath = $this->projectRoot . DIRECTORY_SEPARATOR . 'storage';
        $this->documentsPath = $this->storagePath . DIRECTORY_SEPARATOR . 'documents';
        $this->uploadsPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'uploads';
        $this->documentStorage = new DocumentStorageService($pdo, $this->projectRoot);
    }

    public function resolve(string $type, string $id): ?string
    {
        $type = trim($type);
        $id = trim($id);

        if (!isset(self::TYPE_CONFIG[$type]) || $id === '' || $this->containsPathTraversal($id)) {
            return null;
        }

        $storedDocument = $this->getStoredDocument($type, $id);
        if (is_array($storedDocument)) {
            $cachedPath = $this->documentStorage->materializeToCache($storedDocument);
            if (is_string($cachedPath) && $cachedPath !== '' && is_file($cachedPath)) {
                return $cachedPath;
            }
        }

        return match ($type) {
            'rapport' => $this->resolveRapport($id),
            'fiche_inscription' => $this->resolveFicheInscriptionDocument($id),
            'recu' => $this->resolveGeneratedDocument($type, $id),
            'memoire' => $this->resolveMemoire($id),
            'pv_commission' => $this->resolveCompteRenduDocument($id, false, true),
            'pv_final' => $this->resolvePvFinal($id),
            'pv_ecrits', 'autorisation_soutenance', 'suivi_encadrement' => $this->resolveGeneratedDocument($type, $id),
            'planning' => $this->resolveGeneratedDocument($type, $id),
            'bulletin' => $this->resolveCompteRenduDocument($id, true, false),
            'compte_rendu' => $this->resolveCompteRenduDocument($id, false, false),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStoredDocument(string $type, string $id): ?array
    {
        return $this->documentStorage->findForViewer($type, $id);
    }

    public function hasDocument(string $type, string $id): bool
    {
        if ($this->getStoredDocument($type, $id) !== null) {
            return true;
        }

        return $this->resolve($type, $id) !== null;
    }

    public function canView(string $type, string $id): bool
    {
        if (!isset($_SESSION['id_GU'])) {
            return false;
        }

        $userGroup = (int) ($_SESSION['id_GU'] ?? 0);

        if (in_array($userGroup, [5, 6, 7, 8, 9, 10, 11, 14], true)) {
            return true;
        }

        if ($userGroup === 12) {
            return in_array($type, ['rapport', 'fiche_inscription', 'memoire', 'pv_commission', 'pv_final', 'pv_ecrits', 'autorisation_soutenance', 'suivi_encadrement', 'planning', 'compte_rendu', 'bulletin'], true);
        }

        if ($userGroup === 13) {
            return $this->isOwner($type, $id);
        }

        return false;
    }

    public function generateReference(string $type): string
    {
        $year = date('Y');
        $seq = (int) (microtime(true) * 100) % 99999;
        $num = str_pad((string) max($seq, 1), 5, '0', STR_PAD_LEFT);

        return strtoupper($type) . '-' . $year . '-' . $num;
    }

    /**
     * @return array<int, string>
     */
    public function getTypeCodes(string $type): array
    {
        return self::TYPE_CONFIG[$type]['codes'] ?? [];
    }

    private function resolveRapport(string $id): ?string
    {
        if (ctype_digit($id)) {
            $stmt = $this->pdo->prepare(
                'SELECT chemin_fichier, num_etu
                 FROM rapport_etudiants
                 WHERE id_rapport = :id
                 LIMIT 1'
            );
            $stmt->execute([':id' => (int) $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (is_array($row)) {
                $resolved = $this->resolveStoredPdfPath((string) ($row['chemin_fichier'] ?? ''));
                if ($resolved !== null) {
                    return $resolved;
                }

                $matricule = trim((string) ($row['num_etu'] ?? ''));
                if ($matricule !== '') {
                    $match = $this->findLatestFileByFragments('rapports', [
                        'rapport_' . $this->sanitizeFilenameFragment($matricule),
                        'rapport_' . $matricule,
                    ]);
                    if ($match !== null) {
                        return $match;
                    }
                }
            }
        }

        return $this->resolveGeneratedDocument('rapport', $id);
    }

    private function resolveFicheInscriptionDocument(string $id): ?string
    {
        [$numCarteEtud, $idAnneeAcad, $numVersement] = $this->parseInscriptionDocumentId($id);
        if ($numCarteEtud === null || $idAnneeAcad === null || $numVersement === null) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT fiche_inscription
             FROM inscriptions
             WHERE num_carte_etud = :num_carte_etud
               AND id_annee_acad = :id_annee_acad
               AND num_versement = :num_versement
             LIMIT 1'
        );
        $stmt->execute([
            ':num_carte_etud' => $numCarteEtud,
            ':id_annee_acad' => $idAnneeAcad,
            ':num_versement' => $numVersement,
        ]);
        $storedPath = $stmt->fetchColumn();

        return is_string($storedPath) && $storedPath !== ''
            ? $this->resolveStoredFilePath($storedPath)
            : null;
    }

    private function resolveCompteRenduDocument(string $id, bool $bulletinOnly, bool $preferGenerated): ?string
    {
        if (ctype_digit($id)) {
            $stmt = $this->pdo->prepare(
                'SELECT nom_CR, chemin_fichier_pdf
                 FROM compte_rendu
                 WHERE id_CR = :id
                 LIMIT 1'
            );
            $stmt->execute([':id' => (int) $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (is_array($row)) {
                $isBulletin = str_starts_with((string) ($row['nom_CR'] ?? ''), 'BULLETIN_');
                if ($bulletinOnly !== $isBulletin) {
                    return null;
                }

                if ($preferGenerated) {
                    $generated = $this->resolveGeneratedDocument('pv_commission', $id);
                    if ($generated !== null) {
                        return $generated;
                    }
                }

                $resolved = $this->resolveStoredPdfPath((string) ($row['chemin_fichier_pdf'] ?? ''));
                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        return $this->resolveGeneratedDocument($bulletinOnly ? 'bulletin' : ($preferGenerated ? 'pv_commission' : 'compte_rendu'), $id);
    }

    private function resolveMemoire(string $id): ?string
    {
        $generated = $this->resolveGeneratedDocument('memoire', $id);
        if ($generated !== null) {
            return $generated;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(NULLIF(e.num_carte_etud, \'\'), NULLIF(e.num_ident_etud, \'\'), ps.num_etud) AS matricule
             FROM programmer_soutenance ps
             LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
             WHERE ps.num_soutenance = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $matricule = trim((string) ($stmt->fetchColumn() ?: ''));

        if ($matricule !== '') {
            return $this->findLatestFileByFragments('memoires', [
                'memoire_' . $this->sanitizeFilenameFragment($matricule),
                'memoire_' . $matricule,
                $id,
            ]);
        }

        return $this->findLatestFileByFragments('memoires', [$id]);
    }

    private function resolvePvFinal(string $id): ?string
    {
        $generated = $this->resolveGeneratedDocument('pv_final', $id);
        if ($generated !== null) {
            return $generated;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(NULLIF(e.num_carte_etud, \'\'), NULLIF(e.num_ident_etud, \'\'), ps.num_etud) AS matricule
             FROM programmer_soutenance ps
             LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
             WHERE ps.num_soutenance = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $matricule = trim((string) ($stmt->fetchColumn() ?: ''));

        if ($matricule !== '') {
            return $this->findLatestFileByFragments('pv_finaux', [
                'PV_Final_' . $this->sanitizeFilenameFragment($matricule),
                'PV_Final_' . $matricule,
            ]);
        }

        return null;
    }

    private function resolveGeneratedDocument(string $type, string $id): ?string
    {
        $subdir = self::TYPE_CONFIG[$type]['subdir'] ?? null;
        if ($subdir === null) {
            return null;
        }

        $decodedPath = $this->resolveOpaqueToken($id, $subdir);
        if ($decodedPath !== null) {
            return $decodedPath;
        }

        if ($this->tableExists('document_genere')) {
            $codes = $this->getTypeCodes($type);
            if ($codes !== []) {
                $placeholders = implode(', ', array_fill(0, count($codes), '?'));
                $sql = "SELECT chemin_fichier
                        FROM document_genere
                        WHERE (reference = ? OR id_source = ?)
                          AND type_document IN ($placeholders)
                        ORDER BY date_generation DESC, id_document DESC
                        LIMIT 1";
                $params = array_merge([$id, $id], $codes);
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $storedPath = $stmt->fetchColumn();
                if (is_string($storedPath) && $storedPath !== '') {
                    $resolved = $this->resolveStoredPdfPath($storedPath);
                    if ($resolved !== null) {
                        return $resolved;
                    }
                }
            }
        }

        return $this->findGeneratedFileByReference($subdir, $id);
    }

    private function resolveStoredPdfPath(string $storedPath): ?string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return null;
        }

        if (strtolower((string) pathinfo($storedPath, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        $candidates = [];
        if ($this->isAbsolutePath($storedPath)) {
            $candidates[] = $storedPath;
        } else {
            $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR);
            $candidates[] = $this->projectRoot . DIRECTORY_SEPARATOR . $relativePath;
            $candidates[] = $this->storagePath . DIRECTORY_SEPARATOR . $relativePath;
            $candidates[] = $this->documentsPath . DIRECTORY_SEPARATOR . $relativePath;
            $candidates[] = $this->uploadsPath . DIRECTORY_SEPARATOR . $relativePath;
        }

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if ($realPath === false || !is_file($realPath)) {
                continue;
            }

            if ($this->isAllowedPdfPath($realPath)) {
                return $realPath;
            }
        }

        return null;
    }

    private function resolveStoredFilePath(string $storedPath): ?string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return null;
        }

        $candidates = [];
        if ($this->isAbsolutePath($storedPath)) {
            $candidates[] = $storedPath;
        } else {
            $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR);
            $candidates[] = $this->projectRoot . DIRECTORY_SEPARATOR . $relativePath;
            $candidates[] = $this->storagePath . DIRECTORY_SEPARATOR . $relativePath;
            $candidates[] = $this->documentsPath . DIRECTORY_SEPARATOR . $relativePath;
            $candidates[] = $this->uploadsPath . DIRECTORY_SEPARATOR . $relativePath;
        }

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if ($realPath === false || !is_file($realPath)) {
                continue;
            }

            if ($this->isAllowedStoredPath($realPath)) {
                return $realPath;
            }
        }

        return null;
    }

    private function resolveOpaqueToken(string $id, string $subdir): ?string
    {
        $decoded = $this->decodeOpaqueToken($id);
        if ($decoded === null) {
            return null;
        }

        // Defence-in-depth: reject strings with null bytes before realpath().
        if (str_contains($decoded, "\0")) {
            return null;
        }

        $realPath = realpath($decoded);
        if ($realPath === false || !is_file($realPath)) {
            return null;
        }

        foreach ($this->getAllowedBaseDirs($subdir) as $baseDir) {
            if ($this->isPathInside($realPath, $baseDir)) {
                return $realPath;
            }
        }

        return null;
    }

    private function findGeneratedFileByReference(string $subdir, string $id): ?string
    {
        $fragments = [$id];
        if (!str_ends_with(strtolower($id), '.pdf')) {
            $fragments[] = $id . '.pdf';
        }

        return $this->findLatestFileByFragments($subdir, $fragments);
    }

    /**
     * @param array<int, string> $fragments
     */
    private function findLatestFileByFragments(string $subdir, array $fragments): ?string
    {
        $matches = [];

        foreach ($this->getAllowedBaseDirs($subdir) as $baseDir) {
            foreach ($this->collectPdfFiles($baseDir) as $filePath) {
                $filename = basename($filePath);
                $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

                foreach ($fragments as $fragment) {
                    if ($fragment === '') {
                        continue;
                    }

                    $normalizedFragment = str_ends_with(strtolower($fragment), '.pdf')
                        ? pathinfo($fragment, PATHINFO_FILENAME)
                        : $fragment;

                    if (
                        strcasecmp($filenameWithoutExt, $normalizedFragment) === 0
                        || strcasecmp($filename, $normalizedFragment . '.pdf') === 0
                        || str_starts_with($filename, $normalizedFragment . '_')
                        || str_starts_with($filename, $normalizedFragment . '-')
                    ) {
                        $matches[$filePath] = filemtime($filePath) ?: 0;
                        break;
                    }
                }
            }
        }

        if ($matches === []) {
            return null;
        }

        arsort($matches, SORT_NUMERIC);
        $path = (string) array_key_first($matches);
        return is_file($path) ? $path : null;
    }

    /**
     * @return array<int, string>
     */
    private function collectPdfFiles(string $baseDir): array
    {
        $realBase = realpath($baseDir);
        if ($realBase === false || !is_dir($realBase)) {
            return [];
        }

        $files = [];
        $entries = @scandir($realBase);
        if (!is_array($entries)) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $candidate = $realBase . DIRECTORY_SEPARATOR . $entry;
            if (is_file($candidate) && strcasecmp((string) pathinfo($candidate, PATHINFO_EXTENSION), 'pdf') === 0) {
                $files[] = $candidate;
                continue;
            }

            if (!is_dir($candidate)) {
                continue;
            }

            $nestedFiles = @glob($candidate . DIRECTORY_SEPARATOR . '*.pdf');
            if (is_array($nestedFiles)) {
                foreach ($nestedFiles as $nestedFile) {
                    if (is_file($nestedFile)) {
                        $files[] = $nestedFile;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function getAllowedBaseDirs(string $subdir): array
    {
        return array_values(array_unique([
            $this->documentsPath . DIRECTORY_SEPARATOR . $subdir,
            $this->storagePath . DIRECTORY_SEPARATOR . $subdir,
            $this->storagePath,
            $this->uploadsPath . DIRECTORY_SEPARATOR . $subdir,
        ]));
    }

    private function isOwner(string $type, string $id): bool
    {
        $studentNum = trim((string) ($_SESSION['num_etu'] ?? ''));
        if ($studentNum === '') {
            return false;
        }

        return match ($type) {
            'rapport' => $this->existsForStudent(
                'SELECT 1 FROM rapport_etudiants WHERE id_rapport = :id AND num_etu = :etu LIMIT 1',
                $id,
                $studentNum
            ),
            'compte_rendu', 'bulletin', 'pv_commission' => $this->existsForStudent(
                'SELECT 1
                 FROM compte_rendu cr
                 LEFT JOIN compte_rendu_rapport crr ON crr.id_CR = cr.id_CR
                 LEFT JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
                 LEFT JOIN etudiants e ON (e.num_carte_etud = cr.num_etu OR e.num_ident_etud = cr.num_etu)
                 LEFT JOIN etudiants er ON (er.num_carte_etud = r.num_etu OR er.num_ident_etud = r.num_etu)
                 WHERE cr.id_CR = :id
                   AND (
                        cr.num_etu = :etu
                        OR r.num_etu = :etu
                        OR e.num_carte_etud = :etu
                        OR e.num_ident_etud = :etu
                        OR er.num_carte_etud = :etu
                        OR er.num_ident_etud = :etu
                   )
                 LIMIT 1',
                $id,
                $studentNum
            ),
            'recu' => $this->existsForStudent(
                'SELECT 1
                 FROM inscriptions i
                 LEFT JOIN etudiants e ON (e.num_carte_etud = i.num_carte_etud OR e.num_ident_etud = i.num_carte_etud)
                 WHERE CONCAT(i.num_carte_etud, \'-\', i.id_annee_acad, \'-\', i.num_versement) = :id
                   AND (i.num_carte_etud = :etu OR e.num_carte_etud = :etu OR e.num_ident_etud = :etu)
                 LIMIT 1',
                $id,
                $studentNum
            ),
            'fiche_inscription' => $this->existsForStudent(
                'SELECT 1
                 FROM inscriptions i
                 LEFT JOIN etudiants e ON (e.num_carte_etud = i.num_carte_etud OR e.num_ident_etud = i.num_carte_etud)
                 WHERE CONCAT(i.num_carte_etud, \'-\', i.id_annee_acad, \'-\', i.num_versement) = :id
                   AND (i.num_carte_etud = :etu OR e.num_carte_etud = :etu OR e.num_ident_etud = :etu)
                 LIMIT 1',
                $id,
                $studentNum
            ),
            'pv_final' => $this->existsForStudent(
                'SELECT 1
                 FROM programmer_soutenance ps
                 LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                 WHERE ps.num_soutenance = :id
                   AND (ps.num_etud = :etu OR e.num_carte_etud = :etu OR e.num_ident_etud = :etu)
                 LIMIT 1',
                $id,
                $studentNum
            ),
            'pv_ecrits', 'autorisation_soutenance', 'suivi_encadrement' => $this->generatedDocumentBelongsToStudent(
                $type,
                $id,
                $studentNum
            ),
            'memoire' => $this->existsForStudent(
                'SELECT 1
                 FROM programmer_soutenance ps
                 LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                 WHERE ps.num_soutenance = :id
                   AND (ps.num_etud = :etu OR e.num_carte_etud = :etu OR e.num_ident_etud = :etu)
                 LIMIT 1',
                $id,
                $studentNum
            ),
            default => false,
        };
    }

    private function generatedDocumentBelongsToStudent(string $type, string $id, string $studentNum): bool
    {
        if (!$this->tableExists('document_genere')) {
            return false;
        }

        $codes = $this->getTypeCodes($type);
        if ($codes === []) {
            return false;
        }

        $placeholders = implode(', ', array_fill(0, count($codes), '?'));
        $sql = "SELECT 1
                FROM document_genere
                WHERE type_document IN ($placeholders)
                  AND (reference = ? OR id_source = ?)
                  AND id_source LIKE CONCAT(?, ':%')
                LIMIT 1";
        $params = array_merge($codes, [$id, $id, $studentNum]);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function existsForStudent(string $sql, string $id, string $studentNum): bool
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':etu' => $studentNum,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function tableExists(string $tableName): bool
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = :table_name'
            );
            $stmt->execute([':table_name' => $tableName]);
            $exists = (int) $stmt->fetchColumn() > 0;
            $this->tableExistsCache[$tableName] = $exists;
            return $exists;
        } catch (\Throwable) {
            $this->tableExistsCache[$tableName] = false;
            return false;
        }
    }

    private function decodeOpaqueToken(string $token): ?string
    {
        // Must be a non-empty token containing only URL-safe base64 characters
        if ($token === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
            return null;
        }

        // Never attempt to decode short tokens: they are always regular IDs
        // (references like "PLN-2025-12345", compound keys like "MATRICULE-ANNEE-VERS",
        // numeric IDs, etc.), never base64-encoded paths.
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

        // Reject decoded strings containing null bytes — they would crash realpath().
        if (str_contains($decoded, "\0")) {
            return null;
        }

        // The decoded result must look like a plausible file path:
        // it must contain at least one directory separator to be a path.
        $hasSeparator = str_contains($decoded, '/') || str_contains($decoded, '\\');
        if (!$hasSeparator) {
            return null;
        }

        return $decoded;
    }

    private function containsPathTraversal(string $path): bool
    {
        return str_contains($path, '..') || str_contains($path, "\0");
    }

    private function isAbsolutePath(string $path): bool
    {
        return $path !== '' && (
            $path[0] === '/'
            || $path[0] === '\\'
            || (strlen($path) >= 2 && $path[1] === ':')
        );
    }

    private function isAllowedPdfPath(string $path): bool
    {
        if (!is_file($path) || strcasecmp((string) pathinfo($path, PATHINFO_EXTENSION), 'pdf') !== 0) {
            return false;
        }

        return $this->isAllowedStoredPath($path);
    }

    private function isAllowedStoredPath(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        foreach ([$this->storagePath, $this->documentsPath, $this->uploadsPath] as $baseDir) {
            if ($this->isPathInside($path, $baseDir)) {
                return true;
            }
        }

        return false;
    }

    private function isPathInside(string $path, string $basePath): bool
    {
        $realPath = realpath($path);
        $realBase = realpath($basePath);
        if ($realPath === false || $realBase === false) {
            return false;
        }

        $normalizedPath = str_replace('\\', '/', $realPath);
        $normalizedBase = rtrim(str_replace('\\', '/', $realBase), '/') . '/';

        return strncmp($normalizedPath, $normalizedBase, strlen($normalizedBase)) === 0;
    }

    private function sanitizeFilenameFragment(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value);
        return is_string($clean) ? trim($clean, '_') : '';
    }

    /**
     * @return array{0: ?string, 1: ?int, 2: ?int}
     */
    private function parseInscriptionDocumentId(string $id): array
    {
        $parts = preg_split('/[-|]/', $id);
        if (!is_array($parts) || count($parts) < 3) {
            return [null, null, null];
        }

        $numVersement = array_pop($parts);
        $idAnneeAcad = array_pop($parts);
        $numCarteEtud = implode('-', $parts);

        if ($numCarteEtud === '' || !is_numeric($idAnneeAcad) || !is_numeric($numVersement)) {
            return [null, null, null];
        }

        return [$numCarteEtud, (int) $idAnneeAcad, (int) $numVersement];
    }
}
