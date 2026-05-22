<?php

declare(strict_types=1);

namespace App\Services\Document;

use PDO;

final class DocumentStorageService
{
    private PDO $pdo;
    private string $projectRoot;
    private string $cacheDir;

    /** @var array<string, bool> */
    private array $tableExistsCache = [];

    public function __construct(PDO $pdo, ?string $projectRoot = null)
    {
        $this->pdo = $pdo;
        $this->projectRoot = $projectRoot !== null && $projectRoot !== ''
            ? rtrim($projectRoot, '/\\')
            : dirname(__DIR__, 3);
        $this->cacheDir = $this->projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'documents';
    }

    public function isAvailable(): bool
    {
        return $this->tableExists('documents');
    }

    /**
     * @param array<int, string> $typeDocuments
     * @return array<string, mixed>|null
     */
    public function findLatestByEntity(string $entiteType, string $entiteId, array $typeDocuments = [], ?string $sousType = null, ?string $mimePrefix = null): ?array
    {
        if (!$this->isAvailable() || $entiteType === '' || $entiteId === '') {
            return null;
        }

        $sql = 'SELECT id_document, type_document, sous_type, reference, nom_fichier, extension,
                       type_mime, entite_type, entite_id, contenu, taille_fichier, version,
                       id_utilisateur, date_creation, date_modification, nb_consultations,
                       chemin_original, statut
                FROM documents
                WHERE entite_type = :entite_type
                  AND entite_id = :entite_id
                  AND statut = "actif"';
        $params = [
            ':entite_type' => $entiteType,
            ':entite_id' => $entiteId,
        ];

        if ($typeDocuments !== []) {
            $placeholders = [];
            foreach (array_values($typeDocuments) as $index => $typeDocument) {
                $key = ':type_' . $index;
                $placeholders[] = $key;
                $params[$key] = $typeDocument;
            }
            $sql .= ' AND type_document IN (' . implode(', ', $placeholders) . ')';
        }

        if ($sousType !== null) {
            $sql .= ' AND sous_type = :sous_type';
            $params[':sous_type'] = $sousType;
        }

        if ($mimePrefix !== null && $mimePrefix !== '') {
            $sql .= ' AND type_mime LIKE :mime_prefix';
            $params[':mime_prefix'] = $mimePrefix . '%';
        }

        $sql .= ' ORDER BY version DESC, id_document DESC LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getByReference(string $reference): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id_document, type_document, sous_type, reference, nom_fichier, extension,
                    type_mime, entite_type, entite_id, contenu, taille_fichier, version,
                    id_utilisateur, date_creation, date_modification, nb_consultations,
                    chemin_original, statut
             FROM documents
             WHERE reference = :reference
               AND statut = "actif"
             LIMIT 1'
        );
        $stmt->execute([':reference' => $reference]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function storeDocument(
        string $typeDocument,
        string $nomFichier,
        string $contenu,
        string $mimeType,
        ?string $entiteType = null,
        ?string $entiteId = null,
        ?int $userId = null,
        ?string $reference = null,
        ?string $sousType = null,
        ?string $cheminOriginal = null,
        bool $archiveExisting = false
    ): ?array {
        if (!$this->isAvailable()) {
            return null;
        }

        $typeDocument = trim($typeDocument);
        $nomFichier = trim($nomFichier);
        if ($typeDocument === '' || $nomFichier === '' || $contenu === '') {
            return null;
        }

        if ($archiveExisting && $entiteType !== null && $entiteId !== null) {
            $this->archiveActiveDocuments($entiteType, $entiteId, $typeDocument, $sousType);
        }

        $extension = $this->normalizeExtension($nomFichier, $mimeType);
        $cleanReference = trim((string) $reference);
        if ($cleanReference === '') {
            $cleanReference = $this->generateReference($typeDocument);
        }

        $version = $this->nextVersion($entiteType, $entiteId, $typeDocument, $sousType);
        $size = strlen($contenu);

        $stmt = $this->pdo->prepare(
            'INSERT INTO documents (
                type_document,
                sous_type,
                reference,
                nom_fichier,
                extension,
                type_mime,
                entite_type,
                entite_id,
                contenu,
                taille_fichier,
                version,
                id_utilisateur,
                chemin_original,
                statut
            ) VALUES (
                :type_document,
                :sous_type,
                :reference,
                :nom_fichier,
                :extension,
                :type_mime,
                :entite_type,
                :entite_id,
                :contenu,
                :taille_fichier,
                :version,
                :id_utilisateur,
                :chemin_original,
                "actif"
            )'
        );

        $stmt->bindValue(':type_document', $typeDocument);
        $stmt->bindValue(':sous_type', $sousType);
        $stmt->bindValue(':reference', $cleanReference);
        $stmt->bindValue(':nom_fichier', $nomFichier);
        $stmt->bindValue(':extension', $extension);
        $stmt->bindValue(':type_mime', $mimeType !== '' ? $mimeType : 'application/octet-stream');
        $stmt->bindValue(':entite_type', $entiteType);
        $stmt->bindValue(':entite_id', $entiteId);
        $stmt->bindValue(':contenu', $contenu, PDO::PARAM_LOB);
        $stmt->bindValue(':taille_fichier', $size, PDO::PARAM_INT);
        $stmt->bindValue(':version', $version, PDO::PARAM_INT);
        $stmt->bindValue(':id_utilisateur', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':chemin_original', $cheminOriginal);
        $stmt->execute();

        return $this->getById((int) $this->pdo->lastInsertId());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function storeFileFromPath(
        string $typeDocument,
        string $filePath,
        ?string $entiteType = null,
        ?string $entiteId = null,
        ?int $userId = null,
        ?string $reference = null,
        ?string $sousType = null,
        bool $archiveExisting = false
    ): ?array {
        $realPath = realpath($filePath);
        if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            return null;
        }

        $content = file_get_contents($realPath);
        if (!is_string($content) || $content === '') {
            return null;
        }

        $mimeType = $this->detectMimeType($realPath);

        return $this->storeDocument(
            $typeDocument,
            basename($realPath),
            $content,
            $mimeType,
            $entiteType,
            $entiteId,
            $userId,
            $reference,
            $sousType,
            $realPath,
            $archiveExisting
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForViewer(string $type, string $id): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $type = trim($type);
        $id = trim($id);
        if ($type === '' || $id === '') {
            return null;
        }

        $byReference = $this->getByReference($id);
        if (is_array($byReference) && $this->matchesViewerType($byReference, $type)) {
            return $byReference;
        }

        return match ($type) {
            'rapport' => ctype_digit($id)
                ? ($this->findLatestByEntity('rapport_etudiants', $id, ['rapport'], 'generated', 'application/pdf')
                    ?? $this->findLatestByEntity('rapport_etudiants', $id, ['rapport'], null, 'application/pdf'))
                : null,
            'fiche_inscription' => $this->findLatestByEntity('inscriptions', $id, ['fiche_inscription']),
            'recu' => $this->findLatestByEntity('inscriptions', $id, ['recu']),
            'memoire' => $this->findLatestByEntity('programmer_soutenance', $id, ['memoire'], null, 'application/pdf'),
            'pv_commission' => ctype_digit($id)
                ? $this->findLatestByEntity('compte_rendu', $id, ['pv_commission'], null, 'application/pdf')
                : null,
            'pv_final' => $this->findLatestByEntity('programmer_soutenance', $id, ['pv_final'], null, 'application/pdf'),
            'planning' => $byReference,
            'bulletin' => ctype_digit($id)
                ? $this->findLatestByEntity('compte_rendu', $id, ['bulletin'], null, 'application/pdf')
                : null,
            'compte_rendu' => ctype_digit($id)
                ? $this->findLatestByEntity('compte_rendu', $id, ['compte_rendu'], null, 'application/pdf')
                : null,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $idDocument): ?array
    {
        if (!$this->isAvailable() || $idDocument <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id_document, type_document, sous_type, reference, nom_fichier, extension,
                    type_mime, entite_type, entite_id, contenu, taille_fichier, version,
                    id_utilisateur, date_creation, date_modification, nb_consultations,
                    chemin_original, statut
             FROM documents
             WHERE id_document = :id_document
             LIMIT 1'
        );
        $stmt->execute([':id_document' => $idDocument]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    public function materializeToCache(array $document): ?string
    {
        $content = $document['contenu'] ?? null;
        if (!is_string($content) || $content === '') {
            return null;
        }

        $idDocument = (int) ($document['id_document'] ?? 0);
        $version = (int) ($document['version'] ?? 1);
        $extension = trim((string) ($document['extension'] ?? 'bin'));
        $extension = ltrim($extension, '.');
        if ($extension === '') {
            $extension = 'bin';
        }

        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0775, true) && !is_dir($this->cacheDir)) {
            return null;
        }

        $cachePath = $this->cacheDir . DIRECTORY_SEPARATOR . 'doc_' . $idDocument . '_v' . $version . '.' . $extension;
        if (!is_file($cachePath) || filesize($cachePath) !== strlen($content)) {
            if (file_put_contents($cachePath, $content) === false) {
                return null;
            }
        }

        return $cachePath;
    }

    public function serve(array $document, string $disposition = 'inline', ?int $userId = null, ?string $ip = null): void
    {
        $content = $document['contenu'] ?? null;
        if (!is_string($content) || $content === '') {
            http_response_code(404);
            echo 'Document non trouve';
            exit;
        }

        $filename = trim((string) ($document['nom_fichier'] ?? 'document.bin'));
        if ($filename === '') {
            $filename = 'document.bin';
        }

        header('Content-Type: ' . (string) ($document['type_mime'] ?? 'application/octet-stream'));
        header('Content-Length: ' . strlen($content));
        header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($filename) . '"');
        header('Cache-Control: private, max-age=300');
        header('X-Content-Type-Options: nosniff');

        $this->incrementConsultation((int) ($document['id_document'] ?? 0), $userId, $ip);

        echo $content;
        exit;
    }

    public function incrementConsultation(int $idDocument, ?int $userId = null, ?string $ip = null): void
    {
        if (!$this->isAvailable() || $idDocument <= 0) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE documents
                 SET nb_consultations = nb_consultations + 1
                 WHERE id_document = :id_document'
            );
            $stmt->execute([':id_document' => $idDocument]);

            if ($this->tableExists('documents_consultations')) {
                $logStmt = $this->pdo->prepare(
                    'INSERT INTO documents_consultations (id_document, id_utilisateur, ip)
                     VALUES (:id_document, :id_utilisateur, :ip)'
                );
                $logStmt->bindValue(':id_document', $idDocument, PDO::PARAM_INT);
                $logStmt->bindValue(':id_utilisateur', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $logStmt->bindValue(':ip', $ip);
                $logStmt->execute();
            }
        } catch (\Throwable) {
        }
    }

    private function archiveActiveDocuments(string $entiteType, string $entiteId, string $typeDocument, ?string $sousType = null): void
    {
        if (!$this->isAvailable()) {
            return;
        }

        $sql = 'UPDATE documents
                SET statut = "archive"
                WHERE entite_type = :entite_type
                  AND entite_id = :entite_id
                  AND type_document = :type_document
                  AND statut = "actif"';
        $params = [
            ':entite_type' => $entiteType,
            ':entite_id' => $entiteId,
            ':type_document' => $typeDocument,
        ];

        if ($sousType === null) {
            $sql .= ' AND sous_type IS NULL';
        } else {
            $sql .= ' AND sous_type = :sous_type';
            $params[':sous_type'] = $sousType;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    private function nextVersion(?string $entiteType, ?string $entiteId, string $typeDocument, ?string $sousType = null): int
    {
        if (!$this->isAvailable() || $entiteType === null || $entiteId === null) {
            return 1;
        }

        $sql = 'SELECT COALESCE(MAX(version), 0)
                FROM documents
                WHERE entite_type = :entite_type
                  AND entite_id = :entite_id
                  AND type_document = :type_document';
        $params = [
            ':entite_type' => $entiteType,
            ':entite_id' => $entiteId,
            ':type_document' => $typeDocument,
        ];

        if ($sousType === null) {
            $sql .= ' AND sous_type IS NULL';
        } else {
            $sql .= ' AND sous_type = :sous_type';
            $params[':sous_type'] = $sousType;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();

        return max(1, (int) $value + 1);
    }

    private function matchesViewerType(array $document, string $viewerType): bool
    {
        $typeDocument = trim((string) ($document['type_document'] ?? ''));

        return match ($viewerType) {
            'rapport' => $typeDocument === 'rapport',
            'fiche_inscription' => $typeDocument === 'fiche_inscription',
            'recu' => $typeDocument === 'recu',
            'memoire' => $typeDocument === 'memoire',
            'pv_commission' => $typeDocument === 'pv_commission',
            'pv_final' => $typeDocument === 'pv_final',
            'planning' => $typeDocument === 'planning',
            'bulletin' => $typeDocument === 'bulletin',
            'compte_rendu' => $typeDocument === 'compte_rendu',
            default => false,
        };
    }

    private function detectMimeType(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mimeType) && $mimeType !== '') {
                    return $mimeType;
                }
            }
        }

        return 'application/octet-stream';
    }

    private function normalizeExtension(string $nomFichier, string $mimeType): string
    {
        $extension = strtolower((string) pathinfo($nomFichier, PATHINFO_EXTENSION));
        if ($extension !== '') {
            return '.' . ltrim($extension, '.');
        }

        return match (strtolower($mimeType)) {
            'application/pdf' => '.pdf',
            'text/html', 'text/plain' => '.html',
            'application/msword' => '.doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            default => '.bin',
        };
    }

    private function generateReference(string $typeDocument): string
    {
        $prefix = match (strtolower($typeDocument)) {
            'rapport' => 'RAP',
            'recu' => 'REC',
            'pv_commission' => 'PVC',
            'pv_final' => 'PVF',
            'planning' => 'PLN',
            'compte_rendu' => 'CR',
            'bulletin' => 'BUL',
            'fiche_inscription' => 'FIC',
            'html_doc' => 'HTML',
            default => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $typeDocument) ?: 'DOC', 0, 8)),
        };

        return $prefix . '-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
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
}
