<?php
declare(strict_types=1);

require_once __DIR__ . '/Document/DocumentStorageService.php';
require_once __DIR__ . '/../utils/FormattingUtils.php';

use App\Services\Document\DocumentStorageService;

final class MiseEnLigneMemoireService
{
    private const MAX_FILE_SIZE = 20971520;

    private PDO $db;
    private DocumentStorageService $storage;

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
            return ['success' => false, 'message' => 'Le registre documentaire n’est pas disponible.'];
        }

        $numEtu = trim((string) ($post['num_etu'] ?? ''));
        if ($numEtu === '') {
            return ['success' => false, 'message' => 'Veuillez sélectionner un étudiant.'];
        }

        $soutenance = $this->findLatestSoutenanceByStudent($numEtu);
        if ($soutenance === null) {
            return ['success' => false, 'message' => 'Aucune soutenance trouvée pour cet étudiant.'];
        }

        $file = $files['memoire_pdf'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Veuillez sélectionner un fichier PDF valide.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = trim((string) ($file['name'] ?? 'memoire.pdf'));
        $size = (int) ($file['size'] ?? 0);

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'message' => 'Le fichier uploadé est introuvable.'];
        }

        if ($size <= 0) {
            return ['success' => false, 'message' => 'Le fichier sélectionné est vide.'];
        }

        if ($size > self::MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'Le fichier dépasse la taille maximale autorisée de 20 MB.'];
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return ['success' => false, 'message' => 'Seuls les fichiers PDF sont autorisés.'];
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
            return ['success' => false, 'message' => 'Le fichier transmis n’est pas reconnu comme un PDF.'];
        }

        $content = file_get_contents($tmpName);
        if (!is_string($content) || $content === '') {
            return ['success' => false, 'message' => 'Impossible de lire le fichier transmis.'];
        }

        $safeStudent = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($soutenance['num_etu'] ?? $numEtu));
        $safeDate = preg_replace('/[^0-9-]/', '', (string) ($soutenance['date_soutenance'] ?? date('Y-m-d')));
        $storedName = 'memoire_' . $safeStudent . '_' . ($safeDate !== '' ? $safeDate : date('Y-m-d')) . '.pdf';

        $document = $this->storage->storeDocument(
            'memoire',
            $storedName,
            $content,
            'application/pdf',
            'programmer_soutenance',
            (string) $soutenance['num_soutenance'],
            $userId,
            null,
            null,
            $originalName,
            true
        );

        if (!is_array($document)) {
            return ['success' => false, 'message' => 'L’enregistrement du mémoire a échoué.'];
        }

        return ['success' => true, 'message' => 'Mémoire mis en ligne avec succès.'];
    }

    public function supprimerMemoire(string $numEtu): array
    {
        if (!$this->storage->isAvailable()) {
            return ['success' => false, 'message' => 'Le registre documentaire n’est pas disponible.'];
        }

        $document = $this->findActiveMemoireByStudent($numEtu);
        if ($document === null) {
            return ['success' => false, 'message' => 'Aucun mémoire actif à supprimer.'];
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

        return ['success' => true, 'message' => 'Mémoire supprimé avec succès.'];
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
                WHERE 1 = 1';

        $params = [];
        $anneeId = $this->getSelectedAcademicYearId();
        if ($anneeId !== null) {
            $sql .= ' AND ps.id_annee_acad = :annee_id';
            $params[':annee_id'] = $anneeId;
        }

        $sql .= ' ORDER BY ps.date_soutenance DESC, ps.num_soutenance DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $seen = [];
        $items = [];
        foreach ($rows as $row) {
            $numEtu = trim((string) ($row['num_ident_etud'] ?? $row['num_carte_etud'] ?? $row['num_etud'] ?? ''));
            if ($numEtu === '' || isset($seen[$numEtu])) {
                continue;
            }
            $seen[$numEtu] = true;

            $items[] = [
                'num_etu' => $numEtu,
                'num_soutenance' => (string) ($row['num_soutenance'] ?? ''),
                'nom_complet' => trim((string) ($row['nom_etu'] ?? '') . ' ' . (string) ($row['prenom_etu'] ?? '')),
                'promotion' => $this->formatPromotion($row),
                'theme' => (string) ($row['theme_soutenance'] ?? ''),
                'date_soutenance' => (string) ($row['date_soutenance'] ?? ''),
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
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $numEtu = trim((string) ($row['num_ident_etud'] ?? $row['num_carte_etud'] ?? $row['num_etud'] ?? ''));
            $items[] = [
                'id_document' => (int) ($row['id_document'] ?? 0),
                'num_etu' => $numEtu,
                'num_soutenance' => (string) ($row['num_soutenance'] ?? ''),
                'nom_etudiant' => trim((string) ($row['nom_etu'] ?? '') . ' ' . (string) ($row['prenom_etu'] ?? '')),
                'matricule' => $numEtu,
                'promotion' => $this->formatPromotion($row),
                'theme' => (string) ($row['theme_soutenance'] ?? ''),
                'fichier' => (string) ($row['nom_fichier'] ?? ''),
                'date_depot' => (string) ($row['date_creation'] ?? ''),
                'taille' => $this->formatFileSize((int) ($row['taille_fichier'] ?? 0)),
            ];
        }

        return $items;
    }

    private function findLatestSoutenanceByStudent(string $numEtu): ?array
    {
        $sql = 'SELECT
                    ps.num_soutenance,
                    ps.num_etud,
                    ps.theme_soutenance,
                    ps.date_soutenance,
                    e.num_carte_etud,
                    e.num_ident_etud
                FROM programmer_soutenance ps
                INNER JOIN etudiants e
                    ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
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
        ];
    }

    private function findActiveMemoireByStudent(string $numEtu): ?array
    {
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
}
