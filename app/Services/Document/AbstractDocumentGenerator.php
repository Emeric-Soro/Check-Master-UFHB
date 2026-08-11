<?php

declare(strict_types=1);

namespace App\Services\Document;

use PDO;

/**
 * Implémentation de base des générateurs de documents.
 *
 * Factorise les opérations communes à tous les générateurs :
 *  - nommage des fichiers (type + date + référence) ;
 *  - stockage via DocumentStorageService (si disponible) ;
 *  - enregistrement du fichier physique sous storage/documents/<type>/ ;
 *  - journalisation ;
 *  - diffusion HTTP sécurisée.
 *
 * Les générateurs historiques conservent leur logique de mise en page
 * mais peuvent hériter de cette base pour le stockage et la diffusion.
 */
abstract class AbstractDocumentGenerator implements DocumentGeneratorContract
{
    protected string $documentsDir;
    protected string $logoPath;
    protected ?DocumentStorageService $storage = null;
    protected ?PDO $pdo = null;

    public function __construct(
        string $documentsDir,
        string $logoPath = '',
        ?DocumentStorageService $storage = null,
        ?PDO $pdo = null
    ) {
        $this->documentsDir = rtrim($documentsDir, '/\\');
        $this->logoPath = $logoPath;
        $this->storage = $storage;
        $this->pdo = $pdo;
    }

    /**
     * Nom de fichier standardisé : <type>_<date>_<ref>.<ext>
     */
    protected function buildFilename(string $extension, string $reference = ''): string
    {
        $type = preg_replace('/[^a-z0-9_-]/i', '', $this->documentType()) ?: 'document';
        $ref = $reference !== ''
            ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $reference)
            : '';
        return $type . '_' . date('Ymd_His') . $ref . '.' . ltrim($extension, '.');
    }

    /**
     * Sous-répertoire de stockage par type (ex: recus/, pv_final/...).
     */
    protected function subdir(): string
    {
        $type = preg_replace('/[^a-z0-9_-]/i', '', $this->documentType()) ?: 'documents';
        return $type . 's';
    }

    /**
     * Écrit le fichier sur disque sous storage/documents/<sous-type>/.
     *
     * @return string|null Chemin absolu du fichier écrit.
     */
    protected function writeToDisk(string $content, string $filename): ?string
    {
        $dir = $this->documentsDir . DIRECTORY_SEPARATOR . $this->subdir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (@file_put_contents($path, $content) === false) {
            return null;
        }

        return $path;
    }

    /**
     * Enregistre le document dans la table `documents` (si disponible).
     *
     * @return array<string,mixed>|null Ligne du document enregistré.
     */
    protected function persistDocument(
        string $typeDocument,
        string $filename,
        string $content,
        string $mimeType,
        ?string $entiteType = null,
        ?string $entiteId = null,
        ?int $userId = null,
        ?string $reference = null
    ): ?array {
        if ($this->storage === null) {
            return null;
        }
        return $this->storage->storeDocument(
            $typeDocument,
            $filename,
            $content,
            $mimeType,
            $entiteType,
            $entiteId,
            $userId,
            $reference
        );
    }

    /**
     * Construit le tableau de résultat standard.
     */
    protected function result(
        bool $success,
        array $extra = [],
        string $error = ''
    ): array {
        return array_merge(
            ['success' => $success, 'error' => $error],
            $extra
        );
    }

    /**
     * Journalise une opération de génération.
     */
    protected function log(string $message, array $context = []): void
    {
        error_log('[DocumentGenerator:' . $this->documentType() . '] ' . $message
            . ($context !== [] ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''));
    }

    /**
     * Diffusion HTTP sécurisée d'un fichier généré.
     */
    public function serve(array $result): void
    {
        $path = (string) ($result['path'] ?? '');
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            http_response_code(404);
            echo 'Document non trouvé';
            exit;
        }

        $filename = (string) ($result['filename'] ?? basename($path));
        $mime = $this->guessMime($path);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '', $filename) . '"');
        header('Cache-Control: private, max-age=300');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    private function guessMime(string $path): string
    {
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'html', 'htm' => 'text/html; charset=UTF-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }
}
